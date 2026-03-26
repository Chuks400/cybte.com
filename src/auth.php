<?php

function require_login($redirect = 'login.php'){

    if(session_status() !== PHP_SESSION_ACTIVE){
        session_start();
    }

    if(!isset($_SESSION['user_id'])){
        header('Location: ' . $redirect);
        exit();
    }

    session_regenerate_id(true);
}

function require_role($roles, $redirect = 'login.php'){

    require_login($redirect);

    $userRole = $_SESSION['role'] ?? null;

    if(is_string($roles)){
        $roles = [$roles];
    }

    if(!$userRole || !in_array($userRole, $roles, true)){
        header('Location: ' . $redirect);
        exit();
    }
}
