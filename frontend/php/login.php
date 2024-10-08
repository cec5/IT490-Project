<?php

?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Login Form -->
    <div class="container mt-5">
        <h2 class="mb-4">Login</h2>
        <?php
        if (isset($_POST['username']) && isset($_POST['password'])) {
            // Load RabbitMQ client function
            require 'client_rmq_db.php';

            $username = $_POST['username'];
	    $password = $_POST['password'];

	    $request = array();
	    $request['type'] = "login";
	    $request['username'] = $username;
	    $request['password'] = $password;

            // Use the RabbitMQ client to validate login
	    $isAuthenticated = createRabbitMQClientDatabase($request);

	    // For testing purposes ONLY, comment out otherwise!!!
	    echo "<div class='alert alert-success'>$isAuthenticated</div>";

            if ($isAuthenticated) {
                echo "<div class='alert alert-success'>Login successful!</div>";
                // Redirect or set session token as needed
            } else {
                echo "<div class='alert alert-danger'>Invalid username or password.</div>";
            }
        }
        ?>
        <form action="" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <p class="mt-3">Don't have an account? <a href="register.php">Register here</a>.</p>
    </div>

    <!-- Bootstrap JS -->
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
