<?php
require_once("functions.php");
require_once("config.php");

if (isset($_GET['logout']) && isset($_GET['token'])){
    $token = trim(htmlspecialchars($_GET['token']));
    if (csrf_check($token)){
        session_destroy();
        header("location: index.php");
        exit;
    }else{
        echo "error.auth.logout.invalid_csrf";
        header("HTTP/1.0 401 Unauthorized");
        exit;
    }
}

if (isset($_SESSION['user_id'])){
    echo "warning.already_logged";
    header("location: index.php");
    exit;
}

?>