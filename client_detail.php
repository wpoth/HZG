<?php
// Functie om netjes een veld weer te geven
function showField($label, $value) {
    if (!is_null($value)) {
        $value = htmlspecialchars($value);
        $class = 'value';
    } else {
        $value = '-';
        $class = 'value empty';
    }
    echo "<div class='field'><span class='label'>$label:</span><span class='$class'>$value</span></div>";
}

// ID ophalen en valideren
$client_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Verbinding maken met database (voeg je eigen gegevens toe indien nodig)
$servername = "localhost:3306";
$username = "Aryan";
$password = "UWL90X#!0SK";
$dbname = "Intern_gegevens";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Verbindingsfout: " . $conn->connect_error);
}

if ($client_id) {
    // SQL-query
    $sql = "
    SELECT 
        c.identificationNo, c.firstName, c.lastName, c.initials, c.dateOfBirth,
        c.birthName, c.partnerName, c.birthNamePrefix, c.partnerNamePrefix,
        c.gender, c.deathDate, c.bsn, c.bsnSourceVerified, c.bsnIdVerified,
        c.bsnIdNumber, c.bsnIdType, c.bsnSource, c.bsnIdentityVerifiedType,
        c.emailAddress, c.mobilePhone, l.name AS locatie, c.createdAt,
        MAX(ca.dateBegin) AS inzorg, MAX(ca.dateEnd) AS uitzorg,
        GROUP_CONCAT(ca.dateEnd ORDER BY ca.dateEnd SEPARATOR ', ') AS archief
    FROM clients c
    LEFT JOIN care_allocations ca ON c.objectId = ca.clientObjectId
    LEFT JOIN location_assignments la ON c.objectId = la.clientObjectId
    INNER JOIN locations l ON la.locationObjectId = l.objectId AND l.identificationNo LIKE '2%'
    WHERE c.identificationNo = ?
    GROUP BY c.identificationNo
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL fout: " . $conn->error);
    }

    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $client = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliëntgegevens</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="back-button">
    <a href="mysqli_test.php">&larr; Terug naar overzicht</a>
</div>

<div class="client-details">
    <h2>Cliëntgegevens</h2>

    <?php
    if ($client) {
        showField("Cliëntnummer", $client['identificationNo']);
        showField("Voornaam", $client['firstName']);
        showField("Achternaam", $client['lastName']);
        showField("Geboortedatum", $client['dateOfBirth']);
        showField("Geboortenaam", $client['birthName']);
        showField("Partnernaam", $client['partnerName'] ?? null);
        showField("Initialen", $client['initials']);
        showField("Tussenvoegsel geboortenaam", $client['birthNamePrefix']); 
        showField("Tussenvoegsel partnernaam", $client['partnerNamePrefix'] ?? null);
        showField("Geslacht", $client['gender']);
        showField("Overlijdensdatum", $client['deathDate'] ?? null);
        showField("BSN", $client['bsn']);
        showField("BSN-bron geverifieerd", $client['bsnSourceVerified']);
        showField("BSN-ID geverifieerd", $client['bsnIdVerified']);
        showField("BSN-ID-nummer", $client['bsnIdNumber']);
        showField("BSN-ID-type", $client['bsnIdType']);
        showField("BSN-bron", $client['bsnSource']);
        showField("BSN-identiteitsverificatie type", $client['bsnIdentityVerifiedType']);
        showField("E-mailadres", $client['emailAddress'] ?? null);
        showField("Telefoonnummer", $client['mobilePhone'] ?? null);
        showField("Locatie", $client['locatie'] ?? '');
        showField("Aangemaakt op", $client['createdAt']);
        showField("In zorg", $client['inzorg'] ?? '');
        showField("Uit zorg", $client['uitzorg'] ?? '');
        showField("Archief", $client['archief'] ?? '');
    } else {
        echo "<p class='error-message'>⚠️ Cliënt niet gevonden.</p>";
    }
    ?>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
