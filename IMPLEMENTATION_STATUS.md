# Implementation Status

## Mục đích

Tài liệu này dùng để phân biệt rõ giữa:

- phần nào trong repo đã có backend nghiệp vụ thật
- phần nào mới là UI hoặc flow chưa đủ production
- phần nào còn thiếu và cần làm tiếp

Không dùng cảm giác "đã có màn hình" để kết luận là module đã xong.

## Kết luận nhanh

Codebase hiện tại **không chỉ là UI**. Repo đã có nhiều phần backend thật:

- route xử lý nghiệp vụ trong [index.php](/C:/xampp/htdocs/phongtro/index.php:477)
- logic nghiệp vụ tập trung trong [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:1)
- schema dữ liệu trong [database.sql](/C:/xampp/htdocs/phongtro/database.sql:1)
- webhook thanh toán thật trong [app/payment_webhook.php](/C:/xampp/htdocs/phongtro/app/payment_webhook.php:1)

Tuy vậy, hệ thống **chưa production-complete**. Nhiều flow đã có lõi backend nhưng chưa kín hết ở mức vận hành thật.

## 1. Đã có backend thật

### 1.1 Lead Marketplace

Đã có:

- tạo lead và mua lead
- match score / gợi ý phòng
- lead marketplace draft
- cập nhật stage lead
- mở lead sau thanh toán
- lead notifications

Điểm tựa code:

- [index.php](/C:/xampp/htdocs/phongtro/index.php:733)
- [index.php](/C:/xampp/htdocs/phongtro/index.php:766)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:1782)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:1835)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:1899)

Đánh giá:

- `Backend thật`: có
- `Production đủ kín`: chưa

Thiếu lớn:

- dispute/refund lead lỗi
- anti-fraud / dedupe mạnh hơn
- lifecycle CRM đầy đủ từ lead sang viewing/reservation/tenant

### 1.2 Room Operations

Đã có:

- hồ sơ vận hành phòng
- trạng thái phòng
- thông tin tenant hiện tại
- lưu công tơ điện nước
- lịch sử thuê
- bàn giao

Điểm tựa code:

- [index.php](/C:/xampp/htdocs/phongtro/index.php:1014)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:3801)
- [views/room_operations.php](/C:/xampp/htdocs/phongtro/views/room_operations.php:1)

Đánh giá:

- `Backend thật`: có
- `Production đủ kín`: gần nhưng chưa đủ

Thiếu lớn:

- cấu trúc `property/building/block`
- room status log riêng
- lifecycle reservation/move-in/move-out kín hơn

### 1.3 Contract Management

Đã có:

- bảng `room_contracts`
- tạo/cập nhật hợp đồng
- renew/end contract
- trạng thái theo ngày và xác nhận ký

Điểm tựa code:

- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:3130)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:4790)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:4986)

Đánh giá:

- `Backend thật`: có
- `Production đủ kín`: chưa

Thiếu lớn:

- version contract rõ ràng
- signature flow chuẩn hơn
- template management riêng
- attachment handling và quyền truy cập file chặt hơn

### 1.4 Billing & Payment

Đã có:

- tạo invoice thật
- validate kỳ hóa đơn và công tơ
- tính tiền điện nước
- lưu meter log
- cập nhật payment status
- partial payment ở backend
- QR/payment flow với SePay
- webhook xác nhận thanh toán

Điểm tựa code:

- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:3978)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:4165)
- [index.php](/C:/xampp/htdocs/phongtro/index.php:1726)
- [app/payment_webhook.php](/C:/xampp/htdocs/phongtro/app/payment_webhook.php:1)

Đánh giá:

- `Backend thật`: có
- `Production đủ kín`: chưa

Thiếu lớn:

- invoice PDF
- online payment cho tenant invoice, không chỉ lead payment
- reconciliation/ledger đầy đủ
- auto monthly billing bằng scheduler thật

### 1.5 Maintenance / Support Ticket

Đã có:

- tenant tạo ticket
- validate tenant gắn với phòng
- landlord cập nhật trạng thái
- notify landlord/tenant khi đổi trạng thái

Điểm tựa code:

- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:5051)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:5115)
- [views/room_operations.php](/C:/xampp/htdocs/phongtro/views/room_operations.php:1233)

Đánh giá:

- `Backend thật`: có
- `Production đủ kín`: chưa

Thiếu lớn:

- assignee rõ ràng
- SLA
- comment nội bộ
- vendor/repair workflow

### 1.6 Notification Center

Đã có:

- bảng `app_notifications`
- create/read/mark all read
- notify theo room cho landlord/tenant
- push subscription groundwork

Điểm tựa code:

- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:3157)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:3552)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:3639)

Đánh giá:

- `Backend thật`: có
- `Production đủ kín`: chưa

Thiếu lớn:

- UI notification center chưa gom đủ toàn bộ event type
- email/SMS chưa chạy thành kênh thật
- có TODO rõ ở [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:627)

### 1.7 Admin Operations

Đã có:

- admin fetch rooms/users/leads/payments
- admin duyệt bài và cập nhật status
- admin chỉnh giá lead

Điểm tựa code:

- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:5619)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:5656)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:5776)

Đánh giá:

- `Backend thật`: có
- `Production đủ kín`: chưa

Thiếu lớn:

- moderation workflow sâu hơn
- dispute resolution
- audit trail cấp admin

## 2. Đã có nhưng còn lệch UI/DB/backend

Đây là phần nguy hiểm nhất nếu muốn chạy production.

### 2.1 Invoice status

Backend đã support:

- `draft`
- `issued`
- `unpaid`
- `partially_paid`
- `paid`
- `overdue`
- `cancelled`

Điểm tựa code:

- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:3463)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:3923)

Nhưng UI ở room ops vẫn chủ yếu thao tác kiểu `paid/unpaid`:

- [views/room_operations.php](/C:/xampp/htdocs/phongtro/views/room_operations.php:1493)

### 2.2 Ticket status

Backend đã support:

- `open`
- `in_progress`
- `waiting_parts`
- `resolved`
- `closed`

Điểm tựa code:

- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:3452)

Nhưng UI hiện vẫn mới expose một phần action cơ bản:

- [views/room_operations.php](/C:/xampp/htdocs/phongtro/views/room_operations.php:1276)

### 2.3 Room status

Backend đã có `archived` trong schema/runtime:

- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:2987)
- [app/repositories.php](/C:/xampp/htdocs/phongtro/app/repositories.php:3411)

Nhưng `database.sql` export chính vẫn đang cũ hơn:

- [database.sql](/C:/xampp/htdocs/phongtro/database.sql:81)

Điều này có nghĩa:

- code runtime đã đi trước schema dump
- nếu deploy từ dump cứng mà không chạy migration runtime đầy đủ, có thể lệch trạng thái

## 3. Chưa thể gọi là production-ready

### 3.1 Automation thật

Hiện có rule logic cho reminder/trạng thái, nhưng chưa thấy scheduler/cron job đầy đủ cho:

- auto monthly invoice generation
- auto overdue transition theo batch
- auto contract reminder theo lịch
- auto SLA escalation

### 3.2 Finance / Ledger

Hiện đã có payment, deposit settlement một phần, repair cost một phần, nhưng chưa có sổ cái tài chính đầy đủ:

- ledger entries
- đối soát nhiều nguồn tiền
- payout
- refund accounting

### 3.3 RBAC sâu

Hiện có role cơ bản `tenant/landlord/admin`, nhưng chưa thấy lớp permission chi tiết cho:

- staff theo nghiệp vụ
- quyền theo resource/action
- hạn chế truy cập file/hợp đồng theo scope

### 3.4 Audit log đầy đủ

Có log rải rác theo module, nhưng chưa có audit log chuẩn hóa toàn hệ thống cho mọi hành vi nhạy cảm.

### 3.5 CRM lifecycle kín

Hiện lead đã khá mạnh, nhưng chưa có một pipeline CRM xuyên suốt thật chặt từ:

`lead -> viewing -> reservation -> contract_pending -> active_tenant -> move_out -> closed`

## 4. Đánh giá tổng quan

### Nhóm đã là backend thật

- Lead marketplace
- Room operations
- Contract management
- Billing lõi
- Ticket lõi
- Notification storage
- Admin moderation cơ bản
- Payment webhook

### Nhóm mới ở mức partial production

- notification delivery đa kênh
- invoice lifecycle ngoài UI
- contract lifecycle đầy đủ
- automation/scheduler
- finance/ledger
- CRM lifecycle end-to-end
- RBAC sâu
- audit log chuẩn hóa

## 5. Kết luận thực tế

Nói ngắn gọn:

- Repo hiện tại **không phải app chỉ có UI**.
- Repo hiện tại là **app có backend thật cho nhiều module chính**.
- Nhưng repo hiện tại **chưa đủ kín để gọi là production hoàn chỉnh**.

Đánh giá công bằng nhất:

- `UI demo`: không đúng
- `MVP có backend thật`: đúng
- `Production-grade hoàn chỉnh`: chưa

## 6. Việc nên làm tiếp

Nếu tiếp tục theo hướng làm thật, thứ tự nên là:

1. Chuẩn hóa status giữa UI, runtime schema và database dump
2. Bổ sung `Implementation backlog` theo `Must-have`
3. Làm `RBAC + Audit + Ledger`
4. Khóa `CRM lifecycle + Reservation + Move-out settlement`
5. Thêm `scheduler/jobs` thật cho invoice, contract, reminder, overdue
