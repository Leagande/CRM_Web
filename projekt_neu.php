<?php
// projekt_neu.php - MIT AUTO-ZUWEISUNG Zustaendig_ID!
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
include 'header.php';

// Verarbeiten des Formulars
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $projektname = $_POST['projektname'];
    $status = $_POST['status'];
    $startdatum = $_POST['startdatum'];
    $enddatum = !empty($_POST['enddatum']) ? $_POST['enddatum'] : NULL;
    $budget = !empty($_POST['budget']) ? $_POST['budget'] : 0.00;
    $notizen_projekt = $_POST['notizen']; 
    
    // AUTO-ZUWEISUNG: Zustaendig_ID = aktuelle Benutzer-ID (oder View-Mode)
    $zustaendig_id = get_effektive_benutzer_id();

    // MIT Zustaendig_ID!
    $sql = "INSERT INTO Projekte (Projektname, Status, Startdatum, Enddatum_geplant, Budget, Notizen_Projekt, Zustaendig_ID) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $projektname, 
            $status, 
            $startdatum, 
            $enddatum, 
            $budget, 
            $notizen_projekt,
            $zustaendig_id
        ]);

        $_SESSION['success_message'] = "Projekt erfolgreich erstellt!";
        header("Location: projekte.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Fehler beim Erstellen des Projekts: " . $e->getMessage();
        error_log("Fehler in projekt_neu.php: " . $e->getMessage());
    }
}
?>

<div class="container">
    <div class="card-title-container">
        <h1 class="card-title">Neues Projekt erstellen</h1>
        <a href="projekte.php" class="btn btn-secondary btn-back">
            <i class="fas fa-arrow-left"></i>
            Zurück
        </a>
    </div>

    <div class="card card-full-width">
        <form action="projekt_neu.php" method="POST" class="needs-validation" novalidate>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="projektname">Projektname *</label>
                    <input type="text" class="form-control" id="projektname" name="projektname" required>
                </div>

                <div class="form-group col-md-6">
                    <label for="status">Status *</label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="Planung" selected>Planung</option>
                        <option value="Aktiv">Aktiv</option>
                        <option value="Pausiert">Pausiert</option>
                        <option value="Abgeschlossen">Abgeschlossen</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="startdatum">Startdatum *</label>
                    <input type="date" class="form-control" id="startdatum" name="startdatum" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="enddatum">Geplantes Enddatum</label>
                    <input type="date" class="form-control" id="enddatum" name="enddatum">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="budget">Budget (optional)</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">€</span>
                        </div>
                        <input type="number" step="0.01" class="form-control" id="budget" name="budget" placeholder="z.B. 1500.00">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="notizen">Notizen (optional)</label>
                <textarea class="form-control" id="notizen" name="notizen" rows="4" placeholder="Interne Notizen zum Projekt..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Projekt speichern
            </button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
