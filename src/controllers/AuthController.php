<?php

require_once __DIR__ . '/../models/User.php';

class AuthController {

    public function login($email, $password, $redirect = 'dashboard.php'){

        $userModel = new User();

        $user = $userModel->findByEmail($email);

        if($user){

            if(password_verify($password, $user['password'])){

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