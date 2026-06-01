<?php
/**
 * DBU Blood Bank — OOP Database Auto-Initialization (Task 1)
 * Auto-detects MySQL (XAMPP/production) vs SQLite (Replit demo)
 * University : Debre Berhan University
 * Developers : Feminist Group — DBU
 */

// ─── Database Credentials (XAMPP / Production) ────────────────
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dbu_blood_bank');
define('DB_SOCK', '/tmp/dbu-mysql.sock');

// SQLite fallback path (used when MySQL is unavailable)
define('SQLITE_PATH', __DIR__ . '/../dbu_blood_bank.db');

// ─── Session Bootstrap ────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load SQLite compat layer (only activates when MySQL unavailable)
require_once __DIR__ . '/db_compat.php';

// ─── PHPMailer SMTP Config & Mailer Helper ────────────────────
if (file_exists(__DIR__ . '/mail_config.php')) require_once __DIR__ . '/mail_config.php';
if (file_exists(__DIR__ . '/mailer.php'))      require_once __DIR__ . '/mailer.php';

// ═════════════════════════════════════════════════════════════
// OOP Database Manager — Task 1: Auto-Initialization
// ═════════════════════════════════════════════════════════════
class BloodBankDB {

    private static ?BloodBankDB $instance = null;
    public mysqli|MySQLiCompat $conn;
    private bool $usingSQLite = false;

    // ── Constructor: Connect + Auto-Initialize ──────────────
    private function __construct() {
        // Try native MySQL first (works on XAMPP, production)
        $mysql = $this->tryMySQLConnection();

        if ($mysql !== null) {
            $this->conn = $mysql;
        } else {
            // Fall back to SQLite3 compat layer
            $this->usingSQLite = true;
            $dbDir = dirname(SQLITE_PATH);
            if (!is_dir($dbDir)) mkdir($dbDir, 0755, true);
            $lite = new MySQLiCompat(SQLITE_PATH);
            if (!empty($lite->connect_error)) {
                $this->fatalError('Cannot open SQLite database: ' . $lite->connect_error);
            }
            $this->conn = $lite;
        }

        // Create all tables
        $this->createTables();

        // Seed default data
        $this->seedData();
    }

    private function tryMySQLConnection(): ?mysqli {
        // PHP 8.x throws mysqli_sql_exception on connect failure — must catch
        // Suppress warnings with @ AND catch exceptions for both error modes
        mysqli_report(MYSQLI_REPORT_OFF);
        $prev = set_error_handler(fn() => true); // silence warnings during connect
        try {
            $m = @new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
            if ($m->connect_errno) throw new Exception($m->connect_error);
        } catch (Throwable $e) {
            // Try Unix socket fallback
            try {
                $m = @new mysqli(null, DB_USER, DB_PASS, '', null, DB_SOCK);
                if ($m->connect_errno) { restore_error_handler(); return null; }
            } catch (Throwable $e2) {
                restore_error_handler();
                return null; // No MySQL available → SQLite fallback
            }
        }
        restore_error_handler();

        // Create & select database
        $m->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $m->select_db(DB_NAME);
        $m->set_charset('utf8mb4');
        return $m;
    }

    // ── Singleton Access ──────────────────────────────────────
    public static function getInstance(): BloodBankDB {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isUsingSQLite(): bool { return $this->usingSQLite; }

    // ── Task 1: Table Creation ────────────────────────────────
    private function createTables(): void {
        $isLite = $this->usingSQLite;

        $tables = [];

        // Admin Table
        $tables[] = "CREATE TABLE IF NOT EXISTS admins (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            email       VARCHAR(191) NOT NULL UNIQUE,
            password    VARCHAR(255) NOT NULL,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        // Donors Table — blood_type VARCHAR supports 'Unknown' (Task 3)
        $tables[] = "CREATE TABLE IF NOT EXISTS donors (
            id                  INT AUTO_INCREMENT PRIMARY KEY,
            name                VARCHAR(120) NOT NULL,
            email               VARCHAR(191) NOT NULL UNIQUE,
            blood_type          VARCHAR(10) NOT NULL DEFAULT 'Unknown',
            phone               VARCHAR(30) NOT NULL,
            dbu_id              VARCHAR(50) NOT NULL,
            password            VARCHAR(255) NOT NULL,
            profile_photo       VARCHAR(255) DEFAULT NULL,
            last_donation_date  DATE DEFAULT NULL,
            planned_donation_date DATE DEFAULT NULL,
            is_active           TINYINT(1) NOT NULL DEFAULT 1,
            status              VARCHAR(20) NOT NULL DEFAULT 'active',
            eligibility_status  VARCHAR(20) NOT NULL DEFAULT 'eligible',
            created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        // Blood Requests — includes units_requested for Task 2
        $tables[] = "CREATE TABLE IF NOT EXISTS blood_requests (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            patient_name    VARCHAR(120) NOT NULL DEFAULT '',
            blood_type      VARCHAR(10) NOT NULL,
            hospital        VARCHAR(150) NOT NULL DEFAULT '',
            requester_name  VARCHAR(120) NOT NULL DEFAULT '',
            requester_phone VARCHAR(30) NOT NULL DEFAULT '',
            urgency         VARCHAR(20) NOT NULL DEFAULT 'normal',
            units_requested INT NOT NULL DEFAULT 1,
            status          VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        // Blood Stock Table — Task 2
        $tables[] = "CREATE TABLE IF NOT EXISTS blood_stock (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            blood_type  VARCHAR(10) NOT NULL UNIQUE,
            units       INT NOT NULL DEFAULT 0,
            status      VARCHAR(20) NOT NULL DEFAULT 'unavailable',
            updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        // Test Results — Task 4
        $tables[] = "CREATE TABLE IF NOT EXISTS test_results (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            donor_id          INT NOT NULL,
            screening_date    VARCHAR(20) NOT NULL DEFAULT '',
            hiv_status        VARCHAR(10) NOT NULL DEFAULT 'negative',
            hepatitis_status  VARCHAR(10) NOT NULL DEFAULT 'negative',
            syphilis_status   VARCHAR(10) NOT NULL DEFAULT 'negative',
            overall_outcome   VARCHAR(10) NOT NULL DEFAULT 'safe',
            notes             TEXT DEFAULT NULL,
            recorded_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        // Notifications Log — Tasks 3, 4, 5 (email_sent tracks real SMTP dispatch)
        $tables[] = "CREATE TABLE IF NOT EXISTS notifications_log (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            donor_id    INT DEFAULT NULL,
            recipient   VARCHAR(191) NOT NULL,
            subject     VARCHAR(255) NOT NULL,
            body        TEXT NOT NULL,
            type        VARCHAR(50) NOT NULL DEFAULT 'general',
            email_sent  TINYINT(1) NOT NULL DEFAULT 0,
            sent_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        // Donation Photos
        $tables[] = "CREATE TABLE IF NOT EXISTS donation_photos (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            donor_id    INT DEFAULT NULL,
            donor_name  VARCHAR(120) NOT NULL DEFAULT 'Anonymous',
            photo_path  VARCHAR(255) NOT NULL,
            caption     TEXT DEFAULT NULL,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        // Contact Messages
        $tables[] = "CREATE TABLE IF NOT EXISTS contact_messages (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(120) NOT NULL,
            email       VARCHAR(191) NOT NULL,
            subject     VARCHAR(200) NOT NULL DEFAULT '',
            message     TEXT NOT NULL,
            status      VARCHAR(20) NOT NULL DEFAULT 'new',
            admin_reply TEXT DEFAULT NULL,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        // Hospitals
        $tables[] = "CREATE TABLE IF NOT EXISTS hospitals (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            hospital_name   VARCHAR(200) NOT NULL,
            email           VARCHAR(191) NOT NULL UNIQUE,
            password        VARCHAR(255) NOT NULL,
            primary_contact VARCHAR(30) NOT NULL DEFAULT '',
            address         VARCHAR(255) DEFAULT NULL,
            is_active       TINYINT(1) NOT NULL DEFAULT 1,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        // System Settings
        $tables[] = "CREATE TABLE IF NOT EXISTS system_settings (
            setting_name  VARCHAR(100) NOT NULL PRIMARY KEY,
            setting_value TEXT DEFAULT NULL,
            updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        foreach ($tables as $sql) {
            if (!$this->conn->query($sql)) {
                error_log('[DBU-DB] Table creation failed: ' . ($this->conn->error ?? '?') . ' | SQL: ' . substr($sql, 0, 120));
            }
        }

        // Add columns to existing tables gracefully
        $alters = [
            "ALTER TABLE donors ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'active'",
            "ALTER TABLE donors ADD COLUMN IF NOT EXISTS eligibility_status VARCHAR(20) NOT NULL DEFAULT 'eligible'",
            "ALTER TABLE donors ADD COLUMN IF NOT EXISTS planned_donation_date DATE DEFAULT NULL",
            "ALTER TABLE blood_requests ADD COLUMN IF NOT EXISTS units_requested INT NOT NULL DEFAULT 1",
            "ALTER TABLE notifications_log ADD COLUMN IF NOT EXISTS email_sent TINYINT(1) NOT NULL DEFAULT 0",
        ];
        foreach ($alters as $a) {
            // For SQLite, IF NOT EXISTS is not supported — use try/catch
            try {
                @$this->conn->query($a);
            } catch (Throwable $e) { /* column already exists, safe to ignore */ }
        }
    }

    // ── Seed Default Data ─────────────────────────────────────
    private function seedData(): void {
        // Default admin account
        $chk = $this->conn->query("SELECT id FROM admins LIMIT 1");
        if ($chk && $chk->num_rows === 0) {
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $e    = 'admin@dbu.edu.et';
            $stmt = $this->conn->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
            $stmt->bind_param('ss', $e, $hash);
            $stmt->execute();
            $stmt->close();
        }

        // System settings
        $chk2 = $this->conn->query("SELECT COUNT(*) AS c FROM system_settings");
        if ($chk2 && (int)($chk2->fetch_assoc()['c'] ?? 0) === 0) {
            $this->conn->query("INSERT INTO system_settings (setting_name, setting_value) VALUES ('logo_url', NULL)");
            $this->conn->query("INSERT INTO system_settings (setting_name, setting_value) VALUES ('site_name', 'DBU Blood Donation Management System')");
        }

        // Initial blood inventory
        $chk3 = $this->conn->query("SELECT COUNT(*) AS c FROM blood_stock");
        if ($chk3 && (int)($chk3->fetch_assoc()['c'] ?? 0) === 0) {
            $initial = [
                ['A+',45,'available'],['A-',8,'low'],
                ['B+',32,'available'],['B-',3,'critical'],
                ['AB+',18,'available'],['AB-',2,'critical'],
                ['O+',60,'available'],['O-',5,'low'],
            ];
            foreach ($initial as [$bt, $u, $s]) {
                $stmt = $this->conn->prepare("INSERT INTO blood_stock (blood_type, units, status) VALUES (?, ?, ?)");
                $stmt->bind_param('sis', $bt, $u, $s);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // ── Tasks 3, 4, 5: Log + Real SMTP Email Dispatch ────────
    public function logNotification(int $donor_id, string $recipient, string $subject, string $body, string $type = 'general', string $recipientName = ''): bool {
        // Attempt real SMTP send if PHPMailer is configured
        $email_sent = 0;
        if (function_exists('sendEmail')) {
            $email_sent = sendEmail($recipient, $subject, $body, $recipientName) ? 1 : 0;
        }

        // Audit trail: always insert regardless of email outcome
        $stmt = $this->conn->prepare(
            "INSERT INTO notifications_log (donor_id, recipient, subject, body, type, email_sent) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issssi', $donor_id, $recipient, $subject, $body, $type, $email_sent);
        $stmt->execute();
        $stmt->close();

        return (bool)$email_sent;
    }

    // ── Task 5: Find Non-Donors ───────────────────────────────
    public function getNonDonors(): array {
        $res = $this->conn->query(
            "SELECT d.id, d.name, d.email, d.planned_donation_date
             FROM donors d
             WHERE d.is_active = 1
               AND d.last_donation_date IS NULL
               AND NOT EXISTS (SELECT 1 FROM test_results tr WHERE tr.donor_id = d.id)
             ORDER BY d.created_at ASC"
        );
        return ($res instanceof MySQLiResult || $res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    // ── Task 5: Dispatch Non-Donor Reminders via SMTP ─────────
    public function sendNonDonorReminders(): array {
        $nonDonors = $this->getNonDonors();
        $sent = 0;
        foreach ($nonDonors as $d) {
            $sub  = 'DBU Blood Bank — A Life is Waiting for Your Donation';
            $planned_str = !empty($d['planned_donation_date']) ? date('d M Y', strtotime($d['planned_donation_date'])) : 'your scheduled date';
            $body = "Dear {$d['name']},\n\nWe noticed you registered on our system and planned to donate blood on {$planned_str}. However, you registered previously stating you would donate on that day, but you did not donate (በዚህ ቀን እለግሳለሁ ብለህ ባለፈው ተመዝግበህ ነበር ነገር ግን አልለገስክም/አልመጣህም)!\n\nWe kindly encourage you to visit the campus clinic laboratory during operational hours to make your voluntary, life-saving blood donation today. Local hospitals are in high demand of blood units.\n\nEvery drop counts — your single donation can save up to 3 lives.\n\nWith gratitude,\nDBU Blood Bank Team\nDebre Berhan University";
            $dispatched = $this->logNotification($d['id'], $d['email'], $sub, $body, 'reminder', $d['name']);
            if ($dispatched) $sent++;
        }
        return ['total' => count($nonDonors), 'emailed' => $sent];
    }

    // ── Task 2: Approve Request and Deduct Stock ────
    public function approveRequestOnly(int $request_id): array {
        // Fetch request details
        $stmt = $this->conn->prepare("SELECT blood_type, units_requested, status FROM blood_requests WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
        $req = $stmt->get_result()?->fetch_assoc();
        $stmt->close();
        
        if (!$req || $req['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Request not found or already processed.'];
        }

        $blood_type = $req['blood_type'];
        $units = (int)$req['units_requested'];

        // Fetch current stock
        $stmt_s = $this->conn->prepare("SELECT units FROM blood_stock WHERE blood_type = ? LIMIT 1");
        $stmt_s->bind_param('s', $blood_type);
        $stmt_s->execute();
        $curr = $stmt_s->get_result()?->fetch_assoc();
        $stmt_s->close();
        
        $stock_available = (int)($curr['units'] ?? 0);
        
        if ($stock_available < $units) {
             return ['success' => false, 'message' => "Insufficient blood stock. {$stock_available} units available, {$units} requested."];
        }

        // Deduct from stock
        $new_units = $stock_available - $units;
        $new_status = $new_units === 0 ? 'unavailable' : ($new_units <= 5 ? 'critical' : ($new_units <= 15 ? 'low' : 'available'));
        
        $stmt_deduct = $this->conn->prepare("UPDATE blood_stock SET units = ?, status = ?, updated_at = NOW() WHERE blood_type = ?");
        $stmt_deduct->bind_param('iss', $new_units, $new_status, $blood_type);
        $stmt_deduct->execute();
        $stmt_deduct->close();

        // Update request status
        $stmt_app = $this->conn->prepare("UPDATE blood_requests SET status = 'approved' WHERE id = ?");
        $stmt_app->bind_param('i', $request_id);
        if ($stmt_app->execute()) {
            $stmt_app->close();
            return ['success' => true, 'message' => 'Request approved successfully and stock deducted.'];
        }
        $stmt_app->close();
        return ['success' => false, 'message' => 'Failed to approve request.'];
    }

    // ── Task 2: Return Stock on Deleted Request ───────────────
    public function returnStockOnDeletedRequest(int $request_id): void {
        // Pending requests no longer deduct stock, so deleting them shouldn't return stock.
        // We only return stock if the status was 'approved' and it was deleted before transport (optional logic).
        // For now, doing nothing to prevent artificial stock inflation.
    }

    // ── Task 4: Add Stock on Safe Donation / Re-added Requests ────
    public function addStockOnSafeDonation(string $blood_type, int $units = 1): void {
        $allowed = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
        if (!in_array($blood_type, $allowed)) return;

        // Fetch current units
        $stmt_s = $this->conn->prepare("SELECT units FROM blood_stock WHERE blood_type = ? LIMIT 1");
        $stmt_s->bind_param('s', $blood_type);
        $stmt_s->execute();
        $curr = $stmt_s->get_result()?->fetch_assoc();
        $stmt_s->close();

        $new_units = (int)($curr['units'] ?? 0) + $units;
        $new_status = $new_units === 0 ? 'unavailable' : ($new_units <= 5 ? 'critical' : ($new_units <= 15 ? 'low' : 'available'));

        $stmt_u = $this->conn->prepare("UPDATE blood_stock SET units = ?, status = ?, updated_at = NOW() WHERE blood_type = ?");
        $stmt_u->bind_param('iss', $new_units, $new_status, $blood_type);
        $stmt_u->execute();
        $stmt_u->close();
    }

    // ── Task 2: Approve Request with Stock Deduction (Legacy Compatibility) ─────────
    public function approveRequestWithDeduction(int $request_id): array {
        return $this->approveRequestOnly($request_id);
    }

    // ── Fatal Error Display ───────────────────────────────────
    private function fatalError(string $msg): never {
        $safe = htmlspecialchars($msg);
        die("<!DOCTYPE html><html><head><meta charset='utf-8'>
        <link rel='stylesheet' href='assets/css/bootstrap.min.css'>
        </head><body class='bg-light'><div class='container mt-5'>
        <div class='alert alert-danger shadow-sm rounded-3'>
          <h4>&#x26A0; Database Error</h4><p>{$safe}</p>
        </div></div></body></html>");
    }
}

// ─── Global Initialization ────────────────────────────────────
$db   = BloodBankDB::getInstance();
$conn = $db->conn;

// ─── Helper Functions ─────────────────────────────────────────
function is_logged_in(): bool { return isset($_SESSION['donor_id']); }
function is_admin(): bool     { return isset($_SESSION['admin_id']); }
function is_hospital(): bool  { return isset($_SESSION['hospital_id']); }

function require_login(): void    { if (!is_logged_in()) redirect('login.php'); }
function require_admin(): void    { if (!is_admin()) redirect('admin_login.php'); }
function require_hospital(): void { if (!is_hospital()) redirect('hospital_login.php'); }

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

function sanitize(mysqli|MySQLiCompat $conn, mixed $value): string {
    return $conn->real_escape_string(trim((string)$value));
}
?>
