<?php
    session_start();
    // config is niet nodig, tenzij je hier de bestelling opnieuw uit DB wilt ophalen.
    
    // De order_id is opgeslagen in process_order.php
    $order_id = $_SESSION['order_id'] ?? null;
    $order_date = date("d-m-Y H:i");

    // Haal de gebruikersnaam op voor de begroeting
    $user_name = $_SESSION['user_name'] ?? 'Klant';

    // Wis de order_id uit de sessie nadat we deze hebben gebruikt
    unset($_SESSION['order_id']); 
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Bestelling Bevestigd - 3DTB Shop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="main-header">
    <div class="logo"><h1>3DTB Shop</h1></div>
    <div class="utility-nav">
        <span class="welcome-user">Welkom, <?php echo htmlspecialchars($user_name); ?></span>
        <a href="logout.php">Uitloggen</a>
    </div>
</header>

<main class="content-container" style="text-align: center;">
    <div style="max-width: 700px; margin: 50px auto; padding: 40px; background-color: var(--color-primary); border-radius: 8px; box-shadow: var(--shadow-heavy); border: 1px solid var(--color-accent);">
        <h2 style="color: var(--color-accent); font-size: 2.5em; margin-bottom: 20px;">🎉 Bedankt voor uw Bestelling!</h2>
        
        <p style="font-size: 1.2em; margin-bottom: 30px;">
            Beste **<?php echo htmlspecialchars($user_name); ?>**, uw bestelling is succesvol ontvangen.
        </p>

        <?php if ($order_id): ?>
            <p style="font-size: 1.1em; color: var(--color-text-light);">
                Uw bestelnummer is: **#<?php echo htmlspecialchars($order_id); ?>**
            </p>
        <?php endif; ?>
        
        <p style="margin-top: 15px; margin-bottom: 40px;">
            U ontvangt een bevestiging op uw e-mailadres. De bestelling van **<?php echo $order_date; ?>** zal spoedig worden verwerkt.
        </p>
        
        <a href="index.php" class="button primary">Terug naar de Hoofdpagina</a>
    </div>
</main>
</body>
</html>