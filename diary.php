<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if(empty($_SESSION['member_id'])){header('Location: login.php');exit;}
$currentId=(int)$_SESSION['member_id'];if(empty($_SESSION['member_csrf']))$_SESSION['member_csrf']=bin2hex(random_bytes(32));
$moods=['Vui vẻ','Bình yên','Biết ơn','Hy vọng','Mệt mỏi','Buồn','Lo lắng','Cô đơn','Khác'];
$error='';$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($_SESSION['member_csrf'],(string)($_POST['csrf']??''))){$error='Phiên đã hết hạn. Hãy tải lại trang.';}
 else{
  $action=(string)($_POST['action']??'');
  try{
   if($action==='save'){
    $id=filter_var($_POST['entry_id']??0,FILTER_VALIDATE_INT);$date=(string)($_POST['entry_date']??date('Y-m-d'));$mood=(string)($_POST['mood']??'Bình yên');$title=trim((string)($_POST['title']??''));$content=trim((string)($_POST['content']??''));
    $dt=DateTime::createFromFormat('Y-m-d',$date);
    if(!$dt||$dt->format('Y-m-d')!==$date)$error='Ngày ghi nhật ký không hợp lệ.';
    elseif(!in_array($mood,$moods,true))$error='Tâm trạng không hợp lệ.';
    elseif($title===''||mb_strlen($title)>150)$error='Tiêu đề cần từ 1 đến 150 ký tự.';
    elseif($content===''||mb_strlen($content)>10000)$error='Nội dung cần từ 1 đến 10.000 ký tự.';
    elseif($id&&$id>0){$q=$pdo->prepare('UPDATE member_diary SET entry_date=?,mood=?,title=?,content=? WHERE id=? AND member_id=?');$q->execute([$date,$mood,$title,$content,$id,$currentId]);if(!$q->rowCount()&&$q->errorCode()!=='00000')$error='Không thể cập nhật trang nhật ký.';else{header('Location: diary.php?saved=1');exit;}}
    else{$q=$pdo->prepare('INSERT INTO member_diary(member_id,entry_date,mood,title,content) VALUES(?,?,?,?,?)');$q->execute([$currentId,$date,$mood,$title,$content]);header('Location: diary.php?saved=1');exit;}
   }elseif($action==='delete'){
    $id=filter_var($_POST['entry_id']??null,FILTER_VALIDATE_INT);if($id){$q=$pdo->prepare('DELETE FROM member_diary WHERE id=? AND member_id=?');$q->execute([$id,$currentId]);}header('Location: diary.php?deleted=1');exit;
   }
  }catch(PDOException $ex){$error='Chưa cài đặt dữ liệu nhật ký. Hãy chạy database/social_diary.sql trong phpMyAdmin.';}
 }
}
if(isset($_GET['saved']))$notice='Đã lưu trang nhật ký của bạn.';
if(isset($_GET['deleted']))$notice='Đã xóa trang nhật ký.';
$editId=filter_var($_GET['edit']??0,FILTER_VALIDATE_INT);$editing=null;
if($editId){$q=$pdo->prepare('SELECT * FROM member_diary WHERE id=? AND member_id=?');$q->execute([$editId,$currentId]);$editing=$q->fetch();if(!$editing)$editId=0;}
$searchMood=(string)($_GET['mood']??'');$where='member_id=?';$params=[$currentId];if(in_array($searchMood,$moods,true)){$where.=' AND mood=?';$params[]=$searchMood;}
$q=$pdo->prepare("SELECT * FROM member_diary WHERE $where ORDER BY entry_date DESC,id DESC LIMIT 100");$q->execute($params);$entries=$q->fetchAll();
function diary_h(?string $s):string{return htmlspecialchars($s??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Nhật ký vũ trụ | Góc Hoàng Đạo</title><link rel="stylesheet" href="css/style.css?v=socialdiary1"></head>
<body class="blog-page diary-page"><header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Truyện Đêm</strong><small>Mỗi đêm, một thế giới khác</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="friends.php">Kết bạn</a><a href="community.php">Cộng đồng</a><a href="messages.php">Tin nhắn</a><a href="account.php">Tài khoản</a></nav></div></header>
<main class="diary-main"><section class="social-hero"><span class="compat-kicker">✦ KHOẢNG TRỜI RIÊNG CỦA BẠN ✦</span><h1>Nhật ký <em>vũ trụ</em></h1><p>Ghi lại một ngày đã qua, một điều đáng nhớ hoặc cảm xúc bạn muốn giữ lại. Chỉ bạn mới xem được những trang này.</p></section>
<?php if($notice):?><p class="social-notice"><?=diary_h($notice)?></p><?php endif;?><?php if($error):?><p class="community-error"><?=diary_h($error)?></p><?php endif;?>
<section class="diary-editor"><h2><?=$editing?'Chỉnh sửa trang nhật ký':'Viết trang nhật ký mới'?></h2><form method="post" class="diary-form"><input type="hidden" name="csrf" value="<?=diary_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="save"><input type="hidden" name="entry_id" value="<?=(int)($editing['id']??0)?>"><div class="diary-fields"><label>Ngày <input type="date" name="entry_date" value="<?=diary_h($editing['entry_date']??date('Y-m-d'))?>" required></label><label>Tâm trạng<select name="mood"><?php foreach($moods as $m):?><option value="<?=diary_h($m)?>" <?=((($editing['mood']??'Bình yên')===$m)?'selected':'')?>><?=diary_h($m)?></option><?php endforeach;?></select></label></div><label>Tiêu đề<input name="title" maxlength="150" value="<?=diary_h($editing['title']??'')?>" placeholder="Hôm nay mình muốn nhớ..." required></label><label>Điều bạn muốn ghi lại<textarea name="content" rows="7" maxlength="10000" placeholder="Viết ra những điều đang ở trong lòng..." required><?=diary_h($editing['content']??'')?></textarea></label><div class="diary-form-actions"><small>Riêng tư · Tối đa 10.000 ký tự</small><button class="social-button" type="submit">Lưu nhật ký ✦</button><?php if($editing):?><a class="social-quiet-link" href="diary.php">Hủy sửa</a><?php endif;?></div></form></section>
<section class="diary-entries"><div class="social-section-title"><h2>Những trang đã viết</h2><span><?=count($entries)?> trang gần đây</span></div><form method="get" class="social-search"><select name="mood"><option value="">Mọi tâm trạng</option><?php foreach($moods as $m):?><option value="<?=diary_h($m)?>" <?=($searchMood===$m?'selected':'')?>><?=diary_h($m)?></option><?php endforeach;?></select><button class="social-button" type="submit">Lọc nhật ký</button></form>
<?php if(!$entries):?><div class="community-empty"><span>☾</span><h3>Trang đầu tiên còn đang chờ</h3><p>Hãy bắt đầu bằng một điều nhỏ bạn muốn lưu giữ hôm nay.</p></div><?php else:foreach($entries as $entry):?><article class="diary-entry"><div class="diary-entry-top"><span><?=diary_h(date('d/m/Y',strtotime($entry['entry_date'])))?> · <?=diary_h($entry['mood'])?></span><div><a href="diary.php?edit=<?=(int)$entry['id']?>">Chỉnh sửa</a><form method="post" onsubmit="return confirm('Bạn muốn xóa trang nhật ký này?')"><input type="hidden" name="csrf" value="<?=diary_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="entry_id" value="<?=(int)$entry['id']?>"><button type="submit">Xóa</button></form></div></div><h3><?=diary_h($entry['title'])?></h3><p><?=nl2br(diary_h($entry['content']))?></p></article><?php endforeach;endif;?></section>
</main><footer class="site-footer"><div class="footer-bottom">© 2026 Truyện Đêm · Những điều dịu dàng được lưu lại</div></footer><script src="js/presence.js"></script>
</body></html>
