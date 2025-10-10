/**
 * assets/js/setup.js
 * Fixes:
 *  - Step 3 selects are always populated from Step 2 rosters.
 *  - If a roster is too short, show inline help + add-player actions.
 *  - Smooth step transitions + haptics.
 */

import { qs as $, qsa as $$, haptic } from './util.js';

const state = {
  title: '', overs: 10, bpo: 6,
  toss: 'A', opted: 'bat',            // moved to Step 3
  teamA: 'Team A', teamB: 'Team B',
  playersA: [], playersB: [],
  opening: { striker: '', nonStriker: '', bowler: '' }
};

/* ---------- utilities ---------- */
function syncFromUI(){
  // step 1
  if ($('#s_title')) state.title = $('#s_title').value.trim();
  if ($('#s_overs')) state.overs = +$('#s_overs').value || 10;
  if ($('#s_bpo'))   state.bpo   = +$('#s_bpo').value   || 6;
  // step 2
  if ($('#s_teamA')) state.teamA = $('#s_teamA').value || 'Team A';
  if ($('#s_teamB')) state.teamB = $('#s_teamB').value || 'Team B';
  // step 3
  if ($('#s_toss'))  state.toss  = $('#s_toss').value;
  if ($('#s_opted')) state.opted = $('#s_opted').value;
}

function addPlayer(team){
  const name = prompt(`Add player to Team ${team}:`);
  if(!name) return;
  (team==='A'?state.playersA:state.playersB).push(name.trim());
  renderLists(); haptic('soft');
  if (cur===3) populateOpeners(); // live-refresh Step 3
}

function removePlayer(team, idx){
  const arr = team==='A'?state.playersA:state.playersB;
  arr.splice(idx,1); renderLists(); haptic('soft');
  if (cur===3) populateOpeners();
}

function renderLists(){
  const mk = (arr, team)=>{
    const ul = team==='A' ? $('#listA') : $('#listB');
    if(!ul) return;
    ul.innerHTML = arr.map((n,i)=>`
      <li class="chip">${n}
        <button class="x" data-team="${team}" data-i="${i}" title="Remove">×</button>
      </li>`).join('');
  };
  mk(state.playersA,'A'); mk(state.playersB,'B');
  $$('#listA .x').forEach(b=> b.onclick = ()=> removePlayer('A', +b.dataset.i));
  $$('#listB .x').forEach(b=> b.onclick = ()=> removePlayer('B', +b.dataset.i));
}

/* ---------- toss/roster → openers ---------- */
function battingTeamIndex(){
  if(state.toss==='A') return state.opted==='bat' ? 0 : 1;
  return state.opted==='bat' ? 1 : 0;
}

/** Populate Step-3 selects. Always leaves at least one option so the select opens. */
function populateOpeners(){
  syncFromUI();

  const batIdx  = battingTeamIndex();
  const bowlIdx = 1 - batIdx;

  const batName  = batIdx===0 ? state.teamA : state.teamB;
  const bowlName = bowlIdx===0 ? state.teamA : state.teamB;

  const batRoster  = (batIdx===0 ? state.playersA : state.playersB).slice();
  const bowlRoster = (bowlIdx===0 ? state.playersA : state.playersB).slice();

  // Ensure <select> has something to open even if roster empty
  if (batRoster.length === 0) batRoster.push('(enter later)');
  if (batRoster.length === 1) batRoster.push('(enter later)');
  if (bowlRoster.length === 0) bowlRoster.push('(enter later)');

  $('#openersHint').textContent = `${batName} batting • ${bowlName} bowling`;

  const strikerSel = $('#s_striker');
  const nonSel     = $('#s_nonStriker');
  const bowlerSel  = $('#s_bowler');

  const opt = (name)=> `<option value="${name}">${name}</option>`;
  strikerSel.innerHTML = batRoster.map(opt).join('');
  nonSel.innerHTML     = batRoster.map(opt).join('');
  bowlerSel.innerHTML  = bowlRoster.map(opt).join('');

  // Defaults (use real roster where possible)
  state.opening.striker    = (batRoster[0] && batRoster[0] !== '(enter later)') ? batRoster[0] : '';
  state.opening.nonStriker = (batRoster[1] && batRoster[1] !== '(enter later)') ? batRoster[1] : '';
  state.opening.bowler     = (bowlRoster[0] && bowlRoster[0] !== '(enter later)') ? bowlRoster[0] : '';

  // Set select values (fall back to placeholder if blank)
  strikerSel.value = state.opening.striker || batRoster[0];
  nonSel.value     = state.opening.nonStriker || batRoster[1];
  bowlerSel.value  = state.opening.bowler || bowlRoster[0];

  strikerSel.onchange = ()=> state.opening.striker = strikerSel.value === '(enter later)' ? '' : strikerSel.value;
  nonSel.onchange     = ()=> state.opening.nonStriker = nonSel.value === '(enter later)' ? '' : nonSel.value;
  bowlerSel.onchange  = ()=> state.opening.bowler = bowlerSel.value === '(enter later)' ? '' : bowlerSel.value;

  // Inline help if rosters are insufficient
  const needHelp = ( (state.playersA.length + state.playersB.length) === 0 )
                || ( (batIdx===0 ? state.playersA.length : state.playersB.length) < 2 )
                || ( (bowlIdx===0 ? state.playersA.length : state.playersB.length) < 1 );
  $('#inlineRosterHelp').classList.toggle('hidden', !needHelp);
}

/* ---------- review card & payload ---------- */
function renderReview(){
  const batIdx = battingTeamIndex();
  const batTeam = batIdx===0 ? state.teamA : state.teamB;
  const bowlTeam = batIdx===0 ? state.teamB : state.teamA;
  $('#review').innerHTML = `
    <div class="card lite fade-in" style="margin-top:10px">
      <div><b>Title:</b> ${state.title || '—'}</div>
      <div><b>Overs/Balls:</b> ${state.overs} / ${state.bpo}</div>
      <div><b>Toss:</b> Team ${state.toss} &nbsp; <b>Opted:</b> ${state.opted}</div>
      <div class="mt"><b>Batting first:</b> ${batTeam} • <b>Bowling:</b> ${bowlTeam}</div>
      <div class="mt"><b>Openers:</b> ${(state.opening.striker||'—')} & ${(state.opening.nonStriker||'—')}</div>
      <div><b>Opening Bowler:</b> ${(state.opening.bowler||'—')}</div>
    </div>`;
}

function savePayload(){
  const payload = {
    meta:{ title:state.title, oversPerSide:state.overs, ballsPerOver:state.bpo, toss:state.toss, opted:state.opted },
    teams:[
      { name:state.teamA, players:state.playersA },
      { name:state.teamB, players:state.playersB }
    ],
    opening:{
      battingTeamIndex: battingTeamIndex(),
      striker: state.opening.striker,
      nonStriker: state.opening.nonStriker,
      bowler: state.opening.bowler
    }
  };
  localStorage.setItem('stumpvision_setup_payload', JSON.stringify(payload));
}

/* ---------- stepper ---------- */
let cur = 1;
function goto(step){
  if(step===cur) return;
  const forward = step > cur;

  // stepper UI
  $$('.step').forEach(s=> s.classList.toggle('current', +s.dataset.step===step));

  // animate panels
  const from = $(`.step-panel[data-step="${cur}"]`);
  const to   = $(`.step-panel[data-step="${step}"]`);
  if(from) from.classList.add(forward?'slide-left-out':'slide-right-out');
  if(to){
    to.classList.remove('hidden','slide-left-out','slide-right-out');
    to.classList.add(forward?'slide-right-in':'slide-left-in');
    setTimeout(()=>{
      to.classList.remove('slide-right-in','slide-left-in');
      if(from){ from.classList.add('hidden'); from.classList.remove('slide-left-out','slide-right-out'); }
    }, 260);
  }
  cur = step;

  // Step 3 prep
  if(step===3){
    syncFromUI();
    populateOpeners();
    renderReview();
  }
  haptic('light');
}

/* ---------- wiring ---------- */
$$('.next').forEach(b=> b.addEventListener('click', ()=>{ syncFromUI(); goto(cur+1); }));
$$('.prev').forEach(b=> b.addEventListener('click', ()=> goto(cur-1)));
$$('.step').forEach(s=> s.addEventListener('click', ()=>{ const n=+s.dataset.step; if(n>=1 && n<=3) goto(n); }));

$('[data-add="A"]').onclick = ()=> addPlayer('A');
$('[data-add="B"]').onclick = ()=> addPlayer('B');
$('#inlineRosterHelp [data-add="A"]')?.addEventListener('click', ()=> addPlayer('A'));
$('#inlineRosterHelp [data-add="B"]')?.addEventListener('click', ()=> addPlayer('B'));

$('#startMatch').onclick = ()=>{
  syncFromUI();
  // Nicety: ensure we have picks; if not, we still allow start, but warn.
  if(!state.opening.striker || !state.opening.nonStriker || !state.opening.bowler){
    if(!confirm('Openers/bowler not fully selected. Continue anyway?')) return;
  }
  savePayload(); haptic('success'); location.href='index.php';
};

// init
renderLists();
