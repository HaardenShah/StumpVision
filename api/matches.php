<?php
// api/matches.php — flat‑file JSON CRUD (flattened layout)
// Actions: list, load, save, delete
// Body: JSON { id?, payload? } — payload is full match state

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$dataDir = __DIR__ . '/../data'; // flattened: data/ is one level up from api/

if (!is_dir($dataDir)) { @mkdir($dataDir, 0775, true); }

function safe_id($id){ return substr(preg_replace('~[^a-zA-Z0-9_-]~','',$id),0,64); }
function path_for($id){ global $dataDir; return $dataDir . '/' . $id . '.json'; }

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
  if ($action === 'list') {
    $items = [];
    foreach (glob($dataDir.'/*.json') as $f) {
      $id = basename($f, '.json');
      $j  = @json_decode(@file_get_contents($f), true) ?: [];
      $title = $j['meta']['title'] ?? (($j['teams'][0]['name'] ?? 'Team A').' vs '.($j['teams'][1]['name'] ?? 'Team B'));
      $items[] = [ 'id'=>$id, 'ts'=>filemtime($f), 'title'=>$title ];
    }
    usort($items, fn($a,$b)=>$b['ts']<=>$a['ts']);
    echo json_encode(['ok'=>true,'items'=>$items]);
    exit;
  }

  if ($action === 'load') {
    $id = isset($_GET['id']) ? safe_id($_GET['id']) : '';
    $f  = path_for($id);
    if (!$id || !is_file($f)) { echo json_encode(['ok'=>false,'err'=>'not_found']); exit; }
    $payload = json_decode(file_get_contents($f), true);
    echo json_encode(['ok'=>true,'payload'=>$payload]);
    exit;
  }

  if ($action === 'save' && $method === 'POST') {
    $raw = file_get_contents('php://input');
    $in  = json_decode($raw,true);
    if (!$in || !isset($in['payload'])) { echo json_encode(['ok'=>false,'err'=>'bad_payload']); exit; }
    $id = !empty($in['id']) ? safe_id($in['id']) : bin2hex(random_bytes(8));
    $f  = path_for($id);
    $payload = $in['payload'];
    $payload['__saved_at'] = time();
    file_put_contents($f, json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok'=>true,'id'=>$id]);
    exit;
  }

  if ($action === 'delete' && $method === 'POST') {
    $raw = file_get_contents('php://input');
    $in  = json_decode($raw,true);
    $id  = isset($in['id']) ? safe_id($in['id']) : '';
    $f   = path_for($id);
    if ($id && is_file($f)) { unlink($f); echo json_encode(['ok'=>true]); }
    else { echo json_encode(['ok'=>false,'err'=>'not_found']); }
    exit;
  }

  echo json_encode(['ok'=>false,'err'=>'bad_action']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'err'=>'server_error']);
}
