<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';

$signs = [
 ['name'=>'Bạch Dương','symbol'=>'♈','element'=>'Lửa','love'=>'Nồng nhiệt, chủ động và thích chinh phục.','strength'=>'Cả hai dễ truyền cảm hứng, khích lệ nhau hành động và giữ cho tình yêu luôn sôi nổi.','challenge'=>'Cần học cách nhường nhịn; nóng giận hoặc tranh phần đúng có thể khiến chuyện nhỏ thành chuyện lớn.'],
 ['name'=>'Kim Ngưu','symbol'=>'♉','element'=>'Đất','love'=>'Kiên định, chân thành và cần cảm giác an toàn.','strength'=>'Có khả năng xây dựng mối quan hệ ổn định, biết chăm sóc nhau bằng những hành động thiết thực.','challenge'=>'Đừng để sự cố chấp hoặc im lặng kéo dài biến khác biệt thành khoảng cách.'],
 ['name'=>'Song Tử','symbol'=>'♊','element'=>'Khí','love'=>'Tò mò, dí dỏm và cần sự kết nối qua trò chuyện.','strength'=>'Hai người có thể trở thành đôi bạn đồng hành thú vị, luôn có chuyện để chia sẻ và khám phá.','challenge'=>'Cần nhất quán trong lời nói và dành thời gian lắng nghe cảm xúc, không chỉ trao đổi thông tin.'],
 ['name'=>'Cự Giải','symbol'=>'♋','element'=>'Nước','love'=>'Nhạy cảm, ân cần và mong muốn được chở che.','strength'=>'Sự quan tâm và đồng cảm giúp hai người tạo nên cảm giác thân thuộc, ấm áp.','challenge'=>'Nên nói rõ nhu cầu thay vì chờ đối phương tự đoán; tránh giữ chuyện buồn trong lòng.'],
 ['name'=>'Sư Tử','symbol'=>'♌','element'=>'Lửa','love'=>'Rộng rãi, lãng mạn và thích được trân trọng.','strength'=>'Hai người dễ tạo nên nguồn năng lượng tích cực, khuyến khích nhau tự tin và tỏa sáng.','challenge'=>'Cả hai cần biết chia sẻ sự chú ý, đừng biến bất đồng thành cuộc thi ai đúng hơn.'],
 ['name'=>'Xử Nữ','symbol'=>'♍','element'=>'Đất','love'=>'Quan tâm bằng hành động, tinh tế và thực tế.','strength'=>'Có thể cùng nhau chăm chút những điều nhỏ bé và xây dựng kế hoạch dài lâu.','challenge'=>'Bớt soi xét, bớt kỳ vọng đối phương phải hoàn hảo; lời góp ý nên đi cùng sự dịu dàng.'],
 ['name'=>'Thiên Bình','symbol'=>'♎','element'=>'Khí','love'=>'Duyên dáng, hòa nhã và coi trọng sự cân bằng.','strength'=>'Cả hai có xu hướng tìm tiếng nói chung, thích chia sẻ trải nghiệm đẹp và tôn trọng nhau.','challenge'=>'Đừng né tránh những cuộc trò chuyện khó; sự hài hòa thật sự cần cả sự thành thật.'],
 ['name'=>'Bọ Cạp','symbol'=>'♏','element'=>'Nước','love'=>'Sâu sắc, mãnh liệt và coi trọng lòng tin.','strength'=>'Khi đã tin nhau, hai người có thể gắn bó sâu sắc và luôn đứng về phía nhau.','challenge'=>'Tránh thử lòng, ghen tuông hoặc kiểm soát; lòng tin cần được vun đắp bằng giao tiếp rõ ràng.'],
 ['name'=>'Nhân Mã','symbol'=>'♐','element'=>'Lửa','love'=>'Lạc quan, thẳng thắn và yêu tự do.','strength'=>'Dễ cùng nhau trải nghiệm điều mới, giữ tinh thần vui vẻ và truyền cho nhau sự can đảm.','challenge'=>'Cần thống nhất về cam kết và ranh giới riêng, tránh vô tình khiến đối phương thấy thiếu an toàn.'],
 ['name'=>'Ma Kết','symbol'=>'♑','element'=>'Đất','love'=>'Nghiêm túc, có trách nhiệm và hướng đến lâu dài.','strength'=>'Có thể trở thành một đội vững vàng, hỗ trợ mục tiêu và tôn trọng nỗ lực của nhau.','challenge'=>'Đừng để công việc hay trách nhiệm lấn át sự lãng mạn; hãy chủ động thể hiện tình cảm.'],
 ['name'=>'Bảo Bình','symbol'=>'♒','element'=>'Khí','love'=>'Độc lập, khác biệt và cần sự tôn trọng không gian riêng.','strength'=>'Hai người có thể khơi gợi nhiều ý tưởng, tôn trọng cá tính và cùng phát triển.','challenge'=>'Đừng dùng im lặng để giữ khoảng cách; hãy nói rõ khi cần gần gũi hoặc cần thời gian riêng.'],
 ['name'=>'Song Ngư','symbol'=>'♓','element'=>'Nước','love'=>'Lãng mạn, giàu đồng cảm và giàu trí tưởng tượng.','strength'=>'Sự dịu dàng và thấu cảm có thể giúp hai người cảm thấy được lắng nghe, sẻ chia.','challenge'=>'Cần giữ kỳ vọng thực tế, nói thẳng điều mình mong muốn và không hy sinh bản thân quá mức.']
];
$elements = [
 'Lửa'=>['Lửa'=>'Cùng nhiệt huyết','Khí'=>'Lửa và Khí tiếp thêm năng lượng cho nhau','Đất'=>'Khác nhịp sống','Nước'=>'Cảm xúc và hành động khác biệt'],
 'Đất'=>['Lửa'=>'Khác nhịp sống','Khí'=>'Thực tế và tự do cần dung hòa','Đất'=>'Cùng coi trọng sự ổn định','Nước'=>'Đất và Nước có thể bồi đắp nhau'],
 'Khí'=>['Lửa'=>'Khí và Lửa tiếp thêm năng lượng cho nhau','Đất'=>'Thực tế và tự do cần dung hòa','Khí'=>'Cùng yêu giao tiếp và ý tưởng','Nước'=>'Lý trí và cảm xúc cần lắng nghe'],
 'Nước'=>['Lửa'=>'Cảm xúc và hành động khác biệt','Đất'=>'Đất và Nước có thể bồi đắp nhau','Khí'=>'Lý trí và cảm xúc cần lắng nghe','Nước'=>'Cùng nhạy cảm và thấu hiểu']
];
$scoreMap = [
 'Lửa'=>['Lửa'=>82,'Khí'=>91,'Đất'=>61,'Nước'=>58],
 'Đất'=>['Lửa'=>61,'Khí'=>63,'Đất'=>84,'Nước'=>90],
 'Khí'=>['Lửa'=>91,'Đất'=>63,'Khí'=>83,'Nước'=>64],
 'Nước'=>['Lửa'=>58,'Đất'=>90,'Khí'=>64,'Nước'=>85]
];
$me = filter_input(INPUT_GET, 'me', FILTER_VALIDATE_INT);
$other = filter_input(INPUT_GET, 'other', FILTER_VALIDATE_INT);
$valid = $me !== null && $me !== false && $other !== null && $other !== false && isset($signs[$me], $signs[$other]);
$result = null;
if ($valid) {
 $a=$signs[$me]; $b=$signs[$other];
 $score=$scoreMap[$a['element']][$b['element']];
 $score += (($me*7 + $other*11) % 7) - 3;
 $score=max(50,min(96,$score));
 $result=['a'=>$a,'b'=>$b,'score'=>$score,'dynamic'=>$elements[$a['element']][$b['element']]];
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Độ tương hợp tình yêu | Góc Hoàng Đạo</title><link rel="stylesheet" href="css/style.css?v=20261117"></head>
<body class="blog-page compatibility-page">
<header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Góc Hoàng Đạo</strong><small>Khám phá câu chuyện của các vì sao</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="compatibility.php">Tương hợp</a><a href="blog.php">Blog</a><?php if (!empty($_SESSION['member_id'])): ?><a href="account.php">Tài khoản</a><form class="member-nav-logout" method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= h($_SESSION['member_csrf'] ?? '') ?>"><button type="submit">Đăng xuất</button></form><?php else: ?><a href="login.php">Đăng nhập</a><a href="register.php">Đăng ký</a><?php endif; ?></nav></div></header>
<main class="compat-main">
 <section class="compat-hero"><span class="compat-kicker">✦ BẢN ĐỒ KẾT NỐI ✦</span><h1>Hai cung hoàng đạo,<br><em>một câu chuyện</em></h1><p>Chọn cung của bạn và người ấy để khám phá điểm hòa hợp, khác biệt và cách hai bạn có thể thấu hiểu nhau hơn.</p></section>
 <section class="compat-panel">
  <form method="get" class="compat-form">
   <label><span>♡ Cung của bạn</span><select name="me" required><option value="">Chọn cung của bạn</option><?php foreach($signs as $i=>$s): ?><option value="<?= $i ?>" <?= $valid && $me===$i?'selected':'' ?>><?= h($s['symbol'].' '.$s['name']) ?></option><?php endforeach; ?></select></label>
   <div class="compat-heart">♥</div>
   <label><span>♡ Cung của người ấy</span><select name="other" required><option value="">Chọn cung người ấy</option><?php foreach($signs as $i=>$s): ?><option value="<?= $i ?>" <?= $valid && $other===$i?'selected':'' ?>><?= h($s['symbol'].' '.$s['name']) ?></option><?php endforeach; ?></select></label>
   <button class="compat-submit" type="submit">Khám phá độ tương hợp ✦</button>
  </form>
 </section>
 <?php if($result): ?>
 <section class="compat-result">
  <p class="compat-kicker">KẾT NỐI CỦA HAI BẠN</p><div class="compat-pair"><div><span><?= h($result['a']['symbol']) ?></span><strong><?= h($result['a']['name']) ?></strong><small>Nguyên tố <?= h($result['a']['element']) ?></small></div><b>♥</b><div><span><?= h($result['b']['symbol']) ?></span><strong><?= h($result['b']['name']) ?></strong><small>Nguyên tố <?= h($result['b']['element']) ?></small></div></div>
  <div class="compat-score"><strong><?= $result['score'] ?>%</strong><span><?= $result['score']>=85?'Kết nối đầy tiềm năng':($result['score']>=70?'Có nhiều điểm đồng điệu':'Cần thêm thấu hiểu') ?></span><div class="compat-track"><i style="width:<?= $result['score'] ?>%"></i></div></div>
  <p class="compat-dynamic"><?= h($result['dynamic']) ?></p>
  <div class="compat-insights"><article><span>✧</span><h2>Điểm mạnh</h2><p><?= h($result['a']['strength']) ?></p><p><?= h($result['b']['strength']) ?></p></article><article><span>☾</span><h2>Điều cần thấu hiểu</h2><p><?= h($result['a']['challenge']) ?></p><p><?= h($result['b']['challenge']) ?></p></article></div>
  <div class="compat-advice"><h2>Lời nhắn dành cho hai bạn</h2><p><?= h($result['a']['love']) ?> <?= h($result['b']['love']) ?> Hãy trò chuyện chân thành, tôn trọng ranh giới và cùng vun đắp mối quan hệ bằng những hành động nhỏ.</p></div>
  <a class="compat-again" href="compatibility.php">Thử với cung khác ↗</a>
 </section>
 <?php endif; ?>
 <p class="compat-disclaimer">Lưu ý: Điểm số được xây dựng theo nguyên tố hoàng đạo để tham khảo và giải trí, không phải đánh giá khoa học hay dự đoán chắc chắn về một mối quan hệ.</p>
</main>
<footer class="site-footer"><div class="footer-bottom">© 2026 Góc Hoàng Đạo · Cùng bạn ngắm nhìn bầu trời</div></footer>
</body></html>
