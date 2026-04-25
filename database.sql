-- Production-ready schema for PhongTro (MySQL 8+ / MariaDB 10.5+)
-- Safe approach: keep existing database.sql unchanged and run this file on a new DB.
-- Example:
--   CREATE DATABASE phongtro_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   USE phongtro_production;
--   SOURCE database_production_v2.sql;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- =========================
-- 1) Identity / RBAC
-- =========================
CREATE TABLE IF NOT EXISTS roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_roles_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  primary_role_id BIGINT UNSIGNED NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(30) NOT NULL,
  password_hash VARCHAR(255) NULL,
  avatar_url VARCHAR(255) NULL,
  status ENUM('active', 'locked', 'pending_verification', 'deleted') NOT NULL DEFAULT 'pending_verification',
  phone_verified_at DATETIME NULL,
  email_verified_at DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_phone (phone),
  KEY idx_users_primary_role (primary_role_id),
  KEY idx_users_status_created (status, created_at),
  CONSTRAINT fk_users_primary_role
    FOREIGN KEY (primary_role_id) REFERENCES roles(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL,
  birth_date DATE NULL,
  province VARCHAR(120) NULL,
  district VARCHAR(120) NULL,
  ward VARCHAR(120) NULL,
  address_line VARCHAR(255) NULL,
  occupation VARCHAR(120) NULL,
  emergency_contact_name VARCHAR(150) NULL,
  emergency_contact_phone VARCHAR(30) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_profiles_user (user_id),
  CONSTRAINT fk_user_profiles_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS landlord_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  company_name VARCHAR(180) NULL,
  tax_code VARCHAR(50) NULL,
  national_id_no VARCHAR(50) NULL,
  payout_bank_name VARCHAR(120) NULL,
  payout_bank_account_no VARCHAR(50) NULL,
  payout_bank_account_name VARCHAR(150) NULL,
  verification_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  verification_note VARCHAR(255) NULL,
  verified_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_landlord_profiles_user (user_id),
  UNIQUE KEY uq_landlord_profiles_tax_code (tax_code),
  UNIQUE KEY uq_landlord_profiles_national_id (national_id_no),
  CONSTRAINT fk_landlord_profiles_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenant_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  national_id_no VARCHAR(50) NULL,
  job_title VARCHAR(120) NULL,
  monthly_income DECIMAL(14,2) NULL,
  preferred_room_type VARCHAR(100) NULL,
  preferred_budget_min DECIMAL(14,2) NULL,
  preferred_budget_max DECIMAL(14,2) NULL,
  tenant_score DECIMAL(5,2) NULL,
  verification_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  verified_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tenant_profiles_user (user_id),
  UNIQUE KEY uq_tenant_profiles_national_id (national_id_no),
  KEY idx_tenant_profiles_budget (preferred_budget_min, preferred_budget_max),
  CONSTRAINT fk_tenant_profiles_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- 2) Property / Room
-- =========================
CREATE TABLE IF NOT EXISTS properties (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  landlord_user_id BIGINT UNSIGNED NOT NULL,
  property_code VARCHAR(50) NOT NULL,
  property_name VARCHAR(180) NOT NULL,
  property_type ENUM('boarding_house', 'apartment', 'house', 'mixed') NOT NULL DEFAULT 'boarding_house',
  province VARCHAR(120) NOT NULL,
  district VARCHAR(120) NOT NULL,
  ward VARCHAR(120) NULL,
  address_line VARCHAR(255) NOT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  status ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_properties_code (property_code),
  KEY idx_properties_landlord_status (landlord_user_id, status),
  KEY idx_properties_location (province, district, ward),
  CONSTRAINT fk_properties_landlord
    FOREIGN KEY (landlord_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_properties_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_properties_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rooms (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id BIGINT UNSIGNED NOT NULL,
  room_code VARCHAR(50) NOT NULL,
  room_name VARCHAR(150) NOT NULL,
  floor_no VARCHAR(20) NULL,
  area_m2 DECIMAL(8,2) NULL,
  max_tenants SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  rental_price DECIMAL(14,2) NOT NULL,
  deposit_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  electric_unit_price DECIMAL(14,2) NULL,
  water_unit_price DECIMAL(14,2) NULL,
  service_fee DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('available', 'reserved', 'occupied', 'maintenance', 'archived') NOT NULL DEFAULT 'available',
  available_from DATE NULL,
  description TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_rooms_property_room_code (property_id, room_code),
  KEY idx_rooms_status_price (status, rental_price),
  KEY idx_rooms_property_status (property_id, status),
  CONSTRAINT fk_rooms_property
    FOREIGN KEY (property_id) REFERENCES properties(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_rooms_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_rooms_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_images (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id BIGINT UNSIGNED NOT NULL,
  file_url VARCHAR(255) NOT NULL,
  file_name VARCHAR(255) NULL,
  mime_type VARCHAR(100) NULL,
  file_size BIGINT UNSIGNED NULL,
  is_cover TINYINT(1) NOT NULL DEFAULT 0,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_room_images_room_order (room_id, sort_order),
  KEY idx_room_images_cover (room_id, is_cover),
  CONSTRAINT fk_room_images_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_room_images_uploaded_by
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_status_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id BIGINT UNSIGNED NOT NULL,
  old_status ENUM('available', 'reserved', 'occupied', 'maintenance', 'archived') NULL,
  new_status ENUM('available', 'reserved', 'occupied', 'maintenance', 'archived') NOT NULL,
  reason VARCHAR(255) NULL,
  note TEXT NULL,
  changed_by BIGINT UNSIGNED NULL,
  changed_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_room_status_history_room_changed (room_id, changed_at),
  KEY idx_room_status_history_new_status (new_status, changed_at),
  CONSTRAINT fk_room_status_history_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_room_status_history_changed_by
    FOREIGN KEY (changed_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- 3) Lead Marketplace
-- =========================
CREATE TABLE IF NOT EXISTS leads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_code VARCHAR(50) NOT NULL,
  tenant_user_id BIGINT UNSIGNED NULL,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(190) NULL,
  desired_move_in_date DATE NULL,
  budget_min DECIMAL(14,2) NULL,
  budget_max DECIMAL(14,2) NULL,
  preferred_province VARCHAR(120) NULL,
  preferred_district VARCHAR(120) NULL,
  preferred_ward VARCHAR(120) NULL,
  preferred_room_type VARCHAR(100) NULL,
  note TEXT NULL,
  source_channel VARCHAR(50) NULL,
  quality_score DECIMAL(5,2) NULL,
  status ENUM('new', 'purchased', 'contacted', 'viewing_scheduled', 'negotiating', 'won', 'lost', 'invalid', 'refunded') NOT NULL DEFAULT 'new',
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_leads_code (lead_code),
  KEY idx_leads_status_created (status, created_at),
  KEY idx_leads_location (preferred_province, preferred_district, preferred_ward),
  KEY idx_leads_budget (budget_min, budget_max),
  CONSTRAINT fk_leads_tenant_user
    FOREIGN KEY (tenant_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_leads_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_leads_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_matches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id BIGINT UNSIGNED NOT NULL,
  room_id BIGINT UNSIGNED NOT NULL,
  match_score DECIMAL(5,2) NOT NULL,
  match_reason_json JSON NULL,
  rank_order SMALLINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_lead_matches_lead_room (lead_id, room_id),
  KEY idx_lead_matches_room_score (room_id, match_score),
  CONSTRAINT fk_lead_matches_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_lead_matches_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_purchases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id BIGINT UNSIGNED NOT NULL,
  landlord_user_id BIGINT UNSIGNED NOT NULL,
  room_id BIGINT UNSIGNED NULL,
  purchase_price DECIMAL(14,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'VND',
  status ENUM('pending', 'paid', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'pending',
  purchased_at DATETIME NULL,
  unlocked_at DATETIME NULL,
  refunded_at DATETIME NULL,
  refund_reason VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_lead_purchases_lead_landlord (lead_id, landlord_user_id),
  KEY idx_lead_purchases_status_created (status, created_at),
  KEY idx_lead_purchases_landlord_status (landlord_user_id, status),
  CONSTRAINT fk_lead_purchases_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_lead_purchases_landlord
    FOREIGN KEY (landlord_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_lead_purchases_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_contact_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id BIGINT UNSIGNED NOT NULL,
  landlord_user_id BIGINT UNSIGNED NOT NULL,
  contacted_by BIGINT UNSIGNED NULL,
  contact_method ENUM('call', 'sms', 'zalo', 'email', 'chat', 'other') NOT NULL DEFAULT 'call',
  contact_result ENUM('no_answer', 'connected', 'interested', 'not_interested', 'wrong_number', 'schedule_viewing', 'follow_up', 'won', 'lost') NOT NULL DEFAULT 'follow_up',
  note TEXT NULL,
  next_follow_up_at DATETIME NULL,
  contacted_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_lead_contact_logs_lead_time (lead_id, contacted_at),
  KEY idx_lead_contact_logs_landlord_time (landlord_user_id, contacted_at),
  CONSTRAINT fk_lead_contact_logs_lead
    FOREIGN KEY (lead_id) REFERENCES leads(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_lead_contact_logs_landlord
    FOREIGN KEY (landlord_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_lead_contact_logs_contacted_by
    FOREIGN KEY (contacted_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- 4) Contract / Stay
-- =========================
CREATE TABLE IF NOT EXISTS contracts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contract_code VARCHAR(60) NOT NULL,
  room_id BIGINT UNSIGNED NOT NULL,
  landlord_user_id BIGINT UNSIGNED NOT NULL,
  primary_tenant_user_id BIGINT UNSIGNED NULL,
  previous_contract_id BIGINT UNSIGNED NULL,
  title VARCHAR(180) NOT NULL,
  status ENUM('draft', 'pending_signature', 'active', 'expiring', 'ended', 'renewed', 'cancelled') NOT NULL DEFAULT 'draft',
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  sign_due_date DATE NULL,
  tenant_signed_at DATETIME NULL,
  landlord_signed_at DATETIME NULL,
  monthly_rent DECIMAL(14,2) NOT NULL,
  deposit_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  payment_due_day TINYINT UNSIGNED NOT NULL DEFAULT 5,
  billing_cycle_day TINYINT UNSIGNED NOT NULL DEFAULT 1,
  terms_json JSON NULL,
  note TEXT NULL,
  terminated_at DATETIME NULL,
  termination_reason VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_contracts_code (contract_code),
  KEY idx_contracts_room_status (room_id, status),
  KEY idx_contracts_tenant_status (primary_tenant_user_id, status),
  KEY idx_contracts_end_date_status (end_date, status),
  CONSTRAINT fk_contracts_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_contracts_landlord
    FOREIGN KEY (landlord_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_contracts_primary_tenant
    FOREIGN KEY (primary_tenant_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_contracts_previous
    FOREIGN KEY (previous_contract_id) REFERENCES contracts(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_contracts_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_contracts_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contract_files (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contract_id BIGINT UNSIGNED NOT NULL,
  file_type ENUM('contract', 'appendix', 'id_document', 'handover', 'other') NOT NULL DEFAULT 'contract',
  file_name VARCHAR(255) NOT NULL,
  file_url VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NULL,
  file_size BIGINT UNSIGNED NULL,
  version_no INT UNSIGNED NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_contract_files_contract_created (contract_id, created_at),
  KEY idx_contract_files_contract_type (contract_id, file_type),
  CONSTRAINT fk_contract_files_contract
    FOREIGN KEY (contract_id) REFERENCES contracts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_contract_files_uploaded_by
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_tenants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id BIGINT UNSIGNED NOT NULL,
  tenant_user_id BIGINT UNSIGNED NOT NULL,
  contract_id BIGINT UNSIGNED NULL,
  tenant_role ENUM('primary', 'co_tenant', 'occupant') NOT NULL DEFAULT 'occupant',
  move_in_date DATE NOT NULL,
  move_out_date DATE NULL,
  status ENUM('active', 'moved_out', 'evicted', 'cancelled') NOT NULL DEFAULT 'active',
  deposit_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_room_tenants_unique_stay (room_id, tenant_user_id, move_in_date),
  KEY idx_room_tenants_room_status (room_id, status),
  KEY idx_room_tenants_tenant_status (tenant_user_id, status),
  CONSTRAINT fk_room_tenants_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_room_tenants_tenant
    FOREIGN KEY (tenant_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_room_tenants_contract
    FOREIGN KEY (contract_id) REFERENCES contracts(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- 5) Billing / Payment
-- =========================
CREATE TABLE IF NOT EXISTS invoices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_code VARCHAR(60) NOT NULL,
  room_id BIGINT UNSIGNED NOT NULL,
  contract_id BIGINT UNSIGNED NULL,
  tenant_user_id BIGINT UNSIGNED NULL,
  landlord_user_id BIGINT UNSIGNED NOT NULL,
  billing_period_start DATE NOT NULL,
  billing_period_end DATE NOT NULL,
  issue_date DATE NOT NULL,
  due_date DATE NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'VND',
  subtotal_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('draft', 'issued', 'unpaid', 'partially_paid', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'draft',
  note TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_invoices_code (invoice_code),
  KEY idx_invoices_room_period (room_id, billing_period_start, billing_period_end),
  KEY idx_invoices_tenant_status_due (tenant_user_id, status, due_date),
  KEY idx_invoices_landlord_status_due (landlord_user_id, status, due_date),
  CONSTRAINT fk_invoices_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_invoices_contract
    FOREIGN KEY (contract_id) REFERENCES contracts(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_invoices_tenant
    FOREIGN KEY (tenant_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_invoices_landlord
    FOREIGN KEY (landlord_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_invoices_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_invoices_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoice_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL,
  item_type ENUM('rent', 'electricity', 'water', 'service', 'parking', 'internet', 'maintenance', 'penalty', 'discount', 'other') NOT NULL,
  description VARCHAR(255) NOT NULL,
  quantity DECIMAL(12,3) NOT NULL DEFAULT 1,
  unit_price DECIMAL(14,2) NOT NULL DEFAULT 0,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  reference_type VARCHAR(50) NULL,
  reference_id BIGINT UNSIGNED NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_invoice_items_invoice_order (invoice_id, sort_order),
  KEY idx_invoice_items_invoice_type (invoice_id, item_type),
  CONSTRAINT fk_invoice_items_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS meter_readings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id BIGINT UNSIGNED NOT NULL,
  invoice_id BIGINT UNSIGNED NULL,
  meter_type ENUM('electricity', 'water', 'gas', 'other') NOT NULL DEFAULT 'electricity',
  reading_period_start DATE NOT NULL,
  reading_period_end DATE NOT NULL,
  previous_reading DECIMAL(14,3) NOT NULL DEFAULT 0,
  current_reading DECIMAL(14,3) NOT NULL DEFAULT 0,
  consumption DECIMAL(14,3) NOT NULL DEFAULT 0,
  unit_price DECIMAL(14,2) NOT NULL DEFAULT 0,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  photo_url VARCHAR(255) NULL,
  note VARCHAR(255) NULL,
  recorded_by BIGINT UNSIGNED NULL,
  recorded_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_meter_room_type_period (room_id, meter_type, reading_period_end),
  KEY idx_meter_invoice (invoice_id),
  KEY idx_meter_room_type_period (room_id, meter_type, reading_period_start, reading_period_end),
  CONSTRAINT fk_meter_readings_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_meter_readings_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_meter_readings_recorded_by
    FOREIGN KEY (recorded_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payment_code VARCHAR(60) NOT NULL,
  invoice_id BIGINT UNSIGNED NULL,
  lead_purchase_id BIGINT UNSIGNED NULL,
  payer_user_id BIGINT UNSIGNED NOT NULL,
  payee_user_id BIGINT UNSIGNED NULL,
  payment_type ENUM('rent', 'deposit', 'lead', 'maintenance', 'refund', 'other') NOT NULL DEFAULT 'other',
  payment_method ENUM('cash', 'bank_transfer', 'gateway', 'wallet', 'other') NOT NULL DEFAULT 'bank_transfer',
  amount DECIMAL(14,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'VND',
  status ENUM('pending', 'processing', 'paid', 'failed', 'cancelled', 'refunded', 'partially_refunded') NOT NULL DEFAULT 'pending',
  requested_at DATETIME NULL,
  paid_at DATETIME NULL,
  note VARCHAR(255) NULL,
  metadata_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_payments_code (payment_code),
  KEY idx_payments_invoice_status (invoice_id, status),
  KEY idx_payments_lead_purchase_status (lead_purchase_id, status),
  KEY idx_payments_payer_created (payer_user_id, created_at),
  KEY idx_payments_payee_created (payee_user_id, created_at),
  CONSTRAINT fk_payments_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_payments_lead_purchase
    FOREIGN KEY (lead_purchase_id) REFERENCES lead_purchases(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_payments_payer
    FOREIGN KEY (payer_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_payments_payee
    FOREIGN KEY (payee_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payment_id BIGINT UNSIGNED NOT NULL,
  provider VARCHAR(50) NOT NULL,
  transaction_ref VARCHAR(120) NOT NULL,
  idempotency_key VARCHAR(120) NULL,
  direction ENUM('in', 'out') NOT NULL DEFAULT 'in',
  amount DECIMAL(14,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'VND',
  status ENUM('initiated', 'success', 'failed', 'reversed', 'timeout') NOT NULL DEFAULT 'initiated',
  failure_reason VARCHAR(255) NULL,
  raw_payload_json JSON NULL,
  processed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_payment_transactions_provider_ref (provider, transaction_ref),
  UNIQUE KEY uq_payment_transactions_idempotency (idempotency_key),
  KEY idx_payment_transactions_payment_time (payment_id, created_at),
  KEY idx_payment_transactions_status_time (status, created_at),
  CONSTRAINT fk_payment_transactions_payment
    FOREIGN KEY (payment_id) REFERENCES payments(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- 6) Maintenance
-- =========================
CREATE TABLE IF NOT EXISTS maintenance_tickets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_code VARCHAR(60) NOT NULL,
  property_id BIGINT UNSIGNED NOT NULL,
  room_id BIGINT UNSIGNED NULL,
  tenant_user_id BIGINT UNSIGNED NULL,
  landlord_user_id BIGINT UNSIGNED NOT NULL,
  assigned_to_user_id BIGINT UNSIGNED NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
  status ENUM('open', 'in_progress', 'waiting_parts', 'resolved', 'closed') NOT NULL DEFAULT 'open',
  sla_due_at DATETIME NULL,
  started_at DATETIME NULL,
  resolved_at DATETIME NULL,
  closed_at DATETIME NULL,
  estimated_cost DECIMAL(14,2) NULL,
  actual_cost DECIMAL(14,2) NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_maintenance_tickets_code (ticket_code),
  KEY idx_maintenance_tickets_room_status (room_id, status, priority),
  KEY idx_maintenance_tickets_assignee_status (assigned_to_user_id, status),
  KEY idx_maintenance_tickets_landlord_status (landlord_user_id, status, created_at),
  CONSTRAINT fk_maintenance_tickets_property
    FOREIGN KEY (property_id) REFERENCES properties(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_maintenance_tickets_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_maintenance_tickets_tenant
    FOREIGN KEY (tenant_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_maintenance_tickets_landlord
    FOREIGN KEY (landlord_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_maintenance_tickets_assignee
    FOREIGN KEY (assigned_to_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_maintenance_tickets_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_maintenance_tickets_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS maintenance_comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id BIGINT UNSIGNED NOT NULL,
  author_user_id BIGINT UNSIGNED NOT NULL,
  visibility ENUM('public', 'internal') NOT NULL DEFAULT 'public',
  content TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_maintenance_comments_ticket_time (ticket_id, created_at),
  KEY idx_maintenance_comments_author_time (author_user_id, created_at),
  CONSTRAINT fk_maintenance_comments_ticket
    FOREIGN KEY (ticket_id) REFERENCES maintenance_tickets(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_maintenance_comments_author
    FOREIGN KEY (author_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS maintenance_attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id BIGINT UNSIGNED NOT NULL,
  comment_id BIGINT UNSIGNED NULL,
  file_name VARCHAR(255) NOT NULL,
  file_url VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NULL,
  file_size BIGINT UNSIGNED NULL,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_maintenance_attachments_ticket_time (ticket_id, created_at),
  KEY idx_maintenance_attachments_comment (comment_id),
  CONSTRAINT fk_maintenance_attachments_ticket
    FOREIGN KEY (ticket_id) REFERENCES maintenance_tickets(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_maintenance_attachments_comment
    FOREIGN KEY (comment_id) REFERENCES maintenance_comments(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_maintenance_attachments_uploaded_by
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- 7) Notification / Logs
-- =========================
CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  notification_type VARCHAR(60) NOT NULL,
  channel ENUM('in_app', 'email', 'sms', 'zalo', 'push') NOT NULL DEFAULT 'in_app',
  title VARCHAR(180) NOT NULL,
  body TEXT NULL,
  entity_type VARCHAR(60) NULL,
  entity_id BIGINT UNSIGNED NULL,
  deep_link VARCHAR(255) NULL,
  status ENUM('queued', 'sent', 'delivered', 'failed', 'read') NOT NULL DEFAULT 'queued',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  sent_at DATETIME NULL,
  read_at DATETIME NULL,
  metadata_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notifications_user_read_created (user_id, is_read, created_at),
  KEY idx_notifications_status_created (status, created_at),
  CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  activity_type VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  metadata_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_activity_logs_user_time (user_id, created_at),
  KEY idx_activity_logs_entity_time (entity_type, entity_id, created_at),
  KEY idx_activity_logs_type_time (activity_type, created_at),
  CONSTRAINT fk_activity_logs_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_user_id BIGINT UNSIGNED NULL,
  actor_role VARCHAR(50) NULL,
  action VARCHAR(100) NOT NULL,
  table_name VARCHAR(100) NOT NULL,
  record_id VARCHAR(80) NOT NULL,
  before_data_json JSON NULL,
  after_data_json JSON NULL,
  request_id VARCHAR(80) NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_logs_actor_time (actor_user_id, created_at),
  KEY idx_audit_logs_table_record_time (table_name, record_id, created_at),
  KEY idx_audit_logs_action_time (action, created_at),
  CONSTRAINT fk_audit_logs_actor
    FOREIGN KEY (actor_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- 8) Advanced Intelligence / Lifecycle / Docs
-- =========================
CREATE TABLE IF NOT EXISTS matching_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  run_type ENUM('lead_to_room', 'room_to_lead', 'batch_recompute') NOT NULL DEFAULT 'lead_to_room',
  algorithm_version VARCHAR(60) NOT NULL,
  trigger_source ENUM('manual', 'scheduler', 'event') NOT NULL DEFAULT 'manual',
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  status ENUM('running', 'success', 'failed', 'cancelled') NOT NULL DEFAULT 'running',
  total_candidates INT UNSIGNED NOT NULL DEFAULT 0,
  total_scored INT UNSIGNED NOT NULL DEFAULT 0,
  metadata_json JSON NULL,
  error_message VARCHAR(255) NULL,
  triggered_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_matching_runs_status_time (status, started_at),
  KEY idx_matching_runs_triggered_by_time (triggered_by, created_at),
  CONSTRAINT fk_matching_runs_triggered_by
    FOREIGN KEY (triggered_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_match_details (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_match_id BIGINT UNSIGNED NOT NULL,
  matching_run_id BIGINT UNSIGNED NULL,
  algorithm_version VARCHAR(60) NOT NULL,
  confidence_score DECIMAL(5,2) NULL,
  recommendation_label ENUM('strong_fit', 'fit', 'borderline', 'not_fit') NOT NULL DEFAULT 'fit',
  score_breakdown_json JSON NULL,
  explanation TEXT NULL,
  calculated_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_lead_match_details_match_run (lead_match_id, matching_run_id),
  KEY idx_lead_match_details_run_confidence (matching_run_id, confidence_score),
  KEY idx_lead_match_details_recommendation (recommendation_label, calculated_at),
  CONSTRAINT fk_lead_match_details_match
    FOREIGN KEY (lead_match_id) REFERENCES lead_matches(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_lead_match_details_run
    FOREIGN KEY (matching_run_id) REFERENCES matching_runs(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenant_timeline_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_user_id BIGINT UNSIGNED NOT NULL,
  room_id BIGINT UNSIGNED NULL,
  room_tenant_id BIGINT UNSIGNED NULL,
  contract_id BIGINT UNSIGNED NULL,
  invoice_id BIGINT UNSIGNED NULL,
  payment_id BIGINT UNSIGNED NULL,
  maintenance_ticket_id BIGINT UNSIGNED NULL,
  event_type ENUM('move_in', 'invoice_issued', 'invoice_paid', 'payment_received', 'maintenance_opened', 'maintenance_resolved', 'move_out', 'contract_signed', 'contract_ended', 'note') NOT NULL,
  event_title VARCHAR(180) NOT NULL,
  event_description TEXT NULL,
  event_at DATETIME NOT NULL,
  source_type VARCHAR(60) NULL,
  source_id BIGINT UNSIGNED NULL,
  visibility ENUM('tenant_portal', 'internal_only', 'both') NOT NULL DEFAULT 'both',
  metadata_json JSON NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_tenant_timeline_tenant_time (tenant_user_id, event_at),
  KEY idx_tenant_timeline_event_type_time (event_type, event_at),
  KEY idx_tenant_timeline_room_time (room_id, event_at),
  CONSTRAINT fk_tenant_timeline_tenant
    FOREIGN KEY (tenant_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_tenant_timeline_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_tenant_timeline_room_tenant
    FOREIGN KEY (room_tenant_id) REFERENCES room_tenants(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_tenant_timeline_contract
    FOREIGN KEY (contract_id) REFERENCES contracts(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_tenant_timeline_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_tenant_timeline_payment
    FOREIGN KEY (payment_id) REFERENCES payments(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_tenant_timeline_ticket
    FOREIGN KEY (maintenance_ticket_id) REFERENCES maintenance_tickets(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_tenant_timeline_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_handovers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  handover_code VARCHAR(60) NOT NULL,
  room_id BIGINT UNSIGNED NOT NULL,
  contract_id BIGINT UNSIGNED NULL,
  room_tenant_id BIGINT UNSIGNED NULL,
  handover_type ENUM('move_in', 'move_out', 'inspection') NOT NULL,
  status ENUM('draft', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
  handover_at DATETIME NOT NULL,
  handed_over_by BIGINT UNSIGNED NULL,
  received_by BIGINT UNSIGNED NULL,
  note TEXT NULL,
  metadata_json JSON NULL,
  completed_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_room_handovers_code (handover_code),
  KEY idx_room_handovers_room_type_time (room_id, handover_type, handover_at),
  KEY idx_room_handovers_contract_status (contract_id, status),
  KEY idx_room_handovers_room_tenant_status (room_tenant_id, status),
  CONSTRAINT fk_room_handovers_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_room_handovers_contract
    FOREIGN KEY (contract_id) REFERENCES contracts(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_room_handovers_room_tenant
    FOREIGN KEY (room_tenant_id) REFERENCES room_tenants(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_room_handovers_handed_over_by
    FOREIGN KEY (handed_over_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_room_handovers_received_by
    FOREIGN KEY (received_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_room_handovers_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_room_handovers_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_handover_checklist_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  handover_id BIGINT UNSIGNED NOT NULL,
  item_code VARCHAR(80) NULL,
  item_name VARCHAR(180) NOT NULL,
  category VARCHAR(100) NULL,
  check_phase ENUM('before_move_in', 'before_move_out', 'after_move_out') NOT NULL DEFAULT 'before_move_in',
  expected_condition VARCHAR(120) NULL,
  actual_condition VARCHAR(120) NULL,
  status ENUM('good', 'needs_repair', 'missing', 'broken', 'not_applicable') NOT NULL DEFAULT 'good',
  quantity_expected DECIMAL(10,2) NULL,
  quantity_actual DECIMAL(10,2) NULL,
  deduction_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  checked_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_room_handover_checklist_handover_order (handover_id, sort_order),
  KEY idx_room_handover_checklist_status (status, check_phase),
  CONSTRAINT fk_room_handover_checklist_handover
    FOREIGN KEY (handover_id) REFERENCES room_handovers(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_room_handover_checklist_checked_by
    FOREIGN KEY (checked_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_handover_images (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  handover_id BIGINT UNSIGNED NOT NULL,
  checklist_item_id BIGINT UNSIGNED NULL,
  image_phase ENUM('before_move_in', 'during_stay', 'before_move_out', 'after_move_out') NOT NULL DEFAULT 'before_move_in',
  area_label VARCHAR(120) NULL,
  file_name VARCHAR(255) NOT NULL,
  file_url VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NULL,
  file_size BIGINT UNSIGNED NULL,
  captured_at DATETIME NULL,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_room_handover_images_handover_phase (handover_id, image_phase),
  KEY idx_room_handover_images_checklist_item (checklist_item_id),
  CONSTRAINT fk_room_handover_images_handover
    FOREIGN KEY (handover_id) REFERENCES room_handovers(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_room_handover_images_checklist_item
    FOREIGN KEY (checklist_item_id) REFERENCES room_handover_checklist_items(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_room_handover_images_uploaded_by
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_exports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  export_code VARCHAR(60) NOT NULL,
  document_type ENUM('contract', 'invoice', 'handover_report') NOT NULL,
  contract_id BIGINT UNSIGNED NULL,
  invoice_id BIGINT UNSIGNED NULL,
  room_handover_id BIGINT UNSIGNED NULL,
  status ENUM('queued', 'processing', 'success', 'failed', 'expired') NOT NULL DEFAULT 'queued',
  template_name VARCHAR(120) NULL,
  language_code VARCHAR(10) NOT NULL DEFAULT 'vi',
  file_name VARCHAR(255) NULL,
  file_url VARCHAR(255) NULL,
  mime_type VARCHAR(100) NULL,
  file_size BIGINT UNSIGNED NULL,
  checksum_sha256 CHAR(64) NULL,
  error_message VARCHAR(255) NULL,
  requested_by BIGINT UNSIGNED NULL,
  generated_at DATETIME NULL,
  expires_at DATETIME NULL,
  metadata_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_document_exports_code (export_code),
  KEY idx_document_exports_status_created (status, created_at),
  KEY idx_document_exports_type_generated (document_type, generated_at),
  CONSTRAINT fk_document_exports_contract
    FOREIGN KEY (contract_id) REFERENCES contracts(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_document_exports_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_document_exports_room_handover
    FOREIGN KEY (room_handover_id) REFERENCES room_handovers(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_document_exports_requested_by
    FOREIGN KEY (requested_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS internal_reputation_scores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_user_id BIGINT UNSIGNED NOT NULL,
  subject_role ENUM('tenant', 'landlord') NOT NULL,
  period_start DATE NULL,
  period_end DATE NULL,
  scoring_version VARCHAR(40) NOT NULL DEFAULT 'v1',
  payment_punctuality_score DECIMAL(5,2) NULL,
  maintenance_response_score DECIMAL(5,2) NULL,
  contract_compliance_score DECIMAL(5,2) NULL,
  communication_score DECIMAL(5,2) NULL,
  overall_score DECIMAL(5,2) NOT NULL,
  risk_level ENUM('low', 'medium', 'high', 'watchlist') NOT NULL DEFAULT 'low',
  is_internal_only TINYINT(1) NOT NULL DEFAULT 1,
  summary_note VARCHAR(255) NULL,
  calculated_by BIGINT UNSIGNED NULL,
  calculated_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_internal_reputation_period (subject_user_id, subject_role, period_start, period_end, scoring_version),
  KEY idx_internal_reputation_subject_score (subject_user_id, subject_role, overall_score),
  KEY idx_internal_reputation_role_risk (subject_role, risk_level),
  CONSTRAINT fk_internal_reputation_subject
    FOREIGN KEY (subject_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_internal_reputation_calculated_by
    FOREIGN KEY (calculated_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS internal_reputation_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  score_id BIGINT UNSIGNED NOT NULL,
  subject_user_id BIGINT UNSIGNED NOT NULL,
  factor_type ENUM('payment_on_time', 'payment_overdue', 'maintenance_fast_response', 'maintenance_slow_response', 'handover_damage', 'rule_violation', 'positive_feedback', 'manual_adjustment') NOT NULL,
  related_entity_type VARCHAR(60) NULL,
  related_entity_id BIGINT UNSIGNED NULL,
  factor_value DECIMAL(8,2) NOT NULL,
  weight DECIMAL(8,4) NOT NULL DEFAULT 1,
  impact_score DECIMAL(8,2) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_internal_reputation_events_score_time (score_id, created_at),
  KEY idx_internal_reputation_events_subject_time (subject_user_id, created_at),
  KEY idx_internal_reputation_events_factor_time (factor_type, created_at),
  CONSTRAINT fk_internal_reputation_events_score
    FOREIGN KEY (score_id) REFERENCES internal_reputation_scores(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_internal_reputation_events_subject
    FOREIGN KEY (subject_user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_internal_reputation_events_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- 9) Seed Data
-- =========================
INSERT INTO roles (code, name, description, is_system)
VALUES
  ('admin', 'Administrator', 'System administrator', 1),
  ('landlord', 'Landlord', 'Property owner / operator', 1),
  ('staff', 'Staff', 'Internal operator under landlord/admin scope', 1),
  ('tenant', 'Tenant', 'Renter / lead owner', 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  is_system = VALUES(is_system);
