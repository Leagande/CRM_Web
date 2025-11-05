<?php
// header.php - MIT SUCHBALKEN!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'auth_check.php';
require_once 'berechtigungen.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'CRM System' ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <!-- LOGO führt zur STARTSEITE -->
            <a href="start.php" class="nav-logo">
                <i class="fas fa-user-tie"></i>
                <span>Mein CRM</span>
            </a>
        </div>

        <div class="nav-center">
            <!-- Dashboard = Aktivitäten -->
            <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            
            <!-- Firmen -->
            <a href="firmen.php" class="nav-link <?= $current_page == 'firmen.php' ? 'active' : '' ?>">
                <i class="fas fa-building"></i>
                <span>Firmen</span>
            </a>
            
            <!-- Projekte -->
            <a href="projekte.php" class="nav-link <?= $current_page == 'projekte.php' ? 'active' : '' ?>">
                <i class="fas fa-project-diagram"></i>
                <span>Projekte</span>
            </a>
            
            <!-- NEUER SUCHBALKEN! -->
            <div class="nav-search">
                <form action="suche.php" method="get" class="search-form-nav">
                    <input type="search" 
                           name="query" 
                           id="navSearchInput"
                           placeholder="Suchen..." 
                           autocomplete="off">
                    <div id="searchResults" class="search-results-dropdown"></div>
                </form>
            </div>
            
            <!-- Benutzer - NUR FÜR ADMIN! -->
            <?php if (ist_admin()): ?>
                <a href="benutzerverwaltung.php" class="nav-link <?= $current_page == 'benutzerverwaltung.php' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Benutzer</span>
                </a>
            <?php endif; ?>
        </div>

        <div class="nav-right">
            <div class="user-menu">
                <button class="user-menu-trigger" onclick="toggleUserMenu()">
                    <i class="fas fa-user-circle"></i>
                    <span><?= htmlspecialchars($_SESSION['benutzername']) ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-menu-dropdown" id="userMenuDropdown">
                    <a href="benutzer_passwort_aendern.php">
                        <i class="fas fa-key"></i>
                        Passwort ändern
                    </a>
                    <?php if (ist_admin()): ?>
                        <div class="dropdown-divider"></div>
                        <a href="benutzerverwaltung.php">
                            <i class="fas fa-users-cog"></i>
                            Benutzerverwaltung
                        </a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Abmelden
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- VIEW-MODE BANNER -->
    <?php if (ist_viewing_as_other_user()): 
        $view_username = get_view_as_username();
    ?>
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
            <div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <i class="fas fa-eye" style="font-size: 1.5rem;"></i>
                <div style="flex: 1; min-width: 200px;">
                    <strong style="font-size: 1.1rem;">Ansichtsmodus aktiv</strong><br>
                    <span style="opacity: 0.9;">Sie sehen die Daten von: <strong><?= htmlspecialchars($view_username) ?></strong></span>
                </div>
                <a href="view_as_user.php?action=clear" style="background: rgba(255,255,255,0.2); color: white; padding: 0.6rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; border: 2px solid rgba(255,255,255,0.3); transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <i class="fas fa-times-circle"></i> Ansicht beenden
                </a>
            </div>
        </div>
    <?php endif; ?>

    <main class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error_message']) ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

<style>
/* SUCHBALKEN IM HEADER */
.nav-search {
    position: relative;
    flex: 0 1 300px;
}

.search-form-nav {
    position: relative;
    width: 100%;
}

.search-form-nav input[type="search"] {
    width: 100%;
    padding: 0.6rem 1rem;
    padding-left: 2.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: 0.75rem center;
    transition: all 0.2s;
}

.search-form-nav input[type="search"]:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-results-dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
}

.search-results-dropdown.show {
    display: block;
}

.search-result-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    cursor: pointer;
    transition: background 0.15s;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-item:hover {
    background: #f8fafc;
}

.search-result-item a {
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.search-result-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 6px;
    color: var(--primary-color);
}

.search-result-content {
    flex: 1;
}

.search-result-title {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.15rem;
}

.search-result-meta {
    font-size: 0.85rem;
    color: #64748b;
}

.search-no-results {
    padding: 1.5rem;
    text-align: center;
    color: #64748b;
}

@media (max-width: 1024px) {
    .nav-search {
        display: none; /* Auf kleinen Bildschirmen ausblenden */
    }
}
</style>

<script>
// User-Menü Toggle
function toggleUserMenu() {
    const dropdown = document.getElementById('userMenuDropdown');
    dropdown.classList.toggle('show');
}

window.onclick = function(event) {
    if (!event.target.matches('.user-menu-trigger') && !event.target.closest('.user-menu-trigger')) {
        const dropdown = document.getElementById('userMenuDropdown');
        if (dropdown && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    }
}

// LIVE-SUCHE im Header
const searchInput = document.getElementById('navSearchInput');
const searchResults = document.getElementById('searchResults');
let searchTimeout;

if (searchInput) {
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            searchResults.classList.remove('show');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performLiveSearch(query);
        }, 300);
    });
    
    // Bei Enter auf Vollständige Suche weiterleiten
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = e.target.value.trim();
            if (query) {
                window.location.href = 'suche.php?query=' + encodeURIComponent(query);
            }
        }
    });
    
    // Dropdown schließen wenn außerhalb geklickt
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-search')) {
            searchResults.classList.remove('show');
        }
    });
}

function performLiveSearch(query) {
    // AJAX-Suche
    fetch('ajax_firmen_suche.php?query=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data, query);
        })
        .catch(error => {
            console.error('Suchfehler:', error);
            searchResults.classList.remove('show');
        });
}

function displaySearchResults(data, query) {
    if (!data || data.length === 0) {
        searchResults.innerHTML = `
            <div class="search-no-results">
                <i class="fas fa-search"></i><br>
                Keine Ergebnisse für "${escapeHtml(query)}"<br>
                <small>Drücken Sie Enter für vollständige Suche</small>
            </div>
        `;
        searchResults.classList.add('show');
        return;
    }
    
    let html = '';
    data.slice(0, 5).forEach(item => { // Nur erste 5 Ergebnisse
        html += `
            <div class="search-result-item">
                <a href="firma_details.php?id=${item.id}">
                    <div class="search-result-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="search-result-content">
                        <div class="search-result-title">${escapeHtml(item.name)}</div>
                        <div class="search-result-meta">${escapeHtml(item.ort)} • ${escapeHtml(item.status)}</div>
                    </div>
                </a>
            </div>
        `;
    });
    
    if (data.length > 5) {
        html += `
            <div class="search-result-item" style="background: #f8fafc; text-align: center;">
                <a href="suche.php?query=${encodeURIComponent(query)}">
                    <i class="fas fa-arrow-right"></i>
                    Alle ${data.length} Ergebnisse anzeigen
                </a>
            </div>
        `;
    }
    
    searchResults.innerHTML = html;
    searchResults.classList.add('show');
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
</script>
