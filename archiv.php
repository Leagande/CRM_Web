<?php
// archiv.php - EINFACH UND SICHER
error_reporting(E_ALL);
ini_set('display_errors', 1);

$page_title = "Aktivitäten-Archiv";
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
require_once 'header.php';

$where_zugriff = get_where_zugriff('a.Zustaendig_ID');

try {
    $sql = "
        SELECT a.*, f.Firmenname, p.Projektname, p.Status as ProjektStatus,
               CONCAT(ap.Vorname, ' ', ap.Nachname) as AnsprechpartnerName
        FROM Aktivitäten a
        LEFT JOIN Firmen f ON a.Firma_ID = f.Firma_ID
        LEFT JOIN Projekte p ON a.Projekt_ID = p.Projekt_ID
        LEFT JOIN Ansprechpartner ap ON a.Ansprechpartner_ID = ap.Ansprechpartner_ID
        WHERE (a.Erledigt_Status = 1 OR p.Status = 'Abgeschlossen' OR p.Status = 'Abgerechnet')
              AND {$where_zugriff}
        ORDER BY a.Datum DESC
    ";
    
    $statement = $pdo->prepare($sql);
    $statement->execute();
    $aktivitaeten = $statement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Fehler in archiv.php: " . $e->getMessage());
    echo "<div class='alert alert-error'>Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
    $aktivitaeten = [];
}
?>

<div class="card-title-container">
    <h1 class="card-title">
        <i class="fas fa-archive"></i>
        Aktivitäten-Archiv
    </h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i>
        Zurück
    </a>
</div>

<div class="card stats-card">
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-icon"><i class="fas fa-check-double"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= count($aktivitaeten) ?></div>
                <div class="stat-label">Erledigte Aktivitäten</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <?php if (empty($aktivitaeten)): ?>
        <div class="empty-state-container">
            <i class="fas fa-archive empty-state-icon"></i>
            <h2>Das Archiv ist leer</h2>
            <p>Noch keine Aktivitäten wurden erledigt.</p>
            <a href="index.php" class="btn btn-primary">Zu den Aktivitäten</a>
        </div>
    <?php else: ?>
        <h2><i class="fas fa-list"></i> <?= count($aktivitaeten) ?> <?= count($aktivitaeten) === 1 ? 'Aktivität' : 'Aktivitäten' ?></h2>
        
        <div class="activity-list">
            <?php foreach ($aktivitaeten as $aktivitaet): ?>
                <div class="activity-item erledigt">
                    <div class="activity-header">
                        <div>
                            <span class="activity-type">
                                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                                <?= htmlspecialchars($aktivitaet['Aktivitätstyp']) ?>
                            </span>
                        </div>
                        <div class="action-links icon-links">
                            <span class="activity-date"><?= date('d.m.Y, H:i', strtotime($aktivitaet['Datum'])) ?></span>
                            <a href="aktivitaet_loeschen.php?id=<?= $aktivitaet['Aktivität_ID'] ?>" 
                               onclick="return confirm('Endgültig löschen?');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </div>

                    <div class="activity-body">
                        <p class="activity-subject">
                            <strong>Betreff:</strong> <?= htmlspecialchars($aktivitaet['Betreff'] ?? 'Kein Betreff') ?>
                        </p>
                        <?php if (!empty($aktivitaet['Notiz'])): ?>
                            <p class="activity-note"><?= nl2br(htmlspecialchars($aktivitaet['Notiz'])) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="activity-footer">
                        <span><i class="fas fa-building"></i> Firma: <strong><?= htmlspecialchars($aktivitaet['Firmenname'] ?? 'N/A') ?></strong></span>
                        <span><i class="fas fa-folder-open"></i> Projekt: <strong><?= htmlspecialchars($aktivitaet['Projektname'] ?? 'N/A') ?></strong></span>
                        <span><i class="fas fa-user"></i> Kontakt: <strong><?= htmlspecialchars($aktivitaet['AnsprechpartnerName'] ?? 'N/A') ?></strong></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
