import {qs} from './util.js';
import {State, hydrateFromLocal, autosave, cloneState, curInn, newInnings, DEFAULT_META} from './state.js';
import {handleEvent, newOver, changeInnings} from './scoring.js';
import {bindPad, readInputs, hydrateInputs, renderAll, setReadOnly} from './ui.js';

// read-only mode if ?view=1
const params = new URLSearchParams(location.search);
const VIEW_ONLY = params.get('view') === '1';
let POLL_TIMER = null;
let lastSavedAt = 0;

hydrateFromLocal();
hydrateInputs();
renderAll();

const handle = (ev, opts)=>{ if(VIEW_ONLY) return; readInputs(); handleEvent(ev, State, opts); renderAll(); autosave(); };
bindPad(handle);

if (VIEW_ONLY) {
  document.body.classList.add('read-only');
  setReadOnly(true);
  qs('#saveHint').textContent = 'Live view — updates will appear automatically.';
  // poll if there's an id
  const id = params.get('id');
  if (id) {
    const poll = async ()=>{
      try{
        const j = await (await fetch(`api/matches.php?action=load&id=${encodeURIComponent(id)}`)).json();
        if(j.ok){
          if(!lastSavedAt || j.payload.__saved_at !== lastSavedAt){
            Object.assign(State, j.payload);
            lastSavedAt = j.payload.__saved_at || Date.now();
            hydrateInputs(); renderAll();
          }
        }
      }catch(e){}
    };
    await poll();
    POLL_TIMER = setInterval(poll, 3000);
  }
}

// normal controls
qs('#btnSwap').onclick = ()=>{ if(VIEW_ONLY) return; const inn=curInn(); inn.striker=1-inn.striker; renderAll(); autosave(); };
qs('#btnNewOver').onclick = ()=>{ if(VIEW_ONLY) return; newOver(State); renderAll(); autosave(); };
qs('#btnChangeInnings').onclick = ()=>{ if(VIEW_ONLY) return; changeInnings(State); hydrateInputs(); renderAll(); autosave(); };
qs('#btnReset').onclick = ()=>{
  if(VIEW_ONLY) return;
  if(confirm('Reset current match?')){
    Object.assign(State,{ meta:{...DEFAULT_META}, teams:[{name:'Team A'},{name:'Team B'}], innings:[newInnings(0),newInnings(1)], innNow:0, saveId:null });
    hydrateInputs(); renderAll(); autosave();
  }
};
qs('#btnNew').onclick = ()=>{
  if(VIEW_ONLY) return;
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
  if(VIEW_ONLY) return;
  readInputs();
  const res = await api('save', { body:{ id: State.saveId, payload: cloneState() } });
  const j = await res.json();
  qs('#saveHint').textContent = j.ok?`Saved ✓ (id ${j.id})`:'Save failed';
  if(j.ok) State.saveId = j.id;
};

qs('#btnOpen').onclick = async ()=>{
  if(VIEW_ONLY) return;
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
  const base = location.origin + location.pathname;
  const link = State.saveId ? `${base}?id=${encodeURIComponent(State.saveId)}&view=1` : base;
  await navigator.clipboard.writeText(link);
  qs('#saveHint').textContent = State.saveId ? 'Live viewer link copied.' : 'Save first to generate a live link.';
};

// Load by id param (share link)
(async function(){
  const urlId=new URLSearchParams(location.search).get('id'); if(!urlId) return;
  const j = await (await api('load',{id:urlId})).json();
  if(j.ok){ Object.assign(State, j.payload); State.saveId=urlId; hydrateInputs(); renderAll(); }
})();

// Live input binding for autosave (disabled in view mode)
['title','oversPerSide','ballsPerOver','teamA','teamB','toss','opted','batter1','batter2','bowler'].forEach(id=>{
  const el = qs('#'+id); if(!el) return;
  el.addEventListener('input', ()=>{ if(VIEW_ONLY) return; readInputs(); autosave(); });
});