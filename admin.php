<?php
session_start();


if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['password']) && $_POST['password'] === 'TwojeTajneHaslo123') { 
        $_SESSION['admin_logged_in'] = true;
    } else {
        echo '
        <body style="background:#020617; display:flex; height:100vh; align-items:center; justify-content:center; font-family:sans-serif;">
            <form method="post" style="text-align:center;">
                <input type="password" name="password" placeholder="Hasło administratora" style="padding:10px; border-radius:5px; border:none;">
                <button type="submit" style="padding:10px 20px; background:#38bdf8; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">Wejdź</button>
            </form>
        </body>';
        exit;
    }
}

$db = new mysqli("localhost", "root", "password", "pi_challenge");
$db->set_charset("utf8mb4");

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';


$sql = "SELECT * FROM results WHERE (name LIKE ? OR surname LIKE ? OR unique_code LIKE ?)";


switch ($sort) {
    case 'best_score':
        $sql .= " ORDER BY score DESC, time_spent ASC"; 
        break;
    case 'best_time':

        $sql .= " ORDER BY CASE WHEN score > 0 THEN 0 ELSE 1 END, time_spent ASC, score DESC"; 
        break;
    case 'oldest':
        $sql .= " ORDER BY id ASC"; 
        break;
    default: 
        $sql .= " ORDER BY id DESC"; 
        break;
}

$stmt = $db->prepare($sql);
$searchTerm = "%$search%";
$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - PI</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        .admin-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            background: rgba(0,0,0,0.3);
            padding: 20px;
            border-radius: 15px;
        }
        .admin-input {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(56, 189, 248, 0.3);
            background: rgba(15, 23, 42, 0.8);
            color: white;
            outline: none;
        }
        .admin-btn {
            padding: 12px 25px;
            background: var(--primary-blue);
            color: #000;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            color: #e2e8f0;
        }
        .data-table th {
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid #38bdf8;
            color: #38bdf8;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .data-table tr:hover {
            background: rgba(255,255,255,0.05);
        }
        .badge-score {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="top-banner">
        <div class="banner-content">
            <h1>Panel <span style="color:#38bdf8">Admina</span></h1>
        </div>
        <div style="position: absolute; right: 30px;">
            <a href="index.php" style="color: white; margin-right: 20px; text-decoration: none;">Home</a>
            <a href="?logout=1" style="color: #f43f5e; text-decoration: none;">Log out</a>
        </div>
    </div>

    <div class="container" style="align-items: flex-start; padding-top: 120px;">
        <div class="glass-card" style="max-width: 1000px; width: 95%;">
            
            <form method="GET" class="admin-controls">
                <input type="text" name="search" class="admin-input" style="flex: 1;" placeholder="Szukaj (Imię, Nazwisko, Kod)..." value="<?= htmlspecialchars($search) ?>">
                
                <select name="sort" class="admin-input" onchange="this.form.submit()" style="cursor: pointer;">
                    <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>▼ Najnowsi (domyślne)</option>
                    <option value="best_score" <?= $sort == 'best_score' ? 'selected' : '' ?>>★ Najlepszy wynik</option>
                    <option value="best_time" <?= $sort == 'best_time' ? 'selected' : '' ?>>⏱️ Najlepszy czas</option>
                    <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>▲ Najstarsi</option>
                </select>

                <button type="submit" class="admin-btn">Szukaj / Odśwież</button>
            </form>

            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Uczestnik</th>
                            <th>Unikalny Kod</th>
                            <th>Wynik (PI)</th>
                            <th>Czas</th>
                            <th>Data</th>
                            <th>Akcja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($results->num_rows > 0): ?>
                            <?php while($row = $results->fetch_assoc()): ?>
                            <tr>
                                <td style="opacity: 0.5;"><?= $row['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['name'] . ' ' . $row['surname']) ?></strong>
                                </td>
                                <td><code style="background:rgba(0,0,0,0.3); padding:3px 6px; border-radius:4px;"><?= $row['unique_code'] ?></code></td>
                                <td>
                                    <span class="badge-score"><?= $row['score'] ?></span>
                                </td>
                                <td><?= $row['time_spent'] ?> s</td>
                                <td style="font-size: 0.85rem; opacity: 0.7;"><?= $row['created_at'] ?></td>
                                <td>
                                    <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Usunąć ten wynik?');" style="color: #f43f5e; font-weight: bold; text-decoration: none;">Usuń</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding: 30px; opacity: 0.6;">Brak wyników</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</body>
</html>