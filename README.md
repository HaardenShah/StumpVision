# Cricket Pickup Scorer (PHP + JS, Flattened)

No `public/` folder — everything lives at the project root.

## Structure
- `index.php` — app shell
- `api/matches.php` — JSON CRUD (save/load/list/delete) — uses `data/` at project root
- `assets/css/style.css` — styles
- `assets/js/*.js` — modules
- `data/` — writable JSON store (keep it server-readable)
- `.htaccess` — optional (Apache)

## Install
1. Upload the folder to your webhost so `index.php` is in the webroot.
2. Ensure `data/` exists and is writable by the web server user (`chmod 775 data`).
3. Visit your domain — you're ready to score.

## Notes
- Wide/No-ball add 1 run and do not increment ball count.
- Strike rotates on odd runs; overs auto-roll and swap strike.
- Undo is timeline-based; export downloads current state as JSON.
