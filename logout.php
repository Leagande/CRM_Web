<?php
// logout.php - Einfacher Logout (ALTERNATIVE VERSION)

// Session starten
session_start();

// Alle Session-Variablen löschen
$_SESSION = array();

// Session-Cookie löschen
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session zerstören
session_destroy();

// Zur Login-Seite mit Logout-Parameter weiterleiten
header("Location: login.php?logout=1");
exit;
?>