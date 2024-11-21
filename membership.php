<?php
// Database connection configuration
$host = 'localhost';
$dbname = 'gym_managementdb';
$username = 'root';
$password = '';

try {
    // Create a PDO database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare SQL query to join personal details, users, memberships, and profile photos
    $sql = "
    SELECT 
        pd.id AS personal_detail_id,
        pd.first_name,
        pd.middle_name,
        pd.last_name,
        pd.sex,
        pd.birthdate,
        pd.phone_number,
        u.username,
        r.role_name,
        pp.photo_path,
        m.start_date AS membership_start,
        m.end_date AS membership_end,
        mp.plan_name,
        mp.plan_type
    FROM 
        personal_details pd
    LEFT JOIN 
        users u ON pd.user_id = u.id
    LEFT JOIN 
        roles r ON u.role_id = r.id
    LEFT JOIN 
        profile_photos pp ON pd.user_id = pp.user_id AND pp.is_active = TRUE
    LEFT JOIN 
        memberships m ON pd.user_id = m.user_id
    LEFT JOIN 
        membership_plans mp ON m.membership_plan_id = mp.id
    ORDER BY 
        pd.last_name, pd.first_name
    ";

    // Execute the query
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .profile-photo {
            max-width: 100px;
            max-height: 100px;
        }
    </style>
</head>
<body>
    <h1>User Details</h1>
    <table>
        <thead>
            <tr>
                <th>Profile Photo</th>
                <th>Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Sex</th>
                <th>Birthdate</th>
                <th>Phone Number</th>
                <th>Membership Plan</th>
                <th>Membership Period</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td>
                    <?php if (!empty($user['photo_path'])): ?>
                        <img src="<?php echo htmlspecialchars($user['photo_path']); ?>" alt="Profile Photo" class="profile-photo">
                    <?php else: ?>
                        No Photo
                    <?php endif; ?>
                </td>
                <td>
                    <?php 
                    $fullName = trim(
                        $user['first_name'] . 
                        (!empty($user['middle_name']) ? ' ' . $user['middle_name'] : '') . 
                        ' ' . $user['last_name']
                    );
                    echo htmlspecialchars($fullName); 
                    ?>
                </td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                <td><?php echo htmlspecialchars($user['sex']); ?></td>
                <td><?php echo htmlspecialchars($user['birthdate']); ?></td>
                <td><?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($user['plan_name'] ?? 'No Membership'); ?></td>
                <td>
                    <?php 
                    if (!empty($user['membership_start']) && !empty($user['membership_end'])):
                        echo htmlspecialchars($user['membership_start'] . ' to ' . $user['membership_end']);
                    else:
                        echo 'N/A';
                    endif; 
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>