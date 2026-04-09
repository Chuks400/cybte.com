<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

require_once "../src/config/database.php";

$database = new Database();
$conn = $database->connect();

$query = "SELECT id, name, email, created_at FROM users ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>

<title>Users - CYBTE AI</title>
<link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">

<link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<h2>Registered Users</h2>

<table border="1" cellpadding="10">

<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Registered</th>
</tr>

<?php foreach($users as $user): ?>

<tr>

<td><?php echo $user['id']; ?></td>

<td><?php echo htmlspecialchars($user['name']); ?></td>

<td><?php echo htmlspecialchars($user['email']); ?></td>

<td><?php echo $user['created_at']; ?></td>

</tr>

<?php endforeach; ?>

</table>

<br>

<a href="dashboard.php">⬅ Back to Dashboard</a>

</body>
</html>