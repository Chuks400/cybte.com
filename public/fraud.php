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

<title>Fraud Detection</title>
<link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">

<link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<h2>Fraud Detection Engine</h2>

<form method="POST" action="../src/api/fraud_score.php">

<label>Transaction ID</label>
<input type="text" name="transaction_id" required>

<br><br>

<label>Transaction Amount</label>
<input type="number" name="amount" required>

<br><br>

<label>Location</label>
<select name="location">
<option value="known">Known Location</option>
<option value="unknown">Unknown Location</option>
</select>

<br><br>

<label>Device</label>
<select name="device">
<option value="trusted">Trusted Device</option>
<option value="new">New Device</option>
</select>

<br><br>

<button type="submit">Analyze Transaction</button>

</form>

</body>

</html>