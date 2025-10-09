import {copy, loadLocal, saveLocal} from './util.js';

export const DEFAULT_META = { title:'', oversPerSide:10, ballsPerOver:6, toss:'A', opted:'bat' };
export const newInnings = (bat)=>({
  batting:bat, bowling:1-bat,
  wickets:0, runs:0, balls:0, legalBalls:0, overs:0,
  overBalls:[], timeline:[],
  target:null, batters:['',''], striker:0, bowler:'',
  // NEW: extras + free hit state
  extras:{ nb:0, wd:0, b:0, lb:0 },
  freeHit:false
});

export const State = {
  meta: copy(DEFAULT_META),
  teams:[ {name:'Team A'}, {name:'Team B'} ],
  innings:[ newInnings(0), newInnings(1) ],
  innNow:0,
  saveId:null,
};

export const hydrateFromLocal = ()=>{
  const raw = loadLocal('cricket_scorer_auto');
  if(raw) Object.assign(State, raw);
};

export const autosave = ()=> saveLocal('cricket_scorer_auto', State);
export const cloneState = ()=> copy(State);
export const curInn = ()=> State.innings[State.innNow];