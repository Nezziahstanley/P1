<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'session.php';

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/htdocs/library-system/php_errors.log');

$action = $_GET['action'] ?? '';

if (!isset($conn) || $conn->connect_error) {
    error_log("Database connection failed: " . ($conn ? $conn->connect_error : "No connection object"));
    echo json_encode(['error' => 'Database connection not established']);
    exit;
}

switch ($action) {
    case 'register':
        error_log("Processing register action");
        $fullName = $_POST['fullName'] ?? '';
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $userType = $_POST['userType'] ?? '';
        $role = $_POST['role'] ?? null;
        $level = $_POST['level'] ?? null;
        error_log("Register input: fullName=$fullName, username=$username, email=$email, userType=$userType");
        if (!$fullName || !$username || !$email || !$password || !$userType) {
            error_log("Register error: Missing required fields");
            echo json_encode(['error' => 'All fields are required']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Register error: Invalid email format");
            echo json_encode(['error' => 'Invalid email format']);
            exit;
        }
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if (!$stmt) {
            error_log("Register prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            error_log("Register error: Username or email exists");
            echo json_encode(['error' => 'Username or email already exists']);
            exit;
        }
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (fullName, username, email, password, userType, role, level, approved) VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)");
        if (!$stmt) {
            error_log("Register insert prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("sssssss", $fullName, $username, $email, $hashedPassword, $userType, $role, $level);
        if ($stmt->execute()) {
            error_log("Register success: User $username registered");
            echo json_encode(['success' => true, 'message' => 'Registration successful']);
        } else {
            error_log("Register error: Failed to insert user - " . $conn->error);
            echo json_encode(['error' => 'Registration failed: ' . $conn->error]);
        }
        break;

    case 'login':
        error_log("Processing login action");
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        error_log("Login input: username=$username");
        if (!$username || !$password) {
            error_log("Login error: Missing username or password");
            echo json_encode(['error' => 'Username and password are required']);
            exit;
        }
        $stmt = $conn->prepare("SELECT id, password, userType, approved FROM users WHERE username = ?");
        if (!$stmt) {
            error_log("Login prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            error_log("Login error: Invalid username");
            echo json_encode(['error' => 'Invalid username or password']);
            exit;
        }
        $user = $result->fetch_assoc();
        if (!$user['approved']) {
            error_log("Login error: Account not approved for $username");
            echo json_encode(['error' => 'Account not approved. Contact the librarian.']);
            exit;
        }
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['userId'] = $user['id'];
            $_SESSION['userType'] = $user['userType'];
            error_log("Login success: User $username, userType={$user['userType']}");
            echo json_encode(['success' => true, 'userType' => $user['userType']]);
        } else {
            error_log("Login error: Invalid password for $username");
            echo json_encode(['error' => 'Invalid username or password']);
        }
        break;

    case 'searchBooks':
        error_log("Processing searchBooks action");
        restrictAccess(['student', 'staff', 'librarian']);
        $query = $_GET['query'] ?? '';
        error_log("Search books query: $query");
        $sql = "SELECT b.id, b.title, b.author, b.available, 
                (SELECT COUNT(*) FROM reservations r WHERE r.bookId = b.id AND r.status = 'approved') as activeReservations 
                FROM books b 
                WHERE b.title LIKE ? OR b.author LIKE ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Search books prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $likeQuery = "%$query%";
        $stmt->bind_param("ss", $likeQuery, $likeQuery);
        $stmt->execute();
        $books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        error_log("Search books success: Retrieved " . count($books) . " books");
        echo json_encode($books);
        break;

    case 'borrowBook':
        restrictAccess(['student', 'staff', 'librarian']);
        $userId = getUserId();
        $input = json_decode(file_get_contents('php://input'), true);
        $bookId = $input['bookId'] ?? '';
        error_log("Borrow book attempt: userId=$userId, bookId=$bookId");
        if (!$bookId || !is_numeric($bookId) || $bookId <= 0) {
            error_log("Borrow book error: Invalid or missing bookId: " . ($bookId ?: 'empty'));
            echo json_encode(['error' => 'Book ID is required and must be a positive integer']);
            exit;
        }
        $bookId = (int)$bookId;
        $stmt = $conn->prepare("SELECT available, (SELECT COUNT(*) FROM reservations WHERE bookId = ? AND status = 'approved' AND userId != ?) as activeReservations FROM books WHERE id = ?");
        if (!$stmt) {
            error_log("Borrow book prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iii", $bookId, $userId, $bookId);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();
        if (!$book) {
            error_log("Borrow book error: Book $bookId not found");
            echo json_encode(['error' => 'Book not found']);
            exit;
        }
        error_log("Borrow book check: available={$book['available']}, activeReservations={$book['activeReservations']}");
        if (!$book['available'] || $book['activeReservations'] > 0) {
            error_log("Borrow book error: Book $bookId not available");
            echo json_encode(['error' => 'Book is not available']);
            exit;
        }
        $stmt = $conn->prepare("SELECT id FROM borrowings WHERE userId = ? AND bookId = ? AND returnDate IS NULL");
        $stmt->bind_param("ii", $userId, $bookId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            error_log("Borrow book error: User $userId already borrowed book $bookId");
            echo json_encode(['error' => 'You have already borrowed this book']);
            exit;
        }
        $borrowDate = date('Y-m-d H:i:s');
        $dueDate = date('Y-m-d H:i:s', strtotime('+14 days'));
        $stmt = $conn->prepare("INSERT INTO borrowings (userId, bookId, borrowDate, dueDate) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Borrow book insert prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iiss", $userId, $bookId, $borrowDate, $dueDate);
        $stmt2 = $conn->prepare("UPDATE books SET available = FALSE, borrowerId = ? WHERE id = ?");
        $stmt2->bind_param("ii", $userId, $bookId);
        if ($stmt->execute() && $stmt2->execute()) {
            error_log("Borrow book success: Book $bookId borrowed by user $userId");
            echo json_encode(['success' => true, 'message' => 'Book borrowed successfully']);
        } else {
            error_log("Borrow book error: Failed to borrow book $bookId - " . $conn->error);
            echo json_encode(['error' => 'Failed to borrow book: ' . $conn->error]);
        }
        break;

    case 'reserveBook':
        restrictAccess(['student', 'staff', 'librarian']);
        $userId = getUserId();
        $input = json_decode(file_get_contents('php://input'), true);
        $bookId = $input['bookId'] ?? '';
        error_log("Reserve book attempt: userId=$userId, bookId=$bookId");
        if (!$bookId || !is_numeric($bookId) || $bookId <= 0) {
            error_log("Reserve book error: Invalid or missing bookId: " . ($bookId ?: 'empty'));
            echo json_encode(['error' => 'Book ID is required and must be a positive integer']);
            exit;
        }
        $bookId = (int)$bookId;
        $stmt = $conn->prepare("SELECT available, (SELECT COUNT(*) FROM reservations WHERE bookId = ? AND status = 'approved' AND userId != ?) as activeReservations FROM books WHERE id = ?");
        if (!$stmt) {
            error_log("Reserve book prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iii", $bookId, $userId, $bookId);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();
        if (!$book) {
            error_log("Reserve book error: Book $bookId not found");
            echo json_encode(['error' => 'Book not found']);
            exit;
        }
        error_log("Reserve book check: available={$book['available']}, activeReservations={$book['activeReservations']}");
        if (!$book['available'] || $book['activeReservations'] > 0) {
            error_log("Reserve book error: Book $bookId not available");
            echo json_encode(['error' => 'Book is not available']);
            exit;
        }
        $stmt = $conn->prepare("SELECT id FROM reservations WHERE userId = ? AND bookId = ? AND status = 'pending'");
        $stmt->bind_param("ii", $userId, $bookId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            error_log("Reserve book error: User $userId already reserved book $bookId");
            echo json_encode(['error' => 'You have already reserved this book']);
            exit;
        }
        $reservationDate = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO reservations (userId, bookId, reservationDate, status) VALUES (?, ?, ?, 'pending')");
        if (!$stmt) {
            error_log("Reserve book insert prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iis", $userId, $bookId, $reservationDate);
        if ($stmt->execute()) {
            error_log("Reserve book success: Book $bookId reserved by user $userId");
            echo json_encode(['success' => true, 'message' => 'Book reserved successfully, pending approval']);
        } else {
            error_log("Reserve book error: Failed to reserve book $bookId - " . $conn->error);
            echo json_encode(['error' => 'Failed to reserve book: ' . $conn->error]);
        }
        break;

    case 'getUserBorrowings':
        restrictAccess(['student', 'staff', 'librarian']);
        $userId = getUserId();
        error_log("Get user borrowings for userId=$userId");
        $stmt = $conn->prepare("SELECT b.id, u.username, b.bookId, bk.title, b.borrowDate, b.dueDate 
                                FROM borrowings b 
                                JOIN users u ON b.userId = u.id 
                                JOIN books bk ON b.bookId = bk.id 
                                WHERE b.userId = ? AND b.returnDate IS NULL");
        if (!$stmt) {
            error_log("Get user borrowings prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $borrowings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        error_log("Get user borrowings success: Retrieved " . count($borrowings) . " borrowings for user $userId");
        echo json_encode($borrowings);
        break;

    case 'updateProfile':
        restrictAccess(['student', 'staff', 'librarian']);
        $userId = getUserId();
        $fullName = $_POST['fullName'] ?? '';
        $email = $_POST['email'] ?? '';
        if (!$fullName || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Update profile error: Invalid input for user $userId");
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }
        $profilePicture = null;
        if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            $ext = pathinfo($_FILES['profilePicture']['name'], PATHINFO_EXTENSION);
            $profilePicture = $uploadDir . 'user_' . $userId . '.' . $ext;
            if (!move_uploaded_file($_FILES['profilePicture']['tmp_name'], $profilePicture)) {
                error_log("Update profile error: Failed to upload profile picture for user $userId");
                echo json_encode(['error' => 'Failed to upload profile picture']);
                exit;
            }
        }
        $stmt = $conn->prepare("UPDATE users SET fullName = ?, email = ?, profilePicture = COALESCE(?, profilePicture) WHERE id = ?");
        if (!$stmt) {
            error_log("Update profile prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("sssi", $fullName, $email, $profilePicture, $userId);
        if ($stmt->execute()) {
            error_log("Update profile success: User $userId updated");
            echo json_encode(['success' => true, 'message' => 'Profile updated']);
        } else {
            error_log("Update profile error: Failed to update user $userId - " . $conn->error);
            echo json_encode(['error' => 'Failed to update profile']);
        }
        break;

    case 'getProfile':
        restrictAccess(['student', 'staff', 'librarian']);
        $userId = getUserId();
        $stmt = $conn->prepare("SELECT fullName, username, email, userType, profilePicture, role, level FROM users WHERE id = ?");
        if (!$stmt) {
            error_log("Get profile prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        error_log("Get profile success: User $userId");
        echo json_encode($user);
        break;

    case 'getUsers':
        restrictAccess(['librarian']);
        $stmt = $conn->prepare("SELECT id, fullName, username, email, userType, profilePicture, role, level FROM users");
        if (!$stmt) {
            error_log("Get users prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->execute();
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        error_log("Get users success: Retrieved " . count($users) . " users");
        echo json_encode($users);
        break;

    case 'getBorrowings':
        restrictAccess(['librarian']);
        $stmt = $conn->prepare("SELECT b.id, u.username, b.bookId, bk.title, b.borrowDate, b.dueDate FROM borrowings b JOIN users u ON b.userId = u.id JOIN books bk ON b.bookId = bk.id WHERE b.returnDate IS NULL");
        if (!$stmt) {
            error_log("Get borrowings prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->execute();
        $borrowings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        error_log("Get borrowings success: Retrieved " . count($borrowings) . " borrowings");
        echo json_encode($borrowings);
        break;

    case 'getReservations':
        restrictAccess(['librarian']);
        $stmt = $conn->prepare("SELECT r.id, u.username, r.bookId, bk.title, r.reservationDate, r.status FROM reservations r JOIN users u ON r.userId = u.id JOIN books bk ON r.bookId = bk.id");
        if (!$stmt) {
            error_log("Get reservations prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->execute();
        $reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        error_log("Get reservations success: Retrieved " . count($reservations) . " reservations");
        echo json_encode($reservations);
        break;

    case 'returnBook':
        restrictAccess(['librarian']);
        $input = json_decode(file_get_contents('php://input'), true);
        $borrowingId = $input['borrowingId'] ?? '';
        $bookId = $input['bookId'] ?? '';
        error_log("Return book attempt: borrowingId=$borrowingId, bookId=$bookId");
        if (!$borrowingId || !is_numeric($borrowingId) || $borrowingId <= 0 || !$bookId || !is_numeric($bookId) || $bookId <= 0) {
            error_log("Return book error: Invalid or missing borrowingId or bookId");
            echo json_encode(['error' => 'Borrowing ID and Book ID are required and must be positive integers']);
            exit;
        }
        $borrowingId = (int)$borrowingId;
        $bookId = (int)$bookId;
        $returnDate = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE borrowings SET returnDate = ? WHERE id = ? AND returnDate IS NULL");
        if (!$stmt) {
            error_log("Return book prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("si", $returnDate, $borrowingId);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt2 = $conn->prepare("UPDATE books SET available = TRUE, borrowerId = NULL WHERE id = ?");
        if (!$stmt2) {
            error_log("Return book prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt2->bind_param("i", $bookId);
        $stmt2->execute();
        $affectedRows2 = $stmt2->affected_rows;
        if ($affectedRows > 0 && $affectedRows2 > 0) {
            error_log("Return book success: Book $bookId returned, borrowing $borrowingId");
            echo json_encode(['success' => true, 'message' => 'Book returned successfully']);
        } else {
            error_log("Return book error: No rows affected for borrowing $borrowingId or book $bookId");
            echo json_encode(['error' => 'Failed to return book: Invalid borrowing or book ID']);
        }
        break;

    case 'approveReservation':
        restrictAccess(['librarian']);
        $input = json_decode(file_get_contents('php://input'), true);
        $reservationId = $input['reservationId'] ?? '';
        error_log("Approve reservation attempt: reservationId=$reservationId");
        if (!$reservationId || !is_numeric($reservationId) || $reservationId <= 0) {
            error_log("Approve reservation error: Invalid or missing reservationId");
            echo json_encode(['error' => 'Reservation ID is required and must be a positive integer']);
            exit;
        }
        $reservationId = (int)$reservationId;
        $stmt = $conn->prepare("UPDATE reservations SET status = 'approved' WHERE id = ? AND status = 'pending'");
        if (!$stmt) {
            error_log("Approve reservation prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            error_log("Approve reservation success: Reservation $reservationId approved");
            echo json_encode(['success' => true, 'message' => 'Reservation approved']);
        } else {
            error_log("Approve reservation error: No rows affected for reservation $reservationId");
            echo json_encode(['error' => 'Failed to approve reservation: Invalid or non-pending reservation']);
        }
        break;

    case 'cancelReservation':
        restrictAccess(['librarian']);
        $input = json_decode(file_get_contents('php://input'), true);
        $reservationId = $input['reservationId'] ?? '';
        error_log("Cancel reservation attempt: reservationId=$reservationId");
        if (!$reservationId || !is_numeric($reservationId) || $reservationId <= 0) {
            error_log("Cancel reservation error: Invalid or missing reservationId");
            echo json_encode(['error' => 'Reservation ID is required and must be a positive integer']);
            exit;
        }
        $reservationId = (int)$reservationId;
        $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ? AND status = 'pending'");
        if (!$stmt) {
            error_log("Cancel reservation prepare error: " . $conn->error);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            error_log("Cancel reservation success: Reservation $reservationId cancelled");
            echo json_encode(['success' => true, 'message' => 'Reservation cancelled']);
        } else {
            error_log("Cancel reservation error: No rows affected for reservation $reservationId");
            echo json_encode(['error' => 'Failed to cancel reservation: Invalid or non-pending reservation']);
        }
        break;

    default:
        error_log("Invalid action: $action");
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>