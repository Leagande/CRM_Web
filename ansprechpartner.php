<?php
// ansprechpartner.php
session_start();
require_once 'db_verbindung.php';

// Modus aus header.php übernehmen
// header.php muss VOR diesem Code inkludiert werden
require_once 'header.php';

$firma_id = filter_input(INPUT_GET, 'firma_id', FILTER_VALIDATE_INT);
if (!$firma_id) {
    header("Location: " . add_modus_param('firmen.php')); // Modus an Redirect anhängen
    exit();
}

try {
    // Firmenname holen
    $sql_firma = "SELECT Firmenname FROM Firmen WHERE Firma_ID = :firma_id";
    $stmt_firma = $pdo->prepare($sql_firma);
    $stmt_firma->execute([':firma_id' => $firma_id]);
    $firma = $stmt_firma->fetch();
    $firmenname = $firma ? $firma['Firmenname'] : 'Unbekannte Firma';

    // Ansprechpartner holen
    $sql_kontakte = "SELECT * FROM Ansprechpartner WHERE Firma_ID = :firma_id ORDER BY Nachname, Vorname";
    $stmt_kontakte = $pdo->prepare($sql_kontakte);
    $stmt_kontakte->execute([':firma_id' => $firma_id]);
    $ansprechpartner = $stmt_kontakte->fetchAll();

} catch (\PDOException $e) {
    die("Fehler beim Abrufen der Daten: " . $e->getMessage());
}

$page_title = "Ansprechpartner für " . $firmenname;
// require_once 'header.php'; // Bereits oben inkludiert
?>

<header>
    <h1>Ansprechpartner für: <?= htmlspecialchars($firmenname) ?></h1>
    <?php // Button ist in beiden Modi sichtbar ?>
    <a href="<?= add_modus_param('ansprechpartner_neu.php?firma_id='.$firma_id) ?>" class="btn">+ Neuer Ansprechpartner</a>
</header>

<div class="card">
     <?php if (empty($ansprechpartner)): ?>
         <div class="empty-state-container">
             <i class="fas fa-user-slash empty-state-icon"></i>
             <h2>Noch keine Kontakte erfasst</h2>
             <p>Legen Sie den ersten Ansprechpartner für diese Firma an.</p>
             <a href="<?= add_modus_param('ansprechpartner_neu.php?firma_id='.$firma_id) ?>" class="btn">Ersten Kontakt anlegen</a>
             <a href="<?= add_modus_param('firmen.php') ?>" class="btn-secondary" style="margin-left: 10px;">Zur Firmenübersicht</a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Position</th>
                    <th>E-Mail</th>
                    <th>Telefon</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ansprechpartner as $kontakt): ?>
                    <tr>
                        <td><?= htmlspecialchars(trim($kontakt['Vorname'] . ' ' . $kontakt['Nachname'])) ?></td>
                        <td><?= htmlspecialchars($kontakt['Position'] ?? '') ?></td>
                        <td><?= htmlspecialchars($kontakt['Email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($kontakt['Telefon'] ?? '') ?></td>
                        <td class="action-links icon-links">
                             <?php // Bearbeiten/Löschen nur im Außendienst-Modus ?>
                            <?php if ($modus == 'aussendienst'): ?>
                                <a href="<?= add_modus_param('ansprechpartner_bearbeiten.php?id='.$kontakt['Ansprechpartner_ID']) ?>" title="Ansprechpartner bearbeiten"><i class="fas fa-edit"></i></a>
                                <a href="<?= add_modus_param('ansprechpartner_loeschen.php?id='.$kontakt['Ansprechpartner_ID'].'&firma_id='.$firma_id) ?>" title="Ansprechpartner löschen" onclick="return confirm('Sind Sie sicher...?');"><i class="fas fa-trash-alt"></i></a>
                            <?php else: ?>
                                <!-- Im Telefonmodus ggf. Platzhalter oder nichts anzeigen -->
                                <span style="color: #ccc;">Keine Aktionen</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require_once 'footer.php';
?>

