import {curInn} from './state.js';

export const symbol = (e)=>{
  if(e.extra==='wd') return 'wd';
  if(e.extra==='nb') return e.batRuns ? `nb+${e.batRuns}` : 'nb';
  if(e.extra==='b')  return 'b';
  if(e.extra==='lb') return 'lb';
  if(e.wicket) return 'W';
  return String(e.runs);
};

/**
 * handleEvent(ev, State, opts)
 * - ev: 'dot','1','2','3','4','6','wicket','wide','noball','bye','legbye','undo'
 * - opts: { batRuns?: number } for 'noball'
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

    case 'wide':
      entry.runs=1; entry.extra='wd'; entry.legal=false;
      inn.extras.wd += 1;
      break;

    case 'noball': {
      const batRuns = Number(opts.batRuns ?? 0); // 0/1/2/3/4/6
      entry.extra='nb'; entry.legal=false; entry.batRuns = batRuns;
      entry.runs = 1 + batRuns;
      inn.extras.nb += 1;               // only the penalty 1 goes to extras
      // Strike rotates on odd bat runs (even though ball not legal)
      if(batRuns % 2 === 1) inn.striker = 1 - inn.striker;
      // Free hit applies to the next LEGAL ball
      inn.freeHit = true;
      break;
    }

    case 'bye':
      entry.extra='b'; entry.legal=true; entry.runs=1; inn.extras.b += 1; break;

    case 'legbye':
      entry.extra='lb'; entry.legal=true; entry.runs=1; inn.extras.lb += 1; break;
  }

  // Apply free hit rule: if this is a legal ball after a no-ball,
  // a wicket (except run-out etc., which we're not modeling) doesn't count.
  if(inn.freeHit && entry.legal){
    if(entry.wicket){
      entry.wicket=false;
      entry.freeHitWicketIgnored = true; // marker for the log if desired
    }
    // Free hit consumed after the first legal ball
    inn.freeHit = false;
  }

  // Push entry + update counts
  inn.timeline.push(entry);
  inn.runs += entry.runs;
  if(entry.wicket) inn.wickets++;

  if(entry.legal){
    inn.balls++; inn.legalBalls++;
    inn.overBalls.push(symbol(entry));
    // strike rotation for odd runs on legal balls
    if(entry.runs %2 === 1 && !entry.extra) inn.striker = 1-inn.striker;
    if(inn.legalBalls % bpo === 0){ endOver(State); }
  } else {
    // illegal ball still shows in strip
    inn.overBalls.push(symbol(entry));
  }
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
  inn.runs -= last.runs; if(last.wicket) inn.wickets=Math.max(0,inn.wickets-1);

  // reverse extras/freeHit if needed
  if(last.extra==='wd') inn.extras.wd = Math.max(0, inn.extras.wd-1);
  if(last.extra==='nb'){
    inn.extras.nb = Math.max(0, inn.extras.nb-1);
    // If last entry was a no-ball, free hit would have been set. Restore to true
    // only if there wasn't any legal ball after it (simple approach: set true;
    // recalc below will clear on next legal).
    inn.freeHit = false; // conservative reset; free-hit is consumed on legal balls anyway
  }
  if(last.extra==='b')  inn.extras.b  = Math.max(0, inn.extras.b-1);
  if(last.extra==='lb') inn.extras.lb = Math.max(0, inn.extras.lb-1);

  if(last.legal){
    // remove from over strip, recalc counts for robustness
    if(inn.overBalls.length) inn.overBalls.pop();
    recalcCounts(State, inn);
  } else {
    if(inn.overBalls.length) inn.overBalls.pop();
  }
}

export function rebuildOverStrip(State){
  const inn=curInn(); inn.overBalls=[]; inn.legalBalls=0;
  const bpo=State.meta.ballsPerOver; // reconstruct last partial over
  for(let i=Math.max(0, inn.timeline.length-bpo); i<inn.timeline.length; i++){
    const e=inn.timeline[i]; if(!e) break; inn.overBalls.push(symbol(e)); if(e.legal) inn.legalBalls++; }
}

export function recalcCounts(State, inn){
  inn.balls=0; inn.overs=0; inn.legalBalls=0; inn.overBalls=[];
  let lb=0; const bpo=State.meta.ballsPerOver;
  let freeHitPending=false;
  for(const e of inn.timeline){
    // reconstruct freeHit state: if we saw an nb, set pending; if legal ball after, consume
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
  inn.freeHit = freeHitPending; // if an nb was last and no legal ball since
}