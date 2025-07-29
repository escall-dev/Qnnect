<?php

// require 'vendor/autoload.php';

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// require 'path/to/PHPMailer/src/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPmailer(true);

$mail->isSMTP();
$mail->SMTPAuth = true;
// $mail->SMTPDebug = SMTP::DEBUG_SERVER;

$mail->Host = 'smtp.gmail.com';
$mail->Username = 'spcpc2017ph@gmail.com';  // Your email address
$mail->Password = 'vkjy hafe vfcg dhrq';     // Your email password (or app password)
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Enable TLS encryption
$mail->Port = 587;  // Set the TCP port to connect to (587 for TLS)

$mail->isHTML(true);

return $mail;
?>