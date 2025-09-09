<?php
// Zet foutmeldingen aan
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Controleer of gebruiker is ingelogd via SSO
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}


// Databaseverbinding
$mysqli = new mysqli("localhost:3306", "Aryan", "UWL90X#!0SK", "Intern_gegevens");

if ($mysqli->connect_error) {
    die("Verbindingsfout: " . $mysqli->connect_error);
}

// SQL-query voor clientgegevens
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
WHERE c.deathDate IS NULL
GROUP BY c.identificationNo
ORDER BY c.createdAt DESC
";

$result = $mysqli->query($sql);

if (!$result) {
    die("Fout in query: " . $mysqli->error);
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Intern Gegevens</title>
    <link rel="stylesheet" href="mysqli_test.css">
</head>
<body>
<div class="header-container">
    <a href="logout.php" class="btn" style="margin-top: 10px;">Uitloggen</a>
    <h2>Intern Gegevens</h2>
    <input type="text" id="search" onkeyup="searchClients()" placeholder="Zoek een cliënt...">
    <div id="results"></div>
</div>

<table>
    <tr>
        <th>Cliëntnummer</th><th>Voornaam</th><th>Achternaam</th><th>Geboortedatum</th>
        <th>Geboortenaam</th><th>Partnernaam</th><th>Initialen</th><th>Tussenv. geboortenaam</th>
        <th>Tussenv. partnernaam</th><th>Geslacht</th><th>Overlijdensdatum</th><th>BSN</th>
        <th>BSN-bron geverifieerd</th><th>BSN-ID geverifieerd</th><th>BSN-ID-nummer</th>
        <th>BSN-ID-type</th><th>BSN-bron</th><th>BSN-verif. type</th><th>E-mail</th>
        <th>Telefoon</th><th>Locatie</th><th>Aangemaakt op</th><th>In zorg</th><th>Uit zorg</th><th>Archief</th>

    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['identificationNo'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['firstName'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['lastName'] ?? '') ?></td>
            <td><?= $row['dateOfBirth'] ? date("d-m-Y", strtotime($row['dateOfBirth'])) : '-' ?></td>
            <td><?= htmlspecialchars($row['birthName'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['partnerName'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['initials'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['birthNamePrefix'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['partnerNamePrefix'] ?? '') ?></td>
            <td><?= $row['gender'] == '1' ? 'M' : ($row['gender'] == '2' ? 'V' : '-') ?></td>
            <td><?= $row['deathDate'] ? date("d-m-Y", strtotime($row['deathDate'])) : '-' ?></td>
            <td><?= htmlspecialchars($row['bsn'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['bsnSourceVerified'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['bsnIdVerified'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['bsnIdNumber'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['bsnIdType'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['bsnSource'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['bsnIdentityVerifiedType'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['emailAddress'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['mobilePhone'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['locatie'] ?? '') ?></td>
            <td><?= $row['createdAt'] ? date("d-m-Y H:i", strtotime($row['createdAt'])) : '-' ?></td>
            <td><?= $row['inzorg'] ? date("d-m-Y H:i", strtotime($row['inzorg'])) : '-' ?></td>
            <td><?= $row['uitzorg'] ? date("d-m-Y H:i", strtotime($row['uitzorg'])) : '-' ?></td>
            <td><?= $row['archief'] ? date("d-m-Y H:i", strtotime($row['archief'])) : '-' ?></td>    </tr>
    <?php endwhile; ?>
</table>

<script>
    function searchClients() {
        const query = document.getElementById("search").value;

        if (query.length > 1) {
            fetch("search.php?query=" + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    const resultDiv = document.getElementById("results");
                    resultDiv.innerHTML = "";

                    if (data.length > 0) {
                        resultDiv.style.display = "block";
                        data.forEach(client => {
                            const link = document.createElement("a");
                            link.href = "client_detail.php?id=" + client.identificationNo;
                            link.textContent = client.firstName + " " + client.lastName;
                            link.style.display = "block";
                            link.style.padding = "5px";
                            link.style.cursor = "pointer";
                            resultDiv.appendChild(link);
                        });
                    } else {
                        resultDiv.style.display = "none";
                    }
                })
                .catch(error => console.error("Fout bij ophalen zoekresultaten:", error));
        } else {
            document.getElementById("results").style.display = "none";
        }
    }
</script>
</body>
</html>
<?php $mysqli->close(); ?>










