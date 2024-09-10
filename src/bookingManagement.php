<?php
include("connect/session_check.php");

ob_start();
$styles = ob_get_clean();
ob_start();
?>

<main>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboardView.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="bookingManagement.php">Bookings</a>
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
    <br>
    <div class="container">
        <input type="text" class="form-control" id="searchInput" placeholder="Search bookings by Name, Email, Date,	Status, and Package..." oninput="searchBookings()"><br>
        <div class="container mt-3">
        <div class="row align-items-center">
            <div class="col-auto">
                Sort by:
                    </div>
                    <div class="col-auto">
                        <select id="sortCriteria" onchange="sortBookings()" class="form-select">
                            <option value="fullname">Name</option>
                            <option value="reg_date">Reservation Date</option>
                            <option value="package_price">Price</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select id="sortDirection" onchange="sortBookings()" class="form-select">
                            <option value="asc">Ascending</option>
                            <option value="desc">Descending</option>
                        </select>
                    </div>
                </div>
            </div>
            
        <!-- Place to display the filtered and sorted bookings -->
        <div id="bookingManagementData"></div>

    </div>
</main>

<!-- Update Modal -->
<div id="updateModal">
    <div class="modal-content">
        <span onclick="closeModal()" class="close">&times;</span>
        <h2>Update Booking</h2>
        <!-- Removed fields that are not needed -->
        Reservation Date: <input type="date" id="bookingRegDateUpdate" class="form-control mb-2">
        Package Name: <select id="bookingPackageNameUpdate" class="form-control mb-2"></select><br>
        <button type="button" onclick="submitUpdate()" class="btn btn-primary">Update Booking</button>
    </div>
</div>

<script>
let currentBookingIdToUpdate = null;

document.addEventListener("DOMContentLoaded", function() {
    fetchAllBookings();
});

function sortBookings() {
    const criteria = document.getElementById('sortCriteria').value;
    const direction = document.getElementById('sortDirection').value;
    fetchAllBookings(criteria, direction);
}

function fetchAllBookings(criteria = 'fullname', direction = 'asc') {
    // Update the POST body to include both criteria and direction
    fetch('server/admin_operation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=fetchAllBookings&criteria=${encodeURIComponent(criteria)}&direction=${encodeURIComponent(direction)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            displayBookings(data.bookings);
        } else {
            console.error('Failed to fetch bookings');
        }
    })
    .catch(error => console.error('Error fetching bookings:', error));
}


function displayBookings(bookings) {
    const bookingManagement = document.getElementById('bookingManagementData');
    let tableHTML = `<div class="table-responsive"><table class="table"><thead><tr><th>Full Name</th><th>Email</th><th>Number</th><th>Reservation Date</th><th>Booking Status</th><th>Package Name</th><th>Package Price</th><th>Payment Date</th><th>Payment Status</th><th></th></tr></thead><tbody>`;

    bookings.forEach(booking => {
        tableHTML += `<tr>
    <td>${booking.fullname}</td>
    <td>${booking.email}</td>
    <td>${booking.number}</td>
    <td>${booking.reg_date}</td>
    <td>${booking.booking_status}</td>
    <td>${booking.package_name}</td>
    <td>${booking.package_price}</td>
    <td>${booking.payment_date}</td>
    <td>${booking.payment_status}</td>
    <td>
        <button class="btn btn-primary" onclick="openUpdateModal('${booking.bookingid}', '${booking.reg_date}', '${booking.package_name}')">Update</button>
    </td>
</tr>`;
    });

    tableHTML += `</tbody></table></div>`;
    bookingManagement.innerHTML = tableHTML;
}


function searchBookings() {
    const query = document.getElementById('searchInput').value.trim();

    if (query) {
        fetch('server/admin_operation.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=searchBookings&query=${encodeURIComponent(query)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                displayBookings(data.bookings);
            } else {
                console.error('Search failed:', data.message);
                // Optionally, show a "no results found" message
            }
        })
        .catch(error => console.error('Error searching bookings:', error));
    }
}

function openUpdateModal(bookingid, reg_date) {
    currentBookingIdToUpdate = bookingid;
    // Calls the new function to get the package names and the current package
    fetchPackageNamesAndCurrentPackage(bookingid);
    setupRegDate(reg_date); // Setup the registration date input with disabled dates

    var modal = document.getElementById('updateModal');
    if (modal) {
        modal.style.display = 'block';
    } else {
        console.error('The update modal was not found in the DOM.');
    }
}

// New function to get both the package names and the current package
function fetchPackageNamesAndCurrentPackage(bookingid) {
    fetch('server/admin_operation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=fetchPackageNamesAndCurrentPackage&bookingid=${bookingid}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            populatePackageDropdown(data.currentPackage, data.allPackages);
        } else {
            console.error('Failed to fetch package names: ' + data.message);
        }
    })
    .catch(error => console.error('Error fetching package names:', error));
}

// New function to populate the package dropdown
function populatePackageDropdown(currentPackage, allPackages) {
    const packageSelect = document.getElementById('bookingPackageNameUpdate');
    packageSelect.innerHTML = allPackages.map(pkg => 
        `<option value="${pkg.package_name}" ${currentPackage && pkg.packageid === currentPackage.packageid ? 'selected' : ''}>${pkg.package_name}</option>`
    ).join('');
}


function setupRegDate(selectedDate) {
    fetch('../../user/src/server/user_operation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=fetchDisabledDates'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            const regDateInput = document.getElementById('bookingRegDateUpdate');
            regDateInput.value = selectedDate; // Set the current registration date
            flatpickr(regDateInput, {
                dateFormat: "Y-m-d",
                disable: data.disabledDates.map(date => new Date(date)),
                defaultDate: selectedDate,
                minDate: "today",
            });
        } else {
            console.error('Failed to fetch disabled dates:', data.message);
        }
    })
    .catch(error => {
        console.error('Error fetching disabled dates:', error);
    });
}


function closeModal() {
    document.getElementById('updateModal').style.display = 'none';
}

function submitUpdate() {
    const reg_date = document.getElementById('bookingRegDateUpdate').value;
    const package_name = document.getElementById('bookingPackageNameUpdate').value;

    fetch('server/admin_operation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=updateBookingDetails&bookingid=${currentBookingIdToUpdate}&reg_date=${reg_date}&package_name=${package_name}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            alert('Booking updated successfully.');
            fetchAllBookings();
            closeModal();
        } else {
            alert('Failed to update booking: ' + data.message);
        }
    })
    .catch(error => console.error('Error updating booking:', error));
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
</script>

<?php $content = ob_get_clean(); ?>
<?php ob_start(); ?>
<?php $scripts = ob_get_clean(); ?>
<?php $in_concat = true; include 'layouts/base.php'; ?>
<script src="assets/js/default.js?=<?php echo $randomNumber; ?>"></script>
