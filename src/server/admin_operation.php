<?php
include_once("../connect/config.php");
include_once("../connect/session_check.php");

function jsonResponse($status, $message, $additionalData = []) {
    header('Content-Type: application/json'); // Ensure JSON content type
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $additionalData));
    exit;
}

function handleLogin($conn) {
    if (!isset($_POST['usernameLogin'], $_POST['passwordLogin'])) {
        jsonResponse(false, "Username and password are required.");
        return;
    }

    $usernameLogin = $_POST['usernameLogin'];
    $passwordLogin = $_POST['passwordLogin'];

    $stmt = $conn->prepare("SELECT adminid, admin_password FROM admin WHERE admin_username = ?");
    $stmt->bind_param("s", $usernameLogin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        // Directly compare plaintext passwords - for debugging purposes only
        if ($passwordLogin === $admin['admin_password']) {
            $_SESSION['adminid'] = $admin['adminid'];
            session_regenerate_id();
            jsonResponse(true, "Login successful.", ['redirectUrl' => 'dashboardView.php']);
        } else {
            jsonResponse(false, "Invalid username or password.");
        }
    } else {
        jsonResponse(false, "Invalid username or password.");
    }
    $stmt->close();
}

function handleLogout() {
    // Check if the user is already logged in
    if (!isset($_SESSION['adminid'])) {
        jsonResponse(false, "User not logged in.");
        return;
    }
    
    // Proceed with logout
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    // Destroy the session
    session_destroy();

    // Send back a JSON response indicating successful logout
    jsonResponse(true, "Logout successful.");
}

function fetchDashboardData($conn) {
    // Calculate total earnings
    $earningsQuery = "SELECT SUM(amount) AS totalEarnings FROM payments WHERE payment_status = 'Completed'";
    $earningsResult = $conn->query($earningsQuery);
    $earnings = $earningsResult->fetch_assoc();

    // Calculate bookings counts
    $totalBookingsQuery = "SELECT COUNT(*) AS total FROM bookings";
    $currentBookingsQuery = "SELECT COUNT(*) AS current FROM bookings WHERE Status = 'Approved'";
    $pendingBookingsQuery = "SELECT COUNT(*) AS pending FROM bookings WHERE Status = 'Pending'";
    $cancelledBookingsQuery = "SELECT COUNT(*) AS cancelled FROM bookings WHERE Status = 'Cancelled'";

    $totalBookingsResult = $conn->query($totalBookingsQuery)->fetch_assoc();
    $currentBookingsResult = $conn->query($currentBookingsQuery)->fetch_assoc();
    $pendingBookingsResult = $conn->query($pendingBookingsQuery)->fetch_assoc();
    $cancelledBookingsResult = $conn->query($cancelledBookingsQuery)->fetch_assoc();

    // Scheduled customers
    // Assuming reg_date is in 'YYYY-MM-DD' format and you have indexed this field for performance
    $today = date('Y-m-d');
    $lastScheduledQuery = "SELECT b.firstname, b.lastname, b.reg_date, p.package_name FROM bookings b JOIN payments pay ON b.bookingid = pay.bookingid JOIN packages p ON pay.packageid = p.packageid WHERE b.reg_date < '$today' ORDER BY b.reg_date DESC LIMIT 1";
    $currentScheduledQuery = "SELECT b.firstname, b.lastname, b.reg_date, p.package_name FROM bookings b JOIN payments pay ON b.bookingid = pay.bookingid JOIN packages p ON pay.packageid = p.packageid WHERE b.reg_date = '$today'";
    $nextScheduledQuery = "SELECT b.firstname, b.lastname, b.reg_date, p.package_name FROM bookings b JOIN payments pay ON b.bookingid = pay.bookingid JOIN packages p ON pay.packageid = p.packageid WHERE b.reg_date > '$today' ORDER BY b.reg_date ASC LIMIT 1";    

    $lastScheduledResult = $conn->query($lastScheduledQuery)->fetch_assoc();
    $currentScheduledResult = $conn->query($currentScheduledQuery)->fetch_assoc();
    $nextScheduledResult = $conn->query($nextScheduledQuery)->fetch_assoc();

    jsonResponse(true, "Dashboard data fetched successfully.", [
        'totalEarnings' => $earnings['totalEarnings'],
        'totalBookings' => $totalBookingsResult['total'],
        'currentBookings' => $currentBookingsResult['current'],
        'pendingBookings' => $pendingBookingsResult['pending'],
        'cancelledBookings' => $cancelledBookingsResult['cancelled'],
        'lastScheduledCustomer' => $lastScheduledResult,
        'currentScheduledCustomer' => $currentScheduledResult,
        'nextScheduledCustomer' => $nextScheduledResult
    ]);
}

function fetchAllBookings($conn, $criteria = 'fullname', $direction = 'asc') {
    // Default order by clause
    $orderByClause = "ORDER BY CONCAT(b.firstname, ' ', b.lastname) $direction"; 

    // Adjust the ORDER BY clause based on the criteria and direction
    switch ($criteria) {
        case 'fullname':
            $orderByClause = ($direction == 'asc') ? 
                "ORDER BY CONCAT(b.firstname, ' ', b.lastname) ASC" : 
                "ORDER BY CONCAT(b.firstname, ' ', b.lastname) DESC";
            break;
        case 'reg_date':
            $orderByClause = ($direction == 'asc') ? 
                "ORDER BY b.reg_date ASC" : 
                "ORDER BY b.reg_date DESC";
            break;
        case 'package_price':
            $orderByClause = ($direction == 'asc') ? 
                "ORDER BY p.package_price ASC" : 
                "ORDER BY p.package_price DESC";
            break;
        default:
            // Fallback to default sorting by name ascending
            break;
    }

    $query = "SELECT 
                b.bookingid, 
                CONCAT(b.firstname, ' ', b.lastname) AS fullname, 
                b.email, 
                b.number, 
                b.reg_date, 
                b.Status AS booking_status, 
                p.package_name, 
                p.package_price, 
                pay.payment_date, 
                pay.payment_status
              FROM bookings b
              JOIN payments pay ON b.bookingid = pay.bookingid
              JOIN packages p ON pay.packageid = p.packageid
              $orderByClause";

    $result = $conn->query($query);
    $bookings = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!array_key_exists($row['bookingid'], $bookings)) {
                $bookings[$row['bookingid']] = $row;
            }
        }
        jsonResponse(true, "Bookings fetched successfully.", ['bookings' => array_values($bookings)]);
    } else {
        jsonResponse(false, "No bookings found.");
    }
}

function searchBookings($conn, $keyword) {
    $keyword = "%" . $keyword . "%";
    $query = "SELECT 
                b.bookingid, 
                CONCAT(b.firstname, ' ', b.lastname) AS fullname, 
                b.email, 
                b.number, 
                b.reg_date, 
                b.Status AS booking_status, 
                p.package_name, 
                p.package_price, 
                pay.payment_date, 
                pay.payment_status
              FROM bookings b
              JOIN payments pay ON b.bookingid = pay.bookingid
              JOIN packages p ON pay.packageid = p.packageid
              WHERE b.firstname LIKE ? OR b.lastname LIKE ? OR b.email LIKE ? OR p.package_name LIKE ? OR b.Status LIKE ? OR b.reg_date LIKE ?
              ORDER BY b.reg_date DESC, b.firstname ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssss", $keyword, $keyword, $keyword, $keyword, $keyword, $keyword);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }

    jsonResponse(true, "Search results fetched successfully.", ['bookings' => $bookings]);
}

function updateBookingDetails($conn, $bookingid, $reg_date, $package_name) {
    // Begin transaction to ensure data integrity
    $conn->begin_transaction();

    try {
        // Fetch the packageid and associated package_price for the provided package_name
        $packageStmt = $conn->prepare("SELECT packageid, package_price FROM packages WHERE package_name = ?");
        $packageStmt->bind_param("s", $package_name);
        $packageStmt->execute();
        $packageResult = $packageStmt->get_result();
        if ($packageResult->num_rows === 0) {
            throw new Exception("No package found with the provided name.");
        }
        $packageData = $packageResult->fetch_assoc();
        $packageid = $packageData['packageid'];
        $packagePrice = $packageData['package_price']; // Corrected to use 'package_price' as the column name

        // Update booking's registration date
        $bookingStmt = $conn->prepare("UPDATE bookings SET reg_date = ? WHERE bookingid = ?");
        $bookingStmt->bind_param("si", $reg_date, $bookingid);
        if (!$bookingStmt->execute()) {
            throw new Exception("Failed to update booking registration date.");
        }

        // Update the packageid in the payments table and set the new amount based on the package_price
        $paymentStmt = $conn->prepare("UPDATE payments SET packageid = ?, amount = ? WHERE bookingid = ?");
        $paymentStmt->bind_param("idi", $packageid, $packagePrice, $bookingid);
        if (!$paymentStmt->execute()) {
            throw new Exception("Failed to update the package in payments and set the new amount.");
        }

        // Commit the transaction
        $conn->commit();
        jsonResponse(true, "Booking updated successfully.");
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        jsonResponse(false, "Failed to update booking details: " . $e->getMessage());
    }
}


function fetchPackageNamesAndCurrentPackage($conn, $bookingid) {
    // Start by fetching the current package based on bookingid
    $currentPackageStmt = $conn->prepare("SELECT packages.packageid, packages.package_name FROM packages
                                          INNER JOIN payments ON payments.packageid = packages.packageid
                                          WHERE payments.bookingid = ?");
    $currentPackageStmt->bind_param("i", $bookingid);
    $currentPackageStmt->execute();
    $currentPackageResult = $currentPackageStmt->get_result();
    $currentPackage = $currentPackageResult->fetch_assoc();
    
    // Now fetch all packages to populate the dropdown
    $allPackagesStmt = $conn->prepare("SELECT packageid, package_name FROM packages");
    $allPackagesStmt->execute();
    $allPackagesResult = $allPackagesStmt->get_result();
    
    $packages = [];
    while ($row = $allPackagesResult->fetch_assoc()) {
        $packages[] = $row;
    }
    
    jsonResponse(true, "Packages fetched successfully.", [
        'currentPackage' => $currentPackage, 
        'allPackages' => $packages
    ]);
}

function handleFeedbackView($conn) {
    // Implementation of feedback viewing logic
}

function handleReportGeneration($conn) {
    // Implementation of report generation logic
}



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'admin_login':
                handleLogin($conn);
                break;
            case 'admin_logout':
                handleLogout();
                break;
            case 'fetchDashboardData':
                fetchDashboardData($conn);
                break;
            case 'fetchAllBookings':
                $criteria = $_POST['criteria'] ?? 'fullname'; // Default criteria
                $direction = $_POST['direction'] ?? 'asc'; // Default direction
                fetchAllBookings($conn, $criteria, $direction);
                break;                           
            case 'searchBookings':
                $keyword = $_POST['query'] ?? '';
                searchBookings($conn, $keyword);
                break;   
            case 'updateBookingDetails':
                $bookingid = $_POST['bookingid'] ?? null;
                $reg_date = $_POST['reg_date'] ?? null;
                $package_name = $_POST['package_name'] ?? null;
            if ($bookingid && $reg_date && $package_name) {
                updateBookingDetails($conn, $bookingid, $reg_date, $package_name);
            } else {
                jsonResponse(false, "Missing data for booking update.");
                }
                break; 
            case 'fetchPackageNamesAndCurrentPackage':
                $bookingid = $_POST['bookingid'] ?? null;
                if ($bookingid) {
                    fetchPackageNamesAndCurrentPackage($conn, $bookingid);
                } else {
                    jsonResponse(false, "Booking ID is required.");
                }
                break;    
            case 'viewFeedback':
                handleFeedbackView($conn);
                break;
            case 'generateReport':
                handleReportGeneration($conn);
                break;
            default:
            jsonResponse(false, "Invalid action.");
            break;
        }
    }
}
?>