<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Booking System</title>

    <link rel="stylesheet" href="../styling/styling.css">
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
</head>
<body>
    <img src="../assets/hotel_banner.png" alt="Hotel Booking System" class="header-image">
   
    <div class="container">
        <h1>Hotel Booking System</h1>
        <button id="logoutBtn" class="logout-btn">Logout</button>

        <div class="booking-form">
            <h2>Add New Booking</h2>
            <form id="bookingForm">
                <div class="form-group">
                    <label for="hotel">Hotel:</label>
                    <select id="hotel" required>
                        <!-- Hotels will be populated dynamically -->
                    </select>
                </div>
               
                <div class="form-group">
                    <label for="guestName">Buyer Guest Name:</label>
                    <input type="text" id="guestName" required>
                </div>

                <div class="form-group">
                    <label for="guestCount">Number of Guests:</label>
                    <div class="guest-controls">
                        <button type="button" onclick="decrementGuests()" class="guest-btn">-</button>
                        <input type="tel" pattern="[0-9]*" inputMode="numeric" id="guestCount" value="1" min="1" max="10" required readonly>
                        <button type="button" onclick="incrementGuests()" class="guest-btn">+</button>
                    </div>
                </div>
               
                <div class="form-group">
                    <label for="checkIn">Check-in Date:</label>
                    <input type="date" id="checkIn" required>
                </div>
               
                <div class="form-group">
                    <label for="checkOut">Check-out Date:</label>
                    <input type="date" id="checkOut" required>
                </div>
               
                <button type="submit">Add Booking</button>

                <div class="bookings-list" id="bookingsList">
                    <!-- Bookings will be populated here -->
                </div>
            </form>
        </div>
       
    </div>

    <script>
    // Pasăm valoarea direct din PHP în JavaScript
    window.isAdmin = <?php echo isset($_SESSION['is_admin']) && $_SESSION['is_admin'] ? 'true' : 'false'; ?>;
    </script>
    <script src="../fetch.js"></script>

</body>
</html>
