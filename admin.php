<?php
session_start();

// Use absolute path for database
$db_path = __DIR__ . '/squares.db';
try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get game name for display
$game_name_stmt = $db->prepare("SELECT value FROM metadata WHERE key = 'game_name'");
$game_name_stmt->execute();
$game_name = $game_name_stmt->fetchColumn() ?: "Piber's Squares";

// LOGIN LOGIC
if (isset($_POST['login_pass'])) {
    $stmt = $db->prepare("SELECT value FROM metadata WHERE key = 'admin_pass'");
    $stmt->execute();
    $real_pass = $stmt->fetchColumn();
    if ($_POST['login_pass'] === $real_pass) {
        $_SESSION['logged_in'] = true;
    } else {
        $error = "Wrong Password";
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

if (!isset($_SESSION['logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Commissioner Login</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg-dark: #0a0a0f;
                --bg-card: #141419;
                --bg-elevated: #1a1a22;
                --text-primary: #ffffff;
                --text-muted: #5a5a6b;
                --accent-gold: #d4af37;
                --error: #ef4444;
            }
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', sans-serif;
                background: var(--bg-dark);
                color: var(--text-primary);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .login-card {
                background: var(--bg-card);
                padding: 48px 40px;
                border-radius: 16px;
                width: 100%;
                max-width: 400px;
                border: 1px solid rgba(255,255,255,0.05);
                text-align: center;
            }
            .login-icon {
                width: 64px;
                height: 64px;
                background: linear-gradient(135deg, var(--accent-gold) 0%, #b8962d 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px;
                font-size: 28px;
            }
            h1 {
                font-family: 'Bebas Neue', sans-serif;
                font-size: 1.75rem;
                letter-spacing: 0.05em;
                margin-bottom: 8px;
            }
            .subtitle {
                color: var(--text-muted);
                font-size: 0.85rem;
                margin-bottom: 32px;
            }
            .error {
                background: rgba(239, 68, 68, 0.15);
                border: 1px solid var(--error);
                color: var(--error);
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-size: 0.85rem;
            }
            input {
                width: 100%;
                padding: 14px 16px;
                background: var(--bg-elevated);
                border: 2px solid transparent;
                border-radius: 8px;
                color: var(--text-primary);
                font-size: 1rem;
                margin-bottom: 16px;
                transition: border-color 0.2s;
            }
            input:focus {
                outline: none;
                border-color: var(--accent-gold);
            }
            input::placeholder { color: var(--text-muted); }
            button {
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, var(--accent-gold) 0%, #b8962d 100%);
                border: none;
                border-radius: 8px;
                color: var(--bg-dark);
                font-family: 'Bebas Neue', sans-serif;
                font-size: 1.1rem;
                letter-spacing: 0.1em;
                cursor: pointer;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            button:hover {
                transform: scale(1.02);
                box-shadow: 0 4px 20px rgba(212, 175, 55, 0.4);
            }
        </style>
    </head>
    <body>
        <div class="login-card">
            <div class="login-icon">🏈</div>
            <h1>Commissioner Login</h1>
            <p class="subtitle"><?= htmlspecialchars($game_name) ?> Admin</p>
            <?php if(isset($error)): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="password" name="login_pass" placeholder="Enter password" autofocus>
                <button type="submit">Enter Dashboard</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// DATA HANDLING (Only accessible if logged in)

// Update Scores
if (isset($_POST['update_scores'])) {
    foreach(['q1','q2','q3','q4'] as $q) {
        $top_val = isset($_POST[$q.'_top']) && $_POST[$q.'_top'] !== '' ? $_POST[$q.'_top'] : '';
        $side_val = isset($_POST[$q.'_side']) && $_POST[$q.'_side'] !== '' ? $_POST[$q.'_side'] : '';
        $db->prepare("UPDATE metadata SET value = ? WHERE key = ?")->execute([$top_val, $q.'_top']);
        $db->prepare("UPDATE metadata SET value = ? WHERE key = ?")->execute([$side_val, $q.'_side']);
    }
    header("Location: admin.php?msg=scores"); exit;
}

// Update Settings
if (isset($_POST['update_settings'])) {
    $db->prepare("INSERT OR REPLACE INTO metadata (key, value) VALUES ('game_name', ?)")->execute([$_POST['game_name']]);
    $db->prepare("INSERT OR REPLACE INTO metadata (key, value) VALUES ('top_team', ?)")->execute([$_POST['top_team']]);
    $db->prepare("INSERT OR REPLACE INTO metadata (key, value) VALUES ('side_team', ?)")->execute([$_POST['side_team']]);
    $db->prepare("INSERT OR REPLACE INTO metadata (key, value) VALUES ('price_per_square', ?)")->execute([$_POST['price_per_square']]);
    $db->prepare("INSERT OR REPLACE INTO metadata (key, value) VALUES ('venmo_handle', ?)")->execute([$_POST['venmo_handle']]);
    header("Location: admin.php?msg=settings"); exit;
}

// Payment Actions
if (isset($_POST['approve_name'])) {
    $db->prepare("UPDATE squares SET status = 'locked' WHERE name = ? AND status = 'pending'")->execute([$_POST['approve_name']]);
    header("Location: admin.php?msg=paid"); exit;
}
if (isset($_POST['release_name'])) {
    $db->prepare("UPDATE squares SET name = NULL, status = 'open' WHERE name = ? AND status = 'pending'")->execute([$_POST['release_name']]);
    header("Location: admin.php?msg=released"); exit;
}

// Rename Player
if (isset($_POST['rename_player'])) {
    $old_name = $_POST['old_name'];
    $new_name = trim($_POST['new_name']);
    if (!empty($new_name) && $new_name !== $old_name) {
        $db->prepare("UPDATE squares SET name = ? WHERE name = ?")->execute([$new_name, $old_name]);
        header("Location: admin.php?msg=renamed"); exit;
    }
    header("Location: admin.php"); exit;
}

// Board Actions
if (isset($_POST['randomize'])) {
    try {
        $n1 = range(0,9); shuffle($n1);
        $n2 = range(0,9); shuffle($n2);
        $db->prepare("UPDATE metadata SET value = ? WHERE key = 'top_nums'")->execute([implode(',', $n1)]);
        $db->prepare("UPDATE metadata SET value = ? WHERE key = 'side_nums'")->execute([implode(',', $n2)]);
        header("Location: admin.php?msg=shuffled"); 
        exit;
    } catch (PDOException $e) {
        $error = "Failed to randomize: " . $e->getMessage();
    }
}
if (isset($_POST['clear_nums'])) {
    try {
        $db->exec("UPDATE metadata SET value = '' WHERE key = 'top_nums'");
        $db->exec("UPDATE metadata SET value = '' WHERE key = 'side_nums'");
        header("Location: admin.php?msg=cleared"); 
        exit;
    } catch (PDOException $e) {
        $error = "Failed to clear numbers: " . $e->getMessage();
    }
}
if (isset($_POST['reset_board'])) {
    try {
        $db->exec("UPDATE squares SET name = NULL, status = 'open'");
        $db->exec("UPDATE metadata SET value = '' WHERE key = 'top_nums'");
        $db->exec("UPDATE metadata SET value = '' WHERE key = 'side_nums'");
        foreach(['q1','q2','q3','q4'] as $q) {
            $db->exec("UPDATE metadata SET value = '' WHERE key = '{$q}_top'");
            $db->exec("UPDATE metadata SET value = '' WHERE key = '{$q}_side'");
        }
        header("Location: admin.php?msg=reset"); 
        exit;
    } catch (PDOException $e) {
        $error = "Failed to reset: " . $e->getMessage();
    }
}

// Fetch data
$pending = $db->query("SELECT name, COUNT(*) as count FROM squares WHERE status = 'pending' GROUP BY name")->fetchAll(PDO::FETCH_ASSOC);
$paid = $db->query("SELECT name, COUNT(*) as count FROM squares WHERE status = 'locked' GROUP BY name")->fetchAll(PDO::FETCH_ASSOC);
$meta = $db->query("SELECT * FROM metadata")->fetchAll(PDO::FETCH_KEY_PAIR);

// Set defaults for new fields if not present
$game_name = $meta['game_name'] ?? "Piber's Squares";
$venmo_handle = $meta['venmo_handle'] ?? '@pibervision';
$price_per_square = (int)($meta['price_per_square'] ?? 10);

// Auto-calculate prizes: Q1(12.5%), Half(25%), Q3(12.5%), Final(50%)
$total_pot = 100 * $price_per_square;
$prizes = [
    'q1' => $total_pot * 0.125,
    'q2' => $total_pot * 0.25,
    'q3' => $total_pot * 0.125,
    'q4' => $total_pot * 0.50
];

// Stats
$stats = $db->query("SELECT status, COUNT(*) as cnt FROM squares GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$open_count = $stats['open'] ?? 0;
$pending_count = $stats['pending'] ?? 0;
$locked_count = $stats['locked'] ?? 0;

$messages = [
    'scores' => ['type' => 'success', 'text' => 'Scores updated successfully'],
    'settings' => ['type' => 'success', 'text' => 'Settings saved'],
    'paid' => ['type' => 'success', 'text' => 'Payment confirmed'],
    'released' => ['type' => 'info', 'text' => 'Squares released back to pool'],
    'renamed' => ['type' => 'success', 'text' => 'Player renamed successfully'],
    'shuffled' => ['type' => 'success', 'text' => 'Numbers randomized'],
    'cleared' => ['type' => 'info', 'text' => 'Numbers cleared'],
    'reset' => ['type' => 'warning', 'text' => 'Board has been reset'],
];
$msg = isset($_GET['msg']) && isset($messages[$_GET['msg']]) ? $messages[$_GET['msg']] : null;

$period_labels = ['q1' => '1st Quarter', 'q2' => 'Halftime', 'q3' => '3rd Quarter', 'q4' => 'Final'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commissioner Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0a0a0f;
            --bg-card: #141419;
            --bg-elevated: #1a1a22;
            --text-primary: #ffffff;
            --text-secondary: #8b8b9b;
            --text-muted: #5a5a6b;
            --accent-gold: #d4af37;
            --accent-gold-dim: rgba(212, 175, 55, 0.15);
            --success: #22c55e;
            --success-dim: rgba(34, 197, 94, 0.15);
            --warning: #f59e0b;
            --warning-dim: rgba(245, 158, 11, 0.15);
            --error: #ef4444;
            --error-dim: rgba(239, 68, 68, 0.15);
            --info: #3b82f6;
            --info-dim: rgba(59, 130, 246, 0.15);
            --team-top: #004C54;
            --team-side: #E31837;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.5;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
            padding-bottom: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .header-left h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.75rem;
            letter-spacing: 0.05em;
            color: var(--accent-gold);
        }

        .header-left p {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--bg-elevated);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .btn-link:hover {
            background: var(--bg-card);
            color: var(--text-primary);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.success { background: var(--success-dim); border: 1px solid var(--success); color: var(--success); }
        .alert.warning { background: var(--warning-dim); border: 1px solid var(--warning); color: var(--warning); }
        .alert.info { background: var(--info-dim); border: 1px solid var(--info); color: var(--info); }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .stat-value {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2rem;
        }

        .stat-value.open { color: var(--text-secondary); }
        .stat-value.pending { color: var(--warning); }
        .stat-value.locked { color: var(--success); }

        .stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .section {
            background: var(--bg-card);
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.05);
            overflow: hidden;
        }

        .section-header {
            background: var(--bg-elevated);
            padding: 14px 20px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.1rem;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .section-body {
            padding: 20px;
        }

        .score-grid {
            display: grid;
            gap: 16px;
        }

        .score-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 16px;
            align-items: center;
            padding: 12px 16px;
            background: var(--bg-elevated);
            border-radius: 8px;
        }

        .score-label {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .score-input-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .score-input-group small {
            font-size: 0.65rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .score-input {
            width: 60px;
            padding: 8px;
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 1rem;
            text-align: center;
            font-family: 'Bebas Neue', sans-serif;
        }

        .score-input:focus {
            outline: none;
            border-color: var(--accent-gold);
        }

        .score-input.top { border-color: var(--team-top); }
        .score-input.side { border-color: var(--team-side); }

        .pending-list {
            display: grid;
            gap: 12px;
        }

        .pending-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: var(--bg-elevated);
            border-radius: 8px;
            border-left: 3px solid var(--warning);
        }

        .pending-info h3 {
            font-size: 1rem;
            margin-bottom: 2px;
        }

        .pending-info p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .pending-amount {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.25rem;
            color: var(--warning);
            margin-right: 16px;
        }

        .pending-actions {
            display: flex;
            gap: 8px;
        }

        .btn-approve, .btn-release {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s, opacity 0.15s;
        }

        .btn-approve {
            background: var(--success);
            color: white;
        }

        .btn-release {
            background: var(--bg-card);
            color: var(--text-muted);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .btn-approve:hover, .btn-release:hover {
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 24px;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .settings-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .settings-group.full {
            grid-column: 1 / -1;
        }

        .settings-group label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
        }

        .settings-input {
            padding: 12px;
            background: var(--bg-elevated);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .settings-input:focus {
            outline: none;
            border-color: var(--accent-gold);
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1rem;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 16px;
        }

        .btn:hover {
            transform: scale(1.02);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-gold) 0%, #b8962d 100%);
            color: var(--bg-dark);
        }

        .btn-primary:hover {
            box-shadow: 0 4px 20px rgba(212, 175, 55, 0.4);
        }

        .btn-secondary {
            background: var(--bg-elevated);
            color: var(--text-primary);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-danger:hover {
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.4);
        }

        .btn-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .btn-row .btn {
            margin-top: 0;
        }

        @media (max-width: 500px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .score-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .score-inputs {
                display: flex;
                gap: 16px;
                justify-content: center;
            }

            .settings-grid {
                grid-template-columns: 1fr;
            }

            .btn-row {
                grid-template-columns: 1fr;
            }

            .pending-card {
                flex-wrap: wrap;
                gap: 12px;
            }

            .pending-amount {
                margin-right: 0;
            }
        }

        /* Edit button */
        .btn-edit {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.75rem;
            opacity: 0.5;
            padding: 2px 6px;
            vertical-align: middle;
            transition: opacity 0.2s;
        }

        .btn-edit:hover {
            opacity: 1;
        }

        /* Sort options */
        .sort-options {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sort-btn {
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-muted);
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .sort-btn:hover {
            border-color: var(--accent-gold);
            color: var(--text-primary);
        }

        .sort-btn.active {
            background: var(--accent-gold);
            color: var(--bg-dark);
            border-color: var(--accent-gold);
        }

        /* Edit Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            width: 90%;
            max-width: 400px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .modal h3 {
            margin-bottom: 16px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.25rem;
        }

        .modal input[type="text"] {
            width: 100%;
            padding: 12px;
            background: var(--bg-elevated);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
            margin-bottom: 16px;
        }

        .modal input[type="text"]:focus {
            outline: none;
            border-color: var(--accent-gold);
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .modal-buttons .btn-save {
            background: var(--accent-gold);
            color: var(--bg-dark);
        }

        .modal-buttons .btn-cancel {
            background: var(--bg-elevated);
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-left">
                <h1>Commissioner Dashboard</h1>
                <p><?= htmlspecialchars($meta['game_name'] ?? "Piber's Squares") ?> Admin</p>
            </div>
            <div class="header-actions">
                <a href="index.php" class="btn-link">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
                        <path d="M15 3h6v6"/>
                        <path d="M10 14L21 3"/>
                    </svg>
                    View Board
                </a>
                <a href="?logout" class="btn-link">Logout</a>
            </div>
        </header>

        <?php if ($msg): ?>
            <div class="alert <?= $msg['type'] ?>">
                <?php if ($msg['type'] === 'success'): ?>✓<?php endif; ?>
                <?php if ($msg['type'] === 'warning'): ?>⚠<?php endif; ?>
                <?php if ($msg['type'] === 'info'): ?>ℹ<?php endif; ?>
                <?= $msg['text'] ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value open"><?= $open_count ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-value pending"><?= $pending_count ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-value locked"><?= $locked_count ?></div>
                <div class="stat-label">Sold</div>
            </div>
        </div>

        <!-- Scores Section -->
        <div class="section">
            <div class="section-header">
                <span>🏈</span> Update Scores
            </div>
            <div class="section-body">
                <form method="POST">
                    <div class="score-grid">
                        <?php foreach(['q1','q2','q3','q4'] as $q): ?>
                        <div class="score-row">
                            <span class="score-label"><?= $period_labels[$q] ?></span>
                            <div class="score-input-group">
                                <small><?= htmlspecialchars($meta['top_team']) ?></small>
                                <input type="number" name="<?= $q ?>_top" value="<?= $meta[$q.'_top'] ?>" class="score-input top" min="0" placeholder="—">
                            </div>
                            <div class="score-input-group">
                                <small><?= htmlspecialchars($meta['side_team']) ?></small>
                                <input type="number" name="<?= $q ?>_side" value="<?= $meta[$q.'_side'] ?>" class="score-input side" min="0" placeholder="—">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" name="update_scores" class="btn btn-primary">Update Scores</button>
                </form>
            </div>
        </div>

        <!-- Payments Section -->
        <div class="section">
            <div class="section-header" style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span>💳</span> Player Payments
                </div>
                <div class="sort-options">
                    <span style="font-size: 0.65rem; color: var(--text-muted); font-family: 'Inter', sans-serif; text-transform: none; letter-spacing: 0;">Sort:</span>
                    <button type="button" class="sort-btn <?= (!isset($_GET['sort']) || $_GET['sort'] === 'name') ? 'active' : '' ?>" onclick="window.location='?sort=name'">Name</button>
                    <button type="button" class="sort-btn <?= (isset($_GET['sort']) && $_GET['sort'] === 'squares') ? 'active' : '' ?>" onclick="window.location='?sort=squares'">Squares</button>
                </div>
            </div>
            <div class="section-body">
                <?php 
                // Sort players based on selection
                $sort = $_GET['sort'] ?? 'name';
                if ($sort === 'squares') {
                    usort($pending, fn($a, $b) => $b['count'] - $a['count']);
                    usort($paid, fn($a, $b) => $b['count'] - $a['count']);
                } else {
                    usort($pending, fn($a, $b) => strcasecmp($a['name'], $b['name']));
                    usort($paid, fn($a, $b) => strcasecmp($a['name'], $b['name']));
                }
                ?>
                <?php if (empty($pending) && empty($paid)): ?>
                    <div class="empty-state">No players yet</div>
                <?php else: ?>
                    <div class="pending-list">
                        <?php foreach ($pending as $p): ?>
                        <div class="pending-card">
                            <div class="pending-info">
                                <h3 class="player-name" data-name="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?> <button type="button" class="btn-edit" onclick="editPlayer('<?= htmlspecialchars(addslashes($p['name'])) ?>')">✏️</button></h3>
                                <p><?= $p['count'] ?> square<?= $p['count'] > 1 ? 's' : '' ?> · <span style="color: var(--warning);">Awaiting Payment</span></p>
                            </div>
                            <span class="pending-amount">$<?= $p['count'] * $price_per_square ?></span>
                            <div class="pending-actions">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="approve_name" value="<?= htmlspecialchars($p['name']) ?>">
                                    <button type="submit" class="btn-approve">✓ Paid</button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Release these squares back to the pool?');">
                                    <input type="hidden" name="release_name" value="<?= htmlspecialchars($p['name']) ?>">
                                    <button type="submit" class="btn-release">Release</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php foreach ($paid as $p): ?>
                        <div class="pending-card" style="border-left-color: var(--success);">
                            <div class="pending-info">
                                <h3 class="player-name" data-name="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?> <button type="button" class="btn-edit" onclick="editPlayer('<?= htmlspecialchars(addslashes($p['name'])) ?>')">✏️</button></h3>
                                <p><?= $p['count'] ?> square<?= $p['count'] > 1 ? 's' : '' ?> · <span style="color: var(--success);">✓ Paid</span></p>
                            </div>
                            <span class="pending-amount" style="color: var(--success);">$<?= $p['count'] * $price_per_square ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Board Management -->
        <div class="section">
            <div class="section-header">
                <span>🎲</span> Board Management
            </div>
            <div class="section-body">
                <div class="btn-row">
                    <form method="POST" onsubmit="return confirm('Randomize the numbers? This should only be done once before the game starts.');">
                        <button type="submit" name="randomize" class="btn btn-secondary" style="margin-top:0;">🎲 Randomize Numbers</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Clear the numbers? (Squares remain claimed)');">
                        <button type="submit" name="clear_nums" class="btn btn-secondary" style="margin-top:0;">Clear Numbers</button>
                    </form>
                </div>
                <form method="POST" onsubmit="return confirm('⚠️ FULL RESET: This will clear ALL squares, numbers, and scores. Are you sure?');">
                    <button type="submit" name="reset_board" class="btn btn-danger">⚠️ Full Board Reset</button>
                </form>
            </div>
        </div>

        <!-- Settings -->
        <div class="section">
            <div class="section-header">
                <span>⚙️</span> Game Settings
            </div>
            <div class="section-body">
                <form method="POST">
                    <div class="settings-grid">
                        <div class="settings-group" style="grid-column: 1 / -1;">
                            <label>Game Name</label>
                            <input type="text" name="game_name" value="<?= htmlspecialchars($game_name) ?>" class="settings-input" placeholder="Piber's Squares" required>
                        </div>
                        <div class="settings-group">
                            <label>Top Team (Columns)</label>
                            <input type="text" name="top_team" value="<?= htmlspecialchars($meta['top_team']) ?>" class="settings-input" required>
                        </div>
                        <div class="settings-group">
                            <label>Side Team (Rows)</label>
                            <input type="text" name="side_team" value="<?= htmlspecialchars($meta['side_team']) ?>" class="settings-input" required>
                        </div>
                        <div class="settings-group">
                            <label>Price Per Square ($)</label>
                            <input type="number" name="price_per_square" value="<?= $price_per_square ?>" class="settings-input" min="1" required>
                        </div>
                        <div class="settings-group">
                            <label>Venmo Handle</label>
                            <input type="text" name="venmo_handle" value="<?= htmlspecialchars($venmo_handle) ?>" class="settings-input" placeholder="@username" required>
                        </div>
                    </div>
                    
                    <div style="background: var(--bg-elevated); border-radius: 8px; padding: 16px; margin-top: 16px;">
                        <div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 8px;">Auto-Calculated Prizes</div>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; text-align: center;">
                            <div>
                                <div style="font-size: 0.65rem; color: var(--text-muted);">Q1 (12.5%)</div>
                                <div style="font-family: 'Bebas Neue', sans-serif; font-size: 1.25rem; color: var(--accent-gold);">$<?= number_format($prizes['q1']) ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.65rem; color: var(--text-muted);">HALF (25%)</div>
                                <div style="font-family: 'Bebas Neue', sans-serif; font-size: 1.25rem; color: var(--accent-gold);">$<?= number_format($prizes['q2']) ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.65rem; color: var(--text-muted);">Q3 (12.5%)</div>
                                <div style="font-family: 'Bebas Neue', sans-serif; font-size: 1.25rem; color: var(--accent-gold);">$<?= number_format($prizes['q3']) ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.65rem; color: var(--text-muted);">FINAL (50%)</div>
                                <div style="font-family: 'Bebas Neue', sans-serif; font-size: 1.25rem; color: var(--accent-gold);">$<?= number_format($prizes['q4']) ?></div>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 12px; font-size: 0.75rem; color: var(--text-muted);">
                            Total Pot: <strong style="color: var(--success);">$<?= number_format($total_pot) ?></strong> (100 squares × $<?= $price_per_square ?>)
                        </div>
                    </div>
                    
                    <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Player Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <h3>✏️ Edit Player Name</h3>
            <form method="POST" id="renameForm">
                <input type="hidden" name="rename_player" value="1">
                <input type="hidden" name="old_name" id="oldNameInput">
                <input type="text" name="new_name" id="newNameInput" placeholder="Enter new name" required>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editPlayer(name) {
            document.getElementById('oldNameInput').value = name;
            document.getElementById('newNameInput').value = name;
            document.getElementById('editModal').classList.add('active');
            document.getElementById('newNameInput').focus();
            document.getElementById('newNameInput').select();
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modal on background click
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeEditModal();
        });
    </script>
</body>
</html>