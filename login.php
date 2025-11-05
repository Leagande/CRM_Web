<?php
// login.php - KORRIGIERT MIT ALLEN SESSION-VARIABLEN
session_start();

// Wenn der Benutzer ausloggt, Session sofort beenden
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    session_start(); // Neue Session für Login-Seite
    $success_message = "Sie wurden erfolgreich abgemeldet.";
}

// Wenn bereits eingeloggt, weiterleiten
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: start.php");
    exit;
}

require_once 'db_verbindung.php';

$error_message = isset($success_message) ? '' : '';
$success_message = $success_message ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = "Bitte füllen Sie alle Felder aus.";
    } else {
        try {
            // Benutzer aus Datenbank abrufen (MIT ROLLE!)
            $stmt = $pdo->prepare("SELECT * FROM Benutzer WHERE Benutzername = ? AND Aktiv = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['Passwort_Hash'])) {
                // Login erfolgreich - ALLE Session-Variablen setzen!
                $_SESSION['user_id'] = $user['Benutzer_ID'];
                $_SESSION['benutzer_id'] = $user['Benutzer_ID'];  // ← NEU!
                $_SESSION['rolle_id'] = $user['Rolle_ID'];        // ← NEU! WICHTIG!
                $_SESSION['username'] = $user['Benutzername'];
                $_SESSION['benutzername'] = $user['Benutzername']; // ← NEU!
                $_SESSION['vorname'] = $user['Vorname'];
                $_SESSION['nachname'] = $user['Nachname'];
                $_SESSION['logged_in'] = true;
                
                // Letzten Login aktualisieren
                $update_stmt = $pdo->prepare("UPDATE Benutzer SET Letzter_Login = NOW() WHERE Benutzer_ID = ?");
                $update_stmt->execute([$user['Benutzer_ID']]);
                
                // Weiterleitung zur Startseite
                header("Location: start.php");
                exit;
            } else {
                $error_message = "Ungültiger Benutzername oder Passwort.";
                // Sicherheit: Warte kurz, um Brute-Force zu erschweren
                sleep(1);
            }
        } catch (PDOException $e) {
            error_log("Login-Fehler: " . $e->getMessage());
            $error_message = "Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VertriebsCRM 2</title>
    <link rel="stylesheet" href="style.css?v=7.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 3rem 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #1e293b;
            font-size: 2rem;
            margin: 0 0 0.5rem 0;
            font-weight: 700;
        }
        
        .login-header p {
            color: #64748b;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            color: #334155;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background: #fee;
            color: #c00;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #060;
            border: 1px solid #cfc;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }
        
        .input-icon input {
            padding-left: 3rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-user-circle"></i> VertriebsCRM 2</h1>
                <p>Melden Sie sich an, um fortzufahren</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Benutzername</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" required autofocus autocomplete="username">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Passwort</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                    </div>
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Anmelden
                </button>
            </form>
        </div>
    </div>
</body>
</html>
