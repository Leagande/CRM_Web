<?php
// projekte.php - KOMPAKT mit Sortierung, Filter, wie firmen.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
include 'header.php';

// Sicherer WHERE-Filter
$where_zugriff = get_where_zugriff('Zustaendig_ID');

// SORTIERUNG
$sortierung = isset($_GET['sort']) ? $_GET['sort'] : 'Projektname';
$sortierung_whitelist = ['Projektname', 'Status', 'Startdatum', 'Budget'];
if (!in_array($sortierung, $sortierung_whitelist)) {
    $sortierung = 'Projektname';
}

// FILTER
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$status_whitelist = ['Planung', 'Aktiv', 'Abgeschlossen', 'Pausiert'];

// Suche
$search_query = "";
$params = [];

$sql = "SELECT Projekt_ID, Projektname, Status, Startdatum, Budget, Zustaendig_ID
        FROM Projekte
        WHERE {$where_zugriff}";

if (isset($_GET['suche']) && !empty(trim($_GET['suche']))) {
    $search_query = trim($_GET['suche']);
    $sql .= " AND (Projektname LIKE ? OR Notizen_Projekt LIKE ?)";
    $search_param = "%$search_query%";
    $params = [$search_param, $search_param];
}

if (!empty($filter_status) && in_array($filter_status, $status_whitelist)) {
    if (empty($params)) {
        $sql .= " AND Status = ?";
        $params = [$filter_status];
    } else {
        $sql .= " AND Status = ?";
        $params[] = $filter_status;
    }
}

$sql .= " ORDER BY {$sortierung} ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projekte = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fehler in projekte.php: " . $e->getMessage());
    echo "<div class='alert alert-error'>Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
    $projekte = [];
}

?>

<div class="card-title-container">
    <h1 class="card-title">
        <i class="fas fa-project-diagram"></i>
        Projektliste
    </h1>
    <div class="button-group">
        <a href="csv_export_projekt.php" class="btn btn-secondary">
            <i class="fas fa-file-export"></i>
            Export
        </a>
        <a href="projekt_neu.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Neues Projekt
        </a>
    </div>
</div>

<!-- Suche und Filter -->
<div class="card search-card">
    <form action="projekte.php" method="get" class="search-form">
        <input type="search" name="suche" placeholder="Projekte durchsuchen..." value="<?= htmlspecialchars($search_query) ?>">
        
        <select name="status" class="form-control">
            <option value="">Alle Status</option>
            <?php foreach ($status_whitelist as $status): ?>
                <option value="<?= $status ?>" <?= ($filter_status === $status) ? 'selected' : '' ?>>
                    <?= $status ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select name="sort" class="form-control">
            <option value="Projektname" <?= ($sortierung === 'Projektname') ? 'selected' : '' ?>>Nach Name</option>
            <option value="Status" <?= ($sortierung === 'Status') ? 'selected' : '' ?>>Nach Status</option>
            <option value="Startdatum" <?= ($sortierung === 'Startdatum') ? 'selected' : '' ?>>Nach Startdatum</option>
            <option value="Budget" <?= ($sortierung === 'Budget') ? 'selected' : '' ?>>Nach Budget</option>
        </select>
        
        <button type="submit" class="btn">
            <i class="fas fa-search"></i>
            Suchen
        </button>
        
        <?php if (!empty($search_query) || !empty($filter_status)): ?>
            <a href="projekte.php" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                Zurücksetzen
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Projekt-Liste KOMPAKT -->
<?php if (empty($projekte)): ?>
    <div class="card">
        <div class="empty-state-container">
            <i class="fas fa-project-diagram empty-state-icon"></i>
            <?php if (!empty($search_query) || !empty($filter_status)): ?>
                <h2>Keine Projekte gefunden</h2>
                <p>Für die aktuelle Suche/Filter wurden keine Projekte gefunden.</p>
                <a href="projekte.php" class="btn btn-secondary">Zurücksetzen</a>
            <?php else: ?>
                <h2>Noch keine Projekte vorhanden</h2>
                <p>Legen Sie jetzt Ihr erstes Projekt an.</p>
                <a href="projekt_neu.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Erstes Projekt anlegen
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <!-- KOMPAKTE Tabellen-Ansicht -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Projekt</th>
                        <th>Status</th>
                        <th>Startdatum</th>
                        <th>Budget</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projekte as $projekt): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($projekt['Projektname']) ?></strong>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $projekt['Status'])) ?>">
                                    <?= htmlspecialchars($projekt['Status']) ?>
                                </span>
                            </td>
                            <td>
                                <?= $projekt['Startdatum'] ? date('d.m.Y', strtotime($projekt['Startdatum'])) : '-' ?>
                            </td>
                            <td>
                                <?= $projekt['Budget'] ? number_format($projekt['Budget'], 2, ',', '.') . ' €' : '-' ?>
                            </td>
                            <td>
                                <div class="action-buttons-compact">
                                    <a href="projekt_details.php?id=<?= $projekt['Projekt_ID'] ?>" class="btn btn-sm btn-icon" title="Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="projekt_bearbeiten.php?id=<?= $projekt['Projekt_ID'] ?>" class="btn btn-sm btn-icon" title="Bearbeiten">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="projekt_loeschen.php?id=<?= $projekt['Projekt_ID'] ?>" class="btn btn-sm btn-icon btn-danger" title="Löschen" onclick="return confirm('Wirklich löschen?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<style>
.button-group {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.search-form {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.search-form input[type="search"] {
    flex: 2;
    min-width: 200px;
}

.search-form select {
    flex: 1;
    min-width: 120px;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background-color: var(--bg-secondary);
}

.data-table th {
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid var(--border-color);
}

.data-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

.data-table tbody tr:hover {
    background-color: var(--bg-secondary);
}

.action-buttons-compact {
    display: flex;
    gap: 0.25rem;
}

.btn-icon {
    padding: 0.5rem;
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}
</style>

<?php include 'footer.php'; ?>
