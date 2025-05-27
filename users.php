<?php
require 'getUsers.php';
include_once 'header.php';
$usersResponse = fetchUsers();
// Group key
$groupKey = 'Surname';
$grouped = [];

if ($usersResponse['status'] === 'success' && !empty($usersResponse['data']['users'])) {
    foreach ($usersResponse['data']['users'] as $user) {
        $key = $user[$groupKey] ?? 'Uncategorized';
        $grouped[$key][] = $user;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Users by <?= htmlspecialchars($groupKey) ?></title>
</head>
<body>
    <h1>Users Grouped by <?= htmlspecialchars($groupKey) ?></h1>

    <?php if (!empty($grouped)): ?>
        <?php foreach ($grouped as $group => $users): ?>
            <div class="category-header">
                <h2><?= htmlspecialchars($group) ?></h2>
            </div>
            <div class="user-grid">
            <?php foreach ($users as $user): ?>
                <div class="user-card">
                    <div class="info">
                        <div class="name"><?= htmlspecialchars($user['Name'] ?? 'N/A') ?> <?= htmlspecialchars($user['Surname'] ?? '') ?></div>
                        <div class="email"><?= htmlspecialchars($user['Email'] ?? '') ?></div>
                        <div class="phone"><?= htmlspecialchars($user['Phone_Number'] ?? '') ?></div>
                        <div class="api-key">API Key: <?= htmlspecialchars($user['API_Key'] ?? '') ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Error loading users: <?= htmlspecialchars($usersResponse['message'] ?? 'Unknown error') ?></p>
    <?php endif; ?>
</body>
</html>

<?php include_once 'footer.php'; ?>
?>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f9f9f9;
        margin: 0;
        padding: 20px;
        color: #333;
    }

    h1 {
        margin-bottom: 20px;
        font-size: 28px;
        color: #1890ff;
    }

    .category-header {
        background-color: #e6f7ff;
        padding: 12px 16px;
        margin-top: 40px;
        border-left: 5px solid #1890ff;
        border-radius: 4px;
    }

    .category-header h2 {
        margin: 0;
        font-size: 22px;
        color: #0050b3;
    }

    .user-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 20px;
    }

    .user-card {
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        width: calc(33.333% - 20px);
        padding: 16px;
        box-sizing: border-box;
        transition: box-shadow 0.3s ease;
    }

    .user-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .user-card .info {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .user-card .name {
        font-size: 18px;
        font-weight: 600;
        color: #262626;
    }

    .user-card .email,
    .user-card .phone,
    .user-card .api-key {
        font-size: 14px;
        color: #595959;
        word-break: break-word;
    }

    @media (max-width: 992px) {
        .user-card {
            width: calc(50% - 20px);
        }
    }

    @media (max-width: 600px) {
        .user-card {
            width: 100%;
        }
    }
</style>
