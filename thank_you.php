<?php
session_start();

// Haal het order ID op uit de URL (als het er is)
$order_id = isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : 'Onbekend';

// Optioneel: U kunt hier later de database opnieuw aanspreken om details op te halen

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Bedankt voor uw Bestelling!</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .thank-you-container {
            max-width: 600px;
            margin: 80px auto;
            padding: 40px;
            background-color: var(--color-primary-light);
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        .thank-you-container h2 {
            color: #ffc107;
            font-size: 2em;
            margin-bottom: 20px;
        }
        .thank-you-container p {
            margin-bottom: 15px;
            color: #ccc;
        }
        .order-id {
            font-size: 1.5em;
            font-weight: bold;
            color: #ffffff;
            background-color: #3a3a3a;
            padding: 10px 20px;
            border-radius: 4px;
            display: inline-block;
            margin: 15px 0;
        }
    </style>
</head>
<body>

    <main class="content-container">
        <div class="thank-you-container">
            <h2>🎉 Bedankt voor uw Bestelling! 🎉</h2>
            
            <p>Uw bestelling is succesvol geplaatst en verwerkt.</p>
            
            <p>Uw bestelnummer is:</p>
            <div class="order-id">#<?php echo $order_id; ?></div>
            
            <p>U ontvangt binnenkort een bevestiging per e-mail met alle details.</p>
            
            <div style="margin-top: 30px;">
                <a href="index.php" class="button primary" style="background-color: #ffc107; color: #000; padding: 10px 20px;">
                    Terug naar de Homepage
                </a>
            </div>
        </div>
    </main>
    
</body>
</html>