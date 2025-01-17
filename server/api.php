<?php
// api.php
// CORS Headers

header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Verifică dacă utilizatorul este admin pentru acțiunile PUT și DELETE
if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
}


// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'hotel_bookings';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'error' => "Connection failed: " . $e->getMessage()]);
    exit();
}

// API endpoints
header('Content-Type: application/json');

$path = $_SERVER['REQUEST_URI'];
$path = parse_url($path, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));
$resource = end($path_parts);

// Extract ID from URL if exists
$id = null;
if (is_numeric($resource)) {
    $id = $resource;
    $resource = prev($path_parts);
}

switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($resource === 'bookings') {
            if ($id) {
                getBooking($id);
            } else {
                getAllBookings();
            }
        } elseif ($resource === 'hotels') {
            getAllHotels();
        }
        break;
    
    case 'POST':
        if ($resource === 'bookings') {
            createBooking();
        }
        break;
    
    case 'PUT':
        if ($resource === 'bookings' && $id) {
            updateBooking($id);
        }
        break;
    
    case 'DELETE':
        if ($resource === 'bookings' && $id) {
            deleteBooking($id);
        }
        break;

    default:
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'error' => 'Invalid endpoint']);
        break;
}

function getAllHotels() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT DISTINCT * FROM hotels ORDER BY name");
        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $hotels]);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getAllBookings() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT b.*, h.name as hotel_name, h.location, h.price_per_night 
                            FROM bookings b
                            JOIN hotels h ON b.hotel_id = h.id 
                            ORDER BY b.created_at DESC");
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $bookings]);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getBooking($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT b.*, h.name as hotel_name, h.location, h.price_per_night 
                              FROM bookings b
                              JOIN hotels h ON b.hotel_id = h.id 
                              WHERE b.id = ?");
        $stmt->execute([$id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($booking) {
            echo json_encode(['success' => true, 'data' => $booking]);
        } else {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['success' => false, 'error' => 'Booking not found']);
        }
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function createBooking() {
    global $pdo;
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!$data || !isset($data['hotel_id']) || !isset($data['guest_name']) || 
            !isset($data['guest_count']) || !isset($data['check_in']) || !isset($data['check_out'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }
        
        // Validate guest count
        if ($data['guest_count'] < 1 || $data['guest_count'] > 10) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'error' => 'Invalid guest count (must be between 1 and 10)']);
            return;
        }
        
        $stmt = $pdo->prepare("INSERT INTO bookings (hotel_id, guest_name, guest_count, check_in, check_out) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['hotel_id'],
            $data['guest_name'],
            $data['guest_count'],
            $data['check_in'],
            $data['check_out']
        ]);
        
        $newId = $pdo->lastInsertId();
        
        header('HTTP/1.1 201 Created');
        echo json_encode(['success' => true, 'id' => $newId]);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function updateBooking($id) {
    global $pdo;
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
            return;
        }
        
        // Validate guest count
        if (isset($data['guest_count']) && ($data['guest_count'] < 1 || $data['guest_count'] > 10)) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'error' => 'Invalid guest count (must be between 1 and 10)']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE bookings 
                              SET hotel_id = ?, guest_name = ?, guest_count = ?, check_in = ?, check_out = ? 
                              WHERE id = ?");
        $result = $stmt->execute([
            $data['hotel_id'],
            $data['guest_name'],
            $data['guest_count'],
            $data['check_in'],
            $data['check_out'],
            $id
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['success' => false, 'error' => 'Booking not found']);
        }
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function deleteBooking($id) {
    global $pdo;
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Check if booking exists first
        $checkStmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ?");
        $checkStmt->execute([$id]);
        
        if ($checkStmt->rowCount() === 0) {
            $pdo->rollBack();
            header('HTTP/1.1 404 Not Found');
            echo json_encode([
                'success' => false, 
                'message' => 'Booking not found',
                'data' => null
            ]);
            return;
        }
        
        // Proceed with deletion
        $deleteStmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        $deleteStmt->execute([$id]);
        
        // Commit transaction
        $pdo->commit();
        
        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Booking successfully deleted',
            'data' => [
                'id' => $id
            ]
        ]);
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete booking',
            'error' => $e->getMessage()
        ]);
    }
}
