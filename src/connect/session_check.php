<?php
session_start();
// Prevent caching of the page to avoid unauthorized access after logout
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include("variables.php"); // Assuming this file contains necessary configurations

$currentPage = basename($_SERVER['PHP_SELF']);
$dirFileName = basename(dirname($_SERVER['PHP_SELF'])); // Define $dirFileName here

// Define admin protected pages
$adminProtectedPages = ['bookingManagement.php', 'dashboardView.php', 'feedbackView.php'];

// Admin login page that is accessible without being logged in
$adminPublicOnlyPages = ['index.php'];

// Redirect logic for admin protected pages
if (in_array($currentPage, $adminProtectedPages) && !isset($_SESSION['adminid'])) {
    header("Location: index.php");
    exit;
}

// Adjusted redirection logic for the admin login page
if (in_array($currentPage, $adminPublicOnlyPages)) {
    if (isset($_SESSION['adminid']) && !isset($_SESSION['has_logged_out'])) {
        header("Location: dashboardView.php");
        exit;
    }
    unset($_SESSION['has_logged_out']); // Clear flag after checking it
}

// IP Address detection logic
if (in_array($_SERVER['REMOTE_ADDR'], $localhostTrue)) {
    // Assuming localhost
    $ip = "49.150.164.88"; // Example IP, adjust as necessary
} else {
    // Assuming live server
    $ip = getenv('REMOTE_ADDR');
}

// Check if running on localhost
$localhost_status = in_array($_SERVER['REMOTE_ADDR'], $localhostTrue);
$localhost_status_index = $localhost_status && ($dirFileName == "dist");

// Determine if the script is running in an index or registration context
$index_status = !$localhost_status || $currentPage == "register.php" || $dirFileName == "dist" || $dirFileName == "";

// Execute options based on environment and page context
$in_concat = ($localhost_status && $localhost_status_index) || (!$localhost_status && $index_status);

?>
