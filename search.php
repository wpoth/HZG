<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(["error" => "Niet ingelogd"]);
    exit;
}

// DEBUG instellingen (alleen voor development!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED); // <- Deprecated warnings onderdrukken indien gewenst


ob_start(); // Start output buffering

header("Content-Type: application/json");

// 🪵 Logging (optioneel pad aanpassen)
$debugLogPath = __DIR__ . "/debug.log";
file_put_contents($debugLogPath, "🔍 GET query: " . ($_GET['query'] ?? '[leeg]') . PHP_EOL, FILE_APPEND);

// 📦 DB-verbinding (gegevens invullen)
$conn = new mysqli("localhost:3306", "Aryan", "UWL90X#!0SK", "Intern_gegevens");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Verbinding mislukt: " . $conn->connect_error]);
    exit;
}

// 🔍 Input ophalen en veilig maken
$search = $_GET['query'] ?? '';
$searchTerm = "%" . $search . "%";

// 📄 SQL-query voorbereiden
$sql = "SELECT firstName AS firstName, lastName AS lastName, identificationNo FROM clients 
        WHERE firstName LIKE ? OR lastName LIKE ? OR identificationNo LIKE ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Query voorbereiding mislukt: " . $conn->error]);
    exit;
}

$stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Query uitvoeren mislukt: " . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$clients = [];

while ($row = $result->fetch_assoc()) {
    // Zorg dat alles in UTF-8 is
    array_walk_recursive($row, function (&$val) {
        if (!mb_detect_encoding($val, 'UTF-8', true)) {
            $val = utf8_encode($val);
        }
    });
    $clients[] = $row;
}

$stmt->close();
$conn->close();

// ❗ Check op onverwachte output (HTML/foutmeldingen)
$buffer = ob_get_clean();
if (!empty(trim($buffer))) {
    file_put_contents($debugLogPath, "❗ Onverwachte output:\n" . $buffer, FILE_APPEND);
    http_response_code(500);
    echo json_encode(["error" => "Onverwachte output:\n" . $buffer]);
    exit;
}

// 🔚 JSON-response sturen
$json = json_encode($clients);
if ($json === false) {
    file_put_contents($debugLogPath, "❌ json_encode fout: " . json_last_error_msg() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo json_encode(["error" => "JSON codering mislukt: " . json_last_error_msg()]);
    exit;
}

echo $json;


