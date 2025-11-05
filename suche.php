<?php
// suche.php - GEFIXT!
$page_title = "Globale Suche";
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
require_once 'header.php';

// Suchbegriff aus URL (jetzt 'query' statt 'q')
$suchbegriff = trim($_GET['query'] ?? '');

$firmen_results = [];
$projekte_results = [];
$ansprechpartner_results = [];
$total_results = 0;

if (!empty($suchbegriff)) {
    $page_title = "Suche nach: " . htmlspecialchars($suchbegriff);
    $search_param = '%' . $suchbegriff . '%';

    try {
        $benutzer_id = get_aktuelle_benutzer_id();
        $is_admin = ist_admin();

        // Firmen durchsuchen
        $sql_firmen = "SELECT * FROM Firmen 
                       WHERE (Firmenname LIKE :q1
                       OR Ort LIKE :q2
                       OR PLZ LIKE :q3)";
        if (!$is_admin) {
            $sql_firmen .= " AND Erstellt_von = :benutzer_id";
        }
        $stmt_firmen = $pdo->prepare($sql_firmen);
        $params_firmen = [
            ':q1' => $search_param,
            ':q2' => $search_param,
            ':q3' => $search_param
        ];
        if (!$is_admin) {
            $params_firmen[':benutzer_id'] = $benutzer_id;
        }
        $stmt_firmen->execute($params_firmen);
        $firmen_results = $stmt_firmen->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($firmen_results);

        // Projekte durchsuchen
        $sql_projekte = "SELECT * FROM Projekte 
                         WHERE (Projektname LIKE :q1
                         OR Status LIKE :q2)";
        if (!$is_admin) {
            $sql_projekte .= " AND Zustaendig_ID = :benutzer_id";
        }
        $stmt_projekte = $pdo->prepare($sql_projekte);
        $params_projekte = [
            ':q1' => $search_param,
            ':q2' => $search_param
        ];
        if (!$is_admin) {
            $params_projekte[':benutzer_id'] = $benutzer_id;
        }
        $stmt_projekte->execute($params_projekte);
        $projekte_results = $stmt_projekte->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($projekte_results);

        // Ansprechpartner durchsuchen
        $sql_kontakte = "SELECT a.*, f.Firmenname 
                         FROM Ansprechpartner a
                         LEFT JOIN Firmen f ON a.Firma_ID = f.Firma_ID
                         WHERE (a.Vorname LIKE :q1
                         OR a.Nachname LIKE :q2
                         OR a.Email LIKE :q3
                         OR CONCAT(a.Vorname, ' ', a.Nachname) LIKE :q4)";
        if (!$is_admin) {
            $sql_kontakte .= " AND f.Erstellt_von = :benutzer_id";
        }
        $stmt_kontakte = $pdo->prepare($sql_kontakte);
        $params_kontakte = [
            ':q1' => $search_param,
            ':q2' => $search_param,
            ':q3' => $search_param,
            ':q4' => $search_param
        ];
        if (!$is_admin) {
            $params_kontakte[':benutzer_id'] = $benutzer_id;
        }
        $stmt_kontakte->execute($params_kontakte);
        $ansprechpartner_results = $stmt_kontakte->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($ansprechpartner_results);

    } catch (PDOException $e) {
        error_log("Fehler bei der Suche: " . $e->getMessage());
        $_SESSION['error_message'] = "Fehler bei der Suche. Bitte versuchen Sie es erneut.";
    }
}
?>

<div class="container">
    <div class="card-title-container">
        <h1 class="card-title">
            <i class="fas fa-search"></i>
            <?= empty($suchbegriff) ? 'Suche' : 'Suchergebnisse' ?>
        </h1>
        <a href="start.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Zurück
        </a>
    </div>

    <?php if (empty($suchbegriff)): ?>
        <div class="card">
            <div class="empty-state-container">
                <i class="fas fa-search empty-state-icon"></i>
                <h2>Starten Sie eine Suche</h2>
                <p>Geben Sie einen Suchbegriff in die Suchleiste oben ein, um Firmen, Projekte und Kontakte zu finden.</p>
                <p style="color: #64748b; margin-top: 1rem;">
                    <i class="fas fa-lightbulb"></i>
                    Sie sehen nur Ihre eigenen Daten<?php if (ist_admin()) echo " (als Admin sehen Sie alle Daten)"; ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        
        <div class="card" style="margin-bottom: 1.5rem; padding: 1.5rem; background: #f0f9ff; border: 2px solid #3b82f6;">
            <h2 style="margin: 0 0 0.5rem 0; color: #1e40af;">
                <i class="fas fa-check-circle"></i>
                <strong><?= $total_results ?></strong> Treffer für "<strong><?= htmlspecialchars($suchbegriff) ?></strong>"
            </h2>
            <?php if (!ist_admin()): ?>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i>
                    Sie sehen nur Ihre eigenen Daten
                </p>
            <?php endif; ?>
        </div>

        <!-- FIRMEN -->
        <div class="card">
            <h2 class="card-subtitle">
                <i class="fas fa-building"></i>
                Firmen (<?= count($firmen_results) ?>)
            </h2>
            <?php if (empty($firmen_results)): ?>
                <p style="color: #64748b; padding: 1rem 0;"><i class="fas fa-inbox"></i> Keine Firmen gefunden.</p>
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
                            <?php foreach ($firmen_results as $firma): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($firma['Firmenname']) ?></strong></td>
                                    <td><?= htmlspecialchars($firma['PLZ']) ?> <?= htmlspecialchars($firma['Ort'] ?? '') ?></td>
                                    <td><span class="status-badge status-<?= strtolower(str_replace(' ', '-', $firma['Status'])) ?>"><?= htmlspecialchars($firma['Status']) ?></span></td>
                                    <td>
                                        <div class="action-buttons-compact">
                                            <a href="firma_details.php?id=<?= $firma['Firma_ID'] ?>" class="btn btn-sm btn-icon" title="Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="projekte.php?firma_id=<?= $firma['Firma_ID'] ?>" class="btn btn-sm btn-icon" title="Projekte">
                                                <i class="fas fa-project-diagram"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- PROJEKTE -->
        <div class="card">
            <h2 class="card-subtitle">
                <i class="fas fa-project-diagram"></i>
                Projekte (<?= count($projekte_results) ?>)
            </h2>
            <?php if (empty($projekte_results)): ?>
                <p style="color: #64748b; padding: 1rem 0;"><i class="fas fa-inbox"></i> Keine Projekte gefunden.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Projektname</th>
                                <th>Status</th>
                                <th>Startdatum</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projekte_results as $projekt): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($projekt['Projektname']) ?></strong></td>
                                    <td><span class="status-badge status-<?= strtolower(str_replace(' ', '-', $projekt['Status'])) ?>"><?= htmlspecialchars($projekt['Status']) ?></span></td>
                                    <td><?= $projekt['Startdatum'] ? htmlspecialchars(date('d.m.Y', strtotime($projekt['Startdatum']))) : '' ?></td>
                                    <td>
                                        <div class="action-buttons-compact">
                                            <a href="projekt_details.php?id=<?= $projekt['Projekt_ID'] ?>" class="btn btn-sm btn-icon" title="Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- ANSPRECHPARTNER -->
        <div class="card">
            <h2 class="card-subtitle">
                <i class="fas fa-users"></i>
                Ansprechpartner (<?= count($ansprechpartner_results) ?>)
            </h2>
            <?php if (empty($ansprechpartner_results)): ?>
                <p style="color: #64748b; padding: 1rem 0;"><i class="fas fa-inbox"></i> Keine Ansprechpartner gefunden.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Email</th>
                                <th>Zugehörige Firma</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ansprechpartner_results as $kontakt): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars(trim($kontakt['Vorname'] . ' ' . $kontakt['Nachname'])) ?></strong></td>
                                    <td><?= htmlspecialchars($kontakt['Position'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($kontakt['Email'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($kontakt['Firmenname'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
