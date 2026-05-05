<?php
header('Content-Type: application/json');

require_once __DIR__ . '/game.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['action'])) {
    fail('Action invalide');
}

$game = loadGame();
$action = $data['action'];

if ($game['winner']) {
    fail('Partie terminée. Gagnant : Joueur ' . $game['winner']);
}

switch ($action) {
    case 'draw':
        drawAction($game, $data);
        break;

    case 'nextPhase':
        nextPhaseAction($game, $data);
        break;

    case 'playCard':
        playCardAction($game, $data);
        break;

    case 'activateHand':
        activateHandAction($game, $data);
        break;

    case 'activateBoard':
        activateBoardAction($game, $data);
        break;

    case 'attack':
        attackAction($game, $data);
        break;

    default:
        fail('Action inconnue');
}

function fail($message) {
    echo json_encode([
        'success' => false,
        'error' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function ok($game, $message = '') {
    saveGame($game);

    echo json_encode([
        'success' => true,
        'game' => $game,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function requireCurrentPlayer($game, $player) {
    if (!validPlayer($player)) {
        fail('Joueur inconnu');
    }

    if ($game['turn'] !== $player) {
        fail("Ce n'est pas ton tour");
    }
}

function drawAction($game, $data) {
    $player = $data['player'] ?? null;
    requireCurrentPlayer($game, $player);

    if ($game['phase'] !== 'draw') {
        fail('Tu ne peux piocher qu’en Draw Phase');
    }

    if (isFirstTurn($game)) {
        fail('Le joueur qui commence ne pioche pas au premier tour');
    }

    if ($game['hasDrawn']) {
        fail('Tu as déjà pioché ce tour');
    }

    drawCards($game, $player, 1);
    $game['hasDrawn'] = true;
    $game['phase'] = 'main';

    ok($game, 'Carte piochée. Main Phase.');
}

function nextPhaseAction($game, $data) {
    $player = $data['player'] ?? null;
    requireCurrentPlayer($game, $player);

    if ($game['phase'] === 'draw') {
        if (!$game['hasDrawn'] && !isFirstTurn($game)) {
            fail('Tu dois piocher avant de passer en Main Phase');
        }

        $game['phase'] = 'main';
        ok($game, 'Main Phase.');
    }

    if ($game['phase'] === 'main') {
        if (isFirstTurn($game)) {
            $game['phase'] = 'end';
            ok($game, 'Battle Phase sautée au premier tour.');
        }

        $game['phase'] = 'battle';
        ok($game, 'Battle Phase.');
    }

    if ($game['phase'] === 'battle') {
        $game['phase'] = 'end';
        ok($game, 'End Phase.');
    }

    if ($game['phase'] === 'end') {
        clearEndTurnEffects($game, $game['turn']);

        $game['turn'] = opponent($game['turn']);
        $game['phase'] = 'draw';
        $game['turnNumber']++;
        $game['hasDrawn'] = false;
        $game['hasAttacked'] = false;
        $game['hasPlayedCard'] = false;
        $game['attacks'] = [];

        ok($game, 'Nouveau tour : Draw Phase.');
    }

    fail('Phase inconnue');
}

function playCardAction($game, $data) {
    $player = $data['player'] ?? null;
    $handIndex = intval($data['handIndex'] ?? -1);
    $zone = $data['zone'] ?? '';
    $slot = isset($data['slot']) ? intval($data['slot']) : null;
    $position = $data['position'] ?? 'attack';

    requireCurrentPlayer($game, $player);

    if ($game['phase'] !== 'main') {
        fail('Tu peux poser des cartes uniquement en Main Phase');
    }

    if (!isset($game['hands'][$player][$handIndex])) {
        fail('Carte introuvable dans la main');
    }

    $card = $game['hands'][$player][$handIndex];
    $kind = $card['kind'] ?? 'monster';

    if ($kind === 'field') {
        if ($zone !== 'field') {
            fail('Cette carte Terrain doit être posée dans la zone Terrain');
        }

        if ($game['boards'][$player]['field'] !== null) {
            sendGY($game, $player, $game['boards'][$player]['field']);
        }

        $game['boards'][$player]['field'] = $card;
        array_splice($game['hands'][$player], $handIndex, 1);

        ok($game, 'Carte Terrain posée.');
    }

    if ($kind === 'monster') {
        if ($game['hasPlayedCard']) {
            fail('Tu as déjà posé un personnage ce tour');
        }

        if ($zone !== 'monsters') {
            fail('Ce personnage doit aller dans une zone personnage');
        }

        if ($slot < 0 || $slot > 2) {
            fail('Slot invalide');
        }

        if ($game['boards'][$player]['monsters'][$slot] !== null) {
            fail('Slot déjà occupé');
        }

        $card['position'] = $position === 'defense' ? 'defense' : 'attack';
        $game['boards'][$player]['monsters'][$slot] = $card;
        array_splice($game['hands'][$player], $handIndex, 1);
        $game['hasPlayedCard'] = true;

        ok($game, 'Personnage posé.');
    }

    if ($kind === 'spell') {
        fail('Utilise le bouton Activer pour les cartes magie normales');
    }

    fail('Type de carte inconnu');
}

function activateHandAction($game, $data) {
    $player = $data['player'] ?? null;
    $handIndex = intval($data['handIndex'] ?? -1);
    $target = isset($data['target']) ? intval($data['target']) : -1;

    requireCurrentPlayer($game, $player);

    if ($game['phase'] !== 'main') {
        fail('Les magies s’activent en Main Phase');
    }

    if (!isset($game['hands'][$player][$handIndex])) {
        fail('Carte introuvable');
    }

    $card = $game['hands'][$player][$handIndex];
    $kind = $card['kind'] ?? '';
    $effect = $card['effect'] ?? '';

    if ($kind === 'field') {
        fail('Les cartes Terrain se posent dans la zone Terrain');
    }

    if ($kind !== 'spell') {
        fail('Cette carte ne s’active pas depuis la main');
    }

    if (in_array($effect, ['invasions', 'art'], true)) {
        if ($target < 0 || $target > 2 || $game['boards'][$player]['monsters'][$target] === null) {
            fail('Choisis un de tes personnages comme cible');
        }

        if ($effect === 'invasions') {
            $game['boards'][$player]['monsters'][$target]['extraAttack'] = true;
        }

        if ($effect === 'art') {
            $game['boards'][$player]['monsters'][$target]['canAttackDefense'] = true;
        }
    }

    if ($effect === 'buddhism') {
        $names = array_map(
            fn($deckCard) => $deckCard['nom'] ?? 'Carte inconnue',
            array_slice($game['decks'][$player], 0, 3)
        );
        $game['lastPeek'] = $player . ' : ' . implode(' / ', $names);
    }

    if ($effect === 'heliocentrisme') {
        $opponent = opponent($player);

        if ($target < 0 || $target > 2 || $game['boards'][$opponent]['monsters'][$target] === null) {
            fail('Choisis un personnage adverse comme cible');
        }

        $tmp = $game['boards'][$opponent]['monsters'][$target]['atk'] ?? 0;
        $game['boards'][$opponent]['monsters'][$target]['atk'] = $game['boards'][$opponent]['monsters'][$target]['def'] ?? 0;
        $game['boards'][$opponent]['monsters'][$target]['def'] = $tmp;
    }

    if ($effect === 'gravitation') {
        $discard = discardRandom($game, opponent($player));

        if ($discard === null) {
            fail('La main adverse est vide');
        }
    }

    if ($effect === 'revolution') {
        drawCards($game, $player, 1);
    }

    array_splice($game['hands'][$player], $handIndex, 1);
    sendGY($game, $player, $card);

    ok($game, 'Magie activée.');
}

function activateBoardAction($game, $data) {
    $player = $data['player'] ?? null;
    $slot = intval($data['slot'] ?? -1);
    $target = isset($data['target']) ? intval($data['target']) : -1;

    requireCurrentPlayer($game, $player);

    if ($game['phase'] !== 'main') {
        fail('Les effets s’activent en Main Phase');
    }

    if ($slot < 0 || $slot > 2 || $game['boards'][$player]['monsters'][$slot] === null) {
        fail('Personnage introuvable');
    }

    $card =& $game['boards'][$player]['monsters'][$slot];
    $effect = $card['effect'] ?? '';

    if ($card['effectUsedThisTurn'] ?? false) {
        fail('Effet déjà utilisé ce tour');
    }

    if ($effect === 'tokugawa') {
        $game['life'][$player] += 200;
        $card['effectUsedThisTurn'] = true;
        ok($game, 'Tokugawa : +200 PV.');
    }

    if ($effect === 'confucius') {
        $dead = $card;
        $game['boards'][$player]['monsters'][$slot] = null;
        sendGY($game, $player, $dead);
        drawCards($game, $player, 2);
        ok($game, 'Confucius sacrifié : pioche 2 cartes.');
    }

    if ($effect === 'hokusai') {
        if ($target < 0 || $target > 2 || $game['boards'][$player]['monsters'][$target] === null) {
            fail('Choisis un de tes personnages comme cible');
        }

        $bonus = gyCount($game, $player) * 50;
        $game['boards'][$player]['monsters'][$target]['atkBonus'] = ($game['boards'][$player]['monsters'][$target]['atkBonus'] ?? 0) + $bonus;
        $game['boards'][$player]['monsters'][$target]['defBonus'] = ($game['boards'][$player]['monsters'][$target]['defBonus'] ?? 0) + $bonus;
        $card['effectUsedThisTurn'] = true;

        ok($game, 'Hokusai : bonus appliqué.');
    }

    if ($effect === 'shakespeare') {
        if (count($game['graveyards'][$player]) === 0) {
            fail('Ton cimetière est vide');
        }

        $picked = array_pop($game['graveyards'][$player]);
        $game['hands'][$player][] = $picked;
        $card['effectUsedThisTurn'] = true;

        ok($game, 'Shakespeare : une carte du Cimetière rejoint ta main.');
    }

    if ($effect === 'galileo') {
        $opponent = opponent($player);

        if ($target < 0 || $target > 2 || $game['boards'][$opponent]['monsters'][$target] === null) {
            fail('Choisis un personnage adverse comme cible');
        }

        $sacrificed = $card;
        $destroyed = $game['boards'][$opponent]['monsters'][$target];
        $game['boards'][$player]['monsters'][$slot] = null;
        $game['boards'][$opponent]['monsters'][$target] = null;
        sendGY($game, $player, $sacrificed);
        sendGY($game, $opponent, $destroyed);

        ok($game, 'Galilée : sacrifice réussi, personnage adverse détruit.');
    }

    if ($effect === 'leonard') {
        $found = findFirstSpellInDeck($game, $player);

        if ($found === null) {
            fail('Aucune carte magie trouvée dans ton deck');
        }

        $game['hands'][$player][] = $found;
        shuffle($game['decks'][$player]);
        $card['effectUsedThisTurn'] = true;

        ok($game, 'Léonard de Vinci : une magie ajoutée à ta main, deck mélangé.');
    }

    fail('Cette carte n’a pas encore d’effet activable.');
}

function attackAction($game, $data) {
    $player = $data['player'] ?? null;
    $attackerSlot = intval($data['attacker'] ?? -1);
    $targetPlayer = $data['targetPlayer'] ?? opponent($player);
    $targetSlot = isset($data['targetSlot']) ? intval($data['targetSlot']) : -1;

    requireCurrentPlayer($game, $player);

    if (!validPlayer($targetPlayer)) {
        fail('Joueur inconnu');
    }

    if ($game['phase'] !== 'battle') {
        fail('Tu peux attaquer uniquement en Battle Phase');
    }

    if (isFirstTurn($game)) {
        fail('Le joueur qui commence ne peut pas attaquer au premier tour');
    }

    if ($attackerSlot < 0 || $attackerSlot > 2 || $game['boards'][$player]['monsters'][$attackerSlot] === null) {
        fail('Attaquant introuvable');
    }

    $opponent = opponent($player);

    if ($targetPlayer !== $opponent) {
        fail('Tu dois attaquer l’adversaire');
    }

    $attacker = $game['boards'][$player]['monsters'][$attackerSlot];
    $attackerId = $attacker['id'] ?? ('slot_' . $attackerSlot);

    if (($attacker['position'] ?? 'attack') === 'defense' && !($attacker['canAttackDefense'] ?? false)) {
        fail('Ce personnage en défense ne peut pas attaquer');
    }

    $maxAttacks = ($attacker['extraAttack'] ?? false) ? 2 : 1;
    $usedAttacks = $game['attacks'][$attackerId] ?? 0;

    if ($usedAttacks >= $maxAttacks) {
        fail('Ce personnage a déjà attaqué');
    }

    if ($targetSlot === -1) {
        directAttack($game, $player, $opponent, $attacker, $attackerId, $usedAttacks);
    }

    monsterAttack($game, $player, $opponent, $attackerSlot, $targetSlot, $attacker, $attackerId, $usedAttacks);
}

function directAttack(&$game, $player, $opponent, $attacker, $attackerId, $usedAttacks) {
    $opponentMonsters = array_values(array_filter($game['boards'][$opponent]['monsters']));

    if (count($opponentMonsters) > 0) {
        fail('Tu ne peux pas attaquer directement tant que l’adversaire a un personnage');
    }

    $damage = max(0, effAtk($game, $player, $attacker));
    $game['life'][$opponent] -= $damage;
    $game['attacks'][$attackerId] = $usedAttacks + 1;
    $game['hasAttacked'] = true;

    checkWin($game);
    ok($game, 'Attaque directe : -' . $damage . ' PV.');
}

function monsterAttack(&$game, $player, $opponent, $attackerSlot, $targetSlot, $attacker, $attackerId, $usedAttacks) {
    if ($targetSlot < 0 || $targetSlot > 2 || $game['boards'][$opponent]['monsters'][$targetSlot] === null) {
        fail('Cible introuvable');
    }

    $defender = $game['boards'][$opponent]['monsters'][$targetSlot];
    $attackerAtk = effAtk($game, $player, $attacker);
    $defenderInDefense = ($defender['position'] ?? 'attack') === 'defense';
    $defenderValue = $defenderInDefense
        ? effDef($game, $opponent, $defender)
        : effAtk($game, $opponent, $defender);

    $message = '';

    if ($attackerAtk > $defenderValue) {
        $message = attackerWinsCombat($game, $player, $opponent, $targetSlot, $defender, $defenderInDefense, $attackerAtk, $defenderValue);
    } elseif ($attackerAtk < $defenderValue) {
        $message = attackerLosesCombat($game, $player, $attackerSlot, $attacker, $defenderInDefense, $attackerAtk, $defenderValue);
    } else {
        $message = equalCombat($game, $player, $opponent, $attackerSlot, $targetSlot, $attacker, $defender, $defenderInDefense);
    }

    $game['attacks'][$attackerId] = $usedAttacks + 1;
    $game['hasAttacked'] = true;

    checkWin($game);
    ok($game, $message);
}

function attackerWinsCombat(&$game, $player, $opponent, $targetSlot, $defender, $defenderInDefense, $attackerAtk, $defenderValue) {
    if (($defender['effect'] ?? '') === 'gengis' && !($defender['gengisShieldUsed'] ?? false)) {
        $game['boards'][$opponent]['monsters'][$targetSlot]['gengisShieldUsed'] = true;
        return 'Gengis Khan évite sa destruction une fois ce tour.';
    }

    $game['boards'][$opponent]['monsters'][$targetSlot] = null;
    sendGY($game, $opponent, $defender);

    if (($defender['effect'] ?? '') === 'gengis') {
        $game['life'][$opponent] -= 500;
    }

    if (!$defenderInDefense) {
        $damage = $attackerAtk - $defenderValue;
        $game['life'][$opponent] -= $damage;
        return 'Cible détruite. Dégâts : ' . $damage;
    }

    return 'Cible détruite.';
}

function attackerLosesCombat(&$game, $player, $attackerSlot, $attacker, $defenderInDefense, $attackerAtk, $defenderValue) {
    $damage = $defenderValue - $attackerAtk;
    $game['life'][$player] -= $damage;

    if (!$defenderInDefense) {
        $game['boards'][$player]['monsters'][$attackerSlot] = null;
        sendGY($game, $player, $attacker);
        return 'Ton personnage est détruit. Dégâts : ' . $damage;
    }

    return 'Attaque repoussée. Dégâts : ' . $damage;
}

function equalCombat(&$game, $player, $opponent, $attackerSlot, $targetSlot, $attacker, $defender, $defenderInDefense) {
    if ($defenderInDefense) {
        return 'Égalité contre défense : rien ne se passe.';
    }

    $game['boards'][$player]['monsters'][$attackerSlot] = null;
    $game['boards'][$opponent]['monsters'][$targetSlot] = null;

    sendGY($game, $player, $attacker);
    sendGY($game, $opponent, $defender);

    if (($defender['effect'] ?? '') === 'gengis') {
        $game['life'][$opponent] -= 500;
    }

    if (($attacker['effect'] ?? '') === 'gengis') {
        $game['life'][$player] -= 500;
    }

    return 'Les deux personnages sont détruits.';
}
