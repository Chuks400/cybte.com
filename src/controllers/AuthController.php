<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/database.php';

class AuthController {

    public function login($email, $password, $redirect = 'dashboard.php'){

        $userModel = new User();

        $user = $userModel->findByEmail($email);

        if($user){

            if(password_verify($password, $user['password'])){
                
                // Check if email is verified
                if (empty($user['email_verified'])) {
                    // Email not verified - redirect to pending page
                    header("Location: verification_pending.php?email=" . urlencode($email));
                    exit();
                }

                session_start();

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'] ?? null;

                header("Location: " . $redirect);
                exit();

            } else {

                echo "Invalid password";

            }

        } else {

            echo "User not found";

        }

    }

}
?>
