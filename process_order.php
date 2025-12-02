<?php
    session_start();
    include 'config.php'; 

    // 1. Controleer authenticatie en methode
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SERVER["REQUEST_METHOD"] !== "POST") {
        header("Location: login.php");
        exit();
    }

    // 2. Gegevens verzamelen
    $user_id = $_SESSION['user_id'];
    $cart = $_SESSION['cart'] ?? [];

    if (empty($cart)) {
        // Kan niet afrekenen met lege winkelwagen
        $_SESSION['message'] = "Uw winkelwagen is leeg. Voeg producten toe om af te rekenen.";
        header("Location: cart.php"); 
        exit();
    }
    
    // Gegevens van het checkout formulier (checkout.php)
    $customer_name = $_POST['name'] ?? '';
    $customer_email = $_POST['email'] ?? '';
    $shipping_address = $_POST['address'] ?? '';
    $shipping_zipcode = $_POST['zipcode'] ?? '';
    $shipping_city = $_POST['city'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'Onbekend';

    // 3. Verzendkosten en Totaalbedrag herberekeken (voor veiligheid)
    $shipping_cost = 3.95; 
    $total_products_price = 0;

    foreach ($cart as $item) {
        // Bereken totaalprijs producten
        $total_products_price += ($item['price'] * $item['quantity']);
        
        // Controleer op trekhaakdop (hoofdletterongevoelig)
        if (isset($item['name']) && stripos($item['name'], 'trekhaakdop') !== false) {
            $shipping_cost = 5.95;
        }
    }
    
    $final_total = $total_products_price + $shipping_cost;
    
    // Start database transactie
    $conn->begin_transaction();
    $success = false;
    $order_id = null;
    $order_status = 'In afwachting van betaling'; // Standaard status

    try {
        // 4. BESTELLING OPSLAAN IN 'orders' TABEL
        // De kolomvolgorde moet overeenkomen met uw database schema
        $stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, customer_email, shipping_address, shipping_zipcode, shipping_city, payment_method, shipping_cost, total_amount, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // s = string, i = integer, d = decimal/double
        // process_order.php - rond regel 58

        // De type-definitie string is gecorrigeerd naar "issssssdds"
        // (1x i, 7x s, 2x d, 1x s) -> 10 types
        $stmt->bind_param("issssssdds", 
            $user_id, 
            $customer_name, 
            $customer_email, 
            $shipping_address, 
            $shipping_zipcode, 
            $shipping_city, 
            $payment_method, 
            $shipping_cost, // d (decimal)
            $final_total,   // d (decimal)
            $order_status
        );
        $stmt->execute();
        // ... de rest van de code
        $stmt->execute();
        $order_id = $conn->insert_id; // Haal de ID van de zojuist ingevoegde bestelling op
        $stmt->close();
        
        // 5. ITEMS OPSLAAN IN 'order_items' TABEL
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_name, product_price, quantity, custom_color, custom_text) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($cart as $item) {
            $product_name = $item['name'];
            $price = $item['price'];
            $quantity = $item['quantity'];
            $color = $item['color'] ?? NULL;
            $text = $item['text'] ?? NULL;
            
            $stmt_item->bind_param("isddss", $order_id, $product_name, $price, $quantity, $color, $text);
            $stmt_item->execute();
        }
        $stmt_item->close();

        // Alles gelukt: Commit de transactie en maak de winkelwagen leeg
        $conn->commit();
        $success = true;
        unset($_SESSION['cart']); 
        $_SESSION['order_id'] = $order_id; // Sla de order_id op voor de bevestigingspagina

    } catch (Exception $e) {
        // Iets ging fout: Rollback en bewaar ingevoerde data (voor form stickiness)
        $conn->rollback();
        
        // Sla de ingevulde data op in de sessie en een foutmelding
        $_SESSION['form_data'] = $_POST; 
        $_SESSION['error_message'] = "Er is een technische fout opgetreden bij het plaatsen van uw bestelling. Probeer het opnieuw. Foutdetails: " . $e->getMessage();
        
        header("Location: checkout.php");
        exit();
    }

    // 6. Doorsturen naar de bevestigingspagina
    if ($success) {
        header("Location: order_confirmation.php");
        exit();
    } else {
        // Dit is een fallback, zou zelden bereikt moeten worden
        header("Location: checkout.php");
        exit();
    }
?>