<?php
session_start();
// Zorg ervoor dat dit bestand de $conn databaseverbinding bevat
include 'config.php'; 

$register_error = '';
$register_success = false;

// Controleer of de gebruiker al is ingelogd
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit();
}

// Initialiseer variabelen om de ingevoerde data te behouden bij een fout
$username = $email = $fullname = $street = $house_number = $postcode = $city = $phone = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Haal de gegevens op uit het formulier
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $fullname = trim($_POST['fullname']); 
    $street = trim($_POST['street']);
    $house_number = trim($_POST['house_number']);
    $postcode = trim($_POST['postcode']);
    $city = trim($_POST['city']);
    $phone = trim($_POST['phone']);

    // Validatie: controleer of verplichte velden zijn ingevuld
    if (empty($username) || empty($email) || empty($password) || empty($fullname) || empty($street) || empty($house_number) || empty($postcode) || empty($city)) {
        $register_error = "Vul alstublieft alle verplichte velden (*) in.";
    } 
    
    // Validatie: wachtwoordlengte
    else if (strlen($password) < 6) {
        $register_error = "Het wachtwoord moet minstens 6 tekens lang zijn.";
    }
    
    // Validatie: E-mail formaat
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Voer een geldig e-mailadres in.";
    }

    // Ga door als er geen fouten zijn
    if (empty($register_error)) {
        // Controleer of e-mail al bestaat
        if (isset($conn) && $conn instanceof mysqli) {
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $register_error = "Dit e-mailadres is al geregistreerd.";
            }
            $check_stmt->close();
        } else {
            $register_error = "Fout: Databaseverbinding niet beschikbaar.";
        }
    }

    // Als alle validaties gelukt zijn: registreer gebruiker
    if (empty($register_error)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // CORRECTE INVOEGQUERY: Gebruik 'password' en 'full_name'
        $insert_query = "INSERT INTO users (username, email, password, full_name, street, house_number, postal_code, city, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if (isset($conn) && $conn instanceof mysqli) {
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sssssssss", $username, $email, $password_hash, $fullname, $street, $house_number, $postal_code, $city, $phone_number);

            if ($insert_stmt->execute()) {
                $register_success = true;
                // Wis de ingevulde formuliervelden na succesvolle registratie
                $username = $email = $fullname = $street = $house_number = $postal_code = $city = $phone_number = '';
            } else {
                $register_error = "Er is een fout opgetreden bij de registratie: " . $conn->error;
            }
            $insert_stmt->close();
        }
    }
}

// Header logica voor navigatie
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : 'Gast';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Registreren - 3DTB Shop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="main-header">
        <div class="logo">
            <a href="index.php"><img src="assets/logo.png" alt="3D TB Logo" class="header-logo"></a>
        </div>
        <div class="utility-nav">
            <a href="index.php">Home</a>
            <a href="cart.php" class="cart-link">🛒 Winkelwagen</a> 
            <a href="login.php">Inloggen</a> 
            </div>
    </header>

    <main class="content-container">
        <div class="auth-container"> 
            <h2 class="section-title" style="margin-top: 0; padding-top: 0; border: none; color: var(--color-accent);">Registreer een nieuw account</h2>
            
            <?php if ($register_error): ?>
                <p class="error-message" style="color: red; background-color: #331111; padding: 10px; border-radius: 4px; margin-bottom: 20px;"><?php echo $register_error; ?></p>
            <?php endif; ?>

            <?php if ($register_success): ?>
                <p class="success-message" style="color: #4CAF50; font-weight: bold; background-color: rgba(76, 175, 80, 0.1); padding: 15px; border-radius: var(--border-radius-base); margin-bottom: 30px;">
                    🥳 **Registratie succesvol!** U kunt nu <a href="login.php" style="font-weight: bold; color: #69ff69;">inloggen</a>.
                </p>
            <?php endif; ?>

            <form action="register.php" method="POST" class="login-form" style="<?php echo $register_success ? 'display: none;' : ''; ?>">
                
                <h3 style="color: var(--color-text-primary); margin-bottom: 10px;">Accountgegevens</h3>
                <hr style="border-color: var(--color-border-subtle); margin-bottom: 20px;">
                
                <div class="form-group">
                    <label for="username">Gebruikersnaam (*):</label>
                    <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($username); ?>">
                </div>

                <div class="form-group">
                    <label for="email">E-mailadres (*):</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Wachtwoord (*):</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <h3 style="color: var(--color-text-primary); margin-top: 30px; margin-bottom: 10px;">Persoonlijke & Adresgegevens</h3>
                <hr style="border-color: var(--color-border-subtle); margin-bottom: 20px;">

                <div class="form-group">
                    <label for="fullname">Volledige Naam (*):</label>
                    <input type="text" id="fullname" name="fullname" required value="<?php echo htmlspecialchars($fullname); ?>">
                </div>
                
                <div class="form-group">
                    <label for="street">Straat (*):</label>
                    <input type="text" id="street" name="street" required value="<?php echo htmlspecialchars($street); ?>">
                </div>

                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="house_number">Huisnummer (*):</label>
                        <input type="text" id="house_number" name="house_number" required value="<?php echo htmlspecialchars($house_number); ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="postcode">Postcode (*):</label>
                        <input type="text" id="postcode" name="postcode" required value="<?php echo htmlspecialchars($postcode); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="city">Woonplaats (*):</label>
                    <input type="text" id="city" name="city" required value="<?php echo htmlspecialchars($city); ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Telefoonnummer (Optioneel):</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                </div>

                <p style="text-align: left; margin-top: 10px; margin-bottom: 20px; font-size: 0.9em;">(*) Verplichte velden</p>
                
                <button type="submit" class="button primary">Account aanmaken</button>
            </form>

            <p class="register-link-text">Al een account? <a href="login.php">Log hier in.</a></p>
        </div>
    </main>
    
</body>
</html>
<?php 
if (isset($conn) && $conn instanceof mysqli) {
    // $conn->close(); 
} 
?>