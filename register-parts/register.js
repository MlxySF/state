// ========================================
// GLOBAL VARIABLES
// ========================================
let canvas, ctx;
let isDrawing = false;
let lastX = 0, lastY = 0;
let hasSigned = false;

let currentStep = 1;
const totalSteps = 7;
let registrationData = null;
let savedPdfBlob = null;

// ========================================
// DOM CONTENT LOADED
// ========================================
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById('today-date').value = new Date().toLocaleDateString('en-GB');
    document.getElementById('ic').addEventListener('input', formatIC);
    document.getElementById('ic').addEventListener('input', calculateAge);
    document.getElementById('parent-ic').addEventListener('input', formatIC);
    document.getElementById('phone').addEventListener('input', formatPhone);
    
    document.getElementById('school').addEventListener('change', toggleOtherSchool);
    
    const statusRadios = document.querySelectorAll('.status-radio');
    statusRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            updateStatusRadioStyle();
            updateScheduleAvailability();
        });
    });
    
    updateStatusRadioStyle();
    updateScheduleAvailability();
});

// ========================================
// STATUS RADIO STYLING
// ========================================
function updateStatusRadioStyle() {
    const radios = document.querySelectorAll('.status-radio');
    radios.forEach(radio => {
        const option = radio.nextElementSibling;
        if (radio.checked) {
            option.style.background = '#1e293b';
            option.style.color = 'white';
            option.style.borderColor = '#1e293b';
            option.style.fontWeight = 'bold';
        } else {
            option.style.background = 'white';
            option.style.color = '#475569';
            option.style.borderColor = '#e2e8f0';
            option.style.fontWeight = 'normal';
        }
    });
}

// ========================================
// SCHEDULE AVAILABILITY
// ========================================
function updateScheduleAvailability() {
    const statusRadios = document.getElementsByName('status');
    let selectedStatus = 'Student 学生';
    for (const radio of statusRadios) {
        if (radio.checked) {
            selectedStatus = radio.value;
            break;
        }
    }

    const isRegularStudent = selectedStatus === 'Student 学生';
    const schoolBoxes = document.querySelectorAll('.school-box');
    
    if (isRegularStudent) {
        schoolBoxes.forEach(schoolBox => {
            const schoolHeader = schoolBox.querySelector('.school-text h3');
            const schoolName = schoolHeader ? schoolHeader.textContent : '';
            
            if (schoolName.includes('Wushu Sport Academy') || schoolName.includes('武术体育学院')) {
                schoolBox.style.display = 'block';
                
                const allScheduleLabels = schoolBox.querySelectorAll('label[data-schedule]');
                allScheduleLabels.forEach(label => {
                    const scheduleKey = label.getAttribute('data-schedule');
                    const checkbox = label.querySelector('input[type="checkbox"]');
                    
                    if (scheduleKey === 'wsa-sun-10am' || scheduleKey === 'wsa-sun-12pm') {
                        label.style.display = 'flex';
                        if (checkbox) checkbox.disabled = false;
                    } else {
                        label.style.display = 'none';
                        if (checkbox) {
                            checkbox.checked = false;
                            checkbox.disabled = true;
                        }
                    }
                });
            } else {
                schoolBox.style.display = 'none';
                const allCheckboxes = schoolBox.querySelectorAll('input[type="checkbox"]');
                allCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                    checkbox.disabled = true;
                });
            }
        });
    } else {
        schoolBoxes.forEach(schoolBox => {
            schoolBox.style.display = 'block';
            const allScheduleLabels = schoolBox.querySelectorAll('label[data-schedule]');
            allScheduleLabels.forEach(label => {
                const checkbox = label.querySelector('input[type="checkbox"]');
                label.style.display = 'flex';
                if (checkbox) checkbox.disabled = false;
            });
        });
    }
}

// ========================================
// SIGNATURE FUNCTIONS
// ========================================
function initSignaturePad() {
    if (canvas) return;
    
    const wrapper = document.getElementById('sig-wrapper');
    if (!wrapper) {
        console.error('sig-wrapper not found');
        return;
    }

    canvas = document.createElement('canvas');
    canvas.id = 'sigCanvas';
    canvas.style.display = 'block';
    canvas.style.cursor = 'crosshair';
    canvas.style.position = 'absolute';
    canvas.style.top = '0';
    canvas.style.left = '0';
    wrapper.appendChild(canvas);

    ctx = canvas.getContext('2d');
    resizeCanvas();

    canvas.addEventListener('mousedown', (e) => {
        const rect = canvas.getBoundingClientRect();
        startDraw(e.clientX - rect.left, e.clientY - rect.top);
    });

    canvas.addEventListener('mousemove', (e) => {
        const rect = canvas.getBoundingClientRect();
        moveDraw(e.clientX - rect.left, e.clientY - rect.top);
    });

    canvas.addEventListener('mouseup', stopDraw);
    canvas.addEventListener('mouseleave', stopDraw);

    canvas.addEventListener('touchstart', (e) => {
        e.preventDefault();
        const t = e.touches[0];
        const rect = canvas.getBoundingClientRect();
        startDraw(t.clientX - rect.left, t.clientY - rect.top);
    }, { passive: false });

    canvas.addEventListener('touchmove', (e) => {
        e.preventDefault();
        const t = e.touches[0];
        const rect = canvas.getBoundingClientRect();
        moveDraw(t.clientX - rect.left, t.clientY - rect.top);
    }, { passive: false });

    canvas.addEventListener('touchend', stopDraw);
    
    console.log('✅ Signature pad initialized');
}

function resizeCanvas() {
    if (!canvas) return;
    const wrapper = document.getElementById('sig-wrapper');
    const rect = wrapper.getBoundingClientRect();
    if (rect.width > 0 && rect.height > 0) {
        canvas.width = rect.width;
        canvas.height = rect.height;
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#000';
    }
}

function startDraw(x, y) {
    isDrawing = true;
    hasSigned = true;
    document.getElementById('sig-placeholder').style.display = 'none';
    lastX = x;
    lastY = y;
}

function moveDraw(x, y) {
    if (!isDrawing) return;
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(x, y);
    ctx.stroke();
    lastX = x;
    lastY = y;
}

function stopDraw() {
    isDrawing = false;
}

function clearSig() {
    if (!ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasSigned = false;
    document.getElementById('sig-placeholder').style.display = 'flex';
}

window.addEventListener('resize', resizeCanvas);

// ========================================
// FORMAT FUNCTIONS
// ========================================
function formatIC(e) {
    let val = e.target.value.replace(/\D/g, '');
    if (val.length > 12) val = val.substring(0, 12);
    if (val.length > 8) {
        e.target.value = val.substring(0, 6) + '-' + val.substring(6, 8) + '-' + val.substring(8, 12);
    } else if (val.length > 6) {
        e.target.value = val.substring(0, 6) + '-' + val.substring(6, 8);
    } else {
        e.target.value = val;
    }
}

function formatPhone(e) {
    let val = e.target.value.replace(/\D/g, '');
    if (val.length > 11) val = val.substring(0, 11);
    if (val.length >= 11) {
        e.target.value = val.substring(0, 3) + '-' + val.substring(3, 7) + ' ' + val.substring(7, 11);
    } else if (val.length >= 10) {
        e.target.value = val.substring(0, 3) + '-' + val.substring(3, 6) + ' ' + val.substring(6, 10);
    } else if (val.length > 3) {
        e.target.value = val.substring(0, 3) + '-' + val.substring(3);
    } else {
        e.target.value = val;
    }
}

function calculateAge() {
    const ic = document.getElementById('ic').value;
    const ageInput = document.getElementById('age');
    
    if (ic.length >= 6) {
        const year = ic.substring(0, 2);
        const month = ic.substring(2, 4);
        const day = ic.substring(4, 6);
        
        let fullYear = parseInt(year);
        fullYear = fullYear > 25 ? 1900 + fullYear : 2000 + fullYear;
        
        const birthDate = new Date(fullYear, parseInt(month) - 1, parseInt(day));
        const targetDate = new Date(2026, 0, 1);
        let age = targetDate.getFullYear() - birthDate.getFullYear();
        const monthDiff = targetDate.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && targetDate.getDate() < birthDate.getDate())) {
            age--;
        }
        
        if (age < 4 || age > 100) {
            ageInput.value = '';
            showError('Invalid birth year from IC. Age must be between 4-100 in 2026.');
        } else {
            ageInput.value = age;
        }
    } else {
        ageInput.value = '';
    }
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mt-2';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i>${message}`;
    
    const ageInput = document.getElementById('age');
    const existingError = ageInput.parentElement.querySelector('.bg-red-50');
    if (existingError) existingError.remove();
    
    ageInput.parentElement.appendChild(errorDiv);
    setTimeout(() => errorDiv.remove(), 4000);
}

function toggleOtherSchool() {
    const schoolSelect = document.getElementById('school');
    const otherInput = document.getElementById('school-other');
    
    if (schoolSelect.value === 'Others') {
        otherInput.classList.remove('hidden');
        otherInput.required = true;
    } else {
        otherInput.classList.add('hidden');
        otherInput.required = false;
        otherInput.value = '';
    }
}

function toggleSchoolBox(element) {
    element.classList.toggle('active');
}

// ========================================
// STEP NAVIGATION
// ========================================
function changeStep(dir) {
    if (dir === 1 && !validateStep(currentStep)) {
        return;
    }
    
    if (dir === 1 && currentStep === 5) {
        submitAndGeneratePDF();
        return;
    }

    if (dir === 1 && currentStep === 6) {
        submitPayment();
        return;
    }

    document.getElementById(`step-${currentStep}`).classList.remove('active');
    currentStep += dir;
    document.getElementById(`step-${currentStep}`).classList.add('active');

    if (currentStep === 5) {
        setTimeout(initSignaturePad, 100);
    }

    if (currentStep === 6) {
        updatePaymentDisplay();
        document.getElementById('payment-date').value = new Date().toISOString().split('T')[0];
    }

    document.getElementById('btn-prev').disabled = (currentStep === 1);
    
    if (currentStep === 7) {
        document.getElementById('btn-next').style.display = 'none';
    } else {
        document.getElementById('btn-next').style.display = 'block';
    }

    const stepCounter = document.getElementById('step-counter');
    stepCounter.innerHTML = `0${currentStep}<span style="color: #475569; font-size: 14px;">/07</span>`;

    const progressBar = document.getElementById('progress-bar');
    progressBar.style.width = `${(currentStep / 7) * 100}%`;

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ========================================
// PAYMENT SUBMISSION - UPDATED WITHOUT STUDENT PORTAL
// ========================================
async function submitPayment() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.style.display = 'flex';

    try {
        const { classCount, totalFee } = calculateFees();
        const paymentDate = document.getElementById('payment-date').value;

        const payload = {
            name_cn: registrationData.nameCn || '',
            name_en: registrationData.nameEn,
            ic: registrationData.ic,
            age: registrationData.age,
            school: registrationData.school,
            status: registrationData.status,
            phone: registrationData.phone,
            email: registrationData.email,
            level: registrationData.level || '',
            events: registrationData.events,
            schedule: registrationData.schedule,
            parent_name: registrationData.parent,
            parent_ic: registrationData.parentIC,
            form_date: registrationData.date,
            signature_base64: registrationData.signature,
            signed_pdf_base64: registrationData.pdfBase64,
            payment_amount: totalFee,
            payment_date: paymentDate,
            payment_receipt_base64: receiptBase64,
            class_count: classCount
        };

        console.log('Submitting payload:', payload);

        const response = await fetch('api/process_registration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (overlay) overlay.style.display = 'none';

        if (result.success) {
            // Store registration number
            registrationData.registrationNumber = result.registration_number;

            // Update success page with registration number
            document.getElementById('reg-number-display').innerHTML = `
                <strong style="font-size: 20px; color: #7c3aed;">Registration Number: ${result.registration_number}</strong>
            `;

            // Navigate to success page
            document.getElementById(`step-${currentStep}`).classList.remove('active');
            currentStep = 7;
            document.getElementById(`step-${currentStep}`).classList.add('active');
            
            const stepCounter = document.getElementById('step-counter');
            stepCounter.innerHTML = `07<span style="color: #475569; font-size: 14px;">/07</span>`;
            
            const progressBar = document.getElementById('progress-bar');
            progressBar.style.width = '100%';
            
            document.getElementById('btn-prev').disabled = true;
            document.getElementById('btn-next').style.display = 'none';
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Show success notification
            Swal.fire({
                icon: 'success',
                title: 'Registration Successful!',
                html: `
                    <p>Your registration number is:</p>
                    <p style="font-size: 20px; font-weight: bold; color: #7c3aed; margin: 10px 0;">${result.registration_number}</p>
                    <p style="margin-top: 16px;">Please save this number for your records.</p>
                `,
                confirmButtonText: 'OK'
            });
        } else {
            Swal.fire('Error', result.error || 'Registration failed', 'error');
        }

    } catch (error) {
        if (overlay) overlay.style.display = 'none';
        console.error('Error:', error);
        Swal.fire('Error', 'An error occurred during submission: ' + error.message, 'error');
    }
}

// ========================================
// VALIDATION
// ========================================
function validateStep(step) {
    if (step === 1) {
        const nameEn = document.getElementById('name-en').value.trim();
        const ic = document.getElementById('ic').value.trim();
        const age = document.getElementById('age').value;
        const school = document.getElementById('school').value;
        const schoolOther = document.getElementById('school-other');

        if (!nameEn) {
            Swal.fire('Error', 'Please enter English name', 'error');
            return false;
        }
        if (!ic || ic.length < 14) {
            Swal.fire('Error', 'Please enter a valid IC number', 'error');
            return false;
        }
        if (!age) {
            Swal.fire('Error', 'Age could not be calculated from IC', 'error');
            return false;
        }
        if (!school) {
            Swal.fire('Error', 'Please select a school', 'error');
            return false;
        }
        if (school === 'Others' && !schoolOther.value.trim()) {
            Swal.fire('Error', 'Please specify school name', 'error');
            return false;
        }
    }

    if (step === 2) {
        const phone = document.getElementById('phone').value.trim();
        const email = document.getElementById('email').value.trim();

        if (!phone || phone.length < 12) {
            Swal.fire('Error', 'Please enter a valid phone number', 'error');
            return false;
        }
        if (!email || !email.includes('@')) {
            Swal.fire('Error', 'Please enter a valid email address', 'error');
            return false;
        }
    }

    if (step === 3) {
        const events = document.querySelectorAll('input[name="evt"]:checked');
        if (events.length === 0) {
            Swal.fire('Error', 'Please select at least one event', 'error');
            return false;
        }
    }

    if (step === 4) {
        const schedules = document.querySelectorAll('input[name="sch"]:checked');
        if (schedules.length === 0) {
            Swal.fire('Error', 'Please select at least one training schedule', 'error');
            return false;
        }
    }

    if (step === 5) {
        const parentName = document.getElementById('parent-name').value.trim();
        const parentIC = document.getElementById('parent-ic').value.trim();

        if (!parentName) {
            Swal.fire('Error', 'Please enter parent/guardian name', 'error');
            return false;
        }
        if (!parentIC || parentIC.length < 14) {
            Swal.fire('Error', 'Please enter a valid parent/guardian IC', 'error');
            return false;
        }
        if (!hasSigned) {
            Swal.fire('Error', 'Please sign the agreement', 'error');
            return false;
        }
        if (!canvas) {
            Swal.fire('Error', 'Signature canvas not initialized', 'error');
            return false;
        }
    }

    if (step === 6) {
        const paymentDate = document.getElementById('payment-date').value;
        
        if (!paymentDate) {
            Swal.fire('Error', 'Please select payment date', 'error');
            return false;
        }
        
        if (!receiptBase64) {
            Swal.fire('Error', 'Please upload payment receipt', 'error');
            return false;
        }
    }

    return true;
}

// ========================================
// FORM SUBMISSION
// ========================================
async function submitAndGeneratePDF() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.style.display = 'flex';

    try {
        const nameEn = document.getElementById('name-en').value;
        const nameCn = document.getElementById('name-cn').value || '';
        const ic = document.getElementById('ic').value;
        const age = document.getElementById('age').value;
        const school = document.getElementById('school').value === 'Others' 
            ? document.getElementById('school-other').value 
            : document.getElementById('school').value;
        
        const statusRadios = document.getElementsByName('status');
        let status = '';
        for (const radio of statusRadios) {
            if (radio.checked) {
                status = radio.value;
                break;
            }
        }

        const phone = document.getElementById('phone').value;
        const email = document.getElementById('email').value;

        const levelRadios = document.getElementsByName('lvl');
        let level = '';
        for (const radio of levelRadios) {
            if (radio.checked) {
                level = radio.value === 'Other' 
                    ? document.getElementById('level-other').value 
                    : radio.value;
                break;
            }
        }

        const eventsCheckboxes = document.querySelectorAll('input[name="evt"]:checked');
        const eventsArray = Array.from(eventsCheckboxes).map(cb => cb.value);
        const events = eventsArray.join(', ');

        const scheduleCheckboxes = document.querySelectorAll('input[name="sch"]:checked');
        const schedulesArray = Array.from(scheduleCheckboxes).map(cb => cb.value);
        const schedules = schedulesArray.join(', ');

        const parentName = document.getElementById('parent-name').value;
        const parentIC = document.getElementById('parent-ic').value;
        const formDate = document.getElementById('today-date').value;

        if (!hasSigned) {
            if (overlay) overlay.style.display = 'none';
            Swal.fire('Error', 'Please provide a signature', 'error');
            return;
        }

        const signatureBase64 = canvas.toDataURL('image/png');
        const displayName = nameCn ? `${nameEn} (${nameCn})` : nameEn;
        const namePlain = nameEn;

        const pdfBase64 = await generatePDFFile();

        registrationData = {
            nameCn: nameCn,
            nameEn: nameEn,
            namePlain: namePlain,
            displayName: displayName,
            ic: ic,
            age: age,
            school: school,
            status: status,
            phone: phone,
            email: email,
            level: level,
            events: events,
            schedule: schedules,
            parent: parentName,
            parentIC: parentIC,
            date: formDate,
            signature: signatureBase64,
            pdfBase64: pdfBase64
        };

        if (overlay) overlay.style.display = 'none';

        document.getElementById(`step-${currentStep}`).classList.remove('active');
        currentStep = 6;
        document.getElementById(`step-${currentStep}`).classList.add('active');
        
        const stepCounter = document.getElementById('step-counter');
        stepCounter.innerHTML = `06<span style="color: #475569; font-size: 14px;">/07</span>`;
        
        const progressBar = document.getElementById('progress-bar');
        progressBar.style.width = `${(6 / 7) * 100}%`;
        
        document.getElementById('btn-prev').disabled = false;
        
        updatePaymentDisplay();
        document.getElementById('payment-date').value = new Date().toISOString().split('T')[0];
        
        window.scrollTo({ top: 0, behavior: 'smooth' });

    } catch (error) {
        if (overlay) overlay.style.display = 'none';
        console.error('Error:', error);
        Swal.fire('Error', 'An error occurred during PDF generation: ' + error.message, 'error');
    }
}

// ========================================
// PDF GENERATION - FIXED WITH CORRECT IDs
// ========================================
async function generatePDFFile() {
    const displayName = (document.getElementById('name-cn').value ? 
        `${document.getElementById('name-en').value} (${document.getElementById('name-cn').value})` : 
        document.getElementById('name-en').value);
    
    const ic = document.getElementById('ic').value;
    const age = document.getElementById('age').value;
    const school = document.getElementById('school').value === 'Others' ? 
        document.getElementById('school-other').value : 
        document.getElementById('school').value;
    
    const statusRadios = document.getElementsByName('status');
    let status = '';
    for (const radio of statusRadios) {
        if (radio.checked) {
            status = radio.value;
            break;
        }
    }
    
    const phone = document.getElementById('phone').value;
    const email = document.getElementById('email').value;
    
    const levelRadios = document.getElementsByName('lvl');
    let level = '';
    for (const radio of levelRadios) {
        if (radio.checked) {
            level = radio.value === 'Other' ? 
                document.getElementById('level-other').value : 
                radio.value;
            break;
        }
    }
    
    const eventsCheckboxes = document.querySelectorAll('input[name="evt"]:checked');
    const events = Array.from(eventsCheckboxes).map(cb => cb.value).join(', ');
    
    const scheduleCheckboxes = document.querySelectorAll('input[name="sch"]:checked');
    const schedule = Array.from(scheduleCheckboxes).map(cb => cb.value).join(', ');
    
    const parent = document.getElementById('parent-name').value;
    const parentIC = document.getElementById('parent-ic').value;
    const date = document.getElementById('today-date').value;
    const signature = canvas.toDataURL('image/png');

    // FIXED: Using correct element IDs with 'preview-pdf-' prefix
    const setElementText = (id, value) => {
        const element = document.getElementById(id);
        if (element) {
            element.innerText = value || '';
        } else {
            console.warn(`Element not found: ${id}`);
        }
    };

    setElementText('preview-pdf-name', displayName);
    setElementText('preview-pdf-ic', ic);
    setElementText('preview-pdf-age', age);
    setElementText('preview-pdf-school', school);
    setElementText('preview-pdf-status', status);
    setElementText('preview-pdf-phone', phone);
    setElementText('preview-pdf-email', email);
    setElementText('preview-pdf-level', level);
    setElementText('preview-pdf-events', events);
    setElementText('preview-pdf-schedule', schedule);
    setElementText('preview-pdf-parent-name', parent);
    setElementText('preview-pdf-parent-ic', parentIC);
    setElementText('preview-pdf-date', date);
    
    const sigImg = document.getElementById('preview-pdf-sig-img');
    if (sigImg) {
        sigImg.src = signature;
    }

    setElementText('preview-pdf-parent-name-2', parent);
    setElementText('preview-pdf-parent-ic-2', parentIC);
    setElementText('preview-pdf-date-2', date);

    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('p', 'mm', 'a4');

    const page1 = document.getElementById('pdf-preview-template-page1');
    page1.style.visibility = 'visible';
    page1.style.opacity = '1';
    page1.style.position = 'absolute';
    page1.style.left = '0';
    page1.style.top = '0';
    page1.style.zIndex = '9999';

    await new Promise(resolve => setTimeout(resolve, 200));

    const canvas1 = await html2canvas(page1, {
        scale: 2,
        useCORS: true,
        allowTaint: true,
        width: 794,
        height: 1123,
        logging: false,
        backgroundColor: '#ffffff'
    });

    const imgData1 = canvas1.toDataURL('image/jpeg', 0.95);
    pdf.addImage(imgData1, 'JPEG', 0, 0, 210, 297);

    page1.style.visibility = 'hidden';
    page1.style.opacity = '0';
    page1.style.position = 'fixed';
    page1.style.left = '-99999px';
    page1.style.top = '-99999px';
    page1.style.zIndex = '-9999';

    const page2 = document.getElementById('pdf-preview-template-page2');
    page2.style.visibility = 'visible';
    page2.style.opacity = '1';
    page2.style.position = 'absolute';
    page2.style.left = '0';
    page2.style.top = '0';
    page2.style.zIndex = '9999';

    await new Promise(resolve => setTimeout(resolve, 200));

    const canvas2 = await html2canvas(page2, {
        scale: 2,
        useCORS: true,
        allowTaint: true,
        width: 794,
        height: 1123,
        logging: false,
        backgroundColor: '#ffffff'
    });

    const imgData2 = canvas2.toDataURL('image/jpeg', 0.95);
    pdf.addPage();
    pdf.addImage(imgData2, 'JPEG', 0, 0, 210, 297);

    page2.style.visibility = 'hidden';
    page2.style.opacity = '0';
    page2.style.position = 'fixed';
    page2.style.left = '-99999px';
    page2.style.top = '-99999px';
    page2.style.zIndex = '-9999';

    const nameForFile = document.getElementById('name-en').value.replace(/\s+/g, '_');
    pdf.save(`${nameForFile}_Registration_Agreement.pdf`);

    savedPdfBlob = pdf.output('blob');

    return pdf.output('datauristring').split(',')[1];
}

function downloadPDF() {
    if (savedPdfBlob) {
        const nameForFile = registrationData.nameEn.replace(/\s+/g, '_');
        const url = URL.createObjectURL(savedPdfBlob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${nameForFile}_Registration_Agreement.pdf`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        Swal.fire({
            icon: 'success',
            title: 'Downloaded!',
            text: 'Your registration agreement has been downloaded.',
            timer: 2000,
            showConfirmButton: false
        });
    } else {
        Swal.fire('Error', 'PDF not available. Please try again.', 'error');
    }
}

function submitAnother() {
    location.reload();
}

// ========================================
// PAYMENT FUNCTIONS
// ========================================
let receiptBase64 = null;

function calculateFees() {
    const schedules = document.querySelectorAll('input[name="sch"]:checked');
    const classCount = schedules.length;
    
    let totalFee = 0;
    if (classCount === 1) totalFee = 120;
    else if (classCount === 2) totalFee = 200;
    else if (classCount === 3) totalFee = 280;
    else if (classCount >= 4) totalFee = 320;
    
    return { classCount, totalFee };
}

function updatePaymentDisplay() {
    const { classCount, totalFee } = calculateFees();
    
    document.getElementById('payment-class-count').innerText = classCount;
    document.getElementById('payment-total').innerText = `RM ${totalFee}`;
    
    const statusRadios = document.getElementsByName('status');
    let status = '';
    for (const radio of statusRadios) {
        if (radio.checked) {
            status = radio.value;
            break;
        }
    }
    document.getElementById('payment-status').innerText = status;
}

function copyAccountNumber() {
    const accountNumber = '5621 2345 6789';
    navigator.clipboard.writeText(accountNumber).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'Account number copied to clipboard',
            timer: 1500,
            showConfirmButton: false
        });
    });
}

function handleReceiptUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        Swal.fire('Error', 'File size must be less than 5MB', 'error');
        return;
    }

    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    if (!validTypes.includes(file.type)) {
        Swal.fire('Error', 'Only JPG, PNG, and PDF files are allowed', 'error');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        receiptBase64 = e.target.result;
        
        document.getElementById('upload-prompt').classList.add('hidden');
        document.getElementById('upload-preview').classList.remove('hidden');
        
        if (file.type === 'application/pdf') {
            document.getElementById('preview-image').style.display = 'none';
        } else {
            document.getElementById('preview-image').src = receiptBase64;
            document.getElementById('preview-image').style.display = 'block';
        }
        
        document.getElementById('preview-filename').innerText = file.name;
    };
    reader.readAsDataURL(file);
}

function removeReceipt() {
    receiptBase64 = null;
    document.getElementById('receipt-upload').value = '';
    document.getElementById('upload-prompt').classList.remove('hidden');
    document.getElementById('upload-preview').classList.add('hidden');
}