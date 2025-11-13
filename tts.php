<?php
// tts.php - handles chunking and Google Translate TTS fallback
header('Content-Type: application/json; charset=utf-8');
$payload = json_decode(file_get_contents('php://input'), true);
if(!$payload){ echo json_encode(['error'=>'Invalid request']); exit; }
$text = trim($payload['text'] ?? '');
$lang = $payload['lang'] ?? 'or-IN';
if(mb_strlen($text,'UTF-8')==0){ echo json_encode(['error'=>'Empty text']); exit; }
if(mb_strlen($text,'UTF-8')>10000){ echo json_encode(['error'=>'Text exceeds 10000 characters']); exit; }
$max_chunk = 3000;
$chunks = [];
$remaining = $text;
while(mb_strlen($remaining,'UTF-8')>0){
    if(mb_strlen($remaining,'UTF-8') <= $max_chunk){ $chunks[] = $remaining; break; }
    $piece = mb_substr($remaining,0,$max_chunk,'UTF-8');
    $lastP = max(mb_strrpos($piece, '.', 0) ?: -1, mb_strrpos($piece, '।', 0) ?: -1, mb_strrpos($piece, '?', 0) ?: -1);
    if($lastP !== -1){
        $chunks[] = mb_substr($remaining,0,$lastP+1,'UTF-8');
        $remaining = ltrim(mb_substr($remaining,$lastP+1,'UTF-8'));
    } else {
        $chunks[] = $piece;
        $remaining = ltrim(mb_substr($remaining,$max_chunk,'UTF-8'));
    }
}
$dir = __DIR__ . '/chunks';
if(!is_dir($dir)) mkdir($dir,0755,true);
$files = [];
$counter=0;
foreach($chunks as $c){
    $counter++;
    $safe = 'chunk_'.time().'_'.$counter.'.mp3';
    $outPath = $dir . '/' . $safe;
    $q = rawurlencode($c);
    $url = "https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&q={$q}&tl={$lang}&ttsspeed=1";
    $opts = ["http"=>["method"=>"GET","header"=>"User-Agent: Mozilla/5.0\r\n"]];
    $context = stream_context_create($opts);
    $mp3 = @file_get_contents($url,false,$context);
    if($mp3 === false){
        file_put_contents($outPath, '');
        $files[] = 'chunks/'.$safe;
    } else {
        file_put_contents($outPath, $mp3);
        $files[] = 'chunks/'.$safe;
    }
    usleep(200000);
}
echo json_encode(['files'=>$files], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
