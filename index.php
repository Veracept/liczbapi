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
    <style>
       /* Основная карточка результата */
.glass-card {
    background: rgba(15, 23, 42, 0.95); /* Очень темный синий фон */
    backdrop-filter: blur(10px);
    border: 1px solid rgba(56, 189, 248, 0.3); /* Тонкая голубая рамка */
    border-radius: 24px;
    padding: 40px 30px;
    max-width: 480px;
    width: 90%;
    margin: 0 auto;
    text-align: center;
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.6);
    color: #fff;
    font-family: 'Segoe UI', sans-serif;
}

/* Заголовок (DOBRY POCZĄTEK!) */
.result-title {
    color: #38bdf8; /* Голубой неон */
    text-transform: uppercase;
    font-weight: 700;
    font-size: 1.5rem;
    margin-bottom: 15px;
    letter-spacing: 1px;
}

/* Текст описания */
.result-desc {
    color: #cbd5e1; /* Светло-серый */
    font-size: 1rem;
    line-height: 1.5;
    margin-bottom: 25px;
}

/* Внутренний темный блок с очками */
.score-box {
    background: rgba(2, 6, 23, 0.5); /* Почти черный прозрачный фон */
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 25px;
}

/* Слово "Wynik" */
.score-label {
    font-size: 1.2rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 5px;
}

/* Сама цифра очков (Красная) */
.score-value {
    color: #f43f5e; /* Розово-красный */
    font-size: 2.2rem;
    font-weight: 800;
    margin-left: 8px;
}

/* Время игры */
.time-label {
    font-size: 0.9rem;
    color: #94a3b8; /* Серый текст */
    margin-top: 5px;
}

.time-label strong {
    color: #fff;
}

/* Ссылка внизу (Zagraj jeszcze raz) */
.replay-link {
    color: #818cf8; /* Фиолетово-синий */
    text-decoration: none;
    font-size: 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-block;
    padding: 5px 10px;
}

.replay-link:hover {
    color: #a5b4fc;
    text-shadow: 0 0 10px rgba(129, 140, 248, 0.5);
    transform: translateY(-1px);
} 

#pi-input {
    padding-left: 15px;
    padding-right: 15px; 
    
}
    </style>
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
            <div id="timer-box" style="color: #38bdf8; font-weight: bold; margin-top: 5px;">
            ⏱️ Czas: <span id="timer">0</span>s
            </div>
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
document.addEventListener('DOMContentLoaded', () => {

    const input = document.getElementById('pi-input');
    const scoreDisplay = document.getElementById('current-score');
    const livesDisplay = document.getElementById('lives');
    const timerDisplay = document.getElementById('timer');
    const card = document.getElementById('game-card'); 
    

    let currentIndex = 0;
    let secondsElapsed = 0;
    let timerInterval = null;
    let isGameActive = true;


    function startTimer() {
        if (!timerInterval && isGameActive) {
            timerInterval = setInterval(() => {
                secondsElapsed++;
                if (timerDisplay) timerDisplay.innerText = secondsElapsed;
            }, 1000);
        }
    }

    if (input) {
        input.addEventListener('focus', startTimer);
        input.addEventListener('click', startTimer);
        

        input.addEventListener('paste', e => {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Wklejanie zabronione!', 
                text: 'W tym konkursie liczy się pamięć. Wpisuj cyfry ręcznie!', 
                background: '#0f172a',
                color: '#fff',
                confirmButtonColor: '#38bdf8',
                confirmButtonText: 'Rozumiem'
            });
        });

        // 2. OBSŁUGA KLAWISZY (ОБРАБОТКА ВВОДА)
        input.addEventListener('keypress', function(e) {
            if (!isGameActive) {
                e.preventDefault();
                return;
            }

            // Tylko cyfry (Только цифры)
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
                return;
            }

            e.preventDefault();
            startTimer(); 

            const digit = e.key;

            // Wysyłanie do serwera (Шлем на сервер)
 fetch('verify.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=check&digit=${digit}&index=${currentIndex}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // SUKCES (УСПЕХ)
                    input.value += digit; 
                    
                    // --- ВОТ ЭТО ИСПРАВЛЕНИЕ ---
                    // Принудительно прокручиваем поле в самый конец
                    input.scrollLeft = input.scrollWidth; 
                    // ---------------------------

                    currentIndex++;
                    if(scoreDisplay) scoreDisplay.innerText = currentIndex;
                    
                    // Zielony błysk (Зеленая подсветка)
                    if(card) {
                        card.classList.add('correct-flash');
                        setTimeout(() => card.classList.remove('correct-flash'), 300);
                    }
                } else if (data.status === 'error') {
                    // ... (тут код ошибки остался без изменений)
                    if(livesDisplay) livesDisplay.innerText = data.lives;
                    
                    if(card) {
                        card.classList.add('error-shake');
                        setTimeout(() => card.classList.remove('error-shake'), 500);
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Błąd!',
                        text: `Zła cyfra. Pozostało prób: ${data.lives}`,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        background: '#1e293b',
                        color: '#fff'
                    });

                    if (data.gameOver) {
                        endGame(data.correctScore);
                    }
                }
            })
            .catch(err => {
                console.error("Błąd sieci:", err);
                Swal.fire({
                    icon: 'error', 
                    title: 'Błąd połączenia', 
                    text: 'Sprawdź połączenie z internetem lub bazą danych!',
                    background: '#0f172a',
                    color: '#fff'
                });
            });
        });
    }

    // 3. KONIEC GRY I ZAPIS (ЗАВЕРШЕНИЕ ИГРЫ И СОХРАНЕНИЕ)
    window.endGame = function(finalScore) {
        isGameActive = false;
        clearInterval(timerInterval);
        if (input) input.disabled = true;

        // Save Final
        fetch('verify.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=save_final&score=${finalScore}&time=${secondsElapsed}`
        })
        .then(res => res.json())
        .then(data => {
            showResultScreen(finalScore, secondsElapsed);
        })
        .catch(err => {
            console.error("Nie udało się zapisać:", err);
            showResultScreen(finalScore, secondsElapsed);
        });
    };

// Генератор текста на основе очков (Nowa funkcja)
    function getFeedback(score) {
        if (score < 10) {
            return {
                title: "DOBRY POCZĄTEK!",
                text: "Liczba Pi nie jest łatwa. Nie poddawaj się, trening czyni mistrza!."
            };
        } else if (score < 30) {
            return {
                title: "ŚWIETNA ROBOTA!",
                text: "Masz naprawdę niezłą pamięć! Twój wynik jest powyżej przeciętnej. "
            };
        } else if (score < 60) {
            return {
                title: "NIESAMOWITE!",
                text: "Wow! Twój mózg pracuje na najwyższych obrotach. Niewiele osób potrafi dojść tak daleko. Szacunek!"
            };
        } else {
            return {
                title: "JESTEŚ GENIUSZEM!",
                text: "To poziom mistrzowski! Jesteś jak żywy kalkulator. Ten wynik przejdzie do historii tego konkursu."
            };
        }
    }

    // НОВАЯ ФУНКЦИЯ ОТРИСОВКИ (Как на скрине 2)
    function showResultScreen(score, time) {
        const container = document.querySelector('.container');
        const feedback = getFeedback(score);

        if (container) {
            container.innerHTML = `
                <div class="glass-card animate__animated animate__fadeIn">
                    
                    <h2 class="result-title">${feedback.title}</h2>
                    
                    <p class="result-desc">
                        ${feedback.text}
                    </p>

                    <div class="score-box">
                        <div class="score-label">
                            Wynik: <span class="score-value">${score}</span>
                        </div>
                        <div class="time-label">
                            Czas gry: <strong>${time} s</strong>
                        </div>
                    </div>

                    <div style="border-top: 1px solid rgba(255,255,255,0.1); width: 80%; margin: 0 auto 20px auto;"></div>

                    <a href="index.php?logout=1" class="replay-link">
                        Wróc
                    </a>
                    
                </div>
            `;
        }
    }


    // Przycisk "Poddaj się" (Кнопка Сдаться)
    window.confirmGiveUp = function() {
        Swal.fire({
            title: 'Poddajesz się?',
            text: "Twój obecny wynik zostanie zapisany, ale nie będziesz mógł go już poprawić.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f43f5e',
            cancelButtonColor: '#38bdf8',
            confirmButtonText: 'Tak, kończę grę',
            cancelButtonText: 'Nie, gram dalej!',
            background: '#0f172a',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                endGame(currentIndex);
            }
        });
    };
});
</script>
</body>
</html>