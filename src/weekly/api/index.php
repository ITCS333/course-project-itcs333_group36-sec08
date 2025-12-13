<?php
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 * Weekly Course Breakdown API (SQLite Version)
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

<?php
function sendResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
function sendError(string $message, int $statusCode = 400): void {
    sendResponse(['success' => false, 'error' => $message], $statusCode);
}

function validateDateString(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
@@ -63,8 +19,6 @@ function validateDateString(string $date): bool {
    return $d && $d->format('Y-m-d') === $date;
}

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function isValidSortField(string $field, array $allowed): bool {
@@ -73,9 +27,6 @@ function isValidSortField(string $field, array $allowed): bool {
    return in_array($field, $allowed, true);
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
@@ -85,9 +36,6 @@ function isValidSortField(string $field, array $allowed): bool {
    exit;
}

// TODO: Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();
require_once __DIR__ . '/../../db.php';

try {
    $db = getDatabase();
} catch (PDOException $e) {
@@ -96,16 +44,11 @@ function isValidSortField(string $field, array $allowed): bool {
    error_log('Weekly DB connection failed: '.$e->getMessage());
    sendError('Database connection failed', 500);
}
<?php
// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']

$method   = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$resource = $_GET['resource'] ?? 'weeks';

$requestBody = null;
// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
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
@@ -119,7 +62,6 @@ function isValidSortField(string $field, array $allowed): bool {
}

function getAllWeeks(PDO $db): void {
    // TODO: Initialize variables for search, sort, and order from query parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort   = $_GET['sort']  ?? 'start_date';
    $order  = strtolower($_GET['order'] ?? 'asc');

    $allowedSort = ['title', 'start_date', 'created_at'];
    if (!isValidSortField($sort, $allowedSort)) {
        $sort = 'start_date';
    }
    if (!in_array($order, ['asc', 'desc'], true)) {
@@ -132,45 +74,22 @@ function getAllWeeks(PDO $db): void {
        $order = 'asc';
    }

    // TODO: Start building the SQL query
    // Base query: SELECT week_id, title, start_date, description, links, created_at FROM weeks
    $sql    = 'SELECT id, title, start_date, description, links, created_at
               FROM weeks';
    $params = [];

    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE for title and description
    // Example: WHERE title LIKE ? OR description LIKE ?
    if ($search !== '') {
        $sql .= ' WHERE title LIKE ? OR description LIKE ?';
        $term   = '%'.$search.'%';
        $params = [$term, $term];
    }

    // TODO: Check if sort parameter exists
    // Validate sort field to prevent SQL injection (only allow: title, start_date, created_at)
    // If invalid, use default sort field (start_date)

    // TODO: Check if order parameter exists
    // Validate order to prevent SQL injection (only allow: asc, desc)
    // If invalid, use default order (asc)

    // TODO: Add ORDER BY clause to the query
    $sql .= " ORDER BY {$sort} {$order}";

    // TODO: Prepare the SQL query using PDO
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters if using search
    // Use wildcards for LIKE: "%{$searchTerm}%"
    $stmt->execute($params);

    // TODO: Execute the query
    // TODO: Fetch all results as an associative array
    $weeks = $stmt->fetchAll();

    // TODO: Process each week's links field
    // Decode the JSON string back to an array using json_decode()
    foreach ($weeks as &$week) {
        if (!empty($week['links'])) {
            $decoded = json_decode($week['links'], true);
            $week['links'] = is_array($decoded) ? $decoded : [];
        } else {
            $week['links'] = [];
@@ -180,129 +99,264 @@ function getAllWeeks(PDO $db): void {
        }
    }

    // TODO: Return JSON response with success status and data
    // Use sendResponse() helper function
    sendResponse(['success' => true, 'data' => $weeks]);
}

@@ -128,7 +192,25 @@

    sendResponse(['success' => true, 'data' => $week]);
}
/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * } catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
    error_log('Weekly PDO error: '.$e->getMessage());

    // TODO: Return generic error response with 500 status
    // Do NOT expose database error details to the client
    // Return message: "Database error occurred"
    sendError('Database error occurred', 500);
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    error_log('Weekly API error: '.$e->getMessage());
function createWeek(PDO $db, array $data): void {
    if (
        empty($data['title']) ||
@@ -138,6 +220,8 @@
        sendError('title, start_date and description are required', 400);
    }

    // Return error response with 500 status
    sendError('An error occurred', 500);
    $title       = sanitize($data['title']);
    $description = sanitize((string) $data['description']);
    $startDate   = (string) $data['start_date'];
@@ -172,15 +256,32 @@
    }
    $id = (int) $data['id'];

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================
    $check = $db->prepare('SELECT id FROM weeks WHERE id = ?');
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendError('Week not found', 404);
    }

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    // Use http_response_code($statusCode)
    http_response_code($statusCode);
    $set  = [];
    $vals = [];

    // TODO: Echo JSON encoded data
    // Use json_encode($data)
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (isset($data['title'])) {
        $set[]  = 'title = ?';
        $vals[] = sanitize((string) $data['title']);
@@ -203,6 +304,8 @@
        $vals[] = json_encode($links);
    }

    // TODO: Exit to prevent further execution
    exit;
    if (!$set) {
        sendError('No fields to update', 400);
    }
@@ -225,12 +328,24 @@
        sendError('id is required', 400);
    }

/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */
function sendError($message, $statusCode = 400) {
    // TODO: Create error response array
    // Structure: ['success' => false, 'error' => $message]
    $error = ['success' => false, 'error' => $message];
    $check = $db->prepare('SELECT id FROM weeks WHERE id = ?');
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendError('Week not found', 404);
    }

    // TODO: Call sendResponse() with the error array and status code
    sendResponse($error, $statusCode);
    $stmt = $db->prepare('DELETE FROM weeks WHERE id = ?');
    if (!$stmt->execute([$id])) {
        sendError('Failed to delete week', 500);
@@ -247,6 +362,19 @@
        sendError('week_id is required', 400);
    }

/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDateString($date) {
    // TODO: Use DateTime::createFromFormat() to validate
    // Format: 'Y-m-d'
    // Check that the created date matches the input string
    // Return true if valid, false otherwise
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
    $stmt = $db->prepare(
        'SELECT id, week_id, author, text, created_at
         FROM comments_week
@@ -268,6 +396,15 @@
    $author = sanitize((string) $data['author']);
    $text   = sanitize((string) $data['text']);

/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitize($data) {
    // TODO: Trim whitespace
    $data = trim($data);
    if ($text === '') {
        sendError('Comment text cannot be empty', 400);
    }
@@ -287,8 +424,12 @@
        sendError('Failed to create comment', 500);
    }

    // TODO: Strip HTML tags using strip_tags()
    $data = strip_tags($data);
    $newId = (int) $db->lastInsertId();

    // TODO: Convert special characters using htmlspecialchars()
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $stmt = $db->prepare(
        'SELECT id, week_id, author, text, created_at
         FROM comments_week
@@ -297,6 +438,8 @@
    $stmt->execute([$newId]);
    $comment = $stmt->fetch();

    // TODO: Return sanitized data
    return $data;
    sendResponse(['success' => true, 'data' => $comment], 201);
}

@@ -305,58 +448,70 @@
        sendError('id is required', 400);
    }

/**
 * Helper function to validate allowed sort fields
 * 
 * @param string $field - Field name to validate
 * @param array $allowedFields - Array of allowed field names
 * @return bool - True if valid, false otherwise
 */
function isValidSortField($field, $allowedFields) {
    // TODO: Check if $field exists in $allowedFields array
    // Use in_array()
    // Return true if valid, false otherwise
    return in_array($field, $allowedFields, true);
    $check = $db->prepare('SELECT id FROM comments_week WHERE id = ?');
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendError('Comment not found', 404);
    }

    $stmt = $db->prepare('DELETE FROM comments_week WHERE id = ?');
    if (!$stmt->execute([$id])) {
        sendError('Failed to delete comment', 500);
    }

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
