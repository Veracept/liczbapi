<?php
session_start();
header('Content-Type: application/json');

// Отключаем прямой вывод ошибок, чтобы не ломать JSON-ответ
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    $db = new mysqli("localhost", "root", "password", "pi_challenge");
    $db->set_charset("utf8mb4");
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Connection Fail']);
    exit;
}

// 2. ИНИЦИАЛИЗАЦИЯ СЕССИИ (Если игра только началась)
if (!isset($_SESSION['attempts'])) {
    $_SESSION['attempts'] = 3;
    $_SESSION['score'] = 0;
}

$action = $_POST['action'] ?? '';


if ($action === 'check') {
    $digit = $_POST['digit'] ?? '';
    $index = (int)($_POST['index'] ?? 0);
    $file_path = 'pi.txt';

    if (!file_exists($file_path)) {
        echo json_encode(['status' => 'error', 'message' => 'pi.txt not found']);
        exit;
    }

    // Эффективное чтение: прыгаем сразу на нужный символ
    $f = fopen($file_path, 'r');
    fseek($f, $index); 
    $correct_digit = fread($f, 1);
    fclose($f);

    if ($correct_digit === $digit) {
        $_SESSION['score'] = $index + 1;
        echo json_encode([
            'status' => 'success', 
            'score' => $_SESSION['score']
        ]);
    } else {
        $_SESSION['attempts']--;
        $gameOver = $_SESSION['attempts'] <= 0;
        
        // Если проиграл, сбрасываем попытки для следующей игры
        if ($gameOver) {
            $finalScore = $_SESSION['score'];
            $_SESSION['attempts'] = 3;
            $_SESSION['score'] = 0;
        }

        echo json_encode([
            'status' => 'error', 
            'lives' => $_SESSION['attempts'], 
            'gameOver' => $gameOver,
            'correctScore' => $gameOver ? $finalScore : $_SESSION['score']
        ]);
    }
    exit;
}

// --- ЛОГИКА СОХРАНЕНИЯ РЕЗУЛЬТАТА ---
if ($action === 'save_final') {
    $finalScore = (int)($_POST['score'] ?? 0);
    $timeSpent = (int)($_POST['time'] ?? 0);
    $userCode = $_SESSION['code'] ?? ''; 

    if ($userCode) {
        // Обновляем результат конкретного пользователя по его коду
        $stmt = $db->prepare("UPDATE results SET score = ?, time_spent = ? WHERE unique_code = ?");
        $stmt->bind_param("iis", $finalScore, $timeSpent, $userCode);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'saved', 'score' => $finalScore, 'time' => $timeSpent]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'SQL Error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No Session Code']);
    }
    exit;
}
?>