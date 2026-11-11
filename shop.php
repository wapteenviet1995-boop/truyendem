<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if (empty($_SESSION['member_id'])) { header('Location: login.php?next=shop.php'); exit; }
$me=(int)$_SESSION['member_id'];
if(empty($_SESSION['member_csrf'])) $_SESSION['member_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['member_csrf']; $error=''; $notice='';
function shop_h(?string $s):string{return htmlspecialchars($s??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
try {
 $pdo->prepare('INSERT IGNORE INTO member_wallets(member_id,balance) VALUES(?,0)')->execute([$me]);
} catch(PDOException $e){http_response_code(500);exit('Chưa cài đặt hệ thống Xu. Hãy chạy database/xu_cua_hang.sql trong phpMyAdmin.');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??''))) $error='Phiên đã hết hạn. Hãy tải lại trang.';
 else {
  $action=(string)($_POST['action']??'');
  try {
   if($action==='daily'){
    $pdo->beginTransaction();
    $claim=$pdo->prepare('INSERT IGNORE INTO wallet_daily_claims(member_id,claim_date,amount) VALUES(?,CURDATE(),20)');$claim->execute([$me]);
    if($claim->rowCount()!==1){$pdo->rollBack();$error='Bạn đã nhận Xu điểm danh hôm nay rồi. Mai quay lại nhé!';}
    else{
     $pdo->prepare('UPDATE member_wallets SET balance=balance+20 WHERE member_id=?')->execute([$me]);
     $pdo->prepare("INSERT INTO wallet_transactions(member_id,amount,type,description) VALUES(?,20,'daily','Điểm danh hằng ngày')")->execute([$me]);
     $pdo->commit();header('Location: shop.php?claimed=1');exit;
    }
   } elseif($action==='buy'){
    $item=(int)($_POST['item_id']??0);$pdo->beginTransaction();
    $q=$pdo->prepare('SELECT id,name,price,item_type FROM shop_items WHERE id=? AND is_active=1 FOR UPDATE');$q->execute([$item]);$product=$q->fetch();
    if(!$product){$pdo->rollBack();$error='Vật phẩm không còn bán.';}
    else{
     $q=$pdo->prepare('SELECT id FROM member_inventory WHERE member_id=? AND item_id=?');$q->execute([$me,$item]);
     if($q->fetch()){$pdo->rollBack();$error='Bạn đã sở hữu vật phẩm này rồi.';}
     else{
      $q=$pdo->prepare('SELECT balance FROM member_wallets WHERE member_id=? FOR UPDATE');$q->execute([$me]);$balance=(int)$q->fetchColumn();
      if($balance<(int)$product['price']){$pdo->rollBack();$error='Bạn chưa đủ Xu để mua vật phẩm này.';}
      else{
       $pdo->prepare('UPDATE member_wallets SET balance=balance-? WHERE member_id=?')->execute([(int)$product['price'],$me]);
       $pdo->prepare('INSERT INTO member_inventory(member_id,item_id) VALUES(?,?)')->execute([$me,$item]);
       $inventoryId=(int)$pdo->lastInsertId();
       // Tu dong trang bi vat pham vua mua, moi loai chi dung mot vat pham.
       $pdo->prepare('UPDATE member_inventory SET is_equipped=0 WHERE member_id=? AND item_id IN (SELECT id FROM shop_items WHERE item_type=?)')->execute([$me,$product['item_type']]);
       $pdo->prepare('UPDATE member_inventory SET is_equipped=1 WHERE id=? AND member_id=?')->execute([$inventoryId,$me]);
       $pdo->prepare("INSERT INTO wallet_transactions(member_id,amount,type,description,reference_id) VALUES(?,?,'purchase',?,?)")->execute([$me,-(int)$product['price'],'Mua '.$product['name'],$item]);
       $pdo->commit();header('Location: shop.php?bought=1');exit;
      }
     }
    }
   } elseif($action==='equip'){
    $inventory=(int)($_POST['inventory_id']??0);
    $q=$pdo->prepare('SELECT i.id,s.item_type FROM member_inventory i JOIN shop_items s ON s.id=i.item_id WHERE i.id=? AND i.member_id=?');$q->execute([$inventory,$me]);$owned=$q->fetch();
    if(!$owned)$error='Vật phẩm không thuộc túi đồ của bạn.';
    else{$pdo->beginTransaction();$pdo->prepare('UPDATE member_inventory SET is_equipped=0 WHERE member_id=? AND item_id IN (SELECT id FROM shop_items WHERE item_type=?)')->execute([$me,$owned['item_type']]);$pdo->prepare('UPDATE member_inventory SET is_equipped=1 WHERE id=? AND member_id=?')->execute([$inventory,$me]);$pdo->commit();header('Location: shop.php?equipped=1');exit;}
   } elseif($action==='gift'){
    $to=(int)($_POST['recipient_id']??0);$amount=filter_var($_POST['amount']??null,FILTER_VALIDATE_INT);$amount=$amount===false?0:(int)$amount;
    if($to<1||$to===$me||$amount<1||$amount>5000)$error='Thông tin tặng Xu không hợp lệ (tối đa 5.000 Xu mỗi lần).';
    else{
     $q=$pdo->prepare("SELECT id FROM friend_requests WHERE status='accepted' AND ((requester_id=? AND addressee_id=?) OR (requester_id=? AND addressee_id=?)) LIMIT 1");$q->execute([$me,$to,$to,$me]);
     if(!$q->fetch())$error='Bạn chỉ có thể tặng Xu cho bạn bè.';
     else{
      $pdo->prepare('INSERT IGNORE INTO member_wallets(member_id,balance) VALUES(?,0)')->execute([$to]);
      $pdo->beginTransaction();
      $ids=[$me,$to];sort($ids);$lock=$pdo->prepare('SELECT member_id,balance FROM member_wallets WHERE member_id IN (?,?) ORDER BY member_id FOR UPDATE');$lock->execute($ids);$balances=$lock->fetchAll();$mine=0;foreach($balances as $row){if((int)$row['member_id']===$me)$mine=(int)$row['balance'];}
      if($mine<$amount){$pdo->rollBack();$error='Số dư Xu của bạn không đủ.';}
      else{
       $pdo->prepare('UPDATE member_wallets SET balance=balance-? WHERE member_id=?')->execute([$amount,$me]);$pdo->prepare('UPDATE member_wallets SET balance=balance+? WHERE member_id=?')->execute([$amount,$to]);
       $pdo->prepare("INSERT INTO wallet_transactions(member_id,amount,type,description) VALUES(?,?,'gift_sent','Tặng Xu cho thành viên #".$to."')")->execute([$me,-$amount]);
       $pdo->prepare("INSERT INTO wallet_transactions(member_id,amount,type,description) VALUES(?,?,'gift_received','Nhận Xu từ thành viên #".$me."')")->execute([$to,$amount]);
       $pdo->commit();header('Location: shop.php?gifted=1');exit;
      }
     }
    }
   }
  } catch(PDOException $e){if($pdo->inTransaction())$pdo->rollBack();$error='Giao dịch chưa thành công. Hãy thử lại sau.';}
 }
}
if(isset($_GET['claimed']))$notice='Bạn nhận được 20 Xu điểm danh!';if(isset($_GET['bought']))$notice='Mua vật phẩm thành công!';if(isset($_GET['equipped']))$notice='Đã trang bị vật phẩm.';if(isset($_GET['gifted']))$notice='Đã gửi Xu cho bạn bè.';
$q=$pdo->prepare('SELECT balance FROM member_wallets WHERE member_id=?');$q->execute([$me]);$balance=(int)$q->fetchColumn();
$q=$pdo->prepare('SELECT member_id FROM wallet_daily_claims WHERE member_id=? AND claim_date=CURDATE()');$q->execute([$me]);$claimed=(bool)$q->fetchColumn();
$items=$pdo->query('SELECT s.*,i.id AS inventory_id FROM shop_items s LEFT JOIN member_inventory i ON i.item_id=s.id AND i.member_id='.(int)$me.' WHERE s.is_active=1 ORDER BY s.price')->fetchAll();
$q=$pdo->prepare('SELECT i.id AS inventory_id,i.is_equipped,s.name,s.description,s.item_type,s.icon FROM member_inventory i JOIN shop_items s ON s.id=i.item_id WHERE i.member_id=? ORDER BY i.purchased_at DESC');$q->execute([$me]);$inventory=$q->fetchAll();
$q=$pdo->prepare("SELECT id,amount,type,description,created_at FROM wallet_transactions WHERE member_id=? ORDER BY id DESC LIMIT 12");$q->execute([$me]);$history=$q->fetchAll();
$q=$pdo->prepare("SELECT m.id,m.display_name FROM friend_requests f JOIN members m ON m.id=IF(f.requester_id=?,f.addressee_id,f.requester_id) WHERE f.status='accepted' AND (f.requester_id=? OR f.addressee_id=?) AND m.is_active=1 ORDER BY m.display_name");$q->execute([$me,$me,$me]);$friends=$q->fetchAll();
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Xu & Cửa hàng | Góc Hoàng Đạo</title><link rel="stylesheet" href="css/style.css?v=20261111-3"><link rel="stylesheet" href="css/shop.css?v=1"></head>
<body class="blog-page social-page shop-page"><header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Góc Hoàng Đạo</strong><small>Khám phá câu chuyện của các vì sao</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="community.php">Cộng đồng</a><a href="friends.php">Kết bạn</a><a href="account.php">Tài khoản</a><a href="shop.php">Xu & Cửa hàng</a></nav></div></header>
<main class="shop-main"><section class="shop-hero"><span class="compat-kicker">✦ NGÂN HÀ VŨ TRỤ ✦</span><h1>Ví Xu <em>& Cửa hàng</em></h1><p>Tích lũy Xu, sưu tầm vật phẩm và tô điểm hồ sơ của bạn.</p><div class="shop-balance"><span>✦ Số dư của bạn</span><strong><?=number_format($balance)?> Xu</strong></div></section>
<?php if($notice):?><p class="shop-flash"><?=shop_h($notice)?></p><?php endif;?><?php if($error):?><p class="community-error"><?=shop_h($error)?></p><?php endif;?>
<section class="shop-daily"><div><strong>☀ Điểm danh mỗi ngày</strong><p>Nhận miễn phí 20 Xu mỗi ngày khi ghé thăm cửa hàng.</p></div><form method="post"><input type="hidden" name="csrf" value="<?=shop_h($csrf)?>"><input type="hidden" name="action" value="daily"><button class="shop-button" <?=$claimed?'disabled':''?>><?=$claimed?'Đã nhận hôm nay':'Nhận 20 Xu'?></button></form></section>
<section class="shop-section"><h2>✧ Cửa hàng vật phẩm</h2><div class="shop-grid"><?php foreach($items as $item):?><article class="shop-item"><div class="shop-icon"><?=shop_h($item['icon'])?></div><h3><?=shop_h($item['name'])?></h3><p><?=shop_h($item['description'])?></p><strong><?=number_format((int)$item['price'])?> Xu</strong><?php if($item['inventory_id']):?><span class="shop-owned">✓ Đã sở hữu</span><?php else:?><form method="post"><input type="hidden" name="csrf" value="<?=shop_h($csrf)?>"><input type="hidden" name="action" value="buy"><input type="hidden" name="item_id" value="<?=(int)$item['id']?>"><button class="shop-button">Mua vật phẩm</button></form><?php endif;?></article><?php endforeach;?></div></section>
<section class="shop-section"><h2>🎒 Túi đồ của tôi</h2><?php if(!$inventory):?><p class="shop-empty">Túi đồ đang trống. Ghé cửa hàng chọn món bạn thích nhé!</p><?php else:?><div class="shop-inventory"><?php foreach($inventory as $item):?><div class="shop-inventory-item"><span class="shop-icon"><?=shop_h($item['icon'])?></span><div><strong><?=shop_h($item['name'])?></strong><small><?=shop_h($item['description'])?></small></div><?php if((int)$item['is_equipped']):?><span class="shop-owned">Đang dùng</span><?php else:?><form method="post"><input type="hidden" name="csrf" value="<?=shop_h($csrf)?>"><input type="hidden" name="action" value="equip"><input type="hidden" name="inventory_id" value="<?=(int)$item['inventory_id']?>"><button class="shop-button shop-small">Trang bị</button></form><?php endif;?></div><?php endforeach;?></div><?php endif;?></section>
<section class="shop-section"><h2>♡ Tặng Xu cho bạn bè</h2><?php if(!$friends):?><p class="shop-empty">Bạn cần kết bạn trước khi gửi Xu.</p><?php else:?><form method="post" class="shop-gift"><input type="hidden" name="csrf" value="<?=shop_h($csrf)?>"><input type="hidden" name="action" value="gift"><label>Người nhận<select name="recipient_id" required><?php foreach($friends as $f):?><option value="<?=(int)$f['id']?>"><?=shop_h($f['display_name'])?></option><?php endforeach;?></select></label><label>Số Xu<input type="number" name="amount" min="1" max="5000" required placeholder="Ví dụ: 50"></label><button class="shop-button">Gửi Xu</button></form><?php endif;?></section>
<section class="shop-section"><h2>↻ Lịch sử giao dịch</h2><?php if(!$history):?><p class="shop-empty">Chưa có giao dịch nào.</p><?php else:?><div class="shop-history"><?php foreach($history as $row):?><div><span><strong><?=shop_h($row['description'])?></strong><small><?=shop_h(date('d/m/Y H:i',strtotime($row['created_at'])))?></small></span><b class="<?=$row['amount']>=0?'shop-plus':'shop-minus'?>"><?=$row['amount']>0?'+':''?><?=number_format((int)$row['amount'])?> Xu</b></div><?php endforeach;?></div><?php endif;?></section>
</main><footer class="site-footer"><div class="footer-bottom">© 2026 Góc Hoàng Đạo</div></footer><script src="js/presence.js"></script></body></html>
