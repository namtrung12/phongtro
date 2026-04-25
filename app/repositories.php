<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function landlordLevelFromPoints(int $points): int
{
    return 0;
}

function addLandlordPoints(int $landlordId, int $points): void
{
    // giữ nguyên để không cộng điểm khi bỏ Level
    return;
}

function updateLandlordLevel(int $landlordId): void
{
    // không còn dùng level, giữ stub
    return;
}

function landlordMonthlySpend(int $landlordId): int
{
    $pdo = getPDO();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $sql = 'SELECT COALESCE(SUM(amount),0) FROM payments WHERE landlord_id = :id AND status = "paid" AND DATE_FORMAT(created_at, "%Y-%m") = DATE_FORMAT(NOW(), "%Y-%m")';
    } else {
        $sql = 'SELECT COALESCE(SUM(amount),0) FROM payments WHERE landlord_id = :id AND status = "paid" AND strftime("%Y-%m", created_at) = strftime("%Y-%m","now")';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $landlordId]);
    return (int)$stmt->fetchColumn();
}

function landlordVipTier(int $landlordId): array
{
    // VIP bị tắt, trả về mặc định
    return ['tier' => 'Thường', 'discount' => 0, 'spend' => 0, 'weight' => 0];
}

function getLandlordMeta(int $landlordId): array
{
    $vip = landlordVipTier($landlordId);
    return ['points' => 0, 'level' => 0, 'vip' => $vip];
}

function filterLeadsByVipDelay(array $leads, int $landlordId): array
{
    $vip = landlordVipTier($landlordId);
    $weight = (int)($vip['weight'] ?? 0);
    $now = time();

    return array_values(array_filter($leads, function($l) use ($weight, $now) {
        $created = isset($l['created_at']) ? strtotime($l['created_at']) : 0;
        $ageSec = max(0, $now - $created);

        // “Đặt chỗ trước”: VIP3+ (weight>=3) được xem ngay trong 60s đầu,
        // VIP thấp hơn phải chờ đủ 60s.
        if ($ageSec < 60 && $weight < 3) {
            return false;
        }
        return true;
    }));
}

function ensureLeadMarketplaceSchema(PDO $pdo): void
{
    static $done = [];
    $key = spl_object_id($pdo);
    if (!empty($done[$key])) {
        return;
    }

    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $textType = $driver === 'mysql' ? 'VARCHAR(255) NULL' : 'TEXT NULL';
    $longTextType = $driver === 'mysql' ? 'TEXT NULL' : 'TEXT NULL';
    $intType = $driver === 'mysql' ? 'INT NULL' : 'INTEGER NULL';
    $boolType = $driver === 'mysql' ? 'TINYINT(1) NULL' : 'INTEGER NULL';
    $dateTimeType = $driver === 'mysql' ? 'DATETIME NULL' : 'TEXT NULL';

    ensureTableColumns($pdo, 'tenant_posts', [
        'post_kind' => 'post_kind ' . $textType,
        'district' => 'district ' . $textType,
        'ward' => 'ward ' . $textType,
        'near_place' => 'near_place ' . $textType,
        'room_type' => 'room_type ' . $textType,
        'move_in_time' => 'move_in_time ' . $textType,
        'area_min' => 'area_min ' . $intType,
        'priority' => 'priority ' . $textType,
        'shared_owner' => 'shared_owner ' . $boolType,
        'closed_room' => 'closed_room ' . $boolType,
        'amenities' => 'amenities ' . $longTextType,
        'amenities_list' => 'amenities_list ' . $longTextType,
    ]);

    ensureTableColumns($pdo, 'leads', [
        'source' => 'source ' . $textType,
        'last_interaction_at' => 'last_interaction_at ' . $dateTimeType,
    ]);

    ensureTableColumns($pdo, 'lead_logs', [
        'note' => 'note ' . $longTextType,
        'actor_id' => 'actor_id ' . $intType,
        'actor_role' => 'actor_role ' . $textType,
    ]);

    if ($driver === 'mysql') {
        try {
            $pdo->exec("ALTER TABLE leads MODIFY status ENUM('new','opened','contacted','negotiating','closed','invalid','sold','used','paid') DEFAULT 'new'");
        } catch (PDOException $e) {
            // ignore incompatible engines / insufficient privilege
        }
    }

    try {
        $pdo->exec("UPDATE tenant_posts SET post_kind = CASE
            WHEN post_kind IS NOT NULL AND post_kind <> '' THEN post_kind
            WHEN note LIKE '%- Địa chỉ phòng:%' THEN 'roommate'
            ELSE 'room'
        END");
    } catch (PDOException $e) {
        // ignore if the column does not exist yet
    }

    try {
        $pdo->exec("UPDATE leads SET source = CASE
            WHEN source IS NOT NULL AND source <> '' THEN source
            WHEN tenant_post_id IS NOT NULL THEN 'marketplace'
            ELSE 'direct'
        END");
    } catch (PDOException $e) {
        // ignore if the column does not exist yet
    }

    $done[$key] = true;
}

function rowFirstValue(array $row, array $keys)
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $row)) {
            continue;
        }
        $value = $row[$key];
        if ($value === null) {
            continue;
        }
        if (is_string($value) && trim($value) === '') {
            continue;
        }
        return $value;
    }
    return null;
}

function normalizeLeadSearchText(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    $value = mb_strtolower($value, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', $value);
    return trim((string)$value);
}

function noteValueByLabel(string $note, string $label): ?string
{
    $pattern = '/^\s*-\s*' . preg_quote($label, '/') . '\s*:\s*(.+)$/mi';
    if (!preg_match($pattern, $note, $matches)) {
        return null;
    }
    $value = trim((string)($matches[1] ?? ''));
    return $value === '' ? null : $value;
}

function yesNoValueToFlag(?string $value): ?int
{
    $normalized = normalizeLeadSearchText($value);
    if ($normalized === 'co' || $normalized === 'có') {
        return 1;
    }
    if ($normalized === 'khong' || $normalized === 'không') {
        return 0;
    }
    return null;
}

function splitLeadKeywords(string $value): array
{
    $parts = preg_split('/[,;|•]+/u', $value) ?: [];
    $out = [];
    foreach ($parts as $part) {
        $part = trim((string)$part);
        if ($part === '') {
            continue;
        }
        $out[] = $part;
    }
    return array_values(array_unique($out));
}

function compactCurrencyLabel(?int $amount): ?string
{
    if ($amount === null || $amount <= 0) {
        return null;
    }
    if ($amount >= 1000000) {
        $millions = $amount / 1000000;
        $formatted = fmod($millions, 1.0) === 0.0
            ? (string)(int)$millions
            : rtrim(rtrim(number_format($millions, 1, '.', ''), '0'), '.');
        return $formatted . ' triệu';
    }
    if ($amount >= 1000) {
        return number_format($amount / 1000, 0, ',', '.') . 'k';
    }
    return number_format($amount, 0, ',', '.') . 'đ';
}

function budgetRangeLabel(?int $minPrice, ?int $maxPrice): string
{
    if ($minPrice && $maxPrice) {
        return compactCurrencyLabel($minPrice) . ' - ' . compactCurrencyLabel($maxPrice);
    }
    if ($minPrice) {
        return 'Từ ' . compactCurrencyLabel($minPrice);
    }
    if ($maxPrice) {
        return 'Tối đa ' . compactCurrencyLabel($maxPrice);
    }
    return 'Chưa chốt ngân sách';
}

function leadFreshnessLabel(?string $createdAt): string
{
    $createdAt = trim((string)$createdAt);
    if ($createdAt === '') {
        return 'Không rõ thời điểm';
    }
    $ts = strtotime($createdAt);
    if ($ts === false) {
        return $createdAt;
    }
    $age = max(0, time() - $ts);
    if ($age < 3600) {
        $minutes = max(1, (int)floor($age / 60));
        return 'Mới ' . $minutes . ' phút';
    }
    if ($age < 86400) {
        $hours = max(1, (int)floor($age / 3600));
        return 'Mới ' . $hours . ' giờ';
    }
    if ($age < 604800) {
        $days = max(1, (int)floor($age / 86400));
        return $days . ' ngày trước';
    }
    return date('d/m H:i', $ts);
}

function tenantDemandContext(array $row): array
{
    $note = (string)(rowFirstValue($row, ['post_note', 'note']) ?? '');

    $postKind = trim((string)(rowFirstValue($row, ['post_kind']) ?? ''));
    if ($postKind === '') {
        $postKind = noteValueByLabel($note, 'Địa chỉ phòng') ? 'roommate' : 'room';
    }

    $district = trim((string)(rowFirstValue($row, ['post_district', 'district']) ?? ''));
    $ward = trim((string)(rowFirstValue($row, ['post_ward', 'ward']) ?? ''));
    $nearPlace = trim((string)(rowFirstValue($row, ['post_near_place', 'near_place']) ?? ''));
    if (($district === '' || $ward === '' || $nearPlace === '') && $note !== '') {
        $detail = noteValueByLabel($note, 'Khu vực chi tiết');
        if ($detail) {
            $parts = array_values(array_filter(array_map('trim', explode('•', $detail))));
            if ($ward === '' && isset($parts[0])) {
                $ward = $parts[0];
            }
            if ($district === '' && isset($parts[1])) {
                $district = $parts[1];
            }
            if ($nearPlace === '' && isset($parts[2])) {
                $nearPlace = $parts[2];
            }
        }
    }

    $roomType = trim((string)(rowFirstValue($row, ['post_room_type', 'room_type']) ?? ''));
    if ($roomType === '') {
        $roomType = trim((string)(noteValueByLabel($note, 'Loại phòng') ?? ''));
    }

    $moveInTime = trim((string)(rowFirstValue($row, ['post_move_in_time', 'move_in_time', 'time_slot']) ?? ''));
    if ($moveInTime === '') {
        $moveInTime = trim((string)(noteValueByLabel($note, 'Thời gian chuyển đến') ?? ''));
    }

    $priority = trim((string)(rowFirstValue($row, ['post_priority', 'priority']) ?? ''));
    if ($priority === '') {
        $priority = trim((string)(noteValueByLabel($note, 'Ưu tiên') ?? ''));
    }

    $sharedOwner = rowFirstValue($row, ['post_shared_owner', 'shared_owner']);
    if ($sharedOwner === null || $sharedOwner === '') {
        $sharedOwner = yesNoValueToFlag(noteValueByLabel($note, 'Chung chủ'));
    } else {
        $sharedOwner = (int)$sharedOwner;
    }

    $closedRoom = rowFirstValue($row, ['post_closed_room', 'closed_room']);
    if ($closedRoom === null || $closedRoom === '') {
        $closedRoom = yesNoValueToFlag(noteValueByLabel($note, 'Khép kín'));
    } else {
        $closedRoom = (int)$closedRoom;
    }

    $amenities = trim((string)(rowFirstValue($row, ['post_amenities', 'amenities']) ?? ''));
    if ($amenities === '') {
        $amenities = trim((string)(noteValueByLabel($note, 'Tiện nghi mong muốn') ?? ''));
    }

    $amenitiesList = trim((string)(rowFirstValue($row, ['post_amenities_list', 'amenities_list']) ?? ''));
    if ($amenitiesList === '') {
        $amenitiesList = trim((string)(noteValueByLabel($note, 'Yêu cầu nội thất') ?? ''));
    }

    $amenityKeywords = array_values(array_unique(array_merge(
        splitLeadKeywords($amenities),
        splitLeadKeywords($amenitiesList)
    )));

    $areaMin = rowFirstValue($row, ['post_area_min', 'area_min']);
    if (($areaMin === null || $areaMin === '') && $note !== '') {
        $parsedAreaMin = noteValueByLabel($note, 'Diện tích tối thiểu');
        if ($parsedAreaMin !== null && preg_match('/\d+/', $parsedAreaMin, $matches)) {
            $areaMin = (int)$matches[0];
        }
    }

    $area = trim((string)(rowFirstValue($row, ['post_area', 'area', 'province']) ?? ''));
    $priceMin = rowFirstValue($row, ['post_price_min', 'price_min', 'min_price']);
    $priceMax = rowFirstValue($row, ['post_price_max', 'price_max', 'max_price']);

    $areaParts = array_values(array_filter([
        $ward,
        $district,
        $nearPlace,
        $area,
    ]));

    return [
        'post_kind' => $postKind === '' ? 'room' : $postKind,
        'area' => $area,
        'district' => $district,
        'ward' => $ward,
        'near_place' => $nearPlace,
        'room_type' => $roomType,
        'move_in_time' => $moveInTime,
        'priority' => $priority,
        'shared_owner' => $sharedOwner === null ? null : (int)$sharedOwner,
        'closed_room' => $closedRoom === null ? null : (int)$closedRoom,
        'amenities' => $amenities,
        'amenities_list' => $amenitiesList,
        'amenities_array' => $amenityKeywords,
        'area_min' => $areaMin !== null && $areaMin !== '' ? (int)$areaMin : null,
        'price_min' => $priceMin !== null && $priceMin !== '' ? (int)$priceMin : null,
        'price_max' => $priceMax !== null && $priceMax !== '' ? (int)$priceMax : null,
        'budget_label' => budgetRangeLabel(
            $priceMin !== null && $priceMin !== '' ? (int)$priceMin : null,
            $priceMax !== null && $priceMax !== '' ? (int)$priceMax : null
        ),
        'area_label' => !empty($areaParts) ? implode(' • ', $areaParts) : ($area !== '' ? $area : 'Chưa rõ khu vực'),
        'note' => $note,
    ];
}

function leadHistoryActionLabel(string $action): string
{
    $map = [
        'created' => 'Lead vào hệ thống',
        'opened' => 'Mới mua',
        'contacted' => 'Đã liên hệ',
        'negotiating' => 'Đang thương lượng',
        'closed' => 'Chốt thành công',
        'invalid' => 'Thất bại',
        'sold' => 'Đã bán',
        'used' => 'Chốt thành công',
        'paid' => 'Đã thanh toán',
    ];
    return $map[$action] ?? ucfirst($action);
}

function appendLeadHistory(int $leadId, string $action, string $note = '', ?int $actorId = null, ?string $actorRole = null): void
{
    if ($leadId <= 0 || trim($action) === '') {
        return;
    }

    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);

    $stmt = $pdo->prepare('INSERT INTO lead_logs (lead_id, action, note, actor_id, actor_role) VALUES (:lead_id, :action, :note, :actor_id, :actor_role)');
    try {
        $stmt->execute([
            ':lead_id' => $leadId,
            ':action' => $action,
            ':note' => trim($note),
            ':actor_id' => $actorId,
            ':actor_role' => $actorRole,
        ]);
    } catch (PDOException $e) {
        // ignore log write failure so it does not block the main flow
    }

    try {
        $pdo->prepare('UPDATE leads SET last_interaction_at = :ts WHERE id = :id')
            ->execute([
                ':ts' => date('Y-m-d H:i:s'),
                ':id' => $leadId,
            ]);
    } catch (PDOException $e) {
        // ignore if the column is unavailable
    }
}

function matchRoomToTenantDemand(array $demand, array $room): array
{
    $score = 12;
    $reasons = [];

    $roomPrice = (int)($room['price'] ?? $room['room_price'] ?? 0);
    $priceMin = isset($demand['price_min']) && $demand['price_min'] !== null ? (int)$demand['price_min'] : null;
    $priceMax = isset($demand['price_max']) && $demand['price_max'] !== null ? (int)$demand['price_max'] : null;
    if ($roomPrice > 0) {
        if ($priceMin !== null && $priceMax !== null) {
            if ($roomPrice >= $priceMin && $roomPrice <= $priceMax) {
                $score += 30;
                $reasons[] = 'dung ngan sach';
            } elseif ($roomPrice >= (int)round($priceMin * 0.9) && $roomPrice <= (int)round($priceMax * 1.12)) {
                $score += 20;
                $reasons[] = 'gan ngan sach';
            } else {
                $score += 6;
            }
        } elseif ($priceMin !== null && $roomPrice >= $priceMin) {
            $score += 18;
            $reasons[] = 'dat muc toi thieu';
        } elseif ($priceMax !== null && $roomPrice <= $priceMax) {
            $score += 18;
            $reasons[] = 'khong vuot ngan sach';
        }
    }

    $roomText = normalizeLeadSearchText(
        (string)($room['title'] ?? $room['room_title'] ?? '') . ' ' .
        (string)($room['description'] ?? '') . ' ' .
        (string)($room['area'] ?? $room['room_area'] ?? '') . ' ' .
        (string)($room['address'] ?? $room['room_address'] ?? '')
    );
    foreach ([
        'area' => 10,
        'district' => 10,
        'ward' => 7,
        'near_place' => 5,
    ] as $field => $points) {
        $keyword = normalizeLeadSearchText((string)($demand[$field] ?? ''));
        if ($keyword !== '' && strpos($roomText, $keyword) !== false) {
            $score += $points;
            $reasons[] = 'hop khu vuc';
        }
    }

    $roomType = normalizeLeadSearchText((string)($demand['room_type'] ?? ''));
    if ($roomType !== '' && strpos($roomText, $roomType) !== false) {
        $score += 8;
        $reasons[] = 'dung loai phong';
    }

    $sharedOwnerDemand = $demand['shared_owner'] ?? null;
    if ($sharedOwnerDemand !== null) {
        if ((int)$sharedOwnerDemand === (int)($room['shared_owner'] ?? 0)) {
            $score += 6;
            $reasons[] = 'dung yeu cau chung chu';
        } elseif ((int)$sharedOwnerDemand === 0) {
            $score += 1;
        }
    }

    $closedRoomDemand = $demand['closed_room'] ?? null;
    if ($closedRoomDemand !== null) {
        if ((int)$closedRoomDemand === (int)($room['closed_room'] ?? 0)) {
            $score += 6;
            $reasons[] = 'dung yeu cau khep kin';
        } elseif ((int)$closedRoomDemand === 0) {
            $score += 1;
        }
    }

    $amenityMatches = 0;
    foreach (($demand['amenities_array'] ?? []) as $amenity) {
        $keyword = normalizeLeadSearchText($amenity);
        if ($keyword !== '' && strpos($roomText, $keyword) !== false) {
            $amenityMatches++;
        }
    }
    if ($amenityMatches > 0) {
        $score += min(10, $amenityMatches * 3);
        $reasons[] = 'khop tien nghi';
    }

    $priority = normalizeLeadSearchText((string)($demand['priority'] ?? ''));
    if ($priority !== '') {
        if (strpos($priority, 'gia re') !== false || strpos($priority, 'giá rẻ') !== false) {
            if ($priceMax !== null && $roomPrice > 0 && $roomPrice <= $priceMax) {
                $score += 4;
            }
        } elseif (strpos($priority, 'noi that') !== false || strpos($priority, 'nội thất') !== false) {
            if ($amenityMatches > 0) {
                $score += 4;
            }
        } elseif (strpos($priority, 'gan truong') !== false || strpos($priority, 'gần trường') !== false
            || strpos($priority, 'gan cong ty') !== false || strpos($priority, 'gần công ty') !== false) {
            if (!empty($demand['near_place']) && strpos($roomText, normalizeLeadSearchText((string)$demand['near_place'])) !== false) {
                $score += 4;
            }
        }
    }

    $occupancyStatus = (string)($room['occupancy_status'] ?? 'vacant');
    if ($occupancyStatus === 'vacant') {
        $score += 8;
        $reasons[] = 'phong dang trong';
    } elseif ($occupancyStatus === 'reserved') {
        $score += 5;
    } elseif ($occupancyStatus === 'occupied') {
        $score += 1;
    }

    $score = max(8, min(99, $score));
    return [
        'match_percent' => $score,
        'match_score' => (int)round($score / 10),
        'match_label' => $score . '%',
        'reasons' => array_values(array_unique($reasons)),
    ];
}

function attachLeadPresentation(array $rows): array
{
    foreach ($rows as &$row) {
        $context = tenantDemandContext($row);
        $source = trim((string)($row['source'] ?? ''));
        if ($source === '') {
            $source = !empty($row['tenant_post_id']) ? 'marketplace' : 'direct';
        }
        $row['source'] = $source;
        $row['lead_source_label'] = $source === 'marketplace' ? 'Lead marketplace' : 'Quan tam truc tiep';
        $row['lead_area_label'] = $context['area_label'];
        $row['lead_budget_label'] = $context['budget_label'];
        $row['lead_room_type_label'] = $context['room_type'] !== '' ? $context['room_type'] : 'Chua ro loai phong';
        $row['lead_move_in_label'] = $context['move_in_time'] !== '' ? $context['move_in_time'] : 'Chua chot thoi gian';
        $row['lead_priority_label'] = $context['priority'] !== '' ? $context['priority'] : '';
        $row['lead_freshness_label'] = leadFreshnessLabel((string)($row['created_at'] ?? ''));
        $row['lead_amenities_preview'] = !empty($context['amenities_array'])
            ? implode(', ', array_slice($context['amenities_array'], 0, 3))
            : '';
        $row['lead_summary_note'] = mb_substr(trim(preg_replace('/\s+/u', ' ', $context['note'])), 0, 180, 'UTF-8');
    }
    unset($row);
    return $rows;
}

function attachMatchScore(array $leads): array
{
    foreach ($leads as &$l) {
        $demand = tenantDemandContext($l);
        $match = matchRoomToTenantDemand($demand, [
            'id' => $l['room_id'] ?? null,
            'title' => $l['room_title'] ?? '',
            'description' => $l['room_description'] ?? $l['description'] ?? '',
            'price' => $l['room_price'] ?? $l['price'] ?? 0,
            'area' => $l['room_area'] ?? '',
            'address' => $l['room_address'] ?? '',
            'shared_owner' => $l['shared_owner'] ?? 0,
            'closed_room' => $l['closed_room'] ?? 0,
            'occupancy_status' => $l['occupancy_status'] ?? 'vacant',
        ]);

        $vip = landlordVipTier((int)($l['landlord_id'] ?? 0));
        $vipWeight = (int)($vip['weight'] ?? 0);
        $roomTitle = trim((string)($l['room_title'] ?? 'phong cua ban'));

        $l['match_percent'] = $match['match_percent'];
        $l['match_score'] = $match['match_score'];
        $l['vip_weight'] = $vipWeight;
        $l['match_label'] = $match['match_label'];
        $l['match_reasons'] = $match['reasons'];
        $l['match_suggestion'] = 'Lead nay hop ' . $match['match_percent'] . '% voi ' . $roomTitle;
    }
    unset($l);
    return attachLeadPresentation($leads);
}

function remindLead(int $leadId, int $landlordId): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT l.id, l.reminded_at, r.landlord_id FROM leads l JOIN rooms r ON r.id = l.room_id WHERE l.id = :id');
    $stmt->execute([':id' => $leadId]);
    $row = $stmt->fetch();
    if (!$row || (int)$row['landlord_id'] !== $landlordId) return false;

    // chỉ VIP2 trở lên mới được nhắc
    $vip = landlordVipTier($landlordId);
    if (($vip['weight'] ?? 0) < 2) return false;

    // giới hạn 1 lần mỗi 24h
    if (!empty($row['reminded_at']) && (time() - strtotime($row['reminded_at'])) < 86400) {
        return false;
    }

    $pdo->prepare('UPDATE leads SET reminded_at = :t WHERE id = :id')
        ->execute([':t' => date('Y-m-d H:i:s'), ':id' => $leadId]);

    // TODO: gửi thông báo thực tế (email/sms/push); hiện chỉ lưu dấu thời gian.
    return true;
}

// Tenant posts (tìm phòng / ở ghép)
function createTenantPost(int $userId, string $area, ?int $priceMin, ?int $priceMax, ?int $peopleCount, string $note, string $gender = 'any', string $roomImage1 = '', string $roomImage2 = '', string $roomImage3 = '', array $meta = []): int
{
    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    // Đăng mới ở trạng thái pending để admin duyệt
    $stmt = $pdo->prepare('INSERT INTO tenant_posts (
            user_id, area, price_min, price_max, people_count, note, gender, room_image, room_image2, room_image3,
            post_kind, district, ward, near_place, room_type, move_in_time, area_min, priority, shared_owner, closed_room, amenities, amenities_list, status
        ) VALUES (
            :u,:a,:pmin,:pmax,:pc,:note,:gender,:room_img1,:room_img2,:room_img3,
            :post_kind,:district,:ward,:near_place,:room_type,:move_in_time,:area_min,:priority,:shared_owner,:closed_room,:amenities,:amenities_list,"pending"
        )');
    $stmt->execute([
        ':u' => $userId,
        ':a' => $area,
        ':pmin' => $priceMin,
        ':pmax' => $priceMax,
        ':pc' => $peopleCount,
        ':note' => $note,
        ':gender' => $gender,
        ':room_img1' => $roomImage1,
        ':room_img2' => $roomImage2,
        ':room_img3' => $roomImage3,
        ':post_kind' => trim((string)($meta['post_kind'] ?? 'room')),
        ':district' => trim((string)($meta['district'] ?? '')),
        ':ward' => trim((string)($meta['ward'] ?? '')),
        ':near_place' => trim((string)($meta['near_place'] ?? '')),
        ':room_type' => trim((string)($meta['room_type'] ?? '')),
        ':move_in_time' => trim((string)($meta['move_in_time'] ?? '')),
        ':area_min' => isset($meta['area_min']) && $meta['area_min'] !== '' ? max(0, (int)$meta['area_min']) : null,
        ':priority' => trim((string)($meta['priority'] ?? '')),
        ':shared_owner' => isset($meta['shared_owner']) ? (int)!empty($meta['shared_owner']) : null,
        ':closed_room' => isset($meta['closed_room']) ? (int)!empty($meta['closed_room']) : null,
        ':amenities' => trim((string)($meta['amenities'] ?? '')),
        ':amenities_list' => trim((string)($meta['amenities_list'] ?? '')),
    ]);
    return (int)$pdo->lastInsertId();
}

function fetchTenantPosts(): array
{
    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    $stmt = $pdo->query('SELECT tp.*, u.name AS user_name, u.phone AS user_phone FROM tenant_posts tp JOIN users u ON u.id = tp.user_id WHERE tp.status = "active" ORDER BY tp.created_at DESC');
    $rows = $stmt->fetchAll();
    return array_map(static function (array $row): array {
        return array_merge($row, tenantDemandContext($row));
    }, $rows);
}

function adminFetchTenantPosts(string $status = 'pending'): array
{
    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    $stmt = $pdo->prepare('SELECT tp.*, u.name AS user_name, u.phone AS user_phone FROM tenant_posts tp JOIN users u ON u.id = tp.user_id WHERE tp.status = :s ORDER BY tp.created_at DESC');
    $stmt->execute([':s' => $status]);
    $rows = $stmt->fetchAll();
    return array_map(static function (array $row): array {
        return array_merge($row, tenantDemandContext($row));
    }, $rows);
}

function adminSetTenantPostStatus(int $id, string $status): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE tenant_posts SET status = :s WHERE id = :id');
    $stmt->execute([':s' => $status, ':id' => $id]);
    return $stmt->rowCount() > 0;
}

// CTA messages
function saveCtaMessage(string $name, string $phone, string $email, string $province, string $msg): int
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO cta_messages (name, phone, email, province, message) VALUES (:n,:p,:e,:pr,:m)');
    $stmt->execute([
        ':n' => $name,
        ':p' => $phone,
        ':e' => $email,
        ':pr' => $province,
        ':m' => $msg,
    ]);
    return (int)$pdo->lastInsertId();
}

function fetchCtaMessages(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM cta_messages ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

// Messaging
function findAnyAdminId(): ?int
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id FROM users WHERE role = "admin" LIMIT 1');
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

function createMessage(int $senderId, ?int $receiverId, string $content, bool $isBroadcast = false): int
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, is_broadcast, content, message) VALUES (:s,:r,:b,:c,:c)');
    $stmt->execute([
        ':s' => $senderId,
        ':r' => $receiverId,
        ':b' => $isBroadcast ? 1 : 0,
        ':c' => $content,
    ]);
    return (int)$pdo->lastInsertId();
}

function fetchMessagesForUser(int $userId): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT m.*, u.name AS sender_name, COALESCE(m.content, m.message, "") AS content_effective FROM messages m JOIN users u ON u.id = m.sender_id WHERE (m.receiver_id = :uid OR m.sender_id = :uid OR m.is_broadcast = 1) ORDER BY m.created_at DESC, m.id DESC');
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}

function fetchMessagesForAdmin(?int $userId = null): array
{
    $pdo = getPDO();
    $sql = 'SELECT m.*, u.name AS sender_name, r.name AS receiver_name, COALESCE(m.content, m.message, "") AS content_effective FROM messages m JOIN users u ON u.id = m.sender_id LEFT JOIN users r ON r.id = m.receiver_id';
    if ($userId === null) {
        $sql .= ' ORDER BY m.created_at DESC, m.id DESC';
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }
    $sql .= ' WHERE m.receiver_id = :uid OR m.sender_id = :uid OR m.is_broadcast = 1 ORDER BY m.created_at DESC, m.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}

function fetchMessageUsers(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT DISTINCT
            u.id,
            u.name,
            u.avatar,
            u.role,
            (
                SELECT COALESCE(m2.content, m2.message, "")
                FROM messages m2
                WHERE m2.sender_id = u.id OR m2.receiver_id = u.id
                ORDER BY m2.created_at DESC, m2.id DESC
                LIMIT 1
            ) AS latest_message,
            (
                SELECT m3.created_at
                FROM messages m3
                WHERE m3.sender_id = u.id OR m3.receiver_id = u.id
                ORDER BY m3.created_at DESC, m3.id DESC
                LIMIT 1
            ) AS latest_at
        FROM users u
        WHERE u.id IN (
            SELECT sender_id FROM messages
            UNION
            SELECT receiver_id FROM messages WHERE receiver_id IS NOT NULL
        )
        ORDER BY latest_at DESC, u.name');
    return $stmt->fetchAll();
}

function updateUserAvatar(int $userId, string $path): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE users SET avatar = :a WHERE id = :id');
    $stmt->execute([':a' => $path, ':id' => $userId]);
    return $stmt->rowCount() > 0;
}

function updateUserProfile(int $userId, array $data): bool
{
    $pdo = getPDO();
    $sql = 'UPDATE users SET name = :name, hometown = :home, birthdate = :birth';
    $params = [
        ':name' => $data['name'],
        ':home' => $data['hometown'],
        ':birth' => $data['birthdate'],
        ':id' => $userId,
    ];
    if (!empty($data['phone'])) {
        $sql .= ', phone = :phone';
        $params[':phone'] = $data['phone'];
    }
    $sql .= ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount() > 0;
}

function findUserByPhone(string $phone): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE phone = :phone LIMIT 1');
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function createUser(string $name, string $phone, string $passwordHash, string $role = 'tenant'): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO users (name, phone, password, role) VALUES (:name, :phone, :password, :role)');
    try {
        $stmt->execute([
            ':name' => $name,
            ':phone' => $phone,
            ':password' => $passwordHash,
            ':role' => $role,
        ]);
    } catch (PDOException $e) {
        return null; // likely duplicate phone
    }
    return [
        'id' => (int)$pdo->lastInsertId(),
        'name' => $name,
        'phone' => $phone,
        'role' => $role,
    ];
}

function fetchRooms(array $filters = []): array
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $sql = 'SELECT * FROM rooms WHERE status = "active" AND deleted_at IS NULL';
    $params = [];

    if (!empty($filters['keyword'])) {
        $sql .= ' AND (title LIKE :kw OR area LIKE :kw OR address LIKE :kw)';
        $params[':kw'] = '%' . $filters['keyword'] . '%';
    }

    if (!empty($filters['area'])) {
        $sql .= ' AND (area LIKE :area OR address LIKE :area)';
        $params[':area'] = '%' . $filters['area'] . '%';
    }
    if (!empty($filters['district'])) {
        $sql .= ' AND (area LIKE :district OR address LIKE :district)';
        $params[':district'] = '%' . $filters['district'] . '%';
    }
    if (!empty($filters['ward'])) {
        $sql .= ' AND address LIKE :ward';
        $params[':ward'] = '%' . $filters['ward'] . '%';
    }
    if (!empty($filters['province'])) {
        $sql .= ' AND area LIKE :province';
        $params[':province'] = '%' . $filters['province'] . '%';
    }

    if (!empty($filters['min_price'])) {
        $sql .= ' AND price >= :min_price';
        $params[':min_price'] = (int)$filters['min_price'];
    }

    if (!empty($filters['max_price'])) {
        $sql .= ' AND price <= :max_price';
        $params[':max_price'] = (int)$filters['max_price'];
    }

    if (!empty($filters['min_electric_price'])) {
        $sql .= ' AND (electric_price IS NOT NULL AND electric_price >= :min_electric)';
        $params[':min_electric'] = (int)$filters['min_electric_price'];
    }
    if (!empty($filters['max_electric_price'])) {
        $sql .= ' AND (electric_price IS NOT NULL AND electric_price <= :max_electric)';
        $params[':max_electric'] = (int)$filters['max_electric_price'];
    }
    if (!empty($filters['min_water_price'])) {
        $sql .= ' AND (water_price IS NOT NULL AND water_price >= :min_water)';
        $params[':min_water'] = (int)$filters['min_water_price'];
    }
    if (!empty($filters['max_water_price'])) {
        $sql .= ' AND (water_price IS NOT NULL AND water_price <= :max_water)';
        $params[':max_water'] = (int)$filters['max_water_price'];
    }

    if (isset($filters['shared_owner']) && ($filters['shared_owner'] === '0' || $filters['shared_owner'] === '1')) {
        $sql .= ' AND shared_owner = :shared_owner';
        $params[':shared_owner'] = (int)$filters['shared_owner'];
    }
    if (isset($filters['closed_room']) && ($filters['closed_room'] === '0' || $filters['closed_room'] === '1')) {
        $sql .= ' AND closed_room = :closed_room';
        $params[':closed_room'] = (int)$filters['closed_room'];
    }

    if (!empty($filters['near_school'])) {
        $schoolPatterns = ['đh', 'đại học', 'trường', 'university', 'college', 'campus'];
        $clauses = [];
        foreach ($schoolPatterns as $idx => $pat) {
            $key = ':school' . $idx;
            $clauses[] = "(title LIKE $key OR description LIKE $key OR address LIKE $key)";
            $params[$key] = '%' . $pat . '%';
        }
        if (!empty($clauses)) {
            $sql .= ' AND (' . implode(' OR ', $clauses) . ')';
        }
    }

    $sql .= ' ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $rooms = $stmt->fetchAll();

    // Ưu tiên hiển thị theo VIP (tính ở PHP để tránh query phức tạp)
    $vipCache = [];
    foreach ($rooms as &$room) {
        $lid = (int)($room['landlord_id'] ?? 0);
        if (!isset($vipCache[$lid])) {
            $vip = landlordVipTier($lid);
            $vipCache[$lid] = ['weight' => $vip['weight'] ?? 0, 'tier' => $vip['tier'] ?? 'Thường'];
        }
        $room['vip_weight'] = $vipCache[$lid]['weight'];
        $room['vip_tier'] = $vipCache[$lid]['tier'];
    }
    unset($room);

    usort($rooms, function ($a, $b) {
        $wa = $a['vip_weight'] ?? 0;
        $wb = $b['vip_weight'] ?? 0;
        $ba = !empty($a['boost_until']) && strtotime($a['boost_until']) > time() ? 1 : 0;
        $bb = !empty($b['boost_until']) && strtotime($b['boost_until']) > time() ? 1 : 0;
        if ($ba !== $bb) return $bb <=> $ba; // boost ưu tiên trước
        if ($wa === $wb) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        }
        return $wb <=> $wa;
    });

    return $rooms;
}

function fetchRoom(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('SELECT rooms.*, users.name AS landlord_name, users.phone AS landlord_phone FROM rooms JOIN users ON users.id = rooms.landlord_id WHERE rooms.id = :id AND rooms.deleted_at IS NULL');
    $stmt->execute([':id' => $id]);
    $room = $stmt->fetch();

    if (!$room) return null;

    $room['leads_recent'] = countRoomLeadsRecent($id);
    $quota = landlordLeadQuotaRemaining((int)$room['landlord_id'], 3);
    $room['slots_left'] = $quota['left'];
    $room['slots_total'] = $quota['total'];

    return $room;
}

function fetchSimilarRooms(int $excludeRoomId, string $area = '', string $district = '', int $limit = 6): array
{
    $filters = [];
    if ($district !== '') {
        $filters['district'] = $district;
    } elseif ($area !== '') {
        $filters['area'] = $area;
    }

    // Ưu tiên kết quả cùng khu vực, fallback lấy tất cả phòng active
    $rooms = fetchRooms($filters);
    if (empty($rooms)) {
        $rooms = fetchRooms([]);
    }
    $rooms = array_values(array_filter($rooms, static function ($r) use ($excludeRoomId) {
        return (int)($r['id'] ?? 0) !== $excludeRoomId;
    }));
    return array_slice($rooms, 0, $limit);
}

function createLead(int $tenantId, string $name, string $phone, int $roomId, array $options = []): int
{
    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    // Dedup: 1 tài khoản / 1 số điện thoại chỉ được quan tâm 1 lần cho cùng phòng
    $existing = findExistingLead($roomId, $tenantId, $phone);
    if ($existing) return $existing;

    $stmt = $pdo->prepare('INSERT INTO leads (room_id, tenant_id, tenant_name, tenant_phone, status, min_price, max_price, province, district, ward, time_slot, source) VALUES (:room, :tenant, :name, :phone, "new", :minp, :maxp, :prov, :dist, :ward, :slot, :source)');
    $stmt->execute([
        ':room' => $roomId,
        ':tenant' => $tenantId ?: null,
        ':name' => $name,
        ':phone' => $phone,
        ':minp' => $options['min_price'] ?? null,
        ':maxp' => $options['max_price'] ?? null,
        ':prov' => $options['province'] ?? null,
        ':dist' => $options['district'] ?? null,
        ':ward' => $options['ward'] ?? null,
        ':slot' => $options['time_slot'] ?? null,
        ':source' => trim((string)($options['source'] ?? 'direct')) ?: 'direct',
    ]);

    $leadId = (int)$pdo->lastInsertId();
    appendLeadHistory($leadId, 'created', 'Tenant tao lead quan tam truc tiep', $tenantId ?: null, 'tenant');
    notifyLandlordByRoom(
        $roomId,
        'lead_interest',
        'Có lead mới quan tâm phòng',
        'Lead #' . $leadId . ' vừa được tạo và đang chờ bạn xử lý.',
        '?route=dashboard&tab=lead#lead',
        $leadId
    );
    // Fire-and-forget: lỗi push không được làm hỏng luồng tạo lead.
    try {
        notifyLandlordLeadPush($roomId, $leadId);
    } catch (Throwable $e) {
        error_log('Lead push notify failed: ' . $e->getMessage());
    }

    return $leadId;
}

function pushBase64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function pushWebAudience(string $endpoint): ?string
{
    $parts = parse_url($endpoint);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
        return null;
    }

    $aud = strtolower((string)$parts['scheme']) . '://' . strtolower((string)$parts['host']);
    if (!empty($parts['port'])) {
        $aud .= ':' . (int)$parts['port'];
    }
    return $aud;
}

function pushResolvePath(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('~^(?:[a-zA-Z]:[\\\\/]|/)~', $path) === 1) {
        return $path;
    }
    return dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $path), '/');
}

function pushVapidPublicKey(): string
{
    return trim((string)env('WEB_PUSH_VAPID_PUBLIC_KEY', ''));
}

function pushVapidPrivateKeyPem(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $inline = trim((string)env('WEB_PUSH_VAPID_PRIVATE_KEY', ''));
    if ($inline !== '') {
        $cached = str_replace('\n', "\n", $inline);
        return $cached;
    }

    $path = pushResolvePath((string)env('WEB_PUSH_VAPID_PRIVATE_KEY_PATH', ''));
    if ($path === '' || !is_file($path)) {
        $cached = '';
        return $cached;
    }

    $pem = trim((string)@file_get_contents($path));
    $cached = $pem ?: '';
    return $cached;
}

function pushVapidSubject(): string
{
    $subject = trim((string)env('WEB_PUSH_VAPID_SUBJECT', ''));
    if ($subject === '') {
        return 'mailto:no-reply@phongtro.local';
    }
    return $subject;
}

function isWebPushConfigured(): bool
{
    if (!function_exists('openssl_sign')) {
        return false;
    }
    return pushVapidPublicKey() !== '' && pushVapidPrivateKeyPem() !== '';
}

function ensurePushSubscriptionsTable(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = getPDO();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            endpoint_hash CHAR(64) NOT NULL,
            endpoint TEXT NOT NULL,
            p256dh VARCHAR(255) NOT NULL,
            auth VARCHAR(255) NOT NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_push_endpoint_hash (endpoint_hash),
            KEY idx_push_user_id (user_id),
            CONSTRAINT fk_push_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN endpoint_hash CHAR(64) NOT NULL DEFAULT '' AFTER user_id"); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN endpoint TEXT NOT NULL AFTER endpoint_hash"); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN p256dh VARCHAR(255) NOT NULL AFTER endpoint"); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN auth VARCHAR(255) NOT NULL AFTER p256dh"); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN user_agent VARCHAR(255) NULL AFTER auth"); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec("UPDATE push_subscriptions SET endpoint_hash = SHA2(endpoint, 256) WHERE endpoint_hash = '' AND endpoint <> ''"); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec("CREATE UNIQUE INDEX uniq_push_endpoint_hash ON push_subscriptions(endpoint_hash)"); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec("CREATE INDEX idx_push_user_id ON push_subscriptions(user_id)"); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec("ALTER TABLE push_subscriptions ADD CONSTRAINT fk_push_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE"); } catch (PDOException $e) { /* ignore */ }
    } else {
        $pdo->exec('CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            endpoint_hash TEXT NOT NULL UNIQUE,
            endpoint TEXT NOT NULL,
            p256dh TEXT NOT NULL,
            auth TEXT NOT NULL,
            user_agent TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        );');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_push_user_id ON push_subscriptions(user_id);');
        $cols = $pdo->query('PRAGMA table_info(push_subscriptions)')->fetchAll();
        $names = array_column($cols, 'name');
        if (!in_array('endpoint_hash', $names, true)) {
            $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN endpoint_hash TEXT NOT NULL DEFAULT ''");
            $rows = $pdo->query("SELECT id, endpoint FROM push_subscriptions WHERE endpoint_hash = '' OR endpoint_hash IS NULL")->fetchAll();
            if (!empty($rows)) {
                $upd = $pdo->prepare('UPDATE push_subscriptions SET endpoint_hash = :h WHERE id = :id');
                foreach ($rows as $row) {
                    $endpoint = (string)($row['endpoint'] ?? '');
                    if ($endpoint === '') {
                        continue;
                    }
                    $upd->execute([
                        ':h' => hash('sha256', $endpoint),
                        ':id' => (int)$row['id'],
                    ]);
                }
            }
            $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS uniq_push_endpoint_hash ON push_subscriptions(endpoint_hash);');
        }
        if (!in_array('updated_at', $names, true)) {
            $pdo->exec("ALTER TABLE push_subscriptions ADD COLUMN updated_at TEXT DEFAULT CURRENT_TIMESTAMP");
        }
    }

    $ready = true;
}

function normalizePushSubscriptionPayload(array $payload): ?array
{
    $endpoint = trim((string)($payload['endpoint'] ?? ''));
    $keys = is_array($payload['keys'] ?? null) ? $payload['keys'] : [];
    $p256dh = trim((string)($keys['p256dh'] ?? ''));
    $auth = trim((string)($keys['auth'] ?? ''));
    if ($endpoint === '' || $p256dh === '' || $auth === '') {
        return null;
    }
    if (strlen($endpoint) > 4096 || strlen($p256dh) > 1024 || strlen($auth) > 1024) {
        return null;
    }

    return [
        'endpoint' => $endpoint,
        'p256dh' => $p256dh,
        'auth' => $auth,
    ];
}

function savePushSubscription(int $userId, array $payload, string $userAgent = ''): bool
{
    ensurePushSubscriptionsTable();
    $subscription = normalizePushSubscriptionPayload($payload);
    if (!$subscription) {
        return false;
    }

    $endpointHash = hash('sha256', $subscription['endpoint']);
    $pdo = getPDO();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $stmt = $pdo->prepare('INSERT INTO push_subscriptions (user_id, endpoint_hash, endpoint, p256dh, auth, user_agent)
                               VALUES (:user_id, :endpoint_hash, :endpoint, :p256dh, :auth, :user_agent)
                               ON DUPLICATE KEY UPDATE
                                    user_id = VALUES(user_id),
                                    endpoint = VALUES(endpoint),
                                    p256dh = VALUES(p256dh),
                                    auth = VALUES(auth),
                                    user_agent = VALUES(user_agent),
                                    updated_at = CURRENT_TIMESTAMP');
    } else {
        $stmt = $pdo->prepare('INSERT INTO push_subscriptions (user_id, endpoint_hash, endpoint, p256dh, auth, user_agent, updated_at)
                               VALUES (:user_id, :endpoint_hash, :endpoint, :p256dh, :auth, :user_agent, CURRENT_TIMESTAMP)
                               ON CONFLICT(endpoint_hash) DO UPDATE SET
                                   user_id = excluded.user_id,
                                   endpoint = excluded.endpoint,
                                   p256dh = excluded.p256dh,
                                   auth = excluded.auth,
                                   user_agent = excluded.user_agent,
                                   updated_at = CURRENT_TIMESTAMP');
    }
    $stmt->execute([
        ':user_id' => $userId,
        ':endpoint_hash' => $endpointHash,
        ':endpoint' => $subscription['endpoint'],
        ':p256dh' => $subscription['p256dh'],
        ':auth' => $subscription['auth'],
        ':user_agent' => substr($userAgent, 0, 255),
    ]);

    return true;
}

function deletePushSubscription(int $userId, array $payload): void
{
    ensurePushSubscriptionsTable();
    $endpoint = trim((string)($payload['endpoint'] ?? ''));
    if ($endpoint === '') {
        return;
    }
    $endpointHash = hash('sha256', $endpoint);
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM push_subscriptions WHERE user_id = :user_id AND endpoint_hash = :endpoint_hash');
    $stmt->execute([
        ':user_id' => $userId,
        ':endpoint_hash' => $endpointHash,
    ]);
}

function pushDerLength(string $data, int &$offset): ?int
{
    if (!isset($data[$offset])) {
        return null;
    }
    $length = ord($data[$offset]);
    $offset++;
    if (($length & 0x80) === 0) {
        return $length;
    }
    $bytesCount = $length & 0x7F;
    if ($bytesCount < 1 || $bytesCount > 4) {
        return null;
    }
    $length = 0;
    for ($i = 0; $i < $bytesCount; $i++) {
        if (!isset($data[$offset])) {
            return null;
        }
        $length = ($length << 8) | ord($data[$offset]);
        $offset++;
    }
    return $length;
}

function pushDerToJose(string $der, int $partLength = 32): ?string
{
    $offset = 0;
    if (!isset($der[$offset]) || ord($der[$offset]) !== 0x30) {
        return null;
    }
    $offset++;
    $sequenceLength = pushDerLength($der, $offset);
    if ($sequenceLength === null) {
        return null;
    }

    if (!isset($der[$offset]) || ord($der[$offset]) !== 0x02) {
        return null;
    }
    $offset++;
    $rLength = pushDerLength($der, $offset);
    if ($rLength === null) {
        return null;
    }
    $r = substr($der, $offset, $rLength);
    $offset += $rLength;

    if (!isset($der[$offset]) || ord($der[$offset]) !== 0x02) {
        return null;
    }
    $offset++;
    $sLength = pushDerLength($der, $offset);
    if ($sLength === null) {
        return null;
    }
    $s = substr($der, $offset, $sLength);

    $r = ltrim($r, "\x00");
    $s = ltrim($s, "\x00");
    if (strlen($r) > $partLength || strlen($s) > $partLength) {
        return null;
    }
    $r = str_pad($r, $partLength, "\x00", STR_PAD_LEFT);
    $s = str_pad($s, $partLength, "\x00", STR_PAD_LEFT);
    return $r . $s;
}

function buildVapidJwt(string $audience): ?string
{
    $header = pushBase64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $payload = pushBase64UrlEncode(json_encode([
        'aud' => $audience,
        'exp' => time() + 12 * 60 * 60,
        'sub' => pushVapidSubject(),
    ]));
    $unsigned = $header . '.' . $payload;

    $privateKey = openssl_pkey_get_private(pushVapidPrivateKeyPem());
    if (!$privateKey) {
        return null;
    }

    $signatureDer = '';
    $signed = openssl_sign($unsigned, $signatureDer, $privateKey, OPENSSL_ALGO_SHA256);
    if (is_resource($privateKey)) {
        openssl_free_key($privateKey);
    }
    if (!$signed) {
        return null;
    }

    $signatureJose = pushDerToJose($signatureDer);
    if ($signatureJose === null) {
        return null;
    }

    return $unsigned . '.' . pushBase64UrlEncode($signatureJose);
}

function sendPushSignal(string $endpoint, int $leadId): string
{
    $audience = pushWebAudience($endpoint);
    if ($audience === null) {
        return 'invalid';
    }

    $jwt = buildVapidJwt($audience);
    if ($jwt === null) {
        return 'error';
    }

    $topic = 'lead-' . max(1, $leadId);
    $headers = [
        'TTL: 60',
        'Urgency: high',
        'Topic: ' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $topic),
        'Authorization: WebPush ' . $jwt,
        'Crypto-Key: p256ecdsa=' . pushVapidPublicKey(),
    ];

    if (!function_exists('curl_init')) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => '',
                'timeout' => 8,
                'ignore_errors' => true,
            ]
        ]);
        $result = @file_get_contents($endpoint, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        if (preg_match('/\s(\d{3})\s/', $statusLine, $matches)) {
            $status = (int)$matches[1];
            if ($status === 404 || $status === 410) {
                return 'gone';
            }
            return ($status >= 200 && $status < 300) ? 'ok' : 'error';
        }
        return $result === false ? 'error' : 'ok';
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '',
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8,
    ]);
    curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlError = curl_errno($ch);
    curl_close($ch);

    if ($status === 404 || $status === 410) {
        return 'gone';
    }
    if ($curlError !== 0) {
        return 'error';
    }
    return ($status >= 200 && $status < 300) ? 'ok' : 'error';
}

function notifyLandlordLeadPush(int $roomId, int $leadId): void
{
    if ($roomId <= 0 || $leadId <= 0 || !isWebPushConfigured()) {
        return;
    }

    ensurePushSubscriptionsTable();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT landlord_id FROM rooms WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $roomId]);
    $landlordId = (int)$stmt->fetchColumn();
    if ($landlordId <= 0) {
        return;
    }

    $subsStmt = $pdo->prepare('SELECT id, endpoint FROM push_subscriptions WHERE user_id = :uid');
    $subsStmt->execute([':uid' => $landlordId]);
    $subscriptions = $subsStmt->fetchAll();
    if (empty($subscriptions)) {
        return;
    }

    $deleteStmt = $pdo->prepare('DELETE FROM push_subscriptions WHERE id = :id');
    foreach ($subscriptions as $subscription) {
        $endpoint = trim((string)($subscription['endpoint'] ?? ''));
        if ($endpoint === '') {
            continue;
        }
        $result = sendPushSignal($endpoint, $leadId);
        if ($result === 'gone') {
            $deleteStmt->execute([':id' => (int)$subscription['id']]);
        }
    }
}

function findExistingLead(int $roomId, ?int $tenantId, string $phone): ?int
{
    $pdo = getPDO();
    $sql = 'SELECT id FROM leads WHERE room_id = :room AND (tenant_id = :tenant OR tenant_phone = :phone) ORDER BY id DESC LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':room' => $roomId,
        ':tenant' => $tenantId ?: 0,
        ':phone' => $phone,
    ]);
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

function countLeadsTodayByTenant(int $tenantId, string $phone): int
{
    $pdo = getPDO();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $sql = 'SELECT COUNT(*) FROM leads WHERE (tenant_id = :t OR tenant_phone = :p) AND created_at >= CURDATE()';
    } else {
        $sql = 'SELECT COUNT(*) FROM leads WHERE (tenant_id = :t OR tenant_phone = :p) AND created_at >= date("now","start of day")';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':t' => $tenantId, ':p' => $phone]);
    return (int)$stmt->fetchColumn();
}

function lastLeadCreatedAt(int $tenantId, string $phone): ?string
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT created_at FROM leads WHERE tenant_id = :t OR tenant_phone = :p ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([':t' => $tenantId, ':p' => $phone]);
    $ts = $stmt->fetchColumn();
    return $ts ?: null;
}

function leadPurchasedStatuses(): array
{
    return ['opened', 'contacted', 'negotiating', 'closed', 'sold', 'used', 'paid'];
}

function leadClosedStatuses(): array
{
    return ['closed', 'used'];
}

function isLeadPurchasedStatus(string $status): bool
{
    return in_array($status, leadPurchasedStatuses(), true);
}

function leadHasUnlockedContact(array $lead): bool
{
    $purchaseStatus = strtolower(trim((string)($lead['purchase_status'] ?? '')));
    if ($purchaseStatus === 'paid') {
        return true;
    }

    $openedAt = trim((string)($lead['opened_at'] ?? ''));
    if ($openedAt !== '') {
        return true;
    }

    $purchasedAt = trim((string)($lead['purchased_at'] ?? ''));
    return $purchasedAt !== '';
}

function countLeadStatsByStatus(int $landlordId): array
{
    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('SELECT l.status, COUNT(*) c FROM leads l JOIN rooms r ON r.id = l.room_id WHERE r.landlord_id = :l AND r.deleted_at IS NULL GROUP BY l.status');
    $stmt->execute([':l' => $landlordId]);
    $out = [
        'new' => 0,
        'opened' => 0,
        'contacted' => 0,
        'negotiating' => 0,
        'closed' => 0,
        'invalid' => 0,
        'sold' => 0,
        'used' => 0,
        'paid' => 0,
        'purchased' => 0,
        'unpaid' => 0,
    ];
    foreach ($stmt->fetchAll() as $row) {
        $st = $row['status'] ?? '';
        $count = (int)$row['c'];
        if (isset($out[$st])) $out[$st] = $count;
        if (isLeadPurchasedStatus((string)$st)) {
            $out['purchased'] += $count;
        } elseif ($st !== 'invalid') {
            $out['unpaid'] += $count;
        }
    }
    return $out;
}

function leadRowsSelectSql(): string
{
    return 'SELECT
            l.*,
            r.title AS room_title,
            r.description AS room_description,
            r.landlord_id,
            r.price AS room_price,
            r.area AS room_area,
            r.address AS room_address,
            r.electric_price,
            r.water_price,
            r.shared_owner,
            r.closed_room,
            r.lead_price_final,
            r.lead_price_suggest,
            r.lead_price_expect,
            r.lead_price_admin,
            COALESCE(ro.occupancy_status, \'vacant\') AS occupancy_status,
            u.phone_verified,
            lp.status AS purchase_status,
            lp.created_at AS purchased_at,
            tp.area AS post_area,
            tp.price_min AS post_price_min,
            tp.price_max AS post_price_max,
            tp.people_count AS post_people_count,
            tp.note AS post_note,
            tp.gender AS post_gender,
            tp.post_kind,
            tp.district AS post_district,
            tp.ward AS post_ward,
            tp.near_place AS post_near_place,
            tp.room_type AS post_room_type,
            tp.move_in_time AS post_move_in_time,
            tp.area_min AS post_area_min,
            tp.priority AS post_priority,
            tp.shared_owner AS post_shared_owner,
            tp.closed_room AS post_closed_room,
            tp.amenities AS post_amenities,
            tp.amenities_list AS post_amenities_list
        FROM leads l
        JOIN rooms r ON r.id = l.room_id AND r.deleted_at IS NULL
        LEFT JOIN room_operations ro ON ro.room_id = r.id
        LEFT JOIN users u ON u.phone = l.tenant_phone
        LEFT JOIN lead_purchases lp ON lp.lead_id = l.id AND lp.landlord_id = r.landlord_id AND lp.status = "paid"
        LEFT JOIN tenant_posts tp ON tp.id = l.tenant_post_id';
}

function leadHistoryByIds(array $leadIds): array
{
    if (empty($leadIds)) {
        return [];
    }

    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    $placeholders = implode(',', array_fill(0, count($leadIds), '?'));
    $stmt = $pdo->prepare('SELECT id, lead_id, action, note, actor_role, created_at
                           FROM lead_logs
                           WHERE lead_id IN (' . $placeholders . ')
                           ORDER BY created_at DESC, id DESC');
    foreach (array_values($leadIds) as $index => $leadId) {
        $stmt->bindValue($index + 1, (int)$leadId, PDO::PARAM_INT);
    }
    $stmt->execute();

    $grouped = [];
    foreach ($stmt->fetchAll() as $row) {
        $leadId = (int)($row['lead_id'] ?? 0);
        if ($leadId <= 0) {
            continue;
        }
        $row['label'] = leadHistoryActionLabel((string)($row['action'] ?? ''));
        $grouped[$leadId][] = $row;
    }
    return $grouped;
}

function attachLeadHistories(array $rows): array
{
    $historyMap = leadHistoryByIds(array_map(static function (array $row): int {
        return (int)($row['id'] ?? 0);
    }, $rows));

    foreach ($rows as &$row) {
        $leadId = (int)($row['id'] ?? 0);
        $history = $historyMap[$leadId] ?? [];
        $actions = array_map(static function (array $event): string {
            return (string)($event['action'] ?? '');
        }, $history);

        if (!in_array('created', $actions, true) && !empty($row['created_at'])) {
            $history[] = [
                'id' => 0,
                'lead_id' => $leadId,
                'action' => 'created',
                'label' => leadHistoryActionLabel('created'),
                'note' => !empty($row['tenant_post_id']) ? 'Lead lấy từ nhu cầu thuê' : 'Khách quan tâm trực tiếp tới phòng',
                'actor_role' => 'system',
                'created_at' => $row['created_at'],
            ];
        }

        $openedAt = trim((string)($row['opened_at'] ?? $row['purchased_at'] ?? ''));
        if ($openedAt !== '' && !in_array('opened', $actions, true)) {
            $history[] = [
                'id' => 0,
                'lead_id' => $leadId,
                'action' => 'opened',
                'label' => leadHistoryActionLabel('opened'),
                'note' => 'Da mua va mo thong tin lien he',
                'actor_role' => 'landlord',
                'created_at' => $openedAt,
            ];
        }

        $status = (string)($row['status'] ?? '');
        if (in_array($status, ['contacted', 'negotiating', 'closed', 'invalid'], true) && !in_array($status, $actions, true)) {
            $history[] = [
                'id' => 0,
                'lead_id' => $leadId,
                'action' => $status,
                'label' => leadHistoryActionLabel($status),
                'note' => '',
                'actor_role' => 'landlord',
                'created_at' => (string)($row['last_interaction_at'] ?? $openedAt ?: $row['created_at']),
            ];
        }

        usort($history, static function (array $a, array $b): int {
            return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
        });

        $row['interaction_history'] = $history;
        $row['interaction_history_preview'] = array_slice($history, 0, 3);
    }
    unset($row);

    return $rows;
}

function hydrateLeadRows(array $rows, int $landlordId, bool $applyVipDelay = true): array
{
    $rows = attachMatchScore($rows);
    $rows = attachLeadHistories($rows);
    if ($applyVipDelay) {
        $rows = filterLeadsByVipDelay($rows, $landlordId);
    }
    usort($rows, static function ($a, $b) {
        $wa = $a['vip_weight'] ?? 0;
        $wb = $b['vip_weight'] ?? 0;
        $sa = $a['match_score'] ?? 0;
        $sb = $b['match_score'] ?? 0;
        $ca = $a['created_at'] ?? '';
        $cb = $b['created_at'] ?? '';
        if ($wa === $wb) {
            if ($sa === $sb) {
                return strcmp($cb, $ca);
            }
            return $sb <=> $sa;
        }
        return $wb <=> $wa;
    });
    return $rows;
}

function getLeadsForLandlord(int $landlordId): array
{
    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    ensureRoomOperationsSchema($pdo);
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare(leadRowsSelectSql() . ' WHERE r.landlord_id = :landlord ORDER BY l.created_at DESC, l.id DESC');
    $stmt->execute([':landlord' => $landlordId]);
    return hydrateLeadRows($stmt->fetchAll(), $landlordId, true);
}

function roomLeadsByRoom(int $roomId, int $landlordId): array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return [];
    }

    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    ensureRoomOperationsSchema($pdo);
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare(leadRowsSelectSql() . '
        WHERE r.landlord_id = :landlord AND l.room_id = :room
        ORDER BY l.created_at DESC, l.id DESC');
    $stmt->execute([
        ':landlord' => $landlordId,
        ':room' => $roomId,
    ]);

    return hydrateLeadRows($stmt->fetchAll(), $landlordId, false);
}

function landlordMarketplaceRoomPool(int $landlordId): array
{
    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('SELECT
            r.*,
            COALESCE(ro.occupancy_status, \'vacant\') AS occupancy_status
        FROM rooms r
        LEFT JOIN room_operations ro ON ro.room_id = r.id
        WHERE r.landlord_id = :landlord AND r.status = "active" AND r.deleted_at IS NULL
        ORDER BY CASE COALESCE(ro.occupancy_status, \'vacant\')
            WHEN \'vacant\' THEN 0
            WHEN \'reserved\' THEN 1
            WHEN \'maintenance\' THEN 2
            ELSE 3
        END, r.created_at DESC, r.id DESC');
    $stmt->execute([':landlord' => $landlordId]);
    return $stmt->fetchAll();
}

function bestMarketplaceRoomMatch(array $demand, array $rooms): array
{
    $best = [
        'room' => null,
        'match_percent' => 0,
        'match_score' => 0,
        'match_label' => '0%',
        'reasons' => [],
        'match_suggestion' => 'Chưa có phòng phù hợp',
    ];

    foreach ($rooms as $room) {
        $match = matchRoomToTenantDemand($demand, $room);
        if ($best['room'] === null || (int)$match['match_percent'] > (int)$best['match_percent']) {
            $best = array_merge($match, [
                'room' => $room,
            ]);
        }
    }

    if ($best['room'] !== null) {
        $roomTitle = trim((string)($best['room']['title'] ?? ''));
        $best['match_suggestion'] = 'Lead nay hop ' . (int)$best['match_percent'] . '% voi ' . $roomTitle;
    }

    return $best;
}

function findMarketplaceLeadForLandlord(int $tenantPostId, int $landlordId): ?array
{
    if ($tenantPostId <= 0) {
        return null;
    }

    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    ensureRoomOperationsSchema($pdo);
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare(leadRowsSelectSql() . '
        WHERE r.landlord_id = :landlord AND l.tenant_post_id = :tenant_post_id
        ORDER BY l.id DESC
        LIMIT 1');
    $stmt->execute([
        ':landlord' => $landlordId,
        ':tenant_post_id' => $tenantPostId,
    ]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $rows = hydrateLeadRows([$row], $landlordId, false);
    return $rows[0] ?? null;
}

function ensureMarketplaceLeadDraft(int $tenantPostId, int $landlordId): array
{
    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);

    $existing = findMarketplaceLeadForLandlord($tenantPostId, $landlordId);
    if ($existing) {
        return ['ok' => true, 'lead' => $existing];
    }

    $stmt = $pdo->prepare('SELECT tp.*, u.name AS user_name, u.phone AS user_phone
                           FROM tenant_posts tp
                           JOIN users u ON u.id = tp.user_id
                           WHERE tp.id = :id AND tp.status = "active"
                           LIMIT 1');
    $stmt->execute([':id' => $tenantPostId]);
    $post = $stmt->fetch();
    if (!$post) {
        return ['ok' => false, 'error' => 'Lead nhu cau da an hoac khong ton tai.'];
    }

    $rooms = landlordMarketplaceRoomPool($landlordId);
    if (empty($rooms)) {
        return ['ok' => false, 'error' => 'Can co it nhat 1 phong dang hoat dong de mua lead marketplace.'];
    }

    $demand = array_merge($post, tenantDemandContext($post));
    $match = bestMarketplaceRoomMatch($demand, $rooms);
    $room = $match['room'] ?? null;
    if (!$room || (int)($room['id'] ?? 0) <= 0) {
        return ['ok' => false, 'error' => 'Chua tim duoc phong phu hop de gan lead nay.'];
    }

    $insert = $pdo->prepare('INSERT INTO leads (
            tenant_id, tenant_post_id, room_id, tenant_name, tenant_phone, status, min_price, max_price, district, ward, time_slot, source
        ) VALUES (
            :tenant_id, :tenant_post_id, :room_id, :tenant_name, :tenant_phone, "new", :min_price, :max_price, :district, :ward, :time_slot, "marketplace"
        )');
    $insert->execute([
        ':tenant_id' => (int)($post['user_id'] ?? 0) ?: null,
        ':tenant_post_id' => $tenantPostId,
        ':room_id' => (int)$room['id'],
        ':tenant_name' => trim((string)($post['user_name'] ?? 'Khach')),
        ':tenant_phone' => trim((string)($post['user_phone'] ?? '')),
        ':min_price' => isset($post['price_min']) ? (int)$post['price_min'] : null,
        ':max_price' => isset($post['price_max']) ? (int)$post['price_max'] : null,
        ':district' => trim((string)($post['district'] ?? '')),
        ':ward' => trim((string)($post['ward'] ?? '')),
        ':time_slot' => trim((string)($post['move_in_time'] ?? '')),
    ]);

    $leadId = (int)$pdo->lastInsertId();
    appendLeadHistory(
        $leadId,
        'created',
        'Tao lead tu bai dang nhu cau #' . $tenantPostId . ' va de xuat phong #' . (int)$room['id'],
        $landlordId,
        'landlord'
    );

    $lead = findMarketplaceLeadForLandlord($tenantPostId, $landlordId);
    return ['ok' => $lead !== null, 'lead' => $lead, 'error' => $lead ? null : 'Khong tao duoc draft lead marketplace.'];
}

function landlordLeadMarketplace(int $landlordId): array
{
    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    ensureRoomOperationsSchema($pdo);
    ensureRoomSoftDeleteSchema($pdo);

    $rooms = landlordMarketplaceRoomPool($landlordId);
    $existingStmt = $pdo->prepare('SELECT DISTINCT l.tenant_post_id
                                   FROM leads l
                                   JOIN rooms r ON r.id = l.room_id AND r.deleted_at IS NULL
                                   WHERE r.landlord_id = :landlord AND l.tenant_post_id IS NOT NULL');
    $existingStmt->execute([':landlord' => $landlordId]);
    $existingPostIds = array_map('intval', $existingStmt->fetchAll(PDO::FETCH_COLUMN, 0));
    $existingMap = array_fill_keys($existingPostIds, true);

    $stmt = $pdo->query('SELECT tp.*, u.name AS user_name, u.phone AS user_phone
                         FROM tenant_posts tp
                         JOIN users u ON u.id = tp.user_id
                         WHERE tp.status = "active"
                         ORDER BY tp.created_at DESC, tp.id DESC');
    $available = [];
    foreach ($stmt->fetchAll() as $post) {
        $postId = (int)($post['id'] ?? 0);
        if ($postId <= 0 || isset($existingMap[$postId])) {
            continue;
        }

        $post = array_merge($post, tenantDemandContext($post));
        $match = bestMarketplaceRoomMatch($post, $rooms);
        $room = $match['room'] ?? null;
        $post['freshness_label'] = leadFreshnessLabel((string)($post['created_at'] ?? ''));
        $post['preview_name'] = maskName((string)($post['user_name'] ?? 'Khach'));
        $post['preview_phone'] = maskPhone((string)($post['user_phone'] ?? ''));
        $post['match_percent'] = (int)($match['match_percent'] ?? 0);
        $post['match_label'] = (string)($match['match_label'] ?? '0%');
        $post['match_suggestion'] = (string)($match['match_suggestion'] ?? 'Chua co phong phu hop');
        $post['match_room_id'] = (int)($room['id'] ?? 0);
        $post['match_room_title'] = (string)($room['title'] ?? '');
        $post['buy_price'] = $room ? effectiveLeadPriceFromRow($room) : 0;
        $post['buy_disabled'] = !$room;
        $available[] = $post;
    }

    usort($available, static function (array $a, array $b): int {
        $matchDiff = ((int)($b['match_percent'] ?? 0)) <=> ((int)($a['match_percent'] ?? 0));
        if ($matchDiff !== 0) {
            return $matchDiff;
        }
        return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
    });

    $freshCount = 0;
    foreach ($available as $post) {
        $createdTs = strtotime((string)($post['created_at'] ?? ''));
        if ($createdTs !== false && $createdTs >= (time() - 86400)) {
            $freshCount++;
        }
    }

    return [
        'available_posts' => $available,
        'available_count' => count($available),
        'fresh_count' => $freshCount,
        'rooms_ready_count' => count($rooms),
    ];
}

function updateLeadStage(int $leadId, int $landlordId, string $stage, string $note = ''): bool
{
    $allowedStages = ['contacted', 'negotiating', 'closed', 'invalid'];
    if ($leadId <= 0 || !in_array($stage, $allowedStages, true)) {
        return false;
    }

    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('SELECT l.id, l.status, l.tenant_phone, l.opened_at, r.landlord_id, lp.status AS purchase_status
                           FROM leads l
                           JOIN rooms r ON r.id = l.room_id
                           LEFT JOIN lead_purchases lp ON lp.lead_id = l.id AND lp.landlord_id = r.landlord_id AND lp.status = "paid"
                           WHERE l.id = :id AND r.deleted_at IS NULL
                           LIMIT 1');
    $stmt->execute([':id' => $leadId]);
    $lead = $stmt->fetch();
    if (!$lead || (int)($lead['landlord_id'] ?? 0) !== $landlordId) {
        return false;
    }
    if (!leadHasUnlockedContact($lead)) {
        return false;
    }

    $currentStatus = (string)($lead['status'] ?? 'new');
    if (in_array($currentStatus, ['closed', 'invalid'], true) && $currentStatus !== $stage) {
        return false;
    }

    $now = date('Y-m-d H:i:s');
    $startedTransaction = false;
    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        $upd = $pdo->prepare('UPDATE leads
                              SET status = :status,
                                  contact_attempted = :contact_attempted,
                                  last_interaction_at = :updated_at
                              WHERE id = :id');
        $upd->execute([
            ':status' => $stage,
            ':contact_attempted' => in_array($stage, ['contacted', 'negotiating', 'closed', 'invalid'], true) ? 1 : 0,
            ':updated_at' => $now,
            ':id' => $leadId,
        ]);

        if (in_array($stage, ['contacted', 'negotiating', 'closed'], true)) {
            $phone = trim((string)($lead['tenant_phone'] ?? ''));
            if ($phone !== '') {
                $pdo->prepare('UPDATE users SET phone_verified = 1 WHERE phone = :phone')
                    ->execute([':phone' => $phone]);
            }
        }

        appendLeadHistory(
            $leadId,
            $stage,
            trim($note) !== '' ? trim($note) : leadHistoryActionLabel($stage),
            $landlordId,
            'landlord'
        );

        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Update lead stage failed: ' . $e->getMessage());
        return false;
    }

    ensureChatForLead($leadId);
    return true;
}

function convertLeadToRoomOccupancy(int $leadId, int $roomId, int $landlordId): array
{
    if ($leadId <= 0 || $roomId <= 0) {
        return ['ok' => false, 'error' => 'Thiếu lead hoặc phòng để chốt thuê.'];
    }

    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return ['ok' => false, 'error' => 'Không tìm thấy phòng cần chốt thuê.'];
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT l.*, lp.status AS purchase_status
        FROM leads l
        JOIN rooms r ON r.id = l.room_id
        LEFT JOIN lead_purchases lp ON lp.lead_id = l.id AND lp.landlord_id = r.landlord_id AND lp.status = "paid"
        WHERE l.id = :lead_id AND l.room_id = :room_id AND r.landlord_id = :landlord_id
        LIMIT 1');
    $stmt->execute([
        ':lead_id' => $leadId,
        ':room_id' => $roomId,
        ':landlord_id' => $landlordId,
    ]);
    $lead = $stmt->fetch();
    if (!$lead) {
        return ['ok' => false, 'error' => 'Lead không thuộc phòng này hoặc không còn tồn tại.'];
    }

    $leadStatus = (string)($lead['status'] ?? 'new');
    if (!leadHasUnlockedContact($lead)) {
        return ['ok' => false, 'error' => 'Cần mua lead trước khi chốt thuê.'];
    }
    if ($leadStatus === 'invalid') {
        return ['ok' => false, 'error' => 'Lead đã bị đánh dấu lỗi nên không thể chốt thuê.'];
    }

    $tenantPhone = trim((string)($lead['tenant_phone'] ?? ''));
    if ($tenantPhone === '') {
        return ['ok' => false, 'error' => 'Lead chưa có số điện thoại để gắn vào hồ sơ phòng.'];
    }

    $profile = roomOperationProfile($roomId, $landlordId) ?? roomOperationProfileDefaults($room);
    $currentStatus = (string)($profile['occupancy_status'] ?? 'vacant');
    $currentPhone = trim((string)($profile['tenant_phone'] ?? ''));
    if ($currentStatus === 'occupied' && $currentPhone !== '' && $currentPhone !== $tenantPhone) {
        return ['ok' => false, 'error' => 'Phòng đang gắn với người thuê khác. Hãy checkout kỳ thuê hiện tại trước khi chốt lead mới.'];
    }

    $operationNote = trim((string)($profile['operation_note'] ?? ''));
    $leadNote = 'Chốt thuê từ lead #' . $leadId . ' lúc ' . date('Y-m-d H:i');
    if ($operationNote === '') {
        $operationNote = $leadNote;
    } elseif (strpos($operationNote, $leadNote) === false) {
        $operationNote .= "\n" . $leadNote;
    }

    $payload = [
        'occupancy_status' => 'occupied',
        'tenant_name' => trim((string)($lead['tenant_name'] ?? '')),
        'tenant_phone' => $tenantPhone,
        'monthly_rent' => $profile['monthly_rent'] ?? $room['price'] ?? 0,
        'deposit_amount' => $profile['deposit_amount'] ?? '',
        'service_fee' => $profile['service_fee'] ?? 0,
        'contract_start' => !empty($profile['contract_start']) ? (string)$profile['contract_start'] : date('Y-m-d'),
        'contract_end' => $profile['contract_end'] ?? '',
        'electric_meter_reading' => $profile['electric_meter_reading'] ?? '',
        'water_meter_reading' => $profile['water_meter_reading'] ?? '',
        'room_condition' => $profile['room_condition'] ?? 'ready',
        'issue_note' => $profile['issue_note'] ?? '',
        'operation_note' => $operationNote,
    ];

    $startedTransaction = false;
    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        saveRoomOperationProfile($roomId, $landlordId, $payload);

        $updateLead = $pdo->prepare('UPDATE leads SET status = :status WHERE id = :id');
        $updateLead->execute([
            ':status' => 'closed',
            ':id' => $leadId,
        ]);

        $tenantUser = findUserByPhone($tenantPhone);
        if ($tenantUser) {
            $verifyStmt = $pdo->prepare('UPDATE users SET phone_verified = 1 WHERE id = :id');
            $verifyStmt->execute([':id' => (int)$tenantUser['id']]);
        }

        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Convert lead to room occupancy failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Không chốt được lead sang kỳ thuê thực tế.'];
    }

    appendLeadHistory($leadId, 'closed', 'Chot lead thanh ky thue thuc te cho phong #' . $roomId, $landlordId, 'landlord');
    ensureChatForLead($leadId);
    return ['ok' => true];
}

function countRoomsByLandlord(int $landlordId): int
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE landlord_id = :l AND deleted_at IS NULL');
    $stmt->execute([':l' => $landlordId]);
    return (int)$stmt->fetchColumn();
}

function countLeadsByLandlord(int $landlordId): int
{
    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM leads l JOIN rooms r ON r.id = l.room_id WHERE r.landlord_id = :l AND r.deleted_at IS NULL');
    $stmt->execute([':l' => $landlordId]);
    return (int)$stmt->fetchColumn();
}

function latestLeadsByLandlord(int $landlordId, int $limit = 5): array
{
    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    ensureRoomOperationsSchema($pdo);
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare(leadRowsSelectSql() . ' WHERE r.landlord_id = :l ORDER BY l.created_at DESC, l.id DESC LIMIT :lim');
    $stmt->bindValue(':l', $landlordId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return array_slice(hydrateLeadRows($stmt->fetchAll(), $landlordId, true), 0, $limit);
}

function latestLeadIdByLandlord(int $landlordId): int
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(l.id), 0) FROM leads l JOIN rooms r ON r.id = l.room_id WHERE r.landlord_id = :l AND r.deleted_at IS NULL');
    $stmt->execute([':l' => $landlordId]);
    return (int)$stmt->fetchColumn();
}

function leadNotificationsByLandlord(int $landlordId, int $afterId = 0, int $limit = 10): array
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    try {
        $stmt = $pdo->prepare('SELECT l.id, l.room_id, l.created_at, l.status, l.notification_read_at, r.title AS room_title
                               FROM leads l
                               JOIN rooms r ON r.id = l.room_id
                               WHERE r.landlord_id = :l AND r.deleted_at IS NULL AND l.id > :after_id
                               ORDER BY l.id ASC
                               LIMIT :lim');
    } catch (PDOException $e) {
        error_log('Lead notification query prepare failed: ' . $e->getMessage());
        return [];
    }
    $stmt->bindValue(':l', $landlordId, PDO::PARAM_INT);
    $stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    try {
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Lead notification query failed: ' . $e->getMessage());
        if (stripos($e->getMessage(), 'notification_read_at') === false) {
            return [];
        }
    }

    $stmt = $pdo->prepare('SELECT l.id, l.room_id, l.created_at, l.status, NULL AS notification_read_at, r.title AS room_title
                           FROM leads l
                           JOIN rooms r ON r.id = l.room_id
                           WHERE r.landlord_id = :l AND r.deleted_at IS NULL AND l.id > :after_id
                           ORDER BY l.id ASC
                           LIMIT :lim');
    $stmt->bindValue(':l', $landlordId, PDO::PARAM_INT);
    $stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function recentLeadNotificationsByLandlord(int $landlordId, int $limit = 50): array
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('SELECT l.id, l.room_id, l.created_at, l.status, l.notification_read_at, r.title AS room_title, r.address AS room_address
                           FROM leads l
                           JOIN rooms r ON r.id = l.room_id
                           WHERE r.landlord_id = :l AND r.deleted_at IS NULL
                           ORDER BY l.id DESC
                           LIMIT :lim');
    $stmt->bindValue(':l', $landlordId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
    try {
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Recent lead notification query failed: ' . $e->getMessage());
        if (stripos($e->getMessage(), 'notification_read_at') === false) {
            return [];
        }
    }

    $stmt = $pdo->prepare('SELECT l.id, l.room_id, l.created_at, l.status, NULL AS notification_read_at, r.title AS room_title, r.address AS room_address
                           FROM leads l
                           JOIN rooms r ON r.id = l.room_id
                           WHERE r.landlord_id = :l AND r.deleted_at IS NULL
                           ORDER BY l.id DESC
                           LIMIT :lim');
    $stmt->bindValue(':l', $landlordId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countUnreadLeadNotificationsByLandlord(int $landlordId): int
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('SELECT COUNT(*)
                           FROM leads l
                           JOIN rooms r ON r.id = l.room_id
                           WHERE r.landlord_id = :l
                             AND r.deleted_at IS NULL
                             AND l.notification_read_at IS NULL');
    try {
        $stmt->execute([':l' => $landlordId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Unread lead notification count failed: ' . $e->getMessage());
        return 0;
    }
}

function setLeadNotificationReadState(int $landlordId, int $leadId, bool $isRead): bool
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $exists = $pdo->prepare('SELECT l.id
                             FROM leads l
                             JOIN rooms r ON r.id = l.room_id
                             WHERE l.id = :id AND r.landlord_id = :l AND r.deleted_at IS NULL
                             LIMIT 1');
    $exists->execute([
        ':id' => $leadId,
        ':l' => $landlordId,
    ]);
    if (!$exists->fetchColumn()) {
        return false;
    }

    $stmt = $pdo->prepare('UPDATE leads
                           SET notification_read_at = :read_at
                           WHERE id = :id
                             AND room_id IN (SELECT id FROM rooms WHERE landlord_id = :l AND deleted_at IS NULL)');
    $readAt = $isRead ? date('Y-m-d H:i:s') : null;
    $stmt->bindValue(':read_at', $readAt, $readAt === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':id', $leadId, PDO::PARAM_INT);
    $stmt->bindValue(':l', $landlordId, PDO::PARAM_INT);
    try {
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log('Set lead notification read state failed: ' . $e->getMessage());
        return false;
    }
}

function markAllLeadNotificationsRead(int $landlordId): int
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('UPDATE leads
                           SET notification_read_at = :read_at
                           WHERE notification_read_at IS NULL
                             AND room_id IN (SELECT id FROM rooms WHERE landlord_id = :l AND deleted_at IS NULL)');
    try {
        $stmt->execute([
            ':read_at' => date('Y-m-d H:i:s'),
            ':l' => $landlordId,
        ]);
        return (int)$stmt->rowCount();
    } catch (PDOException $e) {
        error_log('Mark all lead notifications read failed: ' . $e->getMessage());
        return 0;
    }
}

function verifyLeadPhone(int $leadId, int $landlordId): bool
{
    return updateLeadStage($leadId, $landlordId, 'contacted');
}

function openLead(int $leadId, int $landlordId): bool
{
    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    ensureRoomSoftDeleteSchema($pdo);
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT l.id, l.status, l.opened_at, r.landlord_id, r.lead_price_final, r.lead_price_suggest, r.lead_price_expect, r.lead_price_admin, lp.status AS purchase_status
                           FROM leads l
                           JOIN rooms r ON r.id = l.room_id
                           LEFT JOIN lead_purchases lp ON lp.lead_id = l.id AND lp.landlord_id = r.landlord_id AND lp.status = "paid"
                           WHERE l.id = :id AND r.deleted_at IS NULL');
    $stmt->execute([':id' => $leadId]);
    $lead = $stmt->fetch();

    if (!$lead || (int)$lead['landlord_id'] !== $landlordId) {
        $pdo->rollBack();
        return false;
    }

    // tránh double purchase
    $paidExists = $pdo->prepare('SELECT id FROM lead_purchases WHERE lead_id = :lead AND landlord_id = :l AND status = "paid" LIMIT 1');
    $paidExists->execute([':lead' => $leadId, ':l' => $landlordId]);
    if ($paidExists->fetchColumn()) {
        $pdo->commit();
        ensureChatForLead($leadId);
        return true;
    }

    if (leadHasUnlockedContact($lead)) {
        $pdo->commit();
        ensureChatForLead($leadId);
        return true;
    }
    if (!in_array($lead['status'], ['new'], true)) {
        $pdo->rollBack();
        return false;
    }
    $amount = effectiveLeadPriceFromRow($lead);

    $update = $pdo->prepare('UPDATE leads SET status = "opened", opened_at = CURRENT_TIMESTAMP, price = :price WHERE id = :id');
    $update->execute([':id' => $leadId, ':price' => $amount]);

    // ghi nhận purchase (đã check tránh trùng)
    $purchase = $pdo->prepare('INSERT INTO lead_purchases (lead_id, landlord_id, price, status) VALUES (:lead, :landlord, :price, "paid")');
    $purchase->execute([':lead' => $leadId, ':landlord' => $landlordId, ':price' => $amount]);

    $payment = $pdo->prepare('INSERT INTO payments (landlord_id, lead_id, amount, type, status, provider) VALUES (:landlord, :lead, :amount, "lead", "paid", "fake")');
    $payment->execute([
        ':landlord' => $landlordId,
        ':lead' => $leadId,
        ':amount' => $amount,
    ]);

    addLandlordPoints($landlordId, 10);

    $pdo->commit();
    appendLeadHistory($leadId, 'opened', 'Da mua lead va mo thong tin lien he', $landlordId, 'landlord');
    ensureChatForLead($leadId);
    return true;
}

function leadPaymentPhoneSuffix(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    return str_pad(substr($digits, -3), 3, '0', STR_PAD_LEFT);
}

function generateLeadPaymentCode(string $phone): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $prefix = '';
    $max = strlen($alphabet) - 1;
    for ($i = 0; $i < 5; $i++) {
        $prefix .= $alphabet[random_int(0, $max)];
    }
    return $prefix . leadPaymentPhoneSuffix($phone);
}

function paymentExpiresAt(int $minutes = 15): string
{
    return date('Y-m-d H:i:s', time() + ($minutes * 60));
}

function ensurePaymentExpiryColumn(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo = getPDO();
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $cols = $pdo->query("SHOW COLUMNS FROM payments")->fetchAll(PDO::FETCH_COLUMN, 0);
            if (!in_array('expires_at', $cols, true)) {
                $pdo->exec("ALTER TABLE payments ADD COLUMN expires_at DATETIME NULL");
            }
            $pdo->exec("UPDATE payments SET expires_at = DATE_ADD(created_at, INTERVAL 15 MINUTE) WHERE expires_at IS NULL AND status = 'pending'");
        } else {
            $cols = array_column($pdo->query('PRAGMA table_info(payments)')->fetchAll(), 'name');
            if (!in_array('expires_at', $cols, true)) {
                $pdo->exec('ALTER TABLE payments ADD COLUMN expires_at TEXT');
            }
            $pdo->exec("UPDATE payments SET expires_at = datetime(created_at, '+15 minutes') WHERE expires_at IS NULL AND status = 'pending'");
        }
    } catch (PDOException $e) {
        // If ALTER is unavailable, the explicit database.sql update will be required.
    }
    $done = true;
}

function isPaymentExpired(array $payment): bool
{
    if ((string)($payment['status'] ?? '') !== 'pending') {
        return false;
    }
    $expiresAt = trim((string)($payment['expires_at'] ?? ''));
    if ($expiresAt === '') {
        return false;
    }
    $ts = strtotime($expiresAt);
    return $ts !== false && $ts <= time();
}

function expirePaymentIfNeeded(array $payment): bool
{
    ensurePaymentExpiryColumn();
    if (!isPaymentExpired($payment)) {
        return false;
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE payments SET status = "failed" WHERE id = :id AND status = "pending"');
    $stmt->execute([':id' => (int)$payment['id']]);
    return true;
}

function expireStalePendingPayments(?int $landlordId = null): void
{
    ensurePaymentExpiryColumn();
    $pdo = getPDO();
    $now = date('Y-m-d H:i:s');
    if ($landlordId !== null) {
        $stmt = $pdo->prepare('UPDATE payments SET status = "failed" WHERE status = "pending" AND expires_at IS NOT NULL AND expires_at <= :now AND landlord_id = :landlord');
        $stmt->execute([':now' => $now, ':landlord' => $landlordId]);
        return;
    }
    $stmt = $pdo->prepare('UPDATE payments SET status = "failed" WHERE status = "pending" AND expires_at IS NOT NULL AND expires_at <= :now');
    $stmt->execute([':now' => $now]);
}

function createPendingPaymentForLead(int $leadId, int $landlordId, int $amount, string $tenantPhone): int
{
    ensurePaymentExpiryColumn();
    $pdo = getPDO();
    expireStalePendingPayments($landlordId);

    $now = date('Y-m-d H:i:s');
    $active = $pdo->prepare('SELECT id FROM payments WHERE landlord_id = :l AND lead_id = :lead AND status = "pending" AND expires_at IS NOT NULL AND expires_at > :now ORDER BY created_at DESC LIMIT 1');
    $active->execute([':l' => $landlordId, ':lead' => $leadId, ':now' => $now]);
    $activeId = $active->fetchColumn();
    if ($activeId) {
        return (int)$activeId;
    }

    $expiresAt = paymentExpiresAt(15);
    for ($i = 0; $i < 20; $i++) {
        $code = generateLeadPaymentCode($tenantPhone);
        if (findPaymentByCode($code)) {
            continue;
        }
        $stmt = $pdo->prepare('INSERT INTO payments (landlord_id, lead_id, amount, payment_code, expires_at, type, status, provider) VALUES (:l, :lead, :amount, :code, :expires_at, "lead", "pending", "sepay")');
        try {
            $stmt->execute([
                ':l' => $landlordId,
                ':lead' => $leadId,
                ':amount' => $amount,
                ':code' => $code,
                ':expires_at' => $expiresAt,
            ]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            // Rare duplicate payment_code race; retry with a new code.
        }
    }

    throw new RuntimeException('Không tạo được nội dung thanh toán.');
}

function findPaymentByCode(string $code): ?array
{
    ensurePaymentExpiryColumn();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM payments WHERE payment_code = :c LIMIT 1');
    $stmt->execute([':c' => strtoupper(trim($code))]);
    $p = $stmt->fetch();
    return $p ?: null;
}

function markPaymentPaid(int $paymentId, string $providerRef): bool
{
    ensurePaymentExpiryColumn();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $paymentId]);
    $payment = $stmt->fetch();
    if (!$payment || expirePaymentIfNeeded($payment)) {
        return false;
    }
    $stmt = $pdo->prepare('UPDATE payments SET status = "paid", provider = "sepay", provider_ref = :ref WHERE id = :id AND status = "pending"');
    $stmt->execute([':ref' => $providerRef, ':id' => $paymentId]);
    return $stmt->rowCount() > 0;
}

function recordLeadPurchase(int $leadId, int $landlordId, int $amount): void
{
    $pdo = getPDO();
    try {
        $stmt = $pdo->prepare('INSERT INTO lead_purchases (lead_id, landlord_id, price, status) VALUES (:lead, :landlord, :price, "paid")');
        $stmt->execute([':lead' => $leadId, ':landlord' => $landlordId, ':price' => $amount]);
    } catch (PDOException $e) {
        // Already purchased for this lead/landlord.
    }
}

function openLeadByPayment(int $leadId): bool
{
    $pdo = getPDO();
    ensureLeadMarketplaceSchema($pdo);
    ensureRoomSoftDeleteSchema($pdo);
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT l.status, l.opened_at, r.landlord_id, r.lead_price_final, r.lead_price_admin, r.lead_price_suggest, r.lead_price_expect, lp.status AS purchase_status
                           FROM leads l
                           JOIN rooms r ON r.id = l.room_id
                           LEFT JOIN lead_purchases lp ON lp.lead_id = l.id AND lp.landlord_id = r.landlord_id AND lp.status = "paid"
                           WHERE l.id = :id AND r.deleted_at IS NULL');
    $stmt->execute([':id' => $leadId]);
    $lead = $stmt->fetch();
    if (!$lead) { $pdo->rollBack(); return false; }
    $amount = effectiveLeadPriceFromRow($lead);
    $landlordId = (int)$lead['landlord_id'];
    if (leadHasUnlockedContact($lead)) {
        recordLeadPurchase($leadId, $landlordId, (int)$amount);
        $pdo->commit();
        appendLeadHistory($leadId, 'opened', 'Thanh toan thanh cong va mo lead', $landlordId, 'system');
        ensureChatForLead($leadId);
        return true;
    }
    $pdo->prepare('UPDATE leads SET status = "opened", opened_at = CURRENT_TIMESTAMP, price = :p WHERE id = :id')->execute([':p' => $amount, ':id' => $leadId]);
    recordLeadPurchase($leadId, $landlordId, (int)$amount);
    ensureChatForLead($leadId);
    $pdo->commit();
    appendLeadHistory($leadId, 'opened', 'Thanh toan thanh cong va mo lead', $landlordId, 'system');
    return true;
}

function ensureChatForLead(int $leadId): void
{
    $pdo = getPDO();
    // lấy landlord_id, tenant_id
    $stmt = $pdo->prepare('SELECT l.id, l.tenant_id, r.landlord_id FROM leads l JOIN rooms r ON r.id = l.room_id WHERE l.id = :id');
    $stmt->execute([':id' => $leadId]);
    $row = $stmt->fetch();
    if (!$row) return;
    $tenantId = (int)($row['tenant_id'] ?? 0);
    $landlordId = (int)($row['landlord_id'] ?? 0);
    if ($tenantId <= 0 || $landlordId <= 0) return;

    // create chat if not exists
    $insert = $pdo->prepare('INSERT INTO chats (lead_id, landlord_id, tenant_id) VALUES (:lead, :landlord, :tenant)');
    try {
        $insert->execute([':lead' => $leadId, ':landlord' => $landlordId, ':tenant' => $tenantId]);
    } catch (PDOException $e) {
        // unique constraint; ignore
    }
}

function markLeadInvalid(int $leadId, int $landlordId): bool
{
    return updateLeadStage($leadId, $landlordId, 'invalid');
}

function maskName(string $name): string
{
    $first = mb_substr($name, 0, 1, 'UTF-8');
    return $first . '***';
}

function maskPhone(string $phone): string
{
    if (strlen($phone) <= 4) {
        return str_repeat('*', strlen($phone));
    }
    $prefix = substr($phone, 0, 3);
    $suffix = substr($phone, -2);
    return $prefix . str_repeat('*', max(0, strlen($phone) - 5)) . $suffix;
}

function maskAddress(string $address): string
{
    $parts = explode(',', $address);
    if (count($parts) <= 1) {
        return $address;
    }
    return trim($parts[0]) . ', ...';
}

function countUsers(): int
{
    $pdo = getPDO();
    return (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

function countUsersGroupedByRole(): array
{
    $pdo = getPDO();
    $out = [
        'tenant' => 0,
        'landlord' => 0,
        'staff' => 0,
        'admin' => 0,
    ];

    $stmt = $pdo->query('SELECT role, COUNT(*) AS total FROM users GROUP BY role');
    foreach ($stmt->fetchAll() as $row) {
        $role = (string)($row['role'] ?? '');
        if (array_key_exists($role, $out)) {
            $out[$role] = (int)($row['total'] ?? 0);
        }
    }

    return $out;
}

function countRooms(): int
{
    $pdo = getPDO();
    return (int)$pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
}

function countLeads(): int
{
    $pdo = getPDO();
    return (int)$pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn();
}

function countRoomLeadsRecent(int $roomId, int $hours = 24): int
{
    $pdo = getPDO();
    $cutoff = date('Y-m-d H:i:s', time() - ($hours * 3600));
    $sql = 'SELECT COUNT(*) FROM leads WHERE room_id = :room AND created_at >= :cutoff';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':room', $roomId, PDO::PARAM_INT);
    $stmt->bindValue(':cutoff', $cutoff);
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

function landlordLeadQuotaRemaining(int $landlordId, int $dailyQuota = 3): array
{
    if (isAdmin()) {
        return ['left' => 999, 'total' => 999, 'opened' => 0];
    }
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $sql = 'SELECT COUNT(*) FROM leads l JOIN rooms r ON r.id = l.room_id WHERE r.landlord_id = :lid AND r.deleted_at IS NULL AND COALESCE(l.opened_at, l.created_at) >= CURDATE()';
    } else {
        $sql = 'SELECT COUNT(*) FROM leads l JOIN rooms r ON r.id = l.room_id WHERE r.landlord_id = :lid AND r.deleted_at IS NULL AND COALESCE(l.opened_at, l.created_at) >= date("now","start of day")';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':lid' => $landlordId]);
    $openedToday = (int)$stmt->fetchColumn();
    $left = max(0, $dailyQuota - $openedToday);
    return ['left' => $left, 'total' => $dailyQuota, 'opened' => $openedToday];
}

function countPayments(): int
{
    $pdo = getPDO();
    return (int)$pdo->query('SELECT COUNT(*) FROM payments')->fetchColumn();
}

function sumPayments(): int
{
    $pdo = getPDO();
    $v = $pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "paid"')->fetchColumn();
    return (int)$v;
}

function adminOperationalSummary(): array
{
    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);

    $summary = [
        'vacant_rooms' => 0,
        'occupied_rooms' => 0,
        'reserved_rooms' => 0,
        'maintenance_rooms' => 0,
        'expiring_contracts' => 0,
        'active_stays' => 0,
        'unpaid_invoices' => 0,
        'due_soon_invoices' => 0,
        'overdue_invoices' => 0,
        'open_issues' => 0,
        'pending_rooms' => 0,
        'pending_seek_posts' => 0,
    ];

    $occupancyStmt = $pdo->query('SELECT COALESCE(ro.occupancy_status, "vacant") AS occupancy_status, COUNT(*) AS total
        FROM rooms r
        LEFT JOIN room_operations ro ON ro.room_id = r.id
        GROUP BY COALESCE(ro.occupancy_status, "vacant")');
    foreach ($occupancyStmt->fetchAll() as $row) {
        $status = (string)($row['occupancy_status'] ?? 'vacant');
        $key = $status . '_rooms';
        if (array_key_exists($key, $summary)) {
            $summary[$key] = (int)($row['total'] ?? 0);
        }
    }

    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $expiringSql = 'SELECT COUNT(*) FROM room_operations
            WHERE contract_end IS NOT NULL
              AND contract_end >= CURDATE()
              AND contract_end <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
    } else {
        $expiringSql = 'SELECT COUNT(*) FROM room_operations
            WHERE contract_end IS NOT NULL
              AND contract_end >= date("now")
              AND contract_end <= date("now", "+30 day")';
    }
    $summary['expiring_contracts'] = (int)$pdo->query($expiringSql)->fetchColumn();

    $invoiceStmt = $pdo->query('SELECT status, due_date FROM room_invoices');
    foreach ($invoiceStmt->fetchAll() as $invoice) {
        $displayStatus = roomInvoiceDisplayStatus($invoice);
        $reminderState = roomInvoiceReminderState($invoice);
        if ($displayStatus !== 'paid' && $displayStatus !== 'cancelled') {
            $summary['unpaid_invoices']++;
        }
        if ($reminderState === 'due_soon') {
            $summary['due_soon_invoices']++;
        } elseif ($reminderState === 'overdue') {
            $summary['overdue_invoices']++;
        }
    }

    $summary['active_stays'] = (int)$pdo->query('SELECT COUNT(*) FROM tenant_stay_history WHERE status = "active"')->fetchColumn();
    $summary['open_issues'] = (int)$pdo->query('SELECT COUNT(*) FROM tenant_issue_reports WHERE status != "resolved"')->fetchColumn();
    $summary['pending_rooms'] = (int)$pdo->query('SELECT COUNT(*) FROM rooms WHERE status = "pending"')->fetchColumn();

    try {
        $summary['pending_seek_posts'] = (int)$pdo->query('SELECT COUNT(*) FROM tenant_posts WHERE status = "pending"')->fetchColumn();
    } catch (PDOException $e) {
        $summary['pending_seek_posts'] = 0;
    }

    return $summary;
}

function suggestLeadPrice(int $roomPrice): int
{
    if ($roomPrice < 1_000_000) return 7000;
    if ($roomPrice < 2_000_000) return 10000;
    if ($roomPrice < 3_000_000) return 17000;
    if ($roomPrice < 5_000_000) return 22000;
    return 25000;
}

function isThousandAmount(?int $amount): bool
{
    return $amount === null || ($amount >= 0 && $amount % 1000 === 0);
}

function isPositiveThousandAmount(int $amount): bool
{
    return $amount > 0 && $amount % 1000 === 0;
}

function isValidLeadPrice(?int $amount): bool
{
    return $amount === null || ($amount >= 3000 && $amount % 1000 === 0);
}

function normalizeLeadPrice(int $amount): int
{
    if ($amount < 3000) {
        return 3000;
    }
    return (int)(ceil($amount / 1000) * 1000);
}

function effectiveLeadPriceFromRow(array $row): int
{
    $amount = $row['lead_price_final']
        ?? $row['lead_price_admin']
        ?? $row['lead_price_suggest']
        ?? $row['lead_price_expect']
        ?? null;
    if ($amount === null || (int)$amount <= 0) {
        $roomPrice = (int)($row['room_price'] ?? $row['price'] ?? 0);
        $amount = $roomPrice > 0 ? suggestLeadPrice($roomPrice) : 20000;
    }
    return normalizeLeadPrice((int)$amount);
}

function createRoom(
    int $landlordId,
    string $title,
    int $price,
    ?int $leadPriceExpect,
    string $area,
    string $address,
    string $description,
    string $thumb,
    ?int $electricPrice = null,
    ?int $waterPrice = null,
    int $sharedOwner = 0,
    int $closedRoom = 0,
    ?string $image1 = null,
    ?string $image2 = null,
    ?string $image3 = null,
    ?string $image4 = null,
    ?string $image5 = null,
    ?string $image6 = null,
    ?string $image7 = null,
    ?string $image8 = null,
    ?string $videoUrl = null
): int
{
    $pdo = getPDO();
    $leadPriceSuggest = suggestLeadPrice($price);
    $leadPriceExpect = $leadPriceExpect && $leadPriceExpect > 0 ? $leadPriceExpect : $leadPriceSuggest;
    $leadPriceExpect = normalizeLeadPrice($leadPriceExpect);
    // Mặc định pending để admin duyệt trước khi hiển thị
    $stmt = $pdo->prepare('INSERT INTO rooms (title, price, lead_price_expect, lead_price_suggest, area, address, landlord_id, description, thumbnail, electric_price, water_price, shared_owner, closed_room, image1, image2, image3, image4, image5, image6, image7, image8, video_url, status) VALUES (:t,:p,:lp_exp,:lp_sug,:a,:addr,:landlord,:desc,:thumb,:ele,:wat,:shared,:closed,:img1,:img2,:img3,:img4,:img5,:img6,:img7,:img8,:video,"pending")');
    $stmt->execute([
        ':t' => $title,
        ':p' => $price,
        ':lp_exp' => $leadPriceExpect,
        ':lp_sug' => $leadPriceSuggest,
        ':a' => $area,
        ':addr' => $address,
        ':landlord' => $landlordId,
        ':desc' => $description,
        ':thumb' => $thumb,
        ':ele' => $electricPrice,
        ':wat' => $waterPrice,
        ':shared' => $sharedOwner,
        ':closed' => $closedRoom,
        ':img1' => $image1,
        ':img2' => $image2,
        ':img3' => $image3,
        ':img4' => $image4,
        ':img5' => $image5,
        ':img6' => $image6,
        ':img7' => $image7,
        ':img8' => $image8,
        ':video' => $videoUrl,
    ]);
    $points = 5;
    if (!empty($videoUrl)) {
        $points += 5;
    }
    addLandlordPoints($landlordId, $points);
    return (int)$pdo->lastInsertId();
}

function roomsByLandlord(int $landlordId, string $keyword = ''): array
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $sql = 'SELECT * FROM rooms WHERE landlord_id = :l AND deleted_at IS NULL';
    $params = [':l' => $landlordId];
    if ($keyword !== '') {
        $sql .= ' AND (title LIKE :kw OR area LIKE :kw OR address LIKE :kw';
        if (ctype_digit($keyword)) {
            $sql .= ' OR id = :id_kw';
            $params[':id_kw'] = (int)$keyword;
        }
        $sql .= ')';
        $params[':kw'] = '%' . $keyword . '%';
    }
    $sql .= ' ORDER BY id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function deleteRoom(int $roomId, int $landlordId): bool
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('UPDATE rooms
                           SET deleted_at = CURRENT_TIMESTAMP,
                               status = "rejected"
                           WHERE id = :id AND landlord_id = :l AND deleted_at IS NULL');
    $stmt->execute([':id' => $roomId, ':l' => $landlordId]);
    return $stmt->rowCount() > 0;
}

function findRoomOwned(int $roomId, int $landlordId): ?array
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = :id AND landlord_id = :l AND deleted_at IS NULL');
    $stmt->execute([':id' => $roomId, ':l' => $landlordId]);
    $room = $stmt->fetch();
    return $room ?: null;
}

function tableColumnNames(PDO $pdo, string $table): array
{
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $stmt = $pdo->query('SHOW COLUMNS FROM ' . $table);
        if (!$stmt) {
            return [];
        }
        return array_map(static function ($row) {
            return (string)($row['Field'] ?? '');
        }, $stmt->fetchAll());
    }

    $stmt = $pdo->query('PRAGMA table_info(' . $table . ')');
    if (!$stmt) {
        return [];
    }
    return array_map(static function ($row) {
        return (string)($row['name'] ?? '');
    }, $stmt->fetchAll());
}

function ensureTableColumns(PDO $pdo, string $table, array $definitions): void
{
    $columns = tableColumnNames($pdo, $table);
    foreach ($definitions as $column => $definition) {
        if (in_array($column, $columns, true)) {
            continue;
        }
        try {
            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $definition);
        } catch (PDOException $e) {
            // ignore if the current engine rejects a duplicate or incompatible add
        }
    }
}

function ensureRoomSoftDeleteSchema(PDO $pdo): void
{
    static $done = [];
    $key = spl_object_id($pdo);
    if (!empty($done[$key])) {
        return;
    }

    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    ensureTableColumns($pdo, 'rooms', [
        'deleted_at' => $driver === 'mysql' ? 'deleted_at DATETIME NULL' : 'deleted_at TEXT NULL',
    ]);
    $done[$key] = true;
}

function ensureRoomOperationsSchema(PDO $pdo): void
{
    static $done = [];
    $key = spl_object_id($pdo);
    if (!empty($done[$key])) {
        return;
    }

    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS room_operations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            occupancy_status ENUM('vacant','occupied','reserved','maintenance','archived') DEFAULT 'vacant',
            tenant_name VARCHAR(120) NULL,
            tenant_phone VARCHAR(30) NULL,
            monthly_rent INT NULL,
            deposit_amount INT NULL,
            service_fee INT NULL,
            contract_start DATE NULL,
            contract_end DATE NULL,
            electric_meter_reading INT NULL,
            water_meter_reading INT NULL,
            room_condition ENUM('ready','issue','maintenance') DEFAULT 'ready',
            issue_note TEXT NULL,
            operation_note TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_room_operations_room (room_id),
            CONSTRAINT fk_room_operations_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS room_invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            billing_month CHAR(7) NOT NULL,
            rent_amount INT NOT NULL DEFAULT 0,
            service_amount INT NOT NULL DEFAULT 0,
            discount_amount INT NOT NULL DEFAULT 0,
            surcharge_amount INT NOT NULL DEFAULT 0,
            amount_paid INT NOT NULL DEFAULT 0,
            electric_reading_old INT NULL,
            electric_reading_new INT NULL,
            electric_units INT NULL,
            electric_amount INT NOT NULL DEFAULT 0,
            water_reading_old INT NULL,
            water_reading_new INT NULL,
            water_units INT NULL,
            water_amount INT NOT NULL DEFAULT 0,
            other_amount INT NOT NULL DEFAULT 0,
            total_amount INT NOT NULL DEFAULT 0,
            due_date DATE NULL,
            issued_at DATETIME NULL,
            sent_at DATETIME NULL,
            paid_date DATE NULL,
            status ENUM('draft','issued','unpaid','partially_paid','paid','overdue','cancelled') DEFAULT 'draft',
            payment_method VARCHAR(80) NULL,
            note TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_room_invoice_month (room_id, billing_month),
            KEY idx_room_invoices_room_status (room_id, status),
            CONSTRAINT fk_room_invoices_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS room_notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            landlord_id INT NOT NULL,
            notice_type ENUM('payment','fee','utilities','rule','general') DEFAULT 'general',
            title VARCHAR(180) NOT NULL,
            content TEXT NOT NULL,
            effective_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_room_notices_room_created (room_id, created_at),
            CONSTRAINT fk_room_notices_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
            CONSTRAINT fk_room_notices_landlord FOREIGN KEY (landlord_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS tenant_issue_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            tenant_user_id INT NULL,
            tenant_name VARCHAR(120) NOT NULL,
            tenant_phone VARCHAR(30) NOT NULL,
            title VARCHAR(180) NULL,
            priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
            content TEXT NOT NULL,
            image_path VARCHAR(255) NULL,
            video_path VARCHAR(255) NULL,
            status ENUM('open','in_progress','waiting_parts','resolved','closed') DEFAULT 'open',
            landlord_note TEXT NULL,
            repair_cost INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_tenant_issue_room_status (room_id, status),
            CONSTRAINT fk_tenant_issue_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
            CONSTRAINT fk_tenant_issue_user FOREIGN KEY (tenant_user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS tenant_stay_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            landlord_id INT NOT NULL,
            tenant_user_id INT NULL,
            tenant_name VARCHAR(120) NOT NULL,
            tenant_phone VARCHAR(30) NOT NULL,
            started_at DATE NOT NULL,
            ended_at DATE NULL,
            rent_amount INT NOT NULL DEFAULT 0,
            deposit_amount INT NOT NULL DEFAULT 0,
            deposit_deduction_amount INT NOT NULL DEFAULT 0,
            deposit_refund_amount INT NOT NULL DEFAULT 0,
            settlement_note TEXT NULL,
            settled_at DATE NULL,
            status ENUM('active','closed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_tenant_stay_room_status (room_id, status),
            KEY idx_tenant_stay_phone (tenant_phone),
            CONSTRAINT fk_tenant_stay_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
            CONSTRAINT fk_tenant_stay_landlord FOREIGN KEY (landlord_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_tenant_stay_user FOREIGN KEY (tenant_user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS room_meter_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            invoice_id INT NULL,
            billing_month CHAR(7) NOT NULL,
            electric_reading_old INT NULL,
            electric_reading_new INT NULL,
            electric_units INT NULL,
            water_reading_old INT NULL,
            water_reading_new INT NULL,
            water_units INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_room_meter_logs_room_month (room_id, billing_month),
            CONSTRAINT fk_room_meter_logs_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
            CONSTRAINT fk_room_meter_logs_invoice FOREIGN KEY (invoice_id) REFERENCES room_invoices(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS room_handover_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            stay_id INT NULL,
            handover_type ENUM('move_in','move_out') DEFAULT 'move_in',
            wall_image VARCHAR(255) NULL,
            bed_image VARCHAR(255) NULL,
            equipment_image VARCHAR(255) NULL,
            note TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_room_handover_room_created (room_id, created_at),
            CONSTRAINT fk_room_handover_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
            CONSTRAINT fk_room_handover_stay FOREIGN KEY (stay_id) REFERENCES tenant_stay_history(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS room_contracts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            landlord_id INT NOT NULL,
            tenant_user_id INT NULL,
            tenant_name VARCHAR(120) NOT NULL,
            tenant_phone VARCHAR(30) NOT NULL,
            contract_code VARCHAR(40) NOT NULL,
            template_name VARCHAR(120) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            monthly_rent INT NOT NULL DEFAULT 0,
            deposit_amount INT NOT NULL DEFAULT 0,
            payment_terms TEXT NULL,
            attachment_path VARCHAR(255) NULL,
            tenant_signed_at DATETIME NULL,
            landlord_signed_at DATETIME NULL,
            status ENUM('draft','pending_signature','active','expiring','ended','renewed') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_room_contracts_room_status (room_id, status),
            KEY idx_room_contracts_landlord (landlord_id),
            CONSTRAINT fk_room_contracts_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
            CONSTRAINT fk_room_contracts_landlord FOREIGN KEY (landlord_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_room_contracts_tenant FOREIGN KEY (tenant_user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS app_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            notification_type VARCHAR(60) NOT NULL,
            title VARCHAR(180) NOT NULL,
            body TEXT NULL,
            link_url VARCHAR(255) NULL,
            entity_type VARCHAR(60) NULL,
            entity_id INT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at DATETIME NULL,
            KEY idx_app_notifications_user_created (user_id, created_at),
            CONSTRAINT fk_app_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $pdo->exec('CREATE TABLE IF NOT EXISTS room_operations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id INTEGER NOT NULL UNIQUE,
            occupancy_status TEXT DEFAULT "vacant",
            tenant_name TEXT NULL,
            tenant_phone TEXT NULL,
            monthly_rent INTEGER NULL,
            deposit_amount INTEGER NULL,
            service_fee INTEGER NULL,
            contract_start TEXT NULL,
            contract_end TEXT NULL,
            electric_meter_reading INTEGER NULL,
            water_meter_reading INTEGER NULL,
            room_condition TEXT DEFAULT "ready",
            issue_note TEXT NULL,
            operation_note TEXT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS room_invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id INTEGER NOT NULL,
            billing_month TEXT NOT NULL,
            rent_amount INTEGER NOT NULL DEFAULT 0,
            service_amount INTEGER NOT NULL DEFAULT 0,
            discount_amount INTEGER NOT NULL DEFAULT 0,
            surcharge_amount INTEGER NOT NULL DEFAULT 0,
            amount_paid INTEGER NOT NULL DEFAULT 0,
            electric_reading_old INTEGER NULL,
            electric_reading_new INTEGER NULL,
            electric_units INTEGER NULL,
            electric_amount INTEGER NOT NULL DEFAULT 0,
            water_reading_old INTEGER NULL,
            water_reading_new INTEGER NULL,
            water_units INTEGER NULL,
            water_amount INTEGER NOT NULL DEFAULT 0,
            other_amount INTEGER NOT NULL DEFAULT 0,
            total_amount INTEGER NOT NULL DEFAULT 0,
            due_date TEXT NULL,
            issued_at TEXT NULL,
            sent_at TEXT NULL,
            paid_date TEXT NULL,
            status TEXT DEFAULT "draft",
            payment_method TEXT NULL,
            note TEXT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(room_id, billing_month)
        )');

        try { $pdo->exec('CREATE INDEX idx_room_invoices_room_status ON room_invoices(room_id, status)'); } catch (PDOException $e) { /* ignore */ }

        $pdo->exec('CREATE TABLE IF NOT EXISTS room_notices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id INTEGER NOT NULL,
            landlord_id INTEGER NOT NULL,
            notice_type TEXT DEFAULT "general",
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            effective_date TEXT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS tenant_issue_reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id INTEGER NOT NULL,
            tenant_user_id INTEGER NULL,
            tenant_name TEXT NOT NULL,
            tenant_phone TEXT NOT NULL,
            title TEXT NULL,
            priority TEXT DEFAULT "normal",
            content TEXT NOT NULL,
            image_path TEXT NULL,
            video_path TEXT NULL,
            status TEXT DEFAULT "open",
            landlord_note TEXT NULL,
            repair_cost INTEGER NOT NULL DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS tenant_stay_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id INTEGER NOT NULL,
            landlord_id INTEGER NOT NULL,
            tenant_user_id INTEGER NULL,
            tenant_name TEXT NOT NULL,
            tenant_phone TEXT NOT NULL,
            started_at TEXT NOT NULL,
            ended_at TEXT NULL,
            rent_amount INTEGER NOT NULL DEFAULT 0,
            deposit_amount INTEGER NOT NULL DEFAULT 0,
            deposit_deduction_amount INTEGER NOT NULL DEFAULT 0,
            deposit_refund_amount INTEGER NOT NULL DEFAULT 0,
            settlement_note TEXT NULL,
            settled_at TEXT NULL,
            status TEXT DEFAULT "active",
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS room_meter_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id INTEGER NOT NULL,
            invoice_id INTEGER NULL,
            billing_month TEXT NOT NULL,
            electric_reading_old INTEGER NULL,
            electric_reading_new INTEGER NULL,
            electric_units INTEGER NULL,
            water_reading_old INTEGER NULL,
            water_reading_new INTEGER NULL,
            water_units INTEGER NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS room_handover_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id INTEGER NOT NULL,
            stay_id INTEGER NULL,
            handover_type TEXT DEFAULT "move_in",
            wall_image TEXT NULL,
            bed_image TEXT NULL,
            equipment_image TEXT NULL,
            note TEXT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS room_contracts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id INTEGER NOT NULL,
            landlord_id INTEGER NOT NULL,
            tenant_user_id INTEGER NULL,
            tenant_name TEXT NOT NULL,
            tenant_phone TEXT NOT NULL,
            contract_code TEXT NOT NULL,
            template_name TEXT NOT NULL,
            start_date TEXT NOT NULL,
            end_date TEXT NOT NULL,
            monthly_rent INTEGER NOT NULL DEFAULT 0,
            deposit_amount INTEGER NOT NULL DEFAULT 0,
            payment_terms TEXT NULL,
            attachment_path TEXT NULL,
            tenant_signed_at TEXT NULL,
            landlord_signed_at TEXT NULL,
            status TEXT DEFAULT "draft",
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS app_notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            notification_type TEXT NOT NULL,
            title TEXT NOT NULL,
            body TEXT NULL,
            link_url TEXT NULL,
            entity_type TEXT NULL,
            entity_id INTEGER NULL,
            is_read INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            read_at TEXT NULL
        )');

        try { $pdo->exec('CREATE INDEX idx_room_notices_room_created ON room_notices(room_id, created_at)'); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec('CREATE INDEX idx_tenant_issue_room_status ON tenant_issue_reports(room_id, status)'); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec('CREATE INDEX idx_tenant_stay_room_status ON tenant_stay_history(room_id, status)'); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec('CREATE INDEX idx_tenant_stay_phone ON tenant_stay_history(tenant_phone)'); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec('CREATE INDEX idx_room_meter_logs_room_month ON room_meter_logs(room_id, billing_month)'); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec('CREATE INDEX idx_room_handover_room_created ON room_handover_records(room_id, created_at)'); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec('CREATE INDEX idx_room_contracts_room_status ON room_contracts(room_id, status)'); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec('CREATE INDEX idx_room_contracts_landlord ON room_contracts(landlord_id)'); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec('CREATE INDEX idx_app_notifications_user_created ON app_notifications(user_id, created_at)'); } catch (PDOException $e) { /* ignore */ }
    }

    if ($driver === 'mysql') {
        ensureTableColumns($pdo, 'room_operations', [
            'service_fee' => 'service_fee INT NULL',
        ]);
        ensureTableColumns($pdo, 'room_invoices', [
            'service_amount' => 'service_amount INT NOT NULL DEFAULT 0',
            'discount_amount' => 'discount_amount INT NOT NULL DEFAULT 0',
            'surcharge_amount' => 'surcharge_amount INT NOT NULL DEFAULT 0',
            'amount_paid' => 'amount_paid INT NOT NULL DEFAULT 0',
            'issued_at' => 'issued_at DATETIME NULL',
            'sent_at' => 'sent_at DATETIME NULL',
            'payment_method' => 'payment_method VARCHAR(80) NULL',
        ]);
        ensureTableColumns($pdo, 'tenant_issue_reports', [
            'title' => 'title VARCHAR(180) NULL',
            'video_path' => 'video_path VARCHAR(255) NULL',
            'repair_cost' => 'repair_cost INT NOT NULL DEFAULT 0',
        ]);
        ensureTableColumns($pdo, 'room_contracts', [
            'attachment_path' => 'attachment_path VARCHAR(255) NULL',
        ]);
        ensureTableColumns($pdo, 'app_notifications', [
            'read_at' => 'read_at DATETIME NULL',
        ]);
    } else {
        ensureTableColumns($pdo, 'room_operations', [
            'service_fee' => 'service_fee INTEGER NULL',
        ]);
        ensureTableColumns($pdo, 'room_invoices', [
            'service_amount' => 'service_amount INTEGER NOT NULL DEFAULT 0',
            'discount_amount' => 'discount_amount INTEGER NOT NULL DEFAULT 0',
            'surcharge_amount' => 'surcharge_amount INTEGER NOT NULL DEFAULT 0',
            'amount_paid' => 'amount_paid INTEGER NOT NULL DEFAULT 0',
            'issued_at' => 'issued_at TEXT NULL',
            'sent_at' => 'sent_at TEXT NULL',
            'payment_method' => 'payment_method TEXT NULL',
        ]);
        ensureTableColumns($pdo, 'tenant_issue_reports', [
            'title' => 'title TEXT NULL',
            'video_path' => 'video_path TEXT NULL',
            'repair_cost' => 'repair_cost INTEGER NOT NULL DEFAULT 0',
        ]);
        ensureTableColumns($pdo, 'room_contracts', [
            'attachment_path' => 'attachment_path TEXT NULL',
        ]);
        ensureTableColumns($pdo, 'app_notifications', [
            'read_at' => 'read_at TEXT NULL',
        ]);
    }

    if ($driver === 'mysql') {
        try { $pdo->exec("ALTER TABLE room_operations MODIFY occupancy_status ENUM('vacant','occupied','reserved','maintenance','archived') DEFAULT 'vacant'"); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec("ALTER TABLE room_invoices MODIFY status ENUM('draft','issued','unpaid','partially_paid','paid','overdue','cancelled') DEFAULT 'draft'"); } catch (PDOException $e) { /* ignore */ }
        try { $pdo->exec("ALTER TABLE tenant_issue_reports MODIFY status ENUM('open','in_progress','waiting_parts','resolved','closed') DEFAULT 'open'"); } catch (PDOException $e) { /* ignore */ }
    }

    try { $pdo->exec("UPDATE tenant_issue_reports SET status = 'open' WHERE status = 'new'"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("UPDATE room_invoices SET status = 'unpaid' WHERE status = 'issued' AND COALESCE(amount_paid, 0) = 0"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("UPDATE room_invoices SET status = 'partially_paid' WHERE COALESCE(amount_paid, 0) > 0 AND COALESCE(amount_paid, 0) < COALESCE(total_amount, 0)"); } catch (PDOException $e) { /* ignore */ }
    try { $pdo->exec("UPDATE room_invoices SET status = 'paid' WHERE COALESCE(amount_paid, 0) >= COALESCE(total_amount, 0) AND COALESCE(total_amount, 0) > 0"); } catch (PDOException $e) { /* ignore */ }

    $done[$key] = true;
}

function roomOperationStatusOptions(): array
{
    return [
        'vacant' => 'Available / Phòng trống',
        'occupied' => 'Đã có người thuê',
        'reserved' => 'Đã giữ chỗ',
        'maintenance' => 'Đang bảo trì',
        'archived' => 'Đã lưu trữ',
    ];
}

function roomConditionOptions(): array
{
    return [
        'ready' => 'Ổn định',
        'issue' => 'Có sự cố',
        'maintenance' => 'Đang sửa chữa',
    ];
}

function roomNoticeTypeOptions(): array
{
    return [
        'payment' => 'Nhắc thanh toán',
        'fee' => 'Điều chỉnh phí',
        'utilities' => 'Điện nước / bảo trì',
        'rule' => 'Nội quy',
        'general' => 'Thông báo chung',
    ];
}

function tenantIssuePriorityOptions(): array
{
    return [
        'low' => 'Thấp',
        'normal' => 'Bình thường',
        'high' => 'Cao',
        'urgent' => 'Khẩn',
    ];
}

function tenantIssueStatusOptions(): array
{
    return [
        'open' => 'Mới tạo',
        'in_progress' => 'Đang xử lý',
        'waiting_parts' => 'Chờ vật tư',
        'resolved' => 'Đã xử lý',
        'closed' => 'Đã đóng',
    ];
}

function roomInvoiceStatusOptions(): array
{
    return [
        'draft' => 'Bản nháp',
        'issued' => 'Đã phát hành',
        'unpaid' => 'Chưa thanh toán',
        'partially_paid' => 'Thanh toán một phần',
        'paid' => 'Đã thanh toán',
        'overdue' => 'Quá hạn',
        'cancelled' => 'Đã huỷ',
    ];
}

function roomContractStatusOptions(): array
{
    return [
        'draft' => 'Bản nháp',
        'pending_signature' => 'Chờ ký điện tử',
        'active' => 'Đang hiệu lực',
        'expiring' => 'Sắp hết hạn',
        'ended' => 'Đã kết thúc',
        'renewed' => 'Đã gia hạn',
    ];
}

function roomHandoverTypeOptions(): array
{
    return [
        'move_in' => 'Nhận phòng',
        'move_out' => 'Trả phòng',
    ];
}

function normalizeRoomOccupancyStatus(?string $status): string
{
    $status = trim((string)$status);
    if ($status === 'available') {
        return 'vacant';
    }
    return isset(roomOperationStatusOptions()[$status]) ? $status : 'vacant';
}

function normalizeTenantIssueStatus(?string $status): string
{
    $status = trim((string)$status);
    if ($status === 'new') {
        $status = 'open';
    }
    return isset(tenantIssueStatusOptions()[$status]) ? $status : 'open';
}

function normalizeRoomInvoiceStatus(?string $status): string
{
    $status = trim((string)$status);
    return isset(roomInvoiceStatusOptions()[$status]) ? $status : 'draft';
}

function generateRoomContractCode(int $roomId): string
{
    return 'CTR-' . $roomId . '-' . strtoupper(substr(md5((string)microtime(true) . '-' . $roomId), 0, 6));
}

function roomContractStatusByDates(array $contract): string
{
    $status = trim((string)($contract['status'] ?? 'draft'));
    if ($status === 'renewed' || $status === 'ended') {
        return $status;
    }

    $tenantSignedAt = trim((string)($contract['tenant_signed_at'] ?? ''));
    $landlordSignedAt = trim((string)($contract['landlord_signed_at'] ?? ''));
    if ($tenantSignedAt === '' || $landlordSignedAt === '') {
        return 'pending_signature';
    }

    $endDate = trim((string)($contract['end_date'] ?? ''));
    if ($endDate !== '') {
        $daysLeft = daysUntilDate($endDate);
        if ($daysLeft !== null && $daysLeft < 0) {
            return 'ended';
        }
        if ($daysLeft !== null && $daysLeft <= 30) {
            return 'expiring';
        }
    }

    return 'active';
}

function createAppNotification(int $userId, string $type, string $title, string $body = '', string $linkUrl = '', ?string $entityType = null, ?int $entityId = null): int
{
    if ($userId <= 0 || trim($title) === '') {
        return 0;
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('INSERT INTO app_notifications (
            user_id, notification_type, title, body, link_url, entity_type, entity_id
        ) VALUES (
            :user_id, :notification_type, :title, :body, :link_url, :entity_type, :entity_id
        )');
    $stmt->execute([
        ':user_id' => $userId,
        ':notification_type' => trim($type) !== '' ? trim($type) : 'general',
        ':title' => trim($title),
        ':body' => trim($body),
        ':link_url' => trim($linkUrl) !== '' ? trim($linkUrl) : null,
        ':entity_type' => $entityType !== null && trim($entityType) !== '' ? trim($entityType) : null,
        ':entity_id' => $entityId,
    ]);
    return (int)$pdo->lastInsertId();
}

function unreadNotificationCountByUser(int $userId): int
{
    if ($userId <= 0) {
        return 0;
    }
    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM app_notifications WHERE user_id = :user_id AND is_read = 0');
    $stmt->execute([':user_id' => $userId]);
    return (int)$stmt->fetchColumn();
}

function markNotificationReadState(int $notificationId, int $userId, bool $isRead): bool
{
    if ($notificationId <= 0 || $userId <= 0) {
        return false;
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('UPDATE app_notifications
        SET is_read = :is_read,
            read_at = :read_at
        WHERE id = :id AND user_id = :user_id');
    $stmt->execute([
        ':is_read' => $isRead ? 1 : 0,
        ':read_at' => $isRead ? date('Y-m-d H:i:s') : null,
        ':id' => $notificationId,
        ':user_id' => $userId,
    ]);
    return $stmt->rowCount() > 0;
}

function markAllNotificationsRead(int $userId): int
{
    if ($userId <= 0) {
        return 0;
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('UPDATE app_notifications
        SET is_read = 1,
            read_at = :read_at
        WHERE user_id = :user_id AND is_read = 0');
    $stmt->execute([
        ':read_at' => date('Y-m-d H:i:s'),
        ':user_id' => $userId,
    ]);
    return (int)$stmt->rowCount();
}

function notificationCenterByUser(array $user, int $limit = 60): array
{
    $userId = (int)($user['id'] ?? 0);
    if ($userId <= 0) {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT *
        FROM app_notifications
        WHERE user_id = :user_id
        ORDER BY created_at DESC, id DESC
        LIMIT :limit_rows');
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function notifyTenantForRoom(int $roomId, string $type, string $title, string $body = '', string $linkUrl = '', ?int $entityId = null): void
{
    if ($roomId <= 0) {
        return;
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT tenant_user_id
        FROM tenant_stay_history
        WHERE room_id = :room_id AND status = :status
        ORDER BY started_at DESC, id DESC
        LIMIT 1');
    $stmt->execute([
        ':room_id' => $roomId,
        ':status' => 'active',
    ]);
    $tenantUserId = (int)$stmt->fetchColumn();
    if ($tenantUserId <= 0) {
        return;
    }
    createAppNotification($tenantUserId, $type, $title, $body, $linkUrl, $type, $entityId);
}

function notifyLandlordByRoom(int $roomId, string $type, string $title, string $body = '', string $linkUrl = '', ?int $entityId = null): void
{
    if ($roomId <= 0) {
        return;
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT landlord_id FROM rooms WHERE id = :room_id LIMIT 1');
    $stmt->execute([':room_id' => $roomId]);
    $landlordId = (int)$stmt->fetchColumn();
    if ($landlordId <= 0) {
        return;
    }
    createAppNotification($landlordId, $type, $title, $body, $linkUrl, $type, $entityId);
}

function roomContractsByRoom(int $roomId, int $landlordId): array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT *
        FROM room_contracts
        WHERE room_id = :room_id AND landlord_id = :landlord_id
        ORDER BY start_date DESC, id DESC');
    $stmt->execute([
        ':room_id' => $roomId,
        ':landlord_id' => $landlordId,
    ]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['status_effective'] = roomContractStatusByDates($row);
    }
    unset($row);
    return $rows;
}

function activeRoomContractByRoom(int $roomId, int $landlordId): ?array
{
    $contracts = roomContractsByRoom($roomId, $landlordId);
    foreach ($contracts as $contract) {
        if (in_array((string)($contract['status_effective'] ?? ''), ['active', 'expiring', 'pending_signature', 'draft'], true)) {
            return $contract;
        }
    }
    return $contracts[0] ?? null;
}

function roomContractsForTenant(int $roomId, string $tenantPhone): array
{
    $tenantPhone = trim($tenantPhone);
    if ($roomId <= 0 || $tenantPhone === '') {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT *
        FROM room_contracts
        WHERE room_id = :room_id AND tenant_phone = :tenant_phone
        ORDER BY start_date DESC, id DESC');
    $stmt->execute([
        ':room_id' => $roomId,
        ':tenant_phone' => $tenantPhone,
    ]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['status_effective'] = roomContractStatusByDates($row);
    }
    unset($row);
    return $rows;
}

function roomOperationProfileDefaults(array $room): array
{
    return [
        'room_id' => (int)($room['id'] ?? 0),
        'occupancy_status' => 'vacant',
        'tenant_name' => '',
        'tenant_phone' => '',
        'monthly_rent' => isset($room['price']) ? (int)$room['price'] : null,
        'deposit_amount' => null,
        'service_fee' => 0,
        'contract_start' => null,
        'contract_end' => null,
        'electric_meter_reading' => null,
        'water_meter_reading' => null,
        'room_condition' => 'ready',
        'issue_note' => '',
        'operation_note' => '',
        'created_at' => null,
        'updated_at' => null,
    ];
}

function normalizeOptionalDate(?string $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return null;
    }
    return date('Y-m-d', $ts);
}

function roomOperationProfile(int $roomId, int $landlordId): ?array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return null;
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT * FROM room_operations WHERE room_id = :room LIMIT 1');
    $stmt->execute([':room' => $roomId]);
    $row = $stmt->fetch() ?: [];

    return array_merge(roomOperationProfileDefaults($room), $row ?: []);
}

function saveRoomOperationProfile(int $roomId, int $landlordId, array $data): bool
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return false;
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);

    $occupancyOptions = roomOperationStatusOptions();
    $conditionOptions = roomConditionOptions();
    $occupancyStatus = normalizeRoomOccupancyStatus((string)($data['occupancy_status'] ?? 'vacant'));
    $roomCondition = (string)($data['room_condition'] ?? 'ready');
    if (!isset($occupancyOptions[$occupancyStatus])) {
        $occupancyStatus = 'vacant';
    }
    if (!isset($conditionOptions[$roomCondition])) {
        $roomCondition = 'ready';
    }

    $existingProfile = roomOperationProfile($roomId, $landlordId) ?? roomOperationProfileDefaults($room);

    $payload = [
        ':room_id' => $roomId,
        ':occupancy_status' => $occupancyStatus,
        ':tenant_name' => trim((string)($data['tenant_name'] ?? '')),
        ':tenant_phone' => trim((string)($data['tenant_phone'] ?? '')),
        ':monthly_rent' => isset($data['monthly_rent']) && $data['monthly_rent'] !== '' ? max(0, (int)$data['monthly_rent']) : null,
        ':deposit_amount' => isset($data['deposit_amount']) && $data['deposit_amount'] !== '' ? max(0, (int)$data['deposit_amount']) : null,
        ':service_fee' => isset($data['service_fee']) && $data['service_fee'] !== '' ? max(0, (int)$data['service_fee']) : 0,
        ':contract_start' => normalizeOptionalDate($data['contract_start'] ?? null),
        ':contract_end' => normalizeOptionalDate($data['contract_end'] ?? null),
        ':electric_meter_reading' => isset($data['electric_meter_reading']) && $data['electric_meter_reading'] !== '' ? max(0, (int)$data['electric_meter_reading']) : null,
        ':water_meter_reading' => isset($data['water_meter_reading']) && $data['water_meter_reading'] !== '' ? max(0, (int)$data['water_meter_reading']) : null,
        ':room_condition' => $roomCondition,
        ':issue_note' => trim((string)($data['issue_note'] ?? '')),
        ':operation_note' => trim((string)($data['operation_note'] ?? '')),
    ];

    $existsStmt = $pdo->prepare('SELECT id FROM room_operations WHERE room_id = :room LIMIT 1');
    $existsStmt->execute([':room' => $roomId]);
    $existingId = (int)$existsStmt->fetchColumn();

    if ($existingId > 0) {
        $sql = 'UPDATE room_operations
                SET occupancy_status = :occupancy_status,
                    tenant_name = :tenant_name,
                    tenant_phone = :tenant_phone,
                    monthly_rent = :monthly_rent,
                    deposit_amount = :deposit_amount,
                    service_fee = :service_fee,
                    contract_start = :contract_start,
                    contract_end = :contract_end,
                    electric_meter_reading = :electric_meter_reading,
                    water_meter_reading = :water_meter_reading,
                    room_condition = :room_condition,
                    issue_note = :issue_note,
                    operation_note = :operation_note,
                    updated_at = CURRENT_TIMESTAMP
                WHERE room_id = :room_id';
    } else {
        $sql = 'INSERT INTO room_operations (
                    room_id, occupancy_status, tenant_name, tenant_phone, monthly_rent, deposit_amount, service_fee,
                    contract_start, contract_end, electric_meter_reading, water_meter_reading,
                    room_condition, issue_note, operation_note
                ) VALUES (
                    :room_id, :occupancy_status, :tenant_name, :tenant_phone, :monthly_rent, :deposit_amount, :service_fee,
                    :contract_start, :contract_end, :electric_meter_reading, :water_meter_reading,
                    :room_condition, :issue_note, :operation_note
                )';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($payload);
    syncTenantStayHistory($room, $landlordId, $existingProfile, [
        'occupancy_status' => $occupancyStatus,
        'tenant_name' => (string)$payload[':tenant_name'],
        'tenant_phone' => (string)$payload[':tenant_phone'],
        'monthly_rent' => $payload[':monthly_rent'],
        'deposit_amount' => $payload[':deposit_amount'],
        'service_fee' => $payload[':service_fee'],
        'contract_start' => $payload[':contract_start'],
        'contract_end' => $payload[':contract_end'],
    ]);

    $previousStatus = normalizeRoomOccupancyStatus((string)($existingProfile['occupancy_status'] ?? 'vacant'));
    if ($previousStatus !== $occupancyStatus) {
        $statusLabel = $occupancyOptions[$occupancyStatus] ?? $occupancyStatus;
        notifyTenantForRoom(
            $roomId,
            'room_status',
            'Trạng thái phòng đã thay đổi',
            'Phòng hiện ở trạng thái: ' . $statusLabel,
            '?route=my-stay',
            $roomId
        );
    }

    $contractEnd = $payload[':contract_end'];
    $daysLeft = $contractEnd ? daysUntilDate((string)$contractEnd) : null;
    if ($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 30) {
        notifyTenantForRoom(
            $roomId,
            'contract_expiring',
            'Hợp đồng sắp hết hạn',
            'Hợp đồng hiện tại còn ' . $daysLeft . ' ngày.',
            '?route=my-stay#my-contract',
            $roomId
        );
        notifyLandlordByRoom(
            $roomId,
            'contract_expiring',
            'Có hợp đồng sắp hết hạn',
            'Phòng #' . $roomId . ' còn ' . $daysLeft . ' ngày tới hạn kết thúc hợp đồng.',
            '?route=room-ops&id=' . $roomId . '#ops-contract',
            $roomId
        );
    }
    return true;
}

function roomInvoiceDisplayStatus(array $invoice): string
{
    $status = normalizeRoomInvoiceStatus((string)($invoice['status'] ?? 'draft'));
    $totalAmount = max(0, (int)($invoice['total_amount'] ?? 0));
    $amountPaid = max(0, (int)($invoice['amount_paid'] ?? 0));

    if ($status === 'cancelled' || $status === 'draft') {
        return $status;
    }
    if ($totalAmount > 0 && $amountPaid >= $totalAmount) {
        return 'paid';
    }
    if ($amountPaid > 0 && $amountPaid < $totalAmount) {
        $dueDate = trim((string)($invoice['due_date'] ?? ''));
        if ($dueDate !== '') {
            $dueTs = strtotime($dueDate . ' 23:59:59');
            if ($dueTs !== false && $dueTs < time()) {
                return 'overdue';
            }
        }
        return 'partially_paid';
    }

    $dueDate = trim((string)($invoice['due_date'] ?? ''));
    if ($dueDate !== '') {
        $dueTs = strtotime($dueDate . ' 23:59:59');
        if ($dueTs !== false && $dueTs < time()) {
            return 'overdue';
        }
    }

    return $status === 'issued' ? 'issued' : 'unpaid';
}

function roomInvoicesByRoom(int $roomId, int $landlordId): array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT * FROM room_invoices WHERE room_id = :room ORDER BY billing_month DESC, created_at DESC');
    $stmt->execute([':room' => $roomId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row = enrichInvoiceDisplayState($row);
    }
    unset($row);

    return $rows;
}

function createRoomInvoice(int $roomId, int $landlordId, array $data): array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return ['ok' => false, 'error' => 'Không tìm thấy phòng cần lập hoá đơn.'];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $profile = roomOperationProfile($roomId, $landlordId) ?? roomOperationProfileDefaults($room);

    $billingMonth = trim((string)($data['billing_month'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}$/', $billingMonth)) {
        return ['ok' => false, 'error' => 'Tháng hoá đơn không hợp lệ.'];
    }

    $exists = $pdo->prepare('SELECT id FROM room_invoices WHERE room_id = :room AND billing_month = :month LIMIT 1');
    $exists->execute([':room' => $roomId, ':month' => $billingMonth]);
    if ($exists->fetchColumn()) {
        return ['ok' => false, 'error' => 'Hoá đơn tháng này đã tồn tại.'];
    }

    $rentAmount = isset($data['rent_amount']) && $data['rent_amount'] !== ''
        ? max(0, (int)$data['rent_amount'])
        : max(0, (int)($profile['monthly_rent'] ?? $room['price'] ?? 0));
    $serviceAmount = isset($data['service_amount']) && $data['service_amount'] !== ''
        ? max(0, (int)$data['service_amount'])
        : max(0, (int)($profile['service_fee'] ?? 0));
    $discountAmount = isset($data['discount_amount']) && $data['discount_amount'] !== ''
        ? max(0, (int)$data['discount_amount'])
        : 0;
    $surchargeAmount = isset($data['surcharge_amount']) && $data['surcharge_amount'] !== ''
        ? max(0, (int)$data['surcharge_amount'])
        : 0;
    $otherAmount = isset($data['other_amount']) && $data['other_amount'] !== ''
        ? max(0, (int)$data['other_amount'])
        : 0;
    $initialStatus = normalizeRoomInvoiceStatus((string)($data['status'] ?? 'issued'));

    $electricOld = isset($profile['electric_meter_reading']) && $profile['electric_meter_reading'] !== null
        ? (int)$profile['electric_meter_reading']
        : null;
    $waterOld = isset($profile['water_meter_reading']) && $profile['water_meter_reading'] !== null
        ? (int)$profile['water_meter_reading']
        : null;

    $electricNew = isset($data['electric_reading_new']) && $data['electric_reading_new'] !== ''
        ? max(0, (int)$data['electric_reading_new'])
        : null;
    $waterNew = isset($data['water_reading_new']) && $data['water_reading_new'] !== ''
        ? max(0, (int)$data['water_reading_new'])
        : null;

    if ($electricOld !== null && $electricNew !== null && $electricNew < $electricOld) {
        return ['ok' => false, 'error' => 'Chỉ số điện mới phải lớn hơn hoặc bằng chỉ số cũ.'];
    }
    if ($waterOld !== null && $waterNew !== null && $waterNew < $waterOld) {
        return ['ok' => false, 'error' => 'Chỉ số nước mới phải lớn hơn hoặc bằng chỉ số cũ.'];
    }

    $electricUnits = ($electricOld !== null && $electricNew !== null) ? max(0, $electricNew - $electricOld) : null;
    $waterUnits = ($waterOld !== null && $waterNew !== null) ? max(0, $waterNew - $waterOld) : null;
    $electricAmount = $electricUnits !== null ? $electricUnits * (int)($room['electric_price'] ?? 0) : 0;
    $waterAmount = $waterUnits !== null ? $waterUnits * (int)($room['water_price'] ?? 0) : 0;
    $totalAmount = max(0, $rentAmount + $serviceAmount + $electricAmount + $waterAmount + $otherAmount + $surchargeAmount - $discountAmount);
    $issuedAt = $initialStatus === 'draft' ? null : date('Y-m-d H:i:s');
    $sentAt = $initialStatus === 'draft' ? null : date('Y-m-d H:i:s');

    $payload = [
        ':room_id' => $roomId,
        ':billing_month' => $billingMonth,
        ':rent_amount' => $rentAmount,
        ':service_amount' => $serviceAmount,
        ':discount_amount' => $discountAmount,
        ':surcharge_amount' => $surchargeAmount,
        ':amount_paid' => 0,
        ':electric_reading_old' => $electricOld,
        ':electric_reading_new' => $electricNew,
        ':electric_units' => $electricUnits,
        ':electric_amount' => $electricAmount,
        ':water_reading_old' => $waterOld,
        ':water_reading_new' => $waterNew,
        ':water_units' => $waterUnits,
        ':water_amount' => $waterAmount,
        ':other_amount' => $otherAmount,
        ':total_amount' => $totalAmount,
        ':due_date' => normalizeOptionalDate($data['due_date'] ?? null),
        ':issued_at' => $issuedAt,
        ':sent_at' => $sentAt,
        ':status' => $initialStatus === 'draft' ? 'draft' : ($initialStatus === 'issued' ? 'issued' : $initialStatus),
        ':note' => trim((string)($data['note'] ?? '')),
    ];

    $stmt = $pdo->prepare('INSERT INTO room_invoices (
            room_id, billing_month, rent_amount, service_amount,
            discount_amount, surcharge_amount, amount_paid,
            electric_reading_old, electric_reading_new, electric_units, electric_amount,
            water_reading_old, water_reading_new, water_units, water_amount,
            other_amount, total_amount, due_date, issued_at, sent_at, status, note
        ) VALUES (
            :room_id, :billing_month, :rent_amount, :service_amount,
            :discount_amount, :surcharge_amount, :amount_paid,
            :electric_reading_old, :electric_reading_new, :electric_units, :electric_amount,
            :water_reading_old, :water_reading_new, :water_units, :water_amount,
            :other_amount, :total_amount, :due_date, :issued_at, :sent_at, :status, :note
        )');
    $stmt->execute($payload);
    $invoiceId = (int)$pdo->lastInsertId();

    if ($electricNew !== null || $waterNew !== null) {
        saveRoomOperationProfile($roomId, $landlordId, [
            'occupancy_status' => $profile['occupancy_status'] ?? 'vacant',
            'tenant_name' => $profile['tenant_name'] ?? '',
            'tenant_phone' => $profile['tenant_phone'] ?? '',
            'monthly_rent' => $profile['monthly_rent'] ?? $room['price'] ?? null,
            'deposit_amount' => $profile['deposit_amount'] ?? null,
            'service_fee' => $profile['service_fee'] ?? 0,
            'contract_start' => $profile['contract_start'] ?? null,
            'contract_end' => $profile['contract_end'] ?? null,
            'electric_meter_reading' => $electricNew !== null ? $electricNew : ($profile['electric_meter_reading'] ?? null),
            'water_meter_reading' => $waterNew !== null ? $waterNew : ($profile['water_meter_reading'] ?? null),
            'room_condition' => $profile['room_condition'] ?? 'ready',
            'issue_note' => $profile['issue_note'] ?? '',
            'operation_note' => $profile['operation_note'] ?? '',
        ]);
    }

    $meterStmt = $pdo->prepare('INSERT INTO room_meter_logs (
            room_id, invoice_id, billing_month,
            electric_reading_old, electric_reading_new, electric_units,
            water_reading_old, water_reading_new, water_units
        ) VALUES (
            :room_id, :invoice_id, :billing_month,
            :electric_reading_old, :electric_reading_new, :electric_units,
            :water_reading_old, :water_reading_new, :water_units
        )');
    $meterStmt->execute([
        ':room_id' => $roomId,
        ':invoice_id' => $invoiceId,
        ':billing_month' => $billingMonth,
        ':electric_reading_old' => $electricOld,
        ':electric_reading_new' => $electricNew,
        ':electric_units' => $electricUnits,
        ':water_reading_old' => $waterOld,
        ':water_reading_new' => $waterNew,
        ':water_units' => $waterUnits,
    ]);

    $noticeLines = [
        'Tiền phòng: ' . number_format($rentAmount, 0, ',', '.') . ' đ',
        'Phí dịch vụ: ' . number_format($serviceAmount, 0, ',', '.') . ' đ',
        'Tiền điện: ' . number_format($electricAmount, 0, ',', '.') . ' đ',
        'Tiền nước: ' . number_format($waterAmount, 0, ',', '.') . ' đ',
    ];
    if ($surchargeAmount > 0) {
        $noticeLines[] = 'Phụ phí: ' . number_format($surchargeAmount, 0, ',', '.') . ' đ';
    }
    if ($discountAmount > 0) {
        $noticeLines[] = 'Giảm giá: -' . number_format($discountAmount, 0, ',', '.') . ' đ';
    }
    if ($otherAmount > 0) {
        $noticeLines[] = 'Phát sinh khác: ' . number_format($otherAmount, 0, ',', '.') . ' đ';
    }
    $noticeLines[] = 'Tổng cộng: ' . number_format($totalAmount, 0, ',', '.') . ' đ';
    if (!empty($payload[':due_date'])) {
        $noticeLines[] = 'Hạn thanh toán: ' . $payload[':due_date'];
    }
    if ($initialStatus !== 'draft') {
        createRoomNotice($roomId, $landlordId, [
            'notice_type' => 'payment',
            'title' => 'Hóa đơn ' . $billingMonth . ' đã được tạo',
            'content' => implode("\n", $noticeLines),
            'effective_date' => $payload[':due_date'],
        ]);
        notifyTenantForRoom(
            $roomId,
            'invoice_created',
            'Có hóa đơn mới cho kỳ ' . $billingMonth,
            'Tổng cần thanh toán: ' . number_format($totalAmount, 0, ',', '.') . ' đ',
            '?route=my-stay&section=invoices#my-invoices',
            $invoiceId
        );
    }

    return ['ok' => true, 'invoice_id' => $invoiceId];
}

function updateRoomInvoicePaymentStatus(int $invoiceId, int $landlordId, $statusPayload): bool
{
    $status = is_array($statusPayload)
        ? normalizeRoomInvoiceStatus((string)($statusPayload['status'] ?? 'unpaid'))
        : normalizeRoomInvoiceStatus((string)$statusPayload);
    $amountPaidInput = is_array($statusPayload) ? ($statusPayload['amount_paid'] ?? null) : null;
    $paymentMethod = is_array($statusPayload) ? trim((string)($statusPayload['payment_method'] ?? '')) : '';
    $allowed = array_keys(roomInvoiceStatusOptions());
    if (!in_array($status, $allowed, true)) {
        return false;
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT ri.*
        FROM room_invoices ri
        JOIN rooms r ON r.id = ri.room_id
        WHERE ri.id = :invoice AND r.landlord_id = :landlord
        LIMIT 1');
    $stmt->execute([':invoice' => $invoiceId, ':landlord' => $landlordId]);
    $invoice = $stmt->fetch();
    if (!$invoice) {
        return false;
    }

    $totalAmount = max(0, (int)($invoice['total_amount'] ?? 0));
    $currentAmountPaid = max(0, (int)($invoice['amount_paid'] ?? 0));
    $amountPaid = $amountPaidInput !== null && $amountPaidInput !== ''
        ? max(0, (int)$amountPaidInput)
        : $currentAmountPaid;

    if ($status === 'paid') {
        $amountPaid = $totalAmount;
    } elseif ($status === 'unpaid' || $status === 'draft' || $status === 'issued' || $status === 'cancelled') {
        if ($status !== 'partially_paid') {
            $amountPaid = $status === 'cancelled' ? 0 : max(0, min($amountPaid, $totalAmount));
        }
    }

    if ($amountPaid > 0 && $amountPaid < $totalAmount && in_array($status, ['draft', 'issued', 'unpaid', 'overdue'], true)) {
        $status = 'partially_paid';
    }
    if ($totalAmount > 0 && $amountPaid >= $totalAmount && $status !== 'cancelled') {
        $status = 'paid';
    }
    if ($status === 'partially_paid' && $amountPaid <= 0) {
        $status = 'unpaid';
    }
    if ($status === 'draft') {
        $amountPaid = 0;
    }

    $paidDate = $status === 'paid' ? date('Y-m-d') : null;
    $issuedAt = in_array($status, ['issued', 'unpaid', 'partially_paid', 'paid', 'overdue'], true)
        ? (trim((string)($invoice['issued_at'] ?? '')) !== '' ? (string)$invoice['issued_at'] : date('Y-m-d H:i:s'))
        : null;
    $sentAt = $status === 'draft'
        ? null
        : (trim((string)($invoice['sent_at'] ?? '')) !== '' ? (string)$invoice['sent_at'] : date('Y-m-d H:i:s'));

    $update = $pdo->prepare('UPDATE room_invoices
        SET status = :status,
            amount_paid = :amount_paid,
            issued_at = :issued_at,
            sent_at = :sent_at,
            payment_method = :payment_method,
            paid_date = :paid_date
        WHERE id = :invoice');
    $update->execute([
        ':status' => $status,
        ':amount_paid' => $amountPaid,
        ':issued_at' => $issuedAt,
        ':sent_at' => $sentAt,
        ':payment_method' => $paymentMethod !== '' ? $paymentMethod : null,
        ':paid_date' => $paidDate,
        ':invoice' => $invoiceId,
    ]);
    if ($update->rowCount() > 0) {
        $message = 'Trạng thái hóa đơn kỳ ' . (string)($invoice['billing_month'] ?? '') . ' đã cập nhật thành ' . (roomInvoiceStatusOptions()[$status] ?? $status);
        notifyTenantForRoom(
            (int)($invoice['room_id'] ?? 0),
            'invoice_status',
            'Hóa đơn được cập nhật',
            $message,
            '?route=my-stay&section=invoices#my-invoices',
            $invoiceId
        );
        return true;
    }
    return false;
}

function roomInvoiceReminderState(array $invoice): string
{
    $displayStatus = roomInvoiceDisplayStatus($invoice);
    if (in_array($displayStatus, ['paid', 'cancelled', 'draft', 'overdue'], true)) {
        return $displayStatus;
    }

    $dueDate = trim((string)($invoice['due_date'] ?? ''));
    if ($dueDate !== '') {
        $daysLeft = daysUntilDate($dueDate);
        if ($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 3) {
            return 'due_soon';
        }
    }

    return 'unpaid';
}

function enrichInvoiceDisplayState(array $invoice): array
{
    $invoice['display_status'] = roomInvoiceDisplayStatus($invoice);
    $invoice['reminder_state'] = roomInvoiceReminderState($invoice);
    $invoice['amount_paid'] = max(0, (int)($invoice['amount_paid'] ?? 0));
    $invoice['amount_due'] = max(0, (int)($invoice['total_amount'] ?? 0) - (int)$invoice['amount_paid']);
    return $invoice;
}

function activeTenantStayByRoom(int $roomId): ?array
{
    if ($roomId <= 0) {
        return null;
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT *
        FROM tenant_stay_history
        WHERE room_id = :room AND status = :status
        ORDER BY started_at DESC, id DESC
        LIMIT 1');
    $stmt->execute([
        ':room' => $roomId,
        ':status' => 'active',
    ]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function paymentRegularityForStay(array $stay): array
{
    $roomId = (int)($stay['room_id'] ?? 0);
    if ($roomId <= 0) {
        return ['paid_count' => 0, 'unpaid_count' => 0, 'label' => 'Chưa có dữ liệu'];
    }

    $startMonth = substr((string)($stay['started_at'] ?? ''), 0, 7);
    $endDate = trim((string)($stay['ended_at'] ?? ''));
    $endMonth = $endDate !== '' ? substr($endDate, 0, 7) : date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $startMonth)) {
        return ['paid_count' => 0, 'unpaid_count' => 0, 'label' => 'Chưa có dữ liệu'];
    }
    if (!preg_match('/^\d{4}-\d{2}$/', $endMonth)) {
        $endMonth = date('Y-m');
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT * FROM room_invoices
        WHERE room_id = :room
          AND billing_month >= :start_month
          AND billing_month <= :end_month
        ORDER BY billing_month DESC');
    $stmt->execute([
        ':room' => $roomId,
        ':start_month' => $startMonth,
        ':end_month' => $endMonth,
    ]);
    $rows = $stmt->fetchAll();

    $paidCount = 0;
    $unpaidCount = 0;
    foreach ($rows as $row) {
        $displayStatus = roomInvoiceDisplayStatus($row);
        if ($displayStatus === 'paid') {
            $paidCount++;
        } elseif ($displayStatus !== 'cancelled') {
            $unpaidCount++;
        }
    }

    if ($paidCount === 0 && $unpaidCount === 0) {
        $label = 'Chưa có dữ liệu';
    } elseif ($unpaidCount === 0) {
        $label = 'Thanh toán đều';
    } elseif ($paidCount > $unpaidCount) {
        $label = 'Khá đều';
    } else {
        $label = 'Cần theo dõi';
    }

    return [
        'paid_count' => $paidCount,
        'unpaid_count' => $unpaidCount,
        'label' => $label,
    ];
}

function hydrateStayHistoryRow(array $stay): array
{
    $metrics = paymentRegularityForStay($stay);
    return array_merge($stay, [
        'payment_paid_count' => $metrics['paid_count'],
        'payment_unpaid_count' => $metrics['unpaid_count'],
        'payment_regularity_label' => $metrics['label'],
    ]);
}

function syncTenantStayHistory(array $room, int $landlordId, array $previousProfile, array $currentProfile): void
{
    $roomId = (int)($room['id'] ?? 0);
    if ($roomId <= 0) {
        return;
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);

    $currentStatus = (string)($currentProfile['occupancy_status'] ?? 'vacant');
    $currentPhone = trim((string)($currentProfile['tenant_phone'] ?? ''));
    $currentName = trim((string)($currentProfile['tenant_name'] ?? ''));
    $currentStart = normalizeOptionalDate($currentProfile['contract_start'] ?? null) ?? date('Y-m-d');
    $currentEnd = normalizeOptionalDate($currentProfile['contract_end'] ?? null) ?? date('Y-m-d');
    $rentAmount = max(0, (int)($currentProfile['monthly_rent'] ?? $room['price'] ?? 0));
    $depositAmount = max(0, (int)($currentProfile['deposit_amount'] ?? 0));

    $activeStay = activeTenantStayByRoom($roomId);

    if ($currentStatus === 'occupied' && $currentPhone !== '') {
        $tenantUser = findUserByPhone($currentPhone);
        $tenantUserId = $tenantUser ? (int)$tenantUser['id'] : null;

        if ($activeStay && trim((string)$activeStay['tenant_phone']) === $currentPhone) {
            $update = $pdo->prepare('UPDATE tenant_stay_history
                SET tenant_user_id = :tenant_user_id,
                    tenant_name = :tenant_name,
                    started_at = :started_at,
                    rent_amount = :rent_amount,
                    deposit_amount = :deposit_amount,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id');
            $update->execute([
                ':tenant_user_id' => $tenantUserId,
                ':tenant_name' => $currentName !== '' ? $currentName : ($activeStay['tenant_name'] ?? 'Người thuê'),
                ':started_at' => $currentStart,
                ':rent_amount' => $rentAmount,
                ':deposit_amount' => $depositAmount,
                ':id' => (int)$activeStay['id'],
            ]);
            return;
        }

        if ($activeStay) {
            $close = $pdo->prepare('UPDATE tenant_stay_history
                SET ended_at = :ended_at,
                    status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id');
            $close->execute([
                ':ended_at' => $currentStart,
                ':status' => 'closed',
                ':id' => (int)$activeStay['id'],
            ]);
        }

        $insert = $pdo->prepare('INSERT INTO tenant_stay_history (
                room_id, landlord_id, tenant_user_id, tenant_name, tenant_phone, started_at,
                rent_amount, deposit_amount, status
            ) VALUES (
                :room_id, :landlord_id, :tenant_user_id, :tenant_name, :tenant_phone, :started_at,
                :rent_amount, :deposit_amount, :status
            )');
        $insert->execute([
            ':room_id' => $roomId,
            ':landlord_id' => $landlordId,
            ':tenant_user_id' => $tenantUserId,
            ':tenant_name' => $currentName !== '' ? $currentName : 'Người thuê',
            ':tenant_phone' => $currentPhone,
            ':started_at' => $currentStart,
            ':rent_amount' => $rentAmount,
            ':deposit_amount' => $depositAmount,
            ':status' => 'active',
        ]);
        return;
    }

    if ($activeStay) {
        $closeDate = normalizeOptionalDate($currentProfile['contract_end'] ?? null)
            ?? normalizeOptionalDate($previousProfile['contract_end'] ?? null)
            ?? date('Y-m-d');
        $close = $pdo->prepare('UPDATE tenant_stay_history
            SET ended_at = :ended_at,
                status = :status,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id');
        $close->execute([
            ':ended_at' => $closeDate,
            ':status' => 'closed',
            ':id' => (int)$activeStay['id'],
        ]);
    }
}

function tenantStayHistoryByRoom(int $roomId, int $landlordId): array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT tsh.*
        FROM tenant_stay_history tsh
        WHERE tsh.room_id = :room
        ORDER BY tsh.started_at DESC, tsh.id DESC');
    $stmt->execute([':room' => $roomId]);
    $rows = $stmt->fetchAll();

    return array_map('hydrateStayHistoryRow', $rows);
}

function tenantStayHistoryByPhone(string $phone): array
{
    $phone = trim($phone);
    if ($phone === '') {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT tsh.*, r.title AS room_title, r.address AS room_address
        FROM tenant_stay_history tsh
        JOIN rooms r ON r.id = tsh.room_id
        WHERE tsh.tenant_phone = :phone
        ORDER BY tsh.started_at DESC, tsh.id DESC');
    $stmt->execute([':phone' => $phone]);
    $rows = $stmt->fetchAll();

    return array_map('hydrateStayHistoryRow', $rows);
}

function settleTenantDeposit(int $stayId, int $landlordId, array $data): array
{
    if ($stayId <= 0) {
        return ['ok' => false, 'error' => 'Không tìm thấy kỳ thuê để chốt cọc.'];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT *
        FROM tenant_stay_history
        WHERE id = :id AND landlord_id = :landlord
        LIMIT 1');
    $stmt->execute([
        ':id' => $stayId,
        ':landlord' => $landlordId,
    ]);
    $stay = $stmt->fetch();
    if (!$stay) {
        return ['ok' => false, 'error' => 'Không tìm thấy kỳ thuê để hoàn cọc.'];
    }

    $depositAmount = max(0, (int)($stay['deposit_amount'] ?? 0));
    $deductionAmount = isset($data['deposit_deduction_amount']) && $data['deposit_deduction_amount'] !== ''
        ? max(0, (int)$data['deposit_deduction_amount'])
        : 0;
    if ($deductionAmount > $depositAmount) {
        return ['ok' => false, 'error' => 'Khấu trừ không được lớn hơn tiền cọc ban đầu.'];
    }

    $refundAmount = isset($data['deposit_refund_amount']) && $data['deposit_refund_amount'] !== ''
        ? max(0, (int)$data['deposit_refund_amount'])
        : max(0, $depositAmount - $deductionAmount);
    $endedAt = normalizeOptionalDate($data['ended_at'] ?? null) ?? date('Y-m-d');
    $settledAt = normalizeOptionalDate($data['settled_at'] ?? null) ?? date('Y-m-d');

    $update = $pdo->prepare('UPDATE tenant_stay_history
        SET deposit_deduction_amount = :deduction_amount,
            deposit_refund_amount = :refund_amount,
            settlement_note = :settlement_note,
            settled_at = :settled_at,
            ended_at = COALESCE(ended_at, :ended_at),
            status = :status,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id');
    $update->execute([
        ':deduction_amount' => $deductionAmount,
        ':refund_amount' => $refundAmount,
        ':settlement_note' => trim((string)($data['settlement_note'] ?? '')),
        ':settled_at' => $settledAt,
        ':ended_at' => $endedAt,
        ':status' => 'closed',
        ':id' => $stayId,
    ]);

    $resetRoom = $pdo->prepare('UPDATE room_operations
        SET occupancy_status = :occupancy_status,
            tenant_name = :tenant_name,
            tenant_phone = :tenant_phone,
            deposit_amount = :deposit_amount,
            contract_start = :contract_start,
            contract_end = :contract_end,
            updated_at = CURRENT_TIMESTAMP
        WHERE room_id = :room_id');
    $resetRoom->execute([
        ':occupancy_status' => 'vacant',
        ':tenant_name' => '',
        ':tenant_phone' => '',
        ':deposit_amount' => null,
        ':contract_start' => null,
        ':contract_end' => null,
        ':room_id' => (int)$stay['room_id'],
    ]);

    return ['ok' => true];
}

function annotateMeterLogs(array $rows): array
{
    usort($rows, static function (array $a, array $b): int {
        return strcmp((string)($a['billing_month'] ?? ''), (string)($b['billing_month'] ?? ''));
    });

    $previousElectric = null;
    $previousWater = null;
    foreach ($rows as $index => $row) {
        $alerts = [];
        $electricUnits = isset($row['electric_units']) && $row['electric_units'] !== null ? (int)$row['electric_units'] : null;
        $waterUnits = isset($row['water_units']) && $row['water_units'] !== null ? (int)$row['water_units'] : null;
        if ($previousElectric !== null && $electricUnits !== null && $previousElectric > 0 && $electricUnits >= (int)ceil($previousElectric * 1.5)) {
            $alerts[] = 'Điện tăng bất thường';
        }
        if ($previousWater !== null && $waterUnits !== null && $previousWater > 0 && $waterUnits >= (int)ceil($previousWater * 1.5)) {
            $alerts[] = 'Nước tăng bất thường';
        }
        $rows[$index]['usage_alerts'] = $alerts;
        $previousElectric = $electricUnits;
        $previousWater = $waterUnits;
    }

    return array_reverse($rows);
}

function roomMeterLogsByRoom(int $roomId, int $landlordId): array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT *
        FROM room_meter_logs
        WHERE room_id = :room
        ORDER BY billing_month ASC, id ASC');
    $stmt->execute([':room' => $roomId]);
    return annotateMeterLogs($stmt->fetchAll());
}

function roomMeterLogsForTenant(int $roomId, string $tenantPhone): array
{
    $tenantPhone = trim($tenantPhone);
    if ($roomId <= 0 || $tenantPhone === '') {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT rml.*
        FROM room_meter_logs rml
        JOIN room_operations ro ON ro.room_id = rml.room_id
        WHERE rml.room_id = :room AND ro.tenant_phone = :phone
        ORDER BY rml.billing_month ASC, rml.id ASC');
    $stmt->execute([
        ':room' => $roomId,
        ':phone' => $tenantPhone,
    ]);
    return annotateMeterLogs($stmt->fetchAll());
}

function roomHandoverRecordsByRoom(int $roomId, int $landlordId): array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT rhr.*, tsh.tenant_name, tsh.tenant_phone
        FROM room_handover_records rhr
        LEFT JOIN tenant_stay_history tsh ON tsh.id = rhr.stay_id
        WHERE rhr.room_id = :room
        ORDER BY rhr.created_at DESC, rhr.id DESC');
    $stmt->execute([':room' => $roomId]);
    return $stmt->fetchAll();
}

function handoverRecordsForTenant(int $roomId, string $tenantPhone): array
{
    $tenantPhone = trim($tenantPhone);
    if ($roomId <= 0 || $tenantPhone === '') {
        return [];
    }

    $activeStay = activeTenantStayByRoom($roomId);
    if (!$activeStay || trim((string)($activeStay['tenant_phone'] ?? '')) !== $tenantPhone) {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT *
        FROM room_handover_records
        WHERE room_id = :room AND (stay_id = :stay_id OR stay_id IS NULL)
        ORDER BY created_at DESC, id DESC');
    $stmt->execute([
        ':room' => $roomId,
        ':stay_id' => (int)$activeStay['id'],
    ]);
    return $stmt->fetchAll();
}

function createRoomHandoverRecord(int $roomId, int $landlordId, array $data): array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return ['ok' => false, 'error' => 'Không tìm thấy phòng để lưu bàn giao.'];
    }

    $handoverType = (string)($data['handover_type'] ?? 'move_in');
    $handoverTypes = roomHandoverTypeOptions();
    if (!isset($handoverTypes[$handoverType])) {
        $handoverType = 'move_in';
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $activeStay = activeTenantStayByRoom($roomId);
    $stmt = $pdo->prepare('INSERT INTO room_handover_records (
            room_id, stay_id, handover_type, wall_image, bed_image, equipment_image, note
        ) VALUES (
            :room_id, :stay_id, :handover_type, :wall_image, :bed_image, :equipment_image, :note
        )');
    $stmt->execute([
        ':room_id' => $roomId,
        ':stay_id' => $activeStay ? (int)$activeStay['id'] : null,
        ':handover_type' => $handoverType,
        ':wall_image' => !empty($data['wall_image']) ? (string)$data['wall_image'] : null,
        ':bed_image' => !empty($data['bed_image']) ? (string)$data['bed_image'] : null,
        ':equipment_image' => !empty($data['equipment_image']) ? (string)$data['equipment_image'] : null,
        ':note' => trim((string)($data['note'] ?? '')),
    ]);
    return ['ok' => true, 'handover_id' => (int)$pdo->lastInsertId()];
}

function roomNoticesByRoom(int $roomId, int $landlordId): array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT rn.*, u.name AS landlord_name
        FROM room_notices rn
        LEFT JOIN users u ON u.id = rn.landlord_id
        WHERE rn.room_id = :room
        ORDER BY rn.created_at DESC');
    $stmt->execute([':room' => $roomId]);
    return $stmt->fetchAll();
}

function createRoomNotice(int $roomId, int $landlordId, array $data): array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return ['ok' => false, 'error' => 'Không tìm thấy phòng để gửi thông báo.'];
    }

    $noticeTypes = roomNoticeTypeOptions();
    $noticeType = (string)($data['notice_type'] ?? 'general');
    if (!isset($noticeTypes[$noticeType])) {
        $noticeType = 'general';
    }

    $title = trim((string)($data['title'] ?? ''));
    $content = trim((string)($data['content'] ?? ''));
    if ($title === '' || $content === '') {
        return ['ok' => false, 'error' => 'Vui lòng nhập tiêu đề và nội dung thông báo.'];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('INSERT INTO room_notices (
            room_id, landlord_id, notice_type, title, content, effective_date
        ) VALUES (
            :room_id, :landlord_id, :notice_type, :title, :content, :effective_date
        )');
    $stmt->execute([
        ':room_id' => $roomId,
        ':landlord_id' => $landlordId,
        ':notice_type' => $noticeType,
        ':title' => $title,
        ':content' => $content,
        ':effective_date' => normalizeOptionalDate($data['effective_date'] ?? null),
    ]);

    $noticeId = (int)$pdo->lastInsertId();
    notifyTenantForRoom(
        $roomId,
        'room_notice',
        $title,
        mb_substr($content, 0, 180, 'UTF-8'),
        '?route=my-stay&section=notices#my-notices',
        $noticeId
    );

    return ['ok' => true, 'notice_id' => $noticeId];
}

function saveRoomContract(int $roomId, int $landlordId, array $data): array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return ['ok' => false, 'error' => 'Không tìm thấy phòng để tạo hợp đồng.'];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $profile = roomOperationProfile($roomId, $landlordId) ?? roomOperationProfileDefaults($room);
    $contractId = (int)($data['contract_id'] ?? 0);
    $renewContract = !empty($data['renew_contract']);
    $templateName = trim((string)($data['template_name'] ?? 'Hợp đồng thuê trọ chuẩn'));
    $startDate = normalizeOptionalDate($data['start_date'] ?? $profile['contract_start'] ?? null);
    $endDate = normalizeOptionalDate($data['end_date'] ?? $profile['contract_end'] ?? null);
    $tenantName = trim((string)($data['tenant_name'] ?? $profile['tenant_name'] ?? ''));
    $tenantPhone = trim((string)($data['tenant_phone'] ?? $profile['tenant_phone'] ?? ''));
    $paymentTerms = trim((string)($data['payment_terms'] ?? 'Thanh toán trước ngày 05 hàng tháng.'));
    $monthlyRent = isset($data['monthly_rent']) && $data['monthly_rent'] !== ''
        ? max(0, (int)$data['monthly_rent'])
        : max(0, (int)($profile['monthly_rent'] ?? $room['price'] ?? 0));
    $depositAmount = isset($data['deposit_amount']) && $data['deposit_amount'] !== ''
        ? max(0, (int)$data['deposit_amount'])
        : max(0, (int)($profile['deposit_amount'] ?? 0));

    if ($startDate === null || $endDate === null) {
        return ['ok' => false, 'error' => 'Hợp đồng cần có ngày bắt đầu và ngày kết thúc.'];
    }
    if ($tenantName === '' || $tenantPhone === '') {
        return ['ok' => false, 'error' => 'Hợp đồng cần gắn đúng người thuê hiện tại.'];
    }

    $tenantUser = findUserByPhone($tenantPhone);
    $existing = $contractId > 0 ? activeRoomContractByRoom($roomId, $landlordId) : null;
    if ($contractId > 0 && (!$existing || (int)($existing['id'] ?? 0) !== $contractId)) {
        $contractId = 0;
        $existing = null;
    }

    $tenantSignedAt = !empty($data['tenant_sign_confirm'])
        ? (trim((string)($existing['tenant_signed_at'] ?? '')) !== '' ? (string)$existing['tenant_signed_at'] : date('Y-m-d H:i:s'))
        : null;
    $landlordSignedAt = !empty($data['landlord_sign_confirm'])
        ? (trim((string)($existing['landlord_signed_at'] ?? '')) !== '' ? (string)$existing['landlord_signed_at'] : date('Y-m-d H:i:s'))
        : null;

    $contractCode = trim((string)($data['contract_code'] ?? ''));
    if ($contractCode === '') {
        $contractCode = trim((string)($existing['contract_code'] ?? ''));
    }
    if ($contractCode === '') {
        $contractCode = generateRoomContractCode($roomId);
    }

    $contractPayload = [
        'room_id' => $roomId,
        'landlord_id' => $landlordId,
        'tenant_user_id' => $tenantUser ? (int)$tenantUser['id'] : null,
        'tenant_name' => $tenantName,
        'tenant_phone' => $tenantPhone,
        'contract_code' => $contractCode,
        'template_name' => $templateName,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'monthly_rent' => $monthlyRent,
        'deposit_amount' => $depositAmount,
        'payment_terms' => $paymentTerms,
        'attachment_path' => !empty($data['attachment_path']) ? (string)$data['attachment_path'] : ($existing['attachment_path'] ?? null),
        'tenant_signed_at' => $tenantSignedAt,
        'landlord_signed_at' => $landlordSignedAt,
    ];
    $contractPayload['status'] = roomContractStatusByDates($contractPayload);

    $startedTransaction = false;
    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        if ($renewContract && $existing) {
            $pdo->prepare('UPDATE room_contracts SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id')
                ->execute([
                    ':status' => 'renewed',
                    ':id' => (int)$existing['id'],
                ]);
            $contractId = 0;
            $existing = null;
        }

        if ($contractId > 0) {
            $stmt = $pdo->prepare('UPDATE room_contracts
                SET tenant_user_id = :tenant_user_id,
                    tenant_name = :tenant_name,
                    tenant_phone = :tenant_phone,
                    template_name = :template_name,
                    start_date = :start_date,
                    end_date = :end_date,
                    monthly_rent = :monthly_rent,
                    deposit_amount = :deposit_amount,
                    payment_terms = :payment_terms,
                    attachment_path = :attachment_path,
                    tenant_signed_at = :tenant_signed_at,
                    landlord_signed_at = :landlord_signed_at,
                    status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND room_id = :room_id AND landlord_id = :landlord_id');
            $stmt->execute([
                ':tenant_user_id' => $contractPayload['tenant_user_id'],
                ':tenant_name' => $contractPayload['tenant_name'],
                ':tenant_phone' => $contractPayload['tenant_phone'],
                ':template_name' => $contractPayload['template_name'],
                ':start_date' => $contractPayload['start_date'],
                ':end_date' => $contractPayload['end_date'],
                ':monthly_rent' => $contractPayload['monthly_rent'],
                ':deposit_amount' => $contractPayload['deposit_amount'],
                ':payment_terms' => $contractPayload['payment_terms'],
                ':attachment_path' => $contractPayload['attachment_path'],
                ':tenant_signed_at' => $contractPayload['tenant_signed_at'],
                ':landlord_signed_at' => $contractPayload['landlord_signed_at'],
                ':status' => $contractPayload['status'],
                ':id' => $contractId,
                ':room_id' => $roomId,
                ':landlord_id' => $landlordId,
            ]);
            $savedId = $contractId;
        } else {
            $stmt = $pdo->prepare('INSERT INTO room_contracts (
                    room_id, landlord_id, tenant_user_id, tenant_name, tenant_phone, contract_code,
                    template_name, start_date, end_date, monthly_rent, deposit_amount, payment_terms,
                    attachment_path, tenant_signed_at, landlord_signed_at, status
                ) VALUES (
                    :room_id, :landlord_id, :tenant_user_id, :tenant_name, :tenant_phone, :contract_code,
                    :template_name, :start_date, :end_date, :monthly_rent, :deposit_amount, :payment_terms,
                    :attachment_path, :tenant_signed_at, :landlord_signed_at, :status
                )');
            $stmt->execute([
                ':room_id' => $contractPayload['room_id'],
                ':landlord_id' => $contractPayload['landlord_id'],
                ':tenant_user_id' => $contractPayload['tenant_user_id'],
                ':tenant_name' => $contractPayload['tenant_name'],
                ':tenant_phone' => $contractPayload['tenant_phone'],
                ':contract_code' => $contractPayload['contract_code'],
                ':template_name' => $contractPayload['template_name'],
                ':start_date' => $contractPayload['start_date'],
                ':end_date' => $contractPayload['end_date'],
                ':monthly_rent' => $contractPayload['monthly_rent'],
                ':deposit_amount' => $contractPayload['deposit_amount'],
                ':payment_terms' => $contractPayload['payment_terms'],
                ':attachment_path' => $contractPayload['attachment_path'],
                ':tenant_signed_at' => $contractPayload['tenant_signed_at'],
                ':landlord_signed_at' => $contractPayload['landlord_signed_at'],
                ':status' => $contractPayload['status'],
            ]);
            $savedId = (int)$pdo->lastInsertId();
        }

        saveRoomOperationProfile($roomId, $landlordId, [
            'occupancy_status' => $profile['occupancy_status'] ?? 'vacant',
            'tenant_name' => $tenantName,
            'tenant_phone' => $tenantPhone,
            'monthly_rent' => $monthlyRent,
            'deposit_amount' => $depositAmount,
            'service_fee' => $profile['service_fee'] ?? 0,
            'contract_start' => $startDate,
            'contract_end' => $endDate,
            'electric_meter_reading' => $profile['electric_meter_reading'] ?? null,
            'water_meter_reading' => $profile['water_meter_reading'] ?? null,
            'room_condition' => $profile['room_condition'] ?? 'ready',
            'issue_note' => $profile['issue_note'] ?? '',
            'operation_note' => $profile['operation_note'] ?? '',
        ]);

        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Save room contract failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Không lưu được hợp đồng hiện tại.'];
    }

    notifyTenantForRoom(
        $roomId,
        'contract_update',
        $renewContract ? 'Hợp đồng đã được gia hạn' : 'Có cập nhật hợp đồng thuê',
        'Hợp đồng #' . $contractCode . ' hiện ở trạng thái ' . (roomContractStatusOptions()[$contractPayload['status']] ?? $contractPayload['status']),
        '?route=my-stay#my-contract',
        $savedId
    );

    return ['ok' => true, 'contract_id' => $savedId];
}

function endRoomContract(int $contractId, int $landlordId): bool
{
    if ($contractId <= 0) {
        return false;
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT * FROM room_contracts WHERE id = :id AND landlord_id = :landlord_id LIMIT 1');
    $stmt->execute([
        ':id' => $contractId,
        ':landlord_id' => $landlordId,
    ]);
    $contract = $stmt->fetch();
    if (!$contract) {
        return false;
    }

    $update = $pdo->prepare('UPDATE room_contracts
        SET status = :status,
            end_date = :end_date,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id');
    $update->execute([
        ':status' => 'ended',
        ':end_date' => date('Y-m-d'),
        ':id' => $contractId,
    ]);
    if ($update->rowCount() > 0) {
        notifyTenantForRoom(
            (int)($contract['room_id'] ?? 0),
            'contract_update',
            'Hợp đồng đã kết thúc',
            'Hợp đồng #' . (string)($contract['contract_code'] ?? '') . ' đã được chốt kết thúc.',
            '?route=my-stay#my-contract',
            $contractId
        );
        return true;
    }
    return false;
}

function tenantIssueReportsByRoom(int $roomId, int $landlordId): array
{
    $room = findRoomOwned($roomId, $landlordId);
    if (!$room) {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare("SELECT tir.*
        FROM tenant_issue_reports tir
        WHERE tir.room_id = :room
        ORDER BY CASE tir.status
            WHEN 'open' THEN 0
            WHEN 'in_progress' THEN 1
            WHEN 'waiting_parts' THEN 2
            WHEN 'resolved' THEN 3
            ELSE 4
        END, tir.created_at DESC");
    $stmt->execute([':room' => $roomId]);
    return $stmt->fetchAll();
}

function createTenantIssueReport(int $roomId, array $tenantUser, array $data): array
{
    $phone = trim((string)($tenantUser['phone'] ?? ''));
    if ($roomId <= 0 || $phone === '') {
        return ['ok' => false, 'error' => 'Không xác định được phòng hoặc người gửi sự cố.'];
    }

    $content = trim((string)($data['content'] ?? ''));
    if ($content === '') {
        return ['ok' => false, 'error' => 'Vui lòng nhập nội dung sự cố cần báo.'];
    }

    $priorityOptions = tenantIssuePriorityOptions();
    $priority = (string)($data['priority'] ?? 'normal');
    if (!isset($priorityOptions[$priority])) {
        $priority = 'normal';
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);

    $roomStmt = $pdo->prepare('SELECT ro.room_id
        FROM room_operations ro
        WHERE ro.room_id = :room AND ro.tenant_phone = :phone
        LIMIT 1');
    $roomStmt->execute([
        ':room' => $roomId,
        ':phone' => $phone,
    ]);
    if (!$roomStmt->fetchColumn()) {
        return ['ok' => false, 'error' => 'Phòng này chưa được gắn với số điện thoại tài khoản của bạn.'];
    }

    $stmt = $pdo->prepare('INSERT INTO tenant_issue_reports (
            room_id, tenant_user_id, tenant_name, tenant_phone, title, priority, content, image_path, video_path, status
        ) VALUES (
            :room_id, :tenant_user_id, :tenant_name, :tenant_phone, :title, :priority, :content, :image_path, :video_path, :status
        )');
    $stmt->execute([
        ':room_id' => $roomId,
        ':tenant_user_id' => isset($tenantUser['id']) ? (int)$tenantUser['id'] : null,
        ':tenant_name' => trim((string)($tenantUser['name'] ?? 'Người thuê')),
        ':tenant_phone' => $phone,
        ':title' => trim((string)($data['title'] ?? 'Yêu cầu hỗ trợ phòng #' . $roomId)),
        ':priority' => $priority,
        ':content' => $content,
        ':image_path' => !empty($data['image_path']) ? (string)$data['image_path'] : null,
        ':video_path' => !empty($data['video_path']) ? (string)$data['video_path'] : null,
        ':status' => 'open',
    ]);

    $issueId = (int)$pdo->lastInsertId();
    notifyLandlordByRoom(
        $roomId,
        'support_ticket',
        'Có ticket mới từ người thuê',
        trim((string)($data['title'] ?? 'Yêu cầu hỗ trợ phòng #' . $roomId)),
        '?route=room-ops&id=' . $roomId . '#ops-issues',
        $issueId
    );

    return ['ok' => true, 'issue_id' => $issueId];
}

function updateTenantIssueStatus(int $issueId, int $landlordId, string $status, string $landlordNote = '', int $repairCost = 0): bool
{
    $status = normalizeTenantIssueStatus($status);
    $allowed = tenantIssueStatusOptions();
    if (!isset($allowed[$status])) {
        return false;
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT tir.id, tir.room_id
        FROM tenant_issue_reports tir
        JOIN rooms r ON r.id = tir.room_id
        WHERE tir.id = :issue AND r.landlord_id = :landlord
        LIMIT 1');
    $stmt->execute([
        ':issue' => $issueId,
        ':landlord' => $landlordId,
    ]);
    $issue = $stmt->fetch();
    if (!$issue) {
        return false;
    }

    $update = $pdo->prepare('UPDATE tenant_issue_reports
        SET status = :status,
            landlord_note = :landlord_note,
            repair_cost = :repair_cost,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :issue');
    $update->execute([
        ':status' => $status,
        ':landlord_note' => trim($landlordNote),
        ':repair_cost' => max(0, $repairCost),
        ':issue' => $issueId,
    ]);
    if ($update->rowCount() > 0) {
        notifyTenantForRoom(
            (int)($issue['room_id'] ?? 0),
            'support_ticket',
            'Ticket đã được cập nhật',
            'Trạng thái mới: ' . ($allowed[$status] ?? $status),
            '?route=my-stay&section=issues#my-issues',
            $issueId
        );
        return true;
    }
    return false;
}

function roomInvoicesForTenant(int $roomId, string $tenantPhone): array
{
    $tenantPhone = trim($tenantPhone);
    if ($roomId <= 0 || $tenantPhone === '') {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT ri.*
        FROM room_invoices ri
        JOIN room_operations ro ON ro.room_id = ri.room_id
        WHERE ri.room_id = :room AND ro.tenant_phone = :phone
        ORDER BY ri.billing_month DESC, ri.created_at DESC');
    $stmt->execute([
        ':room' => $roomId,
        ':phone' => $tenantPhone,
    ]);

    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row = enrichInvoiceDisplayState($row);
    }
    unset($row);
    return $rows;
}

function roomNoticesForTenant(int $roomId, string $tenantPhone): array
{
    $tenantPhone = trim($tenantPhone);
    if ($roomId <= 0 || $tenantPhone === '') {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT rn.*, u.name AS landlord_name
        FROM room_notices rn
        JOIN room_operations ro ON ro.room_id = rn.room_id
        LEFT JOIN users u ON u.id = rn.landlord_id
        WHERE rn.room_id = :room AND ro.tenant_phone = :phone
        ORDER BY rn.created_at DESC');
    $stmt->execute([
        ':room' => $roomId,
        ':phone' => $tenantPhone,
    ]);
    return $stmt->fetchAll();
}

function tenantIssueReportsForTenant(int $roomId, string $tenantPhone): array
{
    $tenantPhone = trim($tenantPhone);
    if ($roomId <= 0 || $tenantPhone === '') {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stmt = $pdo->prepare('SELECT tir.*
        FROM tenant_issue_reports tir
        JOIN room_operations ro ON ro.room_id = tir.room_id
        WHERE tir.room_id = :room AND ro.tenant_phone = :phone AND tir.tenant_phone = :phone
        ORDER BY tir.created_at DESC');
    $stmt->execute([
        ':room' => $roomId,
        ':phone' => $tenantPhone,
    ]);
    return $stmt->fetchAll();
}

function tenantStaySpaceByUser(array $user): array
{
    $phone = trim((string)($user['phone'] ?? ''));
    $stayHistory = [];
    $empty = [
        'room' => null,
        'operation_profile' => null,
        'landlord' => null,
        'contracts' => [],
        'current_contract' => null,
        'invoices' => [],
        'current_invoice' => null,
        'notices' => [],
        'issues' => [],
        'stay_history' => [],
        'active_stay' => null,
        'meter_logs' => [],
        'handover_records' => [],
        'unpaid_invoice_count' => 0,
        'contract_days_left' => null,
        'linked_room_count' => 0,
    ];
    if ($phone === '') {
        return $empty;
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $stayHistory = tenantStayHistoryByPhone($phone);
    $empty['stay_history'] = $stayHistory;

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM room_operations WHERE tenant_phone = :phone');
    $countStmt->execute([':phone' => $phone]);
    $linkedRoomCount = (int)$countStmt->fetchColumn();
    $empty['linked_room_count'] = $linkedRoomCount;

    $stmt = $pdo->prepare("SELECT
            ro.*,
            r.id AS linked_room_id,
            r.landlord_id AS linked_landlord_id,
            u.name AS landlord_name,
            u.phone AS landlord_phone,
            u.avatar AS landlord_avatar
        FROM room_operations ro
        JOIN rooms r ON r.id = ro.room_id
        LEFT JOIN users u ON u.id = r.landlord_id
        WHERE ro.tenant_phone = :phone
        ORDER BY CASE ro.occupancy_status
            WHEN 'occupied' THEN 0
            WHEN 'reserved' THEN 1
            WHEN 'maintenance' THEN 2
            ELSE 3
        END,
        COALESCE(ro.updated_at, ro.created_at) DESC,
        ro.room_id DESC
        LIMIT 1");
    $stmt->execute([':phone' => $phone]);
    $linked = $stmt->fetch();
    if (!$linked) {
        return $empty;
    }

    $room = fetchRoom((int)$linked['linked_room_id']);
    if (!$room) {
        return $empty;
    }

    $profile = array_merge(roomOperationProfileDefaults($room), [
        'room_id' => (int)$linked['linked_room_id'],
        'occupancy_status' => $linked['occupancy_status'] ?? 'vacant',
        'tenant_name' => $linked['tenant_name'] ?? '',
        'tenant_phone' => $linked['tenant_phone'] ?? '',
        'monthly_rent' => $linked['monthly_rent'] ?? null,
        'deposit_amount' => $linked['deposit_amount'] ?? null,
        'service_fee' => $linked['service_fee'] ?? 0,
        'contract_start' => $linked['contract_start'] ?? null,
        'contract_end' => $linked['contract_end'] ?? null,
        'electric_meter_reading' => $linked['electric_meter_reading'] ?? null,
        'water_meter_reading' => $linked['water_meter_reading'] ?? null,
        'room_condition' => $linked['room_condition'] ?? 'ready',
        'issue_note' => $linked['issue_note'] ?? '',
        'operation_note' => $linked['operation_note'] ?? '',
        'created_at' => $linked['created_at'] ?? null,
        'updated_at' => $linked['updated_at'] ?? null,
    ]);

    $invoices = roomInvoicesForTenant((int)$room['id'], $phone);
    $notices = roomNoticesForTenant((int)$room['id'], $phone);
    $issues = tenantIssueReportsForTenant((int)$room['id'], $phone);
    $contracts = roomContractsForTenant((int)$room['id'], $phone);
    $activeStay = activeTenantStayByRoom((int)$room['id']);
    $meterLogs = roomMeterLogsForTenant((int)$room['id'], $phone);
    $handoverRecords = handoverRecordsForTenant((int)$room['id'], $phone);
    $currentInvoice = null;
    $currentMonth = date('Y-m');
    $unpaidInvoiceCount = 0;
    foreach ($invoices as $invoice) {
        $displayStatus = (string)($invoice['display_status'] ?? 'unpaid');
        if ($displayStatus !== 'paid' && $displayStatus !== 'cancelled') {
            $unpaidInvoiceCount++;
        }
        if ($currentInvoice === null && (string)($invoice['billing_month'] ?? '') === $currentMonth) {
            $currentInvoice = $invoice;
        }
    }
    if ($currentInvoice === null && !empty($invoices)) {
        $currentInvoice = $invoices[0];
    }

    return [
        'room' => $room,
        'operation_profile' => $profile,
        'landlord' => [
            'id' => (int)($linked['linked_landlord_id'] ?? 0),
            'name' => (string)($linked['landlord_name'] ?? ''),
            'phone' => (string)($linked['landlord_phone'] ?? ''),
            'avatar' => (string)($linked['landlord_avatar'] ?? ''),
        ],
        'contracts' => $contracts,
        'current_contract' => $contracts[0] ?? null,
        'invoices' => $invoices,
        'current_invoice' => $currentInvoice,
        'notices' => $notices,
        'issues' => $issues,
        'stay_history' => $stayHistory,
        'active_stay' => $activeStay ? hydrateStayHistoryRow($activeStay) : null,
        'meter_logs' => $meterLogs,
        'handover_records' => $handoverRecords,
        'unpaid_invoice_count' => $unpaidInvoiceCount,
        'contract_days_left' => daysUntilDate($profile['contract_end'] ?? null),
        'linked_room_count' => $linkedRoomCount,
    ];
}

function daysUntilDate(?string $dateValue): ?int
{
    $dateValue = trim((string)$dateValue);
    if ($dateValue === '') {
        return null;
    }
    $target = strtotime($dateValue . ' 00:00:00');
    if ($target === false) {
        return null;
    }
    $today = strtotime(date('Y-m-d') . ' 00:00:00');
    return (int)floor(($target - $today) / 86400);
}

function roomsOperationalOverviewByLandlord(int $landlordId, string $keyword = ''): array
{
    $rooms = roomsByLandlord($landlordId, $keyword);
    if (empty($rooms)) {
        return [];
    }

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);

    $roomIds = array_map(static function ($room) {
        return (int)($room['id'] ?? 0);
    }, $rooms);
    $placeholders = implode(',', array_fill(0, count($roomIds), '?'));

    $operationMap = [];
    $stmt = $pdo->prepare("SELECT ro.*
        FROM room_operations ro
        JOIN rooms r ON r.id = ro.room_id
        WHERE r.landlord_id = ? AND ro.room_id IN ($placeholders)");
    $stmt->execute(array_merge([$landlordId], $roomIds));
    foreach ($stmt->fetchAll() as $row) {
        $operationMap[(int)$row['room_id']] = $row;
    }

    $invoiceMap = [];
    $invoiceStmt = $pdo->prepare("SELECT *
        FROM room_invoices
        WHERE room_id IN ($placeholders)
        ORDER BY billing_month DESC, created_at DESC");
    $invoiceStmt->execute($roomIds);
    foreach ($invoiceStmt->fetchAll() as $invoice) {
        $roomId = (int)$invoice['room_id'];
        if (!isset($invoiceMap[$roomId])) {
            $invoiceMap[$roomId] = [];
        }
        $invoice = enrichInvoiceDisplayState($invoice);
        $invoiceMap[$roomId][] = $invoice;
    }

    $statusLabels = roomOperationStatusOptions();
    $conditionLabels = roomConditionOptions();
    $out = [];
    foreach ($rooms as $room) {
        $roomId = (int)($room['id'] ?? 0);
        $profile = array_merge(roomOperationProfileDefaults($room), $operationMap[$roomId] ?? []);
        $invoices = $invoiceMap[$roomId] ?? [];
        $unpaidCount = 0;
        $latestInvoice = $invoices[0] ?? null;
        foreach ($invoices as $invoice) {
            if (($invoice['display_status'] ?? '') !== 'paid' && ($invoice['display_status'] ?? '') !== 'cancelled') {
                $unpaidCount++;
            }
        }

        $daysLeft = daysUntilDate($profile['contract_end'] ?? null);
        $attention = [];
        if (($profile['occupancy_status'] ?? '') !== 'occupied') {
            $attention[] = 'Phòng đang trống';
        }
        if ($unpaidCount > 0) {
            $attention[] = $unpaidCount . ' hoá đơn chưa thanh toán';
        }
        if ($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 30) {
            $attention[] = 'Hợp đồng sắp hết hạn';
        }
        if (!empty(trim((string)($profile['issue_note'] ?? '')))) {
            $attention[] = 'Có ghi chú sự cố';
        }

        $out[] = array_merge($room, [
            'ops_profile' => $profile,
            'ops_status_label' => $statusLabels[$profile['occupancy_status'] ?? 'vacant'] ?? 'Phòng trống',
            'room_condition_label' => $conditionLabels[$profile['room_condition'] ?? 'ready'] ?? 'Ổn định',
            'ops_unpaid_invoice_count' => $unpaidCount,
            'ops_latest_invoice' => $latestInvoice,
            'ops_contract_days_left' => $daysLeft,
            'ops_attention' => $attention,
            'ops_monthly_rent' => $profile['monthly_rent'] !== null ? (int)$profile['monthly_rent'] : (int)($room['price'] ?? 0),
            'ops_service_fee' => $profile['service_fee'] !== null ? (int)$profile['service_fee'] : 0,
            'ops_tenant_name' => (string)($profile['tenant_name'] ?? ''),
        ]);
    }

    return $out;
}

function landlordOperationDashboardSummary(int $landlordId): array
{
    $rooms = roomsOperationalOverviewByLandlord($landlordId);
    $totalRooms = count($rooms);
    $occupiedRooms = 0;
    $vacantRooms = 0;
    $maintenanceRooms = 0;
    $unpaidInvoices = 0;
    $dueSoonInvoices = 0;
    $overdueInvoices = 0;
    $expiringContracts = 0;
    $revenueMonth = 0;
    $attentionRooms = [];

    $pdo = getPDO();
    ensureRoomOperationsSchema($pdo);
    $monthStart = date('Y-m-01');
    $nextMonthStart = date('Y-m-01', strtotime('+1 month'));

    $invoicesStmt = $pdo->prepare('SELECT ri.*
        FROM room_invoices ri
        JOIN rooms r ON r.id = ri.room_id
        WHERE r.landlord_id = :landlord
        ORDER BY ri.created_at DESC');
    $invoicesStmt->execute([':landlord' => $landlordId]);
    $allInvoices = $invoicesStmt->fetchAll();

    foreach ($rooms as $room) {
        $status = (string)($room['ops_profile']['occupancy_status'] ?? 'vacant');
        if ($status === 'occupied') {
            $occupiedRooms++;
        } elseif ($status === 'maintenance') {
            $maintenanceRooms++;
        } else {
            $vacantRooms++;
        }
        if (($room['ops_contract_days_left'] ?? null) !== null && $room['ops_contract_days_left'] >= 0 && $room['ops_contract_days_left'] <= 30) {
            $expiringContracts++;
        }
        if (!empty($room['ops_attention'])) {
            $attentionRooms[] = [
                'room_id' => (int)$room['id'],
                'title' => (string)($room['title'] ?? ''),
                'attention' => $room['ops_attention'],
                'status_label' => $room['ops_status_label'] ?? '',
            ];
        }
    }

    foreach ($allInvoices as $invoice) {
        $displayStatus = roomInvoiceDisplayStatus($invoice);
        $reminderState = roomInvoiceReminderState($invoice);
        if ($displayStatus !== 'paid' && $displayStatus !== 'cancelled') {
            $unpaidInvoices++;
        }
        if ($reminderState === 'due_soon') {
            $dueSoonInvoices++;
        } elseif ($reminderState === 'overdue') {
            $overdueInvoices++;
        }
        if ((string)($invoice['status'] ?? '') === 'paid') {
            $paidDate = trim((string)($invoice['paid_date'] ?? ''));
            $compareDate = $paidDate !== '' ? $paidDate : substr((string)($invoice['created_at'] ?? ''), 0, 10);
            if ($compareDate >= $monthStart && $compareDate < $nextMonthStart) {
                $revenueMonth += (int)($invoice['total_amount'] ?? 0);
            }
        }
    }

    usort($attentionRooms, static function (array $a, array $b): int {
        return count($b['attention']) <=> count($a['attention']);
    });

    $openIssuesStmt = $pdo->prepare('SELECT COUNT(*)
        FROM tenant_issue_reports tir
        JOIN rooms r ON r.id = tir.room_id
        WHERE r.landlord_id = :landlord AND tir.status != "resolved"');
    $openIssuesStmt->execute([':landlord' => $landlordId]);
    $openIssues = (int)$openIssuesStmt->fetchColumn();

    return [
        'total_rooms' => $totalRooms,
        'occupied_rooms' => $occupiedRooms,
        'vacant_rooms' => $vacantRooms,
        'maintenance_rooms' => $maintenanceRooms,
        'unpaid_invoices' => $unpaidInvoices,
        'due_soon_invoices' => $dueSoonInvoices,
        'overdue_invoices' => $overdueInvoices,
        'expiring_contracts' => $expiringContracts,
        'open_issues' => $openIssues,
        'revenue_month' => $revenueMonth,
        'attention_rooms' => array_slice($attentionRooms, 0, 6),
        'rooms' => $rooms,
    ];
}

function updateRoom(
    int $roomId,
    int $landlordId,
    string $title,
    int $price,
    string $area,
    string $address,
    string $description,
    string $thumb,
    ?int $electricPrice = null,
    ?int $waterPrice = null,
    int $sharedOwner = 0,
    int $closedRoom = 0,
    ?string $image1 = null,
    ?string $image2 = null,
    ?string $image3 = null,
    ?string $image4 = null,
    ?string $image5 = null,
    ?string $image6 = null,
    ?string $image7 = null,
    ?string $image8 = null,
    ?string $videoUrl = null
): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE rooms SET title=:t, price=:p, area=:a, address=:addr, description=:desc, thumbnail=:thumb, electric_price=:ele, water_price=:wat, shared_owner=:shared, closed_room=:closed, image1=:img1, image2=:img2, image3=:img3, image4=:img4, image5=:img5, image6=:img6, image7=:img7, image8=:img8, video_url=:video WHERE id=:id AND landlord_id=:l');
    $stmt->execute([
        ':t' => $title,
        ':p' => $price,
        ':a' => $area,
        ':addr' => $address,
        ':desc' => $description,
        ':thumb' => $thumb,
        ':ele' => $electricPrice,
        ':wat' => $waterPrice,
        ':shared' => $sharedOwner,
        ':closed' => $closedRoom,
        ':img1' => $image1,
        ':img2' => $image2,
        ':img3' => $image3,
        ':img4' => $image4,
        ':img5' => $image5,
        ':img6' => $image6,
        ':img7' => $image7,
        ':img8' => $image8,
        ':video' => $videoUrl,
        ':id' => $roomId,
        ':l' => $landlordId,
    ]);
    return $stmt->rowCount() > 0;
}

// Admin helpers
function adminFetchRooms(string $status = 'pending'): array
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $stmt = $pdo->prepare('SELECT rooms.*, users.name AS landlord_name, users.phone AS landlord_phone FROM rooms JOIN users ON users.id = rooms.landlord_id WHERE rooms.status = :s AND rooms.deleted_at IS NULL ORDER BY rooms.created_at DESC');
    $stmt->execute([':s' => $status]);
    return $stmt->fetchAll();
}

function adminSetRoomStatus(int $roomId, string $status): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE rooms SET status = :s WHERE id = :id');
    $stmt->execute([':s' => $status, ':id' => $roomId]);
    if ($status === 'active') {
        $owner = $pdo->prepare('SELECT landlord_id FROM rooms WHERE id = :id');
        $owner->execute([':id' => $roomId]);
        $landlordId = (int)$owner->fetchColumn();
        if ($landlordId) {
            addLandlordPoints($landlordId, 5);
        }
    }
    return $stmt->rowCount() > 0;
}

function adminUpdateRoomLeadPrice(int $roomId, ?int $adminPrice, ?int $finalPrice): void
{
    $pdo = getPDO();
    $adminPrice = $adminPrice !== null ? normalizeLeadPrice($adminPrice) : null;
    $finalPrice = $finalPrice !== null ? normalizeLeadPrice($finalPrice) : null;
    $stmt = $pdo->prepare('UPDATE rooms SET lead_price_admin = :admin, lead_price_final = :final WHERE id = :id');
    $stmt->execute([
        ':admin' => $adminPrice,
        ':final' => $finalPrice,
        ':id' => $roomId
    ]);
}

function adminFetchUsers(): array
{
    $pdo = getPDO();
    ensureSecurityRuntimeSchema();
    try {
        $sql = 'SELECT
                    u.id,
                    u.name,
                    u.phone,
                    u.role,
                    u.status,
                    u.phone_verified,
                    u.created_at,
                    ss.landlord_id AS scope_landlord_id,
                    ss.permissions_json AS scope_permissions_json
                FROM users u
                LEFT JOIN (
                    SELECT staff_user_id, MIN(landlord_id) AS landlord_id, MAX(permissions_json) AS permissions_json
                    FROM staff_scopes
                    WHERE status = \'active\'
                    GROUP BY staff_user_id
                ) ss ON ss.staff_user_id = u.id
                ORDER BY u.created_at DESC';
        return $pdo->query($sql)->fetchAll();
    } catch (Throwable $e) {
        error_log('Admin fetch users with staff scope failed: ' . $e->getMessage());
        return $pdo->query('SELECT id, name, phone, role, status, phone_verified, created_at FROM users ORDER BY created_at DESC')->fetchAll();
    }
}

function adminFetchLeads(): array
{
    $pdo = getPDO();
    ensureRoomSoftDeleteSchema($pdo);
    $sql = 'SELECT l.*, r.title AS room_title, u.phone AS landlord_phone
            FROM leads l
            JOIN rooms r ON r.id = l.room_id AND r.deleted_at IS NULL
            JOIN users u ON u.id = r.landlord_id
            ORDER BY l.created_at DESC';
    return $pdo->query($sql)->fetchAll();
}

function adminFetchPayments(): array
{
    $pdo = getPDO();
    $sql = 'SELECT p.*, u.name AS landlord_name, r.title AS room_title
            FROM payments p
            JOIN users u ON u.id = p.landlord_id
            LEFT JOIN leads l ON l.id = p.lead_id
            LEFT JOIN rooms r ON r.id = l.room_id
            ORDER BY p.created_at DESC';
    return $pdo->query($sql)->fetchAll();
}

function adminFetchAuditLogs(int $limit = 200): array
{
    $limit = max(20, min(500, $limit));
    $pdo = getPDO();
    ensureSecurityRuntimeSchema();

    $queries = [
        // Legacy schema currently used by the app.
        'SELECT
            al.id,
            al.action,
            al.entity_type,
            al.entity_id,
            al.actor_role,
            al.route,
            al.ip_address,
            al.user_agent,
            al.metadata_json,
            al.created_at,
            u.name AS actor_name,
            u.phone AS actor_phone
        FROM audit_logs al
        LEFT JOIN users u ON u.id = al.actor_id
        ORDER BY al.created_at DESC
        LIMIT ' . $limit,
        // Production-v2 schema compatibility.
        'SELECT
            al.id,
            al.action,
            al.table_name AS entity_type,
            al.record_id AS entity_id,
            al.actor_role,
            NULL AS route,
            al.ip_address,
            al.user_agent,
            COALESCE(al.after_data_json, al.before_data_json) AS metadata_json,
            al.created_at,
            u.name AS actor_name,
            u.phone AS actor_phone
        FROM audit_logs al
        LEFT JOIN users u ON u.id = al.actor_user_id
        ORDER BY al.created_at DESC
        LIMIT ' . $limit,
    ];

    foreach ($queries as $sql) {
        try {
            return $pdo->query($sql)->fetchAll();
        } catch (Throwable $e) {
            // Try next compatible query.
        }
    }

    return [];
}

function tenantLeadsByPhone(string $phone): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT l.*, r.title AS room_title, r.address FROM leads l JOIN rooms r ON r.id = l.room_id WHERE l.tenant_phone = :p ORDER BY l.created_at DESC');
    $stmt->execute([':p' => $phone]);
    return $stmt->fetchAll();
}

function paymentsByLandlord(int $landlordId): array
{
    $pdo = getPDO();
    expireStalePendingPayments($landlordId);
    $stmt = $pdo->prepare('SELECT p.*, l.tenant_name, l.tenant_phone, l.room_id, r.title AS room_title
                           FROM payments p
                           LEFT JOIN leads l ON l.id = p.lead_id
                           LEFT JOIN rooms r ON r.id = l.room_id
                           WHERE p.landlord_id = :l
                           ORDER BY p.created_at DESC');
    $stmt->execute([':l' => $landlordId]);
    return $stmt->fetchAll();
}

function paymentStatusLabel(array $payment): string
{
    if (isPaymentExpired($payment)) {
        return 'Hết hạn';
    }
    $map = [
        'paid' => 'Thành công',
        'failed' => 'Chưa thành công',
        'pending' => 'Chưa thành công',
    ];
    $status = (string)($payment['status'] ?? '');
    return $map[$status] ?? $status;
}

function paymentStatusSnapshotByIds(int $landlordId, array $paymentIds): array
{
    $paymentIds = array_values(array_unique(array_filter(array_map('intval', $paymentIds), function ($id) {
        return $id > 0;
    })));
    if (empty($paymentIds)) {
        return [];
    }

    $pdo = getPDO();
    expireStalePendingPayments($landlordId);
    $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));
    $sql = "SELECT * FROM payments WHERE landlord_id = ? AND id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$landlordId], $paymentIds));

    $out = [];
    foreach ($stmt->fetchAll() as $payment) {
        $out[] = [
            'id' => (int)$payment['id'],
            'status' => (string)$payment['status'],
            'status_label' => paymentStatusLabel($payment),
            'provider' => (string)($payment['provider'] ?? ''),
            'payment_code' => (string)($payment['payment_code'] ?? ''),
            'expires_at' => (string)($payment['expires_at'] ?? ''),
            'can_show_qr' => ((string)($payment['status'] ?? '') === 'pending') && !isPaymentExpired($payment) && !empty($payment['payment_code']),
        ];
    }
    return $out;
}

function adminUpdateUserRole(int $userId, string $role): bool
{
    $allowed = ['tenant', 'landlord', 'staff', 'admin'];
    if ($userId <= 0 || !in_array($role, $allowed, true)) {
        return false;
    }

    ensureSecurityRuntimeSchema();
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE users SET role = :r WHERE id = :id');
    $stmt->execute([':r' => $role, ':id' => $userId]);
    if ($role !== 'staff') {
        try {
            $pdo->prepare('UPDATE staff_scopes SET status = :status WHERE staff_user_id = :staff_user_id')
                ->execute([
                    ':status' => 'revoked',
                    ':staff_user_id' => $userId,
                ]);
        } catch (Throwable $e) {
            // Ignore when scope table is unavailable.
        }
    }
    return $stmt->rowCount() > 0;
}

function adminUpsertStaffScope(int $staffUserId, int $landlordId, array $permissions = []): bool
{
    if ($staffUserId <= 0 || $landlordId <= 0) {
        return false;
    }

    ensureSecurityRuntimeSchema();
    $pdo = getPDO();
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $normalized = staffPermissionDefaults();
    foreach ($normalized as $key => $default) {
        $normalized[$key] = !empty($permissions[$key]) ? 1 : (int)$default;
    }
    $payload = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    try {
        if ($driver === 'mysql') {
            $stmt = $pdo->prepare('INSERT INTO staff_scopes (
                    staff_user_id, landlord_id, permissions_json, status
                ) VALUES (
                    :staff_user_id, :landlord_id, :permissions_json, :status
                )
                ON DUPLICATE KEY UPDATE
                    permissions_json = VALUES(permissions_json),
                    status = VALUES(status),
                    updated_at = CURRENT_TIMESTAMP');
        } else {
            $stmt = $pdo->prepare('INSERT INTO staff_scopes (
                    staff_user_id, landlord_id, permissions_json, status
                ) VALUES (
                    :staff_user_id, :landlord_id, :permissions_json, :status
                )
                ON CONFLICT(staff_user_id, landlord_id) DO UPDATE SET
                    permissions_json = excluded.permissions_json,
                    status = excluded.status,
                    updated_at = CURRENT_TIMESTAMP');
        }
        $stmt->execute([
            ':staff_user_id' => $staffUserId,
            ':landlord_id' => $landlordId,
            ':permissions_json' => $payload,
            ':status' => 'active',
        ]);

        return true;
    } catch (Throwable $e) {
        error_log('Upsert staff scope failed: ' . $e->getMessage());
        return false;
    }
}

function adminUpdateUserStatus(int $userId, string $status): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE users SET status = :s WHERE id = :id');
    $stmt->execute([':s' => $status, ':id' => $userId]);
    return $stmt->rowCount() > 0;
}

function adminUpdateUserPhoneVerification(int $userId, int $verified): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE users SET phone_verified = :v WHERE id = :id');
    $stmt->execute([':v' => $verified ? 1 : 0, ':id' => $userId]);
    return $stmt->rowCount() > 0;
}

function adminUpdateLeadStatus(int $leadId, string $status): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE leads SET status = :s WHERE id = :id');
    $stmt->execute([':s' => $status, ':id' => $leadId]);
    return $stmt->rowCount() > 0;
}

function adminUpdatePaymentStatus(int $paymentId, string $status): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE payments SET status = :s WHERE id = :id');
    $stmt->execute([':s' => $status, ':id' => $paymentId]);
    return $stmt->rowCount() > 0;
}

// Boost phòng
function boostQuotaForLandlord(int $landlordId): int
{
    if (isAdmin()) {
        return 999;
    }
    $vip = landlordVipTier($landlordId);
    $tier = $vip['tier'] ?? 'Thường';
    switch ($tier) {
        case 'VIP 2':
            return 1;
        case 'VIP 3':
        case 'VIP 4':
            return 3;
        default:
            return 0;
    }
}

function boostUsedToday(int $landlordId): int
{
    $pdo = getPDO();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $sql = $driver === 'mysql'
        ? 'SELECT COUNT(*) FROM room_boosts WHERE landlord_id = :l AND DATE(created_at) = CURDATE()'
        : 'SELECT COUNT(*) FROM room_boosts WHERE landlord_id = :l AND date(created_at) = date(\"now\")';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':l' => $landlordId]);
    return (int)$stmt->fetchColumn();
}

function boostRoom(int $roomId, int $landlordId): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT landlord_id FROM rooms WHERE id = :id');
    $stmt->execute([':id' => $roomId]);
    $owner = (int)$stmt->fetchColumn();
    if ($owner !== $landlordId) return false;

    $limit = boostQuotaForLandlord($landlordId);
    if ($limit <= 0) return false;
    if (boostUsedToday($landlordId) >= $limit) return false;

    $pdo->prepare('INSERT INTO room_boosts (landlord_id, room_id) VALUES (:l,:r)')
        ->execute([':l' => $landlordId, ':r' => $roomId]);

    $pdo->prepare('UPDATE rooms SET boost_until = :u WHERE id = :id')
        ->execute([':u' => date('Y-m-d H:i:s', time() + 12*3600), ':id' => $roomId]);
    return true;
}

// Insights cho chủ trọ: giờ hot, khu hot, giá trung bình
function landlordInsights(int $landlordId): array
{
    $pdo = getPDO();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Top giờ có lead
    if ($driver === 'mysql') {
        $sqlHour = 'SELECT HOUR(l.created_at) AS h, COUNT(*) AS c
                    FROM leads l JOIN rooms r ON r.id = l.room_id
                    WHERE r.landlord_id = :l
                    GROUP BY HOUR(l.created_at)
                    ORDER BY c DESC LIMIT 3';
    } else {
        $sqlHour = 'SELECT CAST(strftime("%H", l.created_at) AS INTEGER) AS h, COUNT(*) AS c
                    FROM leads l JOIN rooms r ON r.id = l.room_id
                    WHERE r.landlord_id = :l
                    GROUP BY strftime("%H", l.created_at)
                    ORDER BY c DESC LIMIT 3';
    }
    $stmt = $pdo->prepare($sqlHour);
    $stmt->execute([':l' => $landlordId]);
    $hotHours = $stmt->fetchAll();

    // Khu hot (area)
    $sqlArea = 'SELECT r.area AS area, COUNT(*) AS c
                FROM leads l JOIN rooms r ON r.id = l.room_id
                WHERE r.landlord_id = :l
                GROUP BY r.area
                ORDER BY c DESC
                LIMIT 3';
    $stmt = $pdo->prepare($sqlArea);
    $stmt->execute([':l' => $landlordId]);
    $hotAreas = $stmt->fetchAll();

    // Giá min/avg/max của lead đã nhận
    $sqlPrice = 'SELECT MIN(r.price) AS min_price, MAX(r.price) AS max_price, AVG(r.price) AS avg_price
                 FROM leads l JOIN rooms r ON r.id = l.room_id
                 WHERE r.landlord_id = :l';
    $stmt = $pdo->prepare($sqlPrice);
    $stmt->execute([':l' => $landlordId]);
    $priceStats = $stmt->fetch() ?: ['min_price'=>0,'max_price'=>0,'avg_price'=>0];

    return [
        'hot_hours' => $hotHours,
        'hot_areas' => $hotAreas,
        'price_stats' => $priceStats,
    ];
}
