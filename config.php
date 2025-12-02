<?php
// ==========================================================
// 1. DATABASE INSTELLINGEN VOOR LOKALE HOSTING (XAMPP/Localhost)
// ==========================================================

// De Hostnaam van uw MySQL server (standaard is dit localhost of 127.0.0.1)
$dbHost = "localhost"; 

// De Database Gebruikersnaam (standaard in XAMPP is 'root')
$dbUser = "root";        

// Het Wachtwoord van de Database Gebruiker (standaard in XAMPP is leeg)
$dbPass = "";     

// De Naam van de Database die u lokaal in XAMPP gebruikt (bijv. 'webshop-project')
// VERGEET NIET OM 'uw_lokale_db_naam' TE VERVANGEN DOOR DE JUISTE NAAM!
$dbName = "webshop_db"; 

// ==========================================================
// 2. VERBINDING LEGGEN EN FOUTEN AFHANDELEN (mysqli)
// ==========================================================

// Nieuwe verbinding maken met de MySQLi-extensie
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// Controleer de verbinding en geef een gedetailleerde foutmelding bij mislukking
if ($conn->connect_error) {
    // Toon de foutdetails lokaal
    die("Fout bij databaseverbinding: " . $conn->connect_error);
}

// Zorg ervoor dat de karakterset correct is ingesteld voor speciale tekens
$conn->set_charset("utf8mb4");

// ----------------------------------------------------------
// OPTIONEEL: Functie om de databaseverbinding (veilig) te sluiten
// ----------------------------------------------------------
/*
function close_db_connection($connection) {
    if ($connection && $connection instanceof mysqli) {
        $connection->close();
    }
}
*/
?>