<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? 'checkin';

try {
    $database = new Database();
    $pdo = $database->connect();
    
    if ($action === 'checkin') {
        $identifier = $_POST['identifier'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Modified query to check both username and first_name
        $userQuery = "SELECT u.id, u.password 
                      FROM users u 
                      JOIN transactions t ON u.id = t.user_id 
                      JOIN memberships m ON t.id = m.transaction_id 
                      JOIN personal_details pd ON u.id = pd.user_id 
                      WHERE (u.username = :identifier OR pd.first_name = :identifier)
                      AND (m.status = 'active' OR m.status = 'expiring')
                      AND m.is_paid = 1
                      AND CURRENT_DATE BETWEEN m.start_date AND m.end_date";
        
        $stmt = $pdo->prepare($userQuery);
        $stmt->execute(['identifier' => $identifier]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Handle multiple users with same first name
        $validUser = null;
        foreach ($users as $user) {
            if (password_verify($password, $user['password'])) {
                $validUser = $user;
                break;
            }
        }
        
        if (!$validUser) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid credentials or inactive membership']);
            exit;
        }

        // Check if user already exists in attendance table
        $checkExisting = "SELECT id, status FROM attendance WHERE user_id = :user_id";
        $stmt = $pdo->prepare($checkExisting);
        $stmt->execute(['user_id' => $user['id']]);
        $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRecord) {
            // Check if already checked in today
            if ($existingRecord['status'] === 'checked_in' || $existingRecord['status'] === 'checked_out') {
                echo json_encode(['status' => 'error', 'message' => 'You have already checked in for today']);
                exit;
            }

            // Update existing record
            $updateAttendance = "UPDATE attendance SET 
                                date = CURRENT_DATE(),
                                time_in = CURRENT_TIME(),
                                time_out = NULL,
                                created_at = CURRENT_TIMESTAMP,
                                status = 'checked_in'
                                WHERE id = :id";
            $stmt = $pdo->prepare($updateAttendance);
            $stmt->execute(['id' => $existingRecord['id']]);
            $attendanceId = $existingRecord['id'];
            
            // Insert check-in history
            $insertHistory = "INSERT INTO attendance_history 
                            (attendance_id, time_in, status) VALUES 
                            (:attendance_id, CURRENT_TIME(), 'checked_in')";
            $stmt = $pdo->prepare($insertHistory);
            $stmt->execute(['attendance_id' => $attendanceId]);
            
        } else {
            // Insert new attendance record
            $insertAttendance = "INSERT INTO attendance 
                                (user_id, date, time_in, status) VALUES 
                                (:user_id, CURRENT_DATE(), CURRENT_TIME(), 'checked_in')";
            $stmt = $pdo->prepare($insertAttendance);
            $stmt->execute(['user_id' => $user['id']]);
            $attendanceId = $pdo->lastInsertId();
            
            // Insert check-in history
            $insertHistory = "INSERT INTO attendance_history 
                            (attendance_id, time_in, status) VALUES 
                            (:attendance_id, CURRENT_TIME(), 'checked_in')";
            $stmt = $pdo->prepare($insertHistory);
            $stmt->execute(['attendance_id' => $attendanceId]);
        }
        
    } else if ($action === 'checkout') {
        $userId = $_POST['user_id'] ?? '';
        
        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
            exit;
        }
        
        // Get attendance record ID with proper status check
        $getAttendanceId = "SELECT id FROM attendance 
                           WHERE user_id = :user_id 
                           AND date = CURRENT_DATE()
                           AND status = 'checked_in'
                           LIMIT 1";
        $stmt = $pdo->prepare($getAttendanceId);
        $stmt->execute(['user_id' => $userId]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$attendance) {
            echo json_encode(['status' => 'error', 'message' => 'No active check-in found for this user']);
            exit;
        }
        
        $attendanceId = $attendance['id'];
        
        // Update attendance status
        $updateAttendance = "UPDATE attendance SET 
                            time_out = CURRENT_TIME(),
                            status = 'checked_out'
                            WHERE id = :attendance_id";
        $stmt = $pdo->prepare($updateAttendance);
        $stmt->execute(['attendance_id' => $attendanceId]);
        
        // Insert check-out history
        $insertHistory = "INSERT INTO attendance_history 
                        (attendance_id, time_out, status) VALUES 
                        (:attendance_id, CURRENT_TIME(), 'checked_out')";
        $stmt = $pdo->prepare($insertHistory);
        $stmt->execute(['attendance_id' => $attendanceId]);
    }
    
    // Fetch updated attendance record for the response
    $attendanceQuery = "SELECT u.id AS user_id, 
            CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) AS full_name, 
            u.username, 
            pp.photo_path, 
            a.time_in, 
            a.time_out, 
            a.status 
            FROM attendance a 
            JOIN users u ON a.user_id = u.id 
            JOIN personal_details pd ON u.id = pd.user_id 
            LEFT JOIN profile_photos pp ON u.id = pp.user_id 
            WHERE a.id = :attendance_id";
    
    $stmt = $pdo->prepare($attendanceQuery);
    $stmt->execute(['attendance_id' => $attendanceId]);
    $updatedRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'message' => $action === 'checkin' ? 'Check-in successful' : 'Check-out successful',
        'data' => $updatedRecord
    ]);
    
} catch (Exception $e) {
    error_log("Attendance Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}