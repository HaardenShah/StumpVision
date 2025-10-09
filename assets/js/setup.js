// setup.js — handles multi-step wizard and seeds localStorage payload
const $ = (s, el=document)=>el.querySelector(s);
const $$ = (s, el=document)=>Array.from(el.querySelectorAll(s));

const state = {
  title: '', overs: 10, bpo: 6, toss: 'A', opted: 'bat',
  teamA: 'Team A', teamB: 'Team B',
  playersA: [], playersB: []
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
  (team === 'A' ? state.playersA : state.playersB).push(name.trim());
  renderLists();
}

function removePlayer(team, idx){
  const arr = team==='A'? state.playersA : state.playersB;
  arr.splice(idx,1);
  renderLists();
}

function renderLists(){
  const mk = (arr, team)=>{
    const ul = team==='A' ? $('#listA') : $('#listB');
    ul.innerHTML = arr.map((n,i)=>`<li>${n}<button class="x" data-team="${team}" data-i="${i}">×</button></li>`).join('');
  };
  mk(state.playersA,'A'); mk(state.playersB,'B');
  $$('#listA .x').forEach(b=> b.onclick = ()=> removePlayer('A', +b.dataset.i));
  $$('#listB .x').forEach(b=> b.onclick = ()=> removePlayer('B', +b.dataset.i));
}

function renderReview(){
  $('#review').innerHTML = `
    <div class="grid2">
      <div>
        <div><b>Title:</b> ${state.title || '—'}</div>
        <div><b>Overs/Balls:</b> ${state.overs} / ${state.bpo}</div>
        <div><b>Toss:</b> Team ${state.toss}, <b>Opted:</b> ${state.opted}</div>
      </div>
      <div>
        <div><b>${state.teamA}</b>: ${state.playersA.join(', ') || '—'}</div>
        <div><b>${state.teamB}</b>: ${state.playersB.join(', ') || '—'}</div>
      </div>
    </div>
  `;
}

function savePayload(){
  const payload =
