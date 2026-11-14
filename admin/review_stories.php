<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
if (empty($_SESSION['blog_admin_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf'];$error='';$notice='';
function review_h(?string $v):string{return htmlspecialchars($v??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??''))){$error='Phiên quản trị hết hạn. Hãy tải lại trang.';}
 else{
  $type=(string)($_POST['target_type']??'');$id=(int)($_POST['target_id']??0);$decision=(string)($_POST['decision']??'');
  if(!in_array($type,['story','chapter'],true)||$id<1||!in_array($decision,['approved','rejected'],true))$error='Yêu cầu duyệt không hợp lệ.';
  else{
   try{
    $pdo->beginTransaction();
    if($type==='story'){
     $q=$pdo->prepare('SELECT id,title FROM stories WHERE id=? AND member_id IS NOT NULL FOR UPDATE');$q->execute([$id]);$target=$q->fetch();
     if(!$target)throw new RuntimeException('Không tìm thấy truyện thành viên.');
     if($decision==='approved')$pdo->prepare("UPDATE stories SET status='published',updated_at=NOW() WHERE id=?")->execute([$id]);
     else $pdo->prepare("UPDATE stories SET status='draft',updated_at=NOW() WHERE id=?")->execute([$id]);
     $storyId=$id;
    }else{
     $q=$pdo->prepare('SELECT c.id,c.story_id,s.title FROM chapters c JOIN stories s ON s.id=c.story_id WHERE c.id=? AND s.member_id IS NOT NULL FOR UPDATE');$q->execute([$id]);$target=$q->fetch();
     if(!$target)throw new RuntimeException('Không tìm thấy chương của thành viên.');
     $storyId=(int)$target['story_id'];
     $pdo->prepare("UPDATE chapters SET status=? WHERE id=?")->execute([$decision==='approved'?'published':'draft',$id]);
    }
    $pdo->prepare("INSERT INTO story_moderation_reviews(target_type,target_id,story_id,decision,note,admin_id) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE decision=VALUES(decision),note=VALUES(note),admin_id=VALUES(admin_id),reviewed_at=CURRENT_TIMESTAMP")->execute([$type,$id,$storyId,$decision,trim((string)($_POST['note']??'')),(int)$_SESSION['blog_admin_id']]);
    $pdo->commit();header('Location: review_stories.php?result='.$decision);exit;
   }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e instanceof RuntimeException?$e->getMessage():'Không thể cập nhật trạng thái. Hãy kiểm tra bảng story_moderation_reviews đã được tạo.';}
  }
 }
}
if(($_GET['result']??'')==='approved')$notice='Đã duyệt và xuất bản nội dung.';
if(($_GET['result']??'')==='rejected')$notice='Đã từ chối nội dung. Nội dung vẫn được lưu ở trạng thái bản nháp.';
$stories=$pdo->query("SELECT s.id,s.title,s.author,s.description,s.story_kind,s.short_content,s.created_at,m.display_name FROM stories s LEFT JOIN members m ON m.id=s.member_id LEFT JOIN story_moderation_reviews r ON r.target_type='story' AND r.target_id=s.id WHERE s.member_id IS NOT NULL AND s.status='draft' AND (r.id IS NULL OR r.decision<>'rejected') ORDER BY s.created_at ASC")->fetchAll();
$chapters=$pdo->query("SELECT c.id,c.story_id,c.chapter_number,c.title,c.content,c.created_at,s.title AS story_title,m.display_name FROM chapters c JOIN stories s ON s.id=c.story_id LEFT JOIN members m ON m.id=s.member_id LEFT JOIN story_moderation_reviews r ON r.target_type='chapter' AND r.target_id=c.id WHERE s.member_id IS NOT NULL AND c.status='draft' AND (r.id IS NULL OR r.decision<>'rejected') ORDER BY c.created_at ASC")->fetchAll();
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Duyệt truyện thành viên | Truyện Đêm</title><link rel="stylesheet" href="../css/style.css"><style>
.rv-wrap{max-width:1050px;margin:24px auto;padding:0 13px;color:#eee}.rv-panel{background:#15131d;border:1px solid #332b43;border-radius:16px;padding:18px;margin:15px 0}.rv-panel h1,.rv-panel h2{margin-top:0}.rv-muted{color:#aaa}.rv-item{border:1px solid #393044;border-radius:12px;padding:15px;margin:12px 0}.rv-item h3{margin:0 0 7px}.rv-content{white-space:pre-wrap;line-height:1.75;max-height:260px;overflow:auto;background:#0d0b12;border-radius:9px;padding:12px;margin:10px 0}.rv-actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:12px}.rv-actions button{border:0;border-radius:9px;padding:10px 15px;font-weight:700;color:#fff;background:#7045b5}.rv-actions .reject{background:#733342}.rv-note{width:100%;box-sizing:border-box;padding:10px;border:1px solid #494052;border-radius:9px;background:#0d0b12;color:#fff}.rv-notice{padding:11px;border-radius:9px;background:#203126;color:#c7f4d2}.rv-error{padding:11px;border-radius:9px;background:#3b2029;color:#ffc6d1}
</style><link rel="stylesheet" href="../css/admin-white.css?v=20261114-white1">
</head><body class="story-site admin-theme"><main class="rv-wrap"><section class="rv-panel"><h1>✦ Duyệt truyện thành viên</h1><p class="rv-muted">Xem nội dung trước khi xuất bản. Truyện và từng chương được duyệt riêng.</p><p><a href="index.php">← Trang quản trị</a> · <a href="stories.php?list=all">Quản lý tất cả truyện</a></p><?php if($notice):?><p class="rv-notice"><?=review_h($notice)?></p><?php endif;?><?php if($error):?><p class="rv-error"><?=review_h($error)?></p><?php endif;?></section>
<section class="rv-panel"><h2>📚 Truyện chờ duyệt (<?=count($stories)?>)</h2><?php if(!$stories):?><p class="rv-muted">Hiện không có truyện nào chờ duyệt.</p><?php endif;?><?php foreach($stories as $s):?><article class="rv-item"><h3><?=review_h($s['title'])?></h3><p class="rv-muted">Tác giả: <?=review_h($s['display_name']?:$s['author'])?> · <?=($s['story_kind']==='short'?'Truyện ngắn':'Truyện nhiều chương')?> · <?=review_h(date('d/m/Y H:i',strtotime($s['created_at'])))?></p><?php if($s['description']):?><p><?=review_h($s['description'])?></p><?php endif;?><div class="rv-content"><?=review_h($s['story_kind']==='short'?$s['short_content']:'Truyện nhiều chương. Duyệt truyện để mở trang truyện; từng chương được duyệt riêng.')?></div><form method="post"><input type="hidden" name="csrf" value="<?=review_h($csrf)?>"><input type="hidden" name="target_type" value="story"><input type="hidden" name="target_id" value="<?=(int)$s['id']?>"><input class="rv-note" name="note" maxlength="500" placeholder="Ghi chú cho tác giả (không bắt buộc)"><div class="rv-actions"><button name="decision" value="approved">✓ Duyệt truyện</button><button class="reject" name="decision" value="rejected" onclick="return confirm('Từ chối truyện này? Nội dung sẽ được giữ ở bản nháp.')">Từ chối</button></div></form></article><?php endforeach;?></section>
<section class="rv-panel"><h2>📖 Chương chờ duyệt (<?=count($chapters)?>)</h2><?php if(!$chapters):?><p class="rv-muted">Hiện không có chương nào chờ duyệt.</p><?php endif;?><?php foreach($chapters as $c):?><article class="rv-item"><h3><?=review_h($c['story_title'])?> — Chương <?=(int)$c['chapter_number']?>: <?=review_h($c['title'])?></h3><p class="rv-muted">Tác giả: <?=review_h($c['display_name']?:'Thành viên')?> · <?=review_h(date('d/m/Y H:i',strtotime($c['created_at'])))?></p><div class="rv-content"><?=review_h($c['content'])?></div><form method="post"><input type="hidden" name="csrf" value="<?=review_h($csrf)?>"><input type="hidden" name="target_type" value="chapter"><input type="hidden" name="target_id" value="<?=(int)$c['id']?>"><input class="rv-note" name="note" maxlength="500" placeholder="Ghi chú cho tác giả (không bắt buộc)"><div class="rv-actions"><button name="decision" value="approved">✓ Duyệt chương</button><button class="reject" name="decision" value="rejected" onclick="return confirm('Từ chối chương này? Nội dung sẽ được giữ ở bản nháp.')">Từ chối</button></div></form></article><?php endforeach;?></section></main></body></html>