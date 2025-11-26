<?php
// chatbot_api.php
// Robust JSON API for the eduflect chatbot with login-checks for course details.

ob_start(); // capture accidental output
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();

// unified JSON exit (captures any accidental HTML output)
function out_and_exit(array $arr, int $http_status = 200) {
    if (ob_get_length() !== false && ob_get_length() > 0) {
        $buf = ob_get_clean();
        if ($buf !== '') $arr['debug_html'] = $buf;
    }
    http_response_code($http_status);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// convert PHP error -> JSON
set_error_handler(function($severity, $message, $file, $line) {
    out_and_exit([
        'reply' => 'Server error (PHP).',
        'error' => $message,
        'file'  => $file,
        'line'  => $line
    ], 500);
});

// convert exception -> JSON
set_exception_handler(function($e) {
    out_and_exit([
        'reply' => 'Server exception.',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 500);
});

// Safe require of DB connect (if it emits warnings/errors, handlers above will capture)
require_once __DIR__ . '/Backend/db_connect.php'; // must provide $conn (mysqli)

// Read input
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if ($raw === false) out_and_exit(['reply' => 'No input received.'], 400);
$msg = trim($input['message'] ?? '');
if ($msg === '') out_and_exit(['reply' => 'Empty message.'], 400);

// helper simple out
function out(array $arr, int $http_status = 200) { out_and_exit($arr, $http_status); }

// normalize
$low = mb_strtolower($msg, 'UTF-8');

// categories map
$categories_map = [
    'development' => 'Development',
    'dev' => 'Development',
    'design' => 'Design',
    'marketing' => 'Marketing',
    'data' => 'Data Science',
    'datascience' => 'Data Science',
    'data science' => 'Data Science',
    'it' => 'IT & Software',
    'it & software' => 'IT & Software',
    'software' => 'IT & Software',
    'python' => 'Data Science',
    'web' => 'Development',
];

// detect category helper
function detect_category($msg_low, $map) {
    if (isset($map[$msg_low])) return $map[$msg_low];
    foreach ($map as $alias => $canon) {
        if (mb_strtolower($canon, 'UTF-8') === $msg_low) return $canon;
    }
    foreach ($map as $alias => $canon) {
        if (mb_stripos($msg_low, $alias, 0, 'UTF-8') !== false) return $canon;
    }
    return null;
}

// ----------------- Direct category selection (chip / typed category) -----------------
$directCategory = detect_category($low, $categories_map);
if ($directCategory !== null) {
    // REQUIRE LOGIN: only show course details to logged-in users
    if (empty($_SESSION['user_id'])) {
        out([
            'reply' => "Please log in to view course details.",
            'reply_html' =>
                '<div style="font-weight:700;margin-bottom:6px;">Please log in to see course details</div>'
                . '<div style="margin-bottom:8px;"><a href="login.php" target="_blank" style="color:#5624d0;font-weight:600;">Log in</a>'
                . ' or '
                . '<a href="signup.php" target="_blank" style="color:#5624d0;font-weight:600;">Sign up</a></div>'
                . '<div style="color:#666;font-size:13px;">After logging in, type the field again (e.g., Development) or click a suggestion.</div>',
            'await_field' => false
        ]);
    }

    // fetch courses for the selected category
    $selected = $directCategory;
    $stmt = $conn->prepare("SELECT id, title, thumbnail FROM courses WHERE category = ? ORDER BY id DESC LIMIT 6");
    if ($stmt === false) out(['reply' => "Server error preparing query."]);
    $stmt->bind_param("s", $selected);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($rows)) {
        unset($_SESSION['awaiting_field']);
        out([
            'reply' => "I couldn't find courses in \"{$selected}\" right now. Try another field or visit the Courses page.",
            'await_field' => false
        ]);
    }

    $links = [];
    foreach ($rows as $r) {
        $cid = (int)$r['id'];
        $title = htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8');
        $thumb = '';
        if (!empty($r['thumbnail'])) {
            $thumb = ' data-thumb="' . htmlspecialchars($r['thumbnail'], ENT_QUOTES, 'UTF-8') . '"';
        }
        $links[] = "<div style=\"margin-bottom:8px;\"><a href=\"view_course.php?id={$cid}\" target=\"_blank\" style=\"color:#5624d0;text-decoration:none;font-weight:600;\"{$thumb}>{$title}</a></div>";
    }

    $html = "<div><strong>Top " . count($rows) . " courses in " . htmlspecialchars($selected) . ":</strong><br>" . implode("", $links) . "<div style=\"margin-top:8px;color:#666;font-size:13px;\">Tip: click any course to open it.</div></div>";

    unset($_SESSION['awaiting_field']);
    out([
        'reply' => "Here are some courses I found:",
        'reply_html' => $html,
        'await_field' => false
    ]);
}

// ----------------- Awaiting field selection branch -----------------
if (!empty($_SESSION['awaiting_field'])) {
    // REQUIRE LOGIN before proceeding
    if (empty($_SESSION['user_id'])) {
        out([
            'reply' => "Please log in to view course suggestions.",
            'reply_html' =>
                '<div style="font-weight:700;margin-bottom:6px;">Please log in to get course suggestions</div>'
                . '<div style="margin-bottom:8px;"><a href="login.php" target="_blank" style="color:#5624d0;font-weight:600;">Log in</a>'
                . ' or '
                . '<a href="signup.php" target="_blank" style="color:#5624d0;font-weight:600;">Sign up</a></div>'
                . '<div style="color:#666;font-size:13px;">Once logged in, type the field (e.g., Development) or click a suggestion.</div>',
            'await_field' => false
        ]);
    }

    // try to map the user's reply to a known category
    $selected = detect_category($low, $categories_map);
    if (!$selected) {
        out([
            'reply' => "Sorry, I didn't catch the field. Which field are you interested in? For example: Development, Design, Marketing, Data Science, or IT & Software.",
            'await_field' => true,
            'quick_suggestions' => ['Development', 'Design', 'Marketing', 'Data Science', 'IT & Software']
        ]);
    }

    // fetch courses
    $stmt = $conn->prepare("SELECT id, title, thumbnail FROM courses WHERE category = ? ORDER BY id DESC LIMIT 6");
    if ($stmt === false) {
        $_SESSION['awaiting_field'] = false;
        out(['reply' => "Server error preparing query."]);
    }
    $stmt->bind_param("s", $selected);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($rows)) {
        $_SESSION['awaiting_field'] = false;
        out([
            'reply' => "I couldn't find courses in \"{$selected}\" right now. Try another field or visit the Courses page.",
            'await_field' => false
        ]);
    }

    $links = [];
    foreach ($rows as $r) {
        $cid = (int)$r['id'];
        $title = htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8');
        $thumb = '';
        if (!empty($r['thumbnail'])) {
            $thumb = ' data-thumb="' . htmlspecialchars($r['thumbnail'], ENT_QUOTES, 'UTF-8') . '"';
        }
        $links[] = "<div style=\"margin-bottom:8px;\"><a href=\"view_course.php?id={$cid}\" target=\"_blank\" style=\"color:#5624d0;text-decoration:none;font-weight:600;\"{$thumb}>{$title}</a></div>";
    }

    $html = "<div><strong>Top " . count($rows) . " courses in " . htmlspecialchars($selected) . ":</strong><br>" . implode("", $links) . "<div style=\"margin-top:8px;color:#666;font-size:13px;\">Tip: click any course to open it.</div></div>";

    $_SESSION['awaiting_field'] = false;
    out([
        'reply' => "Here are some courses I found:",
        'reply_html' => $html,
        'await_field' => false
    ]);
}

// ----------------- Detect help / suggest intent -----------------
if (strpos($low, 'help') !== false || strpos($low, 'suggest') !== false || strpos($low, 'course') !== false || strpos($low, 'find') !== false) {
    // set session flag
    $_SESSION['awaiting_field'] = true;

    $chips_html = '
      <div style="font-weight:700;margin-bottom:6px;">Sure — which field are you interested in?</div>
      <div class="suggestions" style="margin-top:8px;">
        <button class="chip" data-suggestion="Development">Development</button>
        <button class="chip" data-suggestion="Design">Design</button>
        <button class="chip" data-suggestion="Marketing">Marketing</button>
        <button class="chip" data-suggestion="Data Science">Data Science</button>
        <button class="chip" data-suggestion="IT & Software">IT & Software</button>
      </div>
      <div style="margin-top:8px;color:#666;font-size:13px;">You can also type a field name (e.g., Development).</div>
    ';

    out([
        'reply' => "Sure — which field are you interested in? (Click a suggestion below.)",
        'reply_html' => $chips_html,
        'await_field' => true,
        'quick_suggestions' => ['Development', 'Design', 'Marketing', 'Data Science', 'IT & Software']
    ]);
}

// ----------------- "My courses" branch -----------------
if (strpos($low, 'my courses') !== false || strpos($low, 'my learning') !== false) {
    if (!empty($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT c.id, c.title, c.thumbnail FROM courses c JOIN enrollments e ON e.course_id = c.id WHERE e.user_id = ? ORDER BY e.enrolled_at DESC LIMIT 6");
        if ($stmt) {
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (!empty($rows)) {
                $lines = [];
                foreach ($rows as $r) {
                    $id = (int)$r['id'];
                    $t = htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8');
                    $thumb = !empty($r['thumbnail']) ? (' data-thumb="' . htmlspecialchars($r['thumbnail'], ENT_QUOTES, 'UTF-8') . '"') : '';
                    $lines[] = "<div style=\"margin-bottom:8px;\"><a href=\"view_course.php?id={$id}\" target=\"_blank\" style=\"color:#5624d0;text-decoration:none;\"{$thumb}>{$t}</a></div>";
                }
                out(['reply' => 'Here are your recent courses:', 'reply_html' => implode("", $lines)]);
            } else {
                out(['reply' => "You don't have any enrolled courses yet."]);
            }
        } else {
            out(['reply' => "Server error while fetching your courses."]);
        }
    } else {
        out(['reply' => "I can't access your courses because you're not logged in. Please log in to see your enrolled courses."]);
    }
}

// ----------------- Greeting fuzzy match -----------------
if (strpos($low, 'hello') !== false || strpos($low, 'hi') !== false) {
    $html = '
      <div style="font-weight:700;margin-bottom:6px;">Hello — what can I help you with?</div>
      <div style="margin-top:6px;color:#666;font-size:13px;">Try a suggestion below or type what you want.</div>
      <div class="suggestions" style="margin-top:8px;">
        <button class="chip" data-suggestion="Development">Development</button>
        <button class="chip" data-suggestion="Design">Design</button>
        <button class="chip" data-suggestion="Data Science">Data Science</button>
      </div>
    ';
    out(['reply' => "Hello — how can I help?", 'reply_html' => $html, 'await_field' => false, 'quick_suggestions' => ['Development','Design','Data Science']]);
}

// ----------------- Default fallback -----------------
out([
    'reply' => "I'm a basic chatbot right now — more features will be added soon. Type 'help' or 'suggest' to get course recommendations (for example: Development, Design, Marketing)."
]);
