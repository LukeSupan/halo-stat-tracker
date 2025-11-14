<?php
session_start();
include '../database/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'];
    $password = $_POST['password'];

    // prepare select to get user if they exist
    // bind the username to it and execute
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    // get the result of that execute
    $result = $stmt->get_result();

    // if exactly 1 user exists go ahead (dupe users arent possible. just in case)
    if ($result->num_rows === 1) {

        // get user as associative array
        $user = $result->fetch_assoc();
        $hashed = $user['password'];

        // hash current password and compare to the hashed one
        if (password_verify($password, $hashed)) {

            // store user data in session to check later
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];

            // redirect
            header("Location: ../postauth/dashboard.php");
            exit();
        }
    }

    // if login fails dont login
    $error = "Invalid username or password";
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Halo: ST - Login</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="login-container">
        <h2>Login</h2>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? 
            <a href="register.php">Register here</a>
        </p>

        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
  </div>
</body>
</html>
