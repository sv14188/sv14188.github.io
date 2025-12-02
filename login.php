<?php
session_start();
// Zorg ervoor dat dit bestand de $conn databaseverbinding bevat
include 'config.php'; 

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Haal de gegevens op uit het formulier
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query om gebruiker op te halen
    if (isset($conn) && $conn instanceof mysqli) {
        // BELANGRIJKE AANPASSING: 'password_hash' vervangen door 'password'
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // BELANGRIJKE AANPASSING: $user['password_hash'] vervangen door $user['password']
            if (password_verify($password, $user['password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['username'];

                // Redirect de gebruiker
                if (isset($_GET['redirect'])) {
                    header("Location: " . $_GET['redirect']);
                    exit();
                } else {
                    header("Location: index.php"); // Standaard redirect naar homepage
                    exit();
                }
            } else {
                $login_error = "Ongeldig e-mailadres of wachtwoord.";
            }
        } else {
            $login_error = "Ongeldig e-mailadres of wachtwoord.";
        }

        $stmt->close();
    } else {
        $login_error = "Fout: Databaseverbinding niet beschikbaar.";
    }
}

// Sluit de DB-verbinding indien deze open is (aan het einde van de pagina)
if (isset($conn) && $conn instanceof mysqli) {
     // $conn->close(); // Laat dit uit commentaar als u de verbinding hier wilt sluiten
}

// Header logica voor navigatie
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : 'Gast';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Inloggen - 3DTB Shop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="main-header">
        <div class="logo">
             <a href="index.php"><img src="assets/logo.png" alt="3D TB Logo" class="header-logo"></a>
        </div>
        <div class="utility-nav">
            <a href="index.php">Home</a>
            <a href="register.php">Registreren</a>
            <?php if ($is_logged_in): ?>
                <span class="welcome-user">Welkom, <?php echo $user_name; ?></span>
                <a href="logout.php">Uitloggen</a>
            <?php else: ?>
                 <a href="cart.php" class="cart-link">🛒 Winkelwagen</a> 
            <?php endif; ?>
        </div>
    </header>

    <main class="content-container">
        <div class="auth-container"> <h2 style="color: var(--color-accent); margin-bottom: 25px;">Inloggen</h2>

            <?php if ($login_error): ?>
                <p class="error-message" style="color: red; background-color: #331111; padding: 10px; border-radius: 4px;"><?php echo $login_error; ?></p>
            <?php endif; ?>

            <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST" class="login-form">
                
                <div class="form-group">
                    <label for="email">E-mailadres:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Wachtwoord:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="button primary">Inloggen</button>
            </form>

            <p class="register-link-text">Nog geen account? <a href="register.php">Registreer hier</a></p>
        </div>
    </main>
    
</body>
</html>