<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();

// Get the latest 5 users to see exactly what is in the mobile column
$stmt = $db->query("SELECT id, name, mobile FROM users ORDER BY id DESC LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Latest 5 Users in Database:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Mobile (Exact value)</th><th>Length</th></tr>";
foreach ($users as $u) {
    $mobile = $u['mobile'];
    $length = strlen($mobile);
    echo "<tr>
            <td>{$u['id']}</td>
            <td>{$u['name']}</td>
            <td>'{$mobile}'</td>
            <td>{$length} chars</td>
          </tr>";
}
echo "</table>";

echo "<h3>Test specific number: 6296488643</h3>";
$testStmt = $db->prepare("SELECT * FROM users WHERE mobile=?");
$testStmt->execute(['6296488643']);
$found = $testStmt->fetch(PDO::FETCH_ASSOC);

if ($found) {
    echo "<p style='color:green;'>SUCCESS! Found user: " . $found['name'] . "</p>";
} else {
    echo "<p style='color:red;'>FAILED! User not found with exact string '6296488643'.</p>";
}
?>
