<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=gold_platform', 'root', '');
    $stmt = $db->query('DESC transactions');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ': ' . $row['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
