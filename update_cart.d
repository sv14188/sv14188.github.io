<?php
    session_start();
    include 'config.php'; 

    // Zorg dat de cart array bestaat als de sessie nieuw is
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // --- 1. ITEM VERWIJDEREN (DE PRULLENBAK FUNCTIE) ---
    // Dit maakt de prullenbak werkend
    if (isset($_POST['remove_item']) && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        
        if (isset($_SESSION['cart'][$product_id])) {
            // Verwijder de hele entry uit de $_SESSION['cart'] array
            unset($_SESSION['cart'][$product_id]);
        }
        
        header("Location: cart.php");
        exit();
    }

    // --- 2. HOEVEELHEID BIJWERKEN (DE 'VERNIEUWEN' KNOP) ---
    // Dit zorgt ervoor dat de prijs verandert bij het aantal
    if (isset($_POST['update_quantity']) && isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];

        if ($quantity > 0 && isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        } elseif ($quantity <= 0 && isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
        
        header("Location: cart.php");
        exit();
    }

    // Standaard terugval
    header("Location: index.php");
    exit();
?>