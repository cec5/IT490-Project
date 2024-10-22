<!-- header.php -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT490-Project</title>
    <!-- Bootstrap CSS -->
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">IT490-Project</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                    <!-- Buttons hidden when not logged in -->
                    <li class="nav-item" id="myLeagues" style="display: none;">
                        <a class="nav-link" href="myleagues.php"">My Leagues</a>
                    </li>
                    <li class="nav-item" id="logoutButton" style="display: none;">
                        <a class="nav-link" href="#" onclick="logout()">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Logout script and conditional display for Logout button -->
    <script>
        // Helper function to get a cookie value by name
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }

        document.addEventListener("DOMContentLoaded", function() {
            // Check for JWT token in cookies
            const jwtToken = getCookie('jwt_token');
            if (jwtToken) {
                // Show the Logout button and hide Login/Register links
                document.getElementById("logoutButton").style.display = "block";
                document.getElementById("myLeagues").style.display = "block";
                document.querySelector('a[href="login.php"]').style.display = "none";
                document.querySelector('a[href="register.php"]').style.display = "none";
            }
        });

        function logout() {
            // Remove the JWT token cookie by setting its expiration to a past date
            document.cookie = "jwt_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            window.location.href = 'login.php';
        }
    </script>
</body>
</html>

