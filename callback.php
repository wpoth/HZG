<?php
// Toon alle fouten behalve deprecated warnings
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php';

use Jumbojett\OpenIDConnectClient;
use Dotenv\Dotenv;

// ✅ Laad .env-configuratie
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$clientId     = $_ENV['OIDC_CLIENT_ID']     ?? null;
$clientSecret = $_ENV['OIDC_CLIENT_SECRET'] ?? null;
$redirectUrl  = $_ENV['OIDC_REDIRECT_URL']  ?? null;

if (!$clientId || !$clientSecret || !$redirectUrl) {
    error_log("❌ Ontbrekende OIDC-configuratie in .env");
    http_response_code(500);
    exit("Serverconfiguratie ontbreekt.");
}

// ✅ Initieer OIDC-client
$oidc = new OpenIDConnectClient(
    'https://login.microsoftonline.com/9e8525a2-2612-4bfc-81c1-d3b5e6101cc2/v2.0',
    $clientId,
    $clientSecret
);

$oidc->setRedirectURL($redirectUrl);
$oidc->addScope([
    'openid',
    'profile',
    'email',
    'https://graph.microsoft.com/User.Read'
]);

// ✅ Loggingfunctie
function logLoginAttempt(mysqli $mysqli, string $userName, string $userEmail, string $loginTime, string $resultaat): void {
    $stmt = $mysqli->prepare("INSERT INTO SSO_logins (user_name, user_email, login_time, resultaat) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $userName, $userEmail, $loginTime, $resultaat);
        if (!$stmt->execute()) {
            error_log("❌ Insert mislukt: " . $stmt->error);
        } else {
            error_log("📥 Login gelogd als '$resultaat': $userEmail op $loginTime");
        }
        $stmt->close();
    } else {
        error_log("❌ Prepare statement mislukt: " . $mysqli->error);
    }
}

try {
    // ✅ Authenticeer gebruiker via OIDC
    $oidc->authenticate();

    session_regenerate_id(true);
    $_SESSION['user_name']    = $oidc->requestUserInfo('name') ?? 'Onbekend';
    $_SESSION['user_email']   = $oidc->requestUserInfo('email')
        ?? $oidc->requestUserInfo('preferred_username')
        ?? 'onbekend@onbekend.com';
    $_SESSION['access_token'] = $oidc->getAccessToken();
    $_SESSION['id_token']     = $oidc->getIdToken();

    $userName  = $_SESSION['user_name'];
    $userEmail = $_SESSION['user_email'];
    $loginTime = date('Y-m-d H:i:s');

    // ✅ Verbind met database
    $mysqli = new mysqli('127.0.0.1', 'Jaden', '4eOx2j5#7', 'inlog_gegevens');
    if ($mysqli->connect_error) {
        error_log("❌ Databasefout: " . $mysqli->connect_error);
        exit("Databaseverbinding mislukt.");
    }

    error_log("🔍 Inlogpoging van: $userEmail");

    // ✅ Check op toegestaan domein
    if (!str_ends_with(strtolower($userEmail), '@hollandsezorggroep.nl')) {
        logLoginAttempt($mysqli, $userName, $userEmail, $loginTime, 'error');
        $mysqli->close();
        http_response_code(403);
        exit("Toegang geweigerd: alleen @hollandsezorggroep.nl accounts zijn toegestaan.");
    }

    // ✅ Succesvolle login loggen
    logLoginAttempt($mysqli, $userName, $userEmail, $loginTime, 'succes');
    $mysqli->close();

    $_SESSION['user_logged_in'] = true;

    // 🔁 Redirect na succesvolle login
    header('Location: login_melding.php');
    exit;

} catch (Exception $e) {
    // Fallback data
    $userName = 'Onbekend';
    $userEmail = 'onbekend@onbekend.com';
    $loginTime = date('Y-m-d H:i:s');

    error_log("❌ OIDC-authenticatie mislukt: " . $e->getMessage());

    // Probeer alsnog te loggen
    $mysqli = new mysqli('127.0.0.1', 'Jaden', '4eOx2j5#7', 'inlog_gegevens');
    if (!$mysqli->connect_error) {
        logLoginAttempt($mysqli, $userName, $userEmail, $loginTime, 'error');
        $mysqli->close();
    }

    http_response_code(500);
    exit('Er ging iets mis bij het inloggen. Neem contact op met de beheerder.');
}
