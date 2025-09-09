<?php
// Verbindingsgegevens (pas deze aan!)
$host = "localhost";
$db = "lokale_gegevens";
$user = "Aryan";
$pass = "UWL90X#!0SK";

// Maak verbinding met MySQL
$conn = new mysqli($host, $user, $pass, $db);

// Check verbinding
if ($conn->connect_error) {
    die(json_encode(["error" => "Verbinding mislukt: " . $conn->connect_error]));
}

// Query om de nieuwste gegevens op te halen
$sql = "SELECT * FROM clients ORDER BY DESC";

$result = $conn->query($sql);

// Controleer of er resultaten zijn
if ($result->num_rows > 0) {
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Zet de data om naar JSON
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
} else {
    echo json_encode(["message" => "Geen resultaten gevonden."]);
}

// Sluit de verbinding
$conn->close();
?>
