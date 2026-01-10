<?php
session_start();


$db = new mysqli("localhost", "root", "password", "pi_challenge");


$PI_DIGITS = "1415926535897932384626433832795028841971"; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'check') {
        $input = $_POST['digit']; 
        $index = intval($_POST['index']); 

        if ($input === $PI_DIGITS[$index]) {
            $_SESSION['score'] = $index + 1;
            echo json_encode(['status' => 'success', 'score' => $_SESSION['score']]);
        } else {
            $_SESSION['attempts']--;
            $gameOver = ($_SESSION['attempts'] <= 0);
            
   

            if ($gameOver) {
                $stmt = $db->prepare("UPDATE results SET score = ? WHERE unique_code = ?");
                $stmt->bind_param("is", $_SESSION['score'], $_SESSION['code']);
                $stmt->execute();
            }

            echo json_encode([
                'status' => 'error', 
                'lives' => $_SESSION['attempts'],
                'gameOver' => $gameOver,
                'correct' => $_SESSION['score']
            ]);
        }
    }
}