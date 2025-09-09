<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();


require 'vendor/autoload.php';

use Jumbojett\OpenIDConnectClient;


$oidc = new OpenIDConnectClient(
    'https://login.microsoftonline.com/9e8525a2-2612-4bfc-81c1-d3b5e6101cc2/v2.0',
    'dad52612-8d19-4a84-82ef-6c4720fef72b',
    '973c5369-f14b-42ef-b8de-7adea3ddad9c'
);

$oidc->setRedirectURL('https://intern.hzgzorg.nl/callback.php');
$oidc->addScope(['openid', 'profile', 'email', 'https://graph.microsoft.com/User.Read']);
$oidc->addAuthParam(['prompt' => 'select_account']);
$oidc->authenticate();

$userName = $oidc->requestUserInfo('name');
$userEmail = $oidc->requestUserInfo('email')
    ?? $oidc->requestUserInfo('preferred_username')
    ?? '';
$loginTime = date('Y-m-d H:i:s');

if (empty($userEmail)) {
    error_log("⚠️ Geen e-mailadres opgehaald uit Microsoft Graph.");
    exit("E-mailadres ontbreekt, kan niet loggen.");
}


$mysqli = new mysqli('127.0.0.1', 'Jaden', '4eOx2j5#7', 'inlog_gegevens');
if ($mysqli->connect_error) {
    error_log("❌ Databaseconnectie mislukt: " . $mysqli->connect_error);
    exit;
}

$stmt = $mysqli->prepare("INSERT INTO SSO_logins (user_name, user_email, login_time) VALUES (?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("sss", $userName, $userEmail, $loginTime);
    if (!$stmt->execute()) {
        error_log("❌ Insert mislukt: " . $stmt->error);
    } else {
        error_log("✅ Gebruiker gelogd: $userEmail op $loginTime");
    }
    $stmt->close();
} else {
    error_log("❌ Prepare statement mislukt: " . $mysqli->error);
}

$mysqli->close();
