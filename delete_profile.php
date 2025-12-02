<?php
session_start();
include 'config.php'; 

// 1. Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Controleer of de databaseverbinding beschikbaar is
if (!isset($conn) || !$conn instanceof mysqli) {
    $_SESSION['error_message'] = "Kan profiel niet verwijderen: Databasefout.";
    header("Location: profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// ==========================================================
// 2. BEVEILIGINGSCONTROLE: CONTROLEER WACHTWOORD
// ==========================================================

$confirm_password = $_GET['confirm_password'] ?? '';

if (empty($confirm_password)) {
    $_SESSION['delete_message'] = "Fout: Wachtwoord is vereist voor profielverwijdering.";
    header("Location: profile.php");
    exit();
}

// Haal de opgeslagen wachtwoord-hash op
$stmt_check = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$user_data = $result_check->fetch_assoc();
$stmt_check->close();

if (!$user_data || !password_verify($confirm_password, $user_data['password'])) {
    $_SESSION['delete_message'] = "Fout: Invoerd wachtwoord is onjuist. Verwijdering geannuleerd.";
    header("Location: profile.php");
    exit();
}
// Wachtwoord is correct, we gaan verder met verwijderen.

// ==========================================================
// 3. START VERWIJDERINGSTRANSACTIE
// ==========================================================

$conn->begin_transaction();
$success = true;

try {
    // STAP 1: Verwijder order items
    $stmt_delete_items = $conn->prepare("DELETE oi FROM order_items oi
                                        INNER JOIN orders o ON oi.order_id = o.id 
                                        WHERE o.user_id = ?");
    $stmt_delete_items->bind_param("i", $user_id);
    if (!$stmt_delete_items->execute()) {
        $success = false;
    }
    $stmt_delete_items->close();


    // STAP 2: Verwijder orders
    if ($success) {
        $stmt_delete_orders = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
        $stmt_delete_orders->bind_param("i", $user_id);
        if (!$stmt_delete_orders->execute()) {
            $success = false;
        }
        $stmt_delete_orders->close();
    }


    // STAP 3: Verwijder gebruiker
    if ($success) {
        $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_delete_user->bind_param("i", $user_id);
        if (!$stmt_delete_user->execute()) {
            $success = false;
        }
        $stmt_delete_user->close();
    }

    if ($success) {
        $conn->commit(); 
        $message = "Uw profiel en alle gerelateerde gegevens zijn succesvol verwijderd.";
    } else {
        $conn->rollback(); 
        $message = "Fout bij het verwijderen van profiel (Rollback uitgevoerd).";
    }

} catch (Exception $e) {
    $conn->rollback();
    $message = "Onverwachte fout bij verwijderen: " . $e->getMessage();
    $success = false;
}

// 4. Log de gebruiker uit en stuur naar de homepage
session_destroy();

// Optioneel: Stuur de foutmelding of het succesbericht terug via de sessie
$_SESSION['delete_message'] = $message;

header("Location: index.php"); 
exit();
?>