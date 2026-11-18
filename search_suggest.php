<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q, 'UTF-8') < 2) { echo json_encode([]); exit; }
try {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare("SELECT title,slug,author,category,story_kind,cover_url FROM stories WHERE status='published' AND (title LIKE ? OR author LIKE ?) ORDER BY CASE WHEN title LIKE ? THEN 0 ELSE 1 END, updated_at DESC LIMIT 8");
    $stmt->execute([$like,$like,$q . '%']);
    $items = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $prefix = (($row['story_kind'] ?? 'serial') === 'short') ? '/truyen-ngan/' : '/truyen/';
        $items[] = [
            'title' => (string)$row['title'],
            'author' => (string)($row['author'] ?? ''),
            'category' => (string)($row['category'] ?? ''),
            'cover_url' => (string)($row['cover_url'] ?? ''),
            'url' => $prefix . rawurlencode((string)$row['slug']) . '.html'
        ];
    }
    echo json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([]);
}
