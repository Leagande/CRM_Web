<?php
// projekt_bearbeiten.php - MIT ZUGRIFFSKONTROLLE!
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
include 'header.php';

// 1. ID aus der URL holen
$projekt_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$projekt_id) {
    $_SESSION['error_message'] = "Ungültige Projekt-ID.";
    header("Location: projekte.php");
    exit();
}

// 3. Aktuelle Daten laden
try {
    $stmt = $pdo->prepare("SELECT * FROM Projekte WHERE Projekt_ID = ?");
    $stmt->execute([$projekt_id]);
    $projekt = $stmt->fetch();

    if (!$projekt) {
        $_SESSION['error_message'] = "Projekt nicht gefunden.";
        header("Location: projekte.php");
        exit();
    }
    
    // ZUGRIFFSKONTROLLE!
    pruefe_zugriff_oder_redirect($projekt['Zustaendig_ID'], 'projekte.php');
    
} catch (PDOException $e) {
    die("Fehler beim Laden des Projekts: " . $e->getMessage());
}

// 2. Formular verarbeiten
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $projektname = $_POST['projektname'];
    $status = $_POST['status'];
    $startdatum = $_POST['startdatum'];
    $enddatum = !empty($_POST['enddatum']) ? $_POST['enddatum'] : NULL;
    $budget = !empty($_POST['budget']) ? $_POST['budget'] : NULL;
    $notizen = $_POST['notizen'];

    $sql = "UPDATE Projekte SET 
                Projektname = ?, 
                Status = ?, 
                Startdatum = ?, 
                Enddatum_geplant = ?, 
                Budget = ?, 
                Notizen_Projekt = ? 
            WHERE Projekt_ID = ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $projektname, 
            $status, 
            $startdatum, 
            $enddatum, 
            $budget, 
            $notizen,
            $projekt_id
        ]);

        $_SESSION['success_message'] = "Projekt erfolgreich aktualisiert!";
        header("Location: projekt_details.php?id=" . $projekt_id);
        exit();

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Fehler beim Aktualisieren: " . $e->getMessage();
    }
}

?>

<div class="container">
    <div class="card-title-container">
        <h1 class="card-title">Projekt bearbeiten: <?php echo htmlspecialchars($projekt['Projektname']); ?></h1>
        <a href="projekt_details.php?id=<?php echo $projekt_id; ?>" class="btn btn-secondary btn-back">
            <i class="fas fa-arrow-left"></i>
            Zurück
        </a>
    </div>

    <div class="card card-full-width">
        <form action="projekt_bearbeiten.php?id=<?php echo $projekt_id; ?>" method="POST">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="projektname">Projektname *</label>
                    <input type="text" class="form-control" id="projektname" name="projektname" 
                           value="<?php echo htmlspecialchars($projekt['Projektname']); ?>" required>
                </div>

                <div class="form-group col-md-6">
                    <label for="status">Status *</label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="Planung" <?php echo ($projekt['Status'] == 'Planung') ? 'selected' : ''; ?>>Planung</option>
                        <option value="Aktiv" <?php echo ($projekt['Status'] == 'Aktiv') ? 'selected' : ''; ?>>Aktiv</option>
                        <option value="Pausiert" <?php echo ($projekt['Status'] == 'Pausiert') ? 'selected' : ''; ?>>Pausiert</option>
                        <option value="Abgeschlossen" <?php echo ($projekt['Status'] == 'Abgeschlossen') ? 'selected' : ''; ?>>Abgeschlossen</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="startdatum">Startdatum *</label>
                    <input type="date" class="form-control" id="startdatum" name="startdatum" 
                           value="<?php echo $projekt['Startdatum']; ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="enddatum">Geplantes Enddatum</label>
                    <input type="date" class="form-control" id="enddatum" name="enddatum" 
                           value="<?php echo $projekt['Enddatum_geplant'] ?? ''; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="budget">Budget (optional)</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">€</span>
                        </div>
                        <input type="number" step="0.01" class="form-control" id="budget" name="budget" 
                               value="<?php echo $projekt['Budget'] ?? ''; ?>" placeholder="z.B. 1500.00">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="notizen">Notizen (optional)</label>
                <textarea class="form-control" id="notizen" name="notizen" rows="4" 
                          placeholder="Interne Notizen zum Projekt..."><?php echo htmlspecialchars($projekt['Notizen_Projekt'] ?? ''); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Änderungen speichern
                </button>
                <a href="projekt_details.php?id=<?php echo $projekt_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Abbrechen
                </a>
            </div>
        </form>
    </div>
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

<?php include 'footer.php'; ?>
