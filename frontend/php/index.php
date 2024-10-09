<?php

?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <!-- Bootstrap CSS -->
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">IT490-Project</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                    <!-- Logout Button, initially hidden -->
                    <li class="nav-item" id="logoutButton" style="display: none;">
                        <a class="nav-link" href="#" onclick="logout()">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content Section -->
    <div class="container mt-5">
        <h1>Welcome to IT490-Project's Homepage</h1>
        <p>This is a simple homepage created using Bootstrap 5.3.3 and PHP.</p>
    </div>

    <!-- Bootstrap JS -->
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to check if the JWT token exists and toggle the Logout button
        document.addEventListener("DOMContentLoaded", function() {
            // Check for JWT token in localStorage
            if (localStorage.getItem('token')) {
                // Show the Logout button and hide the Login/Register buttons
                document.getElementById("logoutButton").style.display = "block";
                document.querySelector('a[href="login.php"]').style.display = "none";
                document.querySelector('a[href="register.php"]').style.display = "none";
            }
        });

        // Logout function to remove JWT token and redirect to the login page
        function logout() {
            // Remove the JWT token from localStorage
            localStorage.removeItem('token');
            // Redirect to the login page
            window.location.href = 'login.php';
        }
    </script>
</body>
</html>

