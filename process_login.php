<?php
    session_start(); 
    include 'config.php';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $sql = "SELECT id, name, password FROM shop_users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Wachtwoord Verificatie
            if (password_verify($password, $user['password'])) {
                
                // Login Succesvol: Start Sessie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['logged_in'] = true;
                
                header("Location: index.php");
                exit();
                
            } else { echo "Ongeldige inloggegevens."; }
        } else { echo "Ongeldige inloggegevens."; }
        
        $stmt->close();
    }
?>