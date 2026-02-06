<?php
// Use absolute path for database
$db_path = __DIR__ . '/squares.db';
try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$meta = $db->query("SELECT * FROM metadata")->fetchAll(PDO::FETCH_KEY_PAIR);
$squares = $db->query("SELECT * FROM squares ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Data Prep
$top_nums = !empty($meta['top_nums']) ? explode(',', $meta['top_nums']) : array_fill(0, 10, '?');
$side_nums = !empty($meta['side_nums']) ? explode(',', $meta['side_nums']) : array_fill(0, 10, '?');

// Game settings
$game_name = $meta['game_name'] ?? "Piber's Squares";
$price_per_square = (int)($meta['price_per_square'] ?? 10);
$venmo_handle = $meta['venmo_handle'] ?? '@pibervision';

// Auto-calculate prizes: Q1(12.5%), Half(25%), Q3(12.5%), Final(50%)
$total_pot = 100 * $price_per_square;
$prizes = [
    'q1' => $total_pot * 0.125,
    'q2' => $total_pot * 0.25,
    'q3' => $total_pot * 0.125,
    'q4' => $total_pot * 0.50
];

// Count squares
$open_count = 0;
$sold_count = 0;
foreach ($squares as $s) {
    if ($s['status'] === 'open') $open_count++;
    else $sold_count++;
}

// Find winners for all quarters
$winners = [];
$winning_square_ids = [];
$leaderboard = [];

foreach(['q1','q2','q3','q4'] as $q) {
    if ($meta[$q.'_top'] !== '' && $meta[$q.'_side'] !== '') {
        $t_digit = (int)$meta[$q.'_top'] % 10;
        $s_digit = (int)$meta[$q.'_side'] % 10;
        
        $col_idx = array_search((string)$t_digit, $top_nums);
        $row_idx = array_search((string)$s_digit, $side_nums);
        
        if ($col_idx !== false && $row_idx !== false) {
            $sq_id = ($row_idx * 10) + $col_idx + 1;
            $sq_data = $squares[$sq_id - 1];
            
            $owner = ($sq_data['status'] == 'locked' || $sq_data['status'] == 'pending') ? $sq_data['name'] : 'Unclaimed';
            
            $winners[$q] = [
                'period' => $q,
                'score_top' => $meta[$q.'_top'],
                'score_side' => $meta[$q.'_side'],
                'owner' => $owner,
                'prize' => $prizes[$q]
            ];
            $winning_square_ids[] = $sq_id;
            
            if (!isset($leaderboard[$owner])) $leaderboard[$owner] = 0;
            $leaderboard[$owner] += $prizes[$q];
        }
    }
}

// Handle Purchasing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['squares'])) {
    $name = trim(htmlspecialchars($_POST['name']));
    if (!empty($name) && !empty($_POST['squares'])) {
        $selected = explode(',', $_POST['squares']);
        $stmt = $db->prepare("UPDATE squares SET name = ?, status = 'pending' WHERE id = ? AND status = 'open'");
        foreach ($selected as $id) {
            if (is_numeric($id)) {
                $stmt->execute([$name, (int)$id]);
            }
        }
    }
    header("Location: index.php"); exit;
}

$period_labels = ['q1' => 'Q1', 'q2' => 'Half', 'q3' => 'Q3', 'q4' => 'Final'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($game_name) ?></title>
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
            --team-top: #004C54;
            --team-top-light: #00C3B3;
            --team-side: #E31837;
            --team-side-light: #FFB81C;
            --success: #22c55e;
            --warning: #f59e0b;
            --cell-size: clamp(32px, 8vw, 48px);
            --header-size: clamp(24px, 6vw, 36px);
            --border-radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
        }

        /* Subtle animated background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(0, 76, 84, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(227, 24, 55, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
            padding: 16px;
            padding-bottom: 100px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(2.5rem, 10vw, 4rem);
            letter-spacing: 0.05em;
            background: linear-gradient(135deg, var(--accent-gold) 0%, #f5e6a3 50%, var(--accent-gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 60px rgba(212, 175, 55, 0.3);
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.3em;
            color: var(--text-muted);
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.75rem;
            color: var(--accent-gold);
        }

        .stat-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--text-muted);
        }

        /* Grid Container */
        .grid-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 24px;
        }

        .top-team-label {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(1.25rem, 4vw, 1.75rem);
            color: var(--team-top-light);
            letter-spacing: 0.1em;
            margin-bottom: 8px;
            text-shadow: 0 0 20px rgba(0, 195, 179, 0.4);
        }

        .grid-with-side {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .side-team-label {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(1.25rem, 4vw, 1.75rem);
            color: var(--team-side-light);
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            letter-spacing: 0.1em;
            text-shadow: 0 0 20px rgba(255, 184, 28, 0.4);
        }

        .grid {
            display: grid;
            grid-template-columns: var(--header-size) repeat(10, var(--cell-size));
            gap: 2px;
            background: var(--bg-card);
            padding: 2px;
            border-radius: var(--border-radius);
            box-shadow: 
                0 4px 24px rgba(0, 0, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .cell {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(0.5rem, 1.8vw, 0.7rem);
            font-weight: 600;
            border-radius: 4px;
            transition: all 0.15s ease;
            user-select: none;
            -webkit-user-select: none;
        }

        .cell-header {
            width: var(--header-size);
            height: var(--header-size);
            background: var(--bg-elevated);
            color: var(--text-secondary);
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(0.9rem, 2.5vw, 1.1rem);
        }

        .cell-header.corner {
            background: transparent;
        }

        .cell-header.top {
            width: var(--cell-size);
            background: linear-gradient(180deg, var(--team-top) 0%, rgba(0, 76, 84, 0.6) 100%);
            color: var(--team-top-light);
        }

        .cell-header.side {
            height: var(--cell-size);
            background: linear-gradient(90deg, var(--team-side) 0%, rgba(227, 24, 55, 0.6) 100%);
            color: var(--team-side-light);
        }

        .cell-square {
            width: var(--cell-size);
            height: var(--cell-size);
            background: var(--bg-elevated);
            color: var(--text-secondary);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .cell-square.open:hover,
        .cell-square.open:active {
            background: var(--accent-gold-dim);
            color: var(--accent-gold);
        }

        .cell-square.pending {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.3) 0%, rgba(245, 158, 11, 0.15) 100%);
            color: var(--warning);
            cursor: default;
        }

        .cell-square.locked {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(34, 197, 94, 0.1) 100%);
            color: var(--success);
            cursor: default;
        }

        .cell-square.selected {
            background: var(--accent-gold) !important;
            color: var(--bg-dark) !important;
            box-shadow: 0 0 12px rgba(212, 175, 55, 0.6);
            transform: scale(1.05);
            z-index: 2;
        }

        .select-number {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(1rem, 3vw, 1.4rem);
            font-weight: bold;
            color: var(--bg-dark);
        }

        .cell-square.winner {
            background: linear-gradient(135deg, var(--accent-gold) 0%, #f5e6a3 100%) !important;
            color: var(--bg-dark) !important;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.8);
            animation: winner-pulse 2s ease-in-out infinite;
            z-index: 3;
        }

        .cell-square.winner .winner-icon {
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 0.6rem;
        }

        @keyframes winner-pulse {
            0%, 100% { box-shadow: 0 0 20px rgba(212, 175, 55, 0.8); }
            50% { box-shadow: 0 0 30px rgba(212, 175, 55, 1); }
        }

        .cell-name {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding: 0 2px;
            font-size: clamp(0.45rem, 1.5vw, 0.6rem);
        }

        /* Winners Section */
        .section-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.5rem;
            letter-spacing: 0.1em;
            color: var(--text-primary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title::before,
        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--text-muted), transparent);
        }

        .winners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .winner-card {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: 16px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .winner-card.won {
            border-color: rgba(212, 175, 55, 0.3);
            background: linear-gradient(135deg, var(--bg-card) 0%, var(--accent-gold-dim) 100%);
        }

        .winner-card.won::before {
            content: '🏆';
            position: absolute;
            top: 8px;
            right: 8px;
            font-size: 0.75rem;
        }

        .winner-period {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .winner-score {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .winner-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--accent-gold);
            margin-bottom: 4px;
        }

        .winner-prize {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* Leaderboard */
        .leaderboard {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .leaderboard-header {
            background: linear-gradient(135deg, var(--accent-gold) 0%, #b8962d 100%);
            color: var(--bg-dark);
            padding: 12px 16px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.1rem;
            letter-spacing: 0.1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .leaderboard-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .leaderboard-row:last-child {
            border-bottom: none;
        }

        .leaderboard-rank {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--bg-elevated);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            margin-right: 12px;
        }

        .leaderboard-row:first-child .leaderboard-rank {
            background: var(--accent-gold);
            color: var(--bg-dark);
        }

        .leaderboard-name {
            flex: 1;
            font-weight: 500;
        }

        .leaderboard-amount {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.1rem;
            color: var(--success);
        }

        /* Legend */
        .legend {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
            font-size: 0.7rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        .legend-dot.open { background: var(--bg-elevated); border: 1px solid var(--text-muted); }
        .legend-dot.pending { background: rgba(245, 158, 11, 0.4); }
        .legend-dot.locked { background: rgba(34, 197, 94, 0.3); }

        /* Purchase Button */
        .purchase-btn {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: linear-gradient(135deg, var(--accent-gold) 0%, #b8962d 100%);
            color: var(--bg-dark);
            border: none;
            padding: 16px 32px;
            border-radius: 50px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.25rem;
            letter-spacing: 0.1em;
            cursor: pointer;
            box-shadow: 0 4px 24px rgba(212, 175, 55, 0.4);
            transition: all 0.3s ease;
            z-index: 100;
            opacity: 0;
            pointer-events: none;
        }

        .purchase-btn.visible {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        .purchase-btn:hover {
            transform: translateX(-50%) scale(1.05);
            box-shadow: 0 6px 32px rgba(212, 175, 55, 0.6);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(8px);
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 32px;
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: modal-in 0.3s ease;
        }

        @keyframes modal-in {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.75rem;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            text-align: center;
        }

        .modal-summary {
            text-align: center;
            margin-bottom: 24px;
        }

        .modal-total {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2.5rem;
            color: var(--accent-gold);
        }

        .modal-squares {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .modal-payment {
            background: var(--bg-elevated);
            border-radius: var(--border-radius);
            padding: 16px;
            margin-bottom: 24px;
            text-align: center;
        }

        .modal-payment-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .modal-venmo-link {
            display: inline-block;
            background: #008CFF;
            color: white;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.3rem;
            letter-spacing: 0.05em;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .modal-venmo-link:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 20px rgba(0, 140, 255, 0.4);
        }

        .modal-venmo-link .venmo-logo {
            background: white;
            color: #008CFF;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: bold;
            margin-right: 8px;
        }

        .modal-note {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 12px;
        }

        .modal-input {
            width: 100%;
            padding: 14px 16px;
            background: var(--bg-elevated);
            border: 2px solid transparent;
            border-radius: var(--border-radius);
            color: var(--text-primary);
            font-size: 1rem;
            margin-bottom: 16px;
            transition: border-color 0.2s ease;
        }

        .modal-input:focus {
            outline: none;
            border-color: var(--accent-gold);
        }

        .modal-input::placeholder {
            color: var(--text-muted);
        }

        .modal-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--success) 0%, #16a34a 100%);
            border: none;
            border-radius: var(--border-radius);
            color: white;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.1rem;
            letter-spacing: 0.1em;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .modal-submit:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.4);
        }

        .modal-cancel {
            width: 100%;
            padding: 12px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 0.85rem;
            cursor: pointer;
            margin-top: 8px;
        }

        .modal-cancel:hover {
            color: var(--text-secondary);
        }

        /* Footer links */
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .footer-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.8rem;
            padding: 10px 20px;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 25px;
            transition: all 0.2s ease;
            background: var(--bg-card);
        }

        .footer-link:hover {
            color: var(--text-primary);
            border-color: var(--text-primary);
            background: var(--bg-elevated);
        }

        .footer-link.admin-link {
            border-color: rgba(212, 175, 55, 0.3);
        }

        .footer-link.admin-link:hover {
            border-color: var(--accent-gold);
            color: var(--accent-gold);
        }

        .text-center {
            text-align: center;
        }

        /* How to Play */
        .how-to-play {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            border: 1px solid rgba(255,255,255,0.05);
            overflow: hidden;
        }

        .how-to-play-header {
            padding: 14px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }

        .how-to-play-header:hover {
            background: var(--bg-elevated);
        }

        .how-to-play-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1rem;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .how-to-play-chevron {
            transition: transform 0.3s;
            color: var(--text-muted);
        }

        .how-to-play.open .how-to-play-chevron {
            transform: rotate(180deg);
        }

        .how-to-play-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .how-to-play.open .how-to-play-content {
            max-height: 500px;
        }

        .how-to-play-inner {
            padding: 0 20px 20px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .how-to-play-inner ol {
            padding-left: 20px;
            margin: 0;
        }

        .how-to-play-inner li {
            margin-bottom: 8px;
        }

        .how-to-play-inner strong {
            color: var(--accent-gold);
        }

        /* Mobile optimizations */
        @media (max-width: 500px) {
            .container {
                padding: 12px;
                padding-bottom: 100px;
            }

            .stats-bar {
                gap: 16px;
            }

            .winners-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .modal {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1 class="logo"><?= htmlspecialchars($game_name) ?></h1>
            <p class="subtitle">Super Bowl LIX</p>
        </header>

        <div class="stats-bar">
            <div class="stat">
                <div class="stat-value">$<?= $total_pot ?></div>
                <div class="stat-label">Total Pot</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?= $open_count ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?= $sold_count ?></div>
                <div class="stat-label">Claimed</div>
            </div>
            <div class="stat">
                <div class="stat-value">$<?= $price_per_square ?></div>
                <div class="stat-label">Per Square</div>
            </div>
        </div>

        <div class="grid-wrapper">
            <div class="top-team-label"><?= htmlspecialchars($meta['top_team']) ?></div>
            <div class="grid-with-side">
                <div class="side-team-label"><?= htmlspecialchars($meta['side_team']) ?></div>
                <div class="grid">
                    <div class="cell cell-header corner"></div>
                    <?php foreach($top_nums as $n): ?>
                        <div class="cell cell-header top"><?= $n ?></div>
                    <?php endforeach; ?>
                    
                    <?php for($row = 0; $row < 10; $row++): ?>
                        <div class="cell cell-header side"><?= $side_nums[$row] ?></div>
                        <?php for($col = 0; $col < 10; $col++): 
                            $idx = ($row * 10) + $col;
                            $s = $squares[$idx];
                            $is_winner = in_array($s['id'], $winning_square_ids);
                            $classes = ['cell', 'cell-square', $s['status']];
                            if ($is_winner) $classes[] = 'winner';
                        ?>
                            <div class="<?= implode(' ', $classes) ?>" data-id="<?= $s['id'] ?>">
                                <?php if ($is_winner): ?>
                                    <span class="winner-icon">🏆</span>
                                <?php endif; ?>
                                <?php if ($s['status'] !== 'open'): ?>
                                    <span class="cell-name"><?= htmlspecialchars(substr($s['name'], 0, 7)) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-dot open"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot pending"></div>
                <span>Pending Payment</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot locked"></div>
                <span>Sold</span>
            </div>
        </div>

        <div class="section-title">Score Results</div>
        <div class="winners-grid">
            <?php foreach(['q1', 'q2', 'q3', 'q4'] as $q): ?>
                <?php $w = $winners[$q] ?? null; ?>
                <div class="winner-card <?= $w ? 'won' : '' ?>">
                    <div class="winner-period"><?= $period_labels[$q] ?></div>
                    <?php if ($w): ?>
                        <div class="winner-score"><?= $w['score_top'] ?> - <?= $w['score_side'] ?></div>
                        <div class="winner-name"><?= htmlspecialchars($w['owner']) ?></div>
                        <div class="winner-prize">$<?= $w['prize'] ?></div>
                    <?php else: ?>
                        <div class="winner-score">—</div>
                        <div class="winner-name" style="color: var(--text-muted);">TBD</div>
                        <div class="winner-prize">$<?= $prizes[$q] ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($leaderboard)): ?>
            <div class="leaderboard">
                <div class="leaderboard-header">
                    <span>🏆</span> Winnings Leaderboard
                </div>
                <?php 
                arsort($leaderboard);
                $rank = 1;
                foreach($leaderboard as $name => $total): 
                ?>
                    <div class="leaderboard-row">
                        <span class="leaderboard-rank"><?= $rank++ ?></span>
                        <span class="leaderboard-name"><?= htmlspecialchars($name) ?></span>
                        <span class="leaderboard-amount">$<?= $total ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="how-to-play" id="howToPlay">
            <div class="how-to-play-header" onclick="document.getElementById('howToPlay').classList.toggle('open')">
                <span class="how-to-play-title">
                    <span>❓</span> How to Play
                </span>
                <svg class="how-to-play-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </div>
            <div class="how-to-play-content">
                <div class="how-to-play-inner">
                    <ol>
                        <li><strong>Buy squares</strong> — Tap empty squares to select them ($<?= $price_per_square ?> each), then complete your purchase</li>
                        <li><strong>Send payment</strong> — Venmo <strong><?= htmlspecialchars($venmo_handle) ?></strong> with only emojis in the note 🏈🍺</li>
                        <li><strong>Numbers assigned</strong> — Before kickoff, numbers 0-9 are randomly assigned to each row and column</li>
                        <li><strong>Win money!</strong> — At the end of each quarter, we look at the last digit of each team's score. The square at that intersection wins!</li>
                    </ol>
                    <p style="margin-top: 12px; color: var(--text-muted);">
                        <strong>Example:</strong> If <?= htmlspecialchars($meta['top_team']) ?> has 17 and <?= htmlspecialchars($meta['side_team']) ?> has 14, we find where column <strong>7</strong> meets row <strong>4</strong> — that person wins the quarter prize!
                    </p>
                    <p style="margin-top: 12px; color: var(--text-muted);">
                        <strong>Note:</strong> The Final prize is based on the final score of the game, including overtime if played.
                    </p>
                </div>
            </div>
        </div>

        <div class="footer-links">
            <a href="index.php" class="footer-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12a9 9 0 11-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/>
                    <path d="M21 3v5h-5"/>
                </svg>
                Refresh
            </a>
            <a href="admin.php" class="footer-link admin-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/>
                    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
                </svg>
                Admin
            </a>
        </div>
    </div>

    <button class="purchase-btn" id="purchaseBtn">
        Complete Purchase (<span id="selectedCount">0</span>)
    </button>

    <div class="modal-overlay" id="modal">
        <div class="modal">
            <h2 class="modal-title">Confirm Your Squares</h2>
            <div class="modal-summary">
                <div class="modal-total" id="modalTotal">$0</div>
                <div class="modal-squares" id="modalSquares">0 squares selected</div>
            </div>
            <div class="modal-payment">
                <div class="modal-payment-label">Send Payment To</div>
                <a href="#" id="venmoLink" class="modal-venmo-link" target="_blank">
                    <span class="venmo-logo">Venmo</span> <?= htmlspecialchars(ltrim($venmo_handle, '@')) ?>
                </a>
                <div class="modal-note">Tap above to pay — note is pre-filled! 🏈🍺</div>
            </div>
            <form method="POST" id="purchaseForm">
                <input type="hidden" name="squares" id="squaresInput">
                <input type="text" name="name" class="modal-input" placeholder="Enter your full name" required autocomplete="name">
                <button type="submit" class="modal-submit">I've Sent the Venmo ✓</button>
            </form>
            <button type="button" class="modal-cancel" onclick="closeModal()">Cancel</button>
        </div>
    </div>

    <script>
        const selected = new Map();
        const purchaseBtn = document.getElementById('purchaseBtn');
        const modal = document.getElementById('modal');
        const selectedCount = document.getElementById('selectedCount');
        const modalTotal = document.getElementById('modalTotal');
        const modalSquares = document.getElementById('modalSquares');
        const squaresInput = document.getElementById('squaresInput');
        const venmoLink = document.getElementById('venmoLink');
        const venmoHandle = '<?= htmlspecialchars(ltrim($venmo_handle, '@')) ?>';
        const pricePerSquare = <?= $price_per_square ?>;
        let selectionOrder = 0;

        document.querySelectorAll('.cell-square.open').forEach(cell => {
            cell.addEventListener('click', () => toggleCell(cell));
        });

        function toggleCell(cell) {
            const id = cell.dataset.id;
            if (selected.has(id)) {
                selected.delete(id);
                cell.classList.remove('selected');
                cell.innerHTML = '';
                // Renumber remaining cells
                renumberCells();
            } else {
                selectionOrder++;
                selected.set(id, selectionOrder);
                cell.classList.add('selected');
                cell.innerHTML = '<span class="select-number">' + selected.size + '</span>';
            }
            updateUI();
        }

        function renumberCells() {
            let num = 1;
            selected.forEach((order, id) => {
                const cell = document.querySelector(`.cell-square[data-id="${id}"]`);
                if (cell) {
                    cell.innerHTML = '<span class="select-number">' + num + '</span>';
                    selected.set(id, num);
                }
                num++;
            });
        }

        function updateUI() {
            const count = selected.size;
            selectedCount.textContent = count;
            purchaseBtn.classList.toggle('visible', count > 0);
        }

        purchaseBtn.addEventListener('click', () => {
            const count = selected.size;
            const total = count * pricePerSquare;
            modalTotal.textContent = '$' + total;
            modalSquares.textContent = count + ' square' + (count !== 1 ? 's' : '') + ' selected';
            squaresInput.value = Array.from(selected.keys()).join(',');
            
            // Build Venmo deep link with pre-filled amount and emoji-only note
            const venmoNote = encodeURIComponent('🏈🍺');
            venmoLink.href = `https://venmo.com/${venmoHandle}?txn=pay&amount=${total}&note=${venmoNote}`;
            
            modal.classList.add('active');
        });

        function closeModal() {
            modal.classList.remove('active');
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>
