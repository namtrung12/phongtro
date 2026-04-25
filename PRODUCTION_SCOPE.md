# Production Scope

## 1. Mục tiêu sản phẩm

Xây dựng một nền tảng quản lý phòng trọ và giao dịch lead vận hành được trong môi trường production, phục vụ 4 nhóm chính:

- `Admin`: vận hành nền tảng, kiểm soát chất lượng, tài chính, an toàn hệ thống.
- `Landlord`: đăng phòng, mua lead, quản lý phòng, hợp đồng, hóa đơn, vận hành thuê trọ.
- `Staff`: nhân sự hỗ trợ chủ trọ theo phân quyền như kế toán, CSKH, kỹ thuật.
- `Tenant`: tìm phòng, gửi nhu cầu, thanh toán, theo dõi hợp đồng, hóa đơn, ticket hỗ trợ.

Mục tiêu không chỉ là hiển thị dữ liệu mà phải đảm bảo:

- giao dịch rõ ràng
- dòng tiền đối soát được
- trạng thái vận hành nhất quán
- truy vết được ai làm gì
- có thể mở rộng quy mô theo chủ trọ, cụm trọ, khu vực

## 2. Must-have

Đây là nhóm bắt buộc phải có nếu muốn vận hành thật.

### 2.1 Lead Marketplace

- Danh sách lead theo khu vực, ngân sách, loại phòng, thời gian cần thuê, độ mới, độ ưu tiên.
- Preview lead trước mua, che thông tin nhạy cảm.
- Mở full contact sau khi thanh toán thành công.
- Lead status chuẩn: `new`, `purchased`, `contacted`, `viewing_scheduled`, `negotiating`, `won`, `lost`, `invalid`, `refunded`.
- Match score giữa lead và phòng.
- Lịch sử tương tác lead.
- Chống trùng lead, chống fake lead, đánh dấu lead lỗi.
- Quy trình dispute/refund khi lead không hợp lệ.

### 2.2 CRM / Tenant Lifecycle

- Quản lý pipeline từ lead đến khách thuê thật.
- Các stage chuẩn: `lead`, `qualified`, `viewing`, `reserved`, `contract_pending`, `active_tenant`, `renewed`, `move_out`, `closed`.
- Ghi chú CSKH, lịch hẹn xem phòng, kết quả follow-up.
- Gắn lead với phòng, tenant, hợp đồng và lịch sử thuê.

### 2.3 Property / Room Operations

- Quản lý cấu trúc `property` -> `building/block` -> `room`.
- Mỗi phòng có hồ sơ sống: giá thuê, tiền cọc, tenant hiện tại, hợp đồng hiện tại, công tơ, hóa đơn, tình trạng, lịch sử thuê, ảnh hiện trạng.
- Room status chuẩn: `available`, `reserved`, `occupied`, `maintenance`, `archived`.
- Nhật ký thay đổi trạng thái phòng.

### 2.4 Reservation / Move-in / Move-out

- Giữ chỗ phòng và theo dõi tiền giữ chỗ.
- Checklist nhận phòng.
- Biên bản bàn giao đầu vào.
- Checklist trả phòng.
- Quyết toán khi kết thúc thuê.
- Hoàn cọc hoặc khấu trừ cọc có lý do rõ ràng.

### 2.5 Contract Management

- Tạo hợp đồng từ mẫu.
- Version hóa hợp đồng.
- Điều khoản thanh toán, cọc, thời hạn thuê, phụ lục.
- File đính kèm.
- Xác nhận điện tử 2 bên.
- Nhắc hết hạn hợp đồng.
- Gia hạn, tái ký, kết thúc hợp đồng.
- Contract status chuẩn: `draft`, `pending_signature`, `active`, `expiring`, `ended`, `renewed`, `cancelled`.

### 2.6 Billing & Payment

- Sinh hóa đơn theo tháng.
- Nhập điện nước, phụ phí, giảm giá.
- Tính tổng tiền tự động.
- Cho phép `partial payment`.
- Tự chuyển `overdue` theo hạn thanh toán.
- Tenant xem hóa đơn và thanh toán online.
- Xuất PDF hóa đơn.
- Theo dõi giao dịch thanh toán và đối soát.
- Invoice status chuẩn: `draft`, `issued`, `unpaid`, `partially_paid`, `paid`, `overdue`, `cancelled`.

### 2.7 Maintenance / Support Ticket

- Tenant tạo ticket với tiêu đề, mô tả, ưu tiên, ảnh/video.
- Phân công người xử lý.
- Theo dõi SLA xử lý.
- Lịch sử trao đổi nội bộ.
- Ghi nhận chi phí sửa chữa.
- Ticket status chuẩn: `open`, `in_progress`, `waiting_parts`, `resolved`, `closed`.

### 2.8 Notification Center

- Trung tâm thông báo tập trung cho lead, hợp đồng, hóa đơn, thanh toán, ticket, trạng thái phòng.
- Hỗ trợ `in-app`.
- Hỗ trợ email cho các sự kiện quan trọng.
- Có khả năng mở rộng SMS/Zalo.
- Cho phép đánh dấu đã đọc, lọc theo loại sự kiện, deep link về đúng đối tượng liên quan.

### 2.9 Finance & Ledger

- Sổ công nợ theo tenant.
- Sổ thu chi theo phòng và theo landlord.
- Theo dõi cọc: thu cọc, hoàn cọc, khấu trừ cọc.
- Chi phí sửa chữa và chi phí vận hành.
- Doanh thu gộp, doanh thu thuần, công nợ quá hạn.
- Đối soát payment gateway và webhook.

### 2.10 Admin Operations

- Duyệt bài đăng, duyệt chủ trọ, kiểm soát nội dung.
- Khóa/mở tài khoản.
- Xử lý dispute lead.
- Xử lý ticket escalations.
- Cấu hình phí nền tảng, giá lead, rule matching cơ bản.
- Theo dõi giao dịch bất thường.

### 2.11 Identity, RBAC, Security

- Đăng nhập an toàn.
- Xác minh số điện thoại, email nếu có.
- Phân quyền theo vai trò và hành động.
- Staff không được thấy hoặc sửa quá quyền.
- Rate limit các hành vi nhạy cảm.
- Quản lý session, thiết bị, reset mật khẩu.
- Bảo vệ file hợp đồng, hóa đơn, ảnh riêng tư.

### 2.12 Audit Log

- Ghi lại ai tạo, sửa, xóa, duyệt, đổi trạng thái.
- Log các thay đổi với lead, hợp đồng, hóa đơn, ticket, room status, refund.
- Có khả năng truy vết khiếu nại và sai lệch dữ liệu.

### 2.13 Automation / Scheduler

- Tự tạo hóa đơn hàng tháng.
- Tự nhắc hạn thanh toán.
- Tự nhắc hợp đồng sắp hết hạn.
- Tự nhắc ticket quá SLA.
- Tự gắn cờ overdue.
- Tự gửi thông báo theo rule.

## 3. Should-have

Nhóm này chưa bắt buộc ngày đầu nhưng rất nên có sớm.

- Dashboard theo landlord: doanh thu tháng, công nợ, occupancy, chi phí sửa chữa, tỷ lệ chốt lead.
- Dashboard theo admin: doanh thu nền tảng, số lead được mua, conversion rate, top landlord, khu vực nhu cầu cao.
- Bộ lọc nâng cao cho lead và phòng.
- Lead scoring nâng cao bằng AI/rule engine.
- Phân quyền chi tiết theo cụm trọ hoặc team nội bộ.
- Import/export Excel.
- Báo cáo PDF định kỳ.
- Mẫu tin nhắn và mẫu thông báo.
- Comment nội bộ giữa landlord và staff.
- Nhật ký hoạt động tenant.

## 4. Phase 2

Nhóm này làm sau khi hệ thống lõi đã ổn định.

- Dynamic pricing cho lead.
- Gợi ý giá thuê theo khu vực.
- Chấm điểm uy tín landlord và tenant.
- KYC nâng cao cho chủ trọ.
- Tích hợp chữ ký điện tử bên thứ ba.
- Zalo/SMS automation.
- Hệ thống commission/payout nhiều cấp.
- Mobile app riêng cho tenant và landlord.
- API public hoặc đối tác.
- BI nâng cao và forecasting occupancy/revenue.

## 5. Yêu cầu phi chức năng

Nếu làm production, cần chốt ngay từ đầu các tiêu chí sau:

- `Consistency`: trạng thái các module phải thống nhất, không để UI và DB dùng bộ status khác nhau.
- `Observability`: có error logging, job logging, webhook logging.
- `Backup`: backup định kỳ DB và file uploads.
- `Recovery`: có quy trình restore dữ liệu.
- `Performance`: danh sách lead, room, invoice phải có filter/index rõ ràng.
- `Privacy`: masking dữ liệu nhạy cảm trước và sau giao dịch theo quyền.
- `Idempotency`: webhook thanh toán, auto-jobs, refund flow phải chống chạy lặp.
- `Security`: phân quyền tải file, kiểm soát upload, rate limit API/action.

## 6. Database entities chính

Đây là nhóm entity nên tồn tại ở mức domain model. Có thể gộp hoặc tách bảng tùy thiết kế cuối cùng, nhưng không nên thiếu về mặt nghiệp vụ.

### 6.1 Identity

- `users`
- `user_roles`
- `staff_permissions`
- `user_sessions`
- `verification_tokens`

### 6.2 Property

- `properties`
- `buildings` hoặc `property_blocks`
- `rooms`
- `room_media`
- `room_operations`
- `room_status_logs`

### 6.3 Lead / CRM

- `tenant_posts`
- `leads`
- `lead_matches`
- `lead_purchases`
- `lead_interactions`
- `lead_disputes`
- `crm_contacts`
- `crm_activities`

### 6.4 Reservation / Stay

- `reservations`
- `tenant_stays`
- `move_in_checklists`
- `move_out_checklists`
- `handover_records`
- `deposit_ledger`

### 6.5 Contracts

- `contract_templates`
- `room_contracts`
- `contract_versions`
- `contract_attachments`
- `contract_signatures`

### 6.6 Billing / Payment

- `invoices`
- `invoice_items`
- `meter_logs`
- `payment_transactions`
- `payment_reconciliations`
- `refunds`

### 6.7 Maintenance

- `tickets`
- `ticket_attachments`
- `ticket_comments`
- `ticket_assignments`
- `maintenance_costs`

### 6.8 Notification / Automation

- `notifications`
- `notification_deliveries`
- `scheduled_jobs`
- `job_runs`
- `webhook_events`

### 6.9 Finance / Audit

- `ledger_entries`
- `expense_records`
- `payouts`
- `audit_logs`

## 7. Canonical status gợi ý

Nên khóa sớm bộ status chuẩn để tránh sau này DB, backend và UI lệch nhau.

- `Room`: `available`, `reserved`, `occupied`, `maintenance`, `archived`
- `Lead`: `new`, `purchased`, `contacted`, `viewing_scheduled`, `negotiating`, `won`, `lost`, `invalid`, `refunded`
- `Contract`: `draft`, `pending_signature`, `active`, `expiring`, `ended`, `renewed`, `cancelled`
- `Invoice`: `draft`, `issued`, `unpaid`, `partially_paid`, `paid`, `overdue`, `cancelled`
- `Ticket`: `open`, `in_progress`, `waiting_parts`, `resolved`, `closed`
- `Reservation`: `pending`, `confirmed`, `cancelled`, `expired`, `converted`

## 8. Thứ tự triển khai khuyến nghị

Nếu phải triển khai production theo phase, nên làm theo thứ tự:

1. `Identity + RBAC + Audit`
2. `Property/Room Operations`
3. `Lead Marketplace + CRM`
4. `Reservation + Contract`
5. `Billing + Payment + Ledger`
6. `Maintenance + Notification`
7. `Automation + Dashboard`
8. `AI scoring + Phase 2 features`

## 9. Kết luận

Để hệ thống vận hành thật, không nên coi `Lead`, `Room`, `Contract`, `Billing`, `Ticket` là các module rời rạc. Chúng phải nối thành một vòng đời chung:

`Lead -> CRM -> Reservation -> Contract -> Billing -> Stay -> Move-out -> Settlement -> Re-engagement`

Đây là target scope production nên dùng làm chuẩn chốt nghiệp vụ. Mọi quyết định UI, route, schema, automation và phân quyền nên bám theo tài liệu này.

## 10. Gói nâng cấp "xịn hẳn" (5 module)

### 10.1 Matching thông minh

- Gợi ý `lead` phù hợp với `room` theo nhiều tín hiệu thay vì chỉ lọc cứng.
- Lưu phiên chạy matching, version thuật toán, độ tin cậy và breakdown điểm.
- Hỗ trợ chế độ chạy theo event hoặc batch re-compute.

### 10.2 Timeline tenant theo vòng đời thuê

- Timeline một dòng thời gian duy nhất cho mỗi tenant:
- `move_in`
- phát hành hóa đơn
- thanh toán
- ticket sự cố
- ký/kết thúc hợp đồng
- `move_out`
- Event có thể gắn ngược về invoice, payment, ticket, contract để truy vết đầy đủ.

### 10.3 PDF export chuẩn vận hành

- Xuất PDF đẹp cho:
- hợp đồng
- hóa đơn
- biên bản bàn giao
- Theo dõi trạng thái tạo file (`queued`, `processing`, `success`, `failed`) và metadata phát hành.

### 10.4 Nhật ký bàn giao phòng

- Biên bản bàn giao vào/ra phòng có mã riêng.
- Checklist nội thất theo từng hạng mục.
- Bộ ảnh hiện trạng trước khi vào và khi trả phòng.
- Hỗ trợ ghi nhận khấu trừ/thiệt hại theo item checklist.

### 10.5 Chấm điểm tenant / landlord nội bộ

- Điểm nội bộ không public cho người dùng cuối.
- Tenant: nhấn mạnh đúng hạn thanh toán, tuân thủ hợp đồng, hành vi vận hành.
- Landlord: nhấn mạnh tốc độ xử lý ticket, phối hợp vận hành, mức độ ổn định.
- Có bảng event chi tiết để giải thích vì sao điểm tăng/giảm.

## 11. Scope triển khai đề xuất (3 giai đoạn)

### Giai đoạn 1

- auth + role
- lead marketplace
- room management
- tenant assignment
- invoice + payment
- tenant portal

### Giai đoạn 2

- contract management
- maintenance tickets
- notifications
- dashboard analytics

### Giai đoạn 3

- matching score
- PDF export
- audit/reporting nâng cao
- payment gateway thật

## 12. Product positioning / concept name

- Tên sản phẩm khuyến nghị: `RentalOS`
- Mô tả chuyên nghiệp: `Smart Boarding House Management & Lead Marketplace`
- Định vị: hệ sinh thái thuê trọ end-to-end gồm marketplace lead + vận hành phòng + tenant portal + billing + support + analytics.
