<?php

function asset_url($path) {
    // Get the base URL dynamically
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $base_path = dirname($_SERVER['PHP_SELF']);
    
    // Clean up the path
    $path = ltrim($path, '/');
    $base_path = rtrim($base_path, '/');
    
    // Return the full URL
    return "$base_url$base_path/assets/$path";
}

function asset_path($path) {
    // Return the filesystem path
    return __DIR__ . "/../assets/" . ltrim($path, '/');
}