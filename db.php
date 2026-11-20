<?php
// Database layer: SQLite (a single file inside the project, no server needed).
// It exposes the same small API the pages already use ($conn->prepare(),
// bind_param(), execute(), get_result() ...), so page code stays unchanged.
//
// Tables and sample events are created automatically on first run.
// DB file location: DB_PATH env var, default ./data/eventify.sqlite

class DbResult {
    public $num_rows = 0;
    private $rows;
    private $i = 0;
    public function __construct(array $rows) { $this->rows = $rows; $this->num_rows = count($rows); }
    public function fetch_assoc() { return $this->rows[$this->i++] ?? null; }
    public function fetch_row()   { $r = $this->rows[$this->i++] ?? null; return $r === null ? null : array_values($r); }
    public function free() {}
}

class DbStmt {
    public $num_rows = 0;
    public $affected_rows = -1;
    private $db, $sql;
    private $params = [];
    private $rows = [];
    private $pos = 0;
    private $bound = [];

    public function __construct(SQLite3 $db, string $sql) { $this->db = $db; $this->sql = $sql; }

    public function bind_param(string $types, &...$vars) { $this->params = $vars; return true; }

    public function execute() {
        $st = $this->db->prepare($this->sql);
        foreach ($this->params as $k => $v) {
            $type = is_int($v) ? SQLITE3_INTEGER : (is_float($v) ? SQLITE3_FLOAT : (is_null($v) ? SQLITE3_NULL : SQLITE3_TEXT));
            $st->bindValue($k + 1, $v, $type);
        }
        $res = $st->execute();
        $this->rows = [];
        $this->pos = 0;
        if ($res->numColumns() > 0) {
            while ($r = $res->fetchArray(SQLITE3_ASSOC)) { $this->rows[] = $r; }
        }
        $res->finalize();
        $this->num_rows = count($this->rows);
        $this->affected_rows = $this->db->changes();
        return true;
    }

    public function get_result()   { return new DbResult($this->rows); }
    public function store_result() { return true; }
    public function bind_result(&...$vars) { $this->bound = []; foreach ($vars as $k => &$v) { $this->bound[$k] = &$v; } }
    public function fetch() {
        $r = $this->rows[$this->pos++] ?? null;
        if ($r === null) return false;
        foreach (array_values($r) as $k => $val) { if (isset($this->bound[$k]) || array_key_exists($k, $this->bound)) $this->bound[$k] = $val; }
        return true;
    }
    public function close() {}
}

class Db {
    public $connect_error = null;
    public $error = '';
    private $db;

    public function __construct(string $path) {
        $this->db = new SQLite3($path);
        $this->db->enableExceptions(true);
        $this->db->busyTimeout(5000);
        $this->db->exec('PRAGMA foreign_keys = ON');
    }
    public function raw(): SQLite3 { return $this->db; }
    public function set_charset($c) { return true; }

    // Translate the few MySQL-only bits used by the pages.
    private function sql(string $sql): string {
        $sql = str_replace('CURDATE()', "date('now', 'localtime')", $sql);
        return preg_replace('/\s+FOR\s+UPDATE\b/i', '', $sql);
    }
    public function prepare(string $sql) { return new DbStmt($this->db, $this->sql($sql)); }
    public function query(string $sql) {
        $res = $this->db->query($this->sql($sql));
        $rows = [];
        if ($res->numColumns() > 0) { while ($r = $res->fetchArray(SQLITE3_ASSOC)) $rows[] = $r; }
        return new DbResult($rows);
    }
    public function begin_transaction() { $this->db->exec('BEGIN IMMEDIATE'); }
    public function commit()   { $this->db->exec('COMMIT'); }
    public function rollback() { try { $this->db->exec('ROLLBACK'); } catch (Throwable $e) {} }
    public function close()    {}
}

const EVENTIFY_SCHEMA = "
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    category TEXT NOT NULL,
    event_date TEXT NOT NULL,
    event_time TEXT NOT NULL,
    venue TEXT NOT NULL,
    city TEXT NOT NULL,
    price REAL NOT NULL DEFAULT 0,
    available_tickets INTEGER NOT NULL DEFAULT 0 CHECK (available_tickets >= 0),
    image TEXT
);
CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_ref TEXT NOT NULL UNIQUE,
    event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    customer_name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    quantity INTEGER NOT NULL,
    total_amount REAL NOT NULL,
    booking_date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date);
CREATE INDEX IF NOT EXISTS idx_bookings_email ON bookings(email);
";

function eventify_seed(SQLite3 $db): void {
    // [title, description, category, days from today, time, venue, city, price, tickets, image]
    $events = [
        ['Sunburn Music Festival', 'Three stages, top DJs and a night of non-stop electronic music under the stars.', 'Music', 14, '18:00:00', 'Vagator Beach Arena', 'Goa', 2499, 200, 'music1'],
        ['Arijit Singh Live in Concert', 'An unforgettable evening of soulful Bollywood hits performed live.', 'Music', 21, '19:30:00', 'Jawaharlal Nehru Stadium', 'Delhi', 3999, 150, 'music2'],
        ['IPL Match: Mumbai vs Chennai', 'Watch the biggest rivalry in T20 cricket live from the stands.', 'Sports', 10, '19:30:00', 'Wankhede Stadium', 'Mumbai', 1800, 300, 'sports1'],
        ['City Marathon 2026', 'Run through the heart of the city. 5K, 10K and half-marathon categories.', 'Sports', 30, '05:30:00', 'Cubbon Park', 'Bengaluru', 599, 500, 'sports2'],
        ['Stand-Up Comedy Night', "A packed lineup of the country's funniest comedians. Laughter guaranteed.", 'Comedy', 7, '20:00:00', 'Canvas Laugh Club', 'Pune', 799, 80, 'comedy1'],
        ['Laugh Riot: Open Mic Special', 'Fresh voices, big laughs. Sign up or just sit back and enjoy the show.', 'Comedy', 18, '19:00:00', 'The Habitat', 'Hyderabad', 499, 60, 'comedy2'],
        ['Web Development Bootcamp', 'A hands-on one-day workshop covering HTML, CSS, JavaScript and PHP.', 'Workshop', 12, '10:00:00', 'Tech Hub Convention Center', 'Chennai', 999, 40, 'workshop1'],
        ['Startup Pitch & Networking Workshop', 'Learn to pitch your idea and meet founders, mentors and investors.', 'Workshop', 25, '11:00:00', 'WeWork Galaxy', 'Bengaluru', 1499, 50, 'workshop2'],
    ];
    $st = $db->prepare("INSERT INTO events (title, description, category, event_date, event_time, venue, city, price, available_tickets, image)
                        VALUES (?, ?, ?, date('now', 'localtime', ?), ?, ?, ?, ?, ?, ?)");
    foreach ($events as $e) {
        $st->bindValue(1, $e[0]); $st->bindValue(2, $e[1]); $st->bindValue(3, $e[2]);
        $st->bindValue(4, '+' . $e[3] . ' day'); $st->bindValue(5, $e[4]); $st->bindValue(6, $e[5]);
        $st->bindValue(7, $e[6]); $st->bindValue(8, $e[7]); $st->bindValue(9, $e[8]);
        $st->bindValue(10, 'https://picsum.photos/seed/eventify-' . $e[9] . '/800/500');
        $st->execute();
        $st->reset();
    }
}

try {
    $dbPath = getenv('DB_PATH') ?: (__DIR__ . '/data/eventify.sqlite');
    $dbDir  = dirname($dbPath);
    if (!is_dir($dbDir)) { @mkdir($dbDir, 0775, true); }

    $conn = new Db($dbPath);

    // First run: create tables and sample events.
    $raw = $conn->raw();
    $hasEvents = $raw->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='events'");
    $raw->exec(EVENTIFY_SCHEMA);
    if (!$hasEvents && getenv('SEED_SAMPLE_DATA') !== 'false') {
        eventify_seed($raw);
    }
} catch (Throwable $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    die("Database error. Make sure the 'data' folder is writable.");
}
