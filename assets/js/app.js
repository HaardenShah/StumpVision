/**
 * assets/js/app.js
 * Boots the match UI, wires events, and connects scoring + UI helpers.
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
  openNewBowlerPicker,
} from './ui.js';

/* ---------------- URL Params / Viewer ---------------- */
const params = new URLSearchParams(location.search);
const VIEW_ONLY = params.get('view') === '1';
const SHARE_ID = params.get('id') || null;
let pollTimer = null;
let lastSavedAt = 0;

const api = (action, p = {}) => {
  const url = `api/matches.php?action=${action}` + (p.id ? `&id=${encodeURIComponent(p.id)}` : '');
  const opts = {
    method: p.body ? 'POST' : 'GET',
    headers: p.body ? { 'Content-Type': 'application/json' } : undefined,
    body: p.body ? JSON.stringify(p.body) : undefined,
  };
  return fetch(url, opts);
};

/* ---------------- Seed from setup ---------------- */
function maybeSeedFromSetup() {
  if (State.saveId) return;
  const raw = localStorage.getItem('stumpvision_setup_payload');
  if (!raw) return;
  try {
    const s = JSON.parse(raw);
    State.meta = { ...DEFAULT_META, ...s.meta };

    if (s.teams?.[0]) State.teams[0].name = s.teams[0].name || 'Team A';
    if (s.teams?.[1]) State.teams[1].name = s.teams[1].name || 'Team B';

    if (typeof s.opening?.battingTeamIndex === 'number') {
      const idx = s.opening.battingTeamIndex;
      State.innings[0].batting = idx;
      State.innings[0].bowling = 1 - idx;
    }
    const inn = curInn();
    if (s.opening) {
      inn.batters[0] = s.opening.striker || '';
      inn.batters[1] = s.opening.nonStriker || '';
      inn.striker = 0;
      inn.bowler = s.opening.bowler || '';
    }
  } catch {}
}

/* ---------------- Boot ---------------- */
hydrateFromLocal();
maybeSeedFromSetup();
hydrateInputs();
renderAll();

const handle = (ev, opts) => {
  if (VIEW_ONLY) return;
  readInputs();
  handleEvent(ev, State, opts || {});
  renderAll();
  autosave();
};

bindPad(handle);

/* ---------------- Viewer polling ---------------- */
if (VIEW_ONLY) {
  document.body.classList.add('read-only');
  setReadOnly(true);
  const hint = qs('#saveHint'); if (hint) hint.textContent = 'Live view — updates appear automatically.';
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
            hydrateInputs(); renderAll();
          }
        }
      } catch {}
    };
    (async () => { await poll(); pollTimer = setInterval(poll, 3000); })();
  }
}

/* ---------------- Top controls ---------------- */
qs('#btnSave')?.addEventListener('click', async () => {
  if (VIEW_ONLY) return;
  readInputs();
  const res = await api('save', { body: { id: State.saveId, payload: cloneState() } });
  const j = await res.json();
  const hint = qs('#saveHint');
  if (j.ok) { State.saveId = j.id; if (hint) hint.textContent = `Saved ✓ (id ${j.id})`; }
  else { if (hint) hint.textContent = 'Save failed'; }
});

qs('#btnOpen')?.addEventListener('click', async () => {
  if (VIEW_ONLY) return;
  const list = await (await api('list')).json();
  if (!list.ok || !list.items.length) { alert('No saved matches found.'); return; }
  const pick = prompt('Enter ID to load:\n' + list.items.map(x => `${x.id} — ${new Date(x.ts * 1000).toLocaleString()} — ${x.title}`).join('\n'));
  if (!pick) return;
  const j = await (await api('load', { id: pick })).json();
  if (j.ok) { Object.assign(State, j.payload); State.saveId = pick; hydrateInputs(); renderAll(); autosave(); }
  else alert('Load failed');
});

qs('#btnExport')?.addEventListener('click', () => {
  const blob = new Blob([JSON.stringify(cloneState(), null, 2)], { type: 'application/json' });
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
  a.download = (State.meta.title || 'stumpvision_match') + '.json'; a.click();
});

qs('#btnCopyShare')?.addEventListener('click', async () => {
  const base = location.origin + location.pathname;
  const link = State.saveId ? `${base}?id=${encodeURIComponent(State.saveId)}&view=1` : base;
  try {
    await navigator.clipboard.writeText(link);
    const hint = qs('#saveHint');
    if (hint) hint.textContent = State.saveId ? 'Live viewer link copied.' : 'Save first to generate a live link.';
  } catch { alert(link); }
});

/* ---------------- Quick actions ---------------- */
qs('#btnSwap')?.addEventListener('click', () => {
  if (VIEW_ONLY) return;
  const inn = curInn();
  inn.striker = 1 - (inn.striker || 0);
  renderAll(); autosave();
});

qs('#btnNewOver')?.addEventListener('click', () => {
  if (VIEW_ONLY) return;
  domainNewOver(State);
  renderAll(); autosave();
  // open classy picker
  openNewBowlerPicker();
});

qs('#btnChangeInnings')?.addEventListener('click', () => {
  if (VIEW_ONLY) return;
  changeInnings(State);
  hydrateInputs(); renderAll(); autosave();
});

/* ---------------- Live input autosave ---------------- */
['title','oversPerSide','ballsPerOver','teamA','teamB','toss','opted','batter1','batter2','bowler']
  .forEach(id => { const el = qs('#' + id); if (!el) return;
    el.addEventListener('input', () => { if (VIEW_ONLY) return; readInputs(); autosave(); });
  });

/* ---------------- Load by ID (if scorer) ---------------- */
(async function initFromQuery() {
  if (!SHARE_ID || VIEW_ONLY) return;
  const j = await (await api('load', { id: SHARE_ID })).json();
  if (j.ok) { Object.assign(State, j.payload); State.saveId = SHARE_ID; hydrateInputs(); renderAll(); autosave(); }
})();
