<?php
require_once 'config.php';

class AttendanceSystem {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getMembers($searchTerm = '') {
        $query = "SELECT 
            u.id AS user_id,
            CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) AS full_name,
            u.username,
            pp.photo_path,
            a.time_in,
            a.time_out,
            a.status,
            a.date
        FROM personal_details pd
        JOIN users u ON pd.user_id = u.id
        LEFT JOIN profile_photos pp ON u.id = pp.user_id
        LEFT JOIN attendance a ON u.id = a.user_id 
            AND a.date = CURRENT_DATE()
            AND a.status = 'checked_in'
        JOIN transactions t ON u.id = t.user_id AND t.status = 'confirmed'
        JOIN memberships m ON t.id = m.transaction_id AND m.status != 'expired'
        WHERE u.role_id = (SELECT id FROM roles WHERE role_name = 'member')
            AND u.is_active = 1
            AND m.is_paid = 1";

        if ($searchTerm) {
            $query .= " AND (pd.first_name LIKE :search 
                       OR pd.last_name LIKE :search 
                       OR u.username LIKE :search)";
        }

        $query .= " GROUP BY u.id, full_name, u.username, pp.photo_path, a.time_in, a.time_out, a.status, a.date
                    ORDER BY a.time_in DESC";

        $stmt = $this->pdo->prepare($query);
        
        if ($searchTerm) {
            $searchTerm = "%$searchTerm%";
            $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Attendance System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Member Attendance</h1>
                <div class="relative">
                    <input type="text" 
                           id="searchInput" 
                           class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                           placeholder="Search members...">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-in Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        $attendance = new AttendanceSystem($pdo);
                        $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
                        $members = $attendance->getMembers($searchTerm);
                        
                        foreach ($members as $member):
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <img src="<?= htmlspecialchars($member['photo_path'] ?? 'default-avatar.png') ?>" 
                                     alt="Profile" 
                                     class="h-10 w-10 rounded-full object-cover">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($member['full_name']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?= htmlspecialchars($member['username']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <?= $member['time_in'] ? date('h:i A', strtotime($member['time_in'])) : 'Not checked in' ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $member['status'] === 'checked_in' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= $member['status'] ?? 'Not checked in' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('search', searchTerm);
        window.location.href = currentUrl.toString();
    });
    </script>
</body>
</html>