/**
 * assets/js/ui.js
 * Adds:
 *  - “New Batter” modal automatically after a wicket
 *  - “Pick Bowler” modal when you tap “New Over”
 *  - Share Recap integration (unchanged from previous answer)
 */

import { qs, qsa, haptic } from './util.js';
import { State, curInn } from './state.js';

/* ---------------- Read-only toggle ---------------- */
let READ_ONLY = false;
export function setReadOnly(v){ READ_ONLY = !!v; document.body.classList.toggle('read-only', READ_ONLY); }

/* ---------------- Scoring pad binding ------------- */
export function bindPad(handle){
  qsa('#padgrid .btn').forEach(b=>{
    const ev = b.dataset.ev;
    const pressFX = ()=>{ b.classList.remove('pressed'); void b.offsetHeight; b.classList.add('pressed'); };

    if(ev==='noball'){
      b.addEventListener('click', ()=>{ haptic('light'); openNoBallModal(handle); pressFX(); });
    } else if(ev==='wide'){
      b.addEventListener('click', ()=>{ haptic('light'); openWideModal(handle); pressFX(); });
    } else {
      b.addEventListener('click', ()=>{
        // call domain logic
        handle(ev);

        // UI side-effects
        if(ev==='4' || ev==='6') haptic('medium');
        else if(ev==='wicket'){ haptic('heavy'); openNewBatterModal(); }
        else haptic('tap');

        pressFX();
      });
    }
  });

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

/* ---------------- New Batter / Bowler ------------- */
/** Prompt after a wicket. Lets you type or pick quickly. */
export function openNewBatterModal(){
  // Build lightweight prompt using native prompt for speed (or replace with custom modal)
  const inn = curInn();
  const currentBatters = inn.batters?.filter(Boolean) || [];
  const taken = new Set(currentBatters);
  // Try to aggregate a roster from team names (payload from setup)
  const setup = JSON.parse(localStorage.getItem('stumpvision_setup_payload') || 'null');
  const batIdx = inn.batting ?? (setup?.opening?.battingTeamIndex ?? 0);
  const roster = (batIdx===0 ? setup?.teams?.[0]?.players : setup?.teams?.[1]?.players) || [];

  // Suggest first unused name
  const suggestion = roster.find(n=>!taken.has(n)) || '';

  const name = prompt('New batter in:', suggestion);
  if(!name) return;
  // Put new batter in the dismissed striker’s slot
  const slot = inn.striker ?? 0;
  inn.batters[slot] = name.trim();
  hydrateInputs(); renderAll();
}

/** Call this when you tap “New Over” (we hook this in index/app). */
export function openNewBowlerModal(){
  const inn = curInn();
  const setup = JSON.parse(localStorage.getItem('stumpvision_setup_payload') || 'null');
  const bowlIdx = (inn.bowling != null) ? inn.bowling : ( (setup?.opening?.battingTeamIndex ?? 0) === 0 ? 1 : 0 );
  const roster = (bowlIdx===0 ? setup?.teams?.[0]?.players : setup?.teams?.[1]?.players) || [];
  const name = prompt('New over — bowler:', roster[0] || (inn.bowler || ''));
  if(!name) return;
  inn.bowler = name.trim();
  if(qs('#bowler')) qs('#bowler').value = inn.bowler;
}

/* ---------------- Share Recap MP4 ----------------- */
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
