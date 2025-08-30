<?php
session_start();

function restrictAccess($allowedRoles) {
    if (!isset($_SESSION['userType']) || !in_array($_SESSION['userType'], $allowedRoles)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized access']);
        exit;
    }
}

function getUserId() {
    if (!isset($_SESSION['userId'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'User not logged in']);
        exit;
    }
    return $_SESSION['userId'];
}
?>