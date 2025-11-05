<?php
// projekt_details.php - MIT ZUGRIFFSKONTROLLE!
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
include 'header.php';

// 1. Projekt-ID aus der URL holen
$projekt_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$projekt_id) {
    $_SESSION['error_message'] = "Ungültige Projekt-ID.";
    header("Location: projekte.php");
    exit();
}

// 2. Projektdetails abrufen
try {
    $stmt_projekt = $pdo->prepare("SELECT * FROM Projekte WHERE Projekt_ID = ?");
    $stmt_projekt->execute([$projekt_id]);
    $projekt = $stmt_projekt->fetch();

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

// 3. Formular-Logik für neue Aktivität
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['neue_aktivitaet'])) {
    $typ = $_POST['typ'];
    $firma_id = $_POST['firma_id'];
    $status = $_POST['status'];
    $notiz = $_POST['notiz']; 
    $umsatz = !empty($_POST['umsatz']) ? $_POST['umsatz'] : 0.00;
    $faellig_am = !empty($_POST['faellig_am']) ? $_POST['faellig_am'] : NULL;
    
    // Zustaendig_ID für Aktivität
    $zustaendig_id = get_effektive_benutzer_id();

    if (empty($firma_id)) {
        $_SESSION['error_message'] = "Bitte wählen Sie eine Firma aus.";
    } else {
        try {
            // Aktivität speichern MIT Zustaendig_ID
            $sql_aktivitaet = "INSERT INTO Aktivitäten (Projekt_ID, Firma_ID, Aktivitätstyp, Status, Notiz, umsatz, Faelligkeitsdatum, Zustaendig_ID) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql_aktivitaet);
            $stmt->execute([$projekt_id, $firma_id, $typ, $status, $notiz, $umsatz, $faellig_am, $zustaendig_id]);
            
            // Automatische Zuordnung Projekt-Firma
            $sql_zuordnung = "INSERT IGNORE INTO Projekt_Firmen_Zuordnung (Projekt_ID, Firma_ID) VALUES (?, ?)";
            $stmt_zuordnung = $pdo->prepare($sql_zuordnung);
            $stmt_zuordnung->execute([$projekt_id, $firma_id]);

            $_SESSION['success_message'] = "Aktivität erfolgreich gespeichert.";

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Fehler beim Speichern: " . $e->getMessage();
        }
    }
    
    header("Location: projekt_details.php?id=" . $projekt_id);
    exit();
}

// 4. Daten laden
try {
    // Aktivitäten
    $sql_aktivitaeten = "SELECT a.*, f.Firmenname as firmenname 
                         FROM Aktivitäten a
                         LEFT JOIN Firmen f ON a.Firma_ID = f.Firma_ID
                         WHERE a.Projekt_ID = ?
                         ORDER BY a.Datum DESC, a.Faelligkeitsdatum ASC";
    $stmt_aktivitaeten = $pdo->prepare($sql_aktivitaeten);
    $stmt_aktivitaeten->execute([$projekt_id]);
    $aktivitaeten = $stmt_aktivitaeten->fetchAll();
    
    // KPIs
    $stmt_kpi = $pdo->prepare("SELECT 
                                SUM(umsatz) as gesamtumsatz,
                                COUNT(*) as anzahl_aktivitaeten
                              FROM Aktivitäten 
                              WHERE Projekt_ID = ?");
    $stmt_kpi->execute([$projekt_id]);
    $kpi = $stmt_kpi->fetch();
    
    // Zugeordnete Firmen
    $sql_firmen = "SELECT f.* 
                   FROM Firmen f
                   INNER JOIN Projekt_Firmen_Zuordnung pz ON f.Firma_ID = pz.Firma_ID
                   WHERE pz.Projekt_ID = ?";
    $stmt_firmen = $pdo->prepare($sql_firmen);
    $stmt_firmen->execute([$projekt_id]);
    $firmen = $stmt_firmen->fetchAll();

} catch (PDOException $e) {
    die("Fehler beim Laden der Daten: " . $e->getMessage());
}

$page_title = htmlspecialchars($projekt['Projektname']);
?>

<div class="container">
    <div class="card-title-container">
        <h1 class="card-title"><?php echo htmlspecialchars($projekt['Projektname']); ?></h1>
        
        <div class="action-buttons">
            <a href="projekte.php" class="btn btn-secondary btn-back">
                <i class="fas fa-arrow-left"></i>
                Zurück
            </a>
            <a href="projekt_bearbeiten.php?id=<?php echo $projekt_id; ?>" class="btn btn-primary">
                <i class="fas fa-pencil-alt"></i>
                Bearbeiten
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1.5rem 0;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-euro-sign"></i></div>
            <div style="font-size: 2rem; font-weight: bold;"><?= number_format($kpi['gesamtumsatz'], 0, ',', '.') ?> €</div>
            <div style="opacity: 0.9;">Gesamtumsatz</div>
        </div>
        
        <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 1.5rem; border-radius: 12px;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-tasks"></i></div>
            <div style="font-size: 2rem; font-weight: bold;"><?= $kpi['anzahl_aktivitaeten'] ?></div>
            <div style="opacity: 0.9;">Aktivitäten</div>
        </div>
        
        <div style="background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%); color: white; padding: 1.5rem; border-radius: 12px;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-building"></i></div>
            <div style="font-size: 2rem; font-weight: bold;"><?= count($firmen) ?></div>
            <div style="opacity: 0.9;">Firmen</div>
        </div>
    </div>

    <!-- Projektdetails -->
    <div class="card">
        <h2 class="card-subtitle">Projektdetails</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
            <div>
                <strong style="color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Status</strong>
                <div style="margin-top: 0.5rem;">
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $projekt['Status'])); ?>">
                        <?php echo htmlspecialchars($projekt['Status']); ?>
                    </span>
                </div>
            </div>
            
            <div>
                <strong style="color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Startdatum</strong>
                <div style="margin-top: 0.5rem; font-weight: 600;">
                    <?php echo $projekt['Startdatum'] ? date('d.m.Y', strtotime($projekt['Startdatum'])) : 'Nicht gesetzt'; ?>
                </div>
            </div>
            
            <div>
                <strong style="color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Budget</strong>
                <div style="margin-top: 0.5rem; font-weight: 600;">
                    <?php echo $projekt['Budget'] ? number_format($projekt['Budget'], 2, ',', '.') . ' €' : 'Nicht gesetzt'; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($projekt['Notizen_Projekt'])): ?>
            <div style="margin-top: 1.5rem;">
                <strong style="color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Notizen</strong>
                <div style="margin-top: 0.5rem; background: #f8f9fa; padding: 1rem; border-radius: 8px; white-space: pre-wrap;">
                    <?php echo htmlspecialchars($projekt['Notizen_Projekt']); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Zugeordnete Firmen -->
    <div class="card">
        <h2 class="card-subtitle">
            <i class="fas fa-building"></i>
            Zugeordnete Firmen (<?= count($firmen) ?>)
        </h2>
        
        <?php if (empty($firmen)): ?>
            <p style="color: #64748b; padding: 1rem 0;">Noch keine Firmen zugeordnet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Firmenname</th>
                            <th>Ort</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($firmen as $firma): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($firma['Firmenname']) ?></strong></td>
                                <td><?= htmlspecialchars($firma['PLZ']) ?> <?= htmlspecialchars($firma['Ort']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $firma['Status'])) ?>">
                                        <?= htmlspecialchars($firma['Status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="firma_details.php?id=<?= $firma['Firma_ID'] ?>" class="btn btn-sm btn-icon" title="Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Aktivitäten -->
    <div class="card">
        <h2 class="card-subtitle">
            <i class="fas fa-list"></i>
            Aktivitäten (<?= count($aktivitaeten) ?>)
        </h2>
        
        <?php if (empty($aktivitaeten)): ?>
            <p style="color: #64748b; padding: 1rem 0;">Noch keine Aktivitäten erfasst.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Typ</th>
                            <th>Firma</th>
                            <th>Notiz</th>
                            <th>Umsatz</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aktivitaeten as $akt): ?>
                            <tr>
                                <td><?= date('d.m.Y', strtotime($akt['Datum'])) ?></td>
                                <td><?= htmlspecialchars($akt['Aktivitätstyp']) ?></td>
                                <td><?= htmlspecialchars($akt['firmenname'] ?? '-') ?></td>
                                <td><?= htmlspecialchars(substr($akt['Notiz'] ?? '', 0, 50)) ?><?= strlen($akt['Notiz'] ?? '') > 50 ? '...' : '' ?></td>
                                <td><?= $akt['umsatz'] ? number_format($akt['umsatz'], 2, ',', '.') . ' €' : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
