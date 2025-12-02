<?php
session_start();
include 'config.php';

// Controleer of het een POST-verzoek is en of de benodigde gegevens aanwezig zijn
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['product_id'])) {
    
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $selected_color = $_POST['selected_color'] ?? '';
    $custom_text = $_POST['custom_text'] ?? '';
    
    // Zorg ervoor dat de winkelwagen-array bestaat
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Maak een unieke sleutel voor dit item, inclusief personalisatie
    $item_key = $product_id . '|' . $selected_color . '|' . $custom_text;
    
    if (isset($_SESSION['cart'][$item_key])) {
        // Product (met deze personalisatie) bestaat al, verhoog de hoeveelheid
        $_SESSION['cart'][$item_key]['quantity'] += $quantity;
    } else {
        // Zoek de productnaam en prijs op in de database
        $stmt = $conn->prepare("SELECT name, price FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product_info = $result->fetch_assoc();
        
        if ($product_info) {
            // Voeg nieuw item toe aan de winkelwagen
            $_SESSION['cart'][$item_key] = [
                'id' => $product_id,
                'name' => $product_info['name'],
                'price' => $product_info['price'],
                'quantity' => $quantity,
                'color' => $selected_color,
                'text' => $custom_text
            ];
        }
        $stmt->close();
    }
    
    $conn->close();

    // Stuur een JSON-succesbericht terug
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Product succesvol toegevoegd!']);
    exit;

} else {
    // Stuur een JSON-foutbericht terug
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Ongeldig verzoek of ontbrekende gegevens.']);
    exit;
}
?>