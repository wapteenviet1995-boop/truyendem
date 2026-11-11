<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/experience.php';
require_once __DIR__ . '/includes/story_rewards.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
$q=$pdo->prepare("SELECT c.*,s.id story_id,s.title story_title,s.slug,s.story_kind,s.member_id FROM chapters c JOIN stories s ON s.id=c.story_id WHERE c.id=? AND c.status='published' AND s.status='published' LIMIT 1");$q->execute([$id?:0]);$chapter=$q->fetch();
if(!$chapter){http_response_code(404);exit('Không tìm thấy chương truyện.');}
$memberId=(int)($_SESSION['member_id']??0);
if($memberId){td_reward_story_view($pdo,(int)$chapter['story_id'],$memberId,(int)($chapter['member_id']??0),'chapter',(int)$chapter['id']);td_add_xp($pdo,$memberId,'read_chapter:'.(int)$chapter['id'],10,'Đọc chương truyện');$q=$pdo->prepare('INSERT INTO story_reading_history(member_id,story_id,chapter_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE chapter_id=VALUES(chapter_id),updated_at=CURRENT_TIMESTAMP');$q->execute([$memberId,$chapter['story_id'],$chapter['id']]);}
$q=$pdo->prepare("SELECT id,chapter_number,title FROM chapters WHERE story_id=? AND status='published' ORDER BY chapter_number");$q->execute([$chapter['story_id']]);$all=$q->fetchAll();$prev=$next=null;
foreach($all as $i=>$c){if((int)$c['id']===(int)$chapter['id']){$prev=$all[$i-1]??null;$next=$all[$i+1]??null;break;}}
function rd_h($v){return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function rd_paragraphs($value): string {
    $value = trim((string)$value);
    if ($value === '') return '';
    $blocks = preg_split('/\\R{2,}/u', $value) ?: [];
    $html = [];
    foreach ($blocks as $block) {
        $safe = rd_h(trim($block));
        $safe = preg_replace_callback('~\\[img\\](/uploads/stories/[a-zA-Z0-9._-]+)\\[/img\\]~', function($m) {
            return '<img class="reader-inline-image" src="' . rd_h($m[1]) . '" alt="Ảnh minh họa truyện" loading="lazy">';
        }, $safe);
        if (trim(strip_tags($safe)) === '' && str_contains($safe, '<img')) $html[] = $safe;
        elseif (trim($safe) !== '') $html[] = '<p>' . nl2br($safe, false) . '</p>';
    }
    return implode("\n", $html);
}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=rd_h($chapter['story_title'])?> - Chương <?= (int)$chapter['chapter_number']?> | Truyện Đêm</title><link rel="stylesheet" href="/css/style.css?v=20261111-reader2"><link rel="stylesheet" href="/css/stories.css?v=20261111-reader2"></head><body class="story-site reader-site"><header class="reader-top"><a href="<?=($chapter['story_kind']??'serial')==='short'?'/truyen-ngan/':'/truyen/'?><?=rawurlencode($chapter['slug'])?>.html">← <?=rd_h($chapter['story_title'])?></a><a href="/index.php">☾ Truyện Đêm</a></header><main class="reader-main"><p class="reader-kicker">TRUYỆN ĐÊM · CHƯƠNG <?= (int)$chapter['chapter_number']?></p><h1><?=rd_h($chapter['title'])?></h1><?php if($chapter['audio_url']):?><audio class="story-audio" controls preload="none" src="<?=rd_h($chapter['audio_url'])?>">Trình duyệt không hỗ trợ audio.</audio><?php endif;?><article class="reader-content"><?=rd_paragraphs($chapter['content'])?></article><nav class="reader-pagination"><?php if($prev):?><a href="/read.php?id=<?=(int)$prev['id']?>">← Chương trước</a><?php else:?><span></span><?php endif;?><?php if($next):?><a href="/read.php?id=<?=(int)$next['id']?>">Chương tiếp theo →</a><?php else:?><a href="/story.php?slug=<?=rawurlencode($chapter['slug'])?>">Danh sách chương</a><?php endif;?></nav></main></body></html>