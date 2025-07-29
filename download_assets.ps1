# Create directories if they don't exist
$dirs = @("assets/css", "assets/js", "assets/fonts")
foreach ($dir in $dirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force
    }
}

# Define assets to download
$assets = @{
    css = @(
        "https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css",
        "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css",
        "https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css",
        "https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css",
        "https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css",
        "https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap4.min.css",
        "https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css"
    )
    js = @(
        "https://code.jquery.com/jquery-3.6.0.min.js",
        "https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js",
        "https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js",
        "https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js",
        "https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js",
        "https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js",
        "https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js",
        "https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js",
        "https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js",
        "https://cdn.jsdelivr.net/momentjs/latest/moment.min.js",
        "https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js",
        "https://cdn.jsdelivr.net/npm/@tensorflow/tfjs",
        "https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface"
    )
}

# Function to download file
function Download-File {
    param (
        [string]$Url,
        [string]$OutFile
    )
    Write-Host "Downloading $Url to $OutFile..."
    try {
        Invoke-WebRequest -Uri $Url -OutFile $OutFile
        Write-Host "Successfully downloaded $Url"
    }
    catch {
        Write-Host "Failed to download $Url : $_"
    }
}

# Download CSS files
foreach ($url in $assets.css) {
    $filename = Split-Path $url -Leaf
    Download-File -Url $url -OutFile "assets/css/$filename"
}

# Download JavaScript files
foreach ($url in $assets.js) {
    $filename = Split-Path $url -Leaf
    if (-not $filename) {
        $filename = [System.IO.Path]::GetRandomFileName() + ".js"
    }
    Download-File -Url $url -OutFile "assets/js/$filename"
}

# Download Font Awesome fonts
$fontAwesomeFonts = @(
    "fa-brands-400.woff2",
    "fa-regular-400.woff2",
    "fa-solid-900.woff2"
)

foreach ($font in $fontAwesomeFonts) {
    $url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/$font"
    Download-File -Url $url -OutFile "assets/fonts/$font"
}

Write-Host "Asset download complete!" 