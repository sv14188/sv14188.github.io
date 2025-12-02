<?php
session_start();

// Alleen doorgaan als de index en de remove-knop zijn verzonden
if (isset($_POST['remove']) && isset($_POST['cart_item_index'])) {
    
    // Converteer de index naar een integer voor veiligheid
    $item_index = (int)$_POST['cart_item_index'];

    // Controleer of de winkelwagen en de index bestaan
    if (isset($_SESSION['cart']) && isset($_SESSION['cart'][$item_index])) {
        
        // **ACTIE:** Verwijder de item uit de sessie met unset()
        unset($_SESSION['cart'][$item_index]);

        // Herschik de array sleutels na verwijdering, dit is essentieel 
        // om ervoor te zorgen dat de indexen ($item['index'] in cart.php) 
        // overeenkomen met de array posities in de sessie.
        $_SESSION['cart'] = array_values($_SESSION['cart']); 
        
        $_SESSION['message'] = "Product succesvol verwijderd.";
    } else {
        $_SESSION['error'] = "Fout: Ongeldig winkelwagenitem.";
    }
}

// Stuur terug naar de winkelwagen
header('Location: cart.php');
exit;
?>