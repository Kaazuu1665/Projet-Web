const PLAYER_NAMES = {
    A: 'Asie',
    B: 'Europe'
};

const PHASE_NAMES = {
    draw: 'Draw Phase',
    main: 'Main Phase',
    battle: 'Battle Phase',
    end: 'End Phase'
};

let currentPlayer = null;
let selectedHandIndex = null;
let selectedPosition = 'attack';
let selectedBoard = null;
let attackMode = false;
let serverState = null;
let refreshTimer = null;

function opponentOf(player) {
    return player === 'A' ? 'B' : 'A';
}

function isFirstTurn() {
    return serverState
        && serverState.turnNumber === 1
        && serverState.turn === serverState.firstPlayer;
}

function setMessage(text) {
    const message = document.getElementById('message');

    if (message) {
        message.textContent = text || '';
    }
}

function cardImg(card) {
    const img = document.createElement('img');
    img.src = card?.img || '';
    img.alt = card?.nom || 'Carte';

    img.onerror = () => {
        const fallback = document.createElement('div');
        fallback.className = 'card-placeholder';
        fallback.textContent = card?.nom || 'Carte';
        img.replaceWith(fallback);
    };

    return img;
}

async function post(payload) {
    const response = await fetch('play.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    return await response.json();
}

async function startForPlayer(player) {
    currentPlayer = player;
    selectedHandIndex = null;
    selectedBoard = null;
    attackMode = false;

    document.getElementById('lobby').classList.add('hidden');
    document.getElementById('side-selector').classList.add('hidden');
    document.getElementById('game-ui').classList.remove('hidden');

    document.querySelectorAll('#side-selector button').forEach(button => {
        button.classList.toggle('active', button.dataset.player === player);
    });

    buildBoard();
    await loadState();

    if (refreshTimer) {
        clearInterval(refreshTimer);
    }

    refreshTimer = setInterval(loadState, 1000);
}

async function loadState() {
    try {
        const response = await fetch('state.php', { cache: 'no-store' });
        serverState = await response.json();
        renderAll();
    } catch (error) {
        console.error(error);
        setMessage('Impossible de charger la partie.');
    }
}

function buildBoard() {
    const bottomPlayer = currentPlayer;
    const topPlayer = opponentOf(currentPlayer);

    const zones = [
        {
            id: 'opponent-back',
            player: topPlayer,
            zone: 'magics',
            className: 'back-slot',
            label: 'Magie'
        },
        {
            id: 'opponent-monsters',
            player: topPlayer,
            zone: 'monsters',
            className: 'monster-slot',
            label: 'Perso'
        },
        {
            id: 'player-monsters',
            player: bottomPlayer,
            zone: 'monsters',
            className: 'monster-slot',
            label: 'Perso'
        },
        {
            id: 'player-back',
            player: bottomPlayer,
            zone: 'magics',
            className: 'back-slot',
            label: 'Magie'
        }
    ];

    zones.forEach(config => {
        const container = document.getElementById(config.id);
        container.innerHTML = '';

        for (let i = 0; i < 3; i++) {
            const slot = document.createElement('div');
            slot.className = config.className;
            slot.dataset.player = config.player;
            slot.dataset.zone = config.zone;
            slot.dataset.index = String(i);
            slot.innerHTML = `<span class="slot-label">${config.label} ${i + 1}</span>`;
            slot.onclick = () => handleSlotClick(slot);
            container.appendChild(slot);
        }
    });

    [
        ['player-field', bottomPlayer],
        ['opponent-field', topPlayer]
    ].forEach(([id, player]) => {
        const field = document.getElementById(id);
        field.dataset.player = player;
        field.dataset.zone = 'field';
        field.onclick = () => handleFieldClick(field);
    });
}

function renderAll() {
    renderInfo();
    renderPhases();
    renderBoard();
    renderHand();
    renderActions();
}


function lifeClass(value, otherValue) {
    if (value > otherValue) {
        return 'life-ahead';
    }

    if (value < otherValue) {
        return 'life-behind';
    }

    return 'life-equal';
}

function renderInfo() {
    if (!currentPlayer || !serverState) {
        return;
    }

    const opponent = opponentOf(currentPlayer);
    const playerLife = document.getElementById('player-life');
    const opponentLife = document.getElementById('opponent-life');
    const playerDeck = document.getElementById('player-deck');
    const opponentDeck = document.getElementById('opponent-deck');

    const myLife = Number(serverState.life[currentPlayer] ?? 0);
    const enemyLife = Number(serverState.life[opponent] ?? 0);

    if (playerLife) {
        playerLife.innerHTML = `
            <span>Vous</span>
            <strong class="${lifeClass(myLife, enemyLife)}">${myLife} PV</strong>
        `;
    }

    if (opponentLife) {
        opponentLife.innerHTML = `
            <span>Adversaire</span>
            <strong class="${lifeClass(enemyLife, myLife)}">${enemyLife} PV</strong>
        `;
    }

    if (playerDeck) {
        playerDeck.innerHTML = `
            <span>Deck</span>
            <span class="deck-count">${serverState.decks[currentPlayer]?.length || 0} cartes</span>
        `;
    }

    if (opponentDeck) {
        opponentDeck.innerHTML = `
            <span>Deck</span>
            <span class="deck-count">${serverState.decks[opponent]?.length || 0} cartes</span>
        `;
    }
}

function renderPhases() {
    const track = document.getElementById('phase-track');
    const phase = serverState?.phase || 'main';
    const myTurn = serverState?.turn === currentPlayer;

    track.classList.toggle('my-turn', myTurn);
    track.classList.toggle('opponent-turn', !myTurn);

    track.querySelectorAll('.phase-step').forEach(step => {
        step.classList.toggle('active', step.dataset.phase === phase);
        step.classList.toggle('blocked', isFirstTurn() && step.dataset.phase === 'battle');
    });

    document.getElementById('phase-info').innerHTML = `
        Tour actuel : <strong>${PLAYER_NAMES[serverState.turn]}</strong><br>
        Phase actuelle : <strong>${PHASE_NAMES[phase]}</strong><br>
        Tour numéro : ${serverState.turnNumber}
        ${isFirstTurn() ? "<br><span class='warning'>Premier tour : pas de pioche et pas d'attaque.</span>" : ''}
    `;

    document.getElementById('draw-btn').disabled = !myTurn
        || phase !== 'draw'
        || serverState.hasDrawn
        || isFirstTurn();

    document.getElementById('next-phase-btn').disabled = !myTurn;
}

function renderBoard() {
    if (!currentPlayer || !serverState) {
        return;
    }

    ['A', 'B'].forEach(player => {
        ['monsters', 'magics'].forEach(zone => {
            for (let index = 0; index < 3; index++) {
                renderZoneSlot(player, zone, index);
            }
        });
    });

    ['A', 'B'].forEach(player => {
        renderField(player);
    });
}

function renderZoneSlot(player, zone, index) {
    const slot = document.querySelector(
        `[data-player="${player}"][data-zone="${zone}"][data-index="${index}"]`
    );

    if (!slot) {
        return;
    }

    slot.classList.remove('selected-board-card', 'defense-slot');
    slot.innerHTML = '';

    const card = serverState.boards[player][zone][index];

    if (!card) {
        slot.innerHTML = `<span class="slot-label">${zone === 'monsters' ? 'Perso' : 'Magie'} ${index + 1}</span>`;
        return;
    }

    const image = cardImg(card);

    if (card.position === 'defense') {
        image.classList.add('defense-position');
        slot.classList.add('defense-slot');
    }

    image.onclick = async event => {
        event.stopPropagation();

        if (attackMode) {
            if (zone === 'monsters' && player !== currentPlayer) {
                await doAttack(index);
            } else {
                setMessage('Choisis un personnage adverse comme cible.');
            }
            return;
        }

        selectBoardCard(player, zone, index, card);
    };

    image.onmouseenter = () => showPreview(card, player);

    slot.appendChild(image);

    if (selectedBoard
        && selectedBoard.player === player
        && selectedBoard.zone === zone
        && selectedBoard.index === index) {
        slot.classList.add('selected-board-card');
    }
}

function renderField(player) {
    const fieldId = player === currentPlayer ? 'player-field' : 'opponent-field';
    const field = document.getElementById(fieldId);

    field.classList.remove('selected-board-card');
    field.innerHTML = 'Terrain';

    const card = serverState.boards[player].field;

    if (!card) {
        return;
    }

    field.innerHTML = '';
    const image = cardImg(card);

    image.onclick = event => {
        event.stopPropagation();
        selectBoardCard(player, 'field', null, card);
    };

    image.onmouseenter = () => showPreview(card, player);
    field.appendChild(image);

    if (selectedBoard && selectedBoard.player === player && selectedBoard.zone === 'field') {
        field.classList.add('selected-board-card');
    }
}

function renderHand() {
    const hand = document.getElementById('hand');
    hand.innerHTML = '';

    if (!currentPlayer || !serverState) {
        hand.textContent = 'Choisis ton camp.';
        return;
    }

    (serverState.hands[currentPlayer] || []).forEach((card, index) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'hand-card-wrap';

        if (selectedHandIndex === index) {
            wrapper.classList.add('selected-hand-wrap');
            wrapper.appendChild(createHandCardActions(card, index));
        }

        const button = document.createElement('button');
        button.className = 'card';
        button.type = 'button';

        if (selectedHandIndex === index) {
            button.classList.add('selected');
        }

        button.appendChild(cardImg(card));

        button.onclick = () => {
            selectedHandIndex = index;
            selectedBoard = null;
            attackMode = false;
            selectedPosition = 'attack';
            showPreview(card, null);
            renderAll();

            if (card.kind === 'spell') {
                setMessage('Carte magie sélectionnée : clique sur Activer au-dessus de la carte.');
            } else if (card.kind === 'field') {
                setMessage('Carte Terrain sélectionnée : clique sur ta zone Terrain pour la poser.');
            } else {
                setMessage('Choisis ATK ou DEF au-dessus de la carte, puis clique sur un slot personnage.');
            }
        };

        button.onmouseenter = () => showPreview(card, null);
        wrapper.appendChild(button);
        hand.appendChild(wrapper);
    });
}

function createHandCardActions(card, index) {
    const actions = document.createElement('div');
    actions.className = 'hand-card-actions';

    if (card.kind === 'monster') {
        const attackButton = document.createElement('button');
        attackButton.type = 'button';
        attackButton.textContent = 'ATK';
        attackButton.className = selectedPosition === 'attack' ? 'active' : '';
        attackButton.onclick = event => {
            event.stopPropagation();
            selectedHandIndex = index;
            selectedPosition = 'attack';
            setMessage('Invocation en attaque : clique sur un slot personnage.');
            renderAll();
        };

        const defenseButton = document.createElement('button');
        defenseButton.type = 'button';
        defenseButton.textContent = 'DEF';
        defenseButton.className = selectedPosition === 'defense' ? 'active' : '';
        defenseButton.onclick = event => {
            event.stopPropagation();
            selectedHandIndex = index;
            selectedPosition = 'defense';
            setMessage('Invocation en défense : clique sur un slot personnage.');
            renderAll();
        };

        actions.appendChild(attackButton);
        actions.appendChild(defenseButton);
        return actions;
    }

    if (card.kind === 'spell') {
        const activateButton = document.createElement('button');
        activateButton.type = 'button';
        activateButton.textContent = 'Activer';
        activateButton.onclick = async event => {
            event.stopPropagation();
            selectedHandIndex = index;
            await activateHandCard();
        };

        actions.appendChild(activateButton);
        return actions;
    }

    if (card.kind === 'field') {
        const fieldButton = document.createElement('button');
        fieldButton.type = 'button';
        fieldButton.textContent = 'Terrain';
        fieldButton.onclick = event => {
            event.stopPropagation();
            selectedHandIndex = index;
            setMessage('Clique sur ta zone Terrain pour poser cette carte.');
        };

        actions.appendChild(fieldButton);
    }

    return actions;
}


function selectedMonsterCanAttack() {
    if (!selectedBoard || !serverState) {
        return false;
    }

    if (selectedBoard.player !== currentPlayer || selectedBoard.zone !== 'monsters') {
        return false;
    }

    if (serverState.turn !== currentPlayer || serverState.phase !== 'battle') {
        return false;
    }

    const card = selectedBoard.card || {};

    if ((card.position || 'attack') === 'defense' && !card.canAttackDefense) {
        return false;
    }

    return true;
}

function opponentHasMonsters() {
    if (!currentPlayer || !serverState) {
        return true;
    }

    return (serverState.boards[opponentOf(currentPlayer)].monsters || []).some(card => card !== null);
}

function renderActions() {
    const handCard = selectedHandIndex !== null
        ? serverState.hands[currentPlayer]?.[selectedHandIndex]
        : null;

    const summonMode = document.getElementById('summon-mode');

    if (summonMode) {
        summonMode.classList.add('hidden');
    }

    const activateButton = document.getElementById('activate-btn');

    if (activateButton) {
        activateButton.disabled = !(
            selectedBoard
            && selectedBoard.player === currentPlayer
            && selectedBoard.zone === 'monsters'
            && selectedBoard.card?.effect
            && !selectedBoard.card?.continuous
        );
    }

    const attackButton = document.getElementById('attack-btn');
    const directAttackButton = document.getElementById('direct-attack-btn');
    const canAttack = selectedMonsterCanAttack();

    if (attackButton) {
        attackButton.disabled = !canAttack;
    }

    if (directAttackButton) {
        directAttackButton.disabled = !(canAttack && !opponentHasMonsters());
    }
}

function computeStats(card, owner) {
    if (!card || card.kind !== 'monster') {
        return null;
    }

    const baseAtk = Number(card.originalAtk ?? card.baseAtk ?? card.atk ?? 0);
    const baseDef = Number(card.originalDef ?? card.baseDef ?? card.def ?? 0);

    let currentAtk = Number(card.atk ?? 0) + Number(card.atkBonus ?? 0);
    let currentDef = Number(card.def ?? 0) + Number(card.defBonus ?? 0);

    if (owner && serverState?.boards?.[owner]?.field) {
        const fieldEffect = serverState.boards[owner].field.effect;

        if (fieldEffect === 'wall') {
            currentDef += 400;
        }

        if (fieldEffect === 'lepante' && card.region === 'Europe') {
            currentAtk += 300;
            currentDef += 200;
        }
    }

    return {
        baseAtk,
        baseDef,
        currentAtk,
        currentDef
    };
}

function statClass(current, base) {
    if (current > base) {
        return 'stat-buff';
    }

    if (current < base) {
        return 'stat-nerf';
    }

    return 'stat-normal';
}

function showPreview(card, owner = null) {
    const preview = document.getElementById('card-preview');
    preview.innerHTML = '';

    if (!card) {
        return;
    }

    const content = document.createElement('div');
    content.className = 'preview-content';
    content.appendChild(cardImg(card));

    const stats = computeStats(card, owner);

    if (stats) {
        const statLine = document.createElement('div');
        statLine.className = 'preview-stats';
        statLine.innerHTML = `
            <span class="${statClass(stats.currentAtk, stats.baseAtk)}">ATK ${stats.currentAtk}</span>
            <span class="${statClass(stats.currentDef, stats.baseDef)}">DEF ${stats.currentDef}</span>
        `;
        content.appendChild(statLine);
    }

    preview.appendChild(content);
}

function selectBoardCard(player, zone, index, card) {
    selectedBoard = {
        player,
        zone,
        index,
        card
    };

    selectedHandIndex = null;
    attackMode = false;

    showPreview(card, player);

    if (player === currentPlayer) {
        setMessage('Carte du terrain sélectionnée.');
    } else {
        setMessage('Carte adverse sélectionnée.');
    }

    renderAll();
}

async function handleFieldClick(field) {
    const player = field.dataset.player;

    if (attackMode) {
        if (player !== currentPlayer) {
            await doAttack(-1);
        } else {
            setMessage('Clique sur le terrain adverse pour une attaque directe.');
        }
        return;
    }

    if (player !== currentPlayer) {
        setMessage('Ce terrain appartient à l’adversaire.');
        return;
    }

    if (selectedHandIndex === null) {
        setMessage('Sélectionne une carte Terrain dans ta main.');
        return;
    }

    const card = serverState.hands[currentPlayer][selectedHandIndex];

    if (card.kind !== 'field') {
        setMessage('Seules les cartes Terrain se posent ici.');
        return;
    }

    await playCard('field', null);
}

async function handleSlotClick(slot) {
    const player = slot.dataset.player;
    const zone = slot.dataset.zone;
    const index = Number(slot.dataset.index);

    if (attackMode) {
        if (zone === 'monsters' && player !== currentPlayer) {
            await doAttack(index);
        } else {
            setMessage('Choisis un monstre adverse ou le terrain adverse pour une attaque directe.');
        }
        return;
    }

    if (selectedHandIndex === null) {
        setMessage('Sélectionne une carte dans ta main.');
        return;
    }

    await playCard(zone, index);
}

async function playCard(zone, slot) {
    const card = serverState.hands[currentPlayer][selectedHandIndex];

    if (!card) {
        return;
    }

    const response = await post({
        action: 'playCard',
        player: currentPlayer,
        handIndex: selectedHandIndex,
        zone,
        slot,
        position: selectedPosition
    });

    if (!response.success) {
        setMessage(response.error);
        return;
    }

    selectedHandIndex = null;
    selectedPosition = 'attack';
    showPreview(null);
    setMessage(response.message);
    await loadState();
}

async function activateSelected() {
    if (selectedHandIndex !== null) {
        await activateHandCard();
        return;
    }

    if (selectedBoard && selectedBoard.player === currentPlayer && selectedBoard.zone === 'monsters') {
        await activateMonsterEffect();
        return;
    }

    setMessage('Sélectionne une carte activable.');
}

async function activateHandCard() {
    const card = serverState.hands[currentPlayer][selectedHandIndex];
    let target = null;

    if (['invasions', 'art'].includes(card.effect)) {
        target = Number(prompt('Choisis le slot de ton personnage cible (1, 2 ou 3)')) - 1;
    }

    if (card.effect === 'heliocentrisme') {
        target = Number(prompt('Choisis le slot du personnage adverse cible (1, 2 ou 3)')) - 1;
    }

    const response = await post({
        action: 'activateHand',
        player: currentPlayer,
        handIndex: selectedHandIndex,
        target
    });

    if (!response.success) {
        setMessage(response.error);
        return;
    }

    if (response.game?.lastPeek && card.effect === 'buddhism') {
        alert(response.game.lastPeek);
    }

    selectedHandIndex = null;
    setMessage(response.message);
    await loadState();
}

async function activateMonsterEffect() {
    let target = null;

    if (selectedBoard.card.effect === 'hokusai') {
        target = Number(prompt('Choisis le slot de ton personnage cible (1, 2 ou 3)')) - 1;
    }

    if (selectedBoard.card.effect === 'galileo') {
        target = Number(prompt('Choisis le slot du personnage adverse à détruire (1, 2 ou 3)')) - 1;
    }

    const response = await post({
        action: 'activateBoard',
        player: currentPlayer,
        slot: selectedBoard.index,
        target
    });

    if (!response.success) {
        setMessage(response.error);
        return;
    }

    selectedBoard = null;
    setMessage(response.message);
    await loadState();
}

function startAttack() {
    if (!selectedBoard || selectedBoard.player !== currentPlayer || selectedBoard.zone !== 'monsters') {
        setMessage('Sélectionne un de tes personnages.');
        return;
    }

    if (!selectedMonsterCanAttack()) {
        setMessage('Ce personnage ne peut pas attaquer maintenant.');
        return;
    }

    attackMode = true;
    setMessage('Choisis un personnage adverse, ou clique sur Attaque directe si le terrain adverse est vide.');
}


async function directAttackFromButton() {
    if (!selectedMonsterCanAttack()) {
        setMessage('Sélectionne un personnage qui peut attaquer.');
        return;
    }

    if (opponentHasMonsters()) {
        setMessage('Impossible : l’adversaire a encore un personnage.');
        return;
    }

    await doAttack(-1);
}

async function doAttack(targetSlot) {
    const response = await post({
        action: 'attack',
        player: currentPlayer,
        attacker: selectedBoard.index,
        targetPlayer: opponentOf(currentPlayer),
        targetSlot
    });

    if (!response.success) {
        setMessage(response.error);
        return;
    }

    attackMode = false;
    selectedBoard = null;
    setMessage(response.message);
    await loadState();
}

async function draw() {
    const response = await post({
        action: 'draw',
        player: currentPlayer
    });

    if (!response.success) {
        setMessage(response.error);
        return;
    }

    setMessage(response.message);
    await loadState();
}

async function nextPhase() {
    const response = await post({
        action: 'nextPhase',
        player: currentPlayer
    });

    if (!response.success) {
        setMessage(response.error);
        return;
    }

    selectedBoard = null;
    attackMode = false;
    setMessage(response.message);
    await loadState();
}

async function resetGame() {
    await fetch('reset.php', { cache: 'no-store' });

    currentPlayer = null;
    selectedHandIndex = null;
    selectedBoard = null;
    attackMode = false;
    serverState = null;

    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }

    showPreview(null);
    document.getElementById('game-ui').classList.add('hidden');
    document.getElementById('side-selector').classList.add('hidden');
    document.getElementById('lobby').classList.remove('hidden');

    document.querySelectorAll('#side-selector button').forEach(button => {
        button.classList.remove('active');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('enter-game-btn').onclick = () => {
        document.getElementById('lobby').classList.add('hidden');
        document.getElementById('side-selector').classList.remove('hidden');
    };

    document.getElementById('help-btn').onclick = () => {
        document.getElementById('help-modal').classList.remove('hidden');
    };

    document.getElementById('close-help-btn').onclick = () => {
        document.getElementById('help-modal').classList.add('hidden');
    };

    document.querySelectorAll('#side-selector button').forEach(button => {
        button.onclick = () => startForPlayer(button.dataset.player);
    });

    document.getElementById('draw-btn').onclick = draw;
    document.getElementById('next-phase-btn').onclick = nextPhase;
    document.getElementById('activate-btn').onclick = activateSelected;
    document.getElementById('attack-btn').onclick = startAttack;
    document.getElementById('direct-attack-btn').onclick = directAttackFromButton;
    document.getElementById('reset-btn').onclick = resetGame;

    document.querySelectorAll('#summon-mode button').forEach(button => {
        button.onclick = () => {
            selectedPosition = button.dataset.position;
            renderActions();
        };
    });
});
