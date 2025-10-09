import {copy, loadLocal, saveLocal} from './util.js';

export const DEFAULT_META = { title:'', oversPerSide:10, ballsPerOver:6, toss:'A', opted:'bat' };

// batting: R,B,4,6 ; bowling: balls,runs,wkts (overs = floor/6 . rem)
// extras: nb, wd, b, lb
export const newInnings = (bat)=>({
  batting:bat, bowling:1-bat,
  wickets:0, runs:0, balls:0, legalBalls:0, overs:0,
  overBalls:[], timeline:[],
  target:null, batters:['',''], striker:0, bowler:'',
  extras:{ nb:0, wd:0, b:0, lb:0 },
  freeHit:false,
  batStats:{}, // name -> {R:0,B:0,4:0,6:0}
  bowlStats:{} // name -> {balls:0,R:0,W:0}
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

// helpers to ensure stat rows exist
export const ensureBatter = (inn, name)=>{
  if(!name) return;
  if(!inn.batStats[name]) inn.batStats[name] = {R:0,B:0,4:0,6:0};
};
export const ensureBowler = (inn, name)=>{
  if(!name) return;
  if(!inn.bowlStats[name]) inn.bowlStats[name] = {balls:0,R:0,W:0};
};