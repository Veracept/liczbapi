<?php
session_start();


$admin_password = "TwojeTajneHaslo123"; 
$db = new mysqli("localhost", "root", "password", "pi_challenge");

if (isset($_POST['login'])) {
    if ($_POST['pass'] === $admin_password) {
        $_SESSION['admin_logged'] = true;
    } else {
        $error = "Błędne hasło!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
}

if (isset($_GET['delete']) && isset($_SESSION['admin_logged'])) {
    $id = intval($_GET['delete']);
    $db->query("DELETE FROM results WHERE id = $id");
    header("Location: admin.php");
}


if (isset($_POST['update_score']) && isset($_SESSION['admin_logged'])) {
    $id = intval($_POST['id']);
    $new_score = intval($_POST['score']);
    $db->query("UPDATE results SET score = $new_score WHERE id = $id");
    exit("success");
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel Administratora - Pi Challenge</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; color: white; }
        .admin-table th, .admin-table td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left; }
        .admin-table tr:hover { background: rgba(255,255,255,0.05); }
        .btn-del { color: #ef4444; text-decoration: none; font-weight: bold; }
        .edit-input { background: transparent; border: 1px solid #38bdf8; color: white; width: 50px; padding: 5px; border-radius: 5px; }
        .container { max-width: 900px; }


    </style>
</head>
<body>

<div class="container">
    <?php if (!isset($_SESSION['admin_logged'])): ?>
        <div class="glass-card animate__animated animate__fadeIn">
            <h2>Panel <span class="accent">Admina</span></h2>
            <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
            <form method="post">
                <input type="password" name="pass" placeholder="Hasło administratora" required>
                <button type="submit" name="login">Zaloguj się</button>
            </form>
        </div>
    <?php else: ?>
        <div class="glass-card animate__animated animate__fadeIn" style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Lista <span class="accent">Wyników</span></h2>
                <a href="?logout" style="color: #38bdf8; text-decoration: none;">Wyloguj</a>
            </div>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imię i Nazwisko</th>
                        <th>Kod</th>
                        <th>Wynik</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = $db->query("SELECT * FROM results ORDER BY created_at DESC");
                    while ($row = $res->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['name'] . " " . $row['surname']; ?></td>
                        <td><code><?php echo $row['unique_code']; ?></code></td>
                        <td>
                            <input type="number" class="edit-input" value="<?php echo $row['score']; ?>" 
                                   onchange="updateScore(<?php echo $row['id']; ?>, this.value)">
                        </td>
                        <td>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn-del" onclick="return confirm('Usunąć?')">Usuń</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function updateScore(id, newScore) {
    const formData = new FormData();
    formData.append('update_score', '1');
    formData.append('id', id);
    formData.append('score', newScore);

    fetch('admin.php', {
        method: 'POST',
        body: formData
    }).then(res => {
        if(res.ok) alert('Wynik zaktualizowany!');
    });
}
</script>

</body>
</html>