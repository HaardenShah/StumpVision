<?php
/* =============================================================================
 * StumpVision — api/renderCard.php
 *
 * Creates a share-ready animated MP4 "Match Recap" card at 1080×1080.
 * - Reads a saved match by ?id=... from /data/{id}.json
 * - Renders 4 styled PNG slides via ImageMagick
 * - Uses FFmpeg to stitch slides into a short MP4 with crossfades
 * - Falls back to a static PNG if FFmpeg is not available
 *
 * Output JSON: { ok: true, mp4: "/data/cards/<id>.mp4", fallback_png: "/data/cards/<id>-cover.png" }
 * Or:         { ok: false, error: "message" }
 *
 * Requirements on host:
 *   - PHP Imagick extension (or `convert`/`magick` CLI via Imagick backend)
 *   - ffmpeg installed (optional but recommended)
 * ========================================================================== */

header('Content-Type: application/json');

$root = dirname(__DIR__);              // project root
$dataDir = $root . '/data';
$cardsDir = $dataDir . '/cards';
@is_dir($cardsDir) || @mkdir($cardsDir, 0775, true);

$id = isset($_GET['id']) ? basename($_GET['id']) : '';
if (!$id) {
  echo json_encode(['ok' => false, 'error' => 'Missing ?id']); exit;
}

$jsonPath = "$dataDir/$id.json";
if (!is_file($jsonPath)) {
  echo json_encode(['ok' => false, 'error' => "Match not found: $id"]); exit;
}

$match = json_decode(file_get_contents($jsonPath), true);
if (!$match) {
  echo json_encode(['ok' => false, 'error' => 'Bad match JSON']); exit;
}

/* ------------------ Extract the bits we need ------------------ */
$meta   = $match['meta'] ?? [];
$teams  = $match['teams'] ?? [['name'=>'Team A'],['name'=>'Team B']];
$inns   = $match['innings'] ?? [];
$Aname  = $teams[0]['name'] ?? 'Team A';
$Bname  = $teams[1]['name'] ?? 'Team B';

// Compute totals from latest innings state (simplified summary)
function scoreTuple($teamIndex, $inns) {
  foreach ($inns as $inn) {
    if (($inn['batting'] ?? 0) === $teamIndex) {
      $r = $inn['runs'] ?? 0;
      $w = $inn['wickets'] ?? 0;
      $bpo = max(1, intval($inn['ballsPerOver'] ?? ($inn['meta']['ballsPerOver'] ?? 6)));
      $balls = intval($inn['balls'] ?? 0);
      $overs = floor($balls / $bpo) . "." . ($balls % $bpo);
      return [$r, $w, $overs];
    }
  }
  return [0,0,'0.0'];
}
list($Ar,$Aw,$Ao) = scoreTuple(0, $inns);
list($Br,$Bw,$Bo) = scoreTuple(1, $inns);

// Winner
$winner = ($Ar === $Br) ? 'Tie'
         : (($Ar > $Br) ? "$Aname win by ".($Ar-$Br)." runs" : "$Bname win by ".($Br-$Ar)." runs");

// Extras (if tracked)
$extrasA = $inns[0]['extras'] ?? ['nb'=>0,'wd'=>0,'b'=>0,'lb'=>0];
$extrasB = isset($inns[1]) ? ($inns[1]['extras'] ?? ['nb'=>0,'wd'=>0,'b'=>0,'lb'=>0]) : ['nb'=>0,'wd'=>0,'b'=>0,'lb'=>0];

// Quick top batter / bowler (best-effort)
function topBatter($inn){
  $top = ['name'=>'—','runs'=>0,'balls'=>0,'fours'=>0,'sixes'=>0];
  foreach (($inn['batStats'] ?? []) as $row){
    if (($row['runs'] ?? 0) > $top['runs']) $top = $row;
  }
  return $top;
}
function topBowler($inn){
  $top = ['name'=>'—','wickets'=>0,'runs'=>0,'overs'=>'0.0'];
  foreach (($inn['bowlStats'] ?? []) as $row){
    if (($row['wickets'] ?? 0) > $top['wickets']) $top = $row;
  }
  return $top;
}
$topBat = topBatter($inns[0] ?? []);
$topBowl = topBowler($inns[0] ?? []); // heuristic: first innings bowlers

/* ------------------ Paths ------------------ */
$baseName   = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $id);
$coverPng   = "$cardsDir/{$baseName}-cover.png"; // also used as poster
$slides     = [
  "$cardsDir/{$baseName}-s0.png",
  "$cardsDir/{$baseName}-s1.png",
  "$cardsDir/{$baseName}-s2.png",
  "$cardsDir/{$baseName}-s3.png",
];
$videoPath  = "$cardsDir/{$baseName}.mp4";

/* ------------------ Helpers: Imagick Drawing ------------------ */
function mkCanvas($w=1080,$h=1080){
  $im = new Imagick();
  $im->newImage($w, $h, new ImagickPixel('#0b1120'));
  $im->setImageFormat('png');

  // soft vignette gradient overlay (cyan to pink glow)
  $overlay = new Imagick();
  $overlay->newPseudoImage($w, $h, "gradient:#0b1120-#0b1120");
  $overlay->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
  $overlay->evaluateImage(Imagick::EVALUATE_MULTIPLY, 0.0, Imagick::CHANNEL_ALPHA);
  $im->compositeImage($overlay, Imagick::COMPOSITE_OVER, 0, 0);

  return $im;
}

function drawText($im, $text, $x, $y, $size=48, $color='#e5e7eb', $weight='bold', $align='left') {
  $draw = new ImagickDraw();
  $draw->setFillColor(new ImagickPixel($color));
  $draw->setFontWeight($weight === 'bold' ? 700 : 400);
  // Use a safe sans; if you deploy a .ttf, setFont() to that path.
  $draw->setFontSize($size);
  if ($align === 'center') $draw->setTextAlignment(Imagick::ALIGN_CENTER);
  if ($align === 'right')  $draw->setTextAlignment(Imagick::ALIGN_RIGHT);
  $im->annotateImage($draw, $x, $y, 0, $text);
}

function roundedRect($im, $x, $y, $w, $h, $r=20, $stroke='#22314b', $fill='rgba(255,255,255,0.03)') {
  $d = new ImagickDraw();
  $d->setFillColor(new ImagickPixel($fill));
  $d->setStrokeColor(new ImagickPixel($stroke));
  $d->setStrokeWidth(2);
  $d->roundRectangle($x, $y, $x+$w, $y+$h, $r, $r);
  $im->drawImage($d);
}

function savePng($im, $path){
  $im->writeImage($path);
  $im->clear(); $im->destroy();
}

/* ------------------ Render Slides ------------------ */
try {
  // Slide 0: Title
  $c0 = mkCanvas();
  drawText($c0, "🏏 StumpVision", 540, 220, 64, '#67e8f9', 'bold', 'center');
  drawText($c0, "Match Recap",    540, 300, 48, '#e5e7eb', 'bold', 'center');
  roundedRect($c0, 150, 360, 780, 360, 26);
  drawText($c0, ($meta['title'] ?? 'Untitled Match'), 540, 520, 44, '#e5e7eb', 'bold', 'center');
  drawText($c0, date('M j, Y'), 540, 580, 32, '#9ca3af', 'normal', 'center');
  savePng($c0, $slides[0]);

  // Slide 1: Scores (Aligned)
  $c1 = mkCanvas();
  drawText($c1, "$Aname", 300, 360, 44, '#e5e7eb', 'bold', 'center');
  drawText($c1, "$Bname", 780, 360, 44, '#e5e7eb', 'bold', 'center');
  roundedRect($c1, 120, 380, 360, 260, 24);
  roundedRect($c1, 600, 380, 360, 260, 24);
  drawText($c1, "{$Ar}/{$Aw}", 300, 500, 72, '#67e8f9', 'bold', 'center');
  drawText($c1, "{$Br}/{$Bw}", 780, 500, 72, '#67e8f9', 'bold', 'center');
  drawText($c1, "{$Ao} ov", 300, 560, 34, '#9ca3af', 'normal', 'center');
  drawText($c1, "{$Bo} ov", 780, 560, 34, '#9ca3af', 'normal', 'center');
  drawText($c1, "🏆 $winner", 540, 720, 44, '#67e8f9', 'bold', 'center');
  savePng($c1, $slides[1]);

  // Slide 2: Highlights
  $c2 = mkCanvas();
  roundedRect($c2, 100, 320, 880, 480, 24);
  $batLine = ($topBat['name'] ?? '—') . ' — ' . ($topBat['runs'] ?? 0) . ' (' . ($topBat['balls'] ?? 0) . ')';
  $bowlLine = ($topBowl['name'] ?? '—') . ' — ' . ($topBowl['wickets'] ?? 0) . '/' . ($topBowl['runs'] ?? 0);
  drawText($c2, "Top Batter", 540, 420, 32, '#9ca3af', 'normal', 'center');
  drawText($c2, $batLine,     540, 470, 42, '#e5e7eb', 'bold', 'center');
  drawText($c2, "Top Bowler", 540, 540, 32, '#9ca3af', 'normal', 'center');
  drawText($c2, $bowlLine,    540, 590, 42, '#e5e7eb', 'bold', 'center');

  $exA = "A Extras: nb ".($extrasA['nb']??0).", wd ".($extrasA['wd']??0).", b ".($extrasA['b']??0).", lb ".($extrasA['lb']??0);
  $exB = "B Extras: nb ".($extrasB['nb']??0).", wd ".($extrasB['wd']??0).", b ".($extrasB['b']??0).", lb ".($extrasB['lb']??0);
  drawText($c2, $exA, 540, 660, 28, '#9ca3af', 'normal', 'center');
  drawText($c2, $exB, 540, 700, 28, '#9ca3af', 'normal', 'center');
  savePng($c2, $slides[2]);

  // Slide 3: Outro
  $c3 = mkCanvas();
  drawText($c3, "Generated by StumpVision", 540, 520, 40, '#e5e7eb', 'bold', 'center');
  drawText($c3, "stump.vision", 540, 580, 34, '#9ca3af', 'normal', 'center');
  savePng($c3, $slides[3]);

  // Cover for previews
  @copy($slides[1], $coverPng);

} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>"Imagick: ".$e->getMessage()]); exit;
}

/* ------------------ Build MP4 via FFmpeg (if available) ------------------ */
function which($bin) {
  $paths = explode(PATH_SEPARATOR, getenv('PATH') ?: '');
  foreach ($paths as $p) {
    $full = rtrim($p, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $bin;
    if (is_file($full) && is_executable($full)) return $full;
  }
  return null;
}

$ffmpeg = which('ffmpeg');

if ($ffmpeg) {
  // Use xfade crossfades between 4 slide streams
  // Slide durations (seconds): [1.2, 2.6, 2.0, 1.0] with 0.35s crossfades
  $cmd = escapeshellcmd($ffmpeg) . ' -y '
    . '-loop 1 -t 1.2 -i ' . escapeshellarg($slides[0]) . ' '
    . '-loop 1 -t 2.6 -i ' . escapeshellarg($slides[1]) . ' '
    . '-loop 1 -t 2.0 -i ' . escapeshellarg($slides[2]) . ' '
    . '-loop 1 -t 1.0 -i ' . escapeshellarg($slides[3]) . ' '
    . '-filter_complex "'
      // prepare all as same pixel format
      . '[0:v]format=yuv420p,setsar=1[v0];'
      . '[1:v]format=yuv420p,setsar=1[v1];'
      . '[2:v]format=yuv420p,setsar=1[v2];'
      . '[3:v]format=yuv420p,setsar=1[v3];'
      // xfade chain (fade 0.35s at offsets)
      . '[v0][v1]xfade=transition=fade:duration=0.35:offset=0.85[v01];'
      . '[v01][v2]xfade=transition=fade:duration=0.35:offset=2.40[v02];'
      . '[v02][v3]xfade=transition=fade:duration=0.35:offset=4.05[vout]" '
    . '-map "[vout]" -r 30 -c:v libx264 -pix_fmt yuv420p -movflags +faststart '
    . escapeshellarg($videoPath) . ' 2>&1';

  exec($cmd, $out, $ret);
  $ok = ($ret === 0 && is_file($videoPath));
  if ($ok) {
    echo json_encode([
      'ok' => true,
      'mp4' => "/data/cards/{$baseName}.mp4",
      'fallback_png' => "/data/cards/{$baseName}-cover.png"
    ]);
    exit;
  }
  // If FFmpeg failed, fall through to PNG fallback
}

/* ------------------ Fallback: static PNG (cover) ------------------ */
echo json_encode([
  'ok' => true,
  'mp4' => null,
  'fallback_png' => "/data/cards/{$baseName}-cover.png",
  'note' => 'FFmpeg not found. Returned static PNG.'
]);
