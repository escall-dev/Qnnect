<?php

// Check if PHPMailer is available via Composer autoload
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    // $mail->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
    
    $mail->Host = 'smtp.gmail.com';
    $mail->Username = 'spcpc2017ph@gmail.com';  // Your email address
    $mail->Password = 'vkjy hafe vfcg dhrq';     // Your email password (or app password)
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;  // Enable TLS encryption
    $mail->Port = 587;  // Set the TCP port to connect to (587 for TLS)
    
    $mail->isHTML(true);
    
    return $mail;
} else {
    // Fallback: Create a simple mail wrapper class
    class SimpleMailer {
        private $from_email = 'spcpc2017ph@gmail.com';
        private $from_name = 'SPCPC Password Reset';
        
        public function setFrom($email, $name = '') {
            $this->from_email = $email;
            $this->from_name = $name;
        }
        
        public function addAddress($email) {
            $this->to_email = $email;
        }
        
        public function Subject($subject) {
            $this->subject = $subject;
        }
        
        public function Body($body) {
            $this->body = $body;
        }
        
        public function send() {
            $headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
            $headers .= "Reply-To: {$this->from_email}\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            return mail($this->to_email, $this->subject, $this->body, $headers);
        }
        
        public function ErrorInfo() {
            return 'Mail function error';
        }
    }
    
    return new SimpleMailer();
}
?>