<?php
declare(strict_types=1);

/* Cong EXP mot lan cho moi su kien; loi he thong EXP khong lam hong thao tac chinh. */
function td_add_xp(PDO $pdo, int $memberId, string $eventKey, int $amount, string $description): void {
    if ($memberId < 1 || $amount < 1 || $eventKey === '') return;
    try {
        $event = $pdo->prepare('INSERT IGNORE INTO member_xp_events(member_id,event_key,amount,description) VALUES(?,?,?,?)');
        $event->execute([$memberId, $eventKey, $amount, mb_substr($description, 0, 180, 'UTF-8')]);
        if ($event->rowCount() === 1) {
            $pdo->prepare('INSERT IGNORE INTO member_experience(member_id,total_xp) VALUES(?,0)')->execute([$memberId]);
            $pdo->prepare('UPDATE member_experience SET total_xp=total_xp+? WHERE member_id=?')->execute([$amount, $memberId]);
        }
    } catch (PDOException $e) {
        // Bang EXP chua duoc cai dat hoac loi tam thoi: bo qua, khong chan tinh nang khac.
    }
}
