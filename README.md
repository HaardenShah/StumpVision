---

## 🏏 StumpVision — Pickup Cricket Scorer

**StumpVision** is a lightweight, mobile-friendly web app built in **PHP + JavaScript** to keep score during casual or competitive cricket matches.
It works completely offline, can be installed as a PWA, and even supports **live sharing** so your friends can follow the match from their phones.

---

### ⚡ Features

✅ **Simple scoring pad**
Record runs, boundaries, wickets, wides, byes, leg-byes, and no-balls in one tap.

✅ **Smart no-ball logic**
Prompts for bat runs and correctly adds total runs (1 extra + bat runs).
Handles **free hit** delivery logic automatically.

✅ **Extras tracking**
Tracks NB, WD, B, and LB totals per innings.

✅ **Batting & bowling stats**
Auto-generates strike rates, economy rates, and overs for all players.

✅ **Ball-by-ball timeline**
Every delivery logged with timestamp, bowler, striker, and outcome.

✅ **Undo and over management**
Supports undoing last ball, strike swapping, and over/innings transitions.

✅ **PWA support (Offline-first)**
Installable on phones or desktops.
Caches assets locally and works offline once loaded.

✅ **Live viewer link**
Generate a shareable link like:
`https://yourdomain.com/index.php?id=abc123&view=1`
Spectators can see the scoreboard live in read-only mode.

✅ **Data persistence**
Local autosave + flat-file JSON backups on the server (no DB required).

---

### 🧱 Project Structure

```
stumpvision/
├─ index.php                 # Main app shell (HTML + bootstrap)
├─ manifest.webmanifest      # PWA metadata
├─ service-worker.js         # Offline caching + updates
├─ api/
│  └─ matches.php            # Flat-file JSON CRUD (save/load/list)
├─ assets/
│  ├─ css/
│  │  └─ style.css           # Theme + layout
│  ├─ js/
│  │  ├─ app.js              # Entry point, events, autosave
│  │  ├─ state.js            # App state, schema, autosave
│  │  ├─ scoring.js          # Match logic, extras, wickets, free hits
│  │  ├─ ui.js               # Rendering + DOM updates
│  │  └─ util.js             # Helper functions
│  └─ icons/
│     ├─ icon-192.png
│     └─ icon-512.png
└─ data/
   └─ (JSON match saves)
```

---

### 🚀 Setup Instructions

1. **Upload to your web host**
   Copy the entire folder (`stumpvision/`) to your server’s web root (or a subdirectory).

2. **Ensure PHP can write to `/data/`**

   ```bash
   chmod 755 data
   ```

   or

   ```bash
   chmod 777 data
   ```

   depending on your host permissions.

3. **Access the app**
   Open `https://yourdomain.com/stumpvision/` in a browser.

4. **(Optional) Install to Home Screen**

   * On Android Chrome → “Add to Home screen”
   * On iOS Safari → “Share → Add to Home Screen”
   * On desktop Chrome → “Install StumpVision” icon in the URL bar.

---

### 💾 Saving & Sharing Matches

* **Save Match:**
  Click **Save** — this creates a JSON entry under `/data/` and assigns it an ID.

* **Copy Share Link:**
  After saving, click **Copy Share Link** → it copies a URL like:

  ```
  https://yourdomain.com/index.php?id=xyz123&view=1
  ```

  Send that to anyone; they’ll see a **read-only live view** of the scoreboard (auto-refresh every 3s).

* **Export JSON:**
  You can also export the full match file manually for backups.

---

### 🧮 Scoring Rules Implemented

| Event     | Logic                                                    |
| --------- | -------------------------------------------------------- |
| Dot / 1–6 | Adds runs to batter & team; rotates strike on odd runs   |
| Wicket    | Increments wickets (ignored on Free Hit unless run out)  |
| Wide      | Adds 1 + runs taken to extras; ball not counted          |
| No Ball   | Adds 1 to extras + bat runs; triggers Free Hit next ball |
| Bye / LB  | Adds runs to extras; ball counts                         |
| Undo      | Reverts last event completely, recalculating stats       |

---

### 📱 Mobile UI Design

* Responsive card layout for phones
* Minimal colors, optimized for sunlight readability
* Touch-sized scoring pad
* Quick badges for Free Hit and current striker
* Dedicated batting & bowling stats tables below the scoreboard

---

### 🔧 Technical Stack

| Layer           | Tech                                                |
| --------------- | --------------------------------------------------- |
| Frontend        | Vanilla JS (ES Modules), HTML5, CSS3                |
| Backend         | PHP 8.x (flat-file JSON storage)                    |
| Data            | `/data/*.json` (match state snapshots)              |
| Offline Support | Service Worker + Cache API                          |
| Live Updates    | Simple AJAX polling via `api/matches.php`           |
| UX              | Responsive layout, modal pickers for wides/no-balls |

---

### 🧹 Maintenance Notes

* Clear `/data/` occasionally to remove old match files.
* Increment `VERSION` in `service-worker.js` if you update assets — this triggers cache refresh for users.
* You can rename or move the app; just update the paths in:

  * `manifest.webmanifest`
  * service worker `CORE` array

---

### 🧑‍💻 Development Notes

**To reset matches (during testing):**

```bash
rm data/*.json
```

**To debug offline caching:**

1. Open DevTools → Application → Service Workers.
2. Unregister → Hard Reload.
3. Revisit site → it will reinstall the worker automatically.

---

### 🏁 Credits

Built by **Haarden Shah** — designed for pickup cricket players who want fast, clean, no-nonsense scoring.
No frameworks, no dependencies — just efficient code and good UX.

---

