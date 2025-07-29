// QR Code generation function
function generateQRCode(text, elementId) {
    try {
        console.log('Generating QR code for:', text, 'in element:', elementId);
        
        // Clear any existing content
        const element = document.getElementById(elementId);
        if (!element) {
            console.error('Element not found:', elementId);
            return;
        }
        element.innerHTML = '';
        
        // Create QR code with larger size
        const qr = new QRCode(element, {
            text: text,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // Style the QR code container
        element.style.display = 'flex';
        element.style.justifyContent = 'center';
        element.style.alignItems = 'center';
        element.style.margin = '0 auto';
        element.style.padding = '20px';

        // Style the QR code image
        setTimeout(() => {
            const qrImage = element.querySelector('img');
            if (qrImage) {
                qrImage.style.margin = '0 auto';
                qrImage.style.maxWidth = '100%';
                qrImage.style.height = 'auto';
            } else {
                console.error('QR code image not found in element');
            }
        }, 50);

        return qr;
    } catch (error) {
        console.error('Error generating QR code:', error);
        alert('Error generating QR code. Please try again.');
    }
}

// Function to generate random code
function generateRandomCode(length) {
    const characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    let randomString = '';

    for (let i = 0; i < length; i++) {
        const randomIndex = Math.floor(Math.random() * characters.length);
        randomString += characters.charAt(randomIndex);
    }

    return randomString;
}

// Function to handle QR code generation button click
function handleQRCodeGeneration() {
    try {
        const qrContainer = document.getElementById('qrImg').parentElement;
        if (!qrContainer) {
            console.error('QR container not found');
            return;
        }
        
        // Clear previous QR code if any
        qrContainer.innerHTML = '<div id="qrImg" style="display: flex; justify-content: center; align-items: center; margin: 0 auto;"></div>';
        
        let text = generateRandomCode(10);
        console.log('Generated random code:', text);
        $("#generatedCode").val(text);

        if (text === "") {
            alert("Please enter text to generate a QR code.");
            return;
        }

        generateQRCode(text, 'qrImg');
        document.getElementById('studentName').style.pointerEvents = 'none';
        document.getElementById('studentCourse').style.pointerEvents = 'none';
        document.querySelector('.modal-close').style.display = '';
        document.querySelector('.qr-con').style.display = '';
        document.querySelector('.qr-generator').style.display = 'none';
    } catch (error) {
        console.error('Error in handleQRCodeGeneration:', error);
        alert('Error generating QR code. Please try again.');
    }
} 