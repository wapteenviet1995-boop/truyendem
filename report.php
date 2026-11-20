<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/notifications.php';
$storyId=(int)($_GET['story_id']??$_POST['story_id']??0);
$chapterId=(int)($_GET['chapter_id']??$_POST['chapter_id']??0);
$q=$pdo->prepare("SELECT id,title,slug,story_type,story_kind FROM stories WHERE id=? AND status='published' LIMIT 1");
$q->execute([$storyId]);$story=$q->fetch();
if(!$story){http_response_code(404);exit('Không tìm thấy truyện cần báo cáo.');}
$chapter=null;
if($chapterId>0){$q=$pdo->prepare("SELECT id,title,chapter_number FROM chapters WHERE id=? AND story_id=? LIMIT 1");$q->execute([$chapterId,$storyId]);$chapter=$q->fetch();if(!$chapter){$chapterId=0;}}
if(empty($_SESSION['report_csrf']))$_SESSION['report_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['report_csrf'];$error='';
$reasons=['spam'=>'Spam hoặc nội dung quảng cáo','copyright'=>'Vi phạm bản quyền','harassment'=>'Quấy rối, xúc phạm hoặc công kích','illegal'=>'Nội dung vi phạm pháp luật','other'=>'Lý do khác'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??''))){$error='Phiên gửi báo cáo đã hết hạn. Hãy tải lại trang.';}
 elseif(trim((string)($_POST['website']??''))!==''){$error='Không thể gửi báo cáo này.';}
 else{
  $reason=(string)($_POST['reason']??'other');$details=trim((string)($_POST['details']??''));$name=trim((string)($_POST['reporter_name']??''));$email=trim((string)($_POST['reporter_email']??''));
  if(!isset($reasons[$reason]))$error='Vui lòng chọn lý do báo cáo hợp lệ.';
  elseif(mb_strlen($details,'UTF-8')<10||mb_strlen($details,'UTF-8')>2000)$error='Mô tả cần từ 10 đến 2.000 ký tự.';
  elseif($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))$error='Địa chỉ email không hợp lệ.';
  else{
   try{
    $ip=(string)($_SERVER['REMOTE_ADDR']??'');$ipHash=$ip!==''?hash('sha256',$ip):null;
    if($ipHash){$limit=$pdo->prepare("SELECT COUNT(*) FROM content_reports WHERE ip_hash=? AND created_at>DATE_SUB(NOW(),INTERVAL 1 HOUR)");$limit->execute([$ipHash]);if((int)$limit->fetchColumn()>=5)throw new RuntimeException('Bạn đã gửi quá nhiều báo cáo trong thời gian ngắn. Vui lòng thử lại sau.');}
    $memberId=(int)($_SESSION['member_id']??0);$memberId=$memberId>0?$memberId:null;
    $ins=$pdo->prepare("INSERT INTO content_reports(story_id,chapter_id,reporter_member_id,reporter_name,reporter_email,reason,details,ip_hash,user_agent) VALUES(?,?,?,?,?,?,?,?,?)");
    $ins->execute([$storyId,$chapterId?:null,$memberId,$name!==''?$name:null,$email!==''?$email:null,$reason,$details,$ipHash,substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,255)]);
    td_notify_admins($pdo,'content_report','Có báo cáo vi phạm mới','Truyện: '.$story['title'].' · Lý do: '.$reasons[$reason], 'reports.php?status=pending');
    header('Location: report.php?story_id='.$storyId.'&sent=1');exit;
   }catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'Chưa gửi được báo cáo. Có thể hệ thống chưa được cài đặt cơ sở dữ liệu mới.';}
  }
 }
}
function report_h($v):string{return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Báo cáo vi phạm | Truyện Đêm</title><link rel="stylesheet" href="/css/style.css"><link rel="stylesheet" href="/css/site-matte-black.css"></head><body class="story-site"><main style="max-width:720px;margin:35px auto;padding:16px"><section style="padding:22px;border:1px solid #3b304a;border-radius:18px;background:#15131d;color:#eee"><a href="<?=report_h(($story['slug']??'')?'/truyen/'.rawurlencode($story['slug']).'.html':'/index.php')?>">← Quay lại truyện</a><h1>Báo cáo vi phạm</h1><p style="color:#aaa">Báo cáo sẽ được gửi đến đội ngũ kiểm duyệt Truyện Đêm.</p><p><strong><?=report_h($story['title'])?></strong><?php if($chapter):?> · Chương <?= (int)$chapter['chapter_number']?>: <?=report_h($chapter['title'])?><?php endif;?></p>
<?php if(isset($_GET['sent'])):?><p style="padding:13px;border-radius:10px;background:#203b2b;color:#baf0c9">✓ Báo cáo đã được gửi. Cảm ơn bạn đã giúp cộng đồng an toàn hơn.</p><a href="/index.php">Về thư viện truyện</a><?php else:?>
<?php if($error!==''):?><p role="alert" style="padding:12px;background:#472630;border-radius:9px;color:#ffd1d8"><?=report_h($error)?></p><?php endif;?>
<form method="post"><input type="hidden" name="csrf" value="<?=report_h($csrf)?>"><input type="hidden" name="story_id" value="<?=$storyId?>"><input type="hidden" name="chapter_id" value="<?=$chapterId?>"><div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
<label style="display:block;margin:14px 0">Lý do báo cáo<select name="reason" required style="display:block;width:100%;padding:12px;margin-top:7px"><?php foreach($reasons as $key=>$label):?><option value="<?=report_h($key)?>"><?=report_h($label)?></option><?php endforeach;?></select></label>
<label style="display:block;margin:14px 0">Mô tả chi tiết *<textarea name="details" rows="6" minlength="10" maxlength="2000" required placeholder="Hãy mô tả cụ thể nội dung vi phạm và vị trí xuất hiện..." style="display:block;width:100%;box-sizing:border-box;padding:12px;margin-top:7px"></textarea></label>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px"><label>Tên của bạn (không bắt buộc)<input name="reporter_name" maxlength="100" style="display:block;width:100%;box-sizing:border-box;padding:11px;margin-top:6px"></label><label>Email liên hệ (không bắt buộc)<input type="email" name="reporter_email" maxlength="190" style="display:block;width:100%;box-sizing:border-box;padding:11px;margin-top:6px"></label></div><button type="submit" style="margin-top:17px;padding:12px 18px;border:0;border-radius:9px;background:#a98ae0;color:#17111f;font-weight:800">Gửi báo cáo</button></form><?php endif;?></section></main></body></html>