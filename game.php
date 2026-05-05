<?php

function baseCards() {
    return [
        [
            'nom' => 'Katsushika Hokusai',
            'kind' => 'monster',
            'type' => 'monster',
            'region' => 'Asia',
            'atk' => 600,
            'def' => 1200,
            'img' => 'Cartes/Hokusai.jpeg',
            'effect' => 'hokusai'
        ],
        [
            'nom' => 'Tokugawa Ieyasu',
            'kind' => 'monster',
            'type' => 'monster',
            'region' => 'Asia',
            'atk' => 1200,
            'def' => 1700,
            'img' => 'Cartes/Tokugawa.jpeg',
            'effect' => 'tokugawa'
        ],
        [
            'nom' => 'Miyamoto Musashi',
            'kind' => 'monster',
            'type' => 'monster',
            'region' => 'Asia',
            'atk' => 1900,
            'def' => 0,
            'img' => 'Cartes/Miyamoto.jpeg',
            'effect' => null
        ],
        [
            'nom' => 'Qin Shi Huang',
            'kind' => 'monster',
            'type' => 'monster',
            'region' => 'Asia',
            'atk' => 1600,
            'def' => 1600,
            'img' => 'Cartes/QinShiHuang.jpeg',
            'effect' => null
        ],
        [
            'nom' => 'Confucius',
            'kind' => 'monster',
            'type' => 'monster',
            'region' => 'Asia',
            'atk' => 800,
            'def' => 1000,
            'img' => 'Cartes/Confucius.jpeg',
            'effect' => 'confucius'
        ],
        [
            'nom' => 'Gengis Khan',
            'kind' => 'monster',
            'type' => 'monster',
            'region' => 'Asia',
            'atk' => 1900,
            'def' => 0,
            'img' => 'Cartes/GengisKhan.jpeg',
            'effect' => 'gengis'
        ],
        [
            'nom' => 'Invasions Mongoles',
            'kind' => 'spell',
            'type' => 'magic',
            'spellType' => 'normal',
            'region' => 'Asia',
            'img' => 'Cartes/InvasionsMongoles.jpeg',
            'effect' => 'invasions'
        ],
        [
            'nom' => 'Grande Muraille de Chine',
            'kind' => 'field',
            'type' => 'magic',
            'spellType' => 'field',
            'region' => 'Asia',
            'img' => 'Cartes/GrandeMuraille.jpeg',
            'effect' => 'wall'
        ],
        [
            'nom' => "L'Art de la Guerre",
            'kind' => 'spell',
            'type' => 'magic',
            'spellType' => 'normal',
            'region' => 'Asia',
            'img' => 'Cartes/ArtDeLaGuerre.jpeg',
            'effect' => 'art'
        ],
        [
            'nom' => 'Bouddhisme',
            'kind' => 'spell',
            'type' => 'magic',
            'spellType' => 'normal',
            'region' => 'Asia',
            'img' => 'Cartes/Bouddhisme.jpeg',
            'effect' => 'buddhism'
        ],
        [
            'nom' => "Jeanne d'Arc",
            'kind' => 'monster',
            'type' => 'monster',
            'region' => 'Europe',
            'atk' => 1600,
            'def' => 1600,
            'img' => 'Cartes/JeanneDArc.jpeg',
            'effect' => null
        ],
        [
            'nom' => 'William Shakespeare',
            'kind' => 'monster',
            'type' => 'monster',
            'region' => 'Europe',
            'atk' => 1200,
            'def' => 1200,
            'img' => 'Cartes/Shakespeare.jpeg',
            'effect' => 'shakespeare'
        ],
        [
            'nom' => 'Galileo Galilei',
            'kind' => 'monster',
            'type' => 'monster',
            'region' => 'Europe',
            'atk' => 300,
            'def' => 1700,
            'img' => 'Cartes/Galileo.jpeg',
            'effect' => 'galileo'
        ],
        [
            'nom' => 'Napoléon Bonaparte',
            'kind' => 'monster',
            'type' => 'monster',
            'region' => 'Europe',
            'atk' => 2000,
            'def' => 1600,
            'img' => 'Cartes/Napoleon.jpeg',
            'effect' => null
        ],
        [
            'nom' => 'Charlemagne',
            'kind' => 'monster',
            'type' => 'monster',
            'region' => 'Europe',
            'atk' => 300,
            'def' => 1700,
            'img' => 'Cartes/Charlemagne.jpeg',
            'effect' => null
        ],
        [
            'nom' => 'Léonard de Vinci',
            'kind' => 'monster',
            'type' => 'monster',
            'region' => 'Europe',
            'atk' => 1000,
            'def' => 1500,
            'img' => 'Cartes/LeonardDeVinci.jpeg',
            'effect' => 'leonard'
        ],
        [
            'nom' => 'Héliocentrisme',
            'kind' => 'spell',
            'type' => 'magic',
            'spellType' => 'normal',
            'region' => 'Europe',
            'img' => 'Cartes/Heliocentrisme.jpeg',
            'effect' => 'heliocentrisme'
        ],
        [
            'nom' => 'Gravitation Universelle',
            'kind' => 'spell',
            'type' => 'magic',
            'spellType' => 'normal',
            'region' => 'Europe',
            'img' => 'Cartes/GravitationUniverselle.jpeg',
            'effect' => 'gravitation'
        ],
        [
            'nom' => 'Révolution Industrielle',
            'kind' => 'spell',
            'type' => 'magic',
            'spellType' => 'normal',
            'region' => 'Europe',
            'img' => 'Cartes/RevolutionIndustrielle.jpeg',
            'effect' => 'revolution'
        ],
        [
            'nom' => 'Bataille de Lépante',
            'kind' => 'field',
            'type' => 'magic',
            'spellType' => 'field',
            'region' => 'Europe',
            'img' => 'Cartes/BatailleDeLepante.jpeg',
            'effect' => 'lepante'
        ]
    ];
}

function normalizeCardStats(&$card) {
    if (!is_array($card)) {
        return;
    }

    if (($card['kind'] ?? '') !== 'monster') {
        return;
    }

    if (!isset($card['originalAtk'])) {
        $card['originalAtk'] = intval($card['atk'] ?? 0);
    }

    if (!isset($card['originalDef'])) {
        $card['originalDef'] = intval($card['def'] ?? 0);
    }
}

function makeDeck($player) {
    $region = $player === 'A' ? 'Asia' : 'Europe';
    $deck = [];
    $number = 1;

    foreach (baseCards() as $card) {
        if (($card['region'] ?? '') !== $region) {
            continue;
        }

        for ($copy = 1; $copy <= 2; $copy++) {
            $newCard = $card;
            $newCard['id'] = $player . '_' . $number . '_' . $copy;
            $newCard['owner'] = $player;
            normalizeCardStats($newCard);
            $deck[] = $newCard;
        }

        $number++;
    }

    shuffle($deck);
    return $deck;
}

function defaultGame() {
    $deckA = makeDeck('A');
    $deckB = makeDeck('B');
    $handA = array_splice($deckA, 0, 5);
    $handB = array_splice($deckB, 0, 5);

    return [
        'score' => ['A' => 0, 'B' => 0],
        'boards' => [
            'A' => [
                'monsters' => [null, null, null],
                'magics' => [null, null, null],
                'field' => null
            ],
            'B' => [
                'monsters' => [null, null, null],
                'magics' => [null, null, null],
                'field' => null
            ]
        ],
        'graveyards' => ['A' => [], 'B' => []],
        'decks' => ['A' => $deckA, 'B' => $deckB],
        'hands' => ['A' => $handA, 'B' => $handB],
        'turn' => 'A',
        'phase' => 'main',
        'turnNumber' => 1,
        'firstPlayer' => 'A',
        'hasDrawn' => false,
        'hasAttacked' => false,
        'hasPlayedCard' => false,
        'attacks' => [],
        'life' => ['A' => 4000, 'B' => 4000],
        'winner' => null,
        'lastPeek' => null
    ];
}

function pathGame() {
    return __DIR__ . '/data/game.json';
}

function normalizeCardsInList(&$cards) {
    if (!is_array($cards)) {
        $cards = [];
        return;
    }

    foreach ($cards as &$card) {
        normalizeCardStats($card);
    }
}

function normalize($game) {
    $base = defaultGame();

    if (!is_array($game)) {
        return $base;
    }

    foreach ([
        'score',
        'boards',
        'graveyards',
        'decks',
        'hands',
        'turn',
        'phase',
        'turnNumber',
        'firstPlayer',
        'hasDrawn',
        'hasAttacked',
        'hasPlayedCard',
        'attacks',
        'life',
        'winner',
        'lastPeek'
    ] as $key) {
        if (isset($game[$key])) {
            $base[$key] = $game[$key];
        }
    }

    foreach (['A', 'B'] as $player) {
        foreach (['monsters', 'magics'] as $zone) {
            if (!isset($base['boards'][$player][$zone]) || !is_array($base['boards'][$player][$zone])) {
                $base['boards'][$player][$zone] = [null, null, null];
            }

            $base['boards'][$player][$zone] = array_slice(
                array_pad($base['boards'][$player][$zone], 3, null),
                0,
                3
            );

            foreach ($base['boards'][$player][$zone] as &$card) {
                normalizeCardStats($card);
            }
        }

        if (!array_key_exists('field', $base['boards'][$player])) {
            $base['boards'][$player]['field'] = null;
        }
        normalizeCardStats($base['boards'][$player]['field']);

        if (!isset($base['graveyards'][$player])) {
            $base['graveyards'][$player] = [];
        }
        normalizeCardsInList($base['graveyards'][$player]);

        if (!isset($base['decks'][$player])) {
            $base['decks'][$player] = [];
        }
        normalizeCardsInList($base['decks'][$player]);

        if (!isset($base['hands'][$player])) {
            $base['hands'][$player] = [];
        }
        normalizeCardsInList($base['hands'][$player]);

        if (!isset($base['life'][$player])) {
            $base['life'][$player] = 4000;
        }
    }

    if (!in_array($base['phase'], ['draw', 'main', 'battle', 'end'], true)) {
        $base['phase'] = 'main';
    }

    if (!in_array($base['turn'], ['A', 'B'], true)) {
        $base['turn'] = 'A';
    }

    $base['turnNumber'] = max(1, intval($base['turnNumber']));
    $base['hasDrawn'] = (bool) $base['hasDrawn'];
    $base['hasAttacked'] = (bool) $base['hasAttacked'];
    $base['hasPlayedCard'] = (bool) $base['hasPlayedCard'];

    return $base;
}

function loadGame() {
    if (!file_exists(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0777, true);
    }

    $path = pathGame();

    if (!file_exists($path)) {
        $game = defaultGame();
        saveGame($game);
        return $game;
    }

    return normalize(json_decode(file_get_contents($path), true));
}

function saveGame($game) {
    file_put_contents(pathGame(), json_encode($game, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function opponent($player) {
    return $player === 'A' ? 'B' : 'A';
}

function validPlayer($player) {
    return in_array($player, ['A', 'B'], true);
}

function isFirstTurn($game) {
    return $game['turnNumber'] === 1 && $game['turn'] === $game['firstPlayer'];
}

function gyCount($game, $player) {
    return count($game['graveyards'][$player] ?? []);
}

function wallActive($game, $player) {
    return ($game['boards'][$player]['field']['effect'] ?? null) === 'wall';
}

function lepanteActive($game, $player) {
    return ($game['boards'][$player]['field']['effect'] ?? null) === 'lepante';
}

function effAtk($game, $player, $card) {
    return intval($card['atk'] ?? 0)
        + intval($card['atkBonus'] ?? 0)
        + (lepanteActive($game, $player) && ($card['region'] ?? '') === 'Europe' ? 300 : 0);
}

function effDef($game, $player, $card) {
    return intval($card['def'] ?? 0)
        + intval($card['defBonus'] ?? 0)
        + (wallActive($game, $player) ? 400 : 0)
        + (lepanteActive($game, $player) && ($card['region'] ?? '') === 'Europe' ? 200 : 0);
}

function sendGY(&$game, $player, $card) {
    if ($card !== null) {
        $game['graveyards'][$player][] = $card;
    }
}

function drawCards(&$game, $player, $number) {
    $drawn = 0;

    for ($i = 0; $i < $number; $i++) {
        if (count($game['decks'][$player]) === 0) {
            break;
        }

        $game['hands'][$player][] = array_shift($game['decks'][$player]);
        $drawn++;
    }

    return $drawn;
}

function clearEndTurnEffects(&$game, $player) {
    foreach (['monsters', 'magics'] as $zone) {
        for ($i = 0; $i < 3; $i++) {
            if (!isset($game['boards'][$player][$zone][$i]) || !is_array($game['boards'][$player][$zone][$i])) {
                continue;
            }

            unset(
                $game['boards'][$player][$zone][$i]['atkBonus'],
                $game['boards'][$player][$zone][$i]['defBonus'],
                $game['boards'][$player][$zone][$i]['extraAttack'],
                $game['boards'][$player][$zone][$i]['canAttackDefense'],
                $game['boards'][$player][$zone][$i]['effectUsedThisTurn'],
                $game['boards'][$player][$zone][$i]['gengisShieldUsed']
            );
        }
    }
}

function checkWin(&$game) {
    foreach (['A', 'B'] as $player) {
        if ($game['life'][$player] <= 0) {
            $game['life'][$player] = 0;
            $game['winner'] = opponent($player);
        }
    }
}

function findFirstSpellInDeck(&$game, $player) {
    foreach ($game['decks'][$player] as $index => $card) {
        if (($card['kind'] ?? '') === 'spell') {
            array_splice($game['decks'][$player], $index, 1);
            return $card;
        }
    }

    return null;
}

function discardRandom(&$game, $player) {
    if (count($game['hands'][$player]) === 0) {
        return null;
    }

    $index = array_rand($game['hands'][$player]);
    $card = $game['hands'][$player][$index];
    array_splice($game['hands'][$player], $index, 1);
    sendGY($game, $player, $card);

    return $card;
}
