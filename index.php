<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/repositories.php';

$route = normalizeIncomingRoute($_GET);
protectIncomingRequest($route);

switch ($route) {
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            $isLandlord = isset($_POST['is_landlord']);
            // Validate phone: exactly 10 digits, bắt đầu bằng 0
            if (!preg_match('/^0\d{9}$/', $phone)) {
                flash('error', 'Số điện thoại phải đủ 10 số và bắt đầu bằng 0.');
                redirect('register');
            }
            // Require full name: tối thiểu 2 từ
            if ($name === '' || mb_strlen($name) < 5 || !preg_match('/\s+/', $name)) {
                flash('error', 'Vui lòng nhập HỌ VÀ TÊN đầy đủ (ít nhất 2 từ).');
                redirect('register');
            }
            if ($password !== $passwordConfirm) {
                flash('error', 'Mật khẩu nhập lại không khớp.');
                redirect('register');
            }
            if ($name === '' || $phone === '' || $password === '') {
                flash('error', 'Vui lòng nhập đủ thông tin.');
                redirect('register');
            }
            if (findUserByPhone($phone)) {
                flash('error', 'Số điện thoại đã tồn tại.');
                redirect('register');
            }
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $role = $isLandlord ? 'landlord' : 'tenant';
            $user = createUser($name, $phone, $hash, $role);
            if ($user) {
                loginUser($user);
                flash('success', 'Đăng ký thành công.');
                redirect(defaultRouteForUser($user));
            }
            flash('error', 'Không tạo được tài khoản.');
            redirect('register');
        }
        render('register', [
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $redirectTo = $_POST['redirect'] ?? 'rooms';
            $ip = clientIpAddress();
            $retryAfter = 0;
            if (isLoginTemporarilyBlocked($phone, $ip, $retryAfter)) {
                flash('error', 'Bạn đăng nhập sai quá nhiều lần. Vui lòng thử lại sau ' . max(30, $retryAfter) . ' giây.');
                securityLog('login_blocked', ['phone' => $phone, 'retry_after' => $retryAfter]);
                redirect('login', ['redirect' => $redirectTo]);
            }
            $user = findUserByPhone($phone);
            if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
                recordFailedLoginAttempt($phone, $ip);
                securityLog('login_failed', ['phone' => $phone]);
                flash('error', 'Sai SĐT hoặc mật khẩu.');
                redirect('login', ['redirect' => $redirectTo]);
            }
            if (($user['status'] ?? 'active') !== 'active') {
                recordFailedLoginAttempt($phone, $ip);
                securityLog('login_locked_account', ['phone' => $phone, 'user_id' => (int)($user['id'] ?? 0)]);
                flash('error', 'Tài khoản đã bị khóa.');
                redirect('login');
            }
            clearFailedLoginAttempts($phone, $ip);
            loginUser($user);
            auditLog('auth.login_success', [
                'entity_type' => 'user',
                'entity_id' => (string)((int)($user['id'] ?? 0)),
            ]);
            flash('success', 'Đăng nhập thành công.');
            redirectToInternalRoute($redirectTo, defaultRouteForUser($user));
        }
        render('login', [
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
            'redirect' => $_GET['redirect'] ?? '',
        ]);
        break;

    case 'logout':
        auditLog('auth.logout');
        logoutUser();
        flash('success', 'Đã đăng xuất.');
        redirect('rooms');
        break;

    case 'mode-management':
        $user = ensureLoggedIn();
        $role = (string)($user['role'] ?? '');
        if (!in_array($role, ['tenant', 'landlord', 'staff', 'admin'], true)) {
            redirect(defaultRouteForUser($user));
        }

        $state = strtolower(trim((string)($_GET['state'] ?? '')));
        $enabled = in_array($state, ['on', '1', 'true'], true);
        $_SESSION['ui_management_mode_' . $role] = $enabled ? '1' : '0';

        if ($enabled) {
            redirect(defaultRouteForUser($user));
        }

        if ($role === 'landlord' || $role === 'staff') {
            redirect('room-create');
        }
        if ($role === 'tenant') {
            redirect('rooms');
        }
        if ($role === 'admin') {
            redirect('rooms');
        }
        redirect('rooms');
        break;

    case 'rooms':
        $filters = [
            'keyword' => trim($_GET['keyword'] ?? ''),
            'area' => trim($_GET['area'] ?? ''),
            'district' => trim($_GET['district'] ?? ''),
            'ward' => trim($_GET['ward'] ?? ''),
            'min_price' => trim($_GET['min_price'] ?? ''),
            'max_price' => trim($_GET['max_price'] ?? ''),
            'min_electric_price' => trim($_GET['min_electric_price'] ?? ''),
            'max_electric_price' => trim($_GET['max_electric_price'] ?? ''),
            'min_water_price' => trim($_GET['min_water_price'] ?? ''),
            'max_water_price' => trim($_GET['max_water_price'] ?? ''),
            'province' => trim($_GET['province'] ?? ''),
            'near_school' => isset($_GET['near_school']) ? (string)$_GET['near_school'] : '',
            'shared_owner' => isset($_GET['shared_owner']) ? (string)$_GET['shared_owner'] : '',
            'closed_room' => isset($_GET['closed_room']) ? (string)$_GET['closed_room'] : '',
        ];
        $sort = $_GET['sort'] ?? 'vip';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 12;

        // lấy toàn bộ danh sách đã được ưu tiên boost/VIP, sau đó áp dụng sort phụ nếu cần
        $roomsAll = fetchRooms($filters);
        if ($sort === 'price_asc') {
            usort($roomsAll, static function ($a, $b) {
                return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
            });
        } elseif ($sort === 'price_desc') {
            usort($roomsAll, static function ($a, $b) {
                return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
            });
        } elseif ($sort === 'newest') {
            usort($roomsAll, static function ($a, $b) {
                return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            });
        }

        $total = count($roomsAll);
        $pages = max(1, (int)ceil($total / $perPage));
        if ($page > $pages) $page = $pages;
        $offset = ($page - 1) * $perPage;
        $rooms = array_slice($roomsAll, $offset, $perPage);
        $tenantPosts = fetchTenantPosts();
        render('rooms_list', [
            'rooms' => $rooms,
            'tenantPosts' => $tenantPosts,
            'filters' => $filters,
            'pagination' => [
                'page' => $page,
                'pages' => $pages,
                'total' => $total,
                'per_page' => $perPage,
            ],
            'sort' => $sort,
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'search':
        $filters = [
            'keyword' => trim($_GET['keyword'] ?? ''),
            'area' => trim($_GET['area'] ?? ''),
            'district' => trim($_GET['district'] ?? ''),
            'ward' => trim($_GET['ward'] ?? ''),
            'province' => trim($_GET['province'] ?? ''),
            'min_price' => trim($_GET['min_price'] ?? ''),
            'max_price' => trim($_GET['max_price'] ?? ''),
            'min_electric_price' => trim($_GET['min_electric_price'] ?? ''),
            'max_electric_price' => trim($_GET['max_electric_price'] ?? ''),
            'min_water_price' => trim($_GET['min_water_price'] ?? ''),
            'max_water_price' => trim($_GET['max_water_price'] ?? ''),
            'near_school' => isset($_GET['near_school']) ? (string)$_GET['near_school'] : '',
            'shared_owner' => isset($_GET['shared_owner']) ? (string)$_GET['shared_owner'] : '',
            'closed_room' => isset($_GET['closed_room']) ? (string)$_GET['closed_room'] : '',
        ];
        $sort = $_GET['sort'] ?? 'vip';
        $roomsAll = fetchRooms($filters);
        if ($sort === 'price_asc') {
            usort($roomsAll, static function ($a, $b) {
                return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
            });
        } elseif ($sort === 'price_desc') {
            usort($roomsAll, static function ($a, $b) {
                return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
            });
        } elseif ($sort === 'newest') {
            usort($roomsAll, static function ($a, $b) {
                return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            });
        }
        render('search', [
            'rooms' => $roomsAll,
            'filters' => $filters,
            'sort' => $sort,
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'notifications':
        $user = ensureLoggedIn();
        $notificationId = max(0, (int)($_GET['notification_id'] ?? 0));
        if ($notificationId > 0) {
            markNotificationReadState($notificationId, (int)$user['id'], true);
        }
        $notifications = notificationCenterByUser($user, 80);
        $unreadCount = unreadNotificationCountByUser((int)$user['id']);
        render('notifications', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'highlightNotificationId' => $notificationId,
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'notifications-mark':
        $user = ensureLoggedIn();
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }
        $userId = (int)$user['id'];
        if (!empty($payload['mark_all'])) {
            markAllNotificationsRead($userId);
            echo json_encode([
                'ok' => true,
                'unread_count' => unreadNotificationCountByUser($userId),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $notificationId = max(0, (int)($payload['notification_id'] ?? 0));
        if ($notificationId <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'invalid_notification_id'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $isRead = !empty($payload['is_read']);
        if (!markNotificationReadState($notificationId, $userId, $isRead)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        echo json_encode([
            'ok' => true,
            'notification_id' => $notificationId,
            'is_read' => $isRead,
            'unread_count' => unreadNotificationCountByUser($userId),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;

    case 'room':
        $roomId = (int)($_GET['id'] ?? 0);
        $room = fetchRoom($roomId);
        if (!$room) {
            http_response_code(404);
            echo 'Không tìm thấy phòng';
            exit;
        }
        $similar = fetchSimilarRooms($roomId, $room['area'] ?? '', $room['district'] ?? '', 6);
        render('room_detail', [
            'room' => $room,
            'similarRooms' => $similar,
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'lead':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('rooms');
        }
        $roomId = (int)($_POST['room_id'] ?? 0);
        if (!currentUser()) {
            flash('error', 'Vui lòng đăng nhập để gửi quan tâm.');
            redirect('login', ['redirect' => '?route=room&id=' . $roomId]);
        }
        $room = fetchRoom($roomId);
        if (!$room) {
            flash('error', 'Phòng không tồn tại.');
            redirect('rooms');
        }
        if (!empty($room['slots_left']) && (int)$room['slots_left'] <= 0) {
            flash('error', 'Chủ trọ đã hết lượt mở SĐT hôm nay, vui lòng thử lại sau.');
            redirect('room', ['id' => $roomId]);
        }
        $user = currentUser();
        if ($user['role'] !== 'tenant' && $user['role'] !== 'admin') {
            flash('error', 'Chỉ tài khoản người tìm phòng mới được tạo lead.');
            redirect('room', ['id' => $roomId]);
        }
        $name = trim($_POST['name'] ?? ($user['name'] ?? ''));
        $phone = trim($_POST['phone'] ?? ($user['phone'] ?? ''));

        // Admin không bị giới hạn; tenant bị giới hạn số lượt và thời gian
        if (($user['role'] ?? '') !== 'admin') {
            $dailyLimit = 5; // tất cả tài khoản cùng hạn mức mỗi ngày
            $todayLeads = countLeadsTodayByTenant((int)$user['id'], $phone);
            if ($todayLeads >= $dailyLimit) {
                flash('error', 'Bạn đã đạt giới hạn ' . $dailyLimit . ' phòng/ngày. Vui lòng quay lại ngày mai.');
                redirect('room', ['id' => $roomId]);
            }

            $lastLeadAt = lastLeadCreatedAt((int)$user['id'], $phone);
            if ($lastLeadAt && (time() - strtotime($lastLeadAt)) < 60) {
                flash('error', 'Bạn thao tác quá nhanh, vui lòng chờ 60 giây trước khi gửi thêm.');
                redirect('room', ['id' => $roomId]);
            }
        }

        $leadOptions = [
            'min_price' => isset($_POST['lead_min_price']) && $_POST['lead_min_price'] !== '' ? (int)$_POST['lead_min_price'] : null,
            'max_price' => isset($_POST['lead_max_price']) && $_POST['lead_max_price'] !== '' ? (int)$_POST['lead_max_price'] : null,
            'province' => trim($_POST['lead_province'] ?? ''),
            'district' => trim($_POST['lead_district'] ?? ''),
            'ward' => trim($_POST['lead_ward'] ?? ''),
            'time_slot' => trim($_POST['lead_time_slot'] ?? ''),
        ];

        if ($roomId <= 0 || $phone === '') {
            flash('error', 'Vui lòng nhập SĐT');
            redirect('room', ['id' => $roomId]);
        }
        if (!isThousandAmount($leadOptions['min_price']) || !isThousandAmount($leadOptions['max_price'])) {
            flash('error', 'Ngân sách phải là bội của 1.000đ.');
            redirect('room', ['id' => $roomId]);
        }

        $existingLeadId = findExistingLead($roomId, (int)$user['id'], $phone);
        if ($existingLeadId) {
            flash('success', 'Bạn đã gửi yêu cầu cho phòng này. Vui lòng chờ chủ trọ liên hệ.');
            redirect('room', ['id' => $roomId]);
        }

        if ($name === '') {
            $name = trim($user['name'] ?? '');
        }
        if ($name === '') {
            $name = 'Khách';
        }

        // fetchRoom đã được gọi phía trên; không cần gọi lại

        $createdId = createLead((int)$user['id'], $name, $phone, $roomId, $leadOptions);
        $remaining = max(0, $dailyLimit - ($todayLeads + 1));
        flash('success', 'Đã gửi quan tâm, chủ trọ sẽ liên hệ. Bạn còn ' . $remaining . '/' . $dailyLimit . ' lượt hôm nay.');
        redirect('room', ['id' => $roomId]);
        break;

    case 'seek-posts':
        $user = ensureLoggedIn('tenant'); // admin được phép qua ensureLoggedIn
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $kind = $_POST['post_kind'] ?? 'roommate';
            $area = trim($_POST['area'] ?? '');
            $priceMin = $_POST['price_min'] !== '' ? (int)$_POST['price_min'] : null;
            $priceMax = $_POST['price_max'] !== '' ? (int)$_POST['price_max'] : null;
            $people = $_POST['people_count'] !== '' ? (int)$_POST['people_count'] : null;
            $note = trim($_POST['note'] ?? '');
            $gender = $_POST['gender'] ?? 'any';
            $postMeta = [
                'post_kind' => $kind,
                'district' => '',
                'ward' => '',
                'near_place' => '',
                'room_type' => $kind === 'room' ? '' : 'Ở ghép',
                'move_in_time' => '',
                'area_min' => null,
                'priority' => '',
                'shared_owner' => null,
                'closed_room' => null,
                'amenities' => '',
                'amenities_list' => '',
            ];

            if ($area === '' || $note === '') {
                flash('error', 'Vui lòng nhập khu vực và mô tả nhu cầu.');
                redirect('seek-posts');
            }
            if ($priceMin === null || $priceMax === null || $priceMin <= 0 || $priceMax <= 0) {
                flash('error', 'Vui lòng nhập ngân sách tối thiểu và tối đa hợp lệ.');
                redirect('seek-posts');
            }
            if ($priceMax < $priceMin) {
                flash('error', 'Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu.');
                redirect('seek-posts');
            }
            if (!isPositiveThousandAmount($priceMin) || !isPositiveThousandAmount($priceMax)) {
                flash('error', 'Ngân sách phải lớn hơn 0 và là bội của 1.000đ.');
                redirect('seek-posts');
            }

            if ($kind === 'roommate') {
                if ($people === null || $people <= 0) {
                    flash('error', 'Vui lòng nhập số người (tối thiểu 1).');
                    redirect('seek-posts');
                }
                if (!in_array($gender, ['male','female','any'], true) || $gender === 'any') {
                    // cho phép any nhưng sẽ gán 'any'
                    $gender = $gender === 'any' ? 'any' : $gender;
                }
                if (containsContactInfo($note)) {
                    flash('error', 'Không được chèn SĐT/Zalo/liên hệ trong nội dung.');
                    redirect('seek-posts');
                }
                $roomAddress = trim($_POST['room_address'] ?? '');
                $totalPrice = $_POST['total_price'] !== '' ? (int)$_POST['total_price'] : null;
                $sharePrice = $_POST['share_price'] !== '' ? (int)$_POST['share_price'] : null;
                $needPeople = $_POST['need_people'] !== '' ? (int)$_POST['need_people'] : null;
                $currentPeople = $_POST['current_people'] !== '' ? (int)$_POST['current_people'] : null;
                $schedule = trim($_POST['schedule'] ?? '');
                if ($roomAddress === '') {
                    flash('error', 'Vui lòng nhập địa chỉ phòng đang ở.');
                    redirect('seek-posts');
                }
                if (($totalPrice !== null && !isPositiveThousandAmount($totalPrice)) || ($sharePrice !== null && !isThousandAmount($sharePrice))) {
                    flash('error', 'Tổng tiền phòng và chi phí mỗi người phải là bội của 1.000đ.');
                    redirect('seek-posts');
                }
                // ảnh tham khảo cho roommate vẫn khuyến khích nhưng không bắt buộc
                $roomImage1 = handleUpload('room_image1', ['jpg','jpeg','png','gif','webp']);
                $roomImage2 = handleUpload('room_image2', ['jpg','jpeg','png','gif','webp']) ?? '';
                $roomImage3 = handleUpload('room_image3', ['jpg','jpeg','png','gif','webp']) ?? '';
                if (isset($_FILES['room_image1']) && !empty($_FILES['room_image1']['name']) && !$roomImage1) {
                    flash('error', 'Tệp ảnh phòng không hợp lệ. Chỉ nhận jpg, jpeg, png, gif, webp.');
                    redirect('seek-posts');
                }
                $sharedOwner = isset($_POST['shared_owner']) ? 1 : 0;
                $closedRoom  = isset($_POST['closed_room']) ? 1 : 0;
                // Ghép thông tin vào note
                $note .= "\n- Địa chỉ phòng: " . $roomAddress;
                if ($totalPrice) $note .= "\n- Tổng tiền phòng: " . number_format($totalPrice, 0, ',', '.') . " đ";
                if ($sharePrice) $note .= "\n- Chi phí mỗi người: " . number_format($sharePrice, 0, ',', '.') . " đ";
                if ($currentPeople) $note .= "\n- Đang ở: {$currentPeople} người";
                if ($needPeople) $note .= "\n- Cần thêm: {$needPeople} người";
                if ($schedule !== '') $note .= "\n- Tính cách / giờ giấc: " . $schedule;
                $note .= "\n- Chung chủ: " . ($sharedOwner ? 'Có' : 'Không');
                $note .= "\n- Khép kín: " . ($closedRoom ? 'Có' : 'Không');

                // map giá: tổng phòng -> price_min, share -> price_max
                $priceMin = $totalPrice ?? $priceMin;
                $priceMax = $sharePrice ?? $priceMax;
                $people = ($currentPeople ?? 1) + ($needPeople ?? 0);
                $area = $roomAddress;
                $postMeta['shared_owner'] = $sharedOwner;
                $postMeta['closed_room'] = $closedRoom;
            } else { // kind = room (đăng tìm phòng)
                // nhẹ nhàng hơn: không bắt ảnh, people mặc định 1, gender any
                $people = $people && $people > 0 ? $people : 1;
                $gender = 'any';
                $roomImage1 = handleUpload('room_image1', ['jpg','jpeg','png','gif','webp']) ?? '';
                $roomImage2 = handleUpload('room_image2', ['jpg','jpeg','png','gif','webp']) ?? '';
                $roomImage3 = handleUpload('room_image3', ['jpg','jpeg','png','gif','webp']) ?? '';
                $moveIn = trim($_POST['move_in'] ?? '');
                $amenities = trim($_POST['amenities'] ?? '');
                $amenitiesCheck = $_POST['amenities_check'] ?? [];
                $roomType = trim($_POST['room_type'] ?? '');
                $district = trim($_POST['district'] ?? '');
                $ward = trim($_POST['ward'] ?? '');
                $nearPlace = trim($_POST['near_place'] ?? '');
                $areaMin = $_POST['area_min'] !== '' ? (int)$_POST['area_min'] : null;
                $priority = trim($_POST['priority'] ?? '');
                $sharedOwner = isset($_POST['shared_owner']) ? 1 : 0;
                $closedRoom  = isset($_POST['closed_room']) ? 1 : 0;
                if ($roomType !== '') $note .= "\n- Loại phòng: " . $roomType;
                if ($district !== '' || $ward !== '' || $nearPlace !== '') {
                    $note .= "\n- Khu vực chi tiết: ";
                    $parts = array_filter([$ward, $district, $nearPlace]);
                    $note .= implode(' • ', $parts);
                }
                if ($areaMin) $note .= "\n- Diện tích tối thiểu: " . $areaMin . " m²";
                if ($moveIn !== '') $note .= "\n- Thời gian chuyển đến: " . $moveIn;
                if ($amenities !== '') $note .= "\n- Tiện nghi mong muốn: " . $amenities;
                if (!empty($amenitiesCheck) && is_array($amenitiesCheck)) {
                    $note .= "\n- Yêu cầu nội thất: " . implode(', ', array_map('trim', $amenitiesCheck));
                }
                if ($priority !== '') $note .= "\n- Ưu tiên: " . $priority;
                $note .= "\n- Chung chủ: " . ($sharedOwner ? 'Có' : 'Không');
                $note .= "\n- Khép kín: " . ($closedRoom ? 'Có' : 'Không');
                $postMeta = [
                    'post_kind' => 'room',
                    'district' => $district,
                    'ward' => $ward,
                    'near_place' => $nearPlace,
                    'room_type' => $roomType,
                    'move_in_time' => $moveIn,
                    'area_min' => $areaMin,
                    'priority' => $priority,
                    'shared_owner' => $sharedOwner,
                    'closed_room' => $closedRoom,
                    'amenities' => $amenities,
                    'amenities_list' => !empty($amenitiesCheck) && is_array($amenitiesCheck) ? implode(', ', array_map('trim', $amenitiesCheck)) : '',
                ];
            }

            createTenantPost((int)$user['id'], $area, $priceMin, $priceMax, $people, $note, $gender, $roomImage1 ?? '', $roomImage2 ?? '', $roomImage3 ?? '', $postMeta);
            $successMsg = $kind === 'room' ? 'Đã đăng nhu cầu tìm phòng.' : 'Đã đăng nhu cầu tìm người ở ghép.';
            flash('success', $successMsg);
            redirect('seek-posts');
        }
        $posts = fetchTenantPosts();
        render('seek_posts', [
            'posts' => $posts,
            'user' => $user,
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'portal-landlord':
        $landlordId = ensureLandlord();
        $authUser = currentUser();
        $canViewLead = !empty($authUser)
            ? (($authUser['role'] ?? '') !== 'staff' || staffHasPermission('lead_view'))
            : true;

        $opsDashboard = landlordOperationDashboardSummary($landlordId);
        $payments = paymentsByLandlord($landlordId);
        $paidPayments = 0;
        foreach ($payments as $payment) {
            if ((string)($payment['status'] ?? '') === 'paid') {
                $paidPayments++;
            }
        }

        render('portal_landlord', [
            'activeSection' => trim((string)($_GET['section'] ?? 'dashboard')) ?: 'dashboard',
            'portalStats' => [
                'rooms' => countRoomsByLandlord($landlordId),
                'leads' => $canViewLead ? countLeadsByLandlord($landlordId) : 0,
                'open_issues' => (int)($opsDashboard['open_issues'] ?? 0),
                'unpaid_invoices' => (int)($opsDashboard['unpaid_invoices'] ?? 0),
                'paid_payments' => $paidPayments,
                'revenue_month' => (int)($opsDashboard['revenue_month'] ?? 0),
            ],
            'permissions' => [
                'lead_view' => $canViewLead,
                'room_manage' => ($authUser['role'] ?? '') !== 'staff' || staffHasPermission('room_manage'),
                'invoice_manage' => ($authUser['role'] ?? '') !== 'staff' || staffHasPermission('invoice_manage'),
            ],
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'portal-tenant':
        $user = ensureLoggedIn();
        if (($user['role'] ?? '') !== 'tenant' && ($user['role'] ?? '') !== 'admin') {
            flash('error', 'Chỉ người thuê mới vào được portal người thuê.');
            redirect('profile');
        }
        $staySpace = tenantStaySpaceByUser($user);
        $invoices = $staySpace['invoices'] ?? [];
        $issues = $staySpace['issues'] ?? [];
        $notices = $staySpace['notices'] ?? [];

        $unpaidInvoices = 0;
        foreach ($invoices as $invoice) {
            $displayStatus = (string)($invoice['display_status'] ?? $invoice['status'] ?? '');
            if (!in_array($displayStatus, ['paid', 'cancelled'], true)) {
                $unpaidInvoices++;
            }
        }

        $openIssues = 0;
        foreach ($issues as $issue) {
            $status = (string)($issue['status'] ?? 'open');
            if (!in_array($status, ['resolved', 'closed'], true)) {
                $openIssues++;
            }
        }

        render('portal_tenant', [
            'activeSection' => trim((string)($_GET['section'] ?? 'dashboard')) ?: 'dashboard',
            'portalStats' => [
                'linked_rooms' => (int)($staySpace['linked_room_count'] ?? 0),
                'unpaid_invoices' => $unpaidInvoices,
                'open_issues' => $openIssues,
                'notices' => count($notices),
                'contract_days_left' => $staySpace['contract_days_left'] ?? null,
            ],
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'portal-admin':
        $user = ensureLoggedIn();
        if (($user['role'] ?? '') !== 'admin') {
            flash('error', 'Bạn không có quyền truy cập portal admin.');
            redirect('login');
        }
        $section = trim((string)($_GET['section'] ?? 'dashboard')) ?: 'dashboard';
        $sectionRouteMap = [
            'dashboard' => 'admin',
            'users' => 'admin-users',
            'leads' => 'admin-leads',
            'transactions' => 'admin-payments',
            'reports' => 'admin-reports',
            'settings' => 'admin-settings',
            'audit' => 'admin-audit-logs',
        ];
        redirect($sectionRouteMap[$section] ?? 'admin');
        break;

    case 'dashboard':
        $landlordId = ensureLandlord();
        $authUser = currentUser();
        $tab = $_GET['tab'] ?? 'home';
        if (($authUser['role'] ?? '') === 'staff' && $tab === 'lead' && !staffHasPermission('lead_view')) {
            flash('error', 'Bạn chưa được cấp quyền xem lead.');
            redirect('dashboard');
        }
        $activeMenu = 'home';
        if ($tab === 'lead') {
            $activeMenu = 'leads';
        } elseif ($tab === 'payments') {
            $activeMenu = 'payments';
        }
        $canViewLead = !empty($authUser) ? (($authUser['role'] ?? '') !== 'staff' || staffHasPermission('lead_view')) : true;
        $leads = $canViewLead ? getLeadsForLandlord($landlordId) : [];
        $roomCount = countRoomsByLandlord($landlordId);
        $leadCount = countLeadsByLandlord($landlordId);
        $latestLeads = latestLeadsByLandlord($landlordId, 5);
        $vip = landlordVipTier($landlordId);
        $insights = landlordInsights($landlordId);
        $leadStats = countLeadStatsByStatus($landlordId);
        $opsDashboard = landlordOperationDashboardSummary($landlordId);
        $leadMarketplace = $canViewLead ? landlordLeadMarketplace($landlordId) : [];
        $fullContactCount = 0;
        foreach ($leads as $leadRow) {
            if (leadHasUnlockedContact($leadRow)) {
                $fullContactCount++;
            }
        }
        if ($fullContactCount > 0) {
            auditLog('lead.full_contact_view', [
                'entity_type' => 'lead',
                'entity_id' => 'multiple',
                'count' => $fullContactCount,
                'view_scope' => 'dashboard',
            ]);
        }
        render('dashboard', [
            'leads' => $leads,
            'leadCount' => $leadCount,
            'roomCount' => $roomCount,
            'latestLeads' => $latestLeads,
            'activeMenu' => $activeMenu,
            'vip' => $vip,
            'insights' => $insights,
            'leadStats' => $leadStats,
            'leadMarketplace' => $leadMarketplace,
            'opsDashboard' => $opsDashboard,
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'lead-notifications':
        $user = ensureLoggedIn();
        header('Content-Type: application/json; charset=utf-8');
        if (($user['role'] ?? '') !== 'landlord') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $landlordId = (int)$user['id'];
        $seed = ($_GET['seed'] ?? '') === '1';
        $latestId = latestLeadIdByLandlord($landlordId);
        $unreadCount = countUnreadLeadNotificationsByLandlord($landlordId);
        if ($seed) {
            echo json_encode([
                'ok' => true,
                'latest_id' => $latestId,
                'unread_count' => $unreadCount,
                'items' => [],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $afterId = max(0, (int)($_GET['after_id'] ?? 0));
        echo json_encode([
            'ok' => true,
            'latest_id' => $latestId,
            'unread_count' => $unreadCount,
            'items' => leadNotificationsByLandlord($landlordId, $afterId, 10),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;

    case 'lead-notifications-mark':
        $user = ensureLoggedIn();
        header('Content-Type: application/json; charset=utf-8');
        if (($user['role'] ?? '') !== 'landlord') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }
        $landlordId = (int)$user['id'];
        $markAll = !empty($payload['mark_all']);
        if ($markAll) {
            markAllLeadNotificationsRead($landlordId);
            echo json_encode([
                'ok' => true,
                'unread_count' => countUnreadLeadNotificationsByLandlord($landlordId),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $leadId = max(0, (int)($payload['lead_id'] ?? 0));
        if ($leadId <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'invalid_lead_id'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $isRead = !empty($payload['is_read']);
        if (!setLeadNotificationReadState($landlordId, $leadId, $isRead)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        echo json_encode([
            'ok' => true,
            'lead_id' => $leadId,
            'is_read' => $isRead,
            'unread_count' => countUnreadLeadNotificationsByLandlord($landlordId),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;

    case 'lead-notifications-stream':
        $user = ensureLoggedIn();
        if (($user['role'] ?? '') !== 'landlord') {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'forbidden';
            exit;
        }

        session_write_close();
        ignore_user_abort(true);
        set_time_limit(30);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $landlordId = (int)$user['id'];
        $afterId = max(0, (int)($_GET['after_id'] ?? 0));
        $startedAt = time();
        $lastPingAt = 0;

        echo ':' . str_repeat(' ', 2048) . "\n\n";
        echo "retry: 2500\n\n";
        @flush();

        while (!connection_aborted() && (time() - $startedAt) < 25) {
            $items = leadNotificationsByLandlord($landlordId, $afterId, 10);
            if (!empty($items)) {
                $lastItem = end($items);
                $afterId = max($afterId, (int)($lastItem['id'] ?? 0));
                $payload = [
                    'ok' => true,
                    'latest_id' => $afterId,
                    'unread_count' => countUnreadLeadNotificationsByLandlord($landlordId),
                    'items' => array_values($items),
                ];
                echo "event: lead\n";
                echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                @flush();
            } elseif ((time() - $lastPingAt) >= 10) {
                $latestId = latestLeadIdByLandlord($landlordId);
                echo "event: ping\n";
                echo 'data: ' . json_encode([
                    'ok' => true,
                    'latest_id' => $latestId,
                    'unread_count' => countUnreadLeadNotificationsByLandlord($landlordId),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                @flush();
                $lastPingAt = time();
            }

            usleep(1000000);
        }
        exit;

    case 'push-subscribe':
        $user = ensureLoggedIn();
        header('Content-Type: application/json; charset=utf-8');
        if (($user['role'] ?? '') !== 'landlord') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw ?: '{}', true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid_payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $saved = savePushSubscription((int)$user['id'], $payload, (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (!$saved) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'invalid_subscription'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;

    case 'push-unsubscribe':
        $user = ensureLoggedIn();
        header('Content-Type: application/json; charset=utf-8');
        if (($user['role'] ?? '') !== 'landlord') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw ?: '{}', true);
        if (is_array($payload)) {
            deletePushSubscription((int)$user['id'], $payload);
        }
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;

    case 'open-lead':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('dashboard');
        }
        $landlordId = ensureLandlord();
        if (!staffHasPermission('lead_manage')) {
            flash('error', 'Staff chưa được cấp quyền mở lead.');
            redirect('dashboard', ['tab' => 'lead']);
        }
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $pdo = getPDO();
        ensureRoomSoftDeleteSchema($pdo);
        $stmt = $pdo->prepare('SELECT l.status, l.tenant_phone, r.lead_price_final, r.lead_price_admin, r.lead_price_suggest, r.lead_price_expect, lp.status AS purchase_status FROM leads l JOIN rooms r ON r.id = l.room_id LEFT JOIN lead_purchases lp ON lp.lead_id = l.id AND lp.landlord_id = r.landlord_id AND lp.status = "paid" WHERE l.id = :id AND r.landlord_id = :l AND r.deleted_at IS NULL');
        $stmt->execute([':id' => $leadId, ':l' => $landlordId]);
        $row = $stmt->fetch();
        if (!$row) {
            auditLog('lead.open_denied', [
                'entity_type' => 'lead',
                'entity_id' => (string)$leadId,
                'reason' => 'not_found_or_forbidden',
            ]);
            flash('error', 'Không mở được lead.');
            redirect('dashboard');
        }
        $status = (string)($row['status'] ?? 'new');
        if (leadHasUnlockedContact($row)) {
            ensureChatForLead($leadId);
            auditLog('lead.full_contact_view', [
                'entity_type' => 'lead',
                'entity_id' => (string)$leadId,
                'source' => 'already_unlocked',
            ]);
            flash('success', 'Lead này đã mua rồi, SĐT đang ở trong lịch sử lead đã mua.');
            redirect('dashboard', ['tab' => 'lead']);
        }
        if ($status === 'invalid') {
            flash('error', 'Lead này đã bị đánh dấu lỗi nên không thể mua.');
            redirect('dashboard', ['tab' => 'lead']);
        }
        if ($status !== 'new') {
            flash('error', 'Lead này không còn ở trạng thái có thể mua.');
            redirect('dashboard', ['tab' => 'lead']);
        }
        $amount = effectiveLeadPriceFromRow($row);
        $paymentId = createPendingPaymentForLead($leadId, $landlordId, (int)$amount, (string)($row['tenant_phone'] ?? ''));
        transactionLog('lead_payment_intent_created', [
            'status' => 'pending',
            'amount' => (int)$amount,
            'entity_type' => 'lead',
            'entity_id' => (string)$leadId,
            'reference_code' => (string)$paymentId,
        ]);
        auditLog('lead.open_requested', [
            'entity_type' => 'lead',
            'entity_id' => (string)$leadId,
            'payment_id' => $paymentId,
            'amount' => (int)$amount,
        ]);
        flash('success', 'Tạo yêu cầu thanh toán thành công. QR có hiệu lực 15 phút. Sau khi SePay báo tiền vào, SĐT sẽ mở tự động.');
        redirect('payment-history', ['focus_payment' => $paymentId]);
        break;

    case 'open-marketplace-lead':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('dashboard', ['tab' => 'lead']);
        }
        $landlordId = ensureLandlord();
        if (!staffHasPermission('lead_manage')) {
            flash('error', 'Staff chưa được cấp quyền mở lead marketplace.');
            redirect('dashboard', ['tab' => 'lead']);
        }
        $tenantPostId = (int)($_POST['tenant_post_id'] ?? 0);
        $draft = ensureMarketplaceLeadDraft($tenantPostId, $landlordId);
        $lead = $draft['lead'] ?? null;
        if (empty($draft['ok']) || !$lead) {
            auditLog('lead.marketplace_open_denied', [
                'entity_type' => 'tenant_post',
                'entity_id' => (string)$tenantPostId,
                'reason' => (string)($draft['error'] ?? 'draft_failed'),
            ]);
            flash('error', (string)($draft['error'] ?? 'Không tạo được lead marketplace.'));
            redirect('dashboard', ['tab' => 'lead']);
        }
        $leadId = (int)($lead['id'] ?? 0);
        if ($leadId <= 0) {
            flash('error', 'Không xác định được lead marketplace cần mở.');
            redirect('dashboard', ['tab' => 'lead']);
        }
        $status = (string)($lead['status'] ?? 'new');
        if (leadHasUnlockedContact($lead)) {
            ensureChatForLead($leadId);
            auditLog('lead.full_contact_view', [
                'entity_type' => 'lead',
                'entity_id' => (string)$leadId,
                'source' => 'marketplace_already_unlocked',
            ]);
            flash('success', 'Lead marketplace này đã mua rồi, full contact đang ở lịch sử lead đã mua.');
            redirect('dashboard', ['tab' => 'lead']);
        }
        if ($status === 'invalid') {
            flash('error', 'Lead marketplace này đã được đánh dấu thất bại.');
            redirect('dashboard', ['tab' => 'lead']);
        }
        $amount = effectiveLeadPriceFromRow($lead);
        $paymentId = createPendingPaymentForLead($leadId, $landlordId, (int)$amount, (string)($lead['tenant_phone'] ?? ''));
        transactionLog('lead_marketplace_payment_intent_created', [
            'status' => 'pending',
            'amount' => (int)$amount,
            'entity_type' => 'lead',
            'entity_id' => (string)$leadId,
            'reference_code' => (string)$paymentId,
            'tenant_post_id' => $tenantPostId,
        ]);
        auditLog('lead.marketplace_open_requested', [
            'entity_type' => 'lead',
            'entity_id' => (string)$leadId,
            'tenant_post_id' => $tenantPostId,
            'payment_id' => $paymentId,
            'amount' => (int)$amount,
        ]);
        flash('success', 'Đã tạo thanh toán cho lead marketplace. Thanh toán xong sẽ tự mở full contact.');
        redirect('payment-history', ['focus_payment' => $paymentId]);
        break;

    case 'update-lead-stage':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('dashboard', ['tab' => 'lead']);
        }
        $landlordId = ensureLandlord();
        if (!staffHasPermission('lead_manage')) {
            flash('error', 'Staff chưa được cấp quyền xử lý lead.');
            redirect('dashboard', ['tab' => 'lead']);
        }
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $stage = trim((string)($_POST['stage'] ?? ''));
        $stageNote = trim((string)($_POST['stage_note'] ?? ''));
        if ($leadId > 0 && updateLeadStage($leadId, $landlordId, $stage, $stageNote)) {
            auditLog('lead.stage_updated', [
                'entity_type' => 'lead',
                'entity_id' => (string)$leadId,
                'stage' => $stage,
            ]);
            flash('success', 'Đã cập nhật trạng thái lead.');
        } else {
            auditLog('lead.stage_update_denied', [
                'entity_type' => 'lead',
                'entity_id' => (string)$leadId,
                'stage' => $stage,
            ]);
            flash('error', 'Không cập nhật được trạng thái lead.');
        }
        redirect('dashboard', ['tab' => 'lead']);
        break;

    case 'verify-lead-phone':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('dashboard');
        }
        $landlordId = ensureLandlord();
        if (!staffHasPermission('lead_manage')) {
            flash('error', 'Staff chưa được cấp quyền xác minh lead.');
            redirect('dashboard', ['tab' => 'lead']);
        }
        $leadId = (int)($_POST['lead_id'] ?? 0);
        if ($leadId && verifyLeadPhone($leadId, $landlordId)) {
            auditLog('lead.phone_verified', [
                'entity_type' => 'lead',
                'entity_id' => (string)$leadId,
            ]);
            flash('success', 'Đã xác nhận lead và đánh dấu SĐT đã được xác minh.');
        } else {
            auditLog('lead.phone_verify_denied', [
                'entity_type' => 'lead',
                'entity_id' => (string)$leadId,
            ]);
            flash('error', 'Không xác minh được lead này.');
        }
        redirect('dashboard', ['tab' => 'lead']);
        break;

    case 'mark-lead-invalid':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('dashboard');
        }
        $landlordId = ensureLandlord();
        if (!staffHasPermission('lead_manage')) {
            flash('error', 'Staff chưa được cấp quyền đánh dấu lead.');
            redirect('dashboard', ['tab' => 'lead']);
        }
        $leadId = (int)($_POST['lead_id'] ?? 0);
        if ($leadId && markLeadInvalid($leadId, $landlordId)) {
            auditLog('lead.mark_invalid', [
                'entity_type' => 'lead',
                'entity_id' => (string)$leadId,
            ]);
            flash('success', 'Đã đánh dấu lead không liên hệ được.');
        } else {
            auditLog('lead.mark_invalid_denied', [
                'entity_type' => 'lead',
                'entity_id' => (string)$leadId,
            ]);
            flash('error', 'Không cập nhật được lead này.');
        }
        redirect('dashboard', ['tab' => 'lead']);
        break;

    case 'payment-webhook':
        require_once __DIR__ . '/app/payment_webhook.php';
        handleSepayPaymentWebhook();
        break;

    case 'my-rooms':
        $landlordId = ensureLandlord();
        if (!staffHasPermission('room_manage')) {
            flash('error', 'Staff chưa được cấp quyền truy cập vận hành phòng.');
            redirect('dashboard');
        }
        $keyword = trim($_GET['q'] ?? '');
        $rooms = roomsOperationalOverviewByLandlord($landlordId, $keyword);
        render('my_rooms', [
            'rooms' => $rooms,
            'keyword' => $keyword,
            'activeMenu' => 'rooms',
            'boostQuota' => boostQuotaForLandlord($landlordId),
            'boostUsed' => boostUsedToday($landlordId),
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'room-create':
        $landlordId = ensureLandlord();
        if (!staffHasPermission('room_manage')) {
            flash('error', 'Staff chưa được cấp quyền tạo phòng.');
            redirect('my-rooms');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $price = (int)($_POST['price'] ?? 0);
            $area = trim($_POST['area'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $thumb = trim($_POST['thumbnail'] ?? '');
            $leadPriceExpect = ($_POST['lead_price_expect'] ?? '') !== '' ? (int)$_POST['lead_price_expect'] : null;
            $electric = ($_POST['electric_price'] ?? '') !== '' ? (int)$_POST['electric_price'] : null;
            $water = ($_POST['water_price'] ?? '') !== '' ? (int)$_POST['water_price'] : null;
            if ($title === '' || $price <= 0 || $area === '' || $address === '') {
                flash('error', 'Vui lòng nhập đầy đủ và đúng giá.');
                redirect('room-create');
            }
            if (!isPositiveThousandAmount($price)) {
                flash('error', 'Giá phòng phải lớn hơn 0 và là bội của 1.000đ.');
                redirect('room-create');
            }
            if (!isValidLeadPrice($leadPriceExpect)) {
                flash('error', 'Giá lead tối thiểu 3.000đ và phải là bội của 1.000đ.');
                redirect('room-create');
            }
            if (!isThousandAmount($electric) || !isThousandAmount($water)) {
                flash('error', 'Giá điện và giá nước phải là bội của 1.000đ.');
                redirect('room-create');
            }
            if (containsContactInfo($title) || containsContactInfo($description)) {
                flash('error', 'Không được chèn số điện thoại / Zalo / liên hệ trong tiêu đề hoặc mô tả.');
                redirect('room-create');
            }
            $shared = isset($_POST['shared_owner']) ? 1 : 0;
            $closed = isset($_POST['closed_room']) ? 1 : 0;
            $video = trim($_POST['video_url'] ?? '');
            // ưu tiên file upload nếu có
            $thumb = handleUpload('thumbnail_file') ?? $thumb;
            $video = handleUpload('video_file', ['mp4','mov','mkv','webm']) ?? $video;
            // gallery images (4-8)
            $gallery = [];
            $urls = array_filter(array_map('trim', preg_split('/\\r?\\n/', $_POST['gallery_urls'] ?? '')));
            foreach ($urls as $u) {
                if ($u !== '') $gallery[] = $u;
            }
            if (!empty($_FILES['gallery_files']['name']) && is_array($_FILES['gallery_files']['name'])) {
                foreach ($_FILES['gallery_files']['name'] as $idx => $name) {
                    if (empty($name)) continue;
                    $tmp = $_FILES['gallery_files']['tmp_name'][$idx] ?? '';
                    if (!is_uploaded_file($tmp)) continue;
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) continue;
                    $destDir = uploadsPath();
                    $newName = uniqid('gallery_') . '.' . $ext;
                    if (move_uploaded_file($tmp, $destDir . '/' . $newName)) {
                        $gallery[] = 'storage/uploads/' . $newName;
                    }
                }
            }
            $gallery = array_values(array_unique(array_filter($gallery)));
            if (count($gallery) < 4 || count($gallery) > 8) {
                flash('error', 'Vui lòng chọn từ 4 đến 8 ảnh phụ (URL hoặc upload).');
                redirect('room-create');
            }
            $imgs = array_pad(array_slice($gallery, 0, 8), 8, null);
            if ($video === '') {
                flash('error', 'Vui lòng thêm ít nhất 1 video (URL hoặc upload).');
                redirect('room-create');
            }
            $createdRoomId = createRoom($landlordId, $title, $price, $leadPriceExpect, $area, $address, $description, $thumb, $electric, $water, $shared, $closed, $imgs[0], $imgs[1], $imgs[2], $imgs[3], $imgs[4], $imgs[5], $imgs[6], $imgs[7], $video);
            auditLog('room.created', [
                'entity_type' => 'room',
                'entity_id' => (string)$createdRoomId,
                'status' => 'pending',
            ]);
            flash('success', 'Đã gửi phòng, chờ admin duyệt.');
            redirect('my-rooms');
        }
        render('room_create', [
            'activeMenu' => 'rooms',
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'room-delete':
        $landlordId = ensureLandlord();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!staffHasPermission('room_manage')) {
                flash('error', 'Staff chưa được cấp quyền xóa dữ liệu phòng.');
                redirect('my-rooms');
            }
            $roomId = (int)($_POST['room_id'] ?? 0);
            if (deleteRoom($roomId, $landlordId)) {
                auditLog('room.soft_deleted', [
                    'entity_type' => 'room',
                    'entity_id' => (string)$roomId,
                ]);
            }
        }
        flash('success', 'Đã xóa phòng (nếu tồn tại).');
        redirect('my-rooms');
        break;

    case 'room-boost':
        $landlordId = ensureLandlord();
        if (!staffHasPermission('room_manage')) {
            flash('error', 'Staff chưa được cấp quyền boost phòng.');
            redirect('my-rooms');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roomId = (int)($_POST['room_id'] ?? 0);
            if (boostRoom($roomId, $landlordId)) {
                auditLog('room.boosted', [
                    'entity_type' => 'room',
                    'entity_id' => (string)$roomId,
                ]);
                flash('success', 'Đã boost phòng trong 12 giờ.');
            } else {
                flash('error', 'Không boost được (hết lượt hoặc không phải phòng của bạn).');
            }
        }
        redirect('my-rooms');
        break;

    case 'room-edit':
        $landlordId = ensureLandlord();
        if (!staffHasPermission('room_manage')) {
            flash('error', 'Staff chưa được cấp quyền sửa phòng.');
            redirect('my-rooms');
        }
        $roomId = (int)($_GET['id'] ?? ($_POST['room_id'] ?? 0));
        $room = $roomId ? findRoomOwned($roomId, $landlordId) : null;
        if (!$room) {
            flash('error', 'Không tìm thấy phòng.');
            redirect('my-rooms');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $price = (int)($_POST['price'] ?? 0);
            $area = trim($_POST['area'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $thumb = trim($_POST['thumbnail'] ?? '');
            $electric = ($_POST['electric_price'] ?? '') !== '' ? (int)$_POST['electric_price'] : null;
            $water = ($_POST['water_price'] ?? '') !== '' ? (int)$_POST['water_price'] : null;
            if ($title === '' || $price <= 0 || $area === '' || $address === '') {
                flash('error', 'Vui lòng nhập đầy đủ và đúng giá.');
                redirect('room-edit', ['id' => $roomId]);
            }
            if (!isPositiveThousandAmount($price)) {
                flash('error', 'Giá phòng phải lớn hơn 0 và là bội của 1.000đ.');
                redirect('room-edit', ['id' => $roomId]);
            }
            if (!isThousandAmount($electric) || !isThousandAmount($water)) {
                flash('error', 'Giá điện và giá nước phải là bội của 1.000đ.');
                redirect('room-edit', ['id' => $roomId]);
            }
            if (containsContactInfo($title) || containsContactInfo($description)) {
                flash('error', 'Không được chèn số điện thoại / Zalo / liên hệ trong tiêu đề hoặc mô tả.');
                redirect('room-edit', ['id' => $roomId]);
            }
            $shared = isset($_POST['shared_owner']) ? 1 : 0;
            $closed = isset($_POST['closed_room']) ? 1 : 0;
            $video = trim($_POST['video_url'] ?? ($room['video_url'] ?? ''));

            $thumbUpload = handleUpload('thumbnail_file');
            $videoUpload = handleUpload('video_file', ['mp4','mov','mkv','webm']);

            // gallery combine existing + urls + uploads
            $gallery = [];
            $urlLines = array_filter(array_map('trim', preg_split('/\\r?\\n/', $_POST['gallery_urls'] ?? '')));
            foreach ($urlLines as $u) if ($u !== '') $gallery[] = $u;
            foreach (['image1','image2','image3','image4','image5','image6','image7','image8'] as $k) {
                if (!empty($room[$k])) $gallery[] = $room[$k];
            }
            if (!empty($_FILES['gallery_files']['name']) && is_array($_FILES['gallery_files']['name'])) {
                foreach ($_FILES['gallery_files']['name'] as $idx => $name) {
                    if (empty($name)) continue;
                    $tmp = $_FILES['gallery_files']['tmp_name'][$idx] ?? '';
                    if (!is_uploaded_file($tmp)) continue;
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) continue;
                    $dest = uploadsPath() . '/' . uniqid('gallery_') . '.' . $ext;
                    if (move_uploaded_file($tmp, $dest)) {
                        $gallery[] = 'storage/uploads/' . basename($dest);
                    }
                }
            }
            $gallery = array_values(array_unique(array_filter($gallery)));
            if (count($gallery) < 4 || count($gallery) > 8) {
                flash('error', 'Vui lòng giữ 4-8 ảnh phụ sau khi chỉnh sửa.');
                redirect('room-edit', ['id' => $roomId]);
            }
            $imgs = array_pad(array_slice($gallery, 0, 8), 8, null);

            $thumb = $thumbUpload ?? $thumb ?? ($room['thumbnail'] ?? '');
            $video = $videoUpload ?? $video;

            updateRoom($roomId, $landlordId, $title, $price, $area, $address, $description, $thumb, $electric, $water, $shared, $closed, $imgs[0], $imgs[1], $imgs[2], $imgs[3], $imgs[4], $imgs[5], $imgs[6], $imgs[7], $video);
            auditLog('room.updated', [
                'entity_type' => 'room',
                'entity_id' => (string)$roomId,
            ]);
            flash('success', 'Đã cập nhật phòng.');
            redirect('my-rooms');
        }
        render('room_edit', [
            'room' => $room,
            'activeMenu' => 'rooms',
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'room-ops':
        $landlordId = ensureLandlord();
        if (!staffHasPermission('room_manage') && !staffHasPermission('invoice_manage') && !staffHasPermission('deposit_manage') && !staffHasPermission('lead_view')) {
            flash('error', 'Staff chưa được cấp quyền truy cập vận hành phòng.');
            redirect('my-rooms');
        }
        $roomId = (int)($_GET['id'] ?? ($_POST['room_id'] ?? 0));
        $room = $roomId > 0 ? findRoomOwned($roomId, $landlordId) : null;
        if (!$room) {
            flash('error', 'Không tìm thấy phòng để vận hành.');
            redirect('my-rooms');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = trim((string)($_POST['action'] ?? ''));

            if ($action === 'convert-room-lead') {
                if (!staffHasPermission('lead_manage')) {
                    flash('error', 'Staff chưa được cấp quyền chốt lead.');
                    redirect('room-ops', ['id' => $roomId]);
                }
                $leadId = (int)($_POST['lead_id'] ?? 0);
                $convertResult = convertLeadToRoomOccupancy($leadId, $roomId, $landlordId);
                if (!empty($convertResult['ok'])) {
                    auditLog('lead.convert_to_tenant', [
                        'entity_type' => 'lead',
                        'entity_id' => (string)$leadId,
                        'room_id' => $roomId,
                    ]);
                    flash('success', 'Đã chốt lead thành kỳ thuê và gắn người thuê vào hồ sơ phòng.');
                } else {
                    auditLog('lead.convert_denied', [
                        'entity_type' => 'lead',
                        'entity_id' => (string)$leadId,
                        'room_id' => $roomId,
                        'reason' => (string)($convertResult['error'] ?? 'unknown'),
                    ]);
                    flash('error', (string)($convertResult['error'] ?? 'Không chốt được lead thành kỳ thuê.'));
                }
                redirect('room-ops', ['id' => $roomId]);
            }

            if ($action === 'save-room-ops') {
                if (!staffHasPermission('room_manage')) {
                    flash('error', 'Staff chưa được cấp quyền cập nhật hồ sơ phòng.');
                    redirect('room-ops', ['id' => $roomId]);
                }
                $tenantPhone = trim((string)($_POST['tenant_phone'] ?? ''));
                if ($tenantPhone !== '' && !preg_match('/^0\d{9}$/', $tenantPhone)) {
                    flash('error', 'Số điện thoại người thuê phải đủ 10 số và bắt đầu bằng 0.');
                    redirect('room-ops', ['id' => $roomId]);
                }

                saveRoomOperationProfile($roomId, $landlordId, [
                    'occupancy_status' => $_POST['occupancy_status'] ?? 'vacant',
                    'tenant_name' => $_POST['tenant_name'] ?? '',
                    'tenant_phone' => $tenantPhone,
                    'monthly_rent' => $_POST['monthly_rent'] ?? '',
                    'deposit_amount' => $_POST['deposit_amount'] ?? '',
                    'service_fee' => $_POST['service_fee'] ?? '',
                    'contract_start' => $_POST['contract_start'] ?? '',
                    'contract_end' => $_POST['contract_end'] ?? '',
                    'electric_meter_reading' => $_POST['electric_meter_reading'] ?? '',
                    'water_meter_reading' => $_POST['water_meter_reading'] ?? '',
                    'room_condition' => $_POST['room_condition'] ?? 'ready',
                    'issue_note' => $_POST['issue_note'] ?? '',
                    'operation_note' => $_POST['operation_note'] ?? '',
                ]);
                auditLog('room.operation_profile_updated', [
                    'entity_type' => 'room',
                    'entity_id' => (string)$roomId,
                ]);
                flash('success', 'Đã cập nhật hồ sơ vận hành phòng.');
                redirect('room-ops', ['id' => $roomId]);
            }

            if ($action === 'create-room-invoice') {
                if (!staffHasPermission('invoice_manage')) {
                    flash('error', 'Staff chưa được cấp quyền tạo hóa đơn.');
                    redirect('room-ops', ['id' => $roomId]);
                }
                $invoiceResult = createRoomInvoice($roomId, $landlordId, [
                    'billing_month' => $_POST['billing_month'] ?? '',
                    'rent_amount' => $_POST['rent_amount'] ?? '',
                    'service_amount' => $_POST['service_amount'] ?? '',
                    'electric_reading_new' => $_POST['electric_reading_new'] ?? '',
                    'water_reading_new' => $_POST['water_reading_new'] ?? '',
                    'other_amount' => $_POST['other_amount'] ?? '',
                    'due_date' => $_POST['due_date'] ?? '',
                    'note' => $_POST['invoice_note'] ?? '',
                ]);
                if (!empty($invoiceResult['ok'])) {
                    transactionLog('room_invoice_created', [
                        'status' => 'issued',
                        'entity_type' => 'room_invoice',
                        'entity_id' => (string)((int)($invoiceResult['invoice_id'] ?? 0)),
                        'room_id' => $roomId,
                    ]);
                    auditLog('room.invoice_created', [
                        'entity_type' => 'room',
                        'entity_id' => (string)$roomId,
                        'invoice_id' => (int)($invoiceResult['invoice_id'] ?? 0),
                    ]);
                    flash('success', 'Đã tạo hóa đơn vận hành cho phòng.');
                } else {
                    flash('error', (string)($invoiceResult['error'] ?? 'Không tạo được hóa đơn.'));
                }
                redirect('room-ops', ['id' => $roomId]);
            }

            if ($action === 'mark-room-invoice') {
                if (!staffHasPermission('invoice_manage')) {
                    flash('error', 'Staff chưa được cấp quyền sửa trạng thái hóa đơn.');
                    redirect('room-ops', ['id' => $roomId]);
                }
                $invoiceId = (int)($_POST['invoice_id'] ?? 0);
                $invoiceStatus = trim((string)($_POST['invoice_status'] ?? 'unpaid'));
                $invoicePayload = [
                    'status' => $invoiceStatus,
                    'amount_paid' => $_POST['amount_paid'] ?? '',
                    'payment_method' => $_POST['payment_method'] ?? '',
                ];
                if ($invoiceId > 0 && updateRoomInvoicePaymentStatus($invoiceId, $landlordId, $invoicePayload)) {
                    transactionLog('room_invoice_status_changed', [
                        'status' => $invoiceStatus,
                        'amount' => isset($invoicePayload['amount_paid']) ? (int)$invoicePayload['amount_paid'] : null,
                        'entity_type' => 'room_invoice',
                        'entity_id' => (string)$invoiceId,
                        'room_id' => $roomId,
                        'note' => (string)($invoicePayload['payment_method'] ?? ''),
                    ]);
                    auditLog('room.invoice_updated', [
                        'entity_type' => 'room_invoice',
                        'entity_id' => (string)$invoiceId,
                        'status' => $invoiceStatus,
                    ]);
                    flash('success', 'Đã cập nhật trạng thái hóa đơn.');
                } else {
                    auditLog('room.invoice_update_denied', [
                        'entity_type' => 'room_invoice',
                        'entity_id' => (string)$invoiceId,
                        'status' => $invoiceStatus,
                    ]);
                    flash('error', 'Không cập nhật được trạng thái hóa đơn.');
                }
                redirect('room-ops', ['id' => $roomId]);
            }

            if ($action === 'create-room-notice') {
                $noticeResult = createRoomNotice($roomId, $landlordId, [
                    'notice_type' => $_POST['notice_type'] ?? 'general',
                    'title' => $_POST['notice_title'] ?? '',
                    'content' => $_POST['notice_content'] ?? '',
                    'effective_date' => $_POST['notice_effective_date'] ?? '',
                ]);
                if (!empty($noticeResult['ok'])) {
                    flash('success', 'Đã gửi thông báo xuống không gian thuê trọ của người thuê.');
                } else {
                    flash('error', (string)($noticeResult['error'] ?? 'Không tạo được thông báo.'));
                }
                redirect('room-ops', ['id' => $roomId]);
            }

            if ($action === 'update-tenant-issue') {
                if (!staffHasPermission('room_manage')) {
                    flash('error', 'Staff chưa được cấp quyền xử lý sự cố.');
                    redirect('room-ops', ['id' => $roomId]);
                }
                $issueId = (int)($_POST['issue_id'] ?? 0);
                $issueStatus = trim((string)($_POST['issue_status'] ?? 'open'));
                $landlordNote = trim((string)($_POST['landlord_note'] ?? ''));
                $repairCost = ($_POST['repair_cost'] ?? '') !== '' ? max(0, (int)$_POST['repair_cost']) : 0;
                if ($issueId > 0 && updateTenantIssueStatus($issueId, $landlordId, $issueStatus, $landlordNote, $repairCost)) {
                    flash('success', 'Đã cập nhật trạng thái sự cố của người thuê.');
                } else {
                    flash('error', 'Không cập nhật được trạng thái sự cố.');
                }
                redirect('room-ops', ['id' => $roomId]);
            }

            if ($action === 'settle-room-deposit') {
                if (!staffHasPermission('deposit_manage')) {
                    flash('error', 'Staff chưa được cấp quyền chốt cọc.');
                    redirect('room-ops', ['id' => $roomId]);
                }
                $stayId = (int)($_POST['stay_id'] ?? 0);
                $settlementResult = settleTenantDeposit($stayId, $landlordId, [
                    'deposit_deduction_amount' => $_POST['deposit_deduction_amount'] ?? '',
                    'deposit_refund_amount' => $_POST['deposit_refund_amount'] ?? '',
                    'settlement_note' => $_POST['settlement_note'] ?? '',
                    'settled_at' => $_POST['settled_at'] ?? '',
                    'ended_at' => $_POST['ended_at'] ?? '',
                ]);
                if (!empty($settlementResult['ok'])) {
                    transactionLog('tenant_deposit_settled', [
                        'status' => 'closed',
                        'entity_type' => 'tenant_stay',
                        'entity_id' => (string)$stayId,
                        'room_id' => $roomId,
                        'amount' => isset($_POST['deposit_refund_amount']) && $_POST['deposit_refund_amount'] !== '' ? (int)$_POST['deposit_refund_amount'] : null,
                        'deduction_amount' => isset($_POST['deposit_deduction_amount']) && $_POST['deposit_deduction_amount'] !== '' ? (int)$_POST['deposit_deduction_amount'] : null,
                    ]);
                    auditLog('tenant.deposit_settled', [
                        'entity_type' => 'tenant_stay',
                        'entity_id' => (string)$stayId,
                        'room_id' => $roomId,
                    ]);
                    flash('success', 'Đã chốt hoàn cọc cho kỳ thuê này.');
                } else {
                    auditLog('tenant.deposit_settlement_denied', [
                        'entity_type' => 'tenant_stay',
                        'entity_id' => (string)$stayId,
                        'room_id' => $roomId,
                        'reason' => (string)($settlementResult['error'] ?? 'unknown'),
                    ]);
                    flash('error', (string)($settlementResult['error'] ?? 'Không chốt được hoàn cọc.'));
                }
                redirect('room-ops', ['id' => $roomId]);
            }

            if ($action === 'create-room-handover') {
                if (!staffHasPermission('room_manage')) {
                    flash('error', 'Staff chưa được cấp quyền lưu biên bản bàn giao.');
                    redirect('room-ops', ['id' => $roomId]);
                }
                $wallImage = handleUpload('handover_wall_image', ['jpg','jpeg','png','gif','webp']);
                $bedImage = handleUpload('handover_bed_image', ['jpg','jpeg','png','gif','webp']);
                $equipmentImage = handleUpload('handover_equipment_image', ['jpg','jpeg','png','gif','webp']);
                foreach ([
                    'handover_wall_image' => $wallImage,
                    'handover_bed_image' => $bedImage,
                    'handover_equipment_image' => $equipmentImage,
                ] as $field => $uploadPath) {
                    if (isset($_FILES[$field]) && !empty($_FILES[$field]['name']) && !$uploadPath) {
                        flash('error', 'Ảnh bàn giao không hợp lệ. Chỉ nhận jpg, jpeg, png, gif, webp.');
                        redirect('room-ops', ['id' => $roomId]);
                    }
                }

                $handoverResult = createRoomHandoverRecord($roomId, $landlordId, [
                    'handover_type' => $_POST['handover_type'] ?? 'move_in',
                    'wall_image' => $wallImage,
                    'bed_image' => $bedImage,
                    'equipment_image' => $equipmentImage,
                    'note' => $_POST['handover_note'] ?? '',
                ]);
                if (!empty($handoverResult['ok'])) {
                    flash('success', 'Đã lưu ảnh và biên bản bàn giao phòng.');
                } else {
                    flash('error', (string)($handoverResult['error'] ?? 'Không lưu được bàn giao phòng.'));
                }
                redirect('room-ops', ['id' => $roomId]);
            }
        }

        $operationProfile = roomOperationProfile($roomId, $landlordId);
        $roomInvoices = roomInvoicesByRoom($roomId, $landlordId);
        render('room_operations', [
            'room' => $room,
            'operationProfile' => $operationProfile,
            'roomInvoices' => $roomInvoices,
            'roomNotices' => roomNoticesByRoom($roomId, $landlordId),
            'roomIssues' => tenantIssueReportsByRoom($roomId, $landlordId),
            'stayHistory' => tenantStayHistoryByRoom($roomId, $landlordId),
            'meterLogs' => roomMeterLogsByRoom($roomId, $landlordId),
            'handoverRecords' => roomHandoverRecordsByRoom($roomId, $landlordId),
            'roomLeads' => roomLeadsByRoom($roomId, $landlordId),
            'occupancyOptions' => roomOperationStatusOptions(),
            'conditionOptions' => roomConditionOptions(),
            'noticeTypeOptions' => roomNoticeTypeOptions(),
            'issuePriorityOptions' => tenantIssuePriorityOptions(),
            'issueStatusOptions' => tenantIssueStatusOptions(),
            'handoverTypeOptions' => roomHandoverTypeOptions(),
            'activeMenu' => 'rooms',
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'profile':
        $user = ensureLoggedIn();
        $userDb = findUserByPhone($user['phone']) ?? [];
        $user['phone_verified'] = (int)($userDb['phone_verified'] ?? 0);
        $user['birthdate'] = $userDb['birthdate'] ?? null;
        $user['hometown'] = $userDb['hometown'] ?? '';
        $user['avatar'] = $userDb['avatar'] ?? ($user['avatar'] ?? null);
        $isEdit = ($_GET['edit'] ?? '') === '1';
        $isAjax = ($_POST['ajax'] ?? '') === '1';
        $jsonResponse = function($ok, $data = []) {
            header('Content-Type: application/json');
            echo json_encode(array_merge(['ok' => $ok], $data));
            exit;
        };
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['update_avatar'])) {
                $avatar = handleUpload('avatar_file', ['jpg','jpeg','png','gif','webp']);
                if (!$avatar) {
                    if ($isAjax) $jsonResponse(false, ['error' => 'Ảnh không hợp lệ.']);
                    flash('error', 'Ảnh không hợp lệ. Chỉ nhận jpg, jpeg, png, gif, webp.');
                    redirect('profile');
                }
                updateUserAvatar((int)$user['id'], $avatar);
                $user['avatar'] = $avatar;
                if ($isAjax) $jsonResponse(true, ['avatar' => $avatar]);
                flash('success', 'Đã cập nhật ảnh đại diện.');
                redirect('profile');
            }
            if (isset($_POST['update_profile'])) {
                $name = trim($_POST['name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $birthdate = $_POST['birthdate'] ?? null;
                $hometown = trim($_POST['hometown'] ?? '');
                $avatar = null;
                if ($name === '' || mb_strlen($name) < 5 || !preg_match('/\s+/', $name)) {
                    $msg = 'Nhập họ tên đầy đủ (ít nhất 2 từ).';
                    if ($isAjax) $jsonResponse(false, ['error' => $msg]);
                    flash('error', $msg);
                    redirect('profile');
                }
                if ($user['phone_verified'] == 0) {
                    if (!preg_match('/^0\\d{9}$/', $phone)) {
                        $msg = 'Số điện thoại phải đủ 10 số và bắt đầu bằng 0.';
                        if ($isAjax) $jsonResponse(false, ['error' => $msg]);
                        flash('error', $msg);
                        redirect('profile');
                    }
                } else {
                    $phone = ''; // không đổi nếu đã xác minh
                }
                if (isset($_FILES['avatar_file']) && !empty($_FILES['avatar_file']['name'])) {
                    $avatar = handleUpload('avatar_file', ['jpg','jpeg','png','gif','webp']);
                    if (!$avatar) {
                        $msg = 'Ảnh đại diện không hợp lệ. Chỉ nhận jpg, jpeg, png, gif, webp.';
                        if ($isAjax) $jsonResponse(false, ['error' => $msg]);
                        flash('error', $msg);
                        redirect('profile');
                    }
                }
                updateUserProfile((int)$user['id'], [
                    'name' => $name,
                    'phone' => $phone,
                    'birthdate' => $birthdate ?: null,
                    'hometown' => $hometown,
                ]);
                if ($avatar) {
                    updateUserAvatar((int)$user['id'], $avatar);
                    $user['avatar'] = $avatar;
                }
                $user['name'] = $name;
                if ($phone) $user['phone'] = $phone;
                $user['birthdate'] = $birthdate;
                $user['hometown'] = $hometown;
                // cập nhật session
                loginUser($user);
                if ($isAjax) $jsonResponse(true, [
                    'name' => $name,
                    'phone' => $user['phone'],
                    'birthdate' => $birthdate,
                    'hometown' => $hometown,
                    'avatar' => $user['avatar'] ?? null,
                ]);
                flash('success', 'Đã lưu thông tin.');
                redirect('profile');
            }
        }
        $roleMatrix = [
            'tenant' => ['Xem phòng', 'Tạo lead (quan tâm)', 'Đăng ở ghép'],
            'landlord' => ['Đăng phòng', 'Quản lý phòng', 'Xem lead', 'Mua lead'],
            'staff' => ['Vận hành thay chủ trọ trong phạm vi được cấp'],
            'admin' => ['Duyệt phòng', 'Quản lý user', 'Quản lý lead', 'Quản lý payment'],
        ];
        render('profile', [
            'user' => $user,
            'roles' => $roleMatrix,
            'isEdit' => $isEdit,
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'my-stay':
        $user = ensureLoggedIn();
        if (($user['role'] ?? '') !== 'tenant' && ($user['role'] ?? '') !== 'admin') {
            flash('error', 'Chỉ người thuê mới vào được không gian thuê trọ.');
            redirect('profile');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = trim((string)($_POST['action'] ?? ''));
            if ($action === 'create-tenant-issue') {
                $roomId = (int)($_POST['room_id'] ?? 0);
                $issueImage = handleUpload('issue_image', ['jpg','jpeg','png','gif','webp']);
                if (isset($_FILES['issue_image']) && !empty($_FILES['issue_image']['name']) && !$issueImage) {
                    flash('error', 'Ảnh minh chứng không hợp lệ. Chỉ nhận jpg, jpeg, png, gif, webp.');
                    redirect('my-stay');
                }
                $issueResult = createTenantIssueReport($roomId, $user, [
                    'priority' => $_POST['priority'] ?? 'normal',
                    'content' => $_POST['content'] ?? '',
                    'image_path' => $issueImage,
                ]);
                if (!empty($issueResult['ok'])) {
                    flash('success', 'Đã gửi báo sự cố cho chủ trọ.');
                } else {
                    flash('error', (string)($issueResult['error'] ?? 'Không gửi được báo sự cố.'));
                }
                redirect('my-stay');
            }
        }

        render('my_stay', [
            'staySpace' => tenantStaySpaceByUser($user),
            'issuePriorityOptions' => tenantIssuePriorityOptions(),
            'issueStatusOptions' => tenantIssueStatusOptions(),
            'noticeTypeOptions' => roomNoticeTypeOptions(),
            'handoverTypeOptions' => roomHandoverTypeOptions(),
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'admin-reports':
        $user = ensureLoggedIn();
        if (($user['role'] ?? '') !== 'admin') {
            flash('error', 'Bạn không có quyền truy cập.');
            redirect('login');
        }
        redirect('admin', ['panel' => 'reports']);
        break;

    case 'admin-settings':
        $user = ensureLoggedIn();
        if (($user['role'] ?? '') !== 'admin') {
            flash('error', 'Bạn không có quyền truy cập.');
            redirect('login');
        }
        redirect('admin-theme');
        break;

    case 'admin-audit-logs':
        $user = ensureLoggedIn();
        if (($user['role'] ?? '') !== 'admin') {
            flash('error', 'Bạn không có quyền truy cập.');
            redirect('login');
        }
        $limit = max(20, min(500, (int)($_GET['limit'] ?? 200)));
        $logs = adminFetchAuditLogs($limit);
        render('admin_audit_logs', [
            'logs' => $logs,
            'activeMenu' => 'audit',
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'admin':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') {
            flash('error', 'Bạn không có quyền truy cập trang quản trị.');
            redirect('login');
        }
        $stats = [
            'users' => countUsers(),
            'rooms' => countRooms(),
            'leads' => countLeads(),
            'payments' => countPayments(),
            'revenue' => sumPayments(),
        ];
        render('admin_home', [
            'stats' => $stats,
            'roleStats' => countUsersGroupedByRole(),
            'opsStats' => adminOperationalSummary(),
            'activeMenu' => 'home',
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'admin-rooms':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        $status = $_GET['status'] ?? 'pending';
        $rooms = adminFetchRooms($status);
        render('admin_rooms', [
            'rooms' => $rooms,
            'status' => $status,
            'activeMenu' => 'rooms',
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'admin-room-action':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roomId = (int)($_POST['room_id'] ?? 0);
            $rawPriceAdmin = $_POST['lead_price_admin'] ?? '';
            $rawPriceFinal = $_POST['lead_price_final'] ?? '';
            $priceAdmin = $rawPriceAdmin !== '' ? (int)$rawPriceAdmin : null;
            $priceFinal = $rawPriceFinal !== '' ? (int)$rawPriceFinal : $priceAdmin;
            if ($priceAdmin === null && $priceFinal !== null) {
                $priceAdmin = $priceFinal;
            }
            $status = $_POST['action'] === 'approve' ? 'active' : 'rejected';
            if ($status === 'active' && (!isValidLeadPrice($priceAdmin) || !isValidLeadPrice($priceFinal))) {
                flash('error', 'Giá lead tối thiểu 3.000đ và phải là bội của 1.000đ.');
                redirect('admin-rooms');
            }
            if ($roomId) {
                adminUpdateRoomLeadPrice($roomId, $priceAdmin, $priceFinal);
            }
            adminSetRoomStatus($roomId, $status);
            auditLog('admin.room_status_updated', [
                'entity_type' => 'room',
                'entity_id' => (string)$roomId,
                'status' => $status,
                'lead_price_admin' => $priceAdmin,
                'lead_price_final' => $priceFinal,
            ]);
            flash('success', 'Đã cập nhật trạng thái phòng.');
        }
        redirect('admin-rooms');
        break;

    case 'admin-users':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        $users = adminFetchUsers();
        auditLog('admin.user_list_view', [
            'entity_type' => 'user',
            'entity_id' => 'multiple',
            'count' => count($users),
        ]);
        render('admin_users', [
            'users' => $users,
            'activeMenu' => 'users',
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'admin-user-action':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $action = $_POST['action'] ?? '';
            $scopeLandlordId = (int)($_POST['scope_landlord_id'] ?? 0);
            $permissionsPresent = !empty($_POST['permissions_present']);
            $staffPermissions = [];
            foreach (staffPermissionDefaults() as $permissionKey => $defaultValue) {
                $field = 'perm_' . $permissionKey;
                if ($permissionsPresent) {
                    $staffPermissions[$permissionKey] = !empty($_POST[$field]) ? 1 : 0;
                } elseif (array_key_exists($field, $_POST)) {
                    $staffPermissions[$permissionKey] = !empty($_POST[$field]) ? 1 : 0;
                } else {
                    $staffPermissions[$permissionKey] = (int)$defaultValue;
                }
            }
            if (in_array($action, ['lock','unlock'], true)) {
                adminUpdateUserStatus($userId, $action === 'lock' ? 'locked' : 'active');
                auditLog('admin.user_status_updated', [
                    'entity_type' => 'user',
                    'entity_id' => (string)$userId,
                    'status' => $action === 'lock' ? 'locked' : 'active',
                ]);
            } elseif (in_array($action, ['tenant','landlord','staff'], true)) {
                if ($action === 'staff' && $scopeLandlordId <= 0) {
                    flash('error', 'Khi chuyển sang nhân sự, vui lòng nhập ID chủ trọ trong scope.');
                    redirect('admin-users');
                }
                adminUpdateUserRole($userId, $action);
                if ($action === 'staff' && $scopeLandlordId > 0) {
                    adminUpsertStaffScope($userId, $scopeLandlordId, $staffPermissions);
                }
                auditLog('admin.user_role_updated', [
                    'entity_type' => 'user',
                    'entity_id' => (string)$userId,
                    'role' => $action,
                    'scope_landlord_id' => $scopeLandlordId > 0 ? $scopeLandlordId : null,
                ]);
            } elseif (in_array($action, ['verify-phone', 'unverify-phone'], true)) {
                adminUpdateUserPhoneVerification($userId, $action === 'verify-phone' ? 1 : 0);
                auditLog('admin.user_phone_verification_updated', [
                    'entity_type' => 'user',
                    'entity_id' => (string)$userId,
                    'verified' => $action === 'verify-phone' ? 1 : 0,
                ]);
            } elseif ($action === 'update-staff-scope') {
                if ($scopeLandlordId > 0) {
                    adminUpsertStaffScope($userId, $scopeLandlordId, $staffPermissions);
                    auditLog('admin.staff_scope_updated', [
                        'entity_type' => 'user',
                        'entity_id' => (string)$userId,
                        'scope_landlord_id' => $scopeLandlordId,
                        'permissions' => $staffPermissions,
                    ]);
                }
            }
            flash('success', 'Đã cập nhật user.');
        }
        redirect('admin-users');
        break;

    case 'admin-leads':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        $leads = adminFetchLeads();
        auditLog('admin.lead_full_contact_view', [
            'entity_type' => 'lead',
            'entity_id' => 'multiple',
            'count' => count($leads),
        ]);
        render('admin_leads', [
            'leads' => $leads,
            'activeMenu' => 'leads',
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'admin-lead-action':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $leadId = (int)($_POST['lead_id'] ?? 0);
            $action = $_POST['action'] ?? '';
            if ($action === 'invalid') {
                adminUpdateLeadStatus($leadId, 'invalid');
                auditLog('admin.lead_status_updated', [
                    'entity_type' => 'lead',
                    'entity_id' => (string)$leadId,
                    'status' => 'invalid',
                ]);
            } elseif ($action === 'used') {
                adminUpdateLeadStatus($leadId, 'used');
                auditLog('admin.lead_status_updated', [
                    'entity_type' => 'lead',
                    'entity_id' => (string)$leadId,
                    'status' => 'used',
                ]);
            }
            flash('success', 'Đã cập nhật lead.');
        }
        redirect('admin-leads');
        break;

    case 'admin-payments':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        $payments = adminFetchPayments();
        auditLog('admin.payment_list_view', [
            'entity_type' => 'payment',
            'entity_id' => 'multiple',
            'count' => count($payments),
        ]);
        render('admin_payments', [
            'payments' => $payments,
            'activeMenu' => 'payments',
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'admin-theme':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        render('admin_theme', [
            'activeMenu' => 'theme',
            'currentBg' => themeBackgroundValue(),
            'currentOpacity' => themeBackgroundOpacity(),
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'admin-theme-save':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bg = trim($_POST['bg_url'] ?? '');
            $upload = handleUpload('bg_file', ['png','jpg','jpeg','webp','gif']);
            if ($upload) {
                $bg = $upload;
            }
            if ($bg === '') {
                $bg = 'trongdong.png';
            }
            $opacityPercent = isset($_POST['bg_opacity']) ? (float)$_POST['bg_opacity'] : (themeBackgroundOpacity() * 100);
            $opacity = max(0.0, min(25.0, $opacityPercent)) / 100;
            saveThemeBackground($bg, $opacity);
            flash('success', 'Đã cập nhật ảnh nền.');
        }
        redirect('admin-theme');
        break;

    case 'cta-submit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
            exit;
        }
        header('Content-Type: application/json');
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $msg = trim($_POST['message'] ?? '');
        if ($name === '' || $phone === '' || $msg === '') {
            echo json_encode(['ok' => false, 'error' => 'Vui lòng nhập tên, SĐT và tin nhắn.']);
            exit;
        }
        if (!preg_match('/^(0|\\+84)[0-9]{8,10}$/', $phone)) {
            echo json_encode(['ok' => false, 'error' => 'SĐT không hợp lệ.']);
            exit;
        }
        saveCtaMessage($name, $phone, $email, $province, $msg);
        echo json_encode(['ok' => true]);
        exit;

    case 'messages':
        $user = ensureLoggedIn();
        if ($user['role'] === 'admin') {
            redirect('admin-messages');
        }
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            $messages = fetchMessagesForUser((int)$user['id']);
            echo json_encode(['ok' => true, 'messages' => $messages]);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAjax = (($_POST['ajax'] ?? '') === '1');
            $content = trim($_POST['content'] ?? '');
            $adminId = findAnyAdminId();
            if ($content === '') {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'error' => 'Nội dung không được để trống.']);
                    exit;
                }
                flash('error', 'Nội dung không được để trống.');
            } elseif ($adminId === null) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'error' => 'Chưa có admin để gửi.']);
                    exit;
                }
                flash('error', 'Chưa có admin để gửi.');
            } else {
                try {
                    createMessage((int)$user['id'], $adminId, $content, false);
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => true, 'messages' => fetchMessagesForUser((int)$user['id'])]);
                        exit;
                    }
                    flash('success', 'Đã gửi tin nhắn cho admin.');
                } catch (Throwable $e) {
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        http_response_code(500);
                        echo json_encode(['ok' => false, 'error' => 'Không gửi được tin nhắn.']);
                        exit;
                    }
                    flash('error', 'Không gửi được tin nhắn.');
                }
            }
            redirect('messages');
        }
        $messages = fetchMessagesForUser((int)$user['id']);
        render('messages', [
            'messages' => $messages,
            'user' => $user,
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'admin-messages':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        $filterUserId = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : null;
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            if ($_GET['ajax'] === 'users') {
                echo json_encode(['ok' => true, 'users' => fetchMessageUsers()]);
                exit;
            }
            $messages = fetchMessagesForAdmin($filterUserId);
            echo json_encode(['ok' => true, 'messages' => $messages]);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAjax = (($_POST['ajax'] ?? '') === '1');
            $content = trim($_POST['content'] ?? '');
            $target = trim($_POST['target_user_id'] ?? '');
            $receiver = $target !== '' ? (int)$target : null;
            $isBroadcast = $receiver === null;
            if ($content === '') {
                if ($isAjax) {
                    header('Content-Type: application/json'); echo json_encode(['ok' => false, 'error' => 'Nội dung không được để trống.']); exit;
                }
                flash('error', 'Nội dung không được để trống.');
            } else {
                try {
                    createMessage((int)$user['id'], $receiver, $content, $isBroadcast);
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'ok' => true,
                            'messages' => fetchMessagesForAdmin($receiver),
                            'users' => fetchMessageUsers(),
                        ]);
                        exit;
                    }
                    flash('success', $isBroadcast ? 'Đã gửi tin nhắn tới tất cả.' : 'Đã gửi tin nhắn tới người dùng.');
                } catch (Throwable $e) {
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        http_response_code(500);
                        echo json_encode(['ok' => false, 'error' => 'Không gửi được tin nhắn.']);
                        exit;
                    }
                    flash('error', 'Không gửi được tin nhắn.');
                }
            }
            redirect('admin-messages');
        }
        $messages = fetchMessagesForAdmin($filterUserId);
        $messageUsers = fetchMessageUsers();
        render('admin_messages', [
            'messages' => $messages,
            'messageUsers' => $messageUsers,
            'filterUserId' => $filterUserId,
            'user' => $user,
            'activeMenu' => 'messages',
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'admin-seek-posts':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        $status = $_GET['status'] ?? 'pending';
        $posts = adminFetchTenantPosts($status);
        render('admin_seek_posts', [
            'posts' => $posts,
            'status' => $status,
            'activeMenu' => 'seek',
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'admin-seek-action':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['post_id'] ?? 0);
            $action = $_POST['action'] ?? '';
            if (in_array($action, ['active','hidden'], true)) {
                adminSetTenantPostStatus($id, $action);
                flash('success', 'Đã cập nhật bài tìm phòng.');
            }
        }
        redirect('admin-seek-posts');
        break;

    case 'admin-cta':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        $messages = fetchCtaMessages();
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'messages' => $messages]);
            exit;
        }
        render('admin_cta', [
            'messages' => $messages,
            'activeMenu' => 'cta',
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'admin-payment-action':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'admin') redirect('login');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $action = $_POST['action'] ?? '';
            if (in_array($action, ['paid','failed','pending'], true)) {
                // lấy payment để biết lead_id trước khi cập nhật
                $pdo = getPDO();
                $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
                $stmt->execute([':id' => $paymentId]);
                $payment = $stmt->fetch();

                adminUpdatePaymentStatus($paymentId, $action);
                transactionLog('admin_payment_status_changed', [
                    'status' => $action,
                    'entity_type' => 'payment',
                    'entity_id' => (string)$paymentId,
                    'reference_code' => (string)($payment['payment_code'] ?? ''),
                    'amount' => isset($payment['amount']) ? (int)$payment['amount'] : null,
                ]);
                auditLog('admin.payment_status_updated', [
                    'entity_type' => 'payment',
                    'entity_id' => (string)$paymentId,
                    'status' => $action,
                ]);

                if ($action === 'paid' && $payment && !empty($payment['lead_id'])) {
                    openLeadByPayment((int)$payment['lead_id']);
                }
                flash('success', 'Đã cập nhật payment.');
            }
        }
        redirect('admin-payments');
        break;

    case 'lead-history':
        $user = ensureLoggedIn();
        if ($user['role'] !== 'tenant' && $user['role'] !== 'admin') {
            flash('error', 'Chỉ người tìm phòng hoặc admin mới xem lịch sử lead.');
            redirect('login');
        }
        $leads = tenantLeadsByPhone($user['phone']);
        render('lead_history', [
            'leads' => $leads,
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'payment-history':
        $landlordId = ensureLandlord();
        if (!staffHasPermission('invoice_manage')) {
            flash('error', 'Staff chưa được cấp quyền xem lịch sử thanh toán.');
            redirect('dashboard');
        }
        if (($_GET['ajax'] ?? '') === 'status') {
            header('Content-Type: application/json; charset=utf-8');
            $ids = preg_split('/\s*,\s*/', (string)($_GET['ids'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            echo json_encode([
                'ok' => true,
                'payments' => paymentStatusSnapshotByIds($landlordId, $ids),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $payments = paymentsByLandlord($landlordId);
        auditLog('payment.history_view', [
            'entity_type' => 'payment',
            'entity_id' => 'multiple',
            'count' => count($payments),
        ]);
        render('payment_history', [
            'payments' => $payments,
            'activeMenu' => 'payments',
            'focusPaymentId' => (int)($_GET['focus_payment'] ?? 0),
            'flashSuccess' => flash('success'),
            'flashError' => flash('error'),
        ]);
        break;

    case 'remind-lead':
        $landlordId = ensureLandlord();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!staffHasPermission('lead_manage')) {
                flash('error', 'Staff chưa được cấp quyền nhắc lead.');
                redirect('dashboard', ['tab' => 'lead']);
            }
            $leadId = (int)($_POST['lead_id'] ?? 0);
            if (remindLead($leadId, $landlordId)) {
                auditLog('lead.reminded', [
                    'entity_type' => 'lead',
                    'entity_id' => (string)$leadId,
                ]);
                flash('success', 'Đã gửi nhắc nhở người thuê (ghi nhận).');
            } else {
                flash('error', 'Không thể nhắc (chỉ VIP2+ hoặc đã nhắc trong 24h).');
            }
        }
        redirect('dashboard', ['tab' => 'lead']);
        break;

    default:
        http_response_code(404);
        echo '404';
}
function containsContactInfo(string $text): bool
{
    $patterns = [
        '/(0|\\+84)[0-9]{8,10}/', // số điện thoại VN
        '/zalo/i',
        '/facebook|fb\\.com|fb /i',
        '/liên\\s*hệ|lien\\s*he/i',
        '/sdt/i',
        '/call/i',
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $text)) {
            return true;
        }
    }
    return false;
}

function normalizeIncomingRoute(array &$query): string
{
    $route = isset($query['route']) ? (string)$query['route'] : 'rooms';
    $params = array();
    $cleanRoute = parseInternalRouteTarget($route, $params, 'rooms');
    foreach ($params as $key => $value) {
        if ($key !== 'route' && !isset($query[$key])) {
            $query[$key] = $value;
            $_GET[$key] = $value;
        }
    }
    return $cleanRoute;
}

function redirectToInternalRoute(string $target, string $fallback = 'rooms'): void
{
    $params = array();
    $route = parseInternalRouteTarget($target, $params, $fallback);
    redirect($route, $params);
}

function parseInternalRouteTarget(string $target, array &$params = array(), string $fallback = 'rooms'): string
{
    $params = array();
    $target = trim(rawurldecode($target));
    if ($target === '') {
        return $fallback;
    }

    if (strpos($target, 'route=') === 0) {
        parse_str($target, $query);
        if (!empty($query['route'])) {
            $route = sanitizeRouteName((string)$query['route'], $fallback);
            unset($query['route']);
            $params = array_filter($query, 'is_scalar');
            return $route;
        }
    }

    $parts = parse_url($target);
    if (is_array($parts) && !empty($parts['query'])) {
        parse_str($parts['query'], $query);
        if (!empty($query['route'])) {
            $route = sanitizeRouteName((string)$query['route'], $fallback);
            unset($query['route']);
            $params = array_filter($query, 'is_scalar');
            return $route;
        }
    }

    $route = trim($target, " \t\n\r\0\x0B/");
    if (strpos($route, '?') !== false) {
        $route = substr($route, 0, strpos($route, '?'));
    }
    return sanitizeRouteName($route, $fallback);
}

function sanitizeRouteName(string $route, string $fallback = 'rooms'): string
{
    $route = trim($route);
    return preg_match('/^[a-zA-Z0-9_-]+$/', $route) ? $route : $fallback;
}

function handleUpload(string $field, array $allowedExtensions = ['jpg','jpeg','png','gif','webp','mp4']): ?string
{
    if (!isset($_FILES[$field]) || empty($_FILES[$field]['name'])) {
        return null;
    }
    if (!is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return null;
    }
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        return null;
    }
    $destDir = uploadsPath();
    $newName = uniqid($field . '_') . '.' . $ext;
    $dest = $destDir . '/' . $newName;
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        return 'storage/uploads/' . $newName;
    }
    return null;
}
