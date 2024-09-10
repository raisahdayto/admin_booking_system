<?php
include("connect/session_check.php");

ob_start();
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
                        <a class="nav-link" href="dashboardView.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookingManagement.php">Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="feedbackView.php">Feedback</a>
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
    <br>
</main>

<script>
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
</script>

<?php $content = ob_get_clean(); ?>
<?php ob_start(); ?>
<?php $scripts = ob_get_clean(); ?>
<?php $in_concat = true; include 'layouts/base.php'; ?>
<script src="assets/js/default.js?=<?php echo $randomNumber; ?>"></script>