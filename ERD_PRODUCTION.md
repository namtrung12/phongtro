# ERD Production (Mermaid)

```mermaid
erDiagram
  roles ||--o{ users : primary_role
  users ||--|| user_profiles : has
  users ||--|| landlord_profiles : has
  users ||--|| tenant_profiles : has

  users ||--o{ properties : owns
  properties ||--o{ rooms : has
  rooms ||--o{ room_images : has
  rooms ||--o{ room_status_history : history
  users ||--o{ room_status_history : changed_by

  users ||--o{ leads : tenant_or_creator
  leads ||--o{ lead_matches : matched
  rooms ||--o{ lead_matches : candidate
  leads ||--o{ lead_purchases : purchased
  users ||--o{ lead_purchases : landlord
  rooms ||--o{ lead_purchases : selected_room
  leads ||--o{ lead_contact_logs : contact_log
  users ||--o{ lead_contact_logs : contacted_by

  rooms ||--o{ contracts : contracted
  users ||--o{ contracts : landlord_or_tenant
  contracts ||--o{ contract_files : attachments
  users ||--o{ contract_files : uploaded_by
  rooms ||--o{ room_tenants : occupancy
  users ||--o{ room_tenants : tenant
  contracts ||--o{ room_tenants : link

  rooms ||--o{ invoices : billed
  contracts ||--o{ invoices : source
  users ||--o{ invoices : landlord_or_tenant
  invoices ||--o{ invoice_items : line_items
  rooms ||--o{ meter_readings : utility
  invoices ||--o{ meter_readings : usage_in_invoice
  users ||--o{ meter_readings : recorded_by

  invoices ||--o{ payments : paid_by
  lead_purchases ||--o{ payments : lead_payment
  users ||--o{ payments : payer_or_payee
  payments ||--o{ payment_transactions : gateway_events

  properties ||--o{ maintenance_tickets : maintenance_scope
  rooms ||--o{ maintenance_tickets : room_issue
  users ||--o{ maintenance_tickets : tenant_landlord_assignee
  maintenance_tickets ||--o{ maintenance_comments : comments
  users ||--o{ maintenance_comments : author
  maintenance_tickets ||--o{ maintenance_attachments : files
  maintenance_comments ||--o{ maintenance_attachments : from_comment
  users ||--o{ maintenance_attachments : uploaded_by

  users ||--o{ notifications : receives
  users ||--o{ activity_logs : performs
  users ||--o{ audit_logs : actor
```

## Ghi chú nhanh

- Canonical status đã khóa trong schema bằng `ENUM` cho: `rooms`, `leads`, `contracts`, `invoices`, `maintenance_tickets`.
- Index đã thêm cho toàn bộ khóa ngoại và các pattern lọc phổ biến: `status + date`, `landlord + status`, `room + period`.
- `audit_logs` giữ `record_id` dạng chuỗi để ghi được cả bản ghi số và UUID nếu sau này mở rộng.
