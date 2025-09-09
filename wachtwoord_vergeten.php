<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$mysqli = new mysqli('localhost', 'Jaden', '4eOx2j5#7', 'inlog_gegevens', 3306);
if ($mysqli->connect_errno) {
    exit("FOUT bij verbinden met MySQL: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];

    $stmt = $mysqli->prepare("SELECT ID FROM gegevens WHERE emailAddress = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt->close();
        $stmt = $mysqli->prepare("UPDATE gegevens SET reset_token = ?, reset_expires = ? WHERE emailAddress = ?");
        $stmt->bind_param('sss', $token, $expires, $email);
        $stmt->execute();


        // Dit zou je moeten vervangen met een echte mailfunctie zoals PHPMailer in productie
        mail($email, "Wachtwoord resetten", "Klik op deze link om je wachtwoord te resetten:\n$reset_link");

        echo "Als het e-mailadres bekend is, is er een resetlink verzonden.";
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Wachtwoord vergeten</title>
</head>
<body>
<h2>Wachtwoord vergeten</h2>
<form method="post">
    <label for="email">Voer je e-mailadres in:</label>
    <input type="email" name="email" id="email" required>
    <button type="submit">Verstuur resetlink</button>
</form>
</body>
</html>
