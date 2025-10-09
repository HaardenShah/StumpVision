import {qs, qsa} from './util.js';
import {State, curInn, cloneState} from './state.js';
import {symbol} from './scoring.js';

export function bindPad(handle){
  qsa('#padgrid .btn').forEach(b=>{
    // noball opens modal instead of immediate handle
    if(b.dataset.ev === 'noball'){
      b.addEventListener('click', ()=> openNoBallModal(handle));
    } else {
      b.addEventListener('click', ()=> handle(b.dataset.ev));
    }
  });
}

export function readInputs(){
  State.meta.title = qs('#title').value.trim();
  State.meta.oversPerSide = Number(qs('#oversPerSide').value||10);
  State.meta.ballsPerOver = Number(qs('#ballsPerOver').value||6);
  State.teams[0].name = qs('#teamA').value||'Team A';
  State.teams[1].name = qs('#teamB').value||'Team B';
  State.meta.toss = qs('#toss').value; State.meta.opted = qs('#opted').value;
  const inn = curInn();
  inn.batters[0] = qs('#batter1').value; inn.batters[1] = qs('#batter2').value; inn.bowler = qs('#bowler').value;
}

export function hydrateInputs(){
  qs('#title').value = State.meta.title||'';
  qs('#oversPerSide').value = State.meta.oversPerSide;
  qs('#ballsPerOver').value = State.meta.ballsPerOver;
  qs('#teamA').value = State.teams[0].name; qs('#teamB').value = State.teams[1].name;
  qs('#toss').value = State.meta.toss; qs('#opted').value = State.meta.opted;
  const inn = curInn();
  qs('#batter1').value = inn.batters[0]||''; qs('#batter2').value = inn.batters[1]||''; qs('#bowler').value = inn.bowler||'';
}

export function renderAll(){
  const inn = curInn();
  qs('#battingTeamLbl').textContent = State.teams[inn.batting].name;
  qs('#tAname').textContent = State.teams[0].name;
  qs('#tBname').textContent = State.teams[1].name;
  qs('#inningsBadge').innerHTML = `Innings ${State.innNow+1} • <span id="battingTeamLbl">${State.teams[inn.batting].name}</span> batting`;
  qs('#strikerLbl').textContent = inn.batters[inn.striker]||'—';

  qs('#scoreNow').textContent = `${inn.runs}/${inn.wickets}`;
  qs('#oversNow').textContent = `${inn.overs}.${inn.legalBalls}`;
  const totalBalls = inn.overs * State.meta.ballsPerOver + inn.legalBalls;
  const rr = totalBalls>0 ? (inn.runs / (totalBalls/6)) : 0; qs('#rrNow').textContent = rr.toFixed(2);
  qs('#wickets').textContent = inn.wickets;
  const A=State.innings[0], B=State.innings[1];
  qs('#tAscore').textContent = `${A.runs}/${A.wickets}`; qs('#tAovers').textContent = `${A.overs}.${A.legalBalls}`;
  qs('#tBscore').textContent = `${B.runs}/${B.wickets}`; qs('#tBovers').textContent = `${B.overs}.${B.legalBalls}`;

  // target / RRR
  if(inn.target){
    qs('#targetBadge').textContent = inn.target;
    const ballsLeft = (State.meta.oversPerSide*State.meta.ballsPerOver) - totalBalls;
    const req = (inn.target - inn.runs);
    qs('#reqRR').textContent = (req>0 && ballsLeft>0) ? ((req)/(ballsLeft/6)).toFixed(2) : '—';
  } else { qs('#targetBadge').textContent='—'; qs('#reqRR').textContent='—'; }

  // over strip
  const strip = qs('#thisOver'); strip.innerHTML='';
  inn.overBalls.forEach(s=>{ const d=document.createElement('div'); d.className='chip'; d.textContent=s; strip.appendChild(d); });

  // free hit badge
  qs('#freeHitBadge').classList.toggle('hidden', !inn.freeHit);

  // extras
  qs('#x_nb').textContent = inn.extras.nb;
  qs('#x_wd').textContent = inn.extras.wd;
  qs('#x_b').textContent  = inn.extras.b;
  qs('#x_lb').textContent = inn.extras.lb;

  renderLog();
}

export function renderLog(){
  const inn = curInn();
  const log = qs('#log');
  const rows = inn.timeline.slice().reverse().map(e=>{
    const when = new Date(e.t).toLocaleTimeString();
    const who  = `${e.batters?.[e.striker]||'Striker'} vs ${e.bowler||'Bowler'}`;
    const tags = [];
    if(e.extra) tags.push(`<span class="pill">${e.extra}${e.extra==='nb' && e.batRuns?`+${e.batRuns}`:''}</span>`);
    if(e.freeHitWicketIgnored) tags.push('<span class="pill">free-hit</span>');
    const tagHtml = tags.join(' ');
    return `<div class="row space-between"><div>${when} • ${who}</div><div><b>${symbol(e)}</b> ${e.wicket?'<span class="pill" style="border-color:#7f1d1d;color:#fca5a5">W</span>':''} ${tagHtml}</div></div>`;
  }).join('');
  log.innerHTML = rows || '<div class="hint">No deliveries yet.</div>';
}

/* ---------- No-ball modal ---------- */
function openNoBallModal(handle){
  const modal = qs('#nbModal');
  modal.classList.remove('hidden');
  const onPick = (e)=>{
    const v = Number(e.currentTarget.dataset.val || 0);
    handle('noball', {batRuns:v});
    closeNoBallModal();
  };
  qsa('.nbpick', modal).forEach(btn=>{
    btn.addEventListener('click', onPick, { once:true });
  });
  qs('#nbCancel').addEventListener('click', closeNoBallModal, { once:true });
}

function closeNoBallModal(){
  qs('#nbModal').classList.add('hidden');
}