<?php
/**
 * tFPDF (UTF-8 FPDF)
 * This is a modified version of FPDF that supports UTF-8 and TrueType Unicode fonts.
 */

require('fpdf.php');

class tFPDF extends FPDF
{
	protected $unifontSubset;
	protected $CurrentFont;

	function AddFont($family, $style='', $file='', $uni=false)
	{
		if($file=='')
			$file = str_replace(' ', '', $family).strtolower($style).'.php';
		if($uni)
		{
			if(defined('FPDF_FONTPATH'))
				$file = FPDF_FONTPATH.$file;
			$fontkey = strtolower($family).strtoupper($style);
			include($file);
			if(!isset($name))
				$this->Error('Could not include font definition file');
			$i = count($this->fonts)+1;
			$this->fonts[$fontkey] = array('i'=>$i, 'type'=>'TTF', 'name'=>$name, 'desc'=>$desc, 'up'=>$up, 'ut'=>$ut, 'cw'=>$cw, 'enc'=>$enc, 'file'=>$file, 'ctg'=>$ctg);
			if(isset($diff))
				$this->fonts[$fontkey]['diff'] = $diff;
			if(isset($originalsize))
				$this->fonts[$fontkey]['originalsize'] = $originalsize;
			$this->FontFiles[$file] = array('length1'=>$originalsize);
			unset($cw);
		}
		else
			parent::AddFont($family, $style, $file);
	}

	function GetStringWidth($s)
	{
		if($this->CurrentFont['type']=='TTF')
		{
			$w = 0;
			$l = mb_strlen($s,'UTF-8');
			for($i=0;$i<$l;$i++)
			{
				$c = mb_substr($s,$i,1,'UTF-8');
				$c = $this->UTF8ToUTF16BE($c, false);
				$w += $this->CurrentFont['cw'][ord($c[0])<<8|ord($c[1])];
			}
			return $w*$this->FontSize/1000;
		}
		else
			return parent::GetStringWidth($s);
	}

	function AddPage($orientation='', $size='', $rotation=0)
	{
		parent::AddPage($orientation,$size,$rotation);
		if($this->unifontSubset)
			$this->unifontSubset = array();
	}

	function SetFont($family, $style='', $size=0)
	{
		if($family=='')
			$family = $this->FontFamily;
		$family = strtolower($family);
		$style = strtoupper($style);
		$fontkey = $family.$style;
		if(!isset($this->fonts[$fontkey]))
		{
			if($family=='arial')
				$family = 'helvetica';
			$fontkey = $family.$style;
			if(!isset($this->fonts[$fontkey]))
				$this->Error("Undefined font: $family $style");
		}
		$this->FontFamily = $family;
		$this->FontStyle = $style;
		if($size==0)
			$size = $this->FontSizePt;
		$this->FontSizePt = $size;
		$this->FontSize = $size/72*25.4;
		$this->CurrentFont = &$this->fonts[$fontkey];
		if($this->page>0)
			$this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
	}

	function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
	{
		if($this->CurrentFont['type']=='TTF')
			$txt = $this->UTF8ToUTF16BE($txt, false);
		parent::Cell($w,$h,$txt,$border,$ln,$align,$fill,$link);
	}

	function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false)
	{
		if($this->CurrentFont['type']=='TTF')
			$txt = $this->UTF8ToUTF16BE($txt, false);
		parent::MultiCell($w,$h,$txt,$border,$align,$fill);
	}

	function Write($h, $txt, $link='')
	{
		if($this->CurrentFont['type']=='TTF')
			$txt = $this->UTF8ToUTF16BE($txt, false);
		parent::Write($h,$txt,$link);
	}

	function Text($x, $y, $txt)
	{
		if($this->CurrentFont['type']=='TTF')
			$txt = $this->UTF8ToUTF16BE($txt, false);
		parent::Text($x,$y,$txt);
	}

	function UTF8ToUTF16BE($str, $setbom=true)
	{
		$outstr = $setbom ? "\xFE\xFF" : '';
		$len = strlen($str);
		for($i=0;$i<$len;$i++)
		{
			$c = ord($str[$i]);
			if($c<0x80)
			{
				$outstr .= "\x00".chr($c);
			}
			elseif($c<0xE0)
			{
				$cc1 = ord($str[++$i]);
				$outstr .= chr(($c>>2)&0x07).chr((($c<<6)&0xC0)|($cc1&0x3F));
			}
			elseif($c<0xF0)
			{
				$cc1 = ord($str[++$i]);
				$cc2 = ord($str[++$i]);
				$outstr .= chr((($c<<4)|($cc1>>2))&0xFF).chr((($cc1<<6)|($cc2&0x3F))&0xFF);
			}
			else
			{
				$cc1 = ord($str[++$i]);
				$cc2 = ord($str[++$i]);
				$cc3 = ord($str[++$i]);
				$cp = (($c&0x07)<<18)|(($cc1&0x3F)<<12)|(($cc2&0x3F)<<6)|($cc3&0x3F);
				$cp -= 0x10000;
				$outstr .= chr(0xD8|($cp>>18)).chr(($cp>>10)&0xFF).chr(0xDC|(($cp>>8)&0x03)).chr($cp&0xFF);
			}
		}
		return $outstr;
	}
}
?>