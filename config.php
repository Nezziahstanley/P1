<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'library_db';

$conn = new mysqli($host, $username, $password);

// Check if the server is reachable
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Check if the database exists
$conn->select_db($database);
if ($conn->errno) {
    $createDb = $conn->query("CREATE DATABASE IF NOT EXISTS $database");
    if (!$createDb) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to create database: ' . $conn->error]);
        exit;
    }
    $conn->select_db($database);
}

// Verify tables exist
$tables = ['users', 'books', 'borrowings', 'reservations'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $schema = '';
        if ($table === 'users') {
            $schema = "
                CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    fullName VARCHAR(255) NOT NULL,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    userType ENUM('student', 'staff', 'librarian') NOT NULL,
                    approved BOOLEAN DEFAULT TRUE,
                    profilePicture VARCHAR(255),
                    role VARCHAR(100),
                    level ENUM('ND1', 'ND2', 'HND1', 'HND2')
                )";
        } elseif ($table === 'books') {
            $schema = "
                CREATE TABLE books (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    author VARCHAR(255) NOT NULL,
                    isbn VARCHAR(13),
                    available BOOLEAN DEFAULT TRUE,
                    publication_year INT,
                    category VARCHAR(100),
                    borrowerId INT,
                    FOREIGN KEY (borrowerId) REFERENCES users(id)
                )";
        } elseif ($table === 'borrowings') {
            $schema = "
                CREATE TABLE borrowings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    userId INT,
                    bookId INT,
                    borrowDate DATETIME,
                    returnDate DATETIME,
                    dueDate DATETIME,
                    FOREIGN KEY (userId) REFERENCES users(id),
                    FOREIGN KEY (bookId) REFERENCES books(id)
                )";
        } elseif ($table === 'reservations') {
            $schema = "
                CREATE TABLE reservations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    userId INT,
                    bookId INT,
                    reservationDate DATETIME,
                    status ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
                    FOREIGN KEY (userId) REFERENCES users(id),
                    FOREIGN KEY (bookId) REFERENCES books(id)
                )";
        }
        if (!$conn->query($schema)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => "Failed to create $table table: " . $conn->error]);
            exit;
        }
    }
}

// Ensure uploads directory exists
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
?>