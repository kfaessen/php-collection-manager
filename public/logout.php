<?php
require_once '../includes/functions.php';

// Logout de gebruiker
Authentication::logout();

// Redirect naar login pagina
header('Location: login.php');
exit;
?> 