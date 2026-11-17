<?php
// ============================================================================
// DJANGO GIRLS SIERRA LEONE - COMMUNITY & ORGANISATION MANAGEMENT PLATFORM
// DCOMP207 Web Programming Final Project (Continuation of Assignment 1: AUTH)
// ============================================================================

// ---- Autoload Composer Dependencies & Load .env ----
$autoloadPaths = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__, 2) . '/vendor/autoload.php'
];
foreach ($autoloadPaths as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
        break;
    }
}

// Load root .env
$envDirs = [dirname(__DIR__, 2), __DIR__];
foreach ($envDirs as $dir) {
    if (file_exists($dir . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable($dir);
        $dotenv->safeLoad();
        break;
    }
}

// Define Supabase Configuration Constants
if (!defined('SUPABASE_URL')) {
    define('SUPABASE_URL', $_ENV['PROJECT_URL'] ?? $_ENV['SUPABASE_URL'] ?? '');
}
if (!defined('SUPABASE_ANON_KEY')) {
    define('SUPABASE_ANON_KEY', $_ENV['ANON_KEY'] ?? $_ENV['SUPABASE_ANON_KEY'] ?? '');
}
if (!defined('SUPABASE_SERVICE_ROLE_KEY')) {
    define('SUPABASE_SERVICE_ROLE_KEY', $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '');
}
if (!defined('DATABASE_URL')) {
    define('DATABASE_URL', $_ENV['DATABASE_URL'] ?? '');
}
if (!defined('PUBLISHABLE_KEY')) {
    define('PUBLISHABLE_KEY', $_ENV['PUBLISHABLE_KEY'] ?? '');
}

/**
 * Return Guzzle client configured for Supabase REST and Auth endpoints.
 */
function supabase_client(?string $bearerToken = null): \GuzzleHttp\Client {
    $key = SUPABASE_ANON_KEY;
    $auth = $bearerToken ? 'Bearer ' . $bearerToken : 'Bearer ' . $key;

    return new \GuzzleHttp\Client([
        'base_uri' => rtrim(SUPABASE_URL, '/') . '/',
        'timeout'  => 10.0,
        'http_errors' => false,
        'headers'  => [
            'apikey'        => $key,
            'Authorization' => $auth,
            'Content-Type'  => 'application/json',
            'Prefer'        => 'return=representation'
        ]
    ]);
}

/**
 * Test the connection to Supabase.
 * Returns array with 'success' boolean, 'message' string, and details.
 */
function test_supabase_connection(): array {
    if (empty(SUPABASE_URL) || empty(SUPABASE_ANON_KEY)) {
        return [
            'success' => false,
            'message' => 'Database failed to connect',
            'details' => 'SUPABASE_URL or SUPABASE_ANON_KEY missing in .env'
        ];
    }

    try {
        $client = supabase_client();
        $res = $client->get('auth/v1/health');
        $code = $res->getStatusCode();

        if ($code === 200) {
            return [
                'success' => true,
                'message' => 'Database Connected Successfully',
                'details' => 'Connected to Supabase project at ' . SUPABASE_URL . ' (Auth health: ' . $code . ' OK)'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Database failed to connect',
                'details' => 'Supabase returned HTTP status code: ' . $code . ' - ' . (string)$res->getBody()
            ];
        }
    } catch (\Throwable $e) {
        return [
            'success' => false,
            'message' => 'Database failed to connect',
            'details' => $e->getMessage()
        ];
    }
}

// ---- STUDENT DETAILS FOR FOOTER ----
const STUDENT_FIRST = 'Deborah Juliet';
const STUDENT_LAST  = 'Moiforay';
const PROGRAM       = 'BSc (Hons) in Information Technology';
const SEMESTER      = 'Semester 5';
const STUDENT_ID    = 'LUCT-SL';
const ORG_NAME      = 'Django Girls Sierra Leone';

const INTERESTS   = ['Python', 'Django', 'HTML & CSS', 'JavaScript', 'Data science', 'UI/UX design'];
const LEVELS      = ['beginner', 'intermediate', 'advanced'];
const EVENT_TYPES = ['Event', 'Workshop', 'Training session'];

const PRESET_AVATARS = [
    'member-deborah.svg' => 'Vibrant Purple & Pink',
    'mentor-aminata.svg' => 'Emerald Forest Green',
    'mentor-fatmata.svg' => 'Django Girls Pink',
    'mentor-mariama.svg' => 'Ocean Blue & Gold',
];

// ---- Session Configuration (Secure Cookie Settings from Assignment 1) ----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---- Database: SQLite via PDO (Auto-created & Auto-seeded) ----
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dataDir = __DIR__ . '/data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . $dataDir . '/djangogirls.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'member',
            interests TEXT NOT NULL DEFAULT '',
            experience_level TEXT NOT NULL DEFAULT 'beginner',
            bio TEXT NOT NULL DEFAULT '',
            avatar TEXT,
            is_mentor INTEGER NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_login TEXT
        );
        CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            type TEXT NOT NULL DEFAULT 'Event',
            topic TEXT NOT NULL DEFAULT 'General',
            event_date TEXT NOT NULL,
            location TEXT NOT NULL,
            capacity INTEGER NOT NULL DEFAULT 30
        );
        CREATE TABLE IF NOT EXISTS event_registrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            status TEXT NOT NULL DEFAULT 'registered',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(event_id, user_id)
        );
        CREATE TABLE IF NOT EXISTS resources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            category TEXT NOT NULL,
            level TEXT NOT NULL DEFAULT 'beginner',
            url TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS resource_progress (
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            resource_id INTEGER NOT NULL REFERENCES resources(id) ON DELETE CASCADE,
            completed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, resource_id)
        );
        CREATE TABLE IF NOT EXISTS topics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            title TEXT NOT NULL,
            body TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS replies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            topic_id INTEGER NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            body TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS mentorships (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            mentee_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            mentor_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            message TEXT NOT NULL DEFAULT '',
            status TEXT NOT NULL DEFAULT 'pending',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(mentee_id, mentor_id)
        );");
        seed($pdo);
    }
    return $pdo;
}

// ---- Seed Initial Community Data (Preserves existing accounts while enriching platform) ----
function seed(PDO $pdo): void {
    // 1. Ensure Admin user exists
    if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO users (full_name,email,password_hash,role,interests,experience_level,bio,avatar,is_mentor) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([
                'Django Girls Admin',
                'admin@djangogirls.sl',
                password_hash('Admin@12345', PASSWORD_DEFAULT),
                'admin',
                'Python,Django,HTML & CSS',
                'advanced',
                'Lead Organiser & Community Administrator at Django Girls Sierra Leone in Freetown.',
                'mentor-aminata.svg',
                1
            ]);
    } else {
        // Give admin an avatar if missing
        $pdo->exec("UPDATE users SET avatar = 'mentor-aminata.svg' WHERE email = 'admin@djangogirls.sl' AND (avatar IS NULL OR avatar = '')");
    }

    // 2. Ensure Community Mentors exist so Mentorship & Member Directory are rich
    $mentors = [
        [
            'Aminata Kamara',
            'aminata@djangogirls.sl',
            'member',
            'Python,Django,Data science',
            'advanced',
            'Senior Python & Backend Engineer in Freetown. Passionate about mentoring women entering software development and data science in Sierra Leone.',
            'mentor-aminata.svg',
            1
        ],
        [
            'Fatmata Sesay',
            'fatmata@djangogirls.sl',
            'member',
            'HTML & CSS,JavaScript,UI/UX design',
            'advanced',
            'Frontend Web Developer & UI/UX Lead at Freetown Innovation Hub. Helping beginners design accessible, beautiful web interfaces.',
            'mentor-fatmata.svg',
            1
        ],
        [
            'Mariama Koroma',
            'mariama@djangogirls.sl',
            'member',
            'Python,Django,HTML & CSS',
            'intermediate',
            'Full-stack web developer and proud Django Girls Sierra Leone alumna. Happy to guide new members through their first web project!',
            'mentor-mariama.svg',
            1
        ],
    ];
    $chkUser = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $insUser = $pdo->prepare('INSERT INTO users (full_name,email,password_hash,role,interests,experience_level,bio,avatar,is_mentor) VALUES (?,?,?,?,?,?,?,?,?)');
    foreach ($mentors as $m) {
        $chkUser->execute([$m[1]]);
        if (!$chkUser->fetchColumn()) {
            $insUser->execute([
                $m[0], $m[1], password_hash('Mentor@12345', PASSWORD_DEFAULT),
                $m[2], $m[3], $m[4], $m[5], $m[6], $m[7]
            ]);
        }
    }

    // Ensure existing member accounts have a default avatar & interests if blank
    $pdo->exec("UPDATE users SET avatar = 'member-deborah.svg' WHERE role = 'member' AND (avatar IS NULL OR avatar = '')");
    $pdo->exec("UPDATE users SET interests = 'Python,Django,HTML & CSS,UI/UX design' WHERE role = 'member' AND (interests IS NULL OR interests = '')");
    $pdo->exec("UPDATE users SET bio = 'Web Programming student at Limkokwing University Sierra Leone building modern web applications.' WHERE role = 'member' AND (bio IS NULL OR bio = '')");

    // 3. Seed Events if empty
    if ((int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn() === 0) {
        $ev = $pdo->prepare('INSERT INTO events (title,description,type,topic,event_date,location,capacity) VALUES (?,?,?,?,?,?,?)');
        $events = [
            ['Intro to Python Workshop', 'Write your first Python programs hands-on. No prior coding experience needed; laptops and coaching provided.', 'Workshop', 'Python', '+6 days 10:00', 'Freetown, Limkokwing Lab 2', 30],
            ['Build Your First Django Site', 'Follow the official Django Girls tutorial step-by-step with a dedicated mentor beside you.', 'Workshop', 'Django', '+13 days 09:30', 'Freetown, Limkokwing Lab 1', 25],
            ['HTML & CSS UI/UX Training Session', 'Learn modern responsive layouts, CSS Grid, Flexbox, and visual design principles.', 'Training session', 'HTML & CSS', '+20 days 14:00', 'Online (Zoom & Freetown Hub)', 40],
            ['Women in Tech Sierra Leone Meetup & Career Talk', 'Network with fellow developers, showcase your projects, and hear from women leading tech in Salone.', 'Event', 'General', '+27 days 16:00', 'Freetown, Civic Innovation Hall', 60],
            ['JavaScript & Interactive Web Apps Lab', 'Make your web pages dynamic with modern JavaScript, DOM manipulation, and form validation.', 'Workshop', 'JavaScript', '+34 days 11:00', 'Freetown, Limkokwing Lab 3', 30],
            ['Freetown Web Programming Orientation Bootcamp', 'Kickoff orientation covering Assignment 1 authentication fundamentals and community onboarding.', 'Workshop', 'Python', '-10 days 10:00', 'Freetown, Limkokwing Lab 1', 35],
        ];
        foreach ($events as $e) {
            $e[4] = date('Y-m-d H:i', strtotime($e[4]));
            $ev->execute($e);
        }
    } elseif ((int)$pdo->query("SELECT COUNT(*) FROM events WHERE event_date < datetime('now')")->fetchColumn() === 0) {
        // Add one past workshop so participation history and attendance analytics are populated
        $pdo->prepare('INSERT INTO events (title,description,type,topic,event_date,location,capacity) VALUES (?,?,?,?,?,?,?)')
            ->execute([
                'Freetown Web Programming Orientation Bootcamp',
                'Kickoff orientation covering Assignment 1 authentication fundamentals and community onboarding.',
                'Workshop',
                'Python',
                date('Y-m-d H:i', strtotime('-10 days 10:00')),
                'Freetown, Limkokwing Lab 1',
                35
            ]);
    }

    // 4. Seed Learning Resources if empty
    if ((int)$pdo->query('SELECT COUNT(*) FROM resources')->fetchColumn() === 0) {
        $rs = $pdo->prepare('INSERT INTO resources (title,description,category,level,url) VALUES (?,?,?,?,?)');
        $resources = [
            ['Django Girls Official Tutorial', 'The step-by-step handbook: build and deploy your first blog application with Python and Django.', 'Django', 'beginner', 'https://tutorial.djangogirls.org/'],
            ['Python Official Beginner Guide', 'A guided tour of Python syntax, data structures, and functions from the official documentation.', 'Python', 'beginner', 'https://docs.python.org/3/tutorial/'],
            ['Django Documentation: Your First App', 'Build an interactive polls web application and master models, views, and templates.', 'Django', 'intermediate', 'https://docs.djangoproject.com/en/stable/intro/tutorial01/'],
            ['MDN Learn Web Development (HTML & CSS)', 'Structured interactive lessons covering semantic HTML5, responsive CSS, and layouts.', 'HTML & CSS', 'beginner', 'https://developer.mozilla.org/en-US/docs/Learn_web_development'],
            ['The Modern JavaScript Tutorial', 'From core fundamentals to DOM events and asynchronous programming with clear examples.', 'JavaScript', 'intermediate', 'https://javascript.info/'],
            ['Kaggle Learn: Intro to Data Science', 'Hands-on micro-courses in Python, Pandas, and data visualisation for beginners.', 'Data science', 'beginner', 'https://www.kaggle.com/learn'],
            ['10 Usability Heuristics for UI/UX Design', 'Essential rules of thumb for designing clean, user-friendly web interfaces.', 'UI/UX design', 'beginner', 'https://www.nngroup.com/articles/ten-usability-heuristics/'],
            ['PHP: The Right Way (Web Security & PDO)', 'Modern PHP reference covering PDO databases, password hashing, CSRF protection, and sessions.', 'JavaScript', 'advanced', 'https://phptherightway.com/'],
        ];
        foreach ($resources as $r) {
            $rs->execute($r);
        }
    }

    // 5. Seed Starter Forum Topics & Replies if empty
    if ((int)$pdo->query('SELECT COUNT(*) FROM topics')->fetchColumn() === 0) {
        $aminataId = (int)$pdo->query("SELECT id FROM users WHERE email = 'aminata@djangogirls.sl'")->fetchColumn() ?: 1;
        $fatmataId = (int)$pdo->query("SELECT id FROM users WHERE email = 'fatmata@djangogirls.sl'")->fetchColumn() ?: 1;
        $mariamaId = (int)$pdo->query("SELECT id FROM users WHERE email = 'mariama@djangogirls.sl'")->fetchColumn() ?: 1;

        $insTopic = $pdo->prepare('INSERT INTO topics (user_id, title, body, created_at) VALUES (?, ?, ?, ?)');
        $insReply = $pdo->prepare('INSERT INTO replies (topic_id, user_id, body, created_at) VALUES (?, ?, ?, ?)');

        $insTopic->execute([
            $aminataId,
            'Welcome to the Django Girls Sierra Leone Community Platform!',
            "Hello everyone and welcome to our upgraded community hub!\n\nBuilding on our Assignment 1 Authentication Portal, you can now track your learning resources, register for Freetown and online workshops, update your member profile with a picture, and connect with mentors.\n\nIntroduce yourself below and share what technology you are most excited to learn this semester!",
            date('Y-m-d H:i:s', strtotime('-5 days 11:20'))
        ]);
        $t1 = (int)$pdo->lastInsertId();
        $insReply->execute([$t1, $fatmataId, 'So excited to see everyone here! If anyone needs help with CSS Grid, Flexbox, or making your project UI look polished, feel free to reach out or request mentorship.', date('Y-m-d H:i:s', strtotime('-4 days 14:15'))]);
        $insReply->execute([$t1, $mariamaId, 'Welcome new members! Make sure to check out the Resources tab and mark tutorials as completed to watch your dashboard progress bar grow.', date('Y-m-d H:i:s', strtotime('-3 days 09:40'))]);

        $insTopic->execute([
            $fatmataId,
            'Tips for designing a clean, accessible UI/UX for your Web Programming project',
            "When building web applications, small visual details make a huge difference:\n1. Use consistent brand colours (like our Django Girls emerald green #0c4b33 and vibrant pink #e8457a).\n2. Give cards breathing room with padding and subtle shadows.\n3. Always pair form inputs with clear labels and helpful validation messages.\n\nWhat UI improvements are you working on this week?",
            date('Y-m-d H:i:s', strtotime('-2 days 16:05'))
        ]);
        $t2 = (int)$pdo->lastInsertId();
        $insReply->execute([$t2, $aminataId, 'Great tips Fatmata! Adding visual progress bars and clear status badges also helps users understand their dashboard at a glance.', date('Y-m-d H:i:s', strtotime('-1 day 18:30'))]);

        $insTopic->execute([
            $mariamaId,
            'Preparing for the upcoming Intro to Python & Django Workshop at Limkokwing Lab',
            "Our next hands-on coding workshop at Limkokwing Lab 2 is coming up soon! Remember to register on the Events page before seats fill up. We will cover Python fundamentals and how web servers process requests.",
            date('Y-m-d H:i:s', strtotime('-1 day 10:00'))
        ]);

        // Also seed a few sample registrations, resource completions, and mentorships for rich analytics
        $pastEvId = (int)$pdo->query("SELECT id FROM events WHERE event_date < datetime('now') LIMIT 1")->fetchColumn();
        $nextEvId = (int)$pdo->query("SELECT id FROM events WHERE event_date >= datetime('now') ORDER BY event_date LIMIT 1")->fetchColumn();
        $allUsers = $pdo->query('SELECT id FROM users')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($allUsers as $uid) {
            if ($pastEvId) {
                $pdo->prepare("INSERT OR IGNORE INTO event_registrations (event_id, user_id, status) VALUES (?, ?, 'attended')")->execute([$pastEvId, $uid]);
            }
            if ($nextEvId) {
                $pdo->prepare("INSERT OR IGNORE INTO event_registrations (event_id, user_id, status) VALUES (?, ?, 'registered')")->execute([$nextEvId, $uid]);
            }
            $pdo->prepare('INSERT OR IGNORE INTO resource_progress (user_id, resource_id) VALUES (?, 1)')->execute([$uid]);
            $pdo->prepare('INSERT OR IGNORE INTO resource_progress (user_id, resource_id) VALUES (?, 4)')->execute([$uid]);
        }
        if ($mariamaId && $aminataId && $mariamaId !== $aminataId) {
            $pdo->prepare("INSERT OR IGNORE INTO mentorships (mentee_id, mentor_id, message, status) VALUES (?, ?, ?, 'accepted')")
                ->execute([$mariamaId, $aminataId, 'Would love guidance on advanced Django deployment and database design!']);
        }
    }
}

// ---------- Helper Functions ----------
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function go(string $url): void { header('Location: ' . $url); exit; }
function fmt(string $d, string $f = 'j M Y, H:i'): string { return date($f, strtotime($d)); }
function now(): string { return date('Y-m-d H:i'); }

function is_logged_in(): bool { return isset($_SESSION['user_id']); }

function current_user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (isset($_SESSION['user_id'])) {
            $s = db()->prepare('SELECT * FROM users WHERE id = ? AND active = 1');
            $s->execute([$_SESSION['user_id']]);
            $u = $s->fetch() ?: null;
        }
    }
    return $u;
}

function require_login(string $base = ''): array {
    $u = current_user();
    if (!$u) { go($base . 'login.php'); }
    return $u;
}

function require_admin(): array {
    $u = require_login('../');
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        exit('Access denied: administrators only.');
    }
    return $u;
}

function redirect_if_logged_in(): void {
    if (current_user()) { go('dashboard.php'); }
}

function user_interests(array $u): array {
    return array_values(array_filter(explode(',', $u['interests'] ?? '')));
}

function clean_interests($posted): string {
    return implode(',', array_values(array_intersect(INTERESTS, is_array($posted) ? $posted : [])));
}

function avatar_html(array $u, string $base = '', string $class = ''): string {
    if (!empty($u['avatar'])) {
        return '<img class="avatar ' . e($class) . '" src="' . e($base) . 'uploads/avatars/' . e($u['avatar']) . '" alt="' . e($u['full_name']) . '">';
    }
    $initial = mb_strtoupper(mb_substr($u['full_name'] ?? 'U', 0, 1));
    return '<span class="avatar avatar-initial ' . e($class) . '" aria-hidden="true">' . e($initial) . '</span>';
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function post_guard(string $back): void {
    if (!csrf_check()) {
        flash('Your session expired. Please try again.');
        go($back);
    }
}

function flash(?string $msg = null): ?string {
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}
