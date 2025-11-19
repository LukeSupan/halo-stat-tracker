<?php
session_start();

// destroy data
session_unset();
session_destroy();

// back to login. done
header("Location: login.php");
exit();
