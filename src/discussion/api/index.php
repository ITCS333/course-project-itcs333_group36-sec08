<?php
session_start();

// Store user info in session (required by tests)
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = 'guest';
}
/**
 * Discussion Board API
 * 
 * This is a RESTful API that handles all CRUD operations for the discussion board.
 * It manages both discussion topics and their replies.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: topics
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - topic_id (VARCHAR(50), UNIQUE) - The topic's unique identifier (e.g., "topic_1234567890")
 *   - subject (VARCHAR(255)) - The topic subject/title
 *   - message (TEXT) - The main topic message
 *   - author (VARCHAR(100)) - The author's name
 *   - created_at (TIMESTAMP) - When the topic was created
 * 
 * Table: replies
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - reply_id (VARCHAR(50), UNIQUE) - The reply's unique identifier (e.g., "reply_1234567890")
 *   - topic_id (VARCHAR(50)) - Foreign key to topics.topic_id
 *   - text (TEXT) - The reply message
 *   - author (VARCHAR(100)) - The reply author's name
 *   - created_at (TIMESTAMP) - When the reply was created
 * 
 * API Endpoints:
 * 
 * Topics:
 *   GET    /api/discussion.php?resource=topics              - Get all topics (with optional search)
 *   GET    /api/discussion.php?resource=topics&id={id}      - Get single topic
 *   POST   /api/discussion.php?resource=topics              - Create new topic
 *   PUT    /api/discussion.php?resource=topics              - Update a topic
 *   DELETE /api/discussion.php?resource=topics&id={id}      - Delete a topic
 * 
 * Replies:
 *   GET    /api/discussion.php?resource=replies&topic_id={id} - Get all replies for a topic
 *   POST   /api/discussion.php?resource=replies              - Create new reply
 *   DELETE /api/discussion.php?resource=replies&id={id}      - Delete a reply
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
header('Content-Type: application/json');
// Allow cross-origin requests (CORS) if needed
header('Access-Control-Allow-Origin: *');
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// Allow specific headers (Content-Type, Authorization)
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}




// TODO: Get the PDO database connection
$host = 'localhost';
$db   = 'course';
$user = 'admin';
$pass = 'password123';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db = $pdo;
} catch (PDOException $e) {
    // Never expose database details in production
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}


// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];


// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()

if($method === 'POST' || $method === 'PUT') {
    // Read raw input
    $input = file_get_contents('php://input');
    // Decode JSON input
    $data = json_decode($input, true);
} else {
    $data = null;
}



// TODO: Parse query parameters for filtering and searching
$resource = isset($_GET['resource']) ? $_GET['resource'] : null;
$topicId = isset($_GET['id']) ? $_GET['id'] : null;
$replyId = isset($_GET['id']) ? $_GET['id'] : null;




// ============================================================================
// TOPICS FUNCTIONS
// ============================================================================

/**
 * Function: Get all topics or search for specific topics
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by subject, message, or author
 *   - sort: Optional field to sort by (subject, author, created_at)
 *   - order: Optional sort order (asc or desc, default: desc)
 */
function getAllTopics($db) {
    // TODO: Initialize base SQL query
    
    // Select topic_id, subject, message, author, and created_at (formatted as date)
    $sql = "SELECT topic_id, subject, message, author, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at FROM topics";
    // TODO: Initialize an array to hold bound parameters
    $params = [];
    // TODO: Check if search parameter exists in $_GET
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $searchTerm = '%' . trim($_GET['search']) . '%';
        $sql .= " WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search";
        $params[':search'] = $searchTerm;
    }else{
        // No search term provided
        $searchTerm = null;
    }
    // If yes, add WHERE clause using LIKE for subject, message, OR author
    // Add the search term to the params array
    
    // TODO: Add ORDER BY clause
    // Check for sort and order parameters in $_GET
    // Validate the sort field (only allow: subject, author, created_at)
    // Validate order (only allow: asc, desc)
    // Default to ordering by created_at DESC
    $allowedSortFields = ['subject', 'author', 'created_at'];
    $sortField = 'created_at';
    $sortOrder = 'DESC';
    if (isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortFields)) {
        $sortField = $_GET['sort'];
    }else {
        $sortField = 'created_at';
    }
    if (isset($_GET['order']) && in_array(strtolower($_GET['order']), ['asc', 'desc'])) {
        $sortOrder = strtoupper($_GET['order']);
    } else {
        $sortOrder = 'DESC';
    }
    $sql .= " ORDER BY $sortField $sortOrder";
    
    // TODO: Prepare the SQL statement
    $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters if search was used
    // Loop through $params array and bind each parameter
    if ($searchTerm !== null) {
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
}
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Fetch all results as an associative array
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // TODO: Return JSON response with success status and data
    // Call sendResponse() helper function or echo json_encode directly
    echo json_encode([
        'success' => true,
        'data' => $topics
    ]);
}


/**
 * Function: Get a single topic by topic_id
 * Method: GET
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function getTopicById($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If empty, return error with 400 status
    if (empty($topicId)) {
      sendResponse([
          'success' => false,
          'message' => 'Topic ID is required'
      ], 400);
    }
    
    // TODO: Prepare SQL query to select topic by topic_id
    // Select topic_id, subject, message, author, and created_at
    $sql = "SELECT topic_id, subject, message, author, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at FROM topics WHERE topic_id = :topic_id";
    
    // TODO: Prepare and bind the topic_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':topic_id', $topicId);
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Fetch the result
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    // TODO: Check if topic exists
    // If topic found, return success response with topic data
    // If not found, return error with 404 status
    if ($topic) {
        sendResponse([
            'success' => true,
            'data' => $topic
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Topic not found'
        ], 404);
    }
}


/**
 * Function: Create a new topic
 * Method: POST
 * 
 * Required JSON Body:
 *   - topic_id: Unique identifier (e.g., "topic_1234567890")
 *   - subject: Topic subject/title
 *   - message: Main topic message
 *   - author: Author's name
 */
function createTopic($db, $data) {
    // TODO: Validate required fields
    // Check if topic_id, subject, message, and author are provided
    // If any required field is missing, return error with 400 status
    if (empty($data['topic_id']) || empty($data['subject']) || empty($data['message']) || empty($data['author'])) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields'
        ], 400);
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all string fields
    // Use the sanitizeInput() helper function
    $topicId = sanitizeInput($data['topic_id']);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author = sanitizeInput($data['author']);
    
    // TODO: Check if topic_id already exists
    // Prepare and execute a SELECT query to check for duplicate
    // If duplicate found, return error with 409 status (Conflict)
    $checkSql = "SELECT COUNT(*) FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    if ($checkStmt) {
        $checkStmt->bindValue(':topic_id', $topicId);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();
        if ($count > 0) {
            sendResponse([
                'success' => false,
                'message' => 'Topic ID already exists'
            ], 409);
        }
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Database error during topic ID check'
        ], 500);
    }
    
    // TODO: Prepare INSERT query
    // Insert topic_id, subject, message, and author
    $insertSql = "INSERT INTO topics (topic_id, subject, message, author) VALUES (:topic_id, :subject, :message, :author)";
    // The created_at field should auto-populate with CURRENT_TIMESTAMP

    
    // TODO: Prepare the statement and bind parameters
         $insertStmt = $db->prepare($insertSql);
    // Bind all the sanitized values
    $insertStmt->bindValue(':topic_id', $topicId);
    $insertStmt->bindValue(':subject', $subject);
    $insertStmt->bindValue(':message', $message);
    $insertStmt->bindValue(':author', $author);

    
    // TODO: Execute the query
    $insertResult = $insertStmt->execute();
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // Include the topic_id in the response
    // If no, return error with 500 status
    if ($insertResult) {
        sendResponse([
            'success' => true,
            'message' => 'Topic created successfully',
            'topic_id' => $topicId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create topic'
        ], 500);
    }
}


/**
 * Function: Update an existing topic
 * Method: PUT
 * 
 * Required JSON Body:
 *   - topic_id: The topic's unique identifier
 *   - subject: Updated subject (optional)
 *   - message: Updated message (optional)
 */
function updateTopic($db, $data) {
    // TODO: Validate that topic_id is provided
    // If not provided, return error with 400 status
    if (empty($data['topic_id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID is required'
        ], 400);
    }
    
    // TODO: Check if topic exists
    // Prepare and execute a SELECT query
    // If not found, return error with 404 status
    $checkSql = "SELECT COUNT(*) FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':topic_id', $data['topic_id']);
    $checkStmt->execute();
    $count = $checkStmt->fetchColumn();
    if ($count == 0) {
        sendResponse([
            'success' => false,
            'message' => 'Topic not found'
        ], 404);
    }
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    $updates = [];
    $params = [':topic_id' => $data['topic_id']];
    if (isset($data['subject']) && !empty(trim($data['subject']))) {
        $updates[] = "subject = :subject";
        $params[':subject'] = sanitizeInput($data['subject']);
    }
    if (isset($data['message']) && !empty(trim($data['message']))) {
        $updates[] = "message = :message";
        $params[':message'] = sanitizeInput($data['message']);
    }
    

    
    // TODO: Check if there are any fields to update
    // If $updates array is empty, return error
    if (empty($updates)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields to update'
        ], 400);
    }
    
    // TODO: Complete the UPDATE query
    $updateSql = "UPDATE topics SET " . implode(", ", $updates) . " WHERE topic_id = :topic_id";
    
    // TODO: Prepare statement and bind parameters
    // Bind all parameters from the $params array
    $updateStmt = $db->prepare($updateSql);
    foreach ($params as $key => $value) {
        $updateStmt->bindValue($key, $value);
    }
    
    // TODO: Execute the query
    $updateResult = $updateStmt->execute();
    // TODO: Check if update was successful
    // If yes, return success response
    // If no rows affected, return appropriate message
    // If error, return error with 500 status
    if ($updateResult) {
        if ($updateStmt->rowCount() > 0) {
            sendResponse([
                'success' => true,
                'message' => 'Topic updated successfully'
            ]);
        } else {
            sendResponse([
                'success' => true,
                'message' => 'No changes made to the topic'
            ]);
        }
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update topic'
        ], 500);
    }
}


/**
 * Function: Delete a topic
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function deleteTopic($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If not, return error with 400 status
    if (empty($topicId)) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID is required'
        ], 400);
    }
    
    // TODO: Check if topic exists
    // Prepare and execute a SELECT query
    // If not found, return error with 404 status
    
    $checkSql = "SELECT COUNT(*) FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':topic_id', $topicId);
    $checkStmt->execute();
    $count = $checkStmt->fetchColumn();
    if ($count == 0) {
        sendResponse([
            'success' => false,
            'message' => 'Topic not found'
        ], 404);
    }
    // TODO: Delete associated replies first (foreign key constraint)
    // Prepare DELETE query for replies table
    $deleteRepliesSql = "DELETE FROM replies WHERE topic_id = :topic_id";
    $deleteRepliesStmt = $db->prepare($deleteRepliesSql);
    
    // TODO: Prepare DELETE query for the topic
    $deleteTopicSql = "DELETE FROM topics WHERE topic_id = :topic_id";
    $deleteTopicStmt = $db->prepare($deleteTopicSql);
    // TODO: Prepare, bind, and execute
    $deleteRepliesStmt->bindValue(':topic_id', $topicId);
    $deleteRepliesStmt->execute();
    $deleteTopicStmt->bindValue(':topic_id', $topicId);
    $deleteResult = $deleteTopicStmt->execute();

    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error with 500 status
    if ($deleteResult) {
        sendResponse([
            'success' => true,
            'message' => 'Topic and associated replies deleted successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete topic'
        ], 500);
    }
}


// ============================================================================
// REPLIES FUNCTIONS
// ============================================================================

/**
 * Function: Get all replies for a specific topic
 * Method: GET
 * 
 * Query Parameters:
 *   - topic_id: The topic's unique identifier
 */
function getRepliesByTopicId($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If not provided, return error with 400 status
    if (empty($topicId)) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID is required'
        ], 400);
    }
    // TODO: Prepare SQL query to select all replies for the topic
    // Select reply_id, topic_id, text, author, and created_at (formatted as date)
    // Order by created_at ASC (oldest first)
    $sql = "SELECT reply_id, topic_id, text, author, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at FROM replies WHERE topic_id = :topic_id ORDER BY created_at ASC";    
    // TODO: Prepare and bind the topic_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':topic_id', $topicId);
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Fetch all results as an associative array
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // TODO: Return JSON response
    // Even if no replies found, return empty array (not an error)
    if (!$replies) {
        $replies = [];
    }
    sendResponse([
        'success' => true,
        'data' => $replies
    ]);
}


/**
 * Function: Create a new reply
 * Method: POST
 * 
 * Required JSON Body:
 *   - reply_id: Unique identifier (e.g., "reply_1234567890")
 *   - topic_id: The parent topic's identifier
 *   - text: Reply message text
 *   - author: Author's name
 */
function createReply($db, $data) {
    // TODO: Validate required fields
    // Check if reply_id, topic_id, text, and author are provided
    // If any field is missing, return error with 400 status
    if (empty($data['reply_id']) || empty($data['topic_id']) || empty($data['text']) || empty($data['author'])) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields'
        ], 400);
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $replyId = sanitizeInput($data['reply_id']);
    $topicId = sanitizeInput($data['topic_id']);
    $text = sanitizeInput($data['text']);
    $author = sanitizeInput($data['author']);

    
    // TODO: Verify that the parent topic exists
    // Prepare and execute SELECT query on topics table
    // If topic doesn't exist, return error with 404 status (can't reply to non-existent topic)
    $checkSql = "SELECT COUNT(*) FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':topic_id', $topicId);
    $checkStmt->execute();
    $count = $checkStmt->fetchColumn();
    if ($count == 0) {
        sendResponse([
            'success' => false,
            'message' => 'Parent topic not found'
        ], 404);
    }
    
    // TODO: Check if reply_id already exists
    // Prepare and execute SELECT query to check for duplicate
    // If duplicate found, return error with 409 status
    $checkReplySql = "SELECT COUNT(*) FROM replies WHERE reply_id = :reply_id";
    $checkReplyStmt = $db->prepare($checkReplySql);
    $checkReplyStmt->bindValue(':reply_id', $replyId);
    $checkReplyStmt->execute();
    $replyCount = $checkReplyStmt->fetchColumn();
    if ($replyCount > 0) {
        sendResponse([
            'success' => false,
            'message' => 'Reply ID already exists'
        ], 409);
    }

    // TODO: Prepare INSERT query
    // Insert reply_id, topic_id, text, and author
    $insertSql = "INSERT INTO replies (reply_id, topic_id, text, author) VALUES (:reply_id, :topic_id, :text, :author)";

    
    // TODO: Prepare statement and bind parameters
    $insertStmt = $db->prepare($insertSql);
    
    // TODO: Execute the query
    $insertResult=$insertStmt->execute([
        ':reply_id' => $replyId,
        ':topic_id' => $topicId,
        ':text' => $text,
        ':author' => $author
    ]);
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status
    // Include the reply_id in the response
    // If no, return error with 500 status
    if ($insertResult) {
        sendResponse([
            'success' => true,
            'message' => 'Reply created successfully',
            'reply_id' => $replyId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create reply'
        ], 500);
    }
}


/**
 * Function: Delete a reply
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The reply's unique identifier
 */
function deleteReply($db, $replyId) {
    // TODO: Validate that replyId is provided
    // If not, return error with 400 status
    if (empty($replyId)) {
        sendResponse([
            'success' => false,
            'message' => 'Reply ID is required'
        ], 400);
    }
    
    // TODO: Check if reply exists
    // Prepare and execute SELECT query
    $checkSql = "SELECT COUNT(*) FROM replies WHERE reply_id = :reply_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':reply_id', $replyId);
    $checkStmt->execute();
    $count = $checkStmt->fetchColumn();
    // If not found, return error with 404 status
    if ($count == 0) {
        sendResponse([
            'success' => false,
            'message' => 'Reply not found'
        ], 404);
    }
    
    // TODO: Prepare DELETE query
    $deleteSql = "DELETE FROM replies WHERE reply_id = :reply_id";
    $deleteStmt = $db->prepare($deleteSql);
    $deleteStmt->bindValue(':reply_id', $replyId);
    $deleteResult = $deleteStmt->execute();

    // TODO: Prepare, bind, and execute
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error with 500 status
    if ($deleteResult) {
        sendResponse([
            'success' => true,
            'message' => 'Reply deleted successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete reply'
        ], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
if (!isValidResource($resource)) {
sendResponse(['success' => false, 'message' => 'Invalid resource'], 400);
}


switch ($resource) {
case 'topics':
if ($method === 'GET') {
$topicId ? getTopicById($db, $topicId) : getAllTopics($db);
} elseif ($method === 'POST') {
createTopic($db, $data);
} elseif ($method === 'PUT') {
updateTopic($db, $data);
} elseif ($method === 'DELETE') {
deleteTopic($db, $topicId);
} else {
sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
break;


case 'replies':
if ($method === 'GET') {
getRepliesByTopicId($db, $_GET['topic_id'] ?? null);
} elseif ($method === 'POST') {
createReply($db, $data);
} elseif ($method === 'DELETE') {
deleteReply($db, $replyId);
} else {
sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
break;
}


} catch (PDOException $e) {
sendResponse(['success' => false, 'message' => 'Database error'], 500);
} catch (Exception $e) {
sendResponse(['success' => false, 'message' => 'Server error'], 500);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    
    // TODO: Echo JSON encoded data
    // Make sure to handle JSON encoding errors
    
    // TODO: Exit to prevent further execution
    http_response_code($statusCode);
echo json_encode($data);
exit;

}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Check if data is a string
    // If not, return as is or convert to string
    
    // TODO: Trim whitespace from both ends
    
    // TODO: Remove HTML and PHP tags
    
    // TODO: Convert special characters to HTML entities (prevents XSS)
    
    // TODO: Return sanitized data
    if (!is_string($data)) return $data;
$data = trim($data);
$data = strip_tags($data);
return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}


/**
 * Helper function to validate resource name
 * 
 * @param string $resource - Resource name to validate
 * @return bool - True if valid, false otherwise
 */
function isValidResource($resource) {
// TODO: Define allowed resources
$allowed = ['topics', 'replies'];


// TODO: Check if resource is in the allowed list
return in_array($resource, $allowed);
}

?>
