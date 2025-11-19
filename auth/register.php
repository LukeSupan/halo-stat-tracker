<?php
session_start();
include '../database/db_connect.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'];
    $password = $_POST['password'];

    // hash password first
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // attempt to put username and password in
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hashed);

    // try to add it. if not show error
    try {
        $stmt->execute();
        header("Location: login.php");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            $error = "Username already exists";
        } else {
            $error = "Registration failed: " . $e->getMessage();
        }
    }

    // if register fails dont go to login
    $error = "Username already exists";
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Halo: ST - Register</title>
    <link rel="stylesheet" href="../public/css/styles.css">
</head>

<body>
    <div class="page-wrapper">
        <h1 class="site-title">Halo: Stat Tracker</h1>
        <div class="auth-container">
            <h2>Register</h2>
            <form method="POST" action="">
                <input type="text" name="username" placeholder="Username" required><br>
                <input type="password" name="password" placeholder="Password" required><br>
                <button type="submit">Register</button>
            </form>

            <p class="auth-switch-text">Have an account? 
                <a href="login.php">Login here</a>
            </p>

            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        </div>
    </div>
</body>
</html>
