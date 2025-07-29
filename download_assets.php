<?php
require_once 'includes/asset_helper.php';

$assets = [
    // CSS files
    'css' => [
        'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
        'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css',
        'https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css',
        'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css',
        'https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap4.min.css',
        'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css'
    ],
    // JavaScript files
    'js' => [
        'https://code.jquery.com/jquery-3.6.0.min.js',
        'https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js',
        'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js',
        'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
        'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js',
        'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js',
        'https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js',
        'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js',
        'https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js',
        'https://cdn.jsdelivr.net/momentjs/latest/moment.min.js',
        'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js',
        'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs',
        'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface'
    ]
];

// Create directories if they don't exist
$dirs = ['assets/css', 'assets/js', 'assets/fonts'];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Function to download and save file using cURL
function downloadFile($url, $path) {
    echo "Downloading $url to $path...\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $content = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo "Failed to download $url: " . curl_error($ch) . "\n";
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    if (file_put_contents($path, $content) === false) {
        echo "Failed to save $path\n";
        return false;
    }
    
    echo "Successfully downloaded $url\n";
    return true;
}

// Download CSS files
foreach ($assets['css'] as $url) {
    $filename = basename(parse_url($url, PHP_URL_PATH));
    downloadFile($url, "assets/css/$filename");
}

// Download JavaScript files
foreach ($assets['js'] as $url) {
    $filename = basename(parse_url($url, PHP_URL_PATH));
    if (empty($filename)) {
        // For URLs without file extension, create a meaningful name
        $filename = md5($url) . '.js';
    }
    downloadFile($url, "assets/js/$filename");
}

// Download Font Awesome fonts
$fontAwesomeFonts = [
    'fa-brands-400.woff2',
    'fa-regular-400.woff2',
    'fa-solid-900.woff2'
];

foreach ($fontAwesomeFonts as $font) {
    $url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/$font";
    downloadFile($url, "assets/fonts/$font");
}

echo "Asset download complete!\n"; 