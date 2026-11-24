<?php
/**
 * Khung avatar Truyện Đêm.
 * Thiếu bảng hoặc chưa cài SQL thì tự động trả về avatar thường, không làm hỏng trang.
 */
if (!function_exists('td_avatar_frame_data')) {
    function td_avatar_frame_data(PDO $pdo, int $memberId): ?array {
        static $cache = [];
        if ($memberId < 1) return null;
        if (array_key_exists($memberId, $cache)) return $cache[$memberId];
        try {
            $q = $pdo->prepare("SELECT f.image_url, f.rarity, f.name
                FROM member_avatar_equipped e
                INNER JOIN avatar_frames f ON f.id=e.frame_id AND f.is_active=1
                INNER JOIN member_avatar_frames own ON own.member_id=e.member_id AND own.frame_id=e.frame_id
                WHERE e.member_id=? LIMIT 1");
            $q->execute([$memberId]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            $cache[$memberId] = $row ?: null;
        } catch (Throwable $e) {
            $cache[$memberId] = null;
        }
        return $cache[$memberId];
    }

    function td_avatar_html(PDO $pdo, int $memberId, ?string $avatarUrl, string $fallback = '✦', string $class = 'td-avatar') : string {
        $safeAvatar = htmlspecialchars((string)$avatarUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeFallback = htmlspecialchars($fallback, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $frame = td_avatar_frame_data($pdo, $memberId);
        $rarity = $frame ? preg_replace('/[^a-z]/', '', (string)$frame['rarity']) : '';
        $html = '<span class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . ' td-avatar-shell' . ($rarity ? ' td-frame-' . $rarity : '') . '">';
        $html .= '<span class="td-avatar-photo">';
        if ($safeAvatar !== '') $html .= '<img src="' . $safeAvatar . '" alt="Ảnh đại diện" loading="lazy">';
        else $html .= '<span class="td-avatar-fallback">' . $safeFallback . '</span>';
        $html .= '</span>';
        if ($frame && !empty($frame['image_url'])) {
            $safeFrame = htmlspecialchars((string)$frame['image_url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeName = htmlspecialchars((string)$frame['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= '<img class="td-avatar-frame-image" src="' . $safeFrame . '" alt="' . $safeName . '" loading="lazy">';
        }
        return $html . '</span>';
    }
}
