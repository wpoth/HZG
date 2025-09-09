<?php
$variable = array("key1" => "value1", "key2" => "value2");
var_dump($variable);
print_r($variable);

error_reporting(E_ALL);
ini_set('display_errors', 1);

// === Instellingen voor externe databaseverbinding ===
$host     = '192.168.2.139';     // Vervang met het IP of de hostname van de externe server
$user     = 'Aryan';             // Vervang met jouw MySQL-gebruikersnaam
$password = 'UWL90X#!0SK';                 // Vervang met jouw MySQL-wachtwoord
$database = 'Machtigingoverzicht';               // Vervang met de naam van de database
$tabel    = 'Clients';                  // Vervang met de tabelnaam die je wilt uitlezen

// === Verbinden met MySQL-server ===
$mysqli = new mysqli($host, $user, $password, $database);

// === Foutafhandeling ===
if ($mysqli->connect_error) {
    die("❌ Verbinding mislukt: " . $mysqli->connect_error);
}

// === Query uitvoeren ===
$sql = "SELECT * FROM `$tabel`";
$result = $mysqli->query($sql);

// === Resultaat tonen ===
if ($result && $result->num_rows > 0) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "⚠️ Geen gegevens gevonden of fout in query.";
}

// === Verbinding sluiten ===
$mysqli->close();

?>