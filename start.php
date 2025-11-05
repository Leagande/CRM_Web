<?php
// start.php - STARTSEITE - Laufende Projekte + Heute/Morgen fÃ¤llig
error_reporting(E_ALL);
ini_set('display_errors', 1);

$page_title = "Startseite";
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
require_once 'header.php';

$where_zugriff = get_where_zugriff('Zustaendig_ID');

try {
    // 1. LAUFENDE PROJEKTE (Status = "Aktiv")
    $sql_projekte = "
        SELECT Projekt_ID, Projektname, Budget, Startdatum, Enddatum_geplant
        FROM Projekte 
        WHERE Status = 'Aktiv' AND {$where_zugriff}
        ORDER BY Startdatum DESC
    ";
    $stmt_projekte = $pdo->query($sql_projekte);
    $laufende_projekte = $stmt_projekte->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. UMSATZ (Budget aller laufenden Projekte)
    $sql_umsatz = "
        SELECT SUM(Budget) 
        FROM Projekte 
        WHERE Status = 'Aktiv' AND {$where_zugriff}
    ";
    $umsatz_aktuell = $pdo->query($sql_umsatz)->fetchColumn() ?? 0;
    
    // 3. AKTIVITÃ„TEN HEUTE FÃ„LLIG
    $heute = date('Y-m-d');
    $sql_heute = "
        SELECT a.*, f.Firmenname, p.Projektname
        FROM AktivitÃ¤ten a
        LEFT JOIN Firmen f ON a.Firma_ID = f.Firma_ID
        LEFT JOIN Projekte p ON a.Projekt_ID = p.Projekt_ID
        WHERE a.Erledigt_Status = 0 
        AND a.Faelligkeitsdatum = ?
        AND " . get_where_zugriff('a.Zustaendig_ID') . "
        ORDER BY a.Datum DESC
    ";
    $stmt_heute = $pdo->prepare($sql_heute);
    $stmt_heute->execute([$heute]);
    $aktivitaeten_heute = $stmt_heute->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. AKTIVITÃ„TEN MORGEN FÃ„LLIG
    $morgen = date('Y-m-d', strtotime('+1 day'));
    $sql_morgen = "
        SELECT a.*, f.Firmenname, p.Projektname
        FROM AktivitÃ¤ten a
        LEFT JOIN Firmen f ON a.Firma_ID = f.Firma_ID
        LEFT JOIN Projekte p ON a.Projekt_ID = p.Projekt_ID
        WHERE a.Erledigt_Status = 0 
        AND a.Faelligkeitsdatum = ?
        AND " . get_where_zugriff('a.Zustaendig_ID') . "
        ORDER BY a.Datum DESC
    ";
    $stmt_morgen = $pdo->prepare($sql_morgen);
    $stmt_morgen->execute([$morgen]);
    $aktivitaeten_morgen = $stmt_morgen->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Fehler in start.php: " . $e->getMessage());
    echo "<div class='alert alert-error'>Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
    $laufende_projekte = [];
    $umsatz_aktuell = 0;
    $aktivitaeten_heute = [];
    $aktivitaeten_morgen = [];
}

?>

<header>
    <h1>
        <i class="fas fa-home"></i>
        Willkommen, <?= htmlspecialchars($_SESSION['benutzername']) ?>!
    </h1>
</header>

<!-- UMSATZ -->
<div class="card">
    <h2 style="margin: 0; display: flex; align-items: center; gap: 1rem;">
        <i class="fas fa-euro-sign" style="color: var(--primary-color);"></i>
        Aktueller Umsatz (Laufende Projekte)
    </h2>
    <div style="font-size: 3rem; font-weight: bold; color: var(--primary-color); margin-top: 1rem;">
        â‚¬ <?= number_format($umsatz_aktuell, 2, ',', '.') ?>
    </div>
</div>

<!-- LAUFENDE PROJEKTE -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin: 0;">
            <i class="fas fa-tasks"></i>
            Projekte in Bearbeitung (<?= count($laufende_projekte) ?>)
        </h2>
        <a href="projekt_neu.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Neues Projekt
        </a>
    </div>
    
    <?php if (empty($laufende_projekte)): ?>
        <div class="empty-state-container">
            <i class="fas fa-project-diagram empty-state-icon"></i>
            <h3>Keine laufenden Projekte</h3>
            <p>Aktuell sind keine Projekte in Bearbeitung.</p>
            <a href="projekt_neu.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Erstes Projekt starten
            </a>
        </div>
    <?php else: ?>
        <div class="projekt-liste">
            <?php foreach ($laufende_projekte as $projekt): ?>
                <a href="projekt_details.php?id=<?= $projekt['Projekt_ID'] ?>" class="projekt-card">
                    <div class="projekt-card-header">
                        <h3>
                            <i class="fas fa-folder-open"></i>
                            <?= htmlspecialchars($projekt['Projektname']) ?>
                        </h3>
                        <?php if ($projekt['Budget'] > 0): ?>
                            <span class="projekt-budget">
                                â‚¬ <?= number_format($projekt['Budget'], 2, ',', '.') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="projekt-card-footer">
                        <span>
                            <i class="fas fa-calendar"></i>
                            Start: <?= date('d.m.Y', strtotime($projekt['Startdatum'])) ?>
                        </span>
                        <?php if ($projekt['Enddatum_geplant']): ?>
                            <span>
                                <i class="fas fa-calendar-check"></i>
                                Ende: <?= date('d.m.Y', strtotime($projekt['Enddatum_geplant'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- AKTIVITÃ„TEN HEUTE FÃ„LLIG -->
<div class="card">
    <h2 style="margin-bottom: 1.5rem;">
        <i class="fas fa-calendar-day"></i>
        Heute fÃ¤llig (<?= count($aktivitaeten_heute) ?>)
    </h2>
    
    <?php if (empty($aktivitaeten_heute)): ?>
        <p style="color: var(--text-color-light); text-align: center; padding: 2rem;">
            <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success-color); margin-bottom: 1rem; display: block;"></i>
            Keine AktivitÃ¤ten heute fÃ¤llig!
        </p>
    <?php else: ?>
        <div class="aktivitaet-liste">
            <?php foreach ($aktivitaeten_heute as $aktivitaet): ?>
                <div class="aktivitaet-mini">
                    <div>
                        <strong><?= htmlspecialchars($aktivitaet['Betreff']) ?></strong>
                        <br>
                        <small>
                            <i class="fas fa-building"></i> <?= htmlspecialchars($aktivitaet['Firmenname'] ?? 'N/A') ?>
                            <?php if ($aktivitaet['Projektname']): ?>
                                | <i class="fas fa-folder-open"></i> <?= htmlspecialchars($aktivitaet['Projektname']) ?>
                            <?php endif; ?>
                        </small>
                    </div>
                    <span class="activity-type"><?= htmlspecialchars($aktivitaet['AktivitÃ¤tstyp']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- AKTIVITÃ„TEN MORGEN FÃ„LLIG -->
<div class="card">
    <h2 style="margin-bottom: 1.5rem;">
        <i class="fas fa-calendar-plus"></i>
        Morgen fÃ¤llig (<?= count($aktivitaeten_morgen) ?>)
    </h2>
    
    <?php if (empty($aktivitaeten_morgen)): ?>
        <p style="color: var(--text-color-light); text-align: center; padding: 2rem;">
            <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success-color); margin-bottom: 1rem; display: block;"></i>
            Keine AktivitÃ¤ten morgen fÃ¤llig!
        </p>
    <?php else: ?>
        <div class="aktivitaet-liste">
            <?php foreach ($aktivitaeten_morgen as $aktivitaet): ?>
                <div class="aktivitaet-mini">
                    <div>
                        <strong><?= htmlspecialchars($aktivitaet['Betreff']) ?></strong>
                        <br>
                        <small>
                            <i class="fas fa-building"></i> <?= htmlspecialchars($aktivitaet['Firmenname'] ?? 'N/A') ?>
                            <?php if ($aktivitaet['Projektname']): ?>
                                | <i class="fas fa-folder-open"></i> <?= htmlspecialchars($aktivitaet['Projektname']) ?>
                            <?php endif; ?>
                        </small>
                    </div>
                    <span class="activity-type"><?= htmlspecialchars($aktivitaet['AktivitÃ¤tstyp']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Projekt-Karten */
.projekt-liste {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.projekt-card {
    display: block;
    padding: 1.5rem;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    text-decoration: none;
    color: var(--text-color);
    transition: all 0.3s ease;
}

.projekt-card:hover {
    border-color: var(--primary-color);
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.projekt-card-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.projekt-card-header h3 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--text-color-heading);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.projekt-card-header h3 i {
    color: var(--primary-color);
}

.projekt-budget {
    font-size: 1.25rem;
    font-weight: bold;
    color: var(--primary-color);
}

.projekt-card-footer {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: var(--text-color-light);
}

.projekt-card-footer i {
    margin-right: 0.5rem;
}

/* AktivitÃ¤t Mini */
.aktivitaet-liste {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.aktivitaet-mini {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
}

.aktivitaet-mini small {
    color: var(--text-color-light);
}
</style>

<?php require_once 'footer.php'; ?>
