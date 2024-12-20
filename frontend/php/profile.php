<?php
include 'header.php';
include 'validation.php';
require_once 'client_rmq_db.php';

// fetch user's profile
$request = array();
$request['type'] = 'get_user_profile';
$request['user_id'] = $userId;

$response = createRabbitMQClientDatabase($request);

if ($response['success']) {
    $email = htmlspecialchars($response['user']['email']); // get user email
    $is2FAEnabled = $response['user']['2fa']; // 0 if disabled, 1 enabled
    $code = htmlspecialchars($response['user']['code'] ?? ''); // 4 digit random
} else {
    $error_message = htmlspecialchars($response['message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // enable button
    if (isset($_POST['enable_2fa'])) {
        $request = array();
        $request['type'] = 'generate_2fa';
        $request['user_id'] = $userId;

        $enableResponse = createRabbitMQClientDatabase($request);

        if ($enableResponse['success']) {
            $verificationCode = $enableResponse['verification_code'];
            mail($email, "Your 2FA Verification Code", "Your verification code is: $verificationCode");
            $codeSent = true; // Flag to indicate the code was sent
        } else {
            echo "<div class='alert alert-danger'>Failed to enable 2FA: {$enableResponse['message']}</div>";
	}
    // disable button
    } elseif (isset($_POST['disable_2fa'])) {
        $request = array();
        $request['type'] = 'disable_2fa';
        $request['user_id'] = $userId;

        $disableResponse = createRabbitMQClientDatabase($request);

        if ($disableResponse['success']) {
		header("Location: " .$_SERVER['PHP_SELF']); //refresh page
        } else {
            echo "<div class='alert alert-danger'>Failed to disable 2FA: {$disableResponse['message']}</div>";
	}
    // compare with what's in database
    } elseif (isset($_POST['submit_verification_code'])) {
        $enteredCode = $_POST['verification_code'];

        $request = array();
        $request['type'] = 'verify_2fa_code';
        $request['user_id'] = $userId;
        $request['verification_code'] = $enteredCode;

        $verifyResponse = createRabbitMQClientDatabase($request);

        if ($verifyResponse['success']) {
		echo "<div class='alert alert-success'>2FA has been successfully enabled.</div>";
		header("Location: " .$_SERVER['PHP_SELF']); //refresh page
        } else {
            echo "<div class='alert alert-danger'>Invalid verification code. Please try again.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <!-- Bootstrap CSS -->
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Contact Info</h1><br>

        <!-- Display user's email -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php else: ?>
            <div class="mb-3">
                <strong>Email: </strong> <?php echo $email; ?>
            </div>
            <div class="mb-3">
                <strong>Two-Factor Authentication:</strong>
                <?php echo $is2FAEnabled ? 'Enabled' : 'Disabled'; ?>
            </div>

            <?php if ($is2FAEnabled): ?>
                <form method="post">
                    <button type="submit" name="disable_2fa" class="btn btn-danger">Disable 2FA</button>
                </form>
            <?php else: ?>
                <form method="post">
                    <button type="submit" name="enable_2fa" class="btn btn-primary">Enable 2FA</button>
                </form>
            <?php endif; ?>

            <!-- Display the form for entering the verification code if the code has been sent -->
            <?php if (isset($codeSent) && $codeSent): ?>
                <div class="mt-4">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="verification_code">Enter the 4-digit verification code:</label>
                            <input type="text" name="verification_code" id="verification_code" maxlength="4" required class="form-control">
                        </div>
                        <button type="submit" name="submit_verification_code" class="btn btn-success">Submit</button>
                    </form>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
