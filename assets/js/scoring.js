import {curInn, ensureBatter, ensureBowler} from './state.js';

export const symbol = (e)=>{
  if(e.extra==='wd') return e.wideRuns ? `wd+${e.wideRuns-1}` : 'wd';
  if(e.extra==='nb') return e.batRuns ? `nb+${e.batRuns}` : 'nb';
  if(e.extra==='b')  return 'b';
  if(e.extra==='lb') return 'lb';
  if(e.wicket) return 'W';
  return String(e.runs);
};

// update batter and bowler stats for this event
function applyStats(inn, e){
  const strikerName = e.batters?.[e.striker] || '';
  const bowlerName  = e.bowler || '';
  ensureBatter(inn, strikerName);
  ensureBowler(inn, bowlerName);

  const bat = inn.batStats[strikerName];
  const bowl= inn.bowlStats[bowlerName];

  // BOWLER: runs conceded (wd & nb count to bowler; b & lb don't)
  let conceded = 0;
  if(e.extra==='wd') conceded += e.runs;
  else if(e.extra==='nb') conceded += e.runs;
  else if(e.extra==='b' || e.extra==='lb') conceded += 0;
  else conceded += e.runs;

  // BATTER: runs off the bat and balls faced rules
  let batRuns = 0, ballFaced = 0;
  if(e.extra==='nb'){ batRuns = e.batRuns||0; ballFaced = 0; }
  else if(e.extra==='wd'){ batRuns = 0; ballFaced = 0; }
  else if(e.extra==='b' || e.extra==='lb'){ batRuns = 0; ballFaced = 1; }
  else { batRuns = e.runs; ballFaced = 1; }

  // apply
  if(bat){
    bat.R += batRuns;
    bat.B += ballFaced;
    if(batRuns===4) bat[4] += 1;
    if(batRuns===6) bat[6] += 1;
  }
  if(bowl){
    if(e.legal) bowl.balls += 1;
    bowl.R += conceded;
    if(e.wicket) bowl.W += 1;
  }
}

/**
 * handleEvent(ev, State, opts)
 * - ev: 'dot','1','2','3','4','6','wicket','wide','noball','bye','legbye','undo'
 * - opts: { batRuns?: number } for 'noball', { runOns?: number } for 'wide'
 */
export function handleEvent(ev, State, opts={}){
  if(ev==='undo') return undo(State);

  const inn = curInn();
  const bpo = State.meta.ballsPerOver;
  const entry = {
    t:Date.now(), ev, runs:0, extra:null, legal:true,
    striker:inn.striker, bowler:inn.bowler, batters:[...inn.batters]
  };

  switch(ev){
    case 'dot': entry.runs=0; break;
    case '1': entry.runs=1; break;
    case '2': entry.runs=2; break;
    case '3': entry.runs=3; break;
    case '4': entry.runs=4; break;
    case '6': entry.runs=6; break;
    case 'wicket': entry.runs=0; entry.wicket=true; break;

    case 'wide': {
      const runOns = Number(opts.runOns ?? 0); // 0..5
      entry.extra='wd'; entry.legal=false;
      entry.wideRuns = 1 + runOns;
      entry.runs = entry.wideRuns;
      inn.extras.wd += entry.wideRuns;
      // strike rotates if they actually ran an odd number (ignoring the 1 penalty)
      if(runOns % 2 === 1) inn.striker = 1 - inn.striker;
      break;
    }

    case 'noball': {
      const batRuns = Number(opts.batRuns ?? 0); // 0/1/2/3/4/6
      entry.extra='nb'; entry.legal=false; entry.batRuns = batRuns;
      entry.runs = 1 + batRuns;
      inn.extras.nb += 1;          // only penalty 1 to extras
      if(batRuns % 2 === 1) inn.striker = 1 - inn.striker; // rotate on odd bat runs
      inn.freeHit = true;          // next legal ball
      break;
    }

    case 'bye':
      entry.extra='b'; entry.legal=true; entry.runs=1; inn.extras.b += 1; break;

    case 'legbye':
      entry.extra='lb'; entry.legal=true; entry.runs=1; inn.extras.lb += 1; break;
  }

  // Apply free hit: on next LEGAL ball, wicket (other than run-out) doesn't count
  if(inn.freeHit && entry.legal){
    if(entry.wicket){
      entry.wicket=false;
      entry.freeHitWicketIgnored = true;
    }
    inn.freeHit = false;
  }

  // push + total
  inn.timeline.push(entry);
  inn.runs += entry.runs;
  if(entry.wicket) inn.wickets++;

  // counts and over strip
  if(entry.legal){
    inn.balls++; inn.legalBalls++;
    inn.overBalls.push(symbol(entry));
    if(entry.runs %2 === 1 && !entry.extra) inn.striker = 1-inn.striker;
    if(inn.legalBalls % bpo === 0){ endOver(State); }
  } else {
    inn.overBalls.push(symbol(entry));
  }

  // stats
  applyStats(inn, entry);
}

export function endOver(State){
  const inn=curInn();
  if(inn.overBalls.length){
    inn.overs++; inn.overBalls=[]; inn.legalBalls=0; inn.striker = 1-inn.striker;
  }
}

export function newOver(State){ endOver(State); }

export function changeInnings(State){
  const old = curInn();
  if(State.innNow===0){
    const target = old.runs + 1; State.innings[1].target = target; State.innNow=1;
    const firstBat = old.batting; State.innings[1].batting=1-firstBat; State.innings[1].bowling=firstBat;
  } else {
    State.innNow=0;
  }
}

export function undo(State){
  const inn=curInn();
  const last = inn.timeline.pop(); if(!last) return;

  // reverse totals
  inn.runs -= last.runs; if(last.wicket) inn.wickets=Math.max(0,inn.wickets-1);

  // reverse extras
  if(last.extra==='wd') inn.extras.wd = Math.max(0, inn.extras.wd - (last.wideRuns||1));
  if(last.extra==='nb') inn.extras.nb = Math.max(0, inn.extras.nb - 1);
  if(last.extra==='b')  inn.extras.b  = Math.max(0, inn.extras.b - 1);
  if(last.extra==='lb') inn.extras.lb = Math.max(0, inn.extras.lb - 1);

  // reverse counts and strip
  if(last.legal){
    if(inn.overBalls.length) inn.overBalls.pop();
    recalcCounts(State, inn);
  } else {
    if(inn.overBalls.length) inn.overBalls.pop();
  }

  // reverse stats (recompute for simplicity)
  recomputeAllStats(inn);
}

export function rebuildOverStrip(State){
  const inn=curInn(); inn.overBalls=[]; inn.legalBalls=0;
  const bpo=State.meta.ballsPerOver;
  for(let i=Math.max(0, inn.timeline.length-bpo); i<inn.timeline.length; i++){
    const e=inn.timeline[i]; if(!e) break; inn.overBalls.push(symbol(e)); if(e.legal) inn.legalBalls++; }
}

export function recalcCounts(State, inn){
  inn.balls=0; inn.overs=0; inn.legalBalls=0; inn.overBalls=[];
  let lb=0; const bpo=State.meta.ballsPerOver;
  let freeHitPending=false;
  for(const e of inn.timeline){
    if(e.extra==='nb') freeHitPending = true;
    if(e.legal && freeHitPending){ freeHitPending=false; }
    if(e.legal){
      lb++;
      if(lb===bpo){ inn.overs++; lb=0; inn.overBalls=[]; }
      else { inn.overBalls.push(symbol(e)); }
    } else {
      inn.overBalls.push(symbol(e));
    }
    inn.balls += e.legal?1:0;
  }
  inn.legalBalls = lb;
  inn.freeHit = freeHitPending;
}

function recomputeAllStats(inn){
  inn.batStats = {};
  inn.bowlStats = {};
  for(const e of inn.timeline){
    applyStats(inn, e);
  }
}