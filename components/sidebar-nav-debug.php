<?php
error_log("Current PHP_SELF: " . $_SERVER['PHP_SELF']);
error_log("Is in admin folder: " . (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'yes' : 'no'));
error_log("Base URL: " . ($isInAdminFolder ? '../' : ''));
?>
