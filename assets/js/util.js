// assets/js/util.js
export const qs  = (sel, el=document)=>el.querySelector(sel);
export const qsa = (sel, el=document)=>Array.from(el.querySelectorAll(sel));
export const on  = (el, ev, fn)=>el.addEventListener(ev, fn);
export const fmtOver = (o,b)=>`${Math.floor(o)}.${b}`;
export const clamp = (n,min,max)=>Math.max(min,Math.min(max,n));
export const now = ()=>Date.now();
export const copy = (x)=>JSON.parse(JSON.stringify(x));
export const saveLocal = (k,v)=>localStorage.setItem(k, JSON.stringify(v));
export const loadLocal = (k)=>{ try{ return JSON.parse(localStorage.getItem(k)); } catch{ return null; } };

/* ---------- Haptics (graceful no-op where unsupported) ---------- */
const reduceMotion = typeof window !== 'undefined'
  && window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function vibrate(pattern){
  if (reduceMotion) return;
  if (navigator && typeof navigator.vibrate === 'function') {
    try { navigator.vibrate(pattern); } catch {}
  }
}

export function haptic(type='tap'){
  // simple patterns; kept short to avoid annoying users
  const map = {
    tap: 15,
    soft: 10,
    light: 20,
    medium: [20, 30, 20],
    heavy: [30, 40, 30],
    success: [20, 40, 20],
    warning: [30, 60],
    error: [40, 60, 40]
  };
  vibrate(map[type] ?? 10);
}
