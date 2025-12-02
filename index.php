<?php
session_start();
include 'config.php'; // Zorgt voor DB connectie

$products = [];
$error_message = '';
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : 'Gast';

// ==========================================================
// WINKELWAGEN TELLER LOGICA
// ==========================================================

$cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        // Telt het aantal van elk item op
        $cart_count += $item['quantity']; 
    }
}

// ==========================================================
// FUNCTIES & KLEUREN
// ==========================================================

// Functie om voorraadstatus te bepalen
function get_stock_status($quantity) {
    if ($quantity > 0) {
        return ['text' => 'Op voorraad', 'color' => 'green'];
    } else {
        return ['text' => 'Uitverkocht', 'color' => 'red'];
    }
}

// Bepaal de personalisatie-opties per product
function get_product_options($product_name) {
    $name = trim(strtolower($product_name));
    
    if ($name === 'sleutelhanger naam') {
        return ['allow_color' => true, 'allow_text' => true, 'color_options' => ['Zwart/Wit', 'Rood/wit', 'Blauw/Wit', 'Groen/Zwart', 'Wit/Zwart']];
    } elseif ($name === 'sleutelhanger world jamboree algemeen' || $name === 'sleutelhanger jamboree') {
        return ['allow_color' => false, 'allow_text' => false, 'color_options' => []];
    } elseif ($name === 'sleutelhanger logo world jamboree met naam') {
         return ['allow_color' => false, 'allow_text' => true, 'color_options' => []];
    } else {
        // Standaard voor Trekaakdop, etc.
        return ['allow_color' => true, 'allow_text' => true, 'color_options' => ['Rood', 'Wit', 'Groen', 'Zwart', 'Blauw']];
    }
}


// Haal producten op uit de database
if (isset($conn) && $conn instanceof mysqli) {
    // Gebruik van 'stock' kolom
    $sql = "SELECT id, name, description, price, image_url, stock FROM products ORDER BY id ASC";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $result->free();
    } else {
        $error_message = "Fout bij het ophalen van producten: " . $conn->error;
    }
} else {
    $error_message = "Fout: Databaseverbinding niet beschikbaar.";
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>3D TB - Home</title>
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
    <h2 class="section-title">Onze Producten</h2>

    <?php if ($error_message): ?>
        <p class="error-message"><?php echo $error_message; ?></p>
    <?php endif; ?>
    
    <div class="products-grid">
        <?php
        if (!empty($products)) {
            foreach($products as $product)
            {
                $status = get_stock_status((int)$product['stock']);
                $is_in_stock = (int)$product['stock'] > 0;
                $options = get_product_options($product['name']);
                $detail_url = "product_detail.php?id=" . $product['id'];
        ?>
            <div class="product-card">
                <div>
                    <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'placeholder.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="price">€ <?php echo number_format($product['price'], 2, ',', '.'); ?></div>
                    
                    <div class="stock-status" style="color: <?php echo $status['color']; ?>;">
                        <span class="stock-indicator" style="background-color: <?php echo $status['color']; ?>;"></span>
                        <?php echo $status['text']; ?>
                    </div>
                </div>

                <div class="product-form-section">
                    
                    <?php if ($is_in_stock): ?>
                        
                        <h4 class="personalization-title">Personaliseer</h4>
                        
                        <form class="add-to-cart-form" action="add_to_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">

                            <?php if ($options['allow_color']): ?>
                                <div class="form-group" style="width: 100%;">
                                    <label for="color_<?php echo $product['id']; ?>">Kies Kleur (*):</label>
                                    <select name="selected_color" id="color_<?php echo $product['id']; ?>" required>
                                        <?php foreach ($options['color_options'] as $color): ?>
                                            <option value="<?php echo htmlspecialchars($color); ?>"><?php echo htmlspecialchars($color); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="selected_color" value="">
                            <?php endif; ?>
                                
                            <?php if ($options['allow_text']): ?>
                                <div class="form-group" style="width: 100%;">
                                    <label for="custom_text_<?php echo $product['id']; ?>">Eigen Tekst (Optioneel):</label>
                                    <input type="text" name="custom_text" id="custom_text_<?php echo $product['id']; ?>" maxlength="50" placeholder="Max. 50 tekens">
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="custom_text" value="">
                                <p style="font-size: 0.9em; margin-bottom: 10px; color: var(--color-text-secondary);">Dit product is niet personaliseerbaar.</p>
                            <?php endif; ?>
                            
                            <a href="<?php echo $detail_url; ?>" class="button secondary">
                                Meer info
                            </a>
                            
                            <button type="submit" class="button primary">
                                Toevoegen aan winkelwagen
                            </button>
                        </form>
                        
                    <?php else: ?>
                        <div style="margin-top: 20px;">
                            <button type="button" class="button primary" disabled>Uitverkocht</button>
                            <a href="<?php echo $detail_url; ?>" class="button secondary" style="margin-top: 10px;">
                                Meer info
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php
            } // einde foreach loop
        } else {
            echo "<p>Er zijn momenteel geen producten om weer te geven.</p>";
        }
        ?>
    </div>
</main>

<div id="cartModal" style="display: none;" class="cart-modal-overlay">
    <div class="cart-modal-content">
        <h2 style="color: #4CAF50;">✅ Toegevoegd aan winkelwagen!</h2>
        <p style="margin-bottom: 25px;">Uw product is succesvol toegevoegd.</p>
        
        <button onclick="closeModal()" class="button secondary" style="width: 45%; margin-right: 10px;">Verder winkelen</button>
        
        <a href="cart.php" class="button primary" style="width: 45%; margin-left: 10px;">Naar winkelwagen</a>
    </div>
</div>

<script>
    // Functie om de modal te sluiten
    function closeModal() {
        document.getElementById('cartModal').style.display = 'none';
    }

    // Functie om de winkelwagenteller bij te werken (op basis van het JSON-antwoord)
    function updateCartCount(newCount) {
        const cartLink = document.querySelector('.cart-link');
        if (cartLink) {
            cartLink.innerHTML = `🛒 Winkelwagen (${newCount})`;
        }
    }


    // AJAX / Fetch logica voor direct toevoegen aan winkelwagen
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('.add-to-cart-form');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); 

                fetch('add_to_cart.php', {
                    method: 'POST',
                    body: new FormData(this) 
                })
                .then(response => {
                    if (!response.ok || response.headers.get('content-type').indexOf('application/json') === -1) {
                        return response.text().then(text => {
                             throw new Error('Serverfout of geen JSON-antwoord: ' + text.substring(0, 100) + '...'); 
                        });
                    }
                    return response.json(); 
                })
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('cartModal').style.display = 'block';
                        
                        // DE TELLER BIJWERKEN VIA HET JSON-ANTWOORD
                        if (data.cart_count !== undefined) {
                            updateCartCount(data.cart_count); 
                        }

                    } else {
                        alert('Fout bij toevoegen: ' + (data.message || 'Onbekende fout.'));
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    alert('Er is een fout opgetreden. Controleer of add_to_cart.php bestaat en alleen JSON terugstuurt bij succes/fout. Foutmelding: ' + error.message);
                });
            });
        });
    });

    // Sluit de modal als de gebruiker buiten de modal klikt
    window.onclick = function(event) {
        const modal = document.getElementById('cartModal');
        if (event.target === modal) {
            closeModal();
        }
    }
</script>

</body>
</html>
<?php 
if (isset($conn) && $conn instanceof mysqli) {
    // $conn->close(); // Laat dit uit commentaar als u de verbinding hier wilt sluiten
} 
?>