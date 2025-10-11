/**
 * assets/js/ui.js
 * UI glue:
 *  - Populates ball-by-ball dropdown
 *  - Binds pad buttons & dropdown submit
 *  - Shows nice player picker modal for new batter/bowler
 *  - Hooks into auto-new-over (from scoring.js)
 */

import { qs, qsa, haptic } from './util.js';
import { State, curInn } from './state.js';
import { handleEvent, newOver as domainNewOver } from './scoring.js';

/* ---------------- Read-only toggle ---------------- */
let READ_ONLY = false;
export function setReadOnly(v){ READ_ONLY = !!v; document.body.classList.toggle('read-only', READ_ONLY); }

/* ---------------- Delivery dropdown ---------------- */
export function initDeliveryPicker(handle) {
  const sel = qs('#deliveryPicker');
  const btn = qs('#applyDelivery');
  if (!sel || !btn) return;

  // Populate (0-6, wides, nb, bye, lb, wicket)
  sel.innerHTML = `
    <optgroup label="Runs">
      <option value="dot">Dot (0)</option>
      <option value="1">1 run</option>
      <option value="2">2 runs</option>
      <option value="3">3 runs</option>
      <option value="4">Boundary 4</option>
      <option value="6">Sixer (6)</option>
    </optgroup>
    <optgroup label="Extras">
      <option value="wide">Wide (+1)</option>
      <option value="noball">No-ball (+1 + bat runs)</option>
      <option value="bye">Bye</option>
      <option value="legbye">Leg Bye</option>
    </optgroup>
    <optgroup label="Special">
      <option value="wicket">Wicket</option>
    </optgroup>
  `;

  btn.onclick = async () => {
    const v = sel.value;
    if (!v) return;

    if (v === 'wide') {
      openWideModal((ev, opts) => handle(ev, opts));
      return;
    }
    if (v === 'noball') {
      openNoBallModal((ev, opts) => handle(ev, opts));
      return;
    }
    if (v === 'bye' || v === 'legbye') {
      const n = Number(prompt(`${v === 'bye' ? 'Byes' : 'Leg byes'} runs?`, '1') || '1');
      handle(v, { runs: n });
      return;
    }
    handle(v);
  };
}

/* ---------------- Scoring pad binding ------------- */
export function bindPad(handle){
  // Buttons
  qsa('#padgrid .btn').forEach(b=>{
    const ev = b.dataset.ev;
    const pressFX = ()=>{ b.classList.remove('pressed'); void b.offsetHeight; b.classList.add('pressed'); };

    if(ev==='noball'){
      b.addEventListener('click', ()=>{ haptic('light'); openNoBallModal(handle); pressFX(); });
    } else if(ev==='wide'){
      b.addEventListener('click', ()=>{ haptic('light'); openWideModal(handle); pressFX(); });
    } else {
      b.addEventListener('click', ()=>{
        handle(ev);
        // UI haptics
        if(ev==='4' || ev==='6') haptic('medium');
        else if(ev==='wicket'){ haptic('heavy'); openNewBatterPicker(); }
        else haptic('tap');
        pressFX();
      });
    }
  });

  // delivery dropdown
  initDeliveryPicker(handle);

  // Share recap button
  qs('#btnShareRecap')?.addEventListener('click', async ()=>{ haptic('light'); await shareRecap(); });
}

/* ---------------- Inputs & render ----------------- */
export function readInputs(){
  const inn = curInn();
  const b1 = qs('#batter1'); if(b1) inn.batters[0] = b1.value || '';
  const b2 = qs('#batter2'); if(b2) inn.batters[1] = b2.value || '';
  const bw = qs('#bowler');  if(bw) inn.bowler      = bw.value || '';
}

export function hydrateInputs(){
  const inn = curInn();
  if(qs('#batter1')) qs('#batter1').value = inn.batters?.[0] || '';
  if(qs('#batter2')) qs('#batter2').value = inn.batters?.[1] || '';
  if(qs('#bowler'))  qs('#bowler').value  = inn.bowler || '';
}

export function renderAll(){
  const inn = curInn();
  const total = (inn.runs ?? 0) + '/' + (inn.wickets ?? 0);
  if(qs('#scoreNow')) qs('#scoreNow').textContent = total;

  // Auto new over prompt?
  if (inn.pendingNewOver) {
    inn.pendingNewOver = false;
    // Prompt for next bowler with a nice picker
    openNewBowlerPicker();
  }
}

/* ---------------- Modals: NB / WD ----------------- */
export function openNoBallModal(handle){
  const modal = qs('#nbModal'); if(!modal) return;
  modal.classList.remove('hidden');
  qsa('.nbpick', modal).forEach(btn=>{
    btn.onclick = ()=>{ modal.classList.add('hidden'); handle('noball', { batRuns:+btn.dataset.val }); };
  });
  qs('#nbCancel').onclick = ()=> modal.classList.add('hidden');
}
export function openWideModal(handle){
  const modal = qs('#wdModal'); if(!modal) return;
  modal.classList.remove('hidden');
  qsa('.wdpick', modal).forEach(btn=>{
    btn.onclick = ()=>{ modal.classList.add('hidden'); handle('wide', { runs:+btn.dataset.val }); };
  });
  qs('#wdCancel').onclick = ()=> modal.classList.add('hidden');
}

/* ---------------- Pretty Player Picker ------------- */
function getSetup() {
  try { return JSON.parse(localStorage.getItem('stumpvision_setup_payload') || 'null'); }
  catch { return null; }
}
function rosterFor(side /* 'bat' | 'bowl' */) {
  const setup = getSetup();
  const inn = curInn();
  let batIdx = inn.batting ?? (setup?.opening?.battingTeamIndex ?? 0);
  let bowlIdx = 1 - batIdx;
  const idx = side === 'bat' ? batIdx : bowlIdx;
  return (setup?.teams?.[idx]?.players || []).slice();
}

/** Generic picker that populates chips with search; calls onPick(name) */
function openPlayerPicker({ title = 'Select Player', side = 'bat', onPick }) {
  const modal = qs('#pickModal'); if (!modal) return;
  const titleEl = qs('#pickTitle', modal);
  const list = qs('#pickList', modal);
  const search = qs('#pickSearch', modal);

  titleEl.textContent = title;
  const names = rosterFor(side);

  // Build chips
  list.innerHTML = names.length
    ? names.map(n => `<button class="chip player" data-name="${n}">${initials(n)}<span>${n}</span></button>`).join('')
    : `<div class="hint center">No roster yet. Type a name below.</div>`;

  // Wire chips
  qsa('.chip.player', modal).forEach(ch => {
    ch.onclick = () => {
      const name = ch.dataset.name || '';
      if (name) {
        modal.classList.add('hidden');
        onPick(name);
      }
    };
  });

  // Enter input path
  search.value = '';
  search.onkeydown = (e) => {
    if (e.key === 'Enter') {
      const name = search.value.trim();
      if (name) {
        modal.classList.add('hidden');
        onPick(name);
      }
    }
  };

  // Close button
  qs('#pickCancel', modal).onclick = () => modal.classList.add('hidden');

  modal.classList.remove('hidden');
  search.focus();
}

function initials(n) {
  const parts = (n || '').split(/\s+/).filter(Boolean);
  const a = parts[0]?.[0] || '';
  const b = parts[1]?.[0] || '';
  return (a + b).toUpperCase();
}

/** After wicket */
export function openNewBatterPicker(){
  const inn = curInn();
  const slot = inn.striker ?? 0; // dismissed striker; replace in that slot
  openPlayerPicker({
    title: 'New Batter In',
    side: 'bat',
    onPick: (name) => {
      inn.batters[slot] = name;
      hydrateInputs(); renderAll();
      haptic('soft');
    }
  });
}

/** On new over */
export function openNewBowlerPicker(){
  const inn = curInn();
  openPlayerPicker({
    title: 'New Over — Pick Bowler',
    side: 'bowl',
    onPick: (name) => {
      inn.bowler = name;
      if(qs('#bowler')) qs('#bowler').value = name;
      haptic('soft');
    }
  });
}

/* ---------------- Share Recap MP4 (unchanged) ------ */
export async function shareRecap(){
  const id = (window.State && window.State.saveId) || (State && State.saveId);
  if(!id){ alert('Save the match first to generate a recap.'); return; }
  const res = await fetch(`api/renderCard.php?id=${encodeURIComponent(id)}`);
  const j = await res.json();
  if(!j.ok){ alert('Could not generate recap: ' + (j.error||'Unknown')); return; }

  if(j.mp4){
    const blob = await (await fetch(j.mp4)).blob();
    const file = new File([blob], 'StumpVisionRecap.mp4', {type:'video/mp4'});
    if(navigator.canShare && navigator.canShare({files:[file]})){
      try{ await navigator.share({title:'StumpVision Match Recap', files:[file]}); return; }catch{}
    }
    const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=file.name; a.click(); return;
  }

  if(j.fallback_png){
    const blob = await (await fetch(j.fallback_png)).blob();
    const file = new File([blob], 'StumpVisionRecap.png', {type:'image/png'});
    if(navigator.canShare && navigator.canShare({files:[file]})){
      try{ await navigator.share({title:'StumpVision Match Recap', files:[file]}); return; }catch{}
    }
    const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=file.name; a.click(); return;
  }

  alert('Recap generated, but no downloadable asset was returned.');
}
