<?php
require_once 'includes/asset_helper.php';
// Set page title and header
$pageTitle = "Database Setup";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?php echo asset_url('css/bootstrap.min.css'); ?>">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2><?= $pageTitle ?></h2>
            </div>
            <div class="card-body">
                <h4>Setting up database tables...</h4>
                <div class="alert alert-info">
                    <?php
                    // Include the database setup script
                    include('./db_setup/create_user_settings_table.php');
                    ?>
                </div>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-primary">Return to Main Page</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 