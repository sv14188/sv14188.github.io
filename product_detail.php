<?php
    session_start();
    include 'config.php'; 

    // Controleer of een product ID is meegegeven
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header('Location: index.php'); // Terug naar home als ID mist
        exit;
    }

    $product_id = $_GET['id'];
    
    // Bereid de query voor om SQL injection te voorkomen
    $stmt = $conn->prepare("SELECT id, name, description, price, image_url, stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Product niet gevonden
        header('Location: index.php');
        exit;
    }

    $product = $result->fetch_assoc();
    $stmt->close();
    
    // Standaard gebruikersnaam voor de header
    $user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Gast';

    // ==========================================================
    // DEFINITIE VAN KLEURCOMBINATIES (MOETEN GELIJK ZIJN AAN INDEX.PHP)
    // ==========================================================
    $standaard_kleuren = [
        'Rood', 'Wit', 'Groen', 'Zwart', 'Blauw'
    ];

    $speciale_naam_kleuren = [
        'Zwart/Wit', 
        'Rood/wit',  
        'Blauw/Wit', 
        'Groen/Zwart', 
        'Wit/Zwart'  
    ];
    
    // Bepaal of dit product personalisatie toestaat (logica van index.php)
    $product_name_raw = $product['name'];
    $allow_color = true; 
    $allow_text = true;  
    $is_naam_sleutelhanger = false; 

    // REGEL A: Product met speciale kleurselectie
    if ($product_name_raw === 'Sleutelhanger Naam') {
        $allow_color = true; 
        $allow_text = true;   
        $is_naam_sleutelhanger = true; 
    
    // REGEL B: Product zonder personalisatie
    } elseif ($product_name_raw === 'Sleutelhanger World jamboree algemeen' || $product_name_raw === 'sleutelhanger jamboree') {
        $allow_color = false; 
        $allow_text = false;  
    }
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>3D TB - Product Detail: <?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Detailpagina specifieke styling */
        .detail-container {
            display: flex;
            gap: 40px;
            max-width: 1000px;
            /* Margin is aangepast om ruimte te maken voor de terugknop */
            margin: 20px auto 40px auto; 
            background-color: var(--color-secondary);
            padding: 30px;
            border-radius: 8px;
            box-shadow: var(--shadow-light);
        }
        .detail-image {
            flex: 1;
        }
        .detail-image img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }
        .detail-info {
            flex: 1.5;
        }
        .detail-info h2 {
            color: var(--color-accent);
            margin-bottom: 10px;
        }
        .detail-info .price {
            font-size: 2em;
            font-weight: bold;
            color: var(--color-text-dark);
            margin-bottom: 20px;
        }
        .detail-info p {
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        /* Personalization & Cart styles */
        .personalization-form {
            padding: 20px;
            border: 1px solid var(--color-primary);
            border-radius: 6px;
            margin-top: 20px;
            background-color: var(--color-primary-light);
        }
        .personalization-form label {
            display: block;
            margin-top: 10px;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--color-text-dark);
        }
        .personalization-form select,
        .personalization-form input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        .personalization-form button {
            background-color: var(--color-accent);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1em;
            transition: background-color 0.3s;
        }
        .personalization-form button:hover {
            background-color: var(--color-accent-hover);
        }
        
        /* Stock status styling */
        .stock-status-detail {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: green;
            font-weight: bold;
        }
        .stock-indicator-detail {
            height: 12px;
            width: 12px;
            background-color: green;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        /* CONTAINER VOOR DE KNOP (KLEINER GEMAAKT) */
        .back-button-placement {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 20px 0 20px; 
        }
        .back-button-placement .button.primary {
            /* De breedte is gereduceerd van 250px naar 180px */
            width: 180px; 
            display: inline-block;
            text-align: center; 
            /* De padding is verminderd om de knop kleiner te maken */
            padding: 8px 15px; 
        }
    </style>
</head>
<body>

<header class="main-header">
    
    <div class="logo"><h1>3D TB</h1></div>
    <div class="utility-nav">
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <span class="welcome-user">Welkom, <?php echo $user_name; ?></span>
            <a href="cart.php">🛒 Winkelwagen</a> 
            <a href="logout.php">Uitloggen</a>
        <?php else: ?>
            <a href="login.php">🔒Inloggen</a>
            <a href="register.php">🔒Registreren</a>
            <a href="cart.php">🛒 Winkelwagen</a> 
        <?php endif; ?>
    </div>
</header>

<div class="back-button-placement">
    <a href="index.php" class="button primary">
        ← Terug
    </a>
</div>

<main class="content-container">
    <div class="detail-container">
        <div class="detail-image">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        
        <div class="detail-info">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <div class="price">€ <?php echo number_format($product['price'], 2, ',', '.'); ?></div>
            
            <p>
                <?php 
                    // Toon de volledige beschrijving
                    echo nl2br(htmlspecialchars($product['description'])); 
                ?>
            </p>
            
            <?php if ($product['stock'] > 0): ?>
            <div class="stock-status-detail">
                <span class="stock-indicator-detail"></span> *Op voorraad*
				</div>
            <?php else: ?>
            <div class="stock-status-detail" style="color: red;">
                <span class="stock-indicator-detail" style="background-color: red;"></span> *Tijdelijk uitverkocht*
            </div>
            <?php endif; ?>

            <div class="personalization-form">
                <?php if ($product['stock'] > 0): ?>
                    <form class="add-to-cart-form" action="add_to_cart.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="quantity" value="1">

                        <h3 style="margin-top: 0; color: var(--color-accent); font-size: 1.2em;">Bestellen & Personaliseren</h3>
                        
                        <?php if ($allow_color): ?>
                            <label for="color_detail">Kies Kleur (*):</label>
                            <select name="selected_color" id="color_detail" required>
                                <?php 
                                if ($is_naam_sleutelhanger): 
                                    foreach ($speciale_naam_kleuren as $color): ?>
                                        <option value="<?php echo htmlspecialchars($color); ?>"><?php echo htmlspecialchars($color); ?></option>
                                    <?php endforeach; ?>
                                <?php 
                                else: 
                                    foreach ($standaard_kleuren as $color): ?>
                                        <option value="<?php echo htmlspecialchars($color); ?>"><?php echo htmlspecialchars($color); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="selected_color" value="">
                        <?php endif; ?>
                            
                        <?php if ($allow_text): ?>
                            <label for="custom_text_detail">Eigen Tekst (Optioneel):</label>
                            <input type="text" name="custom_text" id="custom_text_detail" maxlength="50" placeholder="Max. 50 tekens">
                        <?php else: ?>
                            <input type="hidden" name="custom_text" value="">
                        <?php endif; ?>

                        <button type="submit">
                            Toevoegen aan winkelwagen
                        </button>
                    </form>
                <?php else: ?>
                    <p style="color: red; text-align: center;">Dit product kan momenteel niet besteld worden omdat het is uitverkocht.</p>
                <?php endif; ?>
            </div>
            </div>
    </div>
</main>

<div id="cartModal" style="
    display: none; 
    position: fixed; 
    z-index: 1000; 
    left: 0; 
    top: 0; 
    width: 100%; 
    height: 100%; 
    overflow: auto; 
    background-color: rgba(0,0,0,0.8); 
    padding-top: 100px;
">
    <div style="
        background-color: #fefefe; 
        margin: 5% auto; 
        padding: 30px; 
        border: 1px solid #888; 
        width: 80%; 
        max-width: 400px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    ">
        <h2 style="color: #28a745;">✅ Toegevoegd aan winkelwagen!</h2>
        <p style="margin-bottom: 25px;">Uw product is succesvol toegevoegd.</p>
        
        <button onclick="closeModal()" style="
            background-color: #007bff; 
            color: white; 
            padding: 10px 15px; 
            border: none; 
            border-radius: 4px; 
            margin-right: 10px;
            cursor: pointer;
        ">Verder winkelen</button>
        
        <a href="cart.php" style="
            background-color: #28a745; 
            color: white; 
            padding: 10px 15px; 
            border: none; 
            border-radius: 4px; 
            text-decoration: none;
            cursor: pointer;
        ">Naar winkelwagen</a>
    </div>
</div>

<script>
    // Functie om de modal te sluiten
    function closeModal() {
        document.getElementById('cartModal').style.display = 'none';
    }

    // Wacht tot de pagina geladen is
    document.addEventListener('DOMContentLoaded', function() {
        // Selecteer het formulier in de detailpagina
        const form = document.querySelector('.personalization-form .add-to-cart-form');

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Voorkom de standaard formulier-submit (redirect)

                // Gebruik fetch om de gegevens naar add_to_cart.php te sturen
                fetch('add_to_cart.php', {
                    method: 'POST',
                    body: new FormData(this) // Verstuurt alle formulierdata
                })
                .then(response => response.json()) // Verwacht een JSON-antwoord
                .then(data => {
                    if (data.status === 'success') {
                        // Toon de modal na succes
                        document.getElementById('cartModal').style.display = 'block';
                    } else {
                        // Toon een foutmelding (kan later verbeterd worden)
                        alert('Fout: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Er is een netwerkfout opgetreden.');
                });
            });
        }
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
<?php $conn->close(); ?>