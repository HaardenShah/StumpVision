/**
 * assets/js/scoring.js
 * Pure scoring domain logic for StumpVision.
 *
 * Responsibilities:
 *  - Maintain innings state (runs, wickets, balls, extras)
 *  - Apply events: dot, 1/2/3/4/6, bye, legbye, wide, noball, wicket, undo
 *  - Detect legal deliveries; auto-end over after ballsPerOver legal balls
 *  - Start new overs; change innings
 */

import { now } from './util.js';
import { State } from './state.js';

/* ------------------------- Utilities ------------------------- */
const legalHitEvents = new Set(['dot', '1', '2', '3', '4', '6', 'bye', 'legbye', 'wicket']);

/** Current innings convenience */
export function curInn() {
  return State.innings[State.meta.currentInnings];
}

/** Is this event a legal delivery (increments ball count)? */
export function isLegal(ev, opts = {}) {
  // wides & no-balls are NOT legal deliveries
  if (ev === 'wide' || ev === 'noball') return false;
  // everything else we model as a legal ball
  return legalHitEvents.has(ev);
}

/* ------------------------- Core Apply ------------------------- */
export function handleEvent(ev, state = State, opts = {}) {
  const inn = state.innings[state.meta.currentInnings];
  const bpo = state.meta.ballsPerOver || 6;

  // ensure logs
  inn.log = inn.log || [];
  inn.extras = inn.extras || { nb: 0, wd: 0, b: 0, lb: 0 };

  let runsAdded = 0;
  let legal = isLegal(ev, opts);

  switch (ev) {
    case 'dot':
      // nothing but legal ball
      break;

    case '1': case '2': case '3': case '4': case '6': {
      const n = Number(ev);
      runsAdded += n;
      // batting stats
      addBatStats(inn, currentBatterName(inn), n, 1, n === 4 ? 1 : 0, n === 6 ? 1 : 0);
      // strike rotation for odd runs
      if (n % 2 === 1) inn.striker = 1 - (inn.striker || 0);
      break;
    }

    case 'bye': {
      const r = Number(opts.runs ?? 1);
      inn.extras.b += r;
      runsAdded += r;
      // bye does NOT add to batter runs, but is a legal delivery
      break;
    }

    case 'legbye': {
      const r = Number(opts.runs ?? 1);
      inn.extras.lb += r;
      runsAdded += r;
      break;
    }

    case 'wide': {
      // wides add 1 + additional wides if chosen; NOT a legal ball
      const r = Number(opts.runs ?? 1); // 1,2,3... wides
      inn.extras.wd += r;
      runsAdded += r;
      legal = false;
      break;
    }

    case 'noball': {
      // no-ball adds 1 EXTRA + any bat runs (which go to batter), NOT a legal ball
      const batRuns = Number(opts.batRuns ?? 0); // 0,1,2,3,4,6
      // extras: +1 for NB (penalty)
      inn.extras.nb += 1;
      runsAdded += 1 + batRuns;

      if (batRuns > 0) {
        addBatStats(inn, currentBatterName(inn), batRuns, 0, batRuns === 4 ? 1 : 0, batRuns === 6 ? 1 : 0);
        // strike rotates on odd batRuns (even though not legal ball)
        if (batRuns % 2 === 1) inn.striker = 1 - (inn.striker || 0);
      }
      legal = false; // no-ball does not increment balls
      // optionally: mark next ball as free hit in inn flags
      inn.freeHit = true;
      break;
    }

    case 'wicket': {
      // counts as legal delivery (unless you want to model NB Wkt, skip for now)
      inn.wickets = (inn.wickets || 0) + 1;
      addBatStats(inn, currentBatterName(inn), 0, 1, 0, 0, true);
      break;
    }

    case 'undo': {
      undoLast(inn);
      recompute(inn, state.meta.ballsPerOver || 6);
      return;
    }

    default:
      console.warn('Unknown event', ev);
      return;
  }

  // apply runs
  inn.runs = (inn.runs || 0) + runsAdded;

  // balls/over handling
  if (legal) {
    inn.balls = (inn.balls || 0) + 1;
    inn.freeHit = false; // consumed if was set
  }

  // push to log
  inn.log.push({
    t: now(),
    ev,
    opts,
    runs: runsAdded,
    legal: legal ? 1 : 0,
    strikerIdx: inn.striker || 0,
  });

  // auto end over after bpo legal deliveries
  if (((inn.balls || 0) % bpo) === 0 && (inn.balls || 0) > 0 && legal) {
    newOver(state, { auto: true });
  }
}

/* ------------------------- Stats helpers ------------------------- */
function currentBatterName(inn) {
  const s = inn.striker || 0;
  const name = (inn.batters && inn.batters[s]) || '';
  return name || `Batter${s + 1}`;
}

function addBatStats(inn, name, runs, balls, fours = 0, sixes = 0, out = false) {
  inn.batStats = inn.batStats || [];
  let row = inn.batStats.find(r => r.name === name);
  if (!row) {
    row = { name, runs: 0, balls: 0, fours: 0, sixes: 0, out: false };
    inn.batStats.push(row);
  }
  row.runs += runs;
  row.balls += balls;
  row.fours += fours;
  row.sixes += sixes;
  if (out) row.out = true;
}

function addBowlStats(inn, name, runs, balls, wickets = 0) {
  inn.bowlStats = inn.bowlStats || [];
  let row = inn.bowlStats.find(r => r.name === name);
  if (!row) {
    row = { name, runs: 0, balls: 0, wickets: 0 };
    inn.bowlStats.push(row);
  }
  row.runs += runs;
  row.balls += balls;
  row.wickets += wickets;
}

/** Recalculate derived counters from log (if you ever need a full recompute) */
export function recompute(inn, bpo = 6) {
  inn.runs = 0; inn.wickets = 0; inn.balls = 0;
  inn.extras = { nb: 0, wd: 0, b: 0, lb: 0 };
  inn.batStats = []; inn.bowlStats = [];
  (inn.log || []).forEach(entry => {
    const { ev, opts, runs, legal } = entry;
    // rough recompute — enough for scoreboard
    inn.runs += runs || 0;
    if (ev === 'wicket') inn.wickets += 1;
    if (legal) inn.balls += 1;
    if (ev === 'wide') inn.extras.wd += Number(opts?.runs ?? 1);
    if (ev === 'noball') inn.extras.nb += 1;
    if (ev === 'bye') inn.extras.b += Number(opts?.runs ?? 1);
    if (ev === 'legbye') inn.extras.lb += Number(opts?.runs ?? 1);
  });
}

/* ------------------------- Over & Innings ------------------------- */
export function newOver(state = State, meta = {}) {
  const inn = state.innings[state.meta.currentInnings];
  inn.overs = inn.overs || [];
  inn.overs.push(inn.log.length);
  inn.thisOver = []; // let UI rebuild
  inn.pendingNewOver = true; // UI can use to prompt bowler
  // (No automatic bowler change here; UI will open picker)
}

export function changeInnings(state = State) {
  state.meta.currentInnings = (state.meta.currentInnings || 0) === 0 ? 1 : 0;
  const inn = curInn();
  inn.thisOver = [];
  inn.pendingNewOver = true;
}

/* ------------------------- Export newOver alias for app.js ------------- */
export { newOver };
