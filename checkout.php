<?php
session_start();
include 'config.php'; 

// ==========================================================
// ⚠️ DEBUGGING IS NOG STEEDS AAN
// ==========================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
// ==========================================================

// Controleer of de winkelwagen leeg is
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// ==========================================================
// 2. BEZORGKOSTEN EN TOTAAL BEREKENEN
// ==========================================================
$cart = $_SESSION['cart'];
$total_product_price = 0;
$is_trekhaakdop_in_cart = false;

foreach ($cart as $item) {
    if (isset($item['name']) && isset($item['price']) && isset($item['quantity'])) {
        if (strpos(strtolower($item['name']), 'trekhaakdop') !== false) {
            $is_trekhaakdop_in_cart = true;
        }
        $total_product_price += (float)$item['price'] * (int)$item['quantity'];
    }
}

$TARIFFEN = [
    'BRIEVENBUSPAKKET' => ['methode' => 'PostNL Brievenbuspakket', 'kosten' => 4.50], 
    'STANDAARDPAKKET'  => ['methode' => 'PostNL Standaardpakket', 'kosten' => 6.95]
];

if ($total_product_price > 0) {
    $shipping_info = $is_trekhaakdop_in_cart ? $TARIFFEN['STANDAARDPAKKET'] : $TARIFFEN['BRIEVENBUSPAKKET'];
} else {
    $shipping_info = ['methode' => 'Geen levering nodig', 'kosten' => 0.00];
}

$shipping_cost = $shipping_info['kosten'];
$shipping_method = $shipping_info['methode'];
$grand_total = $total_product_price + $shipping_cost;


// ==========================================================
// 3. GEBRUIKERSDATA OPHALEN
// ==========================================================
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$error_message = '';
$user_data = [];

if ($is_logged_in && isset($conn)) {
    $stmt = $conn->prepare("SELECT full_name, email, street, house_number, postal_code, city, phone_number FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user_data) {
        $is_logged_in = false; 
        $user_data = [];
    }
}

// ZORG ERVOOR DAT $email ALTIJD EEN WAARDE HEEFT (NIEUWE REGEL)
$email = $user_data['email'] ?? ''; 


// ==========================================================
// 4. BESTELLING VERWERKEN (POST)
// ==========================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['place_order'])) {
    
    // Gegevens uit het formulier
    $email = trim($_POST['email']); // Overrides de geinitialiseerde $email met de formulierwaarde
    $fullname = trim($_POST['fullname']);
    $street = trim($_POST['street']);
    $house_number = trim($_POST['house_number']);
    $postcode = trim($_POST['postcode']); 
    $city = trim($_POST['city']);
    $phone = trim($_POST['phone']);
    $payment_method = trim($_POST['payment_method']);
    
    // Validatie
    if (empty($email) || empty($fullname) || empty($street) || empty($house_number) || empty($postcode) || empty($city)) {
        $error_message = "Vul alstublieft alle verplichte velden in.";
    } elseif (empty($payment_method)) {
        $error_message = "Selecteer alstublieft een betaalmethode.";
    } elseif (!isset($conn) || $conn->connect_error) {
        $error_message = "Databaseverbinding mislukt. Controleer config.php.";
    } else {
        
        $conn->begin_transaction();
        $order_success = false;
        
        try {
            
            $total_amount_for_db = $grand_total; 

            // STAP A: Plaats de Bestelling in de 'orders' tabel
            
            if ($is_logged_in) {
                // LID: Query MET user_id
                $sql_order = "INSERT INTO orders (user_id, total_amount, status, payment_method, full_name, email, street, house_number, zipcode, city, phone, order_date) VALUES (?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt_order = $conn->prepare($sql_order);
                
                // CORRECT: 'idssssssss' (10 parameters)
                $stmt_order->bind_param("idssssssss", $user_id, $total_amount_for_db, $payment_method, $fullname, $email, $street, $house_number, $postcode, $city, $phone);
                
            } else {
                // GAST: Query ZONDER user_id (gebruik NULL in de DB)
                $sql_order = "INSERT INTO orders (total_amount, status, payment_method, full_name, email, street, house_number, zipcode, city, phone, order_date) VALUES (?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt_order = $conn->prepare($sql_order);
                
                // CORRECTIE VOOR GASTEN: 'dssssssss' (9 parameters)
                $stmt_order->bind_param("dssssssss", $total_amount_for_db, $payment_method, $fullname, $email, $street, $house_number, $postcode, $city, $phone);
            }
            
            if (!$stmt_order) {
                 throw new Exception("SQL Voorbereidingsfout: " . $conn->error);
            }
            
            $stmt_order->execute();
            
            if ($stmt_order->error) {
                 throw new Exception("SQL Uitvoeringsfout in orders: " . $stmt_order->error);
            }
            
            $order_id = $conn->insert_id;
            $stmt_order->close();
            
            
            // STAP B: Plaats de items in de 'order_items' tabel
            // Gebruikt de door u bevestigde kolomnamen: product_name, product_price, custom_color, custom_text
            $sql_item = "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, custom_color, custom_text) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_item = $conn->prepare($sql_item);
            
            if (!$stmt_item) {
                throw new Exception("SQL item Voorbereidingsfout: " . $conn->error);
            }

            foreach ($_SESSION['cart'] as $item) {
                $product_id = $item['id'] ?? 0;
                $product_name = $item['name'] ?? 'Onbekend'; 
                $price_item = $item['price'] ?? 0.00; 
                $quantity = $item['quantity'] ?? 1;
                $custom_color = $item['color'] ?? null; 
                $custom_text = $item['text'] ?? null; 
                
                // 'iisdiss' (order_id, product_id, product_name, product_price, quantity, custom_color, custom_text)
                $stmt_item->bind_param("iisdiss", $order_id, $product_id, $product_name, $price_item, $quantity, $custom_color, $custom_text);
                
                $stmt_item->execute();
                
                 if ($stmt_item->error) {
                    throw new Exception("SQL item Uitvoeringsfout in order_items: " . $stmt_item->error);
                }
            }
            $stmt_item->close();
            
            $order_success = true;
            
        } catch (Exception $e) {
            // Vang alle fouten op en toon ze
            $error_message = "Bestelling is mislukt. Detail: " . $e->getMessage();
            
            if (isset($conn) && $conn->error) {
                 $error_message .= " (Laatste DB Fout: " . $conn->error . ")";
            }
            
            error_log("Bestelfout: " . $error_message);
            
            if (isset($conn)) {
                 $conn->rollback();
            }
           
        }

        if ($order_success) {
            $conn->commit();
            unset($_SESSION['cart']);
            // Zorgt ervoor dat er naar de correcte thank_you.php pagina wordt gegaan
            header("Location: thank_you.php?order_id=" . $order_id);
            exit();
        } else {
             $error_message = $error_message ?: "Bestelling is mislukt. Probeer het later opnieuw.";
             
             if (isset($conn)) {
                 $conn->rollback();
             }
        }
    }
}

// Sluit de DB-verbinding direct
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Afrekenen</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* CSS voor het centreren en marges */
        .checkout-container {
            max-width: 800px; 
            margin: 40px auto; 
            padding: 20px 40px; 
            background-color: var(--color-primary-light); 
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        .content-container {
            padding: 0 20px; 
        }
        .form-group {
            margin-bottom: 15px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #ffc107;
            font-weight: bold;
        }
        .back-link:hover {
            color: #ffd700;
        }
        /* Veldstijlen */
        .checkout-container input[type="text"],
        .checkout-container input[type="email"],
        .checkout-container input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #555;
            border-radius: 4px;
            box-sizing: border-box; 
            background-color: var(--color-primary);
            color: white;
        }
        /* Extra opmaak voor de rode foutmelding */
        .error-message-box {
            color: #721c24; 
            background-color: #f8d7da; 
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        /* Stijl voor radio buttons */
        .payment-options label {
            display: block;
            background-color: #444;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .payment-options label:hover {
            background-color: #555;
        }
        .payment-options input[type="radio"] {
            margin-right: 10px;
        }
    </style>
</head>
<body>

    <main class="content-container">
        <div class="checkout-container">
            
            <a href="cart.php" class="back-link">← Terug naar Winkelwagen</a>
            
            <h2>Afrekenen</h2>

            <?php if (!$is_logged_in): ?>
                <div class="guest-info" style="padding: 15px; background: #3a3a3a; border-left: 5px solid gold; margin-bottom: 20px;">
                    U bestelt als **gast**. U kunt <a href="login.php" style="color: #ffc107;">hier inloggen</a> als u al een account heeft.
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="error-message-box">
                    **Fout bij Bestelling:** <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="checkout.php">
                <h3>Verzend- en Factuurgegevens</h3>
                <hr>

                <div class="form-group"><label for="fullname">Volledige Naam (*):</label>
                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required></div>
                
                <div class="form-group"><label for="email">E-mailadres (*):</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required></div>
                
                <div class="form-group"><label for="phone">Telefoonnummer:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>"></div>

                <h4>Adres</h4>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 2;"><label for="street">Straat (*):</label>
                    <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($user_data['street'] ?? ''); ?>" required></div>

                    <div class="form-group" style="flex: 1;"><label for="house_number">Huisnummer (*):</label>
                    <input type="text" id="house_number" name="house_number" value="<?php echo htmlspecialchars($user_data['house_number'] ?? ''); ?>" required></div>
                </div>

                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;"><label for="postcode">Postcode (*):</label>
                    <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($user_data['zipcode'] ?? ''); ?>" required></div>
                    
                    <div class="form-group" style="flex: 2;"><label for="city">Woonplaats (*):</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user_data['city'] ?? ''); ?>" required></div>
                </div>

                <h3 style="margin-top: 30px;">Betaalmethode</h3>
                <hr>
                
                <div class="payment-options form-group">
                    <label>
                        <input type="radio" name="payment_method" value="Bankoverschrijving" required> 
                        Bankoverschrijving
                        <br><small style="margin-left: 25px;">U ontvangt onze bankgegevens in de bevestigingsmail die u krijgt van 3dtb@xs4all.nl.</small>
                    </label>
                    
                    <label>
                        <input type="radio" name="payment_method" value="Betaalverzoek Email" required>
                        Betaalverzoek via E-mail
                        <br><small style="margin-left: 25px;">U ontvangt een betaalverzoek van 3dtb@xs4all.nl op uw e-mail <?php echo htmlspecialchars($email); ?></small>
                    </label>
                </div>
                <h3 style="margin-top: 30px;">Totaalbedrag</h3>
                <p>Subtotaal producten: **€<?php echo number_format($total_product_price, 2, ',', '.'); ?>**</p>
                <p>Bezorgmethode (<?php echo htmlspecialchars($shipping_method); ?>): **€<?php echo number_format($shipping_cost, 2, ',', '.'); ?>**</p>
                
                <hr>
                <p style="font-weight: bold; font-size: 1.2em;">Eindtotaalprijs (incl. Bezorging): **€<?php echo number_format($grand_total, 2, ',', '.'); ?>**</p>
                
                <p style="margin-top: 20px;">* Door op Afrekenen te klikken, gaat u akkoord met de algemene voorwaarden.</p>

                <button type="submit" name="place_order" class="button primary" style="background-color: #ffc107; color: #000;">Bestelling Plaatsen en Afrekenen</button>
            </form>
        </div>
    </main>
</body>
</html>