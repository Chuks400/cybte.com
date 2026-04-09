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

<title>Identity Verification</title>
<link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">

<link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<h2>Identity Verification (KYC)</h2>

<form method="POST" action="../src/api/verify_identity.php">

<label>Document Type</label>

<select name="document_type">
<option value="passport">Passport</option>
<option value="national_id">National ID</option>
<option value="drivers_license">Driver License</option>
</select>

<br><br>

<label>Document Number</label>

<input type="text" name="document_number" required>

<br><br>

<button type="submit">Submit Verification</button>

</form>

</body>

</html>