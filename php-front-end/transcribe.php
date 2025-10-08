<?php
// (c) J~Net 2025 - Whisper.cpp API Bridge
// Sends mic audio → Whisper.cpp server → returns JSON text

$server="http://127.0.0.1:8345/inference";

if(!isset($_FILES['file'])){
  echo json_encode(['error'=>'No audio uploaded']);
  exit;
}

$tmpFile=$_FILES['file']['tmp_name'];
$cfile=new CURLFile($tmpFile,'audio/webm','speech.webm');

$post=[
  'file'=>$cfile,
  'temperature'=>'0.0',
  'temperature_inc'=>'0.2',
  'response_format'=>'json'
];

$ch=curl_init();
curl_setopt_array($ch,[
  CURLOPT_URL=>$server,
  CURLOPT_POST=>true,
  CURLOPT_POSTFIELDS=>$post,
  CURLOPT_RETURNTRANSFER=>true
]);

$response=curl_exec($ch);
if(curl_errno($ch)){
  echo json_encode(['error'=>curl_error($ch)]);
  curl_close($ch);
  exit;
}
curl_close($ch);

header('Content-Type: application/json');
echo $response;
?>
