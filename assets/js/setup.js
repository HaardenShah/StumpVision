/**
 * assets/js/setup.js
 * ---------------------------------------------------------------------------
 * Guided setup wizard with smooth animations + haptics.
 *
 * Step 1: Basics (title, overs, balls/over)
 * Step 2: Teams & Players (editable rosters)
 * Step 3: Confirm (Toss + Opted decision, pick Striker/Non-striker and Bowler)
 *
 * On "Start Match", we persist a single payload to localStorage under
 *   key: 'stumpvision_setup_payload'
 * which index.php/app.js will read to seed a fresh match.
 * ---------------------------------------------------------------------------
 */

import { qs as $, qsa as $$, haptic } from './util.js';

/* ----------------------------- Local Wizard State ------------------------- */
const state = {
  title: '',
  overs: 10,
  bpo: 6,

  // Toss fields are now on Step 3 (moved from Step 1)
  toss: 'A',        // 'A' or 'B'
  opted: 'bat',     // 'bat' or 'bowl'

  teamA: 'Team A',
  teamB: 'Team B',
  playersA: [],
  playersB: [],

  opening: { striker: '', nonStriker: '', bowler: '' }
};

/* ----------------------------- Sync Helpers ------------------------------ */
/** Pulls current values from visible inputs into `state`. */
function syncFromUI() {
  // Step 1 inputs
  if ($('#s_title')) state.title = $('#s_title').value.trim();
  if ($('#s_overs')) state.overs = +$('#s_overs').value || 10;
  if ($('#s_bpo'))   state.bpo   = +$('#s_bpo').value   || 6;

  // Step 2 inputs
  if ($('#s_teamA')) state.teamA = $('#s_teamA').value || 'Team A';
  if ($('#s_teamB')) state.teamB = $('#s_teamB').value || 'Team B';

  // Step 3 inputs (toss moved here)
  if ($('#s_toss'))  state.toss  = $('#s_toss').value;
  if ($('#s_opted')) state.opted = $('#s_opted').value;
}

/* ----------------------------- Player List UI ---------------------------- */
function addPlayer(team) {
  const name = prompt(`Add player to Team ${team}:`);
  if (!name) return;
  (team === 'A' ? state.playersA : state.playersB).push(name.trim());
  renderLists();
  haptic('soft');
}

function removePlayer(team, idx) {
  const arr = team === 'A' ? state.playersA : state.playersB;
  arr.splice(idx, 1);
  renderLists();
  haptic('soft');
}

function renderLists() {
  const mk = (arr, team) => {
    const ul = team === 'A' ? $('#listA') : $('#listB');
    if (!ul) return;
    ul.innerHTML = arr
      .map(
        (n, i) =>
          `<li class="chip">${n}<button class="x" data-team="${team}" data-i="${i}">×</button></li>`
      )
      .join('');
  };
  mk(state.playersA, 'A');
  mk(state.playersB, 'B');

  $$('#listA .x').forEach((b) => (b.onclick = () => removePlayer('A', +b.dataset.i)));
  $$('#listB .x').forEach((b) => (b.onclick = () => removePlayer('B', +b.dataset.i)));
}

/* ----------------------------- Openers / Toss ---------------------------- */
/** Determine which team bats first based on toss+opted. Returns 0 (A) or 1 (B). */
function battingTeamIndex() {
  // If Team A wins toss and opts to bat -> Team A bats (0), else Team B bats (1)
  if (state.toss === 'A') return state.opted === 'bat' ? 0 : 1;
  // If Team B wins toss and opts to bat -> Team B bats (1), else Team A bats (0)
  return state.opted === 'bat' ? 1 : 0;
}

/** Populate the Step 3 selects for openers + bowler based on toss decision. */
function populateOpeners() {
  // Ensure latest values
  syncFromUI();

  const batIdx  = battingTeamIndex();
  const bowlIdx = 1 - batIdx;

  const batTeamName  = batIdx === 0 ? state.teamA : state.teamB;
  const bowlTeamName = bowlIdx === 0 ? state.teamA : state.teamB;

  const batRoster  = batIdx === 0 ? state.playersA : state.playersB;
  const bowlRoster = bowlIdx === 0 ? state.playersA : state.playersB;

  $('#openersHint').textContent = `${batTeamName} batting • ${bowlTeamName} bowling`;

  const strikerSel = $('#s_striker');
  const nonSel     = $('#s_nonStriker');
  const bowlerSel  = $('#s_bowler');

  const opt = (name) => `<option value="${name}">${name}</option>`;
  strikerSel.innerHTML = batRoster.map(opt).join('');
  nonSel.innerHTML     = batRoster.map(opt).join('');
  bowlerSel.innerHTML  = bowlRoster.map(opt).join('');

  // Sensible defaults
  state.opening.striker    = batRoster[0] || '';
  state.opening.nonStriker = batRoster[1] || '';
  state.opening.bowler     = bowlRoster[0] || '';

  strikerSel.value = state.opening.striker;
  nonSel.value     = state.opening.nonStriker;
  bowlerSel.value  = state.opening.bowler;

  // Persist on change
  strikerSel.onchange = () => (state.opening.striker = strikerSel.value);
  nonSel.onchange     = () => (state.opening.nonStriker = nonSel.value);
  bowlerSel.onchange  = () => (state.opening.bowler = bowlerSel.value);
}

/** Human-readable summary on Step 3. */
function renderReview() {
  const batIdx = battingTeamIndex();
  const batTeam = batIdx === 0 ? state.teamA : state.teamB;
  const bowlTeam = batIdx === 0 ? state.teamB : state.teamA;

  $('#review').innerHTML = `
    <div class="card lite fade-in" style="margin-top:10px">
      <div><b>Title:</b> ${state.title || '—'}</div>
      <div><b>Overs/Balls:</b> ${state.overs} / ${state.bpo}</div>
      <div><b>Toss:</b> Team ${state.toss} &nbsp; <b>Opted:</b> ${state.opted}</div>
      <div class="mt"><b>Batting first:</b> ${batTeam} &nbsp; • &nbsp; <b>Bowling:</b> ${bowlTeam}</div>
      <div class="mt"><b>Openers:</b> ${state.opening.striker || '—'} & ${state.opening.nonStriker || '—'}</div>
      <div><b>Opening Bowler:</b> ${state.opening.bowler || '—'}</div>
    </div>
  `;
}

/* ----------------------------- Persist Payload --------------------------- */
/** Save the entire setup into localStorage for the match page to consume. */
function savePayload() {
  const payload = {
    meta: {
      title: state.title,
      oversPerSide: state.overs,
      ballsPerOver: state.bpo,
      toss: state.toss,
      opted: state.opted
    },
    teams: [
      { name: state.teamA, players: state.playersA },
      { name: state.teamB, players: state.playersB }
    ],
    opening: {
      battingTeamIndex: battingTeamIndex(), // 0 or 1
      striker: state.opening.striker,
      nonStriker: state.opening.nonStriker,
      bowler: state.opening.bowler
    }
  };

  localStorage.setItem('stumpvision_setup_payload', JSON.stringify(payload));
}

/* ----------------------------- Stepper / Navigation ---------------------- */
let cur = 1;

/** Animated transition between step panels. */
function goto(step) {
  if (step === cur) return;
  const forward = step > cur;

  // Update stepper UI
  $$('.step').forEach((s) =>
    s.classList.toggle('current', +s.dataset.step === step)
  );

  // Animate panels
  const from = $(`.step-panel[data-step="${cur}"]`);
  const to   = $(`.step-panel[data-step="${step}"]`);

  if (from) from.classList.add(forward ? 'slide-left-out' : 'slide-right-out');
  if (to) {
    to.classList.remove('hidden', 'slide-left-out', 'slide-right-out');
    to.classList.add(forward ? 'slide-right-in' : 'slide-left-in');

    // After the brief animation, clean up classes
    setTimeout(() => {
      to.classList.remove('slide-right-in', 'slide-left-in');
      if (from) {
        from.classList.add('hidden');
        from.classList.remove('slide-left-out', 'slide-right-out');
      }
    }, 260);
  }

  cur = step;

  // When arriving at Step 3, ensure openers + review are up to date
  if (step === 3) {
    syncFromUI();        // pull title/overs/teams
    populateOpeners();   // compute from toss/opted + rosters
    renderReview();      // human-readable card
  }

  haptic('light');
}

/* ----------------------------- Wire up Controls -------------------------- */
// Next/Back
$$('.next').forEach((b) =>
  b.addEventListener('click', () => {
    syncFromUI();
    goto(cur + 1);
  })
);
$$('.prev').forEach((b) =>
  b.addEventListener('click', () => goto(cur - 1))
);

// Clickable stepper
$$('.step').forEach((s) =>
  s.addEventListener('click', () => {
    const n = +s.dataset.step;
    if (n >= 1 && n <= 3) goto(n);
  })
);

// Roster add/remove
$('[data-add="A"]').onclick = () => addPlayer('A');
$('[data-add="B"]').onclick = () => addPlayer('B');

// Start Match: save payload and jump to match page
$('#startMatch').onclick = () => {
  syncFromUI();
  // Validate we have at least two batters and one bowler for a smoother start
  const hasOpeners = state.opening.striker && state.opening.nonStriker;
  const hasBowler  = !!state.opening.bowler;
  if (!hasOpeners || !hasBowler) {
    alert('Please choose both opening batters and the opening bowler.');
    return;
  }
  savePayload();
  haptic('success');
  location.href = 'index.php';
};

// Initial render of lists
renderLists();
