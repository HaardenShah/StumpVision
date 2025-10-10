/**
 * assets/js/app.js
 * ---------------------------------------------------------------------------
 * Match page controller:
 *  - Seeds a new match from the setup payload (created by setup.php)
 *  - Wires all scoring pad events
 *  - Handles save/open/export + optional live viewer mode
 *
 * Notes:
 *  - VIEW_ONLY mode (?view=1) shows a read-only board and polls for updates.
 *  - SHARE_ID (?id=...) loads a saved match (as scorer or viewer).
 * ---------------------------------------------------------------------------
 */

import { qs } from './util.js';
import {
  State,
  hydrateFromLocal,
  autosave,
  cloneState,
  curInn,
  newInnings,
  DEFAULT_META,
} from './state.js';
import { handleEvent, newOver, changeInnings } from './scoring.js';
import {
  bindPad,
  readInputs,
  hydrateInputs,
  renderAll,
  setReadOnly,
} from './ui.js';

/* ---------------------------------------------
 * URL Params / Viewer mode
 * ------------------------------------------- */
const params = new URLSearchParams(location.search);
const VIEW_ONLY = params.get('view') === '1';
const SHARE_ID = params.get('id') || null;

let pollTimer = null;
let lastSavedAt = 0;

/* ---------------------------------------------
 * API helper (flat-file JSON endpoints)
 * ------------------------------------------- */
const api = (action, p = {}) => {
  const url =
    `api/matches.php?action=${action}` +
    (p.id ? `&id=${encodeURIComponent(p.id)}` : '');

  const opts = {
    method: p.body ? 'POST' : 'GET',
    headers: p.body ? { 'Content-Type': 'application/json' } : undefined,
    body: p.body ? JSON.stringify(p.body) : undefined,
  };

  return fetch(url, opts);
};

/* ---------------------------------------------
 * Seed: try to hydrate a new match from setup payload
 *  - Uses team names, overs/balls, toss+opted to decide batting first
 *  - Sets opening striker/non-striker and opening bowler
 * ------------------------------------------- */
function maybeSeedFromSetup() {
  if (State.saveId) return; // already a saved match
  const raw = localStorage.getItem('stumpvision_setup_payload');
  if (!raw) return;

  try {
    const s = JSON.parse(raw);

    // Meta
    State.meta = { ...DEFAULT_META, ...s.meta };

    // Team names
    if (s.teams?.[0]) State.teams[0].name = s.teams[0].name || 'Team A';
    if (s.teams?.[1]) State.teams[1].name = s.teams[1].name || 'Team B';

    // Who bats first (from setup)
    if (typeof s.opening?.battingTeamIndex === 'number') {
      const idx = s.opening.battingTeamIndex; // 0 or 1
      State.innings[0].batting = idx;
      State.innings[0].bowling = 1 - idx;
    }

    // Opening players
    const inn = curInn();
    if (s.opening) {
      inn.batters[0] = s.opening.striker || '';
      inn.batters[1] = s.opening.nonStriker || '';
      inn.striker    = 0; // index in batters[]
      inn.bowler     = s.opening.bowler || '';
    } else {
      // Fallback: first two of Team A roster (if any)
      const roster = s.teams?.[0]?.players || [];
      inn.batters[0] = roster[0] || '';
      inn.batters[1] = roster[1] || '';
    }
  } catch {
    // ignore malformed payloads
  }
}

/* ---------------------------------------------
 * Boot sequence
 * ------------------------------------------- */
hydrateFromLocal();
maybeSeedFromSetup();
hydrateInputs();
renderAll();

// Central handler used by scoring pad (supports opts for nb/wd)
const handle = (ev, opts) => {
  if (VIEW_ONLY) return;
  readInputs();
  handleEvent(ev, State, opts || {});
  renderAll();
  autosave();
};

bindPad(handle);

/* ---------------------------------------------
 * Viewer (read-only) mode: auto-poll for updates
 * ------------------------------------------- */
if (VIEW_ONLY) {
  document.body.classList.add('read-only');
  setReadOnly(true);

  const hint = qs('#saveHint');
  if (hint) hint.textContent = 'Live view — updates appear automatically.';

  if (SHARE_ID) {
    const poll = async () => {
      try {
        const res = await api('load', { id: SHARE_ID });
        const j = await res.json();
        if (j.ok) {
          const ts = j.payload.__saved_at || 0;
          if (ts !== lastSavedAt) {
            Object.assign(State, j.payload);
            lastSavedAt = ts;
            hydrateInputs();
            renderAll();
          }
        }
      } catch {
        // ignore transient network errors
      }
    };

    // initial fetch + interval
    (async () => {
      await poll();
      pollTimer = setInterval(poll, 3000);
    })();
  }
}

/* ---------------------------------------------
 * Top controls
 * ------------------------------------------- */
const btnSave = qs('#btnSave');
if (btnSave) {
  btnSave.onclick = async () => {
    if (VIEW_ONLY) return;
    readInputs();
    const res = await api('save', { body: { id: State.saveId, payload: cloneState() } });
    const j = await res.json();
    const hint = qs('#saveHint');

    if (j.ok) {
      State.saveId = j.id;
      if (hint) hint.textContent = `Saved ✓ (id ${j.id})`;
    } else {
      if (hint) hint.textContent = 'Save failed';
    }
  };
}

const btnOpen = qs('#btnOpen');
if (btnOpen) {
  btnOpen.onclick = async () => {
    if (VIEW_ONLY) return;
    const list = await (await api('list')).json();
    if (!list.ok || !list.items.length) {
      alert('No saved matches found.');
      return;
    }
    const pick = prompt(
      'Enter ID to load:\n' +
        list.items
          .map(
            (x) => `${x.id} — ${new Date(x.ts * 1000).toLocaleString()} — ${x.title}`,
          )
          .join('\n'),
    );
    if (!pick) return;

    const j = await (await api('load', { id: pick })).json();
    if (j.ok) {
      Object.assign(State, j.payload);
      State.saveId = pick;
      hydrateInputs();
      renderAll();
      autosave();
    } else {
      alert('Load failed');
    }
  };
}

const btnExport = qs('#btnExport');
if (btnExport) {
  btnExport.onclick = () => {
    const blob = new Blob([JSON.stringify(cloneState(), null, 2)], {
      type: 'application/json',
    });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = (State.meta.title || 'stumpvision_match') + '.json';
    a.click();
  };
}

const btnCopyShare = qs('#btnCopyShare');
if (btnCopyShare) {
  btnCopyShare.onclick = async () => {
    const base = location.origin + location.pathname;
    const link = State.saveId
      ? `${base}?id=${encodeURIComponent(State.saveId)}&view=1`
      : base;
    try {
      await navigator.clipboard.writeText(link);
      const hint = qs('#saveHint');
      if (hint) {
        hint.textContent = State.saveId
          ? 'Live viewer link copied.'
          : 'Save first to generate a live link.';
      }
    } catch {
      alert(link); // fallback: show link to copy manually
    }
  };
}

/* ---------------------------------------------
 * In-match quick actions
 * ------------------------------------------- */
const btnSwap = qs('#btnSwap');
if (btnSwap) {
  btnSwap.onclick = () => {
    if (VIEW_ONLY) return;
    const inn = curInn();
    inn.striker = 1 - inn.striker;
    renderAll();
    autosave();
  };
}

const btnNewOver = qs('#btnNewOver');
if (btnNewOver) {
  btnNewOver.onclick = () => {
    if (VIEW_ONLY) return;
    newOver(State);
    renderAll();
    autosave();
  };
}

const btnChangeInnings = qs('#btnChangeInnings');
if (btnChangeInnings) {
  btnChangeInnings.onclick = () => {
    if (VIEW_ONLY) return;
    changeInnings(State);
    hydrateInputs();
    renderAll();
    autosave();
  };
}

/* ---------------------------------------------
 * Live input autosave (ignored in view-only)
 * ------------------------------------------- */
[
  'title',
  'oversPerSide',
  'ballsPerOver',
  'teamA',
  'teamB',
  'toss',
  'opted',
  'batter1',
  'batter2',
  'bowler',
].forEach((id) => {
  const el = qs('#' + id);
  if (!el) return;
  el.addEventListener('input', () => {
    if (VIEW_ONLY) return;
    readInputs();
    autosave();
  });
});

/* ---------------------------------------------
 * Load by ID (direct open), when not already in memory
 * ------------------------------------------- */
(async function initFromQuery() {
  if (!SHARE_ID) return;
  // If we’re the scorer (not view-only) opening a saved match by link
  if (!VIEW_ONLY) {
    const j = await (await api('load', { id: SHARE_ID })).json();
    if (j.ok) {
      Object.assign(State, j.payload);
      State.saveId = SHARE_ID;
      hydrateInputs();
      renderAll();
      autosave();
    }
  }
})();
