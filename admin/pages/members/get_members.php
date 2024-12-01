<?php
header('Content-Type: application/json');

try {
    // Database connection
    $db = new PDO('mysql:host=localhost;dbname=gym_managementdb', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to join the necessary tables
    $query = "
    SELECT 
        u.id AS user_id,
        u.username,
        pd.first_name,
        pd.middle_name,
        pd.last_name,
        pd.sex,
        pd.birthdate,
        pd.phone_number,
        COALESCE(pp.photo_path, NULL) AS photo_path,
        msp.plan_name,
        ms.start_date AS membership_start,
        ms.end_date AS membership_end,
        ms.total_amount AS membership_amount,
        st.status_name AS membership_status,
        GROUP_CONCAT(DISTINCT 
            CONCAT(
                prg.program_name, 
                ' (Coach: ', 
                coach.username, 
                ', Start: ', 
                ps.start_date, 
                ', End: ', 
                ps.end_date, 
                ', Status: ', 
                ps.status, 
                ', Paid: ', 
                IF(ps.is_paid, 'Yes', 'No')
            )
            SEPARATOR '; '
        ) AS detailed_program_subscriptions,
        GROUP_CONCAT(DISTINCT 
            CONCAT(
                rsvc.service_name, 
                ' (', 
                rs.start_date, 
                ' to ', 
                rs.end_date, 
                ', Status: ', 
                rs.status, 
                ', Paid: ', 
                IF(rs.is_paid, 'Yes', 'No')
            )
            SEPARATOR '; '
        ) AS detailed_rental_services
    FROM users u
    INNER JOIN transactions t ON u.id = t.user_id
    INNER JOIN memberships ms ON t.id = ms.transaction_id
    LEFT JOIN personal_details pd ON u.id = pd.user_id
    LEFT JOIN profile_photos pp ON u.id = pp.user_id AND pp.is_active = 1
    LEFT JOIN membership_plans msp ON ms.membership_plan_id = msp.id
    LEFT JOIN status_types st ON ms.status_id = st.id
    LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id
    LEFT JOIN programs prg ON ps.program_id = prg.id
    LEFT JOIN users coach ON ps.coach_id = coach.id
    LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id
    LEFT JOIN rental_services rsvc ON rs.rental_service_id = rsvc.id
    WHERE u.is_active = 1
    GROUP BY 
        u.id,
        u.username,
        pd.first_name,
        pd.middle_name,
        pd.last_name,
        pd.sex,
        pd.birthdate,
        pd.phone_number,
        pp.photo_path,
        msp.plan_name,
        ms.start_date,
        ms.end_date,
        ms.total_amount,
        st.status_name;
";

    $stmt = $db->prepare($query);
    $stmt->execute();
    // Ensure proper data structure for frontend
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($members as &$member) {
        $member['full_name'] = trim("{$member['first_name']} {$member['middle_name']} {$member['last_name']}") ?: 'N/A';
        $member['status'] = $member['membership_status'] ?: 'Unknown';
        $member['photo_path'] = $member['photo_path'] ?: 'default.jpg';
    }
    echo json_encode([
        'success' => true,
        'data' => $members
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Unexpected error: ' . $e->getMessage()
    ]);
}
?>
