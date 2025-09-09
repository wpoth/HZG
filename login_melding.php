<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Gebruiker';
$userEmail = isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : 'onbekend';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welkom, <?php echo $userName; ?></title>
    <link rel="stylesheet" href="login_melding.css">
</head>
<body>
<div class="container">
    <div class="alert-success">
        ✅ Je bent succesvol ingelogd!
    </div>

    <h1>Welkom, <?php echo $userName; ?>!</h1>
    <p>Je bent ingelogd met het e-mailadres: <strong><?php echo $userEmail; ?></strong></p>
    <p>Je login is gevalideerd en geregistreerd.</p>

    <a class="logout-btn" href="logout.php">Uitloggen</a>
    <a class="next-btn" href="mysqli_test.php">Ga verder</a>
</div>
</body>
</html>
