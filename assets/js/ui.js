/**
 * assets/js/ui.js
 * ---------------------------------------------------------------------------
 * UI helpers: binding the scoring pad, modal flows, rendering, read-only state,
 * AND the Share Recap feature (call API to generate animated MP4).
 * ---------------------------------------------------------------------------
 */

import { qs, qsa, haptic } from './util.js';
import { State, curInn } from './state.js';

/* -------------------------- Read-only toggle ----------------------------- */
let READ_ONLY = false;
export function setReadOnly(v) {
  READ_ONLY = !!v;
  document.body.classList.toggle('read-only', READ_ONLY);
}

/* -------------------------- Scoring pad binding -------------------------- */
export function bindPad(handle) {
  qsa('#padgrid .btn').forEach((b) => {
    const ev = b.dataset.ev;
    const pressFX = () => {
      b.classList.remove('pressed'); void b.offsetHeight; b.classList.add('pressed');
    };

    if (ev === 'noball') {
      b.addEventListener('click', () => { haptic('light'); openNoBallModal(handle); pressFX(); });
    } else if (ev === 'wide') {
      b.addEventListener('click', () => { haptic('light'); openWideModal(handle); pressFX(); });
    } else {
      b.addEventListener('click', () => {
        handle(ev);
        if (ev === '4' || ev === '6') haptic('medium');
        else if (ev === 'wicket') haptic('heavy');
        else haptic('tap');
        pressFX();
      });
    }
  });

  // Wire Share Recap button if present
  const shareBtn = qs('#btnShareRecap');
  if (shareBtn) {
    shareBtn.addEventListener('click', async () => {
      haptic('light');
      await shareRecap();
    });
  }
}

/* -------------------------- Inputs / Rendering --------------------------- */
/* These are stubs; keep your existing implementations if they’re richer. */
export function readInputs() {
  const inn = curInn();
  const b1 = qs('#batter1'); if (b1) inn.batters[0] = b1.value || '';
  const b2 = qs('#batter2'); if (b2) inn.batters[1] = b2.value || '';
  const bw = qs('#bowler');  if (bw) inn.bowler      = bw.value || '';
}
export function hydrateInputs() {
  const inn = curInn();
  if (qs('#batter1')) qs('#batter1').value = inn.batters?.[0] || '';
  if (qs('#batter2')) qs('#batter2').value = inn.batters?.[1] || '';
  if (qs('#bowler'))  qs('#bowler').value  = inn.bowler || '';
}
export function renderAll() {
  // Minimal example—your project likely has richer rendering already
  const inn = curInn();
  const total = (inn.runs ?? 0) + '/' + (inn.wickets ?? 0);
  if (qs('#scoreNow')) qs('#scoreNow').textContent = total;
}

/* -------------------------- Modals (NB/WD) ------------------------------- */
export function openNoBallModal(handle) {
  const modal = qs('#nbModal'); if (!modal) return;
  modal.classList.remove('hidden');
  qsa('.nbpick', modal).forEach(btn => {
    btn.onclick = () => { modal.classList.add('hidden'); handle('noball', { batRuns: +btn.dataset.val }); };
  });
  qs('#nbCancel').onclick = () => modal.classList.add('hidden');
}
export function openWideModal(handle) {
  const modal = qs('#wdModal'); if (!modal) return;
  modal.classList.remove('hidden');
  qsa('.wdpick', modal).forEach(btn => {
    btn.onclick = () => { modal.classList.add('hidden'); handle('wide', { runs: +btn.dataset.val }); };
  });
  qs('#wdCancel').onclick = () => modal.classList.add('hidden');
}

/* -------------------------- Share Recap (MP4) ---------------------------- */
/**
 * Generates an animated recap video server-side then shares/downloads it.
 * - Requires the match to be saved at least once (to have an ID).
 * - If FFmpeg is missing on the server, falls back to a static PNG.
 */
export async function shareRecap() {
  // must be saved to have an ID
  const id = (window.State && window.State.saveId) || (State && State.saveId);
  if (!id) {
    alert('Save the match first to generate a recap.');
    return;
  }

  const res = await fetch(`api/renderCard.php?id=${encodeURIComponent(id)}`);
  const j = await res.json();
  if (!j.ok) {
    alert('Could not generate recap: ' + (j.error || 'Unknown error'));
    return;
  }

  // Prefer MP4
  if (j.mp4) {
    const url = j.mp4;
    const blob = await (await fetch(url)).blob();

    // Use Web Share if available; otherwise download
    if (navigator.canShare && navigator.canShare({ files: [new File([blob], 'StumpVisionRecap.mp4', { type: 'video/mp4' })] })) {
      try {
        await navigator.share({
          title: 'StumpVision Match Recap',
          files: [new File([blob], 'StumpVisionRecap.mp4', { type: 'video/mp4' })],
          text: 'Match recap by StumpVision',
        });
        return;
      } catch (e) {
        // fall through to download
      }
    }
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'StumpVisionRecap.mp4';
    a.click();
    return;
  }

  // Fallback PNG
  if (j.fallback_png) {
    const url = j.fallback_png;
    const blob = await (await fetch(url)).blob();
    if (navigator.canShare && navigator.canShare({ files: [new File([blob], 'StumpVisionRecap.png', { type: 'image/png' })] })) {
      try {
        await navigator.share({
          title: 'StumpVision Match Recap',
          files: [new File([blob], 'StumpVisionRecap.png', { type: 'image/png' })],
          text: 'Match recap by StumpVision',
        });
        return;
      } catch (e) { /* ignore */ }
    }
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'StumpVisionRecap.png';
    a.click();
    return;
  }

  alert('Recap generated, but no downloadable asset was returned.');
}
