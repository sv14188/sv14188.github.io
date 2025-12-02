<?php
session_start();
include 'config.php'; 

// Tijdelijke foutonderdrukking (OPTIONEEL)
error_reporting(0);
ini_set('display_errors', 0);

// Header logica voor navigatie
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : 'Gast';

// Haal de winkelwagen op
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$total_product_price = 0; // Dit is de prijs van de producten
$is_trekhaakdop_in_cart = false;
$cart_count = 0; // Totaal aantal items

// 1. BEREKEN DE TOTALE PRODUCTPRIJS EN CONTROLEER OP TREKHAAKDOP
foreach ($cart as $item) {
    if (isset($item['name']) && isset($item['price']) && isset($item['quantity'])) {
        
        // Controleer of de trekhaakdop in de wagen zit (hoofdletterongevoelig)
        if (strpos(strtolower($item['name']), 'trekhaakdop') !== false) {
            $is_trekhaakdop_in_cart = true;
        }
        
        $total_product_price += (float)$item['price'] * (int)$item['quantity'];
        $cart_count += (int)$item['quantity'];
    }
}

// 2. BEPALEN VAN DE BEZORGMETHODE EN KOSTEN (PostNL tarieven 2024)
$TARIFFEN = [
    'BRIEVENBUSPAKKET' => ['methode' => 'PostNL Brievenbuspakket', 'kosten' => 4.50], 
    'STANDAARDPAKKET'  => ['methode' => 'PostNL Standaardpakket', 'kosten' => 6.95]
];

if ($total_product_price > 0) {
    if ($is_trekhaakdop_in_cart) {
        $shipping_info = $TARIFFEN['STANDAARDPAKKET'];
    } else {
        $shipping_info = $TARIFFEN['BRIEVENBUSPAKKET'];
    }
} else {
    // Winkelwagen is leeg
    $shipping_info = ['methode' => 'Geen levering nodig', 'kosten' => 0.00];
}

$shipping_cost = $shipping_info['kosten'];
$shipping_method = $shipping_info['methode'];

// 3. BEREKEN HET EINDTOTAAL
$grand_total = $total_product_price + $shipping_cost;
    
// Sluit de DB-verbinding direct (hoewel het gebruik van de verbinding voor productinfo ontbreekt in deze code, is het goed om de verbinding te sluiten)
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <title>Winkelwagen - 3DTB Shop</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        /* Basis stijlen voor de tabel */
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: var(--color-primary);
            box-shadow: var(--shadow-dark);
        }
        .cart-table th, .cart-table td {
            border: 1px solid var(--color-secondary-light);
            padding: 12px;
            text-align: left;
        }
        .cart-table th {
            background-color: var(--color-secondary);
            /* AANGEPAST: Geel accent voor de thead tekst */
            color: #ffc107; /* Geel */ 
            font-weight: bold;
        }
        .cart-table tbody tr:nth-child(even) {
            background-color: var(--color-primary-light);
        }
        .cart-item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .quantity-input {
            width: 60px;
            padding: 5px;
            text-align: center;
            border: 1px solid var(--color-secondary);
            border-radius: 4px;
            transition: background-color 0.5s;
        }
        .remove-button {
            background-color: #dc3545; /* Rood (Ongewijzigd) */
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .remove-button:hover {
            background-color: #c82333;
        }
        .cart-table tfoot td {
            font-weight: bold;
            font-size: 1.1em;
        }
        .total-label {
            text-align: right;
            border-right: none !important;
        }
        /* AANGEPAST: Prijsaanduidingen in geel */
        .total-amount {
            color: #ffc107; 
        }
        /* BELANGRIJK: Aanpassing voor de nieuwe knoppen-layout */
        .cart-actions {
            margin-top: 20px;
            display: flex;
            justify-content: space-between; /* Zorgt ervoor dat de Verder Winkelen-knop links blijft */
            gap: 15px;
            padding-top: 20px; /* Ruimte boven de knoppen */
            border-top: 1px solid var(--color-secondary-light); /* Visuele scheiding */
        }
        
        /* === STIJLEN VOOR LEEG WINKELWAGEN BERICHT & GELE KNOP === */
        .empty-cart-message {
            padding: 40px; 
            text-align: center;
            background-color: var(--color-secondary);
            border-radius: 8px;
            margin-top: 20px;
            color: #eeeeee;
            font-size: 1.1em;
        }
        
        /* De gele knop */
        .empty-cart-message .button-link {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 30px;
            background-color: #ffc107; 
            color: #000000;
            text-decoration: none;
            font-weight: bold;
            border-radius: 4px;
            transition: background-color 0.3s;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            font-size: 1.1em;
        }
        
        .empty-cart-message .button-link:hover {
            background-color: #ffcd38;
        }
        /* EINDE AANGEPASTE STIJLEN */
    </style>
</head>
<body>

<header class="main-header">
    <div class="logo">
        <h1>3DTB Shop</h1>
    </div>
    <div class="utility-nav">
        <a href="cart.php" class="cart-link" style="background-color: #ffc107; color: var(--color-primary);">🛒 Winkelwagen (<?php echo $cart_count; ?>)</a> 
        <?php if ($is_logged_in): ?>
            <span class="welcome-user">Welkom, <?php echo $user_name; ?></span>
            <a href="logout.php">Uitloggen</a>
        <?php else: ?>
            <a href="login.php">Inloggen</a>
            <a href="register.php">Registreren</a>
        <?php endif; ?>
    </div>
</header>

<main class="content-container">
    <h2 class="section-title">🛒 Uw Winkelwagen</h2>

    <div id="statusMessage" style="display:none; padding: 10px; border-radius: 4px; margin-bottom: 15px;"></div>

    <?php if (empty($cart)): ?>
        <div class="empty-cart-message">
            <p>Uw winkelwagen is leeg. Ga naar
                <a href="index.php" class="button-link">onze producten</a>
                om iets moois uit te zoeken!
            </p>
        </div>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Afbeelding</th>
                    <th>Prijs p/s</th>
                    <th>Aantal</th>
                    <th>Subtotaal</th>
                    <th>Actie</th>
                </tr>
            </thead>
            <tbody id="cartTableBody">
                <?php foreach ($cart as $unique_key => $item):  
                    
                    if (!isset($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
                        continue; 
                    }
                    
                    $price = (float)$item['price'];
                    $quantity = (int)$item['quantity'];
                    $product_subtotal = $price * $quantity;
                ?>
                <tr id="row-<?php echo htmlspecialchars($unique_key); ?>">
                    <td>
                        <?php echo htmlspecialchars($item['name']); ?>
                        <br>
                        <?php if (isset($item['color']) && !empty($item['color'])): ?>
                            <small style="color: #ffc107;">Kleur: 
                                <strong><?php echo htmlspecialchars($item['color']); ?></strong>
                            </small>
                        <?php endif; ?>
                        
                        <?php if (isset($item['text']) && !empty($item['text'])): ?>
                            <br>
                            <small>Tekst: 
                                <em><?php echo htmlspecialchars($item['text']); ?></em>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                        <?php endif; ?>
                    </td>
                    <td id="price-<?php echo htmlspecialchars($unique_key); ?>">€ <?php echo number_format($price, 2, ',', '.'); ?></td>
                    
                    <td>
                        <input 
                            type="number" 
                            value="<?php echo htmlspecialchars($quantity); ?>" 
                            min="1" 
                            class="quantity-input"
                            data-item-key="<?php echo htmlspecialchars($unique_key); ?>"
                            data-price="<?php echo htmlspecialchars($price); ?>"
                            onchange="updateCartQuantity(this);"
                        >
                    </td>

                    <td id="subtotal-<?php echo htmlspecialchars($unique_key); ?>">€ <?php echo number_format($product_subtotal, 2, ',', '.'); ?></td>
                    
                    <td>
                        <button 
                            type="button" 
                            class="remove-button" 
                            title="Verwijder dit item"
                            onclick="removeItem('<?php echo htmlspecialchars($unique_key); ?>')">
                            🗑️ Verwijderen
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right total-label">Subtotaal producten:</td>
                    <td class="total-amount">€ <?php echo number_format($total_product_price, 2, ',', '.'); ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right total-label">Bezorgmethode (<?php echo htmlspecialchars($shipping_method); ?>):</td>
                    <td class="total-amount">
                        <?php 
                        if ($shipping_cost > 0) {
                            echo '€ ' . number_format($shipping_cost, 2, ',', '.');
                        } else {
                            echo 'Gratis';
                        }
                        ?>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right total-label">Eindtotaalprijs (incl. Bezorging):</td>
                    <td id="grandTotal" class="total-amount" style="font-size: 1.2em; color: #ffc107;">
                        **€ <?php echo number_format($grand_total, 2, ',', '.'); ?>**
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="cart-actions" style="justify-content: space-between;">
            
            <a href="index.php" class="button secondary">🛍️ Verder Winkelen</a>
            
            <div style="display: flex; gap: 15px;">
                
                <?php if ($is_logged_in): ?>
                    <a href="checkout.php" class="button primary" style="background-color: #ffc107; color: #000; padding: 10px 20px;">
                        ✅ Afrekenen als <?php echo $user_name; ?>
                    </a>
                <?php else: ?>
                    <a href="checkout.php" class="button secondary" style="background-color: #28a745; color: white; padding: 10px 20px;">
                        👤 Als Gast Afrekenen
                    </a>
                    
                    <a href="login.php?redirect=checkout.php" class="button primary checkout-prompt" style="background-color: #ffc107; color: #000; padding: 10px 20px;">
                        🔒 Inloggen en Afrekenen
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
</main>
</body>
</html>

<script>
// ==========================================================
// JAVASCRIPT VOOR AJAX HANDELING
// ==========================================================
// De AJAX-functies herladen de pagina na succes, zodat de PHP-kostenlogica 
// opnieuw wordt uitgevoerd.

/**
 * Functie voor het tonen van statusberichten (success of error)
 */
function showStatusMessage(message, type) {
    const msgDiv = document.getElementById('statusMessage');
    msgDiv.textContent = message;
    
    // AANGEPAST: Gebruik neutrale/standaardkleuren voor feedback
    if (type === 'success') {
        msgDiv.style.backgroundColor = '#d4edda'; // Lichtgroen
        msgDiv.style.color = '#155724'; // Donkergroen
        msgDiv.style.border = '1px solid #c3e6cb';
    } else {
        msgDiv.style.backgroundColor = '#f8d7da'; // Lichtrood
        msgDiv.style.color = '#721c24'; // Donkerrood
        msgDiv.style.border = '1px solid #f5c6cb';
    }
    msgDiv.style.display = 'block';
    
    // Verberg bericht na 5 seconden
    setTimeout(() => {
        msgDiv.style.display = 'none';
    }, 5000);
}


/**
 * Verstuurt de AJAX-request om de hoeveelheid aan te passen.
 */
function updateCartQuantity(inputElement) {
    const itemKey = inputElement.getAttribute('data-item-key');
    const newQuantity = parseInt(inputElement.value);

    // AANGEPAST: Gebruik geel als tijdelijke feedback kleur
    inputElement.style.backgroundColor = '#ffc107'; 
    
    if (newQuantity < 1) {
        inputElement.value = 1; 
        removeItem(itemKey);
        return;
    }
    
    fetch('update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `item_key=${encodeURIComponent(itemKey)}&new_quantity=${newQuantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // HERLAAD DE PAGINA om de PHP-bezorgkosten opnieuw te berekenen
            window.location.reload(); 
            
        } else {
            showStatusMessage('Fout: ' + data.message, 'error');
            inputElement.style.backgroundColor = '#f8d7da'; 
        }
    })
    .catch(error => {
        console.error('Netwerkfout:', error);
        showStatusMessage('Er is een netwerkfout opgetreden bij het bijwerken.', 'error');
        inputElement.style.backgroundColor = '#f8d7da';
    });
}


/**
 * Verwijder een item uit de winkelwagen (roept updateCartQuantity aan met quantity = 0).
 */
function removeItem(itemKey) {

    const inputElement = document.querySelector(`.quantity-input[data-item-key="${itemKey}"]`);
    
    if (!inputElement) {
        showStatusMessage('Fout: Item niet gevonden voor verwijdering.', 'error');
        return;
    }
    
    const rowElement = document.getElementById(`row-${itemKey}`);
    if (rowElement) {
        rowElement.style.opacity = '0.5'; 
    }

    fetch('update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `item_key=${encodeURIComponent(itemKey)}&new_quantity=0`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // HERLAAD DE PAGINA om de PHP-bezorgkosten opnieuw te berekenen
            window.location.reload(); 

        } else {
            showStatusMessage('Fout bij verwijderen: ' + data.message, 'error');
            if (rowElement) {
                rowElement.style.opacity = '1.0'; 
            }
        }
    })
    .catch(error => {
        console.error('Netwerkfout:', error);
        showStatusMessage('Er is een netwerkfout opgetreden bij het verwijderen.', 'error');
        if (rowElement) {
            rowElement.style.opacity = '1.0'; 
        }
    });
}
</script>