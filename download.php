<?php
$data = json_decode(file_get_contents('php://input'), true);
if(!$data || !isset($data['files'])){ header('HTTP/1.1 400 Bad Request'); exit; }
$files = $data['files'];
$zipname = tempnam(sys_get_temp_dir(), 'ttszip') . '.zip';
$zip = new ZipArchive();
if($zip->open($zipname, ZipArchive::CREATE)!==TRUE){ header('HTTP/1.1 500 Internal Server Error'); exit; }
foreach($files as $f){
    $path = __DIR__ . '/' . $f;
    if(file_exists($path) && is_file($path)){
        $zip->addFile($path, basename($path));
    }
}
$zip->close();
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="tts_audio.zip"');
readfile($zipname);
@unlink($zipname);
