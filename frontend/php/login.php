<?php include 'header.php';?>
<body>
    <!-- Login Form -->
    <div class="container mt-5">
        <h2 class="mb-4">Login</h2>
        <?php
        if (isset($_POST['username']) && isset($_POST['password'])) {
            // Load RabbitMQ client function
            require_once 'client_rmq_db.php';

            $username = $_POST['username'];
            $password = $_POST['password'];

            $request = array();
            $request['type'] = "login";
            $request['username'] = $username;
            $request['password'] = $password;

            // Use the RabbitMQ client to validate login
            $response = createRabbitMQClientDatabase($request);

            if ($response['success']) {
                // Store the JWT token as a cookie (expires in 1 hour, same as the token's validity)
                setcookie('jwt_token', $response['token'], time() + 3600, "/", "", false, false);
                
                // Redirect to the index page
                echo "<script>
                    alert('Login successful!');
                    window.location.href = 'index.php';
                </script>";
            } else {
                echo "<div class='alert alert-danger'>{$response['message']}</div>";
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
