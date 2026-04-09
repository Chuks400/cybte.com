<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>

<title>Security Scanner</title>
<link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">

<link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<h2>Website Vulnerability Scanner</h2>

<form method="POST" action="../src/api/vuln_scan.php">

<label>Website URL</label>

<input type="text" name="target_url" placeholder="https://example.com" required>

<br><br>

<button type="submit">Scan Website</button>

</form>

</body>

</html>