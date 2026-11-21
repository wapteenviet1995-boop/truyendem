<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
if (empty($_SESSION['blog_admin_id'])) { header('Location: login.php'); exit; }
if (($_SESSION['blog_admin_role'] ?? 'admin') !== 'admin') { http_response_code(403); exit('Bạn không có quyền lập kỳ nhuận bút.'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf'];$error='';$notice='';
function crp_h($v):string{return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$month=(string)($_POST['period']??date('Y-m',strtotime('first day of last month')));
$revenue=(string)($_POST['net_revenue']??'');
$preview=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??'')))$error='Phiên quản trị hết hạn.';
 elseif(!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/',$month))$error='Tháng không hợp lệ.';
 elseif(!is_numeric($revenue)||(float)$revenue<0)$error='Doanh thu thuần phải là số không âm.';
 else{
  try{
   $start=$month.'-01';$end=(new DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');
   $settings=$pdo->query('SELECT * FROM contributor_reward_settings WHERE id=1')->fetch();
   if(!$settings)throw new RuntimeException('Chưa có cấu hình nhuận bút. Hãy lưu cấu hình trước.');
   $exists=$pdo->prepare('SELECT id FROM contributor_reward_periods WHERE period_key=?');$exists->execute([$month]);
   if($exists->fetchColumn())throw new RuntimeException('Kỳ này đã được lập. Không tạo trùng khoản nhuận bút.');
   $net=(float)$revenue;$fund=round($net*(float)$settings['allocation_percent']/100,2);
   $pubPool=round($fund*(float)$settings['publication_pool_percent']/100,2);
   $readPool=round($fund*(float)$settings['reading_pool_percent']/100,2);
   $achPool=round($fund*(float)$settings['achievement_pool_percent']/100,2);
   $pubQ=$pdo->prepare("SELECT s.member_id,COUNT(*) works,
      SUM(CASE WHEN s.story_type IN ('audio','both') THEN ? WHEN s.story_kind='short' THEN ? ELSE ? END) raw_amount
     FROM story_moderation_reviews r JOIN stories s ON s.id=r.story_id
     JOIN members m ON m.id=s.member_id
     WHERE r.target_type='story' AND r.decision='approved' AND r.reviewed_at>=? AND r.reviewed_at<? 
       AND s.member_id IS NOT NULL AND m.account_type='contributor' AND m.is_active=1
     GROUP BY s.member_id");
   $pubQ->execute([(float)$settings['audio_story_fee'],(float)$settings['short_story_fee'],(float)$settings['serial_story_fee'],$start,(new DateTimeImmutable($end))->modify('+1 day')->format('Y-m-d')]);
   $pubRows=$pubQ->fetchAll();
   $readQ=$pdo->prepare("SELECT e.author_id member_id,COUNT(*) reads FROM story_read_events e JOIN members m ON m.id=e.author_id
     WHERE e.qualified_at IS NOT NULL AND e.read_date>=? AND e.read_date<=? AND e.author_id>0
       AND m.account_type='contributor' AND m.is_active=1 GROUP BY e.author_id");
   $readQ->execute([$start,$end]);$readRows=$readQ->fetchAll();
   $byMember=[];
   foreach($pubRows as $r){$id=(int)$r['member_id'];$byMember[$id]=['works'=>(int)$r['works'],'pub_raw'=>(float)$r['raw_amount'],'reads'=>0,'read_raw'=>0.0];}
   foreach($readRows as $r){$id=(int)$r['member_id'];if(!isset($byMember[$id]))$byMember[$id]=['works'=>0,'pub_raw'=>0.0,'reads'=>0,'read_raw'=>0.0];$byMember[$id]['reads']=(int)$r['reads'];}
   foreach($byMember as &$v){$v['read_raw']=floor($v['reads']/(int)$settings['qualified_reads_per_bonus'])*(float)$settings['reading_bonus_amount'];}unset($v);
   $pubRawTotal=array_sum(array_column($byMember,'pub_raw'));$readRawTotal=array_sum(array_column($byMember,'read_raw'));
   $scalePub=$pubRawTotal>0?min(1,$pubPool/$pubRawTotal):0;$scaleRead=$readRawTotal>0?min(1,$readPool/$readRawTotal):0;
   $pdo->beginTransaction();
   $ins=$pdo->prepare("INSERT INTO contributor_reward_periods(period_key,period_start,period_end,net_revenue,allocation_percent,total_fund,publication_pool,reading_pool,achievement_pool,created_by_admin_id) VALUES(?,?,?,?,?,?,?,?,?,?)");
   $ins->execute([$month,$start,$end,$net,(float)$settings['allocation_percent'],$fund,$pubPool,$readPool,$achPool,(int)$_SESSION['blog_admin_id']]);
   $periodId=(int)$pdo->lastInsertId();
   $itemIns=$pdo->prepare('INSERT INTO contributor_reward_period_items(period_id,member_id,approved_works,qualified_reads,publication_raw,reading_raw,publication_amount,reading_amount,total_amount) VALUES(?,?,?,?,?,?,?,?,?)');
   $rewardIns=$pdo->prepare("INSERT INTO contributor_rewards(member_id,amount,reward_type,description,period_label,created_by_admin_id) VALUES(?,?,'royalty',?,?,?)");
   foreach($byMember as $mid=>$v){
    $pa=round($v['pub_raw']*$scalePub,2);$ra=round($v['read_raw']*$scaleRead,2);$total=round($pa+$ra,2);
    if($total<=0)continue;
    $desc='Nhuận bút kỳ '.$month.' · '.$v['works'].' tác phẩm duyệt · '.$v['reads'].' lượt hợp lệ';
    $rewardIns->execute([$mid,$total,$desc,$month,(int)$_SESSION['blog_admin_id']]);$rewardId=(int)$pdo->lastInsertId();
    $itemIns->execute([$periodId,$mid,$v['works'],$v['reads'],$v['pub_raw'],$v['read_raw'],$pa,$ra,$total]);
    $pdo->prepare('UPDATE contributor_reward_period_items SET reward_id=? WHERE period_id=? AND member_id=?')->execute([$rewardId,$periodId,$mid]);
    require_once __DIR__.'/../includes/notifications.php';
    td_notify_member($pdo,(int)$mid,'contributor_reward','Nhuận bút tháng '.$month,'Đã tạo khoản nhuận bút '.number_format($total,0,',','.').'đ. Khoản tiền đang chờ thanh toán.','/contributor-dashboard.php#rewards');
   }
   $pdo->commit();
   $notice='Đã lập kỳ '.$month.'. Tổng quỹ '.number_format($fund,0,',','.').'đ; đã tạo khoản chờ thanh toán cho CTV.';
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e instanceof RuntimeException?$e->getMessage():'Không thể lập kỳ. Kiểm tra các bảng reward settings, reward periods, story read events và moderation reviews đã được cài đặt.';}
 }
}
$periods=[];try{$periods=$pdo->query("SELECT p.*,COUNT(i.id) members,COALESCE(SUM(i.total_amount),0) allocated FROM contributor_reward_periods p LEFT JOIN contributor_reward_period_items i ON i.period_id=p.id GROUP BY p.id ORDER BY p.period_start DESC LIMIT 18")->fetchAll();}catch(Throwable $e){}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Lập kỳ nhuận bút | Truyện Đêm</title><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/admin-white.css?v=20261121-layout2"><link rel="stylesheet" href="../css/admin-cms.css?v=20261121-cms1"><style>
.rp-wrap{max-width:1120px;margin:26px auto;padding:0 16px 40px}.rp-card{background:#fff;border:1px solid #e5e9f0;border-radius:16px;padding:21px;margin:16px 0;box-shadow:0 8px 28px #17243a0a}.rp-card h1,.rp-card h2{margin:0 0 8px;color:#172033}.rp-muted{color:#718096;line-height:1.6}.rp-form{display:grid;grid-template-columns:1fr 1fr auto;gap:14px;align-items:end}.rp-field{display:flex;flex-direction:column;gap:7px}.rp-field label{font-size:13px;font-weight:700;color:#344054}.rp-field input{height:44px;border:1px solid #d9e0e9;border-radius:10px;padding:0 12px;font:inherit}.rp-button{height:44px;padding:0 18px;border:0;border-radius:10px;background:#5b4bd8;color:white;font-weight:700}.rp-warning{background:#fff6e6;color:#85540a;padding:12px;border-radius:10px;margin:14px 0}.rp-success{background:#eaf8ef;color:#17643a;padding:12px;border-radius:10px;margin:14px 0}.rp-error{background:#fff0ef;color:#a22c25;padding:12px;border-radius:10px;margin:14px 0}.rp-table{width:100%;border-collapse:collapse}.rp-table th,.rp-table td{text-align:left;padding:12px;border-bottom:1px solid #edf0f4;font-size:13px}.rp-table th{color:#718096}.rp-overflow{overflow-x:auto}@media(max-width:680px){.rp-form{grid-template-columns:1fr}.rp-button{width:100%}.rp-card{padding:16px}.rp-table{min-width:700px}}
</style></head><body class="admin-theme"><?php include __DIR__.'/_nav.php';?><main class="rp-wrap"><section class="rp-card"><h1>Lập kỳ nhuận bút tự động</h1><p class="rp-muted">Chọn tháng và nhập doanh thu thuần. Hệ thống tính quỹ, phí tác phẩm được duyệt và lượt đọc/nghe hợp lệ; các khoản thưởng được tạo ở trạng thái chờ thanh toán.</p><?php if($notice):?><div class="rp-success"><?=crp_h($notice)?></div><?php endif;?><?php if($error):?><div class="rp-error"><?=crp_h($error)?></div><?php endif;?>
<div class="rp-warning">Lưu ý: mỗi tháng chỉ được lập một lần. Hãy đối soát doanh thu và dữ liệu lượt đọc trước khi bấm tạo kỳ. Quỹ thành tích được giữ riêng, chưa tự chia trong phiên bản này.</div>
<form method="post" class="rp-form"><input type="hidden" name="csrf" value="<?=crp_h($csrf)?>"><div class="rp-field"><label for="period">Kỳ nhuận bút</label><input id="period" type="month" name="period" value="<?=crp_h($month)?>" required></div><div class="rp-field"><label for="net_revenue">Doanh thu thuần tháng (VNĐ)</label><input id="net_revenue" type="number" name="net_revenue" min="0" step="1" value="<?=crp_h($revenue)?>" placeholder="Ví dụ: 5000000" required></div><button class="rp-button" type="submit">Tính và tạo khoản nhuận bút</button></form></section>
<section class="rp-card"><h2>Lịch sử các kỳ đã lập</h2><p class="rp-muted">Không thể tạo trùng kỳ để tránh phát sinh khoản phải trả hai lần.</p><div class="rp-overflow"><table class="rp-table"><thead><tr><th>Tháng</th><th>Doanh thu thuần</th><th>Quỹ nhuận bút</th><th>Đã phân bổ</th><th>Số CTV</th><th>Ngày lập</th></tr></thead><tbody><?php foreach($periods as $p):?><tr><td><strong><?=crp_h($p['period_key'])?></strong></td><td><?=number_format((float)$p['net_revenue'],0,',','.')?>đ</td><td><?=number_format((float)$p['total_fund'],0,',','.')?>đ</td><td><?=number_format((float)$p['allocated'],0,',','.')?>đ</td><td><?=(int)$p['members']?></td><td><?=crp_h(date('d/m/Y',strtotime($p['created_at'])))?></td></tr><?php endforeach;?><?php if(!$periods):?><tr><td colspan="6">Chưa có kỳ nhuận bút nào.</td></tr><?php endif;?></tbody></table></div></section></main></body></html>