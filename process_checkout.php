<?php
    session_start();
    include 'config.php'; 

    if (!isset($_SESSION['logged_in']) || !isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        header("Location: index.php"); 
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $total_amount = $_POST['total_amount']; 
    $order_status = 'Nieuw'; 
    
    // ⚠️ Transacties: Dit is belangrijk om te garanderen dat alles goed gaat. 
    // Als één stap mislukt, wordt alles teruggedraaid.
    $conn->begin_transaction(); 

    try {
        // 1. Hoofdbestelling opslaan in de 'orders' tabel
        $sql_order = "INSERT INTO orders (user_id, order_date, total_amount, status) 
                      VALUES (?, NOW(), ?, ?)";
        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->bind_param("ids", $user_id, $total_amount, $order_status);
        $stmt_order->execute();
        
        // Haal de ID van de zojuist geplaatste bestelling op
        $order_id = $conn->insert_id; 
        $stmt_order->close();

        // 2. Bestelitems opslaan en voorraad aanpassen
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            
            // Haal de actuele prijs en voorraad op voor veiligheid
            $sql_product = "SELECT price, stock FROM products WHERE id = ?";
            $stmt_product = $conn->prepare($sql_product);
            $stmt_product->bind_param("i", $product_id);
            $stmt_product->execute();
            $result_product = $stmt_product->get_result();
            $product_data = $result_product->fetch_assoc();
            $stmt_product->close();

            $current_price = $product_data['price'];
            $current_stock = $product_data['stock'];

            // Controleer de voorraad
            if ($current_stock < $quantity) {
                 throw new Exception("Onvoldoende voorraad voor product ID " . $product_id);
            }

            // A. Opslaan in de 'order_items' tabel
            $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                         VALUES (?, ?, ?, ?)";
            $stmt_item = $conn->prepare($sql_item);
            $stmt_item->bind_param("iiid", $order_id, $product_id, $quantity, $current_price);
            $stmt_item->execute();
            $stmt_item->close();

            // B. Voorraad aanpassen in de 'products' tabel
            $new_stock = $current_stock - $quantity;
            $sql_stock = "UPDATE products SET stock = ? WHERE id = ?";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("ii", $new_stock, $product_id);
            $stmt_stock->execute();
            $stmt_stock->close();
        }

        // 3. Alles is gelukt, bevestig de transactie
        $conn->commit();
        
        // 4. Winkelwagen legen en gebruiker doorsturen
        unset($_SESSION['cart']); 
        
        echo "<h2>Bestelling Succesvol!</h2>";
        echo "<p>Je bestelnummer is **$order_id**.</p>";
        echo "<p>Bedankt voor je bestelling, **{$_SESSION['user_name']}**!</p>";
        echo "<p><a href='index.php'>Terug naar de homepage</a></p>";

    } catch (Exception $e) {
        // Er is iets misgegaan, draai alle veranderingen terug
        $conn->rollback(); 
        echo "Fout bij het verwerken van de bestelling: " . $e->getMessage();
        echo "<p><a href='cart.php'>Terug naar winkelwagen</a></p>";
    }
?>