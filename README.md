# clinic-system

## 🏥 SEDACOE Clinical & Master Store Information System## Enterprise Architecture & System Administration Manual (v2.0)
Welcome to the official deployment and operational documentation for the SEDACOE Clinic & Student Health Portal [INDEX]. This manual serves as an all-in-one guide for system administrators, developers, and technicians to configure, manage, deploy, and troubleshoot the clinic platform.
------------------------------
## 🗂️ 1. System Ecosystem & Architectural Design
The system is engineered as a lightweight, high-performance, decoupled Role-Based Medical Information System built using PHP (PDO), jQuery, and MySQL/MariaDB. It is strictly optimized to comply with advanced database environment rules (such as ONLY_FULL_GROUP_BY and strict SQL selection boundaries) [INDEX].
## 🔒 Core Structural Pillars

   1. Unified Dashboard Workspace (dashboard.php): A single, role-isolated central terminal page that changes its layout, data views, and color schemes dynamically on the fly based on the active user profile ($_SESSION['role']) [INDEX].
   2. Decoupled Pharmacy & Master Storage Vault: Separates the front-of-house dispensing shelves (pharmacy_store.php) from the main back-of-house medical storage warehouse depot (main_store.php), using a secure transaction request voucher pipeline (pharmacy_requisitions.php) [INDEX].
   3. Fintech Auditing & Financial Cash Flow Ledger (finance_ledger.php): Every patient invoice cleared at the cashier desk automatically triggers a background transaction audit sweep that logs data directly into an append-only cash flow ledger [INDEX].
   4. Immutable Append-Only Security Trail (system_activity_logs.php): Fully protected by native database interceptor triggers that completely block any UPDATE or DELETE commands on log tracks, keeping your system history secure [INDEX].

------------------------------
## 🔐 2. Database Schema Configuration (SQL Engine)
Run these complete, production-ready tables and schema migration paths directly inside your MySQL console window.
## 👤 Clinic Personnel Directory (Upgraded Schema)

CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `staff_id` VARCHAR(50) NOT NULL COMMENT 'System auto-generated unique chronological ID e.g., STF-2026-0001',
  `password` VARCHAR(255) NOT NULL COMMENT 'Secure bcrypt cryptographic password string hash',
  `full_name` VARCHAR(150) NOT NULL COMMENT 'Staff first and last legal names descriptor',
  `role` ENUM('Admin','Records','Doctor','Lab Tech','Nurse','Pharmacist','Cashier','Storekeeper') NOT NULL,
  `profile_image` VARCHAR(255) NULL DEFAULT 'default-avatar.png' COMMENT 'Stores the relative URL address to file storage',
  `status` ENUM('Active','Suspended') NOT NULL DEFAULT 'Active' COMMENT 'Allows instantaneous terminal access lockouts',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `idx_unique_staff_id` (`staff_id`) USING BTREE,
  INDEX `idx_staff_role_status` (`role`, `status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

## 📦 Main Medical Store Warehouse Depot (Bulk Vault)

CREATE TABLE `main_medical_store` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `drug_name` VARCHAR(150) NOT NULL COMMENT 'Commercial name of medication item',
  `quantity_in_warehouse` INT(11) NOT NULL DEFAULT '0' COMMENT 'Bulk counts stored inside main medical supply vault',
  `batch_number` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Tracks delivery manufacturing lot identifiers',
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `idx_unique_warehouse_drug` (`drug_name`) USING BTREE,
  INDEX `idx_warehouse_balances` (`quantity_in_warehouse`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

## 💊 Pharmacy Sub-Depot (Active Dispensing Shelf)

CREATE TABLE `pharmacy_store` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `drug_name` VARCHAR(150) NOT NULL,
  `cost_price` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
  `selling_price` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
  `quantity_in_store` INT(11) NOT NULL DEFAULT '0' COMMENT 'Stock balance available on front pharmacy shelves',
  `min_threshold_qty` INT(11) NOT NULL DEFAULT '20' COMMENT 'Triggers a low stock warning indicator',
  `expiry_date` DATE NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `idx_unique_drug_name` (`drug_name`) USING BTREE,
  INDEX `idx_stock_health` (`quantity_in_store`, `min_threshold_qty`, `expiry_date`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

## 📄 Pharmacy Requisitions Logistics Tracker

CREATE TABLE `pharmacy_requisitions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `drug_id` INT(11) NOT NULL COMMENT 'Links directly to the target pharmacy_store item row ID',
  `requested_qty` INT(11) NOT NULL COMMENT 'Number of units demanded from the main warehouse',
  `status` ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `requested_by` INT(11) NOT NULL COMMENT 'Maps to user ID of the pharmacist who sent the request',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_req_drug_id` (`drug_id`) USING BTREE,
  INDEX `idx_req_status` (`status`) USING BTREE,
  CONSTRAINT `fk_req_pharmacy_drug` FOREIGN KEY (`drug_id`) REFERENCES `pharmacy_store` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_req_issuing_staff` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

## 💳 Cash Flow Ledger Registry

CREATE TABLE `cash_flow_ledger` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `transaction_type` ENUM('Inflow','Outflow') NOT NULL,
  `category` ENUM('Consultation','Lab Fee','Drugs','Supplier Payment','Facility Expenses','Salaries') NOT NULL,
  `reference_id` INT(11) NULL DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
  `description` VARCHAR(255) NOT NULL,
  `recorded_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_trx_type` (`transaction_type`) USING BTREE,
  INDEX `idx_category` (`category`) USING BTREE,
  CONSTRAINT `fk_ledger_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

## 🛡️ System Immutable Activity Logs & Security Audit Trail

CREATE TABLE `system_activity_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `module` ENUM('Auth', 'Triage', 'Consultation', 'Laboratory', 'Pharmacy', 'Accounts', 'Admin') NOT NULL,
  `action_type` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL COMMENT 'Child-friendly audit note descriptor text string',
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '0.0.0.0',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_log_user_id` (`user_id`) USING BTREE,
  INDEX `idx_log_timestamp` (`created_at`) USING BTREE,
  CONSTRAINT `fk_logs_user_link` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

------------------------------
## 🛠️ 3. Immutable Append-Only Ledger Triggers (MySQL)
To make your security audit system completely tamper-proof, execute this database block with strict command delimiters. This completely blocks any attempt to UPDATE or DELETE logs [INDEX]:

DELIMITER $$
-- TRIGGER A: INTERCEPT AND KILL ANY ATTEMPTED RECORD UPDATE MODIFICATIONSCREATE TRIGGER `tg_prevent_activity_log_updates` 
BEFORE UPDATE ON `system_activity_logs`FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'Security Protocol Exception: Audit trail logs are completely immutable and cannot be updated.';END$$
-- TRIGGER B: INTERCEPT AND KILL ANY ATTEMPTED DATA PURGE DELETIONSCREATE TRIGGER `tg_prevent_activity_log_deletions` 
BEFORE DELETE ON `system_activity_logs`FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'Security Protocol Exception: Audit trail logs are completely immutable and cannot be deleted.';END$$

DELIMITER ;

------------------------------
## 🚀 4. Automated Cron Job Enrolment (Laragon Setup)
Follow these exact steps to run your background clinic automated backup engine every single minute.
## File addresses configuration paths map:

   1. PHP Script Address: C:\laragon\www\clinic\assets\backend\cron.php
   2. Windows Batch Engine Address: C:\laragon\www\cronical.bat
   3. Laragon Cronical Document config: C:\laragon\usr\cronical.dat

## Configuration Choice A: The Laragon Cronical Setup
Open your file at C:\laragon\usr\cronical.dat, scroll down to the bottom, and add this single line wrapped inside double quotation marks ("...") [INDEX]:

* * * * * "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" "C:\laragon\www\clinic\assets\backend\cron.php"

⚠️ Note: Always go to your Laragon dashboard interface panel, click Stop, and then click Start All to force the service to read your changes [INDEX].
## Configuration Choice B: The Clean Windows Task Scheduler Setup
If Laragon's background engine drops out, use the standard Windows Command Line driver directly.
Open your Command Prompt as Administrator and paste this code line [INDEX]:

schtasks /create /sc minute /mo 1 /tn "SedacoeClinicAutomaticBackup" /tr "C:\laragon\www\cronical.bat" /ru "SYSTEM"

------------------------------
## 🎨 5. Primary 4 Student Friendly Logging Guide
To keep things transparent and simple for review, all logs are automatically saved using easy-to-read terms that a Primary 4 student here in Ghana can easily understand. This avoids confusing technical jargon.
## 📝 Translation Style Reference Map

| Department Module | Tech Action Performed | Friendly Auditing Text Generated |
|---|---|---|
| Auth | Staff Login Authorization | "Kwame typed their secret password keys on the login screen and opened their computer dashboard to work as a Doctor." |
| Triage | Save Patient Vitals | "The nurse used the clinic tools to check how hot Abena's body is, how heavy they are, and how their blood is pumping at the front desk." |
| Consultation | Order Laboratory Tests | "The doctor wrote a request note for Kojo to go to the laboratory room to test their blood and check what is making them sick." |
| Laboratory | Compiling Scans & Values | "The laboratory scientist tested the blood sample for Efia, typed the findings answer inside the computer box, and sent their paper card back to the doctor's room waiting line." |
| Pharmacy | Dispense Drug Items Basket | "The pharmacist counted the drug tablets, put them inside small white envelopes, and handed over [Paracetamol, Amoxicillin] safely to Kwesi at the pharmacy window." |
| Accounts | Cash Invoice Settlement | "The cashier collected a total sum of GHS 50.00 from Yaa at the front desk counter and marked all their treatment paper bills as paid." |
| Admin | System Settings Overrides | "The big boss opened the master dashboard screen to look at charts showing all clinic money counts, sick children records, and top moving medicines." |

------------------------------
## 🔍 6. Technical Troubleshooting Checklist
Use these steps to quickly diagnose and fix common errors if your background automation encounters issues:
## 1. The Terminal Diagnostic Verification Test
If your automated backups stop running, open Command Prompt as Administrator and force-run your batch file [INDEX]:

C:\laragon\www\cronical.bat

Our added pause command will keep the screen from closing [INDEX]. Read the error text displayed to pinpoint the exact issue.
## 2. Error: Failed to open stream: No such file or directory

* The Issue: The relative folder paths (../../) broke because the script was executed from a different directory address.
* The Fix: Ensure your cron.php utilizes our absolute base directory framework path lookups ($baseProjectRootDirectory = dirname(__DIR__, 2) . '/';), forcing the system to map file paths accurately every time.

## 3. Error: Trying to access array offset on value of type null

* The Issue: The code tried to read data fields from an empty or broken row reference slot.
* The Fix: Protect your echo loops by using the modern PHP null-coalescing fallback spacer operators directly on the line throwing the exception [INDEX]:

echo htmlspecialchars($row['some_detail'] ?? 'No details found');


------------------------------
The comprehensive SEDACOE Clinical Suite deployment, schema, and security manual is fully locked and ready for operational reference [INDEX]!
Propose your next milestone or let me know if you would like to run optimization diagnostics on another system module!

