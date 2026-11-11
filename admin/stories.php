<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';

if (empty($_SESSION['blog_admin_id'])) {
    header('Location: login.php');
    exit;
}
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];
$error = '';

function story_slug(string $value): string {
    $value = trim($value);
    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) $value = $converted;
    }
    return strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $value), '-'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $error = 'Phiên làm việc đã hết hạn. Hãy tải lại trang.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        try {
            if ($action === 'save_story') {
                $id = (int)($_POST['story_id'] ?? 0);
                $title = trim((string)($_POST['title'] ?? ''));
                $slugInput = trim((string)($_POST['slug'] ?? ''));
                $slug = $slugInput !== '' ? story_slug($slugInput) : story_slug($title);
                $author = trim((string)($_POST['author'] ?? '')) ?: 'Khuyết danh';
                $category = trim((string)($_POST['category'] ?? '')) ?: 'Truyện dài';
                $description = trim((string)($_POST['description'] ?? ''));
                $cover = trim((string)($_POST['cover_url'] ?? ''));
                $type = in_array($_POST['story_type'] ?? 'text', ['text', 'audio', 'both'], true) ? (string)$_POST['story_type'] : 'text';
                $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
                $kind = ($_POST['story_kind'] ?? 'serial') === 'short' ? 'short' : 'serial';
                $shortContent = trim((string)($_POST['short_content'] ?? ''));

                if ($title === '' || $slug === '') {
                    $error = 'Vui lòng nhập tên truyện hợp lệ.';
                } elseif ($kind === 'short' && $shortContent === '') {
                    $error = 'Truyện ngắn cần có nội dung đầy đủ.';
                } elseif ($cover !== '' && !filter_var($cover, FILTER_VALIDATE_URL)) {
                    $error = 'Đường dẫn ảnh bìa không hợp lệ.';
                } else {
                    if ($id > 0) {
                        $stmt = $pdo->prepare('UPDATE stories SET title=?, slug=?, author=?, description=?, cover_url=?, category=?, story_type=?, status=?, story_kind=?, short_content=? WHERE id=?');
                        $stmt->execute([$title, $slug, $author, $description, $cover, $category, $type, $status, $kind, $kind === 'short' ? $shortContent : null, $id]);
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO stories(title,slug,author,description,cover_url,category,story_type,status,story_kind,short_content) VALUES(?,?,?,?,?,?,?,?,?,?)');
                        $stmt->execute([$title, $slug, $author, $description, $cover, $category, $type, $status, $kind, $kind === 'short' ? $shortContent : null]);
                        $id = (int)$pdo->lastInsertId();
                    }
                    header('Location: stories.php?edit=' . $id . '&saved=story');
                    exit;
                }
            } elseif ($action === 'save_chapter') {
                $chapterId = (int)($_POST['chapter_id'] ?? 0);
                $storyId = (int)($_POST['story_id'] ?? 0);
                $kindCheck = $pdo->prepare('SELECT story_kind FROM stories WHERE id=?');
                $kindCheck->execute([$storyId]);
                if ($kindCheck->fetchColumn() === 'short') { throw new RuntimeException('Truyện ngắn không có chương.'); }
                $number = max(1, (int)($_POST['chapter_number'] ?? 1));
                $title = trim((string)($_POST['chapter_title'] ?? ''));
                $body = trim((string)($_POST['content'] ?? ''));
                $audio = trim((string)($_POST['audio_url'] ?? ''));
                $duration = max(0, (int)($_POST['duration_seconds'] ?? 0));
                $status = ($_POST['chapter_status'] ?? 'draft') === 'published' ? 'published' : 'draft';

                if ($storyId < 1 || $title === '' || $body === '') {
                    $error = 'Hãy chọn truyện và nhập tên, nội dung chương.';
                } elseif ($audio !== '' && !filter_var($audio, FILTER_VALIDATE_URL)) {
                    $error = 'Đường dẫn audio không hợp lệ.';
                } else {
                    if ($chapterId > 0) {
                        $stmt = $pdo->prepare('UPDATE chapters SET story_id=?, chapter_number=?, title=?, content=?, audio_url=?, duration_seconds=?, status=? WHERE id=?');
                        $stmt->execute([$storyId, $number, $title, $body, $audio, $duration, $status, $chapterId]);
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO chapters(story_id,chapter_number,title,content,audio_url,duration_seconds,status) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),content=VALUES(content),audio_url=VALUES(audio_url),duration_seconds=VALUES(duration_seconds),status=VALUES(status)');
                        $stmt->execute([$storyId, $number, $title, $body, $audio, $duration, $status]);
                    }
                    header('Location: stories.php?edit=' . $storyId . '&saved=chapter');
                    exit;
                }
            } elseif ($action === 'delete_story') {
                $id = (int)($_POST['story_id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare('DELETE FROM stories WHERE id=?');
                    $stmt->execute([$id]);
                }
                header('Location: stories.php?deleted=story');
                exit;
            } elseif ($action === 'delete_chapter') {
                $id = (int)($_POST['chapter_id'] ?? 0);
                $storyId = (int)($_POST['story_id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare('DELETE FROM chapters WHERE id=? AND story_id=?');
                    $stmt->execute([$id, $storyId]);
                }
                header('Location: stories.php?edit=' . $storyId . '&deleted=chapter');
                exit;
            }
        } catch (Throwable $exception) {
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Không lưu được. Có thể slug hoặc số chương đã bị trùng, hoặc dữ liệu chưa đúng.';
        }
    }
}

$stories = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM chapters c WHERE c.story_id=s.id) AS chapter_count FROM stories s ORDER BY s.updated_at DESC, s.id DESC")->fetchAll();
$selectedId = (int)($_GET['edit'] ?? $_POST['story_id'] ?? 0);
$selectedStory = null;
if ($selectedId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM stories WHERE id=? LIMIT 1');
    $stmt->execute([$selectedId]);
    $selectedStory = $stmt->fetch() ?: null;
}
$editChapter = null;
$chapterId = (int)($_GET['chapter'] ?? 0);
if ($chapterId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM chapters WHERE id=? LIMIT 1');
    $stmt->execute([$chapterId]);
    $editChapter = $stmt->fetch() ?: null;
    if ($editChapter) {
        $selectedId = (int)$editChapter['story_id'];
        $stmt = $pdo->prepare('SELECT * FROM stories WHERE id=? LIMIT 1');
        $stmt->execute([$selectedId]);
        $selectedStory = $stmt->fetch() ?: $selectedStory;
    }
}
$chapters = [];
if ($selectedStory) {
    $stmt = $pdo->prepare('SELECT * FROM chapters WHERE story_id=? ORDER BY chapter_number ASC');
    $stmt->execute([(int)$selectedStory['id']]);
    $chapters = $stmt->fetchAll();
}
$storyForm = $selectedStory ?: ['id'=>0,'title'=>'','slug'=>'','author'=>'Khuyết danh','category'=>'Truyện dài','description'=>'','cover_url'=>'','story_type'=>'text','story_kind'=>'serial','short_content'=>'','status'=>'draft'];
$chapterForm = $editChapter ?: ['id'=>0,'chapter_number'=>count($chapters)+1,'title'=>'','content'=>'','audio_url'=>'','duration_seconds'=>0,'status'=>'draft'];
?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Quản lý truyện | Truyện Đêm</title>
<link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/stories.css">
<style>
.admin-story-wrap{max-width:1050px;margin:24px auto;padding:0 14px;color:#eee}
.admin-story-panel{background:#15131d;border:1px solid #30283d;border-radius:16px;padding:18px;margin:16px 0}
.admin-story-panel h1,.admin-story-panel h2{margin:0 0 14px}
.admin-story-panel label{display:block;margin:12px 0;color:#d9d2e5}
.admin-story-panel input,.admin-story-panel textarea,.admin-story-panel select{display:block;width:100%;box-sizing:border-box;margin-top:6px;padding:12px;border:1px solid #494052;border-radius:9px;background:#0d0b12;color:#fff;font:inherit}
.admin-story-panel textarea{min-height:110px;resize:vertical}
.admin-story-panel button,.admin-story-btn{display:inline-block;border:0;border-radius:9px;padding:10px 15px;background:#8b5cf6;color:#fff;text-decoration:none;font-weight:700;cursor:pointer}
.admin-story-panel .danger{background:#7f2938}
.admin-story-panel .muted{color:#aaa}
.admin-story-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
.admin-story-item{padding:13px;border:1px solid #393044;border-radius:11px;margin:10px 0}
.admin-story-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:10px}
.admin-story-actions form{display:inline}
.admin-story-notice{padding:11px;border-radius:9px;background:#252039;margin:10px 0}
@media(max-width:600px){.admin-story-wrap{padding:0 10px}.admin-story-panel{padding:14px}}
</style>
</head>
<body class="story-site">
<main class="admin-story-wrap">
<section class="admin-story-panel">
<h1>Quản lý nội dung</h1><p class="muted">Chọn đăng truyện dài nhiều chương hoặc truyện ngắn đọc liền một mạch.</p>
<p><a href="index.php">← Trang quản trị</a> · <a href="../index.php" target="_blank">Xem thư viện</a></p>
<?php if ($error !== ''): ?><p class="admin-story-notice"><?=e($error)?></p><?php endif; ?>
<?php if (($_GET['saved'] ?? '') === 'story'): ?><p class="admin-story-notice">✓ Đã lưu thông tin truyện.</p><?php elseif (($_GET['saved'] ?? '') === 'chapter'): ?><p class="admin-story-notice">✓ Đã lưu chương truyện.</p><?php endif; ?>
<?php if (($_GET['deleted'] ?? '') === 'story'): ?><p class="admin-story-notice">✓ Đã xóa truyện.</p><?php elseif (($_GET['deleted'] ?? '') === 'chapter'): ?><p class="admin-story-notice">✓ Đã xóa chương.</p><?php endif; ?>
</section>

<section class="admin-story-panel">
<h2><?=((int)$storyForm['id'] > 0) ? 'Chỉnh sửa nội dung' : '＋ Đăng nội dung mới'?></h2>
<form method="post">
<input type="hidden" name="csrf" value="<?=e($csrf)?>">
<input type="hidden" name="action" value="save_story">
<input type="hidden" name="story_id" value="<?=(int)$storyForm['id']?>">
<div class="admin-story-grid">
<label>Tên truyện *<input name="title" required maxlength="220" value="<?=e((string)$storyForm['title'])?>"></label>
<label>Slug (để trống tự tạo)<input name="slug" value="<?=e((string)$storyForm['slug'])?>"></label>
<label>Tác giả<input name="author" value="<?=e((string)$storyForm['author'])?>"></label>
<label>Thể loại<input name="category" value="<?=e((string)$storyForm['category'])?>"></label>
<label>Hình thức nội dung<select name="story_kind" id="story-kind"><option value="serial" <?=($storyForm['story_kind']??'serial')==='serial'?'selected':''?>>Truyện dài nhiều chương</option><option value="short" <?=($storyForm['story_kind']??'serial')==='short'?'selected':''?>>Truyện ngắn (một bài)</option></select></label>
<label>Loại truyện<select name="story_type"><option value="text" <?=$storyForm['story_type']==='text'?'selected':''?>>Truyện chữ</option><option value="audio" <?=$storyForm['story_type']==='audio'?'selected':''?>>Truyện audio</option><option value="both" <?=$storyForm['story_type']==='both'?'selected':''?>>Chữ + audio</option></select></label>
<label>Trạng thái<select name="status"><option value="draft" <?=$storyForm['status']==='draft'?'selected':''?>>Bản nháp</option><option value="published" <?=$storyForm['status']==='published'?'selected':''?>>Xuất bản</option></select></label>
</div>
<label>Link ảnh bìa<input type="url" name="cover_url" value="<?=e((string)$storyForm['cover_url'])?>" placeholder="https://..."></label>
<label>Mô tả truyện<textarea name="description" rows="4"><?=e((string)$storyForm['description'])?></textarea></label>
<div id="short-content-box"><label>Nội dung truyện ngắn *<textarea name="short_content" rows="16" placeholder="Nhập toàn bộ nội dung truyện ngắn..."><?=e((string)($storyForm['short_content']??''))?></textarea></label></div>
<div class="admin-story-actions"><button type="submit"><?=((int)$storyForm['id'] > 0)?'Lưu thay đổi':'Đăng truyện'?></button><?php if ((int)$storyForm['id'] > 0): ?><a class="admin-story-btn" href="stories.php">＋ Truyện mới</a><?php endif; ?></div>
</form>
</section>

<section class="admin-story-panel">
<h2><?=$editChapter ? 'Chỉnh sửa chương' : 'Thêm chương mới'?></h2>
<?php if (!$stories): ?><p class="muted">Hãy tạo truyện trước khi thêm chương.</p><?php else: ?>
<form method="post">
<input type="hidden" name="csrf" value="<?=e($csrf)?>">
<input type="hidden" name="action" value="save_chapter">
<input type="hidden" name="chapter_id" value="<?=(int)$chapterForm['id']?>">
<label>Thuộc truyện<select name="story_id" required><?php foreach ($stories as $s): ?><option value="<?=(int)$s['id']?>" <?=(int)$s['id']===$selectedId?'selected':''?>><?=e($s['title'])?></option><?php endforeach; ?></select></label>
<div class="admin-story-grid">
<label>Số chương<input type="number" name="chapter_number" min="1" required value="<?=(int)$chapterForm['chapter_number']?>"></label>
<label>Tên chương<input name="chapter_title" required maxlength="220" value="<?=e((string)$chapterForm['title'])?>"></label>
<label>Thời lượng audio (giây)<input type="number" name="duration_seconds" min="0" value="<?=(int)$chapterForm['duration_seconds']?>"></label>
<label>Trạng thái<select name="chapter_status"><option value="draft" <?=$chapterForm['status']==='draft'?'selected':''?>>Bản nháp</option><option value="published" <?=$chapterForm['status']==='published'?'selected':''?>>Xuất bản</option></select></label>
</div>
<label>Link audio (không bắt buộc)<input type="url" name="audio_url" value="<?=e((string)$chapterForm['audio_url'])?>" placeholder="https://..."></label>
<label>Nội dung chương *<textarea name="content" rows="14" required><?=e((string)$chapterForm['content'])?></textarea></label>
<div class="admin-story-actions"><button type="submit"><?=$editChapter?'Lưu chương':'Lưu chương mới'?></button><?php if ($editChapter): ?><a class="admin-story-btn" href="stories.php?edit=<?=$selectedId?>">Hủy sửa chương</a><?php endif; ?></div>
</form>
<?php endif; ?>
</section>

<section class="admin-story-panel">
<h2>Danh sách truyện (<?=count($stories)?>)</h2>
<?php if (!$stories): ?><p class="muted">Chưa có truyện nào. Hãy dùng biểu mẫu phía trên để đăng truyện đầu tiên.</p><?php else: ?>
<?php foreach ($stories as $s): ?>
<article class="admin-story-item">
<strong><?=e($s['title'])?></strong>
<div class="muted"><?=e($s['category'])?> · <?=($s['story_kind']??'serial')==='short'?'Truyện ngắn':'Truyện dài · '.(int)$s['chapter_count'].' chương'?> · <?=$s['status']==='published'?'Đã xuất bản':'Bản nháp'?></div>
<div class="admin-story-actions">
<a class="admin-story-btn" href="stories.php?edit=<?=(int)$s['id']?>">Sửa truyện / chương</a>
<a href="../story.php?slug=<?=rawurlencode($s['slug'])?>" target="_blank">Xem trang truyện</a>
<form method="post" onsubmit="return confirm('Xóa truyện này và toàn bộ chương của truyện?')">
<input type="hidden" name="csrf" value="<?=e($csrf)?>">
<input type="hidden" name="action" value="delete_story">
<input type="hidden" name="story_id" value="<?=(int)$s['id']?>">
<button type="submit" class="danger">Xóa truyện</button>
</form>
</div>
<?php if ($selectedStory && (int)$selectedStory['id']===(int)$s['id']): ?>
<div class="admin-story-item">
<strong>Danh sách chương</strong>
<?php if (!$chapters): ?><p class="muted">Truyện chưa có chương nào.</p><?php else: ?>
<?php foreach ($chapters as $c): ?>
<div class="admin-story-actions">
<span>Chương <?=(int)$c['chapter_number']?>: <?=e($c['title'])?> · <?=$c['status']==='published'?'Đã xuất bản':'Bản nháp'?></span>
<a href="stories.php?edit=<?=(int)$s['id']?>&chapter=<?=(int)$c['id']?>">Sửa</a>
<form method="post" onsubmit="return confirm('Xóa chương này?')">
<input type="hidden" name="csrf" value="<?=e($csrf)?>">
<input type="hidden" name="action" value="delete_chapter">
<input type="hidden" name="story_id" value="<?=(int)$s['id']?>">
<input type="hidden" name="chapter_id" value="<?=(int)$c['id']?>">
<button type="submit" class="danger">Xóa</button>
</form>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<?php endif; ?>
</article>
<?php endforeach; ?>
<?php endif; ?>
</section>
</main>
<script>(()=>{const kind=document.getElementById('story-kind'),shortBox=document.getElementById('short-content-box'),chapter=document.getElementById('chapter-panel');if(!kind)return;function sync(){const isShort=kind.value==='short';shortBox.style.display=isShort?'block':'none';chapter.style.display=isShort?'none':'block';}kind.addEventListener('change',sync);sync();})();</script>
</body>
</html>