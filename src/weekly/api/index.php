php
<?php
/**
 * Weekly Course Breakdown API (SQLite Version)
 */

session_start();

function sendResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function sendError(string $message, int $statusCode = 400): void {
    sendResponse(['success' => false, 'error' => $message], $statusCode);
}

function validateDateString(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function isValidSortField(string $field, array $allowed): bool {
    return in_array($field, $allowed, true);
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../db.php';

try {
    $db = getDatabase();
} catch (PDOException $e) {
    error_log('Weekly DB connection failed: '.$e->getMessage());
    sendError('Database connection failed', 500);
}

$method   = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$resource = $_GET['resource'] ?? 'weeks';

$_SESSION['method'] = $method;
$_SESSION['resource'] = $resource;

$requestBody = null;
if (in_array($method, ['POST', 'PUT'], true)) {
    $raw = file_get_contents('php://input');
    if ($raw !== '') {
        $requestBody = json_decode($raw, true);
        if (!is_array($requestBody)) {
            sendError('Invalid JSON in request body', 400);
        }
    } else {
        $requestBody = [];
    }
}

function getAllWeeks(PDO $db): void {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort   = $_GET['sort']  ?? 'start_date';
    $order  = strtolower($_GET['order'] ?? 'asc');

    $allowedSort = ['title', 'start_date', 'created_at'];
    if (!isValidSortField($sort, $allowedSort)) {
        $sort = 'start_date';
    }
    if (!in_array($order, ['asc', 'desc'], true)) {
        $order = 'asc';
    }

    $sql    = 'SELECT id, title, start_date, description, links, created_at
               FROM weeks';
    $params = [];

    if ($search !== '') {
        $sql .= ' WHERE title LIKE ? OR description LIKE ?';
        $term   = '%'.$search.'%';
        $params = [$term, $term];
    }

    $sql .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $weeks = $stmt->fetchAll();

    foreach ($weeks as &$week) {
        if (!empty($week['links'])) {
            $decoded = json_decode($week['links'], true);
            $week['links'] = is_array($decoded) ? $decoded : [];
        } else {
            $week['links'] = [];
        }
    }

    sendResponse(['success' => true, 'data' => $weeks]);
}

function getWeekById(PDO $db, $id): void {
    if (empty($id)) {
        sendError('id is required', 400);
    }

    $stmt = $db->prepare(
        'SELECT id, title, start_date, description, links, created_at
         FROM weeks
         WHERE id = ?'
    );
    $stmt->execute([$id]);
    $week = $stmt->fetch();

    if (!$week) {
        sendError('Week not found', 404);
    }

    if (!empty($week['links'])) {
        $decoded = json_decode($week['links'], true);
        $week['links'] = is_array($decoded) ? $decoded : [];
    } else {
        $week['links'] = [];
    }

    sendResponse(['success' => true, 'data' => $week]);
}

function createWeek(PDO $db, array $data): void {
    if (
        empty($data['title']) ||
        empty($data['start_date']) ||
        !array_key_exists('description', $data)
    ) {
        sendError('title, start_date and description are required', 400);
    }

    $title       = sanitize($data['title']);
    $description = sanitize((string) $data['description']);
    $startDate   = (string) $data['start_date'];

    if (!validateDateString($startDate)) {
        sendError('Invalid date format. Use YYYY-MM-DD', 400);
    }

    $links = [];
    if (isset($data['links']) && is_array($data['links'])) {
        $links = $data['links'];
    }
    $linksJson = json_encode($links);

    $stmt = $db->prepare(
        'INSERT INTO weeks (title, start_date, description, links)
         VALUES (?, ?, ?, ?)'
    );

    if (!$stmt->execute([$title, $startDate, $description, $linksJson])) {
        sendError('Failed to create week', 500);
    }

    $newId = (int) $db->lastInsertId();

    $_SESSION['last_created_week'] = $newId;

    getWeekById($db, $newId);
}

function updateWeek(PDO $db, array $data): void {
    if (empty($data['id'])) {
        sendError('id is required', 400);
    }
    $id = (int) $data['id'];

    $check = $db->prepare('SELECT id FROM weeks WHERE id = ?');
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendError('Week not found', 404);
    }

    $set  = [];
    $vals = [];

    if (isset($data['title'])) {
        $set[]  = 'title = ?';
        $vals[] = sanitize((string) $data['title']);
    }
    if (isset($data['start_date'])) {
        $sd = (string) $data['start_date'];
        if (!validateDateString($sd)) {
            sendError('Invalid date format. Use YYYY-MM-DD', 400);
        }
        $set[]  = 'start_date = ?';
        $vals[] = $sd;
    }
    if (isset($data['description'])) {
        $set[]  = 'description = ?';
        $vals[] = sanitize((string) $data['description']);
    }
    if (isset($data['links'])) {
        $links  = is_array($data['links']) ? $data['links'] : [];
        $set[]  = 'links = ?';
        $vals[] = json_encode($links);
    }

    if (!$set) {
        sendError('No fields to update', 400);
    }

    $set[] = 'updated_at = CURRENT_TIMESTAMP';

    $sql   = 'UPDATE weeks SET '.implode(', ', $set).' WHERE id = ?';
    $vals[] = $id;

    $stmt = $db->prepare($sql);
    if (!$stmt->execute($vals)) {
        sendError('Failed to update week', 500);
    }

    $_SESSION['last_updated_week'] = $id;

    getWeekById($db, $id);
}

function deleteWeek(PDO $db, $id): void {
    if (empty($id)) {
        sendError('id is required', 400);
    }

    $check = $db->prepare('SELECT id FROM weeks WHERE id = ?');
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendError('Week not found', 404);
    }

    $stmt = $db->prepare('DELETE FROM weeks WHERE id = ?');
    if (!$stmt->execute([$id])) {
        sendError('Failed to delete week', 500);
    }

    $_SESSION['last_deleted_week'] = $id;

    sendResponse([
        'success' => true,
        'message' => 'Week and its comments deleted',
    ]);
}

function getCommentsByWeek(PDO $db, $weekId): void {
    if (empty($weekId)) {
        sendError('week_id is required', 400);
    }

    $stmt = $db->prepare(
        'SELECT id, week_id, author, text, created_at
         FROM comments_week
         WHERE week_id = ?
         ORDER BY created_at ASC'
    );
    $stmt->execute([$weekId]);
    $comments = $stmt->fetchAll();

    sendResponse(['success' => true, 'data' => $comments]);
}

function createComment(PDO $db, array $data): void {
    if (empty($data['week_id']) || empty($data['author']) || empty($data['text'])) {
        sendError('week_id, author and text are required', 400);
    }

    $weekId = (int) $data['week_id'];
    $author = sanitize((string) $data['author']);
    $text   = sanitize((string) $data['text']);

    if ($text === '') {
        sendError('Comment text cannot be empty', 400);
    }

    $check = $db->prepare('SELECT id FROM weeks WHERE id = ?');
    $check->execute([$weekId]);
    if (!$check->fetch()) {
        sendError('Week not found', 404);
    }

    $stmt = $db->prepare(
        'INSERT INTO comments_week (week_id, author, text)
         VALUES (?, ?, ?)'
    );

    if (!$stmt->execute([$weekId, $author, $text])) {
        sendError('Failed to create comment', 500);
    }

    $newId = (int) $db->lastInsertId();

    $_SESSION['last_created_comment'] = $newId;

    $stmt = $db->prepare(
        'SELECT id, week_id, author, text, created_at
         FROM comments_week
         WHERE id = ?'
    );
    $stmt->execute([$newId]);
    $comment = $stmt->fetch();

    sendResponse(['success' => true, 'data' => $comment], 201);
}

function deleteComment(PDO $db, $id): void {
    if (empty($id)) {
        sendError('id is required', 400);
    }

    $check = $db->prepare('SELECT id FROM comments_week WHERE id = ?');
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendError('Comment not found', 404);
    }

    $stmt = $db->prepare('DELETE FROM comments_week WHERE id = ?');
    if (!$stmt->execute([$id])) {
        sendError('Failed to delete comment', 500);
    }

    $_SESSION['last_deleted_comment'] = $id;

    sendResponse(['success' => true, 'message' => 'Comment deleted']);
}

try {
    if ($resource === 'weeks') {
        if ($method === 'GET') {
            if (isset($_GET['id'])) {
                getWeekById($db, $_GET['id']);
            } else {
                getAllWeeks($db);
            }
        } elseif ($method === 'POST') {
            createWeek($db, $requestBody ?? []);
        } elseif ($method === 'PUT') {
            updateWeek($db, $requestBody ?? []);
        } elseif ($method === 'DELETE') {
            $id = $_GET['id'] ?? ($requestBody['id'] ?? null);
            deleteWeek($db, $id);
        } else {
            sendError('Method not allowed', 405);
        }
    } elseif ($resource === 'comments') {
        if ($method === 'GET') {
            $weekId = $_GET['week_id'] ?? null;
            getCommentsByWeek($db, $weekId);
        } elseif ($method === 'POST') {
            createComment($db, $requestBody ?? []);
        } elseif ($method === 'DELETE') {
            $id = $_GET['id'] ?? ($requestBody['id'] ?? null);
            deleteComment($db, $id);
        } else {
            sendError('Method not allowed', 405);
        }
    } else {
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
    }
} catch (PDOException $e) {
    error_log('Weekly PDO error: '.$e->getMessage());
    sendError('Database error occurred', 500);
} catch (Exception $e) {
    error_log('Weekly API error: '.$e->getMessage());
    sendError('An error occurred', 500);
}

