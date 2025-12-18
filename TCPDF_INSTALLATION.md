# TCPDF Installation Instructions

## Install TCPDF for Chinese Character Support

### Option 1: Using Composer (Recommended)

```bash
cd /home/mlxysf/wushusportacademy.app.tc/state
composer require tecnickcom/tcpdf
```

### Option 2: Manual Installation

```bash
cd /home/mlxysf/wushusportacademy.app.tc/state
mkdir -p vendor/tecnickcom
cd vendor/tecnickcom
git clone https://github.com/tecnickcom/TCPDF.git tcpdf
```

### Option 3: Download ZIP

1. Download TCPDF from: https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip
2. Extract to: `/home/mlxysf/wushusportacademy.app.tc/state/vendor/tecnickcom/tcpdf/`

## After Installation

1. Pull the latest code:
```bash
cd /home/mlxysf/wushusportacademy.app.tc/state
git pull origin main
```

2. Test the new TCPDF invoice:
```
https://wushusportacademy.app.tc/api/download_invoice_tcpdf.php?id=YOUR_REGISTRATION_ID
```

## Switch to TCPDF Permanently

Once TCPDF works correctly, rename the files:

```bash
cd /home/mlxysf/wushusportacademy.app.tc/state/api
mv download_invoice.php download_invoice_fpdf_backup.php
mv download_invoice_tcpdf.php download_invoice.php
```

## Features

✅ Native Chinese character support (初级-南拳 displays correctly)
✅ UTF-8 encoding
✅ HTML/CSS based styling
✅ Professional invoice layout
✅ All original features maintained

## Troubleshooting

If you get "TCPDF library not found" error:
- Check that TCPDF is installed in one of these locations:
  - `vendor/tecnickcom/tcpdf/tcpdf.php`
  - `tcpdf/tcpdf.php`
  - `vendor/tcpdf/tcpdf.php`
