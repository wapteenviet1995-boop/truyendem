<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'message'=>'Method not allowed']);
    exit;
}
$fetchSite = strtolower((string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
if ($fetchSite === 'cross-site') {
    http_response_code(403);
    echo json_encode(['ok'=>false,'message'=>'Forbidden']);
    exit;
}
$storyId = filter_input(INPUT_POST, 'story_id', FILTER_VALIDATE_INT) ?: 0;
$targetId = filter_input(INPUT_POST, 'target_id', FILTER_VALIDATE_INT) ?: 0;
$targetType = (string)($_POST['target_type'] ?? '');
$mode = (string)($_POST['mode'] ?? '');
$memberId = (int)($_SESSION['member_id'] ?? 0);
if ($storyId < 1 || $targetId < 1 || !in_array($targetType, ['short_story','chapter','audio'], true) || !in_array($mode, ['start','ping'], true)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>'Invalid event']);
    exit;
}
try {
    $q = $pdo->prepare("SELECT id,member_id,story_kind,story_type FROM stories WHERE id=? AND status='published' LIMIT 1");
    $q->execute([$storyId]);
    $story = $q->fetch();
    if (!$story) {
        http_response_code(404);
        echo json_encode(['ok'=>false,'message'=>'Story not found']);
        exit;
    }
    $authorId = (int)($story['member_id'] ?? 0);
    if ($memberId > 0 && $memberId === $authorId) {
        echo json_encode(['ok'=>true,'ignored'=>'author']);
        exit;
    }
    if ($targetType === 'short_story' && (($story['story_kind'] ?? '') !== 'short' || $targetId !== $storyId)) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'message'=>'Invalid short story']);
        exit;
    }
    if ($targetType === 'chapter') {
        $check = $pdo->prepare("SELECT id FROM chapters WHERE id=? AND story_id=? AND status='published' LIMIT 1");
        $check->execute([$targetId,$storyId]);
        if (!$check->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['ok'=>false,'message'=>'Chapter not found']);
            exit;
        }
    }
    if ($targetType === 'audio') {
        if (!in_array((string)$story['story_type'], ['audio','both'], true)) {
            http_response_code(400);
            echo json_encode(['ok'=>false,'message'=>'Not an audio story']);
            exit;
        }
        $check = $pdo->prepare("SELECT id FROM audio_tracks WHERE story_id=? AND status='published' LIMIT 1");
        $check->execute([$storyId]);
        if (!$check->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['ok'=>false,'message'=>'No published audio']);
            exit;
        }
        $targetId = $storyId;
    }
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300);
    $visitorSeed = $memberId > 0 ? 'member:'.$memberId : 'guest:'.$ip.'|'.$ua.'|'.session_id();
    $visitorKey = hash('sha256', $visitorSeed);
    $insert = $pdo->prepare("INSERT IGNORE INTO story_read_events
        (story_id,target_type,target_id,member_id,author_id,visitor_key,read_date)
        VALUES(?,?,?,?,?,?,CURDATE())");
    $insert->execute([$storyId,$targetType,$targetId,$memberId ?: null,$authorId,$visitorKey]);
    if ($mode === 'ping') {
        $update = $pdo->prepare("UPDATE story_read_events
            SET qualified_at = IF(active_seconds + 15 >= 20 AND qualified_at IS NULL, NOW(), qualified_at),
                active_seconds = LEAST(1800, active_seconds + 15),
                last_ping_at = NOW()
            WHERE story_id=? AND target_type=? AND target_id=? AND visitor_key=? AND read_date=CURDATE()
              AND active_seconds < 1800
              AND (last_ping_at IS NULL OR last_ping_at <= DATE_SUB(NOW(), INTERVAL 12 SECOND))");
        $update->execute([$storyId,$targetType,$targetId,$visitorKey]);
    } else {
        $pdo->prepare("UPDATE story_read_events SET last_ping_at=COALESCE(last_ping_at,NOW())
            WHERE story_id=? AND target_type=? AND target_id=? AND visitor_key=? AND read_date=CURDATE()")
            ->execute([$storyId,$targetType,$targetId,$visitorKey]);
    }
    $q = $pdo->prepare("SELECT active_seconds,qualified_at FROM story_read_events
        WHERE story_id=? AND target_type=? AND target_id=? AND visitor_key=? AND read_date=CURDATE() LIMIT 1");
    $q->execute([$storyId,$targetType,$targetId,$visitorKey]);
    $event = $q->fetch() ?: [];
    echo json_encode(['ok'=>true,'active_seconds'=>(int)($event['active_seconds']??0),'qualified'=>!empty($event['qualified_at'])]);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok'=>false,'message'=>'Tracking temporarily unavailable']);
}
