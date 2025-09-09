<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

// DB-verbinding
$mysqli = new mysqli('localhost', 'Jaden', '4eOx2j5#7', 'inlog_gegevens', 3306);
if ($mysqli->connect_errno) {
    exit("FOUT bij verbinden met MySQL: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['wachtwoord'])) {
    $email = $_POST['email'];
    $wachtwoord = $_POST['wachtwoord'];
    $role = $_POST['role'] ?? 'user';

    // Alleen bedrijfsemails toestaan
    $toegestaan = str_ends_with(strtolower($email), '@hollandsezorggroep.nl');
    if (!$toegestaan) {
        $fakeHash = password_hash($wachtwoord, PASSWORD_DEFAULT);
        logLoginAttempt($mysqli, $email, $fakeHash, 'Error');
        $_SESSION['login_error'] = "Alleen e-mailadressen van @hollandsezorggroep.nl zijn toegestaan.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Zoek gebruiker in medewerkers
    $stmt = $mysqli->prepare("SELECT ID, wachtwoord FROM login_logs WHERE emailAddress = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        $fakeHash = password_hash($wachtwoord, PASSWORD_DEFAULT);
        logLoginAttempt($mysqli, $email, $fakeHash, 'Error');
        $_SESSION['login_error'] = "Je bent geen geregistreerde medewerker. Neem contact op met beheer.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $stmt->bind_result($id, $hashed_db);
    $stmt->fetch();

    if (empty($hashed_db)) {
        logLoginAttempt($mysqli, $email, '', 'Error');
        $_SESSION['login_error'] = "Account heeft geen wachtwoord. Neem contact op met beheer.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (password_verify($wachtwoord, $hashed_db)) {
        logLoginAttempt($mysqli, $email, $hashed_db, 'Succes');

        session_regenerate_id(true);
        $_SESSION['user_logged_in'] = true;
        $_SESSION['ID'] = $id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = explode('@', $email)[0];
        $_SESSION['role'] = $role;

        header("Location: login_melding.php");
        exit;
    } else {
        $fakeHash = password_hash($wachtwoord, PASSWORD_DEFAULT);
        logLoginAttempt($mysqli, $email, $fakeHash, 'Error');
        $_SESSION['login_error'] = "Verkeerd wachtwoord!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

}

$mysqli->close();

function logLoginAttempt($mysqli, $email, $hash, $resultaat) {
    $stmt = $mysqli->prepare("INSERT INTO login_logs (emailAddress, wachtwoord, resultaat) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $hash, $resultaat);
    $stmt->execute();
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="index.css">
    <title>Login</title>
</head>

<body>
<img src="img/logo.png" alt="HZG logo" class="logo">

<div class="login">
    <h2>Inloggen bij InternHZG</h2>
    <?php if ($error): ?>
        <p class="foutmelding"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="" method="post">
        <label for="email">Gebruikersnaam:</label>
        <input type="text" id="email" autocomplete="on" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />

        <label for="wachtwoord">Wachtwoord:</label>
        <div class="password-wrapper">
            <input type="password" id="wachtwoord" name="wachtwoord" required />
            <span class="toggle-password" onclick="toggleWachtwoord()"></span>
        </div>
        <label for="role">Inloggen als:</label>
        <select name="role" id="role">
            <option value="user">Gebruiker</option>
            <option value="admin">Admin</option>
        </select>

        <button type="submit">Inloggen</button>
    </form>
    <div class="extra-actions">
        <a class="button-link" href="https://intern.hzgzorg.nl/sso-login.php">Inloggen met Microsoft SSO</a>
    </div>

    <a class="button-link" href="https://passwordreset.microsoftonline.com/">Wachtwoord vergeten?</a>
</div>


<script>
    function toggleWachtwoord() {
        const wachtwoordInput = document.getElementById("wachtwoord");
        const eyeIcon = document.getElementById("eyeIcon");

        if (wachtwoordInput.type === "password") {
            wachtwoordInput.type = "text";
            eyeIcon.innerHTML = `
    <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.36 21.36 0 0 1 5.17-6.11"/>
    <path d="M1 1l22 22"/>`;
        } else {
            wachtwoordInput.type = "password";
            eyeIcon.innerHTML = `
    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
    <circle cx="12" cy="12" r="3"/>`;

        }
    }
</script>

</body>
</html>