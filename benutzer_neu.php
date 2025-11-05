<?php
// benutzer_neu.php - Neuen Benutzer anlegen
$page_title = "Neuer Benutzer";
require_once 'db_verbindung.php';
require_once 'header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eingaben sammeln
    $benutzername = trim($_POST['benutzername'] ?? '');
    $passwort = $_POST['passwort'] ?? '';
    $passwort_confirm = $_POST['passwort_confirm'] ?? '';
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
    
    if (empty($passwort)) {
        $errors[] = "Passwort ist erforderlich.";
    } elseif (strlen($passwort) < 6) {
        $errors[] = "Passwort muss mindestens 6 Zeichen lang sein.";
    } elseif ($passwort !== $passwort_confirm) {
        $errors[] = "Die Passwörter stimmen nicht überein.";
    }
    
    if (empty($vorname)) {
        $errors[] = "Vorname ist erforderlich.";
    }
    
    if (empty($nachname)) {
        $errors[] = "Nachname ist erforderlich.";
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Bitte geben Sie eine gültige E-Mail-Adresse ein.";
    }
    
    // Benutzername bereits vorhanden?
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT Benutzer_ID FROM Benutzer WHERE Benutzername = ?");
            $stmt->execute([$benutzername]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Dieser Benutzername ist bereits vergeben.";
            }
        } catch (PDOException $e) {
            error_log("Fehler bei Benutzername-Prüfung: " . $e->getMessage());
            $errors[] = "Ein Fehler ist aufgetreten.";
        }
    }
    
    // Speichern
    if (empty($errors)) {
        try {
            $passwort_hash = password_hash($passwort, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO Benutzer (Benutzername, Passwort_Hash, Vorname, Nachname, Email, Aktiv) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$benutzername, $passwort_hash, $vorname, $nachname, $email, $aktiv]);
            
            $_SESSION['success_message'] = "Benutzer '{$benutzername}' wurde erfolgreich angelegt.";
            header("Location: benutzerverwaltung.php");
            exit;
        } catch (PDOException $e) {
            error_log("Fehler beim Anlegen des Benutzers: " . $e->getMessage());
            $errors[] = "Ein Fehler ist aufgetreten beim Speichern.";
        }
    }
}
?>

<header>
    <h1><i class="fas fa-user-plus"></i> Neuen Benutzer anlegen</h1>
    <div>
        <button onclick="goBack()" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Zurück
        </button>
    </div>
</header>

<div class="card">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <strong><i class="fas fa-exclamation-triangle"></i> Fehler:</strong>
            <ul style="margin: 0.5rem 0 0 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="benutzer_neu.php">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            
            <!-- Benutzername -->
            <div class="form-group">
                <label for="benutzername" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155;">
                    Benutzername <span style="color: #dc2626;">*</span>
                </label>
                <input type="text" 
                       id="benutzername" 
                       name="benutzername" 
                       required 
                       maxlength="50"
                       pattern="[a-zA-Z0-9_-]+"
                       value="<?= htmlspecialchars($_POST['benutzername'] ?? '') ?>"
                       style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem;">
                <small style="color: #64748b; font-size: 0.85rem;">Nur Buchstaben, Zahlen, _ und - erlaubt</small>
            </div>
            
            <!-- Vorname -->
            <div class="form-group">
                <label for="vorname" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155;">
                    Vorname <span style="color: #dc2626;">*</span>
                </label>
                <input type="text" 
                       id="vorname" 
                       name="vorname" 
                       required 
                       maxlength="100"
                       value="<?= htmlspecialchars($_POST['vorname'] ?? '') ?>"
                       style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem;">
            </div>
            
            <!-- Nachname -->
            <div class="form-group">
                <label for="nachname" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155;">
                    Nachname <span style="color: #dc2626;">*</span>
                </label>
                <input type="text" 
                       id="nachname" 
                       name="nachname" 
                       required 
                       maxlength="100"
                       value="<?= htmlspecialchars($_POST['nachname'] ?? '') ?>"
                       style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem;">
            </div>
            
            <!-- E-Mail -->
            <div class="form-group">
                <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155;">
                    E-Mail <span style="color: #64748b; font-weight: normal;">(optional)</span>
                </label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       maxlength="255"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem;">
            </div>
            
            <!-- Passwort -->
            <div class="form-group">
                <label for="passwort" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155;">
                    Passwort <span style="color: #dc2626;">*</span>
                </label>
                <input type="password" 
                       id="passwort" 
                       name="passwort" 
                       required 
                       minlength="6"
                       style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem;">
                <small style="color: #64748b; font-size: 0.85rem;">Mindestens 6 Zeichen</small>
            </div>
            
            <!-- Passwort bestätigen -->
            <div class="form-group">
                <label for="passwort_confirm" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155;">
                    Passwort bestätigen <span style="color: #dc2626;">*</span>
                </label>
                <input type="password" 
                       id="passwort_confirm" 
                       name="passwort_confirm" 
                       required 
                       minlength="6"
                       style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem;">
            </div>
        </div>
        
        <!-- Status -->
        <div class="form-group" style="margin-top: 1.5rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" 
                       id="aktiv" 
                       name="aktiv" 
                       checked
                       style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                <span style="font-weight: 600; color: #334155;">Benutzer ist aktiv</span>
            </label>
            <small style="color: #64748b; font-size: 0.85rem; margin-left: 1.75rem; display: block; margin-top: 0.25rem;">
                Inaktive Benutzer können sich nicht einloggen
            </small>
        </div>
        
        <!-- Buttons -->
        <div style="display: flex; gap: 1rem; margin-top: 2rem; border-top: 2px solid #e2e8f0; padding-top: 2rem;">
            <button type="submit" class="btn" style="flex: 1;">
                <i class="fas fa-save"></i> Benutzer anlegen
            </button>
            <button type="button" onclick="goBack()" class="btn-secondary" style="flex: 1;">
                <i class="fas fa-times"></i> Abbrechen
            </button>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>
