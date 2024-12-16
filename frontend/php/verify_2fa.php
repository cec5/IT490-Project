<?php 
session_start(); // start session to access user ID
include 'header.php'; 
include 'validation.php';
require_once 'client_rmq_db.php';

$request = array();
$request['type'] = 'get_user_profile';
$request['user_id'] = $userId;

$response = createRabbitMQClientDatabase($request);

if ($response['success']) {
    $email = htmlspecialchars($response['user']['email']); // get email
    $is2FAEnabled = $response['user']['2fa']; // 0 if disabled, 1 if enabled
    $code = htmlspecialchars($response['user']['code'] ?? ''); // 4-digit random code
} else {
    $error_message = htmlspecialchars($response['message']);
}

// 2FA is enabled and no code has been generated, request new 2FA code
if ($is2FAEnabled && empty($code)) {
    $request = array();
    $request['type'] = 'generate_2fa';
    $request['user_id'] = $userId;

    $enableResponse = createRabbitMQClientDatabase($request);

    if ($enableResponse['success']) {
        $verificationCode = $enableResponse['verification_code']; // store code
        mail($email, "Your 2FA Verification Code", "Your verification code is: $verificationCode");

        // store code in session
        $_SESSION['2fa_code'] = $verificationCode;
        $_SESSION['2fa_code_sent'] = true;
    } else {
        echo "<div class='alert alert-danger'>Failed to generate 2FA code: {$enableResponse['message']}</div>";
    }
} elseif (!$is2FAEnabled) {
	header('Location: index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_verification_code'])) {
        $enteredCode = $_POST['verification_code'];

        // rabbit request for 2FA
        $request = array();
        $request['type'] = 'verify_2fa_code';
        $request['user_id'] = $userId;
        $request['verification_code'] = $enteredCode;

        $verifyResponse = createRabbitMQClientDatabase($request);

        if ($verifyResponse['success']) {
            // if code is correct, send through
            echo "<div class='alert alert-success'>2FA has been successfully verified.</div>";
            header('Location: index.php');
            exit();
        } else {
            // if code is wrong, error
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
    <title>2FA Verification</title>
    <!-- Bootstrap CSS -->
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
	<h2 class="mb-4">Enter 2FA Code</h2>

        <!-- Form to enter the 2FA code -->
        <?php if ($is2FAEnabled && isset($_SESSION['2fa_code_sent']) && $_SESSION['2fa_code_sent']): ?>
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

        <!-- If an error occurs, show it here -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
