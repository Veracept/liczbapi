<?php
session_start();

$db = new mysqli("localhost", "root", "password", "pi_challenge");


if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if (isset($_POST['start'])) {
    $name = htmlspecialchars($_POST['name']);
    $surname = htmlspecialchars($_POST['surname']);
    $code = strtoupper(substr(md5($name . $surname), 0, 8));
    

    $check = $db->prepare("SELECT id FROM results WHERE unique_code = ?");
    $check->bind_param("s", $code);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $already_played = true;
    } else {

        $_SESSION['name'] = $name;
        $_SESSION['surname'] = $surname;
        $_SESSION['code'] = $code;
        $_SESSION['attempts'] = 3;
        $_SESSION['score'] = 0;
        

        $stmt = $db->prepare("INSERT INTO results (name, surname, unique_code, score) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $name, $surname, $code);
        $stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Święto Liczby Pi</title>
    
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<header class="top-banner">
    <div class="banner-content">
        <img  alt="Logo" src="https://us.edu.pl/wp-content/uploads/strona-g%C5%82%C3%B3wna/logo/logo-U%C5%9A.png"> 
        <h1>Święto Liczby <span style="color:#38bdf8">Pi</span></h1>
    </div>
</header>

<?php if (!isset($_SESSION['name']) || isset($already_played)): ?>
    <div class="container">
        <div class="glass-card animate__animated animate__fadeInDown">
            <?php if(isset($already_played)): ?>
                <h2 style="color: #f43f5e;">Już brałeś udział!</h2><br>
                <p>Każdy uczestnik konkursu ma tylko jedną szansę.</p><br>
                <button onclick="window.location.href='index.php?logout=1'">Powrót do menu</button>
            <?php else: ?>
                <h2>Rejestracja <span class="accent">Konkursowa</span></h2>
                <p style="margin-bottom: 20px; opacity: 0.8;">Wpisz swoje dane, aby rozpocząć wyzwanie.</p>
                <form method="post">
                    <input type="text" name="name" placeholder="Imię" required>
                    <input type="text" name="surname" placeholder="Nazwisko" required>
                    <button type="submit" name="start">Zarejestruj się i graj</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="user-info animate__animated animate__fadeInRight">
        <div>
            <p>👤 <?php echo $_SESSION['name'] . " " . $_SESSION['surname']; ?></p>
            <small>Kod: <?php echo $_SESSION['code']; ?></small>
        </div>
        <a href="?logout=1" class="logout-link">Wyloguj</a>
    </div>

    <div class="container">
        <div id="game-card" class="glass-card animate__animated">
            <h3>Wprowadź kolejne cyfry PI</h3><br>
            <br>
            <div class="stats-bar">
                <span>Szansy: <strong id="lives"><?php echo $_SESSION['attempts']; ?></strong></span>
                <span>Twój wynik: <strong id="current-score">0</strong></span>
            </div>
            <br>
            <br>
            <div class="input-group">
                <span class="pi-prefix">3.</span>
                <input type="text" id="pi-input" placeholder="..." autocomplete="off" autofocus>
            </div>
<br><br>
            <button class="btn-finish" onclick="confirmGiveUp()">Zakończ grę</button>
        </div>
    </div>
<?php endif; ?>

<footer class="animate__animated animate__fadeIn">
    &copy; 2026 Uniwersytet Śląski. Wszelkie prawa zastrzeżone.
</footer>

<script>
const input = document.getElementById('pi-input');
const card = document.getElementById('game-card');
const livesDisplay = document.getElementById('lives');
const scoreDisplay = document.getElementById('current-score');

let currentIndex = 0;

if (input) {
    input.addEventListener('keypress', function(e) {

        if (!/[0-9]/.test(e.key)) {
            e.preventDefault();
            return;
        }
        
        const digit = e.key;
        e.preventDefault(); 

        fetch('verify.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=check&digit=${digit}&index=${currentIndex}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                currentIndex++;
                scoreDisplay.innerText = data.score;
                input.value += digit;
                
            
                card.classList.add('correct-flash');
                setTimeout(() => card.classList.remove('correct-flash'), 500);
                
            } else {
                livesDisplay.innerText = data.lives;
                notifyError(data.lives);
                
                card.classList.add('error-shake');
                setTimeout(() => card.classList.remove('error-shake'), 500);

                if (data.gameOver) {
                    showFinalTable(data.correct);
                }
            }
        });
    });
}

input.addEventListener('paste', function(e) {
    e.preventDefault(); 
    
    Swal.fire({
        title: 'Wklejanie zabronione!',
        text: 'W konkursie liczy się Twoja pamięć. Prosimy o ręczne wpisywanie cyfr!',
        icon: 'warning',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#38bdf8',
        confirmButtonText: 'Rozumiem'
    });
});


input.addEventListener('contextmenu', function(e) {
    e.preventDefault();
});


function notifyError(lives) {
    Swal.fire({
        title: 'Błędna cyfra!',
        text: `Pozostało prób: ${lives}`,
        icon: 'error',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#38bdf8',
        timer: 1500,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

function showFinalTable(score) {
    let msg = "";
    if (score == 0) msg = "Dziękujemy za udział! Każdy początek jest trudny.";
    else if (score < 10) msg = "Dobry wynik! Liczba Pi skrywa jeszcze wiele tajemnic.";
    else if (score < 30) msg = "Niesamowite! Masz świetną pamięć!";
    else msg = "Genialnie! Jesteś prawdziwym mistrzem liczby Pi!";

    document.querySelector('.container').innerHTML = `
        <div class="glass-card animate__animated animate__zoomIn">
            <h2 style="color: #38bdf8">Gra zakończona</h2>
            <p style="margin: 10px 0;">Twój wynik: <span style="font-size: 2.5rem; font-weight: 800; display: block; color: white;">${score}</span></p>
            <hr style="opacity: 0.1; margin: 20px 0;">
            <p>${msg}</p>
            <p style="font-size: 0.8rem; margin-top: 25px; opacity: 0.6;">Wynik został trwale zapisany w systemie konkursowym.</p>
            <button onclick="window.location.href='index.php?logout=1'" style="margin-top: 20px;">Wyjdź</button>
        </div>
    `;
}

function confirmGiveUp() {
    Swal.fire({
        title: 'Zakończyć grę?',
        text: "Twój obecny wynik zostanie zapisany. Nie będzie można spróbować ponownie!",
        icon: 'warning',
        showCancelButton: true,
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#38bdf8',
        confirmButtonText: 'Tak, zakończ',
        cancelButtonText: 'Anuluj'
    }).then((result) => {
        if (result.isConfirmed) {
            const finalScore = scoreDisplay.innerText;
            showFinalTable(finalScore);
        }
    });
}
</script>
</body>
</html>