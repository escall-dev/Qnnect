# QR Code Attendance System with Facial Recognition

This system allows for student registration with facial recognition verification before generating unique QR codes for attendance tracking.

## New Feature: Facial Recognition for Student Registration

The system now requires students to capture their face during registration before a QR code can be generated. This adds an extra layer of security and verification to the registration process.

Plus a leaderboards that encourges students to attend classws by having a token or appreciation once the leaderboards show the results

## Setup Instructions

### Database Update

To add support for facial recognition, you need to update your database schema:

1. Log in to phpMyAdmin
2. Select the `qr_attendance_db` database
3. Go to the SQL tab
4. Run the following SQL command:

```sql
ALTER TABLE `tbl_student` 
ADD COLUMN `face_image_path` VARCHAR(255) NULL AFTER `generated_code`;
```

Alternatively, you can run the provided `update_db_schema.sql` file.

### Directory Setup

The system needs a directory to store facial images:

1. Create a directory named `face_images` in the root of your project
2. Make sure the directory has write permissions for the web server

```bash
mkdir face_images
chmod 777 face_images
```

## How It Works

### Student Registration Process

1. Admin opens the "Add Student" modal
2. Admin enters student details (name, department, subject)
3. Student must complete facial recognition:
   - Click "Start Camera" to activate webcam
   - Position face within the circle
   - Click "Capture Face" when ready
4. After successful face capture, the "Generate QR Code" button is enabled
5. Click "Generate QR Code" to create a unique QR code for the student
6. Click "Add List" to save the student record with facial data and QR code

### Technical Implementation

- The system uses TensorFlow.js and the BlazeFace model for face detection
- Face images are captured from the webcam and stored as JPEG files
- Each student record includes a reference to their facial image
- The QR code generation is only enabled after successful face capture

## Security Considerations

- Face images are stored in a separate directory with unique filenames
- The system verifies that a face is detected before allowing capture
- The registration process requires both facial data and student information

## Troubleshooting

- If the camera doesn't start, check browser permissions for camera access
- Make sure the `face_images` directory exists and has proper permissions
- Ensure you have updated the database schema with the `face_image_path` column

# QR Code Attendance System - Offline Setup

This document explains how to set up and run the QR Code Attendance System in an offline environment.

## Prerequisites

1. XAMPP (or similar local web server with PHP and MySQL)
2. PHP 7.4 or higher
3. MySQL 5.7 or higher
4. Composer (for PHP dependencies)

## Installation Steps

1. Clone or download this repository to your XAMPP's htdocs directory:
   ```
   C:\xampp\htdocs\qr-code-attendance-system\
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Set up the database:
   - Start XAMPP's Apache and MySQL services
   - Open your browser and navigate to `http://localhost/qr-code-attendance-system/setup-database.php`
   - Follow the on-screen instructions to create and initialize the database

4. Download offline assets (if not already present):
   ```powershell
   .\download_assets.ps1
   ```

5. Update asset references to use local files:
   ```bash
   php update_asset_references.php
   ```

## Offline Features

The system has been modified to work completely offline with:

1. Local asset serving:
   - All CSS and JavaScript files are served locally from the `assets` directory
   - Font files are stored locally
   - No CDN dependencies required

2. Local QR code generation:
   - QR codes are generated using PHP's built-in libraries
   - No external API calls required

3. Local face detection:
   - TensorFlow.js and Blazeface models are stored locally
   - Face detection works without internet connection

## Directory Structure

```
qr-code-attendance-system/
├── assets/
│   ├── css/        # Local CSS files
│   ├── js/         # Local JavaScript files
│   ├── fonts/      # Local font files
│   └── models/     # Local ML models
├── includes/
│   └── asset_helper.php  # Asset path helper
├── api/
│   └── generate-qr.php   # Local QR code generator
├── download_assets.ps1    # Asset download script
└── update_asset_references.php  # Asset reference updater
```

## Troubleshooting

1. If assets are not loading:
   - Make sure all files were downloaded successfully
   - Check file permissions in the assets directory
   - Verify the asset paths in PHP files

2. If QR code generation fails:
   - Ensure PHP GD library is enabled
   - Check write permissions in the api directory

3. If face detection is not working:
   - Verify TensorFlow.js files are present in assets/js
   - Check browser console for any JavaScript errors

## Support

For issues or questions, please:
1. Check the troubleshooting section above
2. Review the error logs in your XAMPP installation
3. Create an issue in the project repository 