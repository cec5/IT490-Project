<?php include 'header.php';?>
<body>
    <!-- Registration Form -->
    <div class="container mt-5">
        <h2 class="mb-4">Register</h2>
        <?php
        if (isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password'])) {
            // Load RabbitMQ client function
            require 'client_rmq_db.php';

            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];

	    $request = array();
	    $request['type'] = "register";
	    $request['username'] = $username;
	    $request['email'] = $email;
	    $request['password'] = $password;

            // Use the RabbitMQ client to handle registration
            $response = createRabbitMQClientDatabase($request);

            if ($response['success']) {
                echo "<div class='alert alert-success'>Registration successful! <a href='login.php'>Login here</a></div>";
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
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <p class="mt-3">Already have an account? <a href="login.php">Login here</a>.</p>
    </div>
    <!-- Bootstrap JS -->
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
