export const qs = (sel, el=document)=>el.querySelector(sel);
export const qsa = (sel, el=document)=>Array.from(el.querySelectorAll(sel));
export const on = (el, ev, fn)=>el.addEventListener(ev, fn);
export const fmtOver = (o,b)=>`${Math.floor(o)}.${b}`;
export const clamp = (n,min,max)=>Math.max(min,Math.min(max,n));
export const now = ()=>Date.now();
export const copy = (x)=>JSON.parse(JSON.stringify(x));
export const saveLocal = (k,v)=>localStorage.setItem(k, JSON.stringify(v));
export const loadLocal = (k)=>{ try{ return JSON.parse(localStorage.getItem(k)); } catch{ return null; } };