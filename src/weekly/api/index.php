 <?php
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
    return $d && $d->format('Y-m-d') === $date;
}

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function isValidSortField(string $field, array $allowed): bool {
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
    exit;
}

// TODO: Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();
require_once __DIR__ . '/../../db.php';

try {
    $db = getDatabase();
} catch (PDOException $e) {
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
        }
    }

    // TODO: Return JSON response with success status and data
    // Use sendResponse() helper function
    sendResponse(['success' => true, 'data' => $weeks]);
}

function getWeekById(PDO $db, $id): void {
    if (empty($id)) {
        sendError('id is required', 400);
    }
}
/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")
 */
function getWeekById(PDO $db, $weekId): void {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendError('week_id is required', 400);
    }

    // TODO: Prepare SQL query to select week by week_id
    // SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?
    $stmt = $db->prepare(
        'SELECT id, title, start_date, description, links, created_at
         FROM weeks
         WHERE id = ?'
    );

    // TODO: Bind the week_id parameter
    $stmt->execute([$weekId]);

    // TODO: Execute the query
    // TODO: Fetch the result
    $week = $stmt->fetch();

    // TODO: Check if week exists
    // If yes, decode the links JSON and return success response with week data
    // If no, return error response with 404 status
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

/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek(PDO $db, array $data): void {
    // TODO: Validate required fields
    // Check if week_id, title, start_date, and description are provided
    // If any field is missing, return error response with 400 status
    if (
        empty($data['week_id']) ||
        empty($data['title']) ||
        empty($data['start_date']) ||
        !array_key_exists('description', $data)
    ) {
        sendError('week_id, title, start_date and description are required', 400);
    }

    // TODO: Sanitize input data
    // Trim whitespace from title, description
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

    getWeekById($db, $id);
}

/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier
 */
function deleteWeek(PDO $db, $weekId): void {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendError('week_id is required', 400);
    }

    // TODO: Check if week exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $check = $db->prepare('SELECT id FROM weeks WHERE id = ?');
    $check->execute([$weekId]);
    if (!$check->fetch()) {
        sendError('Week not found', 404);
    }

    // TODO: Delete associated comments first (to maintain referential integrity)
    // Prepare DELETE query for comments table
    // DELETE FROM comments WHERE week_id = ?
    $delComments = $db->prepare('DELETE FROM comments_week WHERE week_id = ?');
    $delComments->execute([$weekId]);

    // TODO: Prepare DELETE query for week
    // DELETE FROM weeks WHERE week_id = ?
    $stmt = $db->prepare('DELETE FROM weeks WHERE id = ?');

    // TODO: Bind the week_id parameter
    // TODO: Execute the query
    if (!$stmt->execute([$weekId])) {
        sendError('Failed to delete week', 500);
    }

    // TODO: Check if delete was successful
    sendResponse([
        'success' => true,
        'message' => 'Week and its comments deleted',
    ]);
}

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek(PDO $db, $weekId): void {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendError('week_id is required', 400);
    }

    // TODO: Prepare SQL query to select comments for the week
    // SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC
    $stmt = $db->prepare(
        'SELECT id, week_id, author, text, created_at
         FROM comments_week
         WHERE week_id = ?
         ORDER BY created_at ASC'
    );

    // TODO: Bind the week_id parameter
    $stmt->execute([$weekId]);
 function deleteComment(PDO $db, $id): void {
    if (empty($id)) {
        sendError('id is required', 400);
    }

    $check = $db->prepare('SELECT id FROM comments_week WHERE id = ?');
    $check->execute([$id]);
    if (!$check->fetch()) {
        sendError('Comment not found', 404);
    }

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================
    $stmt = $db->prepare('DELETE FROM comments_week WHERE id = ?');
    if (!$stmt->execute([$id])) {
        sendError('Failed to delete comment', 500);
    }

    sendResponse(['success' => true, 'message' => 'Comment deleted']);
}

try {
    // TODO: Determine the resource type from query parameters
    // Get 'resource' parameter (?resource=weeks or ?resource=comments)
    // If not provided, default to 'weeks'
    $resource = $_GET['resource'] ?? 'weeks';
    
    // Route based on resource type and HTTP method
    
    // ========== WEEKS ROUTES ==========
    if ($resource === 'weeks') {
        
        if ($method === 'GET') {
            // TODO: Check if week_id is provided in query parameters
            // If yes, call getWeekById()
            // If no, call getAllWeeks() to get all weeks (with optional search/sort)
            if (isset($_GET['id'])) {
                getWeekById($db, $_GET['id']);
            } else {
                getAllWeeks($db);
            }
        } elseif ($method === 'POST') {
            // TODO: Call createWeek() with the decoded request body
            createWeek($db, $requestBody ?? []);
        } elseif ($method === 'PUT') {
            // TODO: Call updateWeek() with the decoded request body
            updateWeek($db, $requestBody ?? []);
        } elseif ($method === 'DELETE') {
            // TODO: Get week_id from query parameter or request body
            // Call deleteWeek()
            $id = $_GET['id'] ?? ($requestBody['id'] ?? null);
            deleteWeek($db, $id);
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            sendError('Method not allowed', 405);
        }
    }
    
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        if ($method === 'GET') {
            // TODO: Get week_id from query parameters
            // Call getCommentsByWeek()
            $weekId = $_GET['week_id'] ?? null;
            getCommentsByWeek($db, $weekId);
        } elseif ($method === 'POST') {
            // TODO: Call createComment() with the decoded request body
            createComment($db, $requestBody ?? []);
        } elseif ($method === 'DELETE') {
            // TODO: Get comment id from query parameter or request body
            // Call deleteComment()
            $id = $_GET['id'] ?? ($requestBody['id'] ?? null);
            deleteComment($db, $id);
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            sendError('Method not allowed', 405);
        }
    } else {
        // TODO: Return error for invalid resource
        // Set HTTP status to 400 (Bad Request)
        // Return JSON error message: "Invalid resource. Use 'weeks' or 'comments'"
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
    error_log('Weekly PDO error: '.$e->getMessage());
    
    // TODO: Return generic error response with 500 status
    // Do NOT expose database error details to
