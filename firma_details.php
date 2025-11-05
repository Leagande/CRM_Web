<?php
require_once 'db_verbindung.php';
require_once 'berechtigungen.php';
include 'header.php';

// 1. ID aus der URL holen
$firma_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$firma_id) {
    $_SESSION['error_message'] = "Ungültige Firma-ID.";
    header("Location: firmen.php");
    exit();
}

// 2. Alle Daten für diese Firma abrufen
try {
    // A) Firmendetails abrufen
    $stmt_firma = $pdo->prepare("SELECT * FROM Firmen WHERE Firma_ID = ?");
    $stmt_firma->execute([$firma_id]);
    $firma = $stmt_firma->fetch();

    if (!$firma) {
        $_SESSION['error_message'] = "Firma nicht gefunden.";
        header("Location: firmen.php");
        exit();
    }
    
    // ZUGRIFFSKONTROLLE!
    pruefe_zugriff_oder_redirect($firma['Erstellt_von'], 'firmen.php');
    
    // B) Zugehörige Ansprechpartner abrufen
    $stmt_ansprechpartner = $pdo->prepare("
        SELECT Ansprechpartner_ID, Vorname, Nachname, Position, Telefon 
        FROM Ansprechpartner 
        WHERE Firma_ID = ? 
        ORDER BY Nachname ASC
    ");
    $stmt_ansprechpartner->execute([$firma_id]);
    $ansprechpartner = $stmt_ansprechpartner->fetchAll();

    // C) Zugehörige Projekte abrufen
    $stmt_projekte = $pdo->prepare("
        SELECT p.Projekt_ID, p.Projektname, p.Status 
        FROM Projekte p
        JOIN Projekt_Firmen_Zuordnung pz ON p.Projekt_ID = pz.Projekt_ID
        WHERE pz.Firma_ID = ?
        ORDER BY p.Startdatum DESC
    ");
    $stmt_projekte->execute([$firma_id]);
    $projekte = $stmt_projekte->fetchAll();

} catch (PDOException $e) {
    die("Fehler beim Laden der Firmendetails: " . $e->getMessage());
}

$page_title = htmlspecialchars($firma['Firmenname']);
?>

<div class="container">
    
    <div class="card-title-container">
        <h1 class="card-title"><?php echo htmlspecialchars($firma['Firmenname']); ?></h1>
        
        <div class="action-buttons">
            <a href="firmen.php" class="btn btn-secondary btn-back">
                <i class="fas fa-arrow-left"></i>
                Zurück
            </a>
            <a href="ansprechpartner_neu.php?firma_id=<?php echo $firma_id; ?>" class="btn btn-secondary">
                <i class="fas fa-user-plus"></i>
                Neuer Kontakt
            </a>
            <a href="firma_bearbeiten.php?id=<?php echo $firma_id; ?>" class="btn btn-primary">
                <i class="fas fa-pencil-alt"></i>
                Bearbeiten
            </a>
            <a href="firma_loeschen.php?id=<?php echo $firma_id; ?>" class="btn btn-danger" onclick="return confirm('Firma wirklich löschen?')">
                <i class="fas fa-trash"></i>
                Löschen
            </a>
        </div>
    </div>

    <div class="grid-2-columns">

        <div class="card card-full-width">
            <h2 class="card-subtitle">Firmendetails</h2>
            
            <ul class="details-list">
                <li>
                    <i class="fas fa-info-circle list-icon"></i>
                    <div>
                        <strong>Status</strong>
                        <span class="status-badge status-<?php echo strtolower(htmlspecialchars($firma['Status'])); ?>">
                            <?php echo htmlspecialchars($firma['Status']); ?>
                        </span>
                    </div>
                </li>
                <li>
                    <i class="fas fa-phone list-icon"></i>
                    <div>
                        <strong>Telefonnummer</strong>
                        <span><?php echo htmlspecialchars($firma['Telefonnummer'] ?? 'N/A'); ?></span>
                    </div>
                </li>
                <li>
                    <i class="fas fa-map-marker-alt list-icon"></i>
                    <div>
                        <strong>Adresse</strong>
                        <span>
                            <?php echo htmlspecialchars($firma['Strasse'] ?? ''); ?><br>
                            <?php echo htmlspecialchars($firma['PLZ'] ?? ''); ?> <?php echo htmlspecialchars($firma['Ort'] ?? ''); ?><br>
                            <?php echo htmlspecialchars($firma['Land'] ?? ''); ?>
                        </span>
                    </div>
                </li>
                <li>
                    <i class="fas fa-clipboard list-icon"></i>
                    <div>
                        <strong>Notizen</strong>
                        <p class="details-notiz">
                            <?php echo nl2br(htmlspecialchars($firma['Notizen_Firma'] ?? 'Keine Notizen vorhanden.')); ?>
                        </p>
                    </div>
                </li>
            </ul>
        </div>

        <div class="card card-full-width">
            
            <h2 class="card-subtitle">Ansprechpartner</h2>
            <ul class="linked-item-list">
                <?php if (empty($ansprechpartner)): ?>
                    <li class="empty-list-item">Keine Ansprechpartner erfasst.</li>
                <?php else: ?>
                    <?php foreach ($ansprechpartner as $kontakt): ?>
                        <li>
                            <i class="fas fa-user list-icon"></i>
                            <div class="linked-item-content">
                                <strong><?php echo htmlspecialchars($kontakt['Vorname'] . ' ' . $kontakt['Nachname']); ?></strong>
                                <span><?php echo htmlspecialchars($kontakt['Position'] ?? 'Keine Position'); ?></span>
                                <span><?php echo htmlspecialchars($kontakt['Telefon'] ?? ''); ?></span>
                            </div>
                            <a href="ansprechpartner_bearbeiten.php?id=<?php echo $kontakt['Ansprechpartner_ID']; ?>" class="btn btn-sm btn-icon btn-secondary" title="Bearbeiten" style="margin-left: auto;">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
            
            <hr class="card-hr">

            <h2 class="card-subtitle">Zugehörige Projekte</h2>
            <ul class="linked-item-list">
                <?php if (empty($projekte)): ?>
                    <li class="empty-list-item">Diese Firma ist in keinen Projekten involviert.</li>
                <?php else: ?>
                    <?php foreach ($projekte as $projekt): ?>
                        <li>
                            <i class="fas fa-folder-open list-icon"></i>
                            <a href="projekt_details.php?id=<?php echo $projekt['Projekt_ID']; ?>">
                                <?php echo htmlspecialchars($projekt['Projektname']); ?>
                            </a>
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', htmlspecialchars($projekt['Status']))); ?>" style="margin-left: auto;">
                                <?php echo htmlspecialchars($projekt['Status']); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

        </div>
    </div>
</div>

<style>
.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-left: auto;
}
.grid-2-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    align-items: start;
}
.details-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.details-list li {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}
.details-list .list-icon {
    font-size: 1rem;
    color: var(--text-color-light);
    width: 20px;
    text-align: center;
    margin-top: 5px;
}
.details-list li > div {
    display: flex;
    flex-direction: column;
}
.details-list li strong {
    font-weight: 600;
    color: var(--text-color-light);
    font-size: 0.85rem;
    text-transform: uppercase;
    margin-bottom: 0.25rem;
}
.details-list li span, .details-list li p {
    font-weight: 500;
    color: var(--text-color-heading);
    line-height: 1.5;
}
.details-list p.details-notiz {
    white-space: pre-wrap;
    background-color: rgba(0,0,0,0.02);
    padding: 0.5rem 0.75rem;
    border-radius: var(--border-radius-sm);
}

.linked-item-list .linked-item-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.9rem;
}
.linked-item-list .linked-item-content strong {
    font-size: 1rem;
    color: var(--text-color-heading);
    font-weight: 600;
}
.linked-item-list .linked-item-content span {
    color: var(--text-color-light);
}

hr.card-hr {
    border: none;
    border-top: 1px solid var(--border-color);
    margin: 1.5rem 0;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

@media (max-width: 768px) {
    .grid-2-columns {
        grid-template-columns: 1fr;
    }
    .action-buttons {
        width: 100%;
        margin-top: 1rem;
        margin-left: 0;
    }
    .action-buttons a {
        flex: 1;
        text-align: center;
    }
}
</style>

<?php include 'footer.php'; ?>
