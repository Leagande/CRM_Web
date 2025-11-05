<?php
// benutzer_bearbeiten.php - Benutzer bearbeiten - MIT FUNKTIONIERENDEN BUTTONS!
$page_title = "Benutzer bearbeiten";
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
require_once 'header.php';

// Nur Admin darf hier rein
nur_admin_zugriff('benutzerverwaltung.php');

$errors = [];
$user_id = intval($_GET['id'] ?? 0);

if ($user_id <= 0) {
    $_SESSION['error_message'] = "Ungültige Benutzer-ID.";
    header("Location: benutzerverwaltung.php");
    exit;
}

// Benutzer laden
try {
    $stmt = $pdo->prepare("SELECT * FROM Benutzer WHERE Benutzer_ID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error_message'] = "Benutzer nicht gefunden.";
        header("Location: benutzerverwaltung.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Fehler beim Laden des Benutzers: " . $e->getMessage());
    $_SESSION['error_message'] = "Fehler beim Laden des Benutzers.";
    header("Location: benutzerverwaltung.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eingaben sammeln
    $benutzername = trim($_POST['benutzername'] ?? '');
    $vorname = trim($_POST['vorname'] ?? '');
    $nachname = trim($_POST['nachname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $aktiv = isset($_POST['aktiv']) ? 1 : 0;
    
    // Validierung
    if (empty($benutzername)) {
        $errors[] = "Benutzername ist erforderlich.";
    } elseif (strlen($benutzername) < 3) {
        $errors[] = "Benutzername muss mindestens 3 Zeichen lang sein.";
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $benutzername)) {
        $errors[] = "Benutzername darf nur Buchstaben, Zahlen, _ und - enthalten.";
    }
    
    // Prüfen ob Benutzername bereits vergeben (außer bei sich selbst)
    if (empty($errors)) {
        try {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM Benutzer WHERE Benutzername = ? AND Benutzer_ID != ?");
            $stmt_check->execute([$benutzername, $user_id]);
            if ($stmt_check->fetchColumn() > 0) {
                $errors[] = "Benutzername bereits vergeben.";
            }
        } catch (PDOException $e) {
            error_log("Fehler bei Benutzername-Prüfung: " . $e->getMessage());
            $errors[] = "Datenbankfehler bei der Validierung.";
        }
    }
    
    // Email validierung (optional)
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Ungültige E-Mail-Adresse.";
    }
    
    // Wenn keine Fehler, dann speichern
    if (empty($errors)) {
        try {
            $stmt_update = $pdo->prepare("
                UPDATE Benutzer 
                SET Benutzername = ?,
                    Vorname = ?,
                    Nachname = ?,
                    Email = ?,
                    Aktiv = ?
                WHERE Benutzer_ID = ?
            ");
            
            $stmt_update->execute([
                $benutzername,
                $vorname,
                $nachname,
                $email,
                $aktiv,
                $user_id
            ]);
            
            $_SESSION['success_message'] = "Benutzer erfolgreich aktualisiert!";
            header("Location: benutzerverwaltung.php");
            exit;
            
        } catch (PDOException $e) {
            error_log("Fehler beim Update des Benutzers: " . $e->getMessage());
            $errors[] = "Datenbankfehler beim Speichern.";
        }
    }
}
?>

<div class="card">
    <div class="card-title-container">
        <h1 class="card-title">
            <i class="fas fa-user-edit"></i>
            Benutzer bearbeiten
        </h1>
        <a href="benutzerverwaltung.php" class="btn btn-secondary">
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
    
    <form method="POST" action="benutzer_bearbeiten.php?id=<?= $user_id ?>">
        <!-- Benutzername -->
        <div class="form-group">
            <label for="benutzername">
                Benutzername <span style="color: #dc2626;">*</span>
            </label>
            <input type="text" 
                   id="benutzername" 
                   name="benutzername" 
                   class="form-control"
                   maxlength="50"
                   value="<?= htmlspecialchars($_POST['benutzername'] ?? $user['Benutzername']) ?>"
                   required>
            <small style="color: #64748b; font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                Nur Buchstaben, Zahlen, _ und - erlaubt. Mindestens 3 Zeichen.
            </small>
        </div>
        
        <!-- Vorname & Nachname -->
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="vorname">Vorname</label>
                <input type="text" 
                       id="vorname" 
                       name="vorname" 
                       class="form-control"
                       maxlength="100"
                       value="<?= htmlspecialchars($_POST['vorname'] ?? $user['Vorname']) ?>">
            </div>
            
            <div class="form-group col-md-6">
                <label for="nachname">Nachname</label>
                <input type="text" 
                       id="nachname" 
                       name="nachname" 
                       class="form-control"
                       maxlength="100"
                       value="<?= htmlspecialchars($_POST['nachname'] ?? $user['Nachname']) ?>">
            </div>
        </div>
        
        <!-- Email -->
        <div class="form-group">
            <label for="email">
                E-Mail <span style="color: #64748b; font-weight: normal;">(optional)</span>
            </label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   class="form-control"
                   maxlength="255"
                   value="<?= htmlspecialchars($_POST['email'] ?? $user['Email']) ?>">
        </div>
        
        <!-- Status -->
        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" 
                       id="aktiv" 
                       name="aktiv" 
                       <?= ($user['Aktiv'] == 1) ? 'checked' : '' ?>
                       <?= ($user_id == $_SESSION['benutzer_id']) ? 'disabled' : '' ?>
                       style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                <span style="font-weight: 600;">Benutzer ist aktiv</span>
            </label>
            <small style="color: #64748b; font-size: 0.85rem; margin-left: 1.75rem; display: block; margin-top: 0.25rem;">
                <?= ($user_id == $_SESSION['benutzer_id']) ? 'Sie können sich nicht selbst deaktivieren' : 'Inaktive Benutzer können sich nicht einloggen' ?>
            </small>
        </div>
        
        <!-- Info-Box -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <strong>Hinweis:</strong> Um das Passwort zu ändern, nutzen Sie bitte die 
            <a href="benutzer_passwort_aendern.php?id=<?= $user_id ?>" style="text-decoration: underline;">Passwort ändern</a> Funktion.
        </div>
        
        <!-- Letzte Infos -->
        <div class="alert" style="background: #f8f9fa; border-color: #e2e8f0;">
            <strong>Erstellt am:</strong> <?= date('d.m.Y H:i', strtotime($user['Erstellt_am'])) ?> Uhr<br>
            <strong>Letzter Login:</strong> <?= $user['Letzter_Login'] ? date('d.m.Y H:i', strtotime($user['Letzter_Login'])) . ' Uhr' : 'Noch nie' ?>
        </div>
        
        <!-- Buttons -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Änderungen speichern
            </button>
            <a href="benutzerverwaltung.php" class="btn btn-secondary">
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

.alert-info {
    background: #dbeafe;
    border: 1px solid #93c5fd;
    color: #1e40af;
}
</style>

<?php require_once 'footer.php'; ?>
