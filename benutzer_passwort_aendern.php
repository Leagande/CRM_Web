<?php
// benutzer_passwort_aendern.php - MIT FUNKTIONIERENDEN BUTTONS!
$page_title = "Passwort ändern";
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
require_once 'header.php';

$errors = [];
$user_id = intval($_GET['id'] ?? $_SESSION['benutzer_id']);

// Berechtigung prüfen: Entweder eigenes Passwort ODER Admin
if ($user_id != $_SESSION['benutzer_id'] && !ist_admin()) {
    $_SESSION['error_message'] = "Keine Berechtigung.";
    header("Location: index.php");
    exit;
}

// Benutzer laden
try {
    $stmt = $pdo->prepare("SELECT Benutzer_ID, Benutzername FROM Benutzer WHERE Benutzer_ID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error_message'] = "Benutzer nicht gefunden.";
        header("Location: " . (ist_admin() ? "benutzerverwaltung.php" : "index.php"));
        exit;
    }
} catch (PDOException $e) {
    error_log("Fehler beim Laden des Benutzers: " . $e->getMessage());
    $_SESSION['error_message'] = "Datenbankfehler.";
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $neues_passwort = $_POST['neues_passwort'] ?? '';
    $passwort_bestaetigung = $_POST['passwort_bestaetigung'] ?? '';
    
    // Validierung
    if (empty($neues_passwort)) {
        $errors[] = "Neues Passwort ist erforderlich.";
    } elseif (strlen($neues_passwort) < 6) {
        $errors[] = "Passwort muss mindestens 6 Zeichen lang sein.";
    }
    
    if ($neues_passwort !== $passwort_bestaetigung) {
        $errors[] = "Passwörter stimmen nicht überein.";
    }
    
    // Wenn keine Fehler, dann speichern
    if (empty($errors)) {
        try {
            $passwort_hash = password_hash($neues_passwort, PASSWORD_BCRYPT);
            
            $stmt_update = $pdo->prepare("UPDATE Benutzer SET Passwort_Hash = ? WHERE Benutzer_ID = ?");
            $stmt_update->execute([$passwort_hash, $user_id]);
            
            $_SESSION['success_message'] = "Passwort erfolgreich geändert!";
            
            // Weiterleitung zur Startseite
            header("Location: start.php");
            exit;
            
        } catch (PDOException $e) {
            error_log("Fehler beim Passwort-Update: " . $e->getMessage());
            $errors[] = "Datenbankfehler beim Speichern.";
        }
    }
}

$is_own_password = ($user_id == $_SESSION['benutzer_id']);
$zurueck_url = $is_own_password ? "index.php" : "benutzerverwaltung.php";
?>

<div class="card">
    <div class="card-title-container">
        <h1 class="card-title">
            <i class="fas fa-key"></i>
            Passwort ändern
            <?php if (!$is_own_password): ?>
                <small style="font-size: 0.6em; font-weight: normal; color: #64748b;">
                    für <?= htmlspecialchars($user['Benutzername']) ?>
                </small>
            <?php endif; ?>
        </h1>
        <a href="<?= $zurueck_url ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Zurück
        </a>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Fehler:</strong>
            <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="benutzer_passwort_aendern.php?id=<?= $user_id ?>">
        <!-- Neues Passwort -->
        <div class="form-group">
            <label for="neues_passwort">
                Neues Passwort <span style="color: #dc2626;">*</span>
            </label>
            <input type="password" 
                   id="neues_passwort" 
                   name="neues_passwort" 
                   class="form-control"
                   minlength="6"
                   required
                   autocomplete="new-password">
            <small style="color: #64748b; font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                Mindestens 6 Zeichen. Empfohlen: 8-12 Zeichen mit Groß-/Kleinbuchstaben, Zahlen und Sonderzeichen.
            </small>
        </div>
        
        <!-- Passwort bestätigen -->
        <div class="form-group">
            <label for="passwort_bestaetigung">
                Passwort bestätigen <span style="color: #dc2626;">*</span>
            </label>
            <input type="password" 
                   id="passwort_bestaetigung" 
                   name="passwort_bestaetigung" 
                   class="form-control"
                   minlength="6"
                   required
                   autocomplete="new-password">
        </div>
        
        <!-- Tipps -->
        <div class="alert" style="background: #fef3c7; border-color: #fbbf24; color: #92400e;">
            <p style="margin: 0 0 0.5rem 0; font-weight: 600;">
                <i class="fas fa-shield-alt"></i> Tipps für ein sicheres Passwort:
            </p>
            <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
                <li>Mindestens 8-12 Zeichen (je länger, desto besser)</li>
                <li>Kombination aus Groß- und Kleinbuchstaben</li>
                <li>Zahlen und Sonderzeichen verwenden</li>
                <li>Keine persönlichen Informationen (Namen, Geburtsdaten)</li>
                <li>Nicht das gleiche Passwort wie auf anderen Webseiten</li>
            </ul>
        </div>
        
        <!-- Buttons -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-key"></i> Passwort ändern
            </button>
            <a href="<?= $zurueck_url ?>" class="btn btn-secondary">
                <i class="fas fa-times"></i> Abbrechen
            </a>
        </div>
    </form>
</div>

<style>
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #e2e8f0;
}

.form-actions .btn {
    flex: 1;
}
</style>

<?php require_once 'footer.php'; ?>
