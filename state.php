<?php
header('Content-Type: application/json');

require_once __DIR__ . '/game.php';

$game = loadGame();
saveGame($game);

echo json_encode($game, JSON_UNESCAPED_UNICODE);
