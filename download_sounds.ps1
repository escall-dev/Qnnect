$successUrl = "https://github.com/iamasink/lilysound/raw/master/chime/positive-notification.mp3"
$errorUrl = "https://github.com/iamasink/lilysound/raw/master/chime/negative-notification.mp3"

$successPath = "c:\xamppie\htdocs\personal-proj\Qnnect\assets\sounds\success.mp3"
$errorPath = "c:\xamppie\htdocs\personal-proj\Qnnect\assets\sounds\error.mp3"

Invoke-WebRequest -Uri $successUrl -OutFile $successPath
Invoke-WebRequest -Uri $errorUrl -OutFile $errorPath

Write-Host "Sound files downloaded successfully to $($successPath) and $($errorPath)"
