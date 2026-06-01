<?php
/**
 * DBU Blood Bank — MySQLi/SQLite3 Compatibility Layer
 * Provides a MySQLi-compatible interface backed by SQLite3.
 * On production XAMPP/MySQL servers, use the native mysqli $conn from config.php.
 * On Replit/sandboxed environments (no MySQL), this layer auto-activates.
 */

// ─── Result Object (mimics mysqli_result) ─────────────────────
class MySQLiResult {
    public int $num_rows;
    private array $data;
    private int $pos = 0;

    public function __construct(array $data) {
        $this->data   = $data;
        $this->num_rows = count($data);
    }

    public function fetch_assoc(): ?array {
        return isset($this->data[$this->pos]) ? $this->data[$this->pos++] : null;
    }

    public function fetch_all(int $_ = MYSQLI_ASSOC): array {
        return $this->data;
    }
}

// ─── Statement Object (mimics mysqli_stmt) ────────────────────
class MySQLiStatement {
    private SQLite3 $db;
    private string  $rawSql;
    private array   $bound = [];
    public  int     $num_rows = 0;
    private ?MySQLiResult $cachedResult = null;

    public function __construct(SQLite3 $db, string $sql) {
        $this->db     = $db;
        $this->rawSql = $sql;
    }

    /** Capture bound values (by-value copy, sufficient for PHP use patterns) */
    public function bind_param(string $types, mixed ...$vars): bool {
        $this->bound = $vars;
        return true;
    }

    public function execute(): bool {
        $sql = MySQLiCompat::convertSql($this->rawSql);
        try {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('[DBU-DB] Prepare error: ' . $this->db->lastErrorMsg() . ' SQL: ' . $sql);
                return false;
            }
            foreach ($this->bound as $i => $val) {
                $type = is_int($val) ? SQLITE3_INTEGER : (is_float($val) ? SQLITE3_FLOAT : SQLITE3_TEXT);
                $stmt->bindValue($i + 1, $val, $type);
            }
            $raw = $stmt->execute();
            if ($raw === false) {
                error_log('[DBU-DB] Execute error: ' . $this->db->lastErrorMsg());
                return false;
            }
            $verb = strtoupper(substr(ltrim($sql), 0, 6));
            if (in_array($verb, ['SELECT', 'PRAGMA', 'WITH   '])) {
                $rows = [];
                while ($row = $raw->fetchArray(SQLITE3_ASSOC)) {
                    $rows[] = $row;
                }
                $this->cachedResult = new MySQLiResult($rows);
                $this->num_rows     = count($rows);
            } else {
                $this->cachedResult = new MySQLiResult([]);
                $this->num_rows     = 0;
            }
            return true;
        } catch (Exception $e) {
            error_log('[DBU-DB] Statement exception: ' . $e->getMessage() . ' SQL: ' . $sql);
            return false;
        }
    }

    public function get_result(): ?MySQLiResult { return $this->cachedResult; }
    public function store_result(): void { /* num_rows already set in execute() */ }
    public function close(): void { }
}

// ─── Connection Object (mimics mysqli) ────────────────────────
class MySQLiCompat {
    private SQLite3 $db;
    public  ?string $connect_error = null;
    public  ?string $error         = null;
    public  int     $insert_id     = 0;
    private int     $_affected     = 0;

    public function __construct(string $dbPath) {
        try {
            $this->db = new SQLite3($dbPath);
            $this->db->enableExceptions(true);
            $this->db->exec("PRAGMA journal_mode = WAL; PRAGMA foreign_keys = ON;");
        } catch (Exception $e) {
            $this->connect_error = $e->getMessage();
        }
    }

    public function query(string $sql): MySQLiResult|bool {
        $sql = self::convertSql($sql);
        if (trim($sql) === '' || $sql === 'SELECT 1') return true;

        try {
            $result = $this->db->query($sql);
            $this->error      = null;
            $this->insert_id  = (int)$this->db->lastInsertRowID();

            $verb = strtoupper(substr(ltrim($sql), 0, 6));
            if (in_array($verb, ['SELECT', 'PRAGMA', 'WITH   ', 'EXPLAI'])) {
                $rows = [];
                if ($result !== false) {
                    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        $rows[] = $row;
                    }
                }
                return new MySQLiResult($rows);
            }
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            error_log('[DBU-DB] Query error: ' . $e->getMessage() . "\nSQL: " . substr($sql, 0, 500));
            return false;
        }
    }

    public function prepare(string $sql): MySQLiStatement {
        return new MySQLiStatement($this->db, $sql);
    }

    public function real_escape_string(string $val): string {
        return SQLite3::escapeString($val);
    }

    public function select_db(string $_): bool  { return true; }
    public function set_charset(string $_): bool { return true; }

    public function begin_transaction(): bool { $this->db->exec('BEGIN'); return true; }
    public function commit(): bool            { $this->db->exec('COMMIT'); return true; }
    public function rollback(): bool          { $this->db->exec('ROLLBACK'); return true; }

    // ── SQL Conversion: MySQL → SQLite ────────────────────────
    public static function convertSql(string $sql): string {
        $orig = trim($sql);

        // No-op conversions
        if (preg_match('/^\s*CREATE\s+DATABASE/i', $orig)) return 'SELECT 1';
        if (preg_match('/^\s*ALTER\s+TABLE\s+\S+\s+MODIFY\s+COLUMN/i', $orig)) return 'SELECT 1';
        if (preg_match('/^\s*ALTER\s+TABLE\s+\S+\s+CHANGE\s+/i', $orig)) return 'SELECT 1';

        // IF NOT EXISTS in ALTER TABLE (SQLite doesn't support it; wrap logic is outside)
        $sql = preg_replace('/\bADD\s+COLUMN\s+IF\s+NOT\s+EXISTS\b/i', 'ADD COLUMN', $sql);

        // Remove MySQL engine/charset clauses
        $sql = preg_replace('/\bENGINE\s*=\s*\w+/i', '', $sql);
        $sql = preg_replace('/\bDEFAULT\s+CHARSET\s*=\s*\w+/i', '', $sql);
        $sql = preg_replace('/\bCHARACTER\s+SET\s+\w+\b/i', '', $sql);
        $sql = preg_replace('/\bCOLLATE\s+[\w_]+/i', '', $sql);
        $sql = preg_replace('/\bON\s+UPDATE\s+CURRENT_TIMESTAMP\b/i', '', $sql);

        // Convert ENUM to TEXT
        $sql = preg_replace("/ENUM\s*\('[^']*'(?:\s*,\s*'[^']*')*\)/i", 'TEXT', $sql);

        // Data type conversions
        $sql = preg_replace('/\bTINYINT\s*\(\s*\d+\s*\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/\bSMALLINT\s*(?:\(\d+\))?/i', 'INTEGER', $sql);
        $sql = preg_replace('/\bBIGINT\s*(?:\(\d+\))?/i', 'INTEGER', $sql);
        $sql = preg_replace('/\bINT\s*\(\s*\d+\s*\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/\bMEDIUMINT\s*(?:\(\d+\))?/i', 'INTEGER', $sql);
        $sql = preg_replace('/\bVARCHAR\s*\(\s*\d+\s*\)/i', 'TEXT', $sql);
        $sql = preg_replace('/\bCHAR\s*\(\s*\d+\s*\)/i', 'TEXT', $sql);
        $sql = preg_replace('/\bLONGTEXT\b/i', 'TEXT', $sql);
        $sql = preg_replace('/\bMEDIUMTEXT\b/i', 'TEXT', $sql);

        // Handle AUTO_INCREMENT
        // "INTEGER PRIMARY KEY AUTOINCREMENT" is the SQLite pattern
        $sql = preg_replace('/\bINT\s+AUTO_INCREMENT\b/i', 'INTEGER', $sql);
        $sql = preg_replace('/\bAUTO_INCREMENT\b/i', '', $sql);

        // Convert backticks to double-quotes
        $sql = str_replace('`', '"', $sql);

        // Date/time functions
        $sql = preg_replace('/\bCURDATE\s*\(\s*\)/i', "date('now')", $sql);
        $sql = preg_replace('/\bNOW\s*\(\s*\)/i', "datetime('now')", $sql);

        // Handle DEFAULT (CURDATE()) in column definitions
        $sql = preg_replace('/DEFAULT\s+\(\s*CURDATE\s*\(\s*\)\s*\)/i', "DEFAULT (date('now'))", $sql);

        // ON DUPLICATE KEY UPDATE → just remove; use INSERT OR IGNORE instead
        // (Caller uses ON CONFLICT via the seed logic)
        $sql = preg_replace('/\bON\s+DUPLICATE\s+KEY\s+UPDATE\b.*/is', '', $sql);

        // Cleanup multiple spaces/newlines left by removals
        $sql = preg_replace('/[ \t]+,/', ',', $sql);
        $sql = preg_replace('/,\s*\)/', ')', $sql);
        $sql = preg_replace('/\n{3,}/', "\n\n", $sql);

        return trim($sql);
    }
}
?>
