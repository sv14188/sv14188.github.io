<?php
session_start();

// Tijdelijke foutonderdrukking voor de zekerheid. Schakel dit uit zodra alles werkt.
error_reporting(0);
ini_set('display_errors', 0);

// Zorg ervoor dat de respons ALTIJD JSON is, ongeacht fouten
header('Content-Type: application/json');

// Controleer of de request een POST is en de benodigde gegevens aanwezig zijn
// We verwachten 'item_key' en 'new_quantity'
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['item_key']) || !isset($_POST['new_quantity'])) {
    // Fout bij ontbrekende gegevens
    echo json_encode(['status' => 'error', 'message' => 'Ongeldig verzoek of ontbrekende gegevens.']);
    exit;
}

// ==========================================================
// DATA VALIDATIE
// ==========================================================

// Haal de benodigde gegevens op
$item_key = filter_input(INPUT_POST, 'item_key', FILTER_SANITIZE_STRING);
$new_quantity = filter_input(INPUT_POST, 'new_quantity', FILTER_VALIDATE_INT);

// Controleren of de winkelwagen bestaat en de key geldig is
if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$item_key])) {
    echo json_encode(['status' => 'error', 'message' => 'Item niet gevonden in winkelwagen.']);
    exit;
}

// Controleren op een geldige hoeveelheid
if ($new_quantity === false || $new_quantity < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Ongeldige hoeveelheid opgegeven.']);
    exit;
}

// ==========================================================
// WINKELWAGEN LOGICA UITVOEREN
// ==========================================================

if ($new_quantity === 0) {
    // 1. VERWIJDEREN: Als de hoeveelheid 0 is, verwijder het item
    unset($_SESSION['cart'][$item_key]);
    $message = 'Item succesvol verwijderd uit de winkelwagen.';
    
} else {
    // 2. AANTAL AANPASSEN: Update de hoeveelheid
    $_SESSION['cart'][$item_key]['quantity'] = $new_quantity;
    $message = 'Hoeveelheid succesvol aangepast.';
}

// ==========================================================
// JSON SUCCES RESPONS
// ==========================================================

// Bereken de nieuwe totale telling van items (niet unieke keys)
$total_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_items += $item['quantity'];
}

echo json_encode([
    'status' => 'success',
    'message' => $message,
    // Stuur de huidige status terug
    'total_cart_items' => $total_items,
    'unique_items_count' => count($_SESSION['cart']),
    'item_key' => $item_key, 
    'new_quantity' => $new_quantity
]);
exit;
?>