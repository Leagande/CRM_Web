<?php
// benutzerverwaltung.php - MIT "ALS BENUTZER ANSEHEN"-BUTTON
$page_title = "Benutzerverwaltung";
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
require_once 'header.php';

// NUR ADMINS DÜRFEN HIER REIN!
nur_admin_zugriff('index.php');

// Suchlogik
$suche = trim($_GET['suche'] ?? '');
$filter_aktiv = $_GET['filter_aktiv'] ?? 'alle';

// SQL aufbauen
$sql = "SELECT 
    Benutzer_ID,
    Benutzername,
    Vorname,
    Nachname,
    Email,
    Aktiv,
    Erstellt_am,
    Letzter_Login
FROM Benutzer WHERE 1=1";

$params = [];

// Suchfilter
if (!empty($suche)) {
    $sql .= " AND (Benutzername LIKE ? OR Vorname LIKE ? OR Nachname LIKE ? OR Email LIKE ?)";
    $search_term = "%$suche%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

// Status-Filter
if ($filter_aktiv === 'aktiv') {
    $sql .= " AND Aktiv = 1";
} elseif ($filter_aktiv === 'inaktiv') {
    $sql .= " AND Aktiv = 0";
}

$sql .= " ORDER BY Erstellt_am DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $benutzer = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiken
    $stmt_stats = $pdo->query("SELECT 
        COUNT(*) as gesamt,
        SUM(CASE WHEN Aktiv = 1 THEN 1 ELSE 0 END) as aktiv,
        SUM(CASE WHEN Aktiv = 0 THEN 1 ELSE 0 END) as inaktiv
    FROM Benutzer");
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Fehler in Benutzerverwaltung: " . $e->getMessage());
    $_SESSION['error_message'] = "Fehler beim Laden der Benutzer.";
    $benutzer = [];
    $stats = ['gesamt' => 0, 'aktiv' => 0, 'inaktiv' => 0];
}

// Status ändern (Aktivieren/Deaktivieren)
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    // Nicht sich selbst deaktivieren
    if ($user_id == get_aktuelle_benutzer_id()) {
        $_SESSION['error_message'] = "Sie können sich nicht selbst deaktivieren!";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE Benutzer SET Aktiv = NOT Aktiv WHERE Benutzer_ID = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success_message'] = "Benutzerstatus wurde geändert.";
            header("Location: benutzerverwaltung.php");
            exit;
        } catch (PDOException $e) {
            error_log("Fehler beim Status-Ändern: " . $e->getMessage());
            $_SESSION['error_message'] = "Fehler beim Ändern des Status.";
        }
    }
}
?>

<div class="card">
    <div class="card-title-container">
        <h1 class="card-title">
            <i class="fas fa-users-cog"></i>
            Benutzerverwaltung
        </h1>
        <a href="benutzer_neu.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Neuer Benutzer
        </a>
    </div>

    <!-- Statistik-Kacheln -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1.5rem 0;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-users"></i></div>
            <div style="font-size: 2rem; font-weight: bold;"><?= $stats['gesamt'] ?></div>
            <div style="opacity: 0.9;">Gesamt</div>
        </div>
        
        <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-check-circle"></i></div>
            <div style="font-size: 2rem; font-weight: bold;"><?= $stats['aktiv'] ?></div>
            <div style="opacity: 0.9;">Aktiv</div>
        </div>
        
        <div style="background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%); color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-times-circle"></i></div>
            <div style="font-size: 2rem; font-weight: bold;"><?= $stats['inaktiv'] ?></div>
            <div style="opacity: 0.9;">Inaktiv</div>
        </div>
    </div>

    <!-- Suche und Filter -->
    <form method="GET" style="display: flex; gap: 0.75rem; margin: 1.5rem 0; flex-wrap: wrap;">
        <input type="search" 
               name="suche" 
               placeholder="Benutzer suchen..." 
               value="<?= htmlspecialchars($suche) ?>"
               style="flex: 2; min-width: 200px; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px;">
        
        <select name="filter_aktiv" style="flex: 1; min-width: 150px; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px;">
            <option value="alle" <?= $filter_aktiv === 'alle' ? 'selected' : '' ?>>Alle Status</option>
            <option value="aktiv" <?= $filter_aktiv === 'aktiv' ? 'selected' : '' ?>>Nur Aktive</option>
            <option value="inaktiv" <?= $filter_aktiv === 'inaktiv' ? 'selected' : '' ?>>Nur Inaktive</option>
        </select>
        
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-search"></i> Suchen
        </button>
        
        <?php if (!empty($suche) || $filter_aktiv !== 'alle'): ?>
            <a href="benutzerverwaltung.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Zurücksetzen
            </a>
        <?php endif; ?>
    </form>

    <!-- Benutzer-Tabelle -->
    <?php if (empty($benutzer)): ?>
        <div style="text-align: center; padding: 3rem; color: #64748b;">
            <i class="fas fa-users" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <h2>Keine Benutzer gefunden</h2>
            <p>Keine Benutzer entsprechen Ihren Suchkriterien.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Benutzername</th>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Status</th>
                        <th>Letzter Login</th>
                        <th>Erstellt am</th>
                        <th style="text-align: center;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($benutzer as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['Benutzername']) ?></strong></td>
                            <td><?= htmlspecialchars(trim($user['Vorname'] . ' ' . $user['Nachname'])) ?></td>
                            <td><?= htmlspecialchars($user['Email'] ?? '') ?></td>
                            <td>
                                <?php if ($user['Aktiv']): ?>
                                    <span class="status-badge status-kunde">
                                        <i class="fas fa-check-circle"></i> Aktiv
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-verloren">
                                        <i class="fas fa-times-circle"></i> Inaktiv
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['Letzter_Login']): ?>
                                    <?= date('d.m.Y H:i', strtotime($user['Letzter_Login'])) ?> Uhr
                                <?php else: ?>
                                    <span style="color: #64748b;">Noch nie</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d.m.Y', strtotime($user['Erstellt_am'])) ?></td>
                            <td>
                                <div class="action-buttons-compact">
                                    <!-- NEU: Als Benutzer ansehen -->
                                    <a href="view_as_user.php?action=set&user_id=<?= $user['Benutzer_ID'] ?>" 
                                       class="btn btn-sm btn-icon" 
                                       title="Als dieser Benutzer ansehen"
                                       style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <!-- Bearbeiten -->
                                    <a href="benutzer_bearbeiten.php?id=<?= $user['Benutzer_ID'] ?>" 
                                       class="btn btn-sm btn-icon btn-secondary" 
                                       title="Bearbeiten">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <!-- Passwort ändern -->
                                    <a href="benutzer_passwort_aendern.php?id=<?= $user['Benutzer_ID'] ?>" 
                                       class="btn btn-sm btn-icon btn-secondary" 
                                       title="Passwort ändern">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    
                                    <!-- Aktivieren/Deaktivieren (nur bei anderen Benutzern) -->
                                    <?php if ($user['Benutzer_ID'] != get_aktuelle_benutzer_id()): ?>
                                        <a href="benutzerverwaltung.php?toggle_status=1&id=<?= $user['Benutzer_ID'] ?>" 
                                           class="btn btn-sm btn-icon" 
                                           title="<?= $user['Aktiv'] ? 'Deaktivieren' : 'Aktivieren' ?>"
                                           style="background: <?= $user['Aktiv'] ? '#ef4444' : '#10b981' ?>; color: white;"
                                           onclick="return confirm('Möchten Sie den Status wirklich ändern?');">
                                            <i class="fas fa-<?= $user['Aktiv'] ? 'ban' : 'check' ?>"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
