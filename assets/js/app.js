import {qs} from './util.js';
import {State, hydrateFromLocal, autosave, cloneState, curInn, newInnings, DEFAULT_META} from './state.js';
import {handleEvent, newOver, changeInnings} from './scoring.js';
import {bindPad, readInputs, hydrateInputs, renderAll} from './ui.js';

hydrateFromLocal();
hydrateInputs();
renderAll();

// handle(ev, opts?) signature to support no-ball batRuns
const handle = (ev, opts)=>{ readInputs(); handleEvent(ev, State, opts); renderAll(); autosave(); };

bindPad(handle);

qs('#btnSwap').onclick = ()=>{ const inn=curInn(); inn.striker=1-inn.striker; renderAll(); autosave(); };
qs('#btnNewOver').onclick = ()=>{ newOver(State); renderAll(); autosave(); };
qs('#btnChangeInnings').onclick = ()=>{ changeInnings(State); hydrateInputs(); renderAll(); autosave(); };
qs('#btnReset').onclick = ()=>{
  if(confirm('Reset current match?')){
    Object.assign(State,{ meta:{...DEFAULT_META}, teams:[{name:'Team A'},{name:'Team B'}], innings:[newInnings(0),newInnings(1)], innNow:0, saveId:null });
    hydrateInputs(); renderAll(); autosave();
  }
};
qs('#btnNew').onclick = ()=>{
  if(confirm('Start a brand new match? Unsaved progress will be lost.')){
    Object.assign(State,{ meta:{...DEFAULT_META}, teams:[{name:'Team A'},{name:'Team B'}], innings:[newInnings(0),newInnings(1)], innNow:0, saveId:null });
    hydrateInputs(); renderAll(); autosave();
  }
};

// API helpers
const api = (action, params={})=> fetch(`api/matches.php?action=${action}${params.id?`&id=${encodeURIComponent(params.id)}`:''}`, {
  method: params.body?'POST':'GET',
  body: params.body?JSON.stringify(params.body):undefined,
  headers: params.body?{'Content-Type':'application/json'}:{}
});

qs('#btnSave').onclick = async ()=>{
  readInputs();
  const res = await api('save', { body:{ id: State.saveId, payload: cloneState() } });
  const j = await res.json();
  qs('#saveHint').textContent = j.ok?`Saved ✓ (id ${j.id})`:'Save failed';
  if(j.ok) State.saveId = j.id;
};

qs('#btnOpen').onclick = async ()=>{
  const list = await (await api('list')).json();
  if(!list.ok || !list.items.length){ alert('No saved matches found.'); return; }
  const pick = prompt('Enter ID to load:\n' + list.items.map(x=>`${x.id} — ${new Date(x.ts*1000).toLocaleString()} — ${x.title}`).join('\n'));
  if(!pick) return;
  const j = await (await api('load', {id:pick})).json();
  if(j.ok){ Object.assign(State, j.payload); State.saveId=pick; hydrateInputs(); renderAll(); autosave(); }
  else alert('Load failed');
};

qs('#btnExport').onclick = ()=>{
  const blob = new Blob([JSON.stringify(cloneState(),null,2)], {type:'application/json'});
  const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=(State.meta.title||'stumpvision_match')+'.json'; a.click();
};

qs('#btnCopyShare').onclick = async ()=>{
  if(!State.saveId){ qs('#saveHint').textContent='Save first to get a shareable link.'; return; }
  const url = location.origin + location.pathname + `?id=${encodeURIComponent(State.saveId)}`;
  await navigator.clipboard.writeText(url); qs('#saveHint').textContent='Link copied to clipboard.';
};

// Load by id param (share link)
(function(){
  const urlId=new URLSearchParams(location.search).get('id'); if(!urlId) return;
  api('load',{id:urlId}).then(r=>r.json()).then(j=>{
    if(j.ok){ Object.assign(State, j.payload); State.saveId=urlId; hydrateInputs(); renderAll(); }
  });
})();

// Live input binding for autosave
['title','oversPerSide','ballsPerOver','teamA','teamB','toss','opted','batter1','batter2','bowler'].forEach(id=>{
  const el = qs('#'+id); if(!el) return; el.addEventListener('input', ()=>{ readInputs(); autosave(); });
});