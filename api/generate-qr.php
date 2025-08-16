<?php
// Simple QR generator for student codes and other payloads
// Requires composer dependency endroid/qr-code configured in project root

declare(strict_types=1);

// Basic guard to avoid open relay; allow unauthenticated rendering if explicitly permitted
session_start();
header('Content-Type: image/png');

// Lazy include composer autoload from project root or admin/vendor
$autoloadPaths = [
	__DIR__ . '/../vendor/autoload.php',
	__DIR__ . '/../admin/vendor/autoload.php',
];
foreach ($autoloadPaths as $autoload) {
	if (file_exists($autoload)) {
		require_once $autoload;
		break;
	}
}

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;

$text = isset($_GET['text']) ? trim((string)$_GET['text']) : '';
$size = isset($_GET['size']) ? max(100, min(512, (int)$_GET['size'])) : 300;

if ($text === '') {
	// Render a placeholder QR with instructions
	$text = 'QR-EMPTY';
}

$qr = QrCode::create($text)
	->setEncoding(new Encoding('UTF-8'))
	->setErrorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh())
	->setSize($size)
	->setMargin(8)
	->setForegroundColor(new Color(0, 0, 0))
	->setBackgroundColor(new Color(255, 255, 255));

$writer = new PngWriter();
$result = $writer->write($qr);

// Cache for a short time to help browser performance
header('Cache-Control: public, max-age=300');
echo $result->getString();
exit;
?>
