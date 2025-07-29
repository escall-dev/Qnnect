<?php

require_once 'includes/asset_helper.php';

// Map of CDN URLs to local asset paths
$asset_map = [
    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css' => 'css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css' => 'css/all.min.css',
    'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css' => 'css/jquery.dataTables.css',
    'https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css' => 'css/buttons.dataTables.min.css',
    'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css' => 'css/dataTables.bootstrap4.min.css',
    'https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap4.min.css' => 'css/buttons.bootstrap4.min.css',
    'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css' => 'css/daterangepicker.css',
    'https://code.jquery.com/jquery-3.6.0.min.js' => 'js/jquery-3.6.0.min.js',
    'https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js' => 'js/popper.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js' => 'js/bootstrap.min.js',
    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js' => 'js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js' => 'js/dataTables.bootstrap4.min.js',
    'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js' => 'js/dataTables.buttons.min.js',
    'https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js' => 'js/buttons.bootstrap4.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js' => 'js/jszip.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js' => 'js/pdfmake.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js' => 'js/vfs_fonts.js',
    'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js' => 'js/buttons.html5.min.js',
    'https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js' => 'js/buttons.print.min.js',
    'https://cdn.jsdelivr.net/momentjs/latest/moment.min.js' => 'js/moment.min.js',
    'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js' => 'js/daterangepicker.min.js',
    'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs' => 'js/tfjs',
    'https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface' => 'js/blazeface'
];

// Function to update file contents
function update_file_contents($file_path) {
    global $asset_map;
    
    // Read file contents
    $content = file_get_contents($file_path);
    if ($content === false) {
        echo "Failed to read file: $file_path\n";
        return false;
    }
    
    // Replace each CDN URL with its local equivalent
    $modified = false;
    foreach ($asset_map as $cdn_url => $local_path) {
        if (strpos($content, $cdn_url) !== false) {
            $content = str_replace(
                "href=\"$cdn_url\"",
                "href=\"<?php echo asset_url('$local_path'); ?>\"",
                $content
            );
            $content = str_replace(
                "src=\"$cdn_url\"",
                "src=\"<?php echo asset_url('$local_path'); ?>\"",
                $content
            );
            $modified = true;
        }
    }
    
    // If file was modified, save it
    if ($modified) {
        // Add the asset_helper include if not already present
        if (strpos($content, 'asset_helper.php') === false) {
            $content = preg_replace(
                '/<\?php/',
                "<?php\nrequire_once 'includes/asset_helper.php';",
                $content,
                1
            );
        }
        
        if (file_put_contents($file_path, $content) === false) {
            echo "Failed to write file: $file_path\n";
            return false;
        }
        echo "Updated file: $file_path\n";
    }
    
    return true;
}

// Get all PHP files in the directory
$php_files = glob('*.php');

// Update each file
foreach ($php_files as $file) {
    update_file_contents($file);
}

echo "Asset reference update complete!\n"; 