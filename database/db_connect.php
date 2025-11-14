<?php
// variables for setup
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wampville";

// creat a MySQL connection object and connect.
$conn = new mysqli($servername, $username, $password, $dbname);

// if connection fails let em know
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// if it succeeds theyll know

?>
