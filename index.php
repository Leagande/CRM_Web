<?php
// index.php - AktivitÃ¤ten-Dashboard - ULTRA-EINFACH!
error_reporting(E_ALL);
ini_set('display_errors', 1);

$page_title = "Dashboard";
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
require_once 'header.php';

// Sicherer WHERE-Filter (OHNE "a." da Query in Zeile 43 keinen Alias hat)
$where_zugriff = get_where_zugriff('Zustaendig_ID');

// Suche & Sortierung
$suche_aktivitaet = trim($_GET['suche_aktivitaet'] ?? '');
$sort_by = $_GET['sort'] ?? 'datum_desc';

$order_clause = 'ORDER BY ';
switch ($sort_by) {
    case 'prio_desc':
        $order_clause .= " CASE a.Prioritaet WHEN 'Dringend' THEN 1 WHEN 'Hoch' THEN 2 WHEN 'Normal' THEN 3 ELSE 4 END ASC";
        break;
    case 'faelligkeit_asc':
        $order_clause .= " CASE WHEN a.Faelligkeitsdatum IS NULL THEN 1 ELSE 0 END, a.Faelligkeitsdatum ASC";
        break;
    case 'datum_asc':
        $order_clause .= " a.Datum ASC";
        break;
    default:
        $order_clause .= " a.Datum DESC";
        break;
}

try {
    // KPIs
    $sql_umsatz = "SELECT SUM(Budget) FROM Projekte WHERE Status != 'Abgeschlossen' AND " . get_where_zugriff('Zustaendig_ID');
    $umsatz_offen = $pdo->query($sql_umsatz)->fetchColumn() ?? 0;
    
    $sql_projekte = "SELECT COUNT(*) FROM Projekte WHERE Status != 'Abgeschlossen' AND " . get_where_zugriff('Zustaendig_ID');
    $anzahl_offene_projekte = $pdo->query($sql_projekte)->fetchColumn();
    
    $heute = date('Y-m-d');
    $sql_faellig = "SELECT COUNT(*) FROM AktivitÃ¤ten WHERE Erledigt_Status = 0 AND Faelligkeitsdatum <= ? AND {$where_zugriff}";
    $stmt = $pdo->prepare($sql_faellig);
    $stmt->execute([$heute]);
    $anzahl_heute_faellig = $stmt->fetchColumn();

    // AktivitÃ¤ten
    $sql_activities = "
        SELECT a.*, f.Firmenname, p.Projektname, CONCAT(ap.Vorname, ' ', ap.Nachname) as AnsprechpartnerName
        FROM AktivitÃ¤ten a
        LEFT JOIN Firmen f ON a.Firma_ID = f.Firma_ID
        LEFT JOIN Projekte p ON a.Projekt_ID = p.Projekt_ID
        LEFT JOIN Ansprechpartner ap ON a.Ansprechpartner_ID = ap.Ansprechpartner_ID
        WHERE a.Erledigt_Status = 0 AND {$where_zugriff}
    ";
    
    $params = [];
    if (!empty($suche_aktivitaet)) {
        $sql_activities .= " AND (a.Betreff LIKE ? OR f.Firmenname LIKE ?)";
        $searchTerm = '%' . $suche_aktivitaet . '%';
        $params = [$searchTerm, $searchTerm];
    }
    
    $sql_activities .= " " . $order_clause;
    $statement = $pdo->prepare($sql_activities);
    $statement->execute($params);
    $aktivitaeten = $statement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Fehler in index.php: " . $e->getMessage());
    echo "<div class='alert alert-error'>Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
    $umsatz_offen = 0;
    $anzahl_offene_projekte = 0;
    $anzahl_heute_faellig = 0;
    $aktivitaeten = [];
}
?>

<header>
    <h1>Dashboard (Alle AktivitÃ¤ten)</h1>
    <div>
        <a href="aktivitaet_neu.php" class="btn"><i class="fas fa-plus"></i> Neue AktivitÃ¤t</a>
        <a href="archiv.php" class="btn-secondary"><i class="fas fa-archive"></i> Archiv</a>
    </div>
</header>

<!-- KPI Kacheln -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon-budget"><i class="fas fa-euro-sign"></i></div>
        <div class="kpi-content">
            <div class="kpi-value">â‚¬ <?= number_format($umsatz_offen, 0, ',', '.') ?></div>
            <div class="kpi-label">Umsatz</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon-projects"><i class="fas fa-tasks"></i></div>
        <div class="kpi-content">
            <div class="kpi-value"><?= $anzahl_offene_projekte ?></div>
            <div class="kpi-label">Projekte</div>
        </div>
    </div>
    <div class="kpi-card kpi-due-today <?= ($anzahl_heute_faellig > 0) ? 'highlight' : '' ?>">
        <div class="kpi-icon kpi-icon-tasks"><i class="fas fa-calendar-check"></i></div>
        <div class="kpi-content">
            <div class="kpi-value"><?= $anzahl_heute_faellig ?></div>
            <div class="kpi-label">Heute fÃ¤llig</div>
        </div>
    </div>
</div>

<!-- Suche -->
<div class="card search-card">
    <h3 style="margin-bottom: 1rem; color: var(--text-color-heading); font-size: 1.1rem;">
        <i class="fas fa-search" style="color: var(--primary-color);"></i> AktivitÃ¤ten durchsuchen
    </h3>
    <form action="index.php" method="get" class="search-form">
        <input type="search" name="suche_aktivitaet" placeholder="Suchen..." value="<?= htmlspecialchars($suche_aktivitaet) ?>">
        <button type="submit" class="btn"><i class="fas fa-search"></i> Suchen</button>
    </form>
</div>

<!-- Sortierung -->
<div class="sort-options-modern">
    <span class="sort-label"><i class="fas fa-sort-amount-down"></i> Sortieren:</span>
    <div class="sort-buttons">
        <a href="index.php?sort=datum_desc" class="sort-btn <?= $sort_by == 'datum_desc' ? 'active' : '' ?>">
            <i class="fas fa-clock"></i> Neueste
        </a>
        <a href="index.php?sort=datum_asc" class="sort-btn <?= $sort_by == 'datum_asc' ? 'active' : '' ?>">
            <i class="fas fa-history"></i> Ã„lteste
        </a>
        <a href="index.php?sort=prio_desc" class="sort-btn <?= $sort_by == 'prio_desc' ? 'active' : '' ?>">
            <i class="fas fa-exclamation-circle"></i> PrioritÃ¤t
        </a>
        <a href="index.php?sort=faelligkeit_asc" class="sort-btn <?= $sort_by == 'faelligkeit_asc' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i> FÃ¤lligkeit
        </a>
    </div>
    <a href="aktivitaet_neu.php" class="btn btn-primary" style="margin-left: auto;">
        <i class="fas fa-plus"></i> Neue AktivitÃ¤t
    </a>
</div>

<div class="card">
    <h2>Offene AktivitÃ¤ten</h2>
    <div class="activity-list">
        <?php if (empty($aktivitaeten)): ?>
            <div class="empty-state-container">
                <i class="fas fa-check-double empty-state-icon"></i>
                <h2>Alles erledigt!</h2>
                <p>Aktuell gibt es keine offenen AktivitÃ¤ten.</p>
                <a href="aktivitaet_neu.php" class="btn">Neue AktivitÃ¤t erstellen</a>
            </div>
        <?php else: ?>
            <?php foreach ($aktivitaeten as $aktivitaet): ?>
                <div class="activity-item">
                    <div class="activity-header">
                        <div>
                            <span class="activity-type"><?= htmlspecialchars($aktivitaet['AktivitÃ¤tstyp']) ?></span>
                            <?php if (($aktivitaet['Prioritaet'] ?? 'Normal') !== 'Normal'): ?>
                                <span class="priority-badge priority-<?= strtolower($aktivitaet['Prioritaet']) ?>">
                                    <?= htmlspecialchars($aktivitaet['Prioritaet']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="action-links icon-links">
                            <span class="activity-date"><?= date('d.m.Y, H:i', strtotime($aktivitaet['Datum'])) ?></span>
                            <a href="aktivitaet_erledigt.php?id=<?= $aktivitaet['AktivitÃ¤t_ID'] ?>" title="Erledigt"><i class="fas fa-check-circle"></i></a>
                            <a href="aktivitaet_bearbeiten.php?id=<?= $aktivitaet['AktivitÃ¤t_ID'] ?>" title="Bearbeiten"><i class="fas fa-edit"></i></a>
                            <a href="aktivitaet_loeschen.php?id=<?= $aktivitaet['AktivitÃ¤t_ID'] ?>" title="LÃ¶schen" onclick="return confirm('Sicher?');"><i class="fas fa-trash-alt"></i></a>
                        </div>
                    </div>
                    <div class="activity-body">
                        <p class="activity-subject"><strong>Betreff:</strong> <?= htmlspecialchars($aktivitaet['Betreff'] ?? 'Kein Betreff') ?></p>
                        <?php if (!empty($aktivitaet['Notiz'])): ?>
                            <p class="activity-note"><?= nl2br(htmlspecialchars($aktivitaet['Notiz'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($aktivitaet['Faelligkeitsdatum'])): ?>
                        <div class="activity-due-date">
                            <strong>FÃ¤llig bis:</strong> <?= date('d.m.Y', strtotime($aktivitaet['Faelligkeitsdatum'])) ?>
                        </div>
                    <?php endif; ?>
                    <div class="activity-footer">
                        <span>Firma: <strong><?= htmlspecialchars($aktivitaet['Firmenname'] ?? 'N/A') ?></strong></span>
                        <span>Projekt: <strong><?= htmlspecialchars($aktivitaet['Projektname'] ?? 'N/A') ?></strong></span>
                        <span>Kontakt: <strong><?= htmlspecialchars($aktivitaet['AnsprechpartnerName'] ?? 'N/A') ?></strong></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
