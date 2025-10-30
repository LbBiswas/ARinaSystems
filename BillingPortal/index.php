<?php
// index.php - Main entry point
require_once 'config/config.php';

// Redirect to appropriate page based on login status
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin.php');
    } else {
        redirect('customer.php');
    }
} else {
    redirect('login.html');
}
?>