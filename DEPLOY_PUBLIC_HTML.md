# Deploy len public_html

## 1. Upload source

Upload toan bo file/folder trong thu muc du an nay len `public_html`:

- `.env`
- `.env.hosting`
- Khong can upload `.env.local`; file nay chi dung cho XAMPP local.
- `.htaccess`
- `index.php`
- `app/`
- `views/`
- `storage/`
- `logo1.png`, `trongdong.png`, `avt.jpg`
- `database.sql`

Co the upload vao thang `public_html` de chay domain chinh, hoac vao
`public_html/phongtro` neu muon chay o duong dan `/phongtro`.

## 2. Cau hinh database

Trong hosting panel, tao database/user nhu sau:

```env
APP_URL=https://lehanam.website
DB_NAME=bhbaxoluhosting_phongtro
DB_USER=bhbaxoluhosting_lehanam2124
DB_PASS=Namtrung12@!
DB_HOST=localhost
DB_PORT=3306
```

File `.env` da duoc cau hinh san cho hosting. File `.env.local` chi dung cho
XAMPP local va se override `.env` khi code chay trong `xampp/htdocs`.

## 2.1. Cau hinh SePay

Trong SePay > WebHooks, dung URL:

```text
https://lehanam.website/payment-webhook.php
```

Tai khoan ngan hang/QR dang cau hinh trong `.env`:

```env
SEPAY_BANK_CODE=MBBank
SEPAY_ACCOUNT_NUMBER=0383765225
SEPAY_ACCOUNT_NAME=LE HA NAM
SEPAY_WEBHOOK_URL=https://lehanam.website/payment-webhook.php
```

Neu sau nay chon chung thuc API Key trong SePay, them vao `.env.hosting`:

```env
SEPAY_WEBHOOK_API_KEY=api_key_cua_ban
```

## 3. Import database

Neu co quyen root/admin MySQL, chay:

```bash
mysql -u root -p < database.sql
```

Neu import bang phpMyAdmin tren shared hosting va gap loi `CREATE USER`,
`GRANT` hoac `CREATE DATABASE`, hay tao database/user trong panel truoc,
sau do import phan tao bang trong `database.sql` tu dong:

```sql
SET FOREIGN_KEY_CHECKS=0;
```

tro xuong. Trong phpMyAdmin nen chon database
`bhbaxoluhosting_phongtro` truoc khi import.

## 3.1. Kiem tra loi 500

Sau khi upload, mo duong dan sau de xem hosting dang loi o buoc nao:

```text
https://ten-mien-cua-ban/hosting-check.php?key=check
```

File nay khong in mat khau, chi bao PHP version, extension, file thieu, quyen
ghi thu muc va ket noi database. Khi website da chay on, co the xoa file
`hosting-check.php`.

## 4. Quyen ghi file

Thu muc sau can ghi duoc de upload anh/video va luu giao dien:

```text
storage/
storage/uploads/
```

Thuong tren cPanel de quyen `755` la du neu file/folder thuoc dung user
hosting. Neu upload loi, doi rieng `storage` va `storage/uploads` sang `775`.

## 5. Yeu cau PHP

Chon PHP 8.0 tro len va bat extension:

- `pdo`
- `pdo_mysql`
- `mbstring`
- `fileinfo`

Tai khoan admin seed trong SQL:

```text
SDT: 0999999999
Mat khau: 123456
```
