<?php
// firmen.php - KOMPAKT mit Sortierung, Filter, Import/Export
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
include 'header.php';

// Sicherer WHERE-Filter
$where_zugriff = get_where_zugriff('Erstellt_von');

// SORTIERUNG
$sortierung = isset($_GET['sort']) ? $_GET['sort'] : 'Firmenname';
$sortierung_whitelist = ['Firmenname', 'PLZ', 'Ort', 'Status', 'Erstellt_am'];
if (!in_array($sortierung, $sortierung_whitelist)) {
    $sortierung = 'Firmenname';
}

// FILTER
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$status_whitelist = ['Lead', 'Kunde', 'Partner', 'Verloren', 'Archiviert'];

// Suche
$search_query = "";
$params = [];

$sql = "SELECT Firma_ID, Firmenname, PLZ, Ort, Telefonnummer, Status, Erstellt_von
        FROM Firmen
        WHERE {$where_zugriff}";

if (isset($_GET['suche']) && !empty(trim($_GET['suche']))) {
    $search_query = trim($_GET['suche']);
    $sql .= " AND (Firmenname LIKE ? OR Ort LIKE ? OR PLZ LIKE ?)";
    $search_param = "%$search_query%";
    $params = [$search_param, $search_param, $search_param];
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
    $firmen = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fehler in firmen.php: " . $e->getMessage());
    echo "<div class='alert alert-error'>Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
    $firmen = [];
}

?>

<div class="card-title-container">
    <h1 class="card-title">
        <i class="fas fa-building"></i>
        Firmenliste
    </h1>
    <div class="button-group">
        <a href="csv_import.php" class="btn btn-secondary">
            <i class="fas fa-file-import"></i>
            Import
        </a>
        <a href="csv_export_firmen.php" class="btn btn-secondary">
            <i class="fas fa-file-export"></i>
            Export
        </a>
        <a href="firma_neu.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Neue Firma
        </a>
    </div>
</div>

<!-- Suche und Filter -->
<div class="card search-card">
    <form action="firmen.php" method="get" class="search-form">
        <input type="search" name="suche" placeholder="Firmen durchsuchen..." value="<?= htmlspecialchars($search_query) ?>">
        
        <select name="status" class="form-control">
            <option value="">Alle Status</option>
            <?php foreach ($status_whitelist as $status): ?>
                <option value="<?= $status ?>" <?= ($filter_status === $status) ? 'selected' : '' ?>>
                    <?= $status ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select name="sort" class="form-control">
            <option value="Firmenname" <?= ($sortierung === 'Firmenname') ? 'selected' : '' ?>>Nach Name</option>
            <option value="PLZ" <?= ($sortierung === 'PLZ') ? 'selected' : '' ?>>Nach PLZ</option>
            <option value="Ort" <?= ($sortierung === 'Ort') ? 'selected' : '' ?>>Nach Ort</option>
            <option value="Status" <?= ($sortierung === 'Status') ? 'selected' : '' ?>>Nach Status</option>
            <option value="Erstellt_am" <?= ($sortierung === 'Erstellt_am') ? 'selected' : '' ?>>Nach Datum</option>
        </select>
        
        <button type="submit" class="btn">
            <i class="fas fa-search"></i>
            Suchen
        </button>
        
        <?php if (!empty($search_query) || !empty($filter_status)): ?>
            <a href="firmen.php" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                Zurücksetzen
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Firmen-Liste KOMPAKT -->
<?php if (empty($firmen)): ?>
    <div class="card">
        <div class="empty-state-container">
            <i class="fas fa-building empty-state-icon"></i>
            <?php if (!empty($search_query) || !empty($filter_status)): ?>
                <h2>Keine Firmen gefunden</h2>
                <p>Für die aktuelle Suche/Filter wurden keine Firmen gefunden.</p>
                <a href="firmen.php" class="btn btn-secondary">Zurücksetzen</a>
            <?php else: ?>
                <h2>Noch keine Firmen vorhanden</h2>
                <p>Legen Sie jetzt Ihre erste Firma an.</p>
                <a href="firma_neu.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Erste Firma anlegen
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
                        <th>Firma</th>
                        <th>Ort</th>
                        <th>Telefon</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($firmen as $firma): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($firma['Firmenname']) ?></strong>
                            </td>
                            <td>
                                <?= htmlspecialchars($firma['PLZ']) ?> <?= htmlspecialchars($firma['Ort']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($firma['Telefonnummer'] ?? '-') ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $firma['Status'])) ?>">
                                    <?= htmlspecialchars($firma['Status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons-compact">
                                    <a href="firma_details.php?id=<?= $firma['Firma_ID'] ?>" class="btn btn-sm btn-icon" title="Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="firma_bearbeiten.php?id=<?= $firma['Firma_ID'] ?>" class="btn btn-sm btn-icon" title="Bearbeiten">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="firma_loeschen.php?id=<?= $firma['Firma_ID'] ?>" class="btn btn-sm btn-icon btn-danger" title="Löschen" onclick="return confirm('Wirklich löschen?')">
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
