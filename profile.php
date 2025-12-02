<?php
session_start();
// ZORG ERVOOR DAT DIT BESTAND BESTAAT EN DE VARIABELE $conn BEVAT (mysqli object)
include 'config.php'; 

// 1. Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = ''; 
$user = []; // Initialiseer $user array

// Zorg ervoor dat de $conn variabele beschikbaar is en een mysqli object is
if (!isset($conn) || !$conn instanceof mysqli) {
    die("FATALE FOUT: Databaseverbinding (\$conn) niet correct ingesteld in config.php.");
}


// 2. Gegevens van de gebruiker ophalen
// LET OP: Gebruik hier de kolomnamen zoals ze in uw database staan 
$stmt = $conn->prepare("SELECT id, username, email, password, full_name, street, house_number, postal_code, city, phone_number FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}


// 3. Verwerk het verstuurde formulier (Update Logica)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {
    
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_fullname = trim($_POST['fullname']); 
    $new_street = trim($_POST['street']);
    $new_house_number = trim($_POST['house_number']);
    $new_postcode = trim($_POST['postcode']); 
    $new_city = trim($_POST['city']);
    $new_phone = trim($_POST['phone']);
    $new_password = $_POST['new_password'];
    $current_password = $_POST['current_password'];

    // Eerst het HUIS-wachtwoord controleren ter autorisatie
    if (password_verify($current_password, $user['password'])) {
        
        $update_fields = [];
        $update_params = [];
        $types = "";

        // Veldnamen moeten overeenkomen met de DB kolommen (zipcode in DB, postcode in form)
        $update_fields[] = "username = ?"; $update_params[] = $new_username; $types .= "s";
        $update_fields[] = "email = ?"; $update_params[] = $new_email; $types .= "s";
        $update_fields[] = "full_name = ?"; $update_params[] = $new_fullname; $types .= "s";
        $update_fields[] = "street = ?"; $update_params[] = $new_street; $types .= "s";
        $update_fields[] = "house_number = ?"; $update_params[] = $new_house_number; $types .= "s";
        $update_fields[] = "zipcode = ?"; $update_params[] = $new_postcode; $types .= "s"; 
        $update_fields[] = "city = ?"; $update_params[] = $new_city; $types .= "s";
        $update_fields[] = "phone = ?"; $update_params[] = $new_phone; $types .= "s";

        // Wachtwoord updaten alleen indien nieuw wachtwoord is ingevoerd
        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                 $message = "Fout: Het nieuwe wachtwoord moet minstens 6 tekens lang zijn.";
            } else {
                 $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                 $update_fields[] = "password = ?";
                 $update_params[] = $hashed_password;
                 $types .= "s";
            }
        }
        
        // Alleen doorgaan met de update als er geen fouten zijn
        if (strpos($message, 'Fout') === false) {
             // Bouw en executeer de SQL-query
            $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
            $update_params[] = $user_id;
            $types .= "i"; // 'i' voor de user_id integer

            $stmt_update = $conn->prepare($sql);
            $stmt_update->bind_param($types, ...$update_params);

            if ($stmt_update->execute()) {
                // Succesvolle update: Herhaal de select query om de formuliergegevens bij te werken
                $stmt_update->close();
                
                // Opnieuw ophalen van gegevens (zonder wachtwoord)
                $stmt_refresh = $conn->prepare("SELECT id, username, email, password, full_name, street, house_number, zipcode, city, phone FROM users WHERE id = ?");
                $stmt_refresh->bind_param("i", $user_id);
                $stmt_refresh->execute();
                $result_refresh = $stmt_refresh->get_result();
                $user = $result_refresh->fetch_assoc();
                $stmt_refresh->close();
                
                $message = "Uw profielgegevens zijn succesvol bijgewerkt!";
            } else {
                $message = "Fout bij het opslaan van gegevens: " . $conn->error;
            }
        }

    } else {
        $message = "Fout: Huidig wachtwoord is onjuist. Wijzigingen niet opgeslagen.";
    }
}

// Haal delete message op, die uit delete_profile.php komt
if (isset($_SESSION['delete_message'])) {
    $message = $_SESSION['delete_message'];
    unset($_SESSION['delete_message']);
}

// ==========================================================
// WINKELWAGEN TELLER LOGICA VOOR NAVIGATIE
// ==========================================================
$cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity']; 
    }
}
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : 'Gast';

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Mijn Profiel - 3DTB Shop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="main-header">
        <div class="logo">
            <a href="index.php">
                <img src="assets/logo.png" alt="3D TB Logo" class="header-logo">
            </a>
        </div>
        <div class="utility-nav">
            <a href="index.php">Home</a>
            <?php if ($is_logged_in): ?>
                <span class="welcome-user">Welkom, **<?php echo $user_name; ?>**</span>
                <a href="profile.php">⚙️ Instellingen</a> 
                <a href="logout.php">🔒 Uitloggen</a>
            <?php else: ?>
                <a href="login.php">🔒 Inloggen</a>
                <a href="register.php">🔒 Registreren</a>
            <?php endif; ?>
            <a href="cart.php" class="cart-link">🛒 Winkelwagen (<?php echo $cart_count; ?>)</a> 
        </div>
    </header>

    <main class="content-container">
        <div class="auth-container">
            <h2 class="section-title">⚙️ Mijn Profiel Instellingen</h2>
        
            <?php if ($message): ?>
                <p class="message" style="color: <?php echo (strpos($message, 'Fout') !== false) ? 'red; background-color: #331111' : '#4CAF50; background-color: rgba(76, 175, 80, 0.1)'; ?>; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                    <?php echo $message; ?>
                </p>
            <?php endif; ?>

            <form method="POST" action="profile.php" class="login-form">
                <input type="hidden" name="update_profile" value="1">

                <h3 style="margin-top: 10px;">Account & Persoonlijke Gegevens</h3>
                <hr>

                <div class="form-group"><label for="username">Gebruikersnaam:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required></div>

                <div class="form-group"><label for="email">E-mailadres:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
                
                <div class="form-group"><label for="fullname">Volledige Naam:</label>
                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"></div>

                <h3>Adresgegevens</h3>
                <hr>
                
                <div class="form-group"><label for="street">Straat:</label>
                <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($user['street'] ?? ''); ?>"></div>

                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;"><label for="house_number">Huisnummer:</label>
                    <input type="text" id="house_number" name="house_number" value="<?php echo htmlspecialchars($user['house_number'] ?? ''); ?>"></div>
                    
                    <div class="form-group" style="flex: 1;"><label for="postcode">Postcode:</label>
                    <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($user['zipcode'] ?? ''); ?>"></div>
                </div>

                <div class="form-group"><label for="city">Woonplaats:</label>
                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>"></div>
                
                <div class="form-group"><label for="phone">Telefoonnummer:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></div>


                <h3>Wachtwoord wijzigen (optioneel)</h3>
                <hr>
                <div class="form-group"><label for="new_password">Nieuw Wachtwoord (laat leeg om niet te wijzigen, min 6 tekens):</label>
                <input type="password" id="new_password" name="new_password"></div>

                <h3>Bevestig Update</h3>
                <p style="font-size: 0.9em; margin-bottom: 10px;">Voer uw **huidige wachtwoord** in ter bevestiging van alle wijzigingen.</p>
                <div class="form-group"><label for="current_password">Huidig Wachtwoord (verplicht):</label>
                <input type="password" id="current_password" name="current_password" required></div>

                <button type="submit" class="button primary">Profiel Opslaan</button>
            </form>

            <hr style="margin-top: 30px;">

            <h2>Account Verwijderen</h2>
            <p>Door uw profiel te verwijderen, verliest u toegang tot al uw bestellingen. Dit kan niet ongedaan gemaakt worden.</p>
            
            <button id="delete-button" type="button" onclick="confirmDelete()" class="button secondary" style="background-color: #d9534f; border-color: #d9534f; color: white;">Profiel Definitief Verwijderen</button>

        </div>
    </main>

    <script>
        function confirmDelete() {
            // Vraag om definitieve bevestiging via een standaard pop-up
            if (!confirm("WAARSCHUWING: Weet u zeker dat u uw profiel definitief wilt verwijderen? Dit is onomkeerbaar.")) {
                return; // Stop als de gebruiker annuleert
            }

            // Vraag om het wachtwoord ter verificatie via een tweede pop-up
            const password = prompt("Voer ter beveiliging uw huidige wachtwoord in om de verwijdering te bevestigen:");

            if (password !== null && password !== "") {
                // Stuur het wachtwoord mee als query parameter
                window.location.href = 'delete_profile.php?confirm_password=' + encodeURIComponent(password);
            } else {
                alert("Verwijdering geannuleerd, wachtwoord is vereist.");
            }
        }
    </script>
</body>
</html>