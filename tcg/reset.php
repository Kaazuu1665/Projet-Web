<?php
header('Content-Type: application/json');

require_once __DIR__ . '/game.php';

$game = defaultGame();
saveGame($game);

echo json_encode([
    'success' => true,
    'game' => $game
], JSON_UNESCAPED_UNICODE);
