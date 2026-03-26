<?php

session_start();

require_once __DIR__ . '/../src/controllers/ApiController.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$apiKey = "";

if(isset($_POST['generate'])){

    $controller = new ApiController();

    $apiKey = $controller->createKey($_SESSION['user_id']);
}

?>

<!DOCTYPE html>
<html>

<head>

<title>API Keys</title>

<link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<h2>Generate API Key</h2>

<form method="POST">

<button name="generate">Generate API Key</button>

</form>

<?php if($apiKey): ?>

<h3>Your API Key</h3>

<p><?php echo $apiKey; ?></p>

<?php endif; ?>

</body>

</html>