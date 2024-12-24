<?php
include '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    
    $stmt = $conn->prepare("INSERT INTO weight_classes (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->close();
    echo "Weight class added!";
}
?>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<form method="post">
    <label for="name">Weight Class Name:</label>
    <input type="text" id="name" name="name" required>
    <button type="submit">Add Weight Class</button>
</form>
