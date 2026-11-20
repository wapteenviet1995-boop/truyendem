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
    $map = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
        'À'=>'a','Á'=>'a','Ạ'=>'a','Ả'=>'a','Ã'=>'a','Â'=>'a','Ầ'=>'a','Ấ'=>'a','Ậ'=>'a','Ẩ'=>'a','Ẫ'=>'a','Ă'=>'a','Ằ'=>'a','Ắ'=>'a','Ặ'=>'a','Ẳ'=>'a','Ẵ'=>'a',
        'È'=>'e','É'=>'e','Ẹ'=>'e','Ẻ'=>'e','Ẽ'=>'e','Ê'=>'e','Ề'=>'e','Ế'=>'e','Ệ'=>'e','Ể'=>'e','Ễ'=>'e',
        'Ì'=>'i','Í'=>'i','Ị'=>'i','Ỉ'=>'i','Ĩ'=>'i',
        'Ò'=>'o','Ó'=>'o','Ọ'=>'o','Ỏ'=>'o','Õ'=>'o','Ô'=>'o','Ồ'=>'o','Ố'=>'o','Ộ'=>'o','Ổ'=>'o','Ỗ'=>'o','Ơ'=>'o','Ờ'=>'o','Ớ'=>'o','Ợ'=>'o','Ở'=>'o','Ỡ'=>'o',
        'Ù'=>'u','Ú'=>'u','Ụ'=>'u','Ủ'=>'u','Ũ'=>'u','Ư'=>'u','Ừ'=>'u','Ứ'=>'u','Ự'=>'u','Ử'=>'u','Ữ'=>'u',
        'Ỳ'=>'y','Ý'=>'y','Ỵ'=>'y','Ỷ'=>'y','Ỹ'=>'y','Đ'=>'d'
    ];
    $value = strtr(trim($value), $map);
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
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
            if ($action === 'upload_image') {
                header('Content-Type: application/json; charset=utf-8');
                if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
                    http_response_code(400); echo json_encode(['ok'=>false,'message'=>'Bạn chưa chọn ảnh.']); exit;
                }
                $file = $_FILES['image'];
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int)$file['size'] > 5 * 1024 * 1024) {
                    http_response_code(400); echo json_encode(['ok'=>false,'message'=>'Ảnh lỗi hoặc vượt quá 5 MB.']); exit;
                }
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
                    http_response_code(400); echo json_encode(['ok'=>false,'message'=>'Chỉ nhận ảnh JPG, PNG hoặc WEBP hợp lệ.']); exit;
                }
                $uploadDir = __DIR__ . '/../uploads/stories';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    http_response_code(500); echo json_encode(['ok'=>false,'message'=>'Không tạo được thư mục lưu ảnh.']); exit;
                }
                $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                    http_response_code(500); echo json_encode(['ok'=>false,'message'=>'Không lưu được ảnh lên hosting.']); exit;
                }
                echo json_encode(['ok'=>true,'url'=>'/uploads/stories/' . $filename]); exit;
            } elseif ($action === 'save_story') {
                $id = (int)($_POST['story_id'] ?? 0);
                $title = trim((string)($_POST['title'] ?? ''));
                $slugInput = trim((string)($_POST['slug'] ?? ''));
                $slug = $slugInput !== '' ? story_slug($slugInput) : story_slug($title);
                $author = trim((string)($_POST['author'] ?? '')) ?: 'Khuyết danh';
                $category = trim((string)($_POST['category'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $cover = trim((string)($_POST['cover_url'] ?? ''));
                $type = in_array($_POST['story_type'] ?? 'text', ['text', 'audio', 'both'], true) ? (string)$_POST['story_type'] : 'text';
                $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
                $kindInput = (string)($_POST['story_kind'] ?? 'serial');
                $kind = $kindInput === 'short' ? 'short' : 'serial';
                if ($kindInput === 'audio' || $type === 'audio') {
                    $type = 'audio';
                    $kind = 'short'; // Audio không dùng nội dung chữ/chương; giữ giá trị tương thích CSDL hiện tại.
                }
                $shortContent = trim((string)($_POST['short_content'] ?? ''));

                if ($title === '' || $slug === '') {
                    $error = 'Vui lòng nhập tên truyện hợp lệ.';
                } elseif ($category === '') {
                    $error = 'Vui lòng chọn hoặc nhập thể loại truyện, ví dụ: Kinh dị, Tình cảm, Bí ẩn.';
                } elseif ($kind === 'short' && $shortContent === '') {
                    $error = 'Truyện ngắn cần có nội dung đầy đủ.';
                } elseif ($cover !== '' && !filter_var($cover, FILTER_VALIDATE_URL) && !preg_match('~^/uploads/stories/[a-zA-Z0-9._-]+$~', $cover)) {
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
$audioStories = array_values(array_filter($stories, fn($s) => ($s['story_type'] ?? 'text') === 'audio'));
$shortStories = array_values(array_filter($stories, fn($s) => ($s['story_type'] ?? 'text') !== 'audio' && ($s['story_kind'] ?? 'serial') === 'short'));
$serialStories = array_values(array_filter($stories, fn($s) => ($s['story_type'] ?? 'text') !== 'audio' && ($s['story_kind'] ?? 'serial') !== 'short'));
$listType = in_array($_GET['list'] ?? 'all', ['all','short','serial','audio'], true) ? (string)($_GET['list'] ?? 'all') : 'all';
$searchTerm = trim((string)($_GET['q'] ?? ''));
$statusFilter = in_array($_GET['status'] ?? 'all', ['all','published','draft'], true) ? (string)($_GET['status'] ?? 'all') : 'all';
$visibleStories = $listType === 'short' ? $shortStories : ($listType === 'serial' ? $serialStories : ($listType === 'audio' ? $audioStories : $stories));
$visibleStories = array_values(array_filter($visibleStories, static function($story) use ($searchTerm,$statusFilter) {
    $matchesSearch = $searchTerm === '' || stripos((string)$story['title'],$searchTerm)!==false || stripos((string)$story['author'],$searchTerm)!==false || stripos((string)$story['category'],$searchTerm)!==false;
    $matchesStatus = $statusFilter === 'all' || (string)$story['status'] === $statusFilter;
    return $matchesSearch && $matchesStatus;
}));
$perPage = 15;
$totalVisible = count($visibleStories);
$totalPages = max(1,(int)ceil($totalVisible/$perPage));
$currentPage = max(1,min($totalPages,(int)($_GET['page']??1)));
$visibleStories = array_slice($visibleStories,($currentPage-1)*$perPage,$perPage);
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
$storyForm = $selectedStory ?: ['id'=>0,'title'=>'','slug'=>'','author'=>'Khuyết danh','category'=>'','description'=>'','cover_url'=>'','story_type'=>'text','story_kind'=>'serial','short_content'=>'','status'=>'draft'];
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
.admin-story-panel input[type=file]{padding:9px;background:#17131f}
.admin-story-panel small.muted{display:block;margin-top:5px}
.image-upload-trigger{margin:0 0 12px;background:#3b3150!important;border:1px solid #6b568e!important}
.image-upload-status{display:block;margin:7px 0;color:#c9b7ef;font-size:13px}
.admin-story-panel button,.admin-story-btn{display:inline-block;border:0;border-radius:9px;padding:10px 15px;background:#8b5cf6;color:#fff;text-decoration:none;font-weight:700;cursor:pointer}
.admin-story-panel .danger{background:#7f2938}
.admin-story-panel .muted{color:#aaa}
.admin-story-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
.admin-story-item{padding:13px;border:1px solid #393044;border-radius:11px;margin:10px 0}
.admin-story-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:10px}
.admin-story-actions form{display:inline}
.admin-story-notice{padding:11px;border-radius:9px;background:#252039;margin:10px 0}
.admin-story-tabs{display:flex;gap:9px;flex-wrap:wrap;margin:18px 0 4px}
.admin-story-tabs a{display:inline-block;padding:10px 14px;border:1px solid #493b5d;border-radius:10px;color:#ddd;text-decoration:none;background:#100e16}
.admin-story-tabs a.active{background:#7546c8;border-color:#9b76df;color:#fff;font-weight:700}
@media(max-width:600px){.admin-story-wrap{padding:0 10px}.admin-story-panel{padding:14px}}
</style>
<link rel="stylesheet" href="../css/story-editor.css?v=20261114-1">
<link rel="stylesheet" href="../css/admin-white.css?v=20261114-white1">
<link rel="stylesheet" href="../css/admin-cms.css?v=20261121-cms1">
<style>
body.admin-theme .story-list-filters{display:grid;grid-template-columns:minmax(180px,1fr) minmax(150px,220px) auto auto;gap:9px;align-items:center;margin:16px 0 8px;padding:13px;background:#f7f8fb;border:1px solid #e8ebf2;border-radius:12px}
body.admin-theme .story-list-filters input,body.admin-theme .story-list-filters select{width:100%;min-width:0;min-height:40px;padding:9px 11px;border:1px solid #dfe3ec;border-radius:8px;background:#fff;color:#293044}
body.admin-theme .story-list-filters button{min-height:40px;padding:8px 13px;border:0;border-radius:8px;background:#6941a5;color:#fff;font-weight:750}
body.admin-theme .story-list-filters a{font-size:12px;font-weight:700;text-decoration:none}
body.admin-theme .story-list-summary{margin:10px 2px;color:#7d8495;font-size:12px}
body.admin-theme .story-list-summary strong{color:#6941a5;font-size:15px}
body.admin-theme .story-pagination{display:flex;align-items:center;justify-content:center;gap:16px;padding:16px}
body.admin-theme .story-pagination a{padding:9px 13px;border:1px solid #e1e4ec;border-radius:8px;background:#fff;text-decoration:none;font-size:12px;font-weight:700}
body.admin-theme .story-pagination strong{color:#626a7d;font-size:12px}
@media(max-width:650px){body.admin-theme .story-list-filters{grid-template-columns:1fr 1fr}body.admin-theme .story-list-filters input{grid-column:1/-1}body.admin-theme .story-list-filters button{width:100%}body.admin-theme .story-list-filters a{padding:10px}body.admin-theme .story-pagination{justify-content:space-between}}
</style>
</head>
<body class="story-site admin-theme"><?php include __DIR__ . '/_nav.php'; ?>
<main class="admin-story-wrap">
<section class="admin-story-panel">
<h1>Quản lý nội dung</h1><p class="muted">Chọn đăng truyện dài nhiều chương hoặc truyện ngắn đọc liền một mạch.</p>
<p><a href="index.php">← Trang quản trị</a> · <a href="../index.php" target="_blank">Xem thư viện</a></p>
<nav class="admin-story-tabs">
<a class="<?=$listType==='all'?'active':''?>" href="stories.php?list=all">Tất cả (<?=count($stories)?>)</a>
<a class="<?=$listType==='short'?'active':''?>" href="stories.php?list=short">Truyện ngắn (<?=count($shortStories)?>)</a>
<a class="<?=$listType==='serial'?'active':''?>" href="stories.php?list=serial">Truyện nhiều chương (<?=count($serialStories)?>)</a>
<a class="<?=$listType==='audio'?'active':''?>" href="stories.php?list=audio">Audio (<?=count($audioStories)?>)</a>
</nav>
<form class="story-list-filters" method="get">
<input type="hidden" name="list" value="<?=e($listType)?>">
<input type="search" name="q" value="<?=e($searchTerm)?>" placeholder="Tìm tên truyện, tác giả hoặc thể loại">
<select name="status"><option value="all" <?=$statusFilter==='all'?'selected':''?>>Tất cả trạng thái</option><option value="published" <?=$statusFilter==='published'?'selected':''?>>Đã xuất bản</option><option value="draft" <?=$statusFilter==='draft'?'selected':''?>>Bản nháp</option></select>
<button type="submit">Lọc danh sách</button>
<?php if($searchTerm!==''||$statusFilter!=='all'):?><a href="stories.php?list=<?=e($listType)?>">Đặt lại</a><?php endif;?>
</form>
<div class="story-list-summary"><strong><?=$totalVisible?></strong> nội dung phù hợp <span>·</span> Trang <?=$currentPage?> / <?=$totalPages?></div>
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
<label>Thể loại *<input name="category" required placeholder="Ví dụ: Kinh dị, Tình cảm, Bí ẩn" value="<?=e((string)$storyForm['category'])?>"></label>
<label>Hình thức nội dung<select name="story_kind" id="story-kind"><option value="serial" <?=($storyForm['story_kind']??'serial')==='serial'?'selected':''?>>Truyện dài nhiều chương</option><option value="short" <?=($storyForm['story_kind']??'serial')==='short' && ($storyForm['story_type']??'text')!=='audio'?'selected':''?>>Truyện ngắn (một bài)</option><option value="audio" <?=($storyForm['story_type']??'text')==='audio'?'selected':''?>>Audio</option></select></label>
<label>Loại truyện<select name="story_type" id="story-type"><option value="text" <?=$storyForm['story_type']==='text'?'selected':''?>>Truyện chữ</option><option value="audio" <?=$storyForm['story_type']==='audio'?'selected':''?>>Truyện audio</option><option value="both" <?=$storyForm['story_type']==='both'?'selected':''?>>Chữ + audio</option></select></label>
<label>Trạng thái<select name="status"><option value="draft" <?=$storyForm['status']==='draft'?'selected':''?>>Bản nháp</option><option value="published" <?=$storyForm['status']==='published'?'selected':''?>>Xuất bản</option></select></label>
</div>
<label>Ảnh bìa truyện<input type="text" id="cover-url" name="cover_url" value="<?=e((string)$storyForm['cover_url'])?>" placeholder="Link ảnh hoặc tải ảnh từ điện thoại"><input type="file" id="cover-image-file" accept="image/jpeg,image/png,image/webp"><small class="muted">JPG, PNG, WEBP · tối đa 5 MB</small><img id="cover-preview" src="<?=e((string)$storyForm['cover_url'])?>" alt="" style="display:<?=!empty($storyForm['cover_url'])?'block':'none'?>;max-width:150px;max-height:180px;object-fit:cover;border-radius:10px;margin-top:10px"></label>
<label>Mô tả truyện<textarea name="description" rows="4"><?=e((string)$storyForm['description'])?></textarea></label>
<div id="short-content-box"><label>Nội dung truyện ngắn *<textarea name="short_content" id="short-content" rows="16" placeholder="Nhập toàn bộ nội dung truyện ngắn..."><?=e((string)($storyForm['short_content']??''))?></textarea></label><button type="button" class="image-upload-trigger" data-target="short-content">＋ Chèn ảnh vào truyện ngắn</button></div>
<div class="admin-story-actions"><button type="submit"><?=((int)$storyForm['id'] > 0)?'Lưu thay đổi':'Đăng truyện'?></button><?php if ((int)$storyForm['id'] > 0): ?><a class="admin-story-btn" href="stories.php">＋ Truyện mới</a><?php endif; ?></div>
</form>
</section>

<?php if ($serialStories && (!$selectedStory || ($selectedStory['story_kind'] ?? 'serial') !== 'short')): ?>
<section class="admin-story-panel" id="chapter-panel">
<h2><?=$editChapter ? 'Chỉnh sửa chương' : 'Thêm chương mới'?></h2>
<form method="post">
<input type="hidden" name="csrf" value="<?=e($csrf)?>">
<input type="hidden" name="action" value="save_chapter">
<input type="hidden" name="chapter_id" value="<?=(int)$chapterForm['id']?>">
<label>Thuộc truyện<select name="story_id" required><?php foreach ($serialStories as $s): ?><option value="<?=(int)$s['id']?>" <?=(int)$s['id']===$selectedId?'selected':''?>><?=e($s['title'])?></option><?php endforeach; ?></select></label>
<div class="admin-story-grid">
<label>Số chương<input type="number" name="chapter_number" min="1" required value="<?=(int)$chapterForm['chapter_number']?>"></label>
<label>Tên chương<input name="chapter_title" required maxlength="220" value="<?=e((string)$chapterForm['title'])?>"></label>
<label>Thời lượng audio (giây)<input type="number" name="duration_seconds" min="0" value="<?=(int)$chapterForm['duration_seconds']?>"></label>
<label>Trạng thái<select name="chapter_status"><option value="draft" <?=$chapterForm['status']==='draft'?'selected':''?>>Bản nháp</option><option value="published" <?=$chapterForm['status']==='published'?'selected':''?>>Xuất bản</option></select></label>
</div>
<label>Link audio (không bắt buộc)<input type="url" name="audio_url" value="<?=e((string)$chapterForm['audio_url'])?>" placeholder="https://..."></label>
<label>Nội dung chương *<textarea name="content" id="chapter-content" rows="14" required><?=e((string)$chapterForm['content'])?></textarea></label><button type="button" class="image-upload-trigger" data-target="chapter-content">＋ Chèn ảnh vào chương</button>
<div class="admin-story-actions"><button type="submit"><?=$editChapter?'Lưu chương':'Lưu chương mới'?></button><?php if ($editChapter): ?><a class="admin-story-btn" href="stories.php?edit=<?=$selectedId?>">Hủy sửa chương</a><?php endif; ?></div>
</form>
</section>
<?php elseif ($selectedStory && ($selectedStory['story_kind'] ?? 'serial') === 'short'): ?>
<section class="admin-story-panel"><p class="muted">Đây là truyện ngắn nên không cần thêm chương. Hãy sửa nội dung trong phần đăng truyện ở phía trên.</p></section>
<?php endif; ?>

<?php
$renderStoryList = function(array $items, string $heading, string $empty, bool $showChapters) use ($csrf, $selectedStory, $chapters, $selectedId) {
?>
<section class="admin-story-panel">
<h2><?=e($heading)?> (<?=count($items)?>)</h2>
<?php if (!$items): ?><p class="muted"><?=e($empty)?></p><?php else: ?>
<?php foreach ($items as $s): ?>
<article class="admin-story-item">
<strong><?=e($s['title'])?></strong>
<div class="muted"><?=e($s['category'])?> · <?=($s['story_kind']??'serial')==='short'?'Truyện ngắn':'Truyện nhiều chương · '.(int)$s['chapter_count'].' chương'?> · <?=$s['status']==='published'?'Đã xuất bản':'Bản nháp'?></div>
<div class="admin-story-actions">
<a class="admin-story-btn" href="stories.php?list=<?=($s['story_type']??'text')==='audio'?'audio':((($s['story_kind']??'serial')==='short')?'short':'serial')?>&edit=<?=(int)$s['id']?>">Sửa nội dung</a>
<a href="<?=($s['story_type']??'text')==='audio'?'../audio/'.rawurlencode($s['slug']).'.html':'../story.php?slug='.rawurlencode($s['slug'])?>" target="_blank">Xem trang truyện</a>
<form method="post" onsubmit="return confirm('Xóa truyện này và toàn bộ chương của truyện?')">
<input type="hidden" name="csrf" value="<?=e($csrf)?>">
<input type="hidden" name="action" value="delete_story">
<input type="hidden" name="story_id" value="<?=(int)$s['id']?>">
<button type="submit" class="danger">Xóa truyện</button>
</form>
</div>
<?php if ($showChapters && $selectedStory && (int)$selectedStory['id']===(int)$s['id']): ?>
<div class="admin-story-item">
<strong>Danh sách chương</strong>
<?php if (!$chapters): ?><p class="muted">Truyện chưa có chương nào.</p><?php else: ?>
<?php foreach ($chapters as $ch): ?>
<div class="admin-story-actions">
<span>Chương <?=(int)$ch['chapter_number']?>: <?=e($ch['title'])?> · <?=$ch['status']==='published'?'Đã xuất bản':'Bản nháp'?></span>
<a href="stories.php?list=serial&edit=<?=(int)$s['id']?>&chapter=<?=(int)$ch['id']?>">Sửa</a>
<form method="post" onsubmit="return confirm('Xóa chương này?')">
<input type="hidden" name="csrf" value="<?=e($csrf)?>">
<input type="hidden" name="action" value="delete_chapter">
<input type="hidden" name="story_id" value="<?=(int)$s['id']?>">
<input type="hidden" name="chapter_id" value="<?=(int)$ch['id']?>">
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
<?php }; ?>
<?php $heading = $listType==='audio'?'Danh sách truyện audio':($listType==='short'?'Danh sách truyện ngắn':($listType==='serial'?'Danh sách truyện nhiều chương':'Tất cả nội dung')); ?>
<?php $renderStoryList($visibleStories,$heading,'Không tìm thấy nội dung phù hợp với bộ lọc.', $listType==='serial' || $listType==='all'); ?>
<?php if($totalPages>1):?><nav class="story-pagination" aria-label="Phân trang"><?php if($currentPage>1):?><a href="?<?=http_build_query(['list'=>$listType,'q'=>$searchTerm,'status'=>$statusFilter,'page'=>$currentPage-1])?>">← Trước</a><?php endif;?><strong><?=$currentPage?> / <?=$totalPages?></strong><?php if($currentPage<$totalPages):?><a href="?<?=http_build_query(['list'=>$listType,'q'=>$searchTerm,'status'=>$statusFilter,'page'=>$currentPage+1])?>">Sau →</a><?php endif;?></nav><?php endif;?>
</main>
<input type="file" id="content-image-file" accept="image/jpeg,image/png,image/webp" hidden>
<span class="image-upload-status" id="image-upload-status" aria-live="polite"></span>
<script>(()=> {
const kind=document.getElementById('story-kind'),shortBox=document.getElementById('short-content-box'),chapter=document.getElementById('chapter-panel');
const type=document.getElementById('story-type');
if(kind){function sync(){const audio=kind.value==='audio'||(type&&type.value==='audio');if(audio){kind.value='audio';if(type)type.value='audio';}const isShort=kind.value==='short';shortBox.style.display=isShort?'block':'none';if(chapter)chapter.style.display=(isShort||audio)?'none':'block';}
kind.addEventListener('change',()=>{if(kind.value==='audio'&&type)type.value='audio';sync();});
type?.addEventListener('change',()=>{if(type.value==='audio')kind.value='audio';else if(kind.value==='audio')kind.value='short';sync();});sync();}
const csrf=document.querySelector('input[name="csrf"]')?.value||'';
const status=document.getElementById('image-upload-status');
const contentFile=document.getElementById('content-image-file');
let activeTarget=null;
async function upload(file){const data=new FormData();data.append('csrf',csrf);data.append('action','upload_image');data.append('image',file);const response=await fetch('stories.php',{method:'POST',body:data,credentials:'same-origin'});const result=await response.json();if(!response.ok||!result.ok)throw new Error(result.message||'Tải ảnh thất bại');return result.url;}
document.querySelectorAll('.image-upload-trigger').forEach(button=>button.addEventListener('click',()=>{activeTarget=document.getElementById(button.dataset.target);contentFile.click();}));
contentFile.addEventListener('change',async()=>{const file=contentFile.files[0];if(!file||!activeTarget)return;try{status.textContent='Đang tải ảnh lên...';const url=await upload(file);const marker='\n\n[img]'+url+'[/img]\n\n';const start=activeTarget.selectionStart??activeTarget.value.length,end=activeTarget.selectionEnd??start;activeTarget.setRangeText(marker,start,end,'end');activeTarget.focus();status.textContent='✓ Đã tải ảnh và chèn vào nội dung.';}catch(error){status.textContent=error.message;}finally{contentFile.value='';}});
const coverFile=document.getElementById('cover-image-file'),coverUrl=document.getElementById('cover-url'),preview=document.getElementById('cover-preview');
coverFile?.addEventListener('change',async()=>{const file=coverFile.files[0];if(!file)return;try{status.textContent='Đang tải ảnh bìa...';const url=await upload(file);coverUrl.value=url;preview.src=url;preview.style.display='block';status.textContent='✓ Đã tải ảnh bìa lên.';}catch(error){status.textContent=error.message;}finally{coverFile.value='';}});
})();</script>
</body>
</html>