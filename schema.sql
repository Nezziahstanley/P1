-- Create the database
CREATE DATABASE IF NOT EXISTS library_db;

-- Select the database
USE library_db;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullName VARCHAR(255) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    userType ENUM('student', 'staff', 'librarian') NOT NULL,
    approved BOOLEAN DEFAULT TRUE,
    profilePicture VARCHAR(255),
    role VARCHAR(100), -- For staff (e.g., Lecturer, Technician, Administrative Staff)
    level ENUM('ND1', 'ND2', 'HND1', 'HND2') -- For students (ND1, ND2, HND1, HND2)
);

-- Create books table
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
);

-- Create borrowings table
CREATE TABLE borrowings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT,
    bookId INT,
    borrowDate DATETIME,
    returnDate DATETIME,
    dueDate DATETIME,
    FOREIGN KEY (userId) REFERENCES users(id),
    FOREIGN KEY (bookId) REFERENCES books(id)
);

-- Create reservations table
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT,
    bookId INT,
    reservationDate DATETIME,
    status ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (userId) REFERENCES users(id),
    FOREIGN KEY (bookId) REFERENCES books(id)
);

-- Create fines table
CREATE TABLE fines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT,
    borrowingId INT,
    amount DECIMAL(10,2),
    paid BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (userId) REFERENCES users(id),
    FOREIGN KEY (borrowingId) REFERENCES borrowings(id)
);

-- Create announcements table
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    librarianId INT,
    userId INT,
    message TEXT NOT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    `isRead` TINYINT(1) DEFAULT FALSE,
    FOREIGN KEY (librarianId) REFERENCES users(id),
    FOREIGN KEY (userId) REFERENCES users(id)
);