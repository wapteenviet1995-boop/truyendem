<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
if (empty($_SESSION['blog_admin_id'])) { header('Location: login.php'); exit; }
if (($_SESSION['blog_admin_role'] ?? 'admin') !== 'admin') { http_response_code(403); exit('Bạn không có quyền cấu hình nhuận bút.'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf']; $error = ''; $notice = '';
function crs_h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$defaults = ['allocation_percent'=>30,'publication_pool_percent'=>40,'reading_pool_percent'=>40,'achievement_pool_percent'=>20,'short_story_fee'=>10000,'serial_story_fee'=>20000,'audio_story_fee'=>30000,'qualified_reads_per_bonus'=>1000,'reading_bonus_amount'=>10000];
$settings = $defaults;
try {
 $row = $pdo->query('SELECT * FROM contributor_reward_settings WHERE id=1')->fetch();
 if ($row) foreach ($defaults as $key=>$value) $settings[$key] = $row[$key] ?? $value;
} catch (Throwable $e) { $error = 'Chưa tìm thấy bảng cấu hình. Hãy chạy database/contributor_reward_settings.sql trong phpMyAdmin.'; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) $error = 'Phiên quản trị hết hạn, hãy tải lại trang.';
 else {
  $values = [];
  foreach ($defaults as $key=>$default) {
   $raw = trim((string)($_POST[$key] ?? ''));
   if ($raw === '' || !is_numeric($raw)) { $error = 'Vui lòng nhập đầy đủ các mức cấu hình hợp lệ.'; break; }
   $values[$key] = (float)$raw;
  }
  if (!$error) {
   if ($values['allocation_percent'] < 0 || $values['allocation_percent'] > 100 ||
       $values['publication_pool_percent'] < 0 || $values['reading_pool_percent'] < 0 || $values['achievement_pool_percent'] < 0 ||
       abs($values['publication_pool_percent'] + $values['reading_pool_percent'] + $values['achievement_pool_percent'] - 100) > 0.01 ||
       $values['short_story_fee'] < 0 || $values['serial_story_fee'] < 0 || $values['audio_story_fee'] < 0 ||
       $values['qualified_reads_per_bonus'] < 1 || $values['qualified_reads_per_bonus'] > 100000000 ||
       $values['reading_bonus_amount'] < 0) {
    $error = 'Tỷ lệ phân bổ phải cộng đúng 100%; tỷ lệ quỹ nằm trong 0–100%, lượt đọc từ 1 trở lên và các mức tiền không âm.';
   } else {
    try {
     $stmt = $pdo->prepare('INSERT INTO contributor_reward_settings (id,allocation_percent,publication_pool_percent,reading_pool_percent,achievement_pool_percent,short_story_fee,serial_story_fee,audio_story_fee,qualified_reads_per_bonus,reading_bonus_amount,updated_by_admin_id) VALUES (1,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE allocation_percent=VALUES(allocation_percent),publication_pool_percent=VALUES(publication_pool_percent),reading_pool_percent=VALUES(reading_pool_percent),achievement_pool_percent=VALUES(achievement_pool_percent),short_story_fee=VALUES(short_story_fee),serial_story_fee=VALUES(serial_story_fee),audio_story_fee=VALUES(audio_story_fee),qualified_reads_per_bonus=VALUES(qualified_reads_per_bonus),reading_bonus_amount=VALUES(reading_bonus_amount),updated_by_admin_id=VALUES(updated_by_admin_id)');
     $stmt->execute([$values['allocation_percent'],$values['publication_pool_percent'],$values['reading_pool_percent'],$values['achievement_pool_percent'],$values['short_story_fee'],$values['serial_story_fee'],$values['audio_story_fee'],$values['qualified_reads_per_bonus'],$values['reading_bonus_amount'],(int)$_SESSION['blog_admin_id']]);
     $settings = $values; $notice = 'Đã lưu quy tắc nhuận bút.';
    } catch (Throwable $e) { $error = 'Không lưu được. Hãy kiểm tra đã chạy database/contributor_reward_settings.sql chưa.'; }
   }
  }
 }
}
?><!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cấu hình nhuận bút | Truyện Đêm</title><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/admin-white.css?v=20261121-layout2"><link rel="stylesheet" href="../css/admin-cms.css?v=20261121-cms1"><style>
.reward-settings{max-width:1080px;margin:26px auto;padding:0 18px 40px}.rs-hero{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:22px}.rs-hero h1{margin:0 0 8px;font-size:27px}.rs-hero p,.rs-note{color:#687386;line-height:1.6;margin:0}.rs-card{background:#fff;border:1px solid #e5e9f0;border-radius:16px;padding:22px;margin:16px 0;box-shadow:0 8px 28px #17243a0a}.rs-card h2{font-size:17px;margin:0 0 6px}.rs-card>p{color:#738095;font-size:13px;margin:0 0 18px}.rs-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.rs-field{display:flex;flex-direction:column;gap:7px}.rs-field label{font-size:13px;font-weight:700;color:#344054}.rs-field input{width:100%;min-height:44px;border:1px solid #d9e0e9;border-radius:10px;padding:10px 12px;font:inherit;color:#172033;background:#fff}.rs-field small{font-size:12px;color:#8490a2}.rs-save{border:0;border-radius:10px;background:#5b4bd8;color:#fff;font-weight:700;padding:13px 20px;font-size:14px;cursor:pointer}.rs-alert{padding:12px 14px;border-radius:10px;margin:14px 0;background:#eaf8ef;color:#17643a}.rs-alert.error{background:#fff0ef;color:#a22c25}.rs-preview{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:14px}.rs-preview div{padding:14px;border-radius:12px;background:#f5f6fb}.rs-preview strong{display:block;font-size:20px;color:#202a3d}.rs-preview span{font-size:12px;color:#69758a}@media(max-width:650px){.reward-settings{padding:0 12px 30px}.rs-hero h1{font-size:22px}.rs-card{padding:16px}.rs-grid{grid-template-columns:1fr}.rs-preview{grid-template-columns:1fr}.rs-save{width:100%}}
</style></head><body class="admin-theme"><?php include __DIR__.'/_nav.php';?><main class="reward-settings"><header class="rs-hero"><div><h1>Cấu hình nhuận bút CTV</h1><p>Thiết lập quỹ tháng, tỷ lệ chia quỹ và đơn giá. Đây là bộ quy tắc dùng cho bước tính thưởng tự động tiếp theo.</p></div></header>
<?php if($notice):?><div class="rs-alert"><?=crs_h($notice)?></div><?php endif;?><?php if($error):?><div class="rs-alert error"><?=crs_h($error)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf" value="<?=crs_h($csrf)?>">
<section class="rs-card"><h2>1. Quỹ nhuận bút theo doanh thu</h2><p>Quỹ tháng sẽ được tính bằng doanh thu thuần tháng × tỷ lệ phân bổ. Doanh thu tháng sẽ được nhập ở bước lập kỳ chi trả.</p><div class="rs-grid"><div class="rs-field"><label for="allocation_percent">Tỷ lệ trích từ doanh thu thuần (%)</label><input id="allocation_percent" type="number" name="allocation_percent" min="0" max="100" step="0.01" value="<?=crs_h($settings['allocation_percent'])?>" required><small>Ví dụ 30% nghĩa là dành 30% doanh thu thuần cho quỹ nhuận bút.</small></div></div></section>
<section class="rs-card"><h2>2. Tỷ lệ chia quỹ</h2><p>Ba nhóm phải cộng đúng 100% để không phân bổ vượt quỹ.</p><div class="rs-grid"><?php foreach(['publication_pool_percent'=>'Quỹ phí duyệt tác phẩm (%)','reading_pool_percent'=>'Quỹ thưởng lượt đọc/nghe hợp lệ (%)','achievement_pool_percent'=>'Quỹ thưởng thành tích (%)'] as $key=>$label):?><div class="rs-field"><label for="<?=crs_h($key)?>"><?=crs_h($label)?></label><input id="<?=crs_h($key)?>" type="number" name="<?=crs_h($key)?>" min="0" max="100" step="0.01" value="<?=crs_h($settings[$key])?>" required></div><?php endforeach;?></div></section>
<section class="rs-card"><h2>3. Phí tác phẩm được duyệt</h2><p>Đơn giá tham khảo ban đầu; bạn có thể thay đổi bất cứ lúc nào trước khi chốt kỳ.</p><div class="rs-grid"><?php foreach(['short_story_fee'=>'Truyện ngắn (VNĐ)','serial_story_fee'=>'Truyện dài / nhiều chương (VNĐ)','audio_story_fee'=>'Truyện audio (VNĐ)'] as $key=>$label):?><div class="rs-field"><label for="<?=crs_h($key)?>"><?=crs_h($label)?></label><input id="<?=crs_h($key)?>" type="number" name="<?=crs_h($key)?>" min="0" step="1000" value="<?=crs_h($settings[$key])?>" required></div><?php endforeach;?></div></section>
<section class="rs-card"><h2>4. Thưởng lượt đọc/nghe hợp lệ</h2><p>Đơn giá thưởng dựa trên tổng lượt đã đạt điều kiện hợp lệ ở bảng theo dõi, không dùng lượt xem thô của website.</p><div class="rs-grid"><div class="rs-field"><label for="qualified_reads_per_bonus">Số lượt hợp lệ cho mỗi mốc thưởng</label><input id="qualified_reads_per_bonus" type="number" name="qualified_reads_per_bonus" min="1" step="1" value="<?=crs_h($settings['qualified_reads_per_bonus'])?>" required></div><div class="rs-field"><label for="reading_bonus_amount">Tiền thưởng mỗi mốc (VNĐ)</label><input id="reading_bonus_amount" type="number" name="reading_bonus_amount" min="0" step="1000" value="<?=crs_h($settings['reading_bonus_amount'])?>" required></div></div><div class="rs-preview"><div><strong><?=number_format((float)$settings['publication_pool_percent'],2)?>%</strong><span>Phí tác phẩm</span></div><div><strong><?=number_format((float)$settings['reading_pool_percent'],2)?>%</strong><span>Lượt đọc/nghe</span></div><div><strong><?=number_format((float)$settings['achievement_pool_percent'],2)?>%</strong><span>Thành tích</span></div></div></section>
<button class="rs-save" type="submit">Lưu cấu hình nhuận bút</button></form><p class="rs-note" style="margin-top:18px">Lưu ý: phần này chỉ lưu quy tắc. Chưa tự tạo khoản phải trả hay đánh dấu thanh toán; bước tiếp theo sẽ lập kỳ, nhập doanh thu, tính tiền theo lượt hợp lệ và giới hạn tổng chi trong quỹ.</p></main></body></html>