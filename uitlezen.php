<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
   

$servername = "localhost:3306";
$username = "Aryan";
$password = "UWL90X#!0SK";
$dbname = "Intern_gegevens";


// Maak de verbinding
$mysqli = new mysqli($servername, $username, $password, $dbname);


// Controleer de verbinding
if ($mysqli->connect_error) {
    die("Verbindingsfout: " . $mysqli->connect_error);
}

// Selecteer de lokale_gegevens database
if (!$mysqli->select_db("lokale_gegevens")) {
    die("Kan database 'lokale_gegevens' niet selecteren: " . $mysqli->error);
}

// Haal de gegevens op uit de tabel 'clients' in de gegevensverzorgende database
$query = "SELECT * FROM clients ";
$result = $mysqli->query($query);

if (!$result) {
    die("Fout in de query: " . $mysqli->error);
}

// Verwerk de resultaten en voeg ze in de tabel 'clients'
while ($row = $result->fetch_assoc()) {
  // Maak variabelen aan voor elke kolom in de $row en controleer of de waarde aanwezig is.
  $identificationNo = $row['identificationNo'] ?? null;
  $firstName = $row['firstName'] ?? null;
  $lastName = $row['lastName'] ?? null;
  $dateOfBirth = $row['dateOfBirth'] ?? null;
  $birthName = $row['birthName'] ?? null;
  $partnerName = $row['partnerName'] ?? null;
  $initials = $row['initials'] ?? null;
  $birthNamePrefix = $row['birthNamePrefix'] ?? null;
  $partnerNamePrefix = $row['partnerNamePrefix'] ?? null;
  $gender = $row['gender'] ?? null;
  $deathDate = $row['deathDate'] ?? null;
  $bsn = $row['bsn'] ?? null;
  $bsnSourceVerified = $row['bsnSourceVerified'] ?? null;
  $bsnIdVerified = $row['bsnIdVerified'] ?? null;
  $bsnIdNumber = $row['bsnIdNumber'] ?? null;
  $bsnIdType = $row['bsnIdType'] ?? null;
  $bsnSource = $row['bsnSource'] ?? null;
  $bsnIdentityVerifiedType = $row['bsnIdentityVerifiedType'] ?? null;
  $emailAddress = $row['emailAddress'] ?? null;
  $mobilePhone = $row['mobilePhone'] ?? null;
  $name = $row['name'] ?? null;
  $createdAt = $row['createdAt'] ?? null;
  $dateBegin = $row['dateBegin'] ?? null;
  $dateEnd = $row['dateEnd'] ?? null;
  $archief = $row['archief'] ?? null;
  $locatie = $row['locatie'] ?? null;
}

// SQL-query instellen met sortering op createdAt 
$query1 = "
SELECT 
    c.identificationNo, 
    c.firstName, 
    c.lastName, 
    c.initials, 
    c.dateOfBirth, 
    c.birthName, 
    c.partnerName, 
    c.birthNamePrefix, 
    c.partnerNamePrefix, 
    c.gender, 
    c.deathDate, 
    c.bsn, 
    c.bsnSourceVerified, 
    c.bsnIdVerified, 
    c.bsnIdNumber, 
    c.bsnIdType, 
    c.bsnSource, 
    c.bsnIdentityVerifiedType, 
    c.emailAddress, 
    c.mobilePhone, 
    l.name AS locatie,  
    c.createdAt, 
    MAX(ca.dateBegin) AS inzorg, 
    MAX(ca.dateEnd) AS uitzorg,
    GROUP_CONCAT(ca.dateEnd ORDER BY ca.dateEnd SEPARATOR ', ') AS archief  
FROM 
    lokale_gegevens.clients c
LEFT JOIN lokale_gegevens.care_allocations ca 
    ON c.objectId = ca.clientObjectId 
LEFT JOIN lokale_gegevens.location_assignments la 
    ON c.objectId = la.clientObjectId  
INNER JOIN lokale_gegevens.locations l  
    ON la.locationObjectId = l.objectId
    AND l.identificationNo LIKE '2%'  
WHERE 
    c.deathDate IS NULL  
GROUP BY 
    c.identificationNo, 
    c.firstName, 
    c.lastName, 
    c.initials, 
    c.dateOfBirth, 
    c.birthName, 
    c.partnerName, 
    c.birthNamePrefix, 
    c.partnerNamePrefix, 
    c.gender, 
    c.deathDate, 
    c.bsn, 
    c.bsnSourceVerified, 
    c.bsnIdVerified, 
    c.bsnIdNumber, 
    c.bsnIdType, 
    c.bsnSource, 
    c.bsnIdentityVerifiedType,   
    c.emailAddress, 
    c.mobilePhone, 
    c.createdAt, 
    c.archief,
    l.name  
ORDER BY 
    c.createdAt DESC;
";

$result1 = $mysqli->query($query1);

if (!$result1) {
    die("Fout in query: " . htmlspecialchars($mysqli->error));
}

// Toon gegevens in een tabel
echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr>
      <th>Cliëntnummer</th><th>Voornaam</th><th>Achternaam</th><th>Geboortedatum</th><th>Geboortenaam</th>
      <th>Partnernaam</th><th>Initialen</th><th>Tussenvoegsel geboortenaam</th><th>Tussenvoegsel partnernaam</th>
      <th>Geslacht</th><th>Overlijdensdatum</th><th>BSN</th><th>BSN-bron geverifieerd</th><th>BSN-ID geverifieerd</th>
      <th>BSN-ID-nummer</th><th>BSN-ID-type</th><th>BSN-bron</th><th>BSN-identiteitsverificatie type</th>
      <th>E-mailadres</th><th>Telefoonnummer</th><th>Locatie</th><th>Aangemaakt op</th><th>In zorg</th><th>Uit zorg</th><th>Archief</th>
    </tr>";

while ($row = $result1->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . ($row['identificationNo']) . "</td>";
    echo "<td>" . ($row['firstName']) . "</td>";
    echo "<td>" . ($row['lastName']) . "</td>";
    echo "<td>" . (!empty($row['dateOfBirth']) ? date("d-m-Y", strtotime($row['dateOfBirth'])) : '-') . "</td>";
    echo "<td>" . ($row['birthName']) . "</td>";
    echo "<td>" . ($row['partnerName']) . "</td>";
    echo "<td>" . ($row['initials']) . "</td>";
    echo "<td>" . ($row['birthNamePrefix']) . "</td>";
    echo "<td>" . ($row['partnerNamePrefix']) . "</td>";
    echo "<td>" . ($row['gender'] == '1' ? 'M' : ($row['gender'] == '2' ? 'V' : '-')) . "</td>";
    echo "<td>" . (!empty($row['deathDate']) ? date("d-m-Y", strtotime($row['deathDate'])) : '-') . "</td>";
    echo "<td>" . ($row['bsn']) . "</td>";
    echo "<td>" . ($row['bsnSourceVerified']) . "</td>";
    echo "<td>" . ($row['bsnIdVerified']) . "</td>";
    echo "<td>" . ($row['bsnIdNumber']) . "</td>";
    echo "<td>" . ($row['bsnIdType']) . "</td>";
    echo "<td>" . ($row['bsnSource']) . "</td>";
    echo "<td>" . ($row['bsnIdentityVerifiedType']) . "</td>";
    echo "<td>" . ($row['emailAddress']) . "</td>";
    echo "<td>" . ($row['mobilePhone']) . "</td>"; 
    echo "<td>" . ($row['locatie']) . "</td>";
    echo "<td>" . (!empty($row['createdAt']) ? date("d-m-Y H:i", strtotime($row['createdAt'])) : '-') . "</td>";
    echo "<td>" . (!empty($row['inzorg']) ? date("d-m-Y H:i", strtotime($row['inzorg'])) : '-') . "</td>";
    echo "<td>" . (!empty($row['uitzorg']) ? date("d-m-Y H:i", strtotime($row['uitzorg'])) : '-') . "</td>";
    echo "<td>" . (!empty($row['archief']) ? date("d-m-Y H:i", strtotime($row['archief'])) : '-') . "</td>";}
echo "</table>";

// Sluit de databaseverbinding
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lokale gegevens</title>
    <style>
        /* Algemene styling voor de body */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        /* Positioneer de koptekst en zoekbalk linksboven */
        .header-container {
            position: absolute;
            top: 10px; /* Afstand van de bovenkant van het scherm */
            left: 10px; /* Afstand van de linkerkant van het scherm */
            z-index: 10; /* Zorgt ervoor dat de header boven andere elementen staat */
        }

        /* Styling voor de koptekst "lokale_gegevens" */
        h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        /* Styling voor de zoekbalk */
        #search {
            width: 300px;
            padding: 10px;
            font-size: 16px;
            border: 2px solid #ccc;
            border-radius: 4px;
            margin-top: 10px;
        }

        /* Container voor de zoekresultaten, komt onder de zoekbalk */
        #results {
            margin-top: 20px;
            margin-left: 10px;
            width: 300px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 10px;
            display: none; /* Standaard niet zichtbaar, alleen zichtbaar bij zoeken */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 160px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            color: #333;
        }
    </style>
</head>
<body>
    <!-- Container voor koptekst en zoekbalk -->
    <div class="header-container">
        <h2>Lokale gegevens</h2>
        <input type="text" id="search" onkeyup="searchClients()" placeholder="Zoek een cliënt...">
        <div id="results"></div>
    </div>

    <script>
        function searchClients() {
            let query = document.getElementById("search").value;
            console.log("🔍 searchClients() functie wordt aangeroepen!"); // Debugging

            if (query.length > 1) { // Voorkomt lege zoekopdrachten
                console.log("👀 Zoeken naar:", query); // Debugging

                fetch("search.php?query=" + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        console.log("✅ Server response:", data); // Debugging

                        let resultDiv = document.getElementById("results");
                        resultDiv.innerHTML = ""; // Maak de resultaten leeg voor nieuwe zoekopdracht

                        if (data.length > 0) {
                            resultDiv.style.display = "block"; // Maak de zoekresultaten zichtbaar
                            data.forEach(client => {
                                let clientLink = document.createElement('a');
                                clientLink.href = 'client_detail.php?id=' + client.identificationNo;
                                clientLink.textContent = client.firstname + " " + client.lastname;
                                clientLink.style.display = 'block';
                                clientLink.style.padding = '5px';
                                resultDiv.appendChild(clientLink);
                            });
                        } else {
                            resultDiv.style.display = "none"; // Verberg zoekresultaten als er geen resultaten zijn
                        }
                    })
                    .catch(error => console.error("❌ Fetch error:", error));
            } else {
                document.getElementById("results").style.display = "none"; // Verberg zoekresultaten als de zoekbalk leeg is
            }
        }
    </script>
</body>
</html>





