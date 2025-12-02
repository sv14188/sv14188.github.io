<?php
    include 'config.php'; 

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // Versleutel het wachtwoord VEILIG
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Gebruiker Invoegen (Prepared Statement)
        $sql = "INSERT INTO shop_users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        
        if ($stmt->execute()) {
            echo "Registratie succesvol! U kunt nu <a href='login.php'>inloggen</a>.";
        } else {
            echo "Fout bij registreren: " . $stmt->error;
        }
        $stmt->close();
    }
?>