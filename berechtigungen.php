<?php
/**
 * BERECHTIGUNGEN.PHP - MIT "ALS BENUTZER ANSEHEN"-FUNKTION
 * Version 4.0 - Admin kann in andere Benutzer "reinschauen"
 */

// Session starten wenn nicht aktiv
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Prüft ob Benutzer eingeloggt ist
 */
function ist_eingeloggt() {
    return isset($_SESSION['benutzer_id']) && !empty($_SESSION['benutzer_id']);
}

/**
 * Prüft ob Benutzer Admin ist
 */
function ist_admin() {
    if (!ist_eingeloggt()) {
        return false;
    }
    return isset($_SESSION['rolle_id']) && intval($_SESSION['rolle_id']) === 1;
}

/**
 * Gibt Benutzer-ID zurück
 */
function get_aktuelle_benutzer_id() {
    if (!ist_eingeloggt()) {
        return null;
    }
    return intval($_SESSION['benutzer_id']);
}

/**
 * NEU: Gibt die "View As"-Benutzer-ID zurück
 * Wenn Admin einen anderen Benutzer ansieht, wird dessen ID zurückgegeben
 */
function get_view_as_user_id() {
    if (!ist_admin()) {
        return null;
    }
    return isset($_SESSION['view_as_user_id']) ? intval($_SESSION['view_as_user_id']) : null;
}

/**
 * NEU: Prüft ob Admin gerade als anderer Benutzer ansieht
 */
function ist_viewing_as_other_user() {
    return ist_admin() && isset($_SESSION['view_as_user_id']);
}

/**
 * NEU: Gibt die effektive Benutzer-ID zurück
 * - Wenn Admin als anderer Benutzer ansieht → deren ID
 * - Sonst → eigene ID
 */
function get_effektive_benutzer_id() {
    $view_as = get_view_as_user_id();
    if ($view_as !== null) {
        return $view_as;
    }
    return get_aktuelle_benutzer_id();
}

/**
 * ERWEITERT: WHERE-Bedingung mit View-Mode-Support
 */
function get_where_zugriff($spalte) {
    // Admin im View-Mode → Daten des angesehenen Benutzers
    if (ist_viewing_as_other_user()) {
        $view_user_id = get_view_as_user_id();
        return "({$spalte} = {$view_user_id} OR {$spalte} IS NULL)";
    }
    
    // Admin ohne View-Mode → ALLE Daten
    if (ist_admin()) {
        return "1=1";
    }
    
    // Benutzer-ID holen
    $benutzer_id = get_aktuelle_benutzer_id();
    
    // Wenn nicht eingeloggt → nichts anzeigen
    if ($benutzer_id === null) {
        return "1=0";
    }
    
    // Normale Benutzer sehen nur eigene + NULL-Werte
    return "({$spalte} = {$benutzer_id} OR {$spalte} IS NULL)";
}

/**
 * Prüft Zugriff auf Datensatz
 */
function hat_zugriff($ersteller_id) {
    // Admin im View-Mode
    if (ist_viewing_as_other_user()) {
        $view_user_id = get_view_as_user_id();
        return intval($ersteller_id) === $view_user_id || $ersteller_id === null;
    }
    
    // Admin hat immer Zugriff
    if (ist_admin()) {
        return true;
    }
    
    // NULL-Werte sind für alle sichtbar
    if ($ersteller_id === null || $ersteller_id === '') {
        return true;
    }
    
    // Eigene Datensätze
    return intval($ersteller_id) === get_aktuelle_benutzer_id();
}

/**
 * Nur für Admin-Seiten
 */
function nur_admin_zugriff($redirect = 'index.php') {
    if (!ist_admin()) {
        $_SESSION['error_message'] = 'Keine Berechtigung für diese Seite.';
        header("Location: $redirect");
        exit();
    }
}

/**
 * Redirect wenn kein Zugriff
 */
function pruefe_zugriff_oder_redirect($ersteller_id, $redirect = 'index.php') {
    if (!hat_zugriff($ersteller_id)) {
        $_SESSION['error_message'] = 'Kein Zugriff auf diesen Datensatz.';
        header("Location: $redirect");
        exit();
    }
}

/**
 * NEU: Setzt den "View As"-Modus
 */
function set_view_as_user($user_id) {
    if (!ist_admin()) {
        return false;
    }
    $_SESSION['view_as_user_id'] = intval($user_id);
    return true;
}

/**
 * NEU: Beendet den "View As"-Modus
 */
function clear_view_as_user() {
    if (isset($_SESSION['view_as_user_id'])) {
        unset($_SESSION['view_as_user_id']);
    }
}

/**
 * NEU: Holt Benutzername für "View As"-Banner
 */
function get_view_as_username() {
    global $pdo;
    
    if (!ist_viewing_as_other_user()) {
        return null;
    }
    
    $view_user_id = get_view_as_user_id();
    
    try {
        $stmt = $pdo->prepare("SELECT Benutzername FROM Benutzer WHERE Benutzer_ID = ?");
        $stmt->execute([$view_user_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Fehler in get_view_as_username: " . $e->getMessage());
        return null;
    }
}
?>
