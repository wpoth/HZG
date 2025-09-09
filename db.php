<?php
// Externe database
$remote = new mysqli('DESKTOP-RHD406H', 'Aryan', 'UWL90X#!0SK', 'machtigingoverzicht');

// Lokale database (op Plesk)
$local = new mysqli('localhost', 'Aryan', 'UWL90X#!0SK', 'lokale_gegevens');

// Verbinding controleren
if ($remote->connect_error || $local->connect_error) {
    die("Connectie mislukt: " . $remote->connect_error . " / " . $local->connect_error);
}

// Tabelnaam die je wilt kopiëren
$clients = 'clients';

// Ophalen van gegevens
$result = $remote->query("SELECT * FROM $clients");

if (!$result) {
    die("Query mislukt: " . $remote->error);
}

while ($row = $result->fetch_assoc()) {
    $columns = implode(", ", array_keys($row));
    $values = array_map([$local, 'real_escape_string'], array_values($row));
    $escaped_values = "'" . implode("', '", $values) . "'";

    $sql = "INSERT INTO $clients ($columns) VALUES ($escaped_values)";
    if (!$local->query($sql)) {
        echo "Fout bij insert: " . $local->error . "<br>";
    }
}

echo "Klaar!";
?>
