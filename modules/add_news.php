<?php
include '../config/db_connect.php';

// Function to generate a URL-friendly slug
function generateSlug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return trim($slug, '-');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize input data
    $title = trim($_POST['title']);
    $slug = generateSlug($_POST['slug'] ?? $title);
    $content = trim($_POST['content']);
    $author = trim($_POST['author']);
    $category = $_POST['category'];
    $video_url = trim($_POST['video_url']);
    $tags = trim($_POST['tags']);
    $status = $_POST['status'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $comments_enabled = isset($_POST['comments_enabled']) ? 1 : 0;

    // Handle image upload
    $image = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadsDir = "../assets/uploads/news_images/";
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true); // Create directory if it doesn't exist
        }
        $image = uniqid() . "_" . basename($_FILES['image']['name']);
        $imagePath = $uploadsDir . $image;
        move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
    } else {
        die("Error uploading image.");
    }

    // Validate required fields
    if (empty($title) || empty($slug) || empty($content) || empty($category)) {
        echo "Please fill out all required fields!";
    } else {
        // Insert into database
        $sql = "INSERT INTO news (title, slug, content, author, category, status, image_url, video_url, tags, is_featured, comments_enabled) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssssssiii", $title, $slug, $content, $author, $category, $status, $image, $video_url, $tags, $is_featured, $comments_enabled);

            if ($stmt->execute()) {
                // Create a new file for the article in the /news directory
                $newsDir = "../news/";
                if (!is_dir($newsDir)) {
                    mkdir($newsDir, 0755, true);
                }
                $filePath = $newsDir . $slug . ".php";

                $newsPageContent = "<?php\n";
                $newsPageContent .= "\$nslug = '$slug';\n";
                $newsPageContent .= "include '../templates/news_template.php';\n";

                if (file_put_contents($filePath, $newsPageContent)) {
                    echo "News added successfully! File created at $filePath";
                } else {
                    echo "News added to database, but file creation failed.";
                }
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add News</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <h1>Add New News</h1>
    <form action="add_news.php" method="POST" enctype="multipart/form-data">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" required>

        <label for="slug">Slug:</label>
        <input type="text" name="slug" id="slug" placeholder="news-slug" required>

        <label for="content">Content:</label>
        <textarea name="content" id="content" rows="10" required></textarea>

        <label for="author">Author:</label>
        <input type="text" name="author" id="author">

        <label for="category">Category:</label>
        <select name="category" id="category" required>
            <option value="News">News</option>
            <option value="Live">Live</option>
            <option value="Review">Review</option>
            <option value="Schedule">Schedule</option>
            <option value="Event">Event</option>
            <option value="Announcement">Announcement</option>
            <option value="Highlights">Highlights</option>
            <option value="Interviews">Interviews</option>
            <option value="Opinion">Opinion</option>
        </select>

        <label for="image">Image:</label>
        <input type="file" name="image" id="image" accept="image/*" required>

        <label for="video_url">Video URL:</label>
        <input type="url" name="video_url" id="video_url" placeholder="https://example.com">

        <label for="tags">Tags:</label>
        <input type="text" name="tags" id="tags" placeholder="tag1, tag2, tag3">

        <label for="status">Status:</label>
        <select name="status" id="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>

        <label for="is_featured">Feature:</label>
        <input type="checkbox" name="is_featured" id="is_featured">

        <label for="comments_enabled">Enable Comments:</label>
        <input type="checkbox" name="comments_enabled" id="comments_enabled" checked>

        <button type="submit">Submit</button>
    </form>
</body>
</html>
