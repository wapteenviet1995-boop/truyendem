<?php
declare(strict_types=1);

/**
 * Thuong tac gia 5 Xu cho mot luot doc hop le.
 * Truyen ngan: target_type=short_story, target_id=story_id.
 * Truyen chuong: target_type=chapter, target_id=chapter_id.
 * UNIQUE key trong DB ngan viec mot tai khoan tao thuong lap lai.
 */
function td_reward_story_view(PDO $pdo, int $storyId, int $viewerId, int $authorId, string $targetType, int $targetId): bool {
    if ($storyId < 1 || $viewerId < 1 || $authorId < 1 || $viewerId === $authorId || $targetId < 1) return false;
    if (!in_array($targetType, ['short_story', 'chapter'], true)) return false;
    $ownsTransaction = !$pdo->inTransaction();
    try {
        if ($ownsTransaction) $pdo->beginTransaction();
        $insert = $pdo->prepare('INSERT IGNORE INTO story_view_rewards(viewer_id,author_id,target_type,target_id,amount) VALUES(?,?,?,?,5)');
        $insert->execute([$viewerId, $authorId, $targetType, $targetId]);
        if ($insert->rowCount() !== 1) {
            if ($ownsTransaction) $pdo->commit();
            return false;
        }
        $pdo->prepare('INSERT IGNORE INTO member_wallets(member_id,balance) VALUES(?,0)')->execute([$authorId]);
        $pdo->prepare('UPDATE member_wallets SET balance=balance+5 WHERE member_id=?')->execute([$authorId]);
        $label = $targetType === 'short_story' ? 'Luot doc truyen ngan' : 'Luot doc chuong';
        $pdo->prepare("INSERT INTO wallet_transactions(member_id,amount,type,description,reference_id) VALUES(?,5,'post_reward',?,?)")
            ->execute([$authorId, $label . ' #' . $targetId . ' · +5 Xu', $targetId]);
        if ($ownsTransaction) $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
        return false;
    }
}
