// assets/js/setup.js — animated stepper + haptics
import { qs as $, qsa as $$, haptic } from './util.js';

const state = {
  title:'', overs:10, bpo:6, toss:'A', opted:'bat',
  teamA:'Team A', teamB:'Team B',
  playersA:[], playersB:[]
};

function syncFromUI(){
  state.title = $('#s_title').value.trim();
  state.overs = +$('#s_overs').value || 10;
  state.bpo   = +$('#s_bpo').value || 6;
  state.toss  = $('#s_toss').value;
  state.opted = $('#s_opted').value;
  state.teamA = $('#s_teamA').value || 'Team A';
  state.teamB = $('#s_teamB').value || 'Team B';
}

function addPlayer(team){
  const name = prompt(`Add player to Team ${team}:`);
  if(!name) return;
  (team==='A'?state.playersA:state.playersB).push(name.trim());
  renderLists();
  haptic('soft');
}
function removePlayer(team, idx){
  const arr = team==='A'?state.playersA:state.playersB;
  arr.splice(idx,1); renderLists(); haptic('soft');
}

function renderLists(){
  const mk = (arr, team)=>{
    const ul = team==='A'? $('#listA') : $('#listB');
    ul.innerHTML = arr.map((n,i)=>`<li class="chip">${n}<button class="x" data-team="${team}" data-i="${i}">×</button></li>`).join('');
  };
  mk(state.playersA,'A'); mk(state.playersB,'B');
  $$('#listA .x').forEach(b=> b.onclick = ()=> removePlayer('A', +b.dataset.i));
  $$('#listB .x').forEach(b=> b.onclick = ()=> removePlayer('B', +b.dataset.i));
}

function renderReview(){
  $('#review').innerHTML = `
    <div class="grid2 fade-in">
      <div>
        <div><b>Title:</b> ${state.title || '—'}</div>
        <div><b>Overs/Balls:</b> ${state.overs} / ${state.bpo}</div>
        <div><b>Toss:</b> Team ${state.toss}, <b>Opted:</b> ${state.opted}</div>
      </div>
      <div>
        <div><b>${state.teamA}</b>: ${state.playersA.join(', ') || '—'}</div>
        <div><b>${state.teamB}</b>: ${state.playersB.join(', ') || '—'}</div>
      </div>
    </div>`;
}

function savePayload(){
  const payload = {
    meta:{ title:state.title, oversPerSide:state.overs, ballsPerOver:state.bpo, toss:state.toss, opted:state.opted },
    teams:[ {name:state.teamA, players:state.playersA}, {name:state.teamB, players:state.playersB} ]
  };
  localStorage.setItem('stumpvision_setup_payload', JSON.stringify(payload));
}

let cur = 1;
function goto(step){
  if(step===cur) return;
  const forward = step > cur;
  // stepper state
  $$('.step').forEach(s=> s.classList.toggle('current', +s.dataset.step===step));
  // panels animated
  const from = $(`.step-panel[data-step="${cur}"]`);
  const to   = $(`.step-panel[data-step="${step}"]`);
  if(from){ from.classList.add(forward?'slide-left-out':'slide-right-out'); }
  if(to){
    to.classList.remove('hidden','slide-left-out','slide-right-out');
    to.classList.add(forward?'slide-right-in':'slide-left-in');
    // cleanup classes after animation
    setTimeout(()=>{
      to.classList.remove('slide-right-in','slide-left-in');
      if(from){ from.classList.add('hidden'); from.classList.remove('slide-left-out','slide-right-out'); }
    }, 260);
  }
  cur = step;
  if(step===3){ syncFromUI(); renderReview(); }
  haptic('light');
}

/* Wiring */
$$('.next').forEach(b=> b.addEventListener('click', ()=>{ syncFromUI(); goto(cur+1); }));
$$('.prev').forEach(b=> b.addEventListener('click', ()=> goto(cur-1)));
$('[data-add="A"]').onclick = ()=> addPlayer('A');
$('[data-add="B"]').onclick = ()=> addPlayer('B');

// Clickable stepper
$$('.step').forEach(s=>{
  s.addEventListener('click', ()=>{ const n = +s.dataset.step; if(n>=1 && n<=3) goto(n); });
});

$('#startMatch').onclick = ()=>{
  syncFromUI(); savePayload(); haptic('success'); location.href='index.php';
};

renderLists();
