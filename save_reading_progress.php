<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 http_response_code(405); echo json_encode(['ok'=>false,'message'=>'Method not allowed']); exit;
}
$memberId=(int)($_SESSION['member_id']??0);
if(!$memberId){http_response_code(401);echo json_encode(['ok'=>false]);exit;}
$csrf=(string)($_POST['csrf']??'');
if(empty($_SESSION['member_csrf'])||!hash_equals((string)$_SESSION['member_csrf'],$csrf)){
 http_response_code(403);echo json_encode(['ok'=>false,'message'=>'Invalid session']);exit;
}
$chapterId=(int)($_POST['chapter_id']??0);
$progress=(float)($_POST['progress_percent']??-1);
if($chapterId<1||!is_finite($progress)||$progress<0||$progress>100){
 http_response_code(422);echo json_encode(['ok'=>false,'message'=>'Invalid progress']);exit;
}
try{
 $q=$pdo->prepare('UPDATE story_reading_history SET progress_percent=?,updated_at=CURRENT_TIMESTAMP WHERE member_id=? AND chapter_id=?');
 $q->execute([round($progress,2),$memberId,$chapterId]);
 echo json_encode(['ok'=>$q->rowCount()>=0]);
}catch(PDOException $e){
 http_response_code(500);echo json_encode(['ok'=>false]);
}
