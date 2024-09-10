<?php
include("connect/session_check.php");

ob_start();
// Add any styles you need here
$styles = ob_get_clean();
ob_start();
?>

<main>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboardView.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookingManagement.php">Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="feedbackView.php">Feedback</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                    <a class="nav-link" href="#" id="admin_logout">Log Out</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div id="dashboardData" class="container mt-4"></div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    fetchDashboardData();
});

function fetchDashboardData() {
    fetch('server/admin_operation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=fetchDashboardData'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            const totalEarnings = parseFloat(data.totalEarnings);
            const formattedEarnings = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PHP' }).format(totalEarnings);
            
            const dashboard = document.getElementById('dashboardData');
            dashboard.innerHTML = `
            <div class="row text-center mb-4">
                <div class="col-xl-8 col-lg-7 col-md-12 mb-4">
                    <div class="card h-100 border-0 shadow">
                        <div class="card-body p-5 bg-info text-white rounded">
                            <h3 class="card-title"><strong>Total Earnings</strong></h3>
                            <p class="card-text fs-1">${formattedEarnings}</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-5 col-md-12 mb-4">
                    <div class="card h-100 border-0 shadow bg-primary text-white">
                        <div class="card-body p-5">
                            <h3 class="card-title"><strong>Total Bookings</strong></h3>
                            <p class="card-text fs-1">${data.totalBookings}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row text-center mb-4">
                <div class="col-lg-4 col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow bg-success text-white">
                        <div class="card-body p-5">
                            <h5 class="card-title">Current Bookings</h5>
                            <p class="card-text">${data.currentBookings}</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow bg-warning text-dark">
                        <div class="card-body p-5">
                            <h5 class="card-title">Pending Bookings</h5>
                            <p class="card-text">${data.pendingBookings}</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow bg-danger text-white">
                        <div class="card-body p-5">
                            <h5 class="card-title">Cancelled Bookings</h5>
                            <p class="card-text">${data.cancelledBookings}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row text-center mb-4">
                <div class="col-lg-4 col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow">
                        <div class="card-header bg-secondary text-white">Last Scheduled Customer</div>
                        <div class="card-body bg-light">
                            <h5 class="card-title">${data.lastScheduledCustomer ? `${data.lastScheduledCustomer.firstname} ${data.lastScheduledCustomer.lastname}` : 'None'}</h5>
                            <p class="card-text">${data.lastScheduledCustomer ? `${data.lastScheduledCustomer.reg_date} <br> ${data.lastScheduledCustomer.package_name} Package` : ''}</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow">
                        <div class="card-header bg-secondary text-white">Current Scheduled Customer</div>
                        <div class="card-body bg-light">
                            <h5 class="card-title">${data.currentScheduledCustomer ? `${data.currentScheduledCustomer.firstname} ${data.currentScheduledCustomer.lastname}` : 'None'}</h5>
                            <p class="card-text">${data.currentScheduledCustomer ? `${data.currentScheduledCustomer.reg_date} <br> ${data.currentScheduledCustomer.package_name} Package` : ''}</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow">
                        <div class="card-header bg-secondary text-white">Next Scheduled Customer</div>
                        <div class="card-body bg-light">
                            <h5 class="card-title">${data.nextScheduledCustomer ? `${data.nextScheduledCustomer.firstname} ${data.nextScheduledCustomer.lastname}` : 'None'}</h5>
                            <p class="card-text">${data.nextScheduledCustomer ? `${data.nextScheduledCustomer.reg_date} <br> ${data.nextScheduledCustomer.package_name} Package` : ''}</p>
                        </div>
                    </div>
                </div>
            </div>
            `;

        } else {
            alert('Failed to fetch dashboard data. Please try again.');
        }
    })
    .catch(error => console.error('Error fetching dashboard data:', error));
}

document.addEventListener("DOMContentLoaded", function() {
    const logoutLink = document.getElementById('admin_logout'); // Corrected to 'admin_logout'
    if (logoutLink) {
        logoutLink.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent the default link action
            logout();
        });
    }
});

function logout() {
    // Use POST method and include action in the body
    fetch('server/admin_operation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=admin_logout' // Specify the action parameter
    })
    .then(response => response.json()) // Handle JSON response
    .then(data => {
        if(data.status) {
            window.location.href = 'index.php'; // Redirect on successful logout
        } else {
            console.error('Logout failed:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
</script>/

<?php $content = ob_get_clean(); ?>
<?php ob_start(); ?>
<?php $scripts = ob_get_clean(); ?>
<?php $in_concat = true; include 'layouts/base.php'; ?>
<script src="assets/js/default.js?=<?php echo $randomNumber; ?>"></script>