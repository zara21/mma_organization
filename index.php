<?php include __DIR__ . '/config/db_connect.php';
 
include  __DIR__ . '/includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMA Organization</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .ticket-button {
            position: absolute;
            bottom: 20px; /* ბანერის ქვედა კიდიდან 20px */
            left: 20px;   /* ბანერის მარცხენა კიდიდან 20px */
            text-decoration: none;
            background: #D20A0A;
            border: 1px solid #D20A0A;
            border-radius: 6px;
            box-shadow: rgba(0, 0, 0, 0.1) 1px 2px 4px;
            box-sizing: border-box;
            color: #FFFFFF;
            cursor: pointer;
            display: inline-block;
            font-family: nunito,roboto,proxima-nova,"proxima nova",sans-serif;
            font-size: 16px;
            font-weight: 800;
            line-height: 16px;
            min-height: 40px;
            outline: 0;
            padding: 20px 40px;
            text-align: center;
            text-rendering: geometricprecision;
            text-transform: none;
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
            vertical-align: middle;
        }

        .ticket-button:hover{
            background-color: #FFFFFF;
            color: #D20A0A;
        }
        .ticket-button:active {
            background-color: #d04028;
        }

        .ticket-button:active {
        opacity: .5;
        }


    </style>


</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <!-- Main Event Banner Section -->
        <?php
            // Fetch the nearest upcoming event
            $stmt = $conn->prepare("
                SELECT e.event_id, e.title,  e.e_name, e.event_date, e.banner, e.ticket_link,
                    REPLACE(REPLACE(REPLACE(LOWER(e.title), ' ', '-'), '\"', ''), '''', '') AS slug
                FROM events e
                WHERE e.event_date > NOW()
                ORDER BY e.event_date ASC
                LIMIT 1
            ");
            $stmt->execute();
            $event = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $bannerPath = !empty($event['banner']) && file_exists("assets/uploads/banners/{$event['banner']}")
                ? "assets/uploads/banners/{$event['banner']}"
                : 'images/default-banner.jpg';

            $eventName = htmlspecialchars($event['e_name']); // Title for display

            $eventTitle = htmlspecialchars($event['title']); // Title for display
            $eventDate = htmlspecialchars($event['event_date']); // Date for display
            $eventSlug = $event['slug']; // Raw slug for URL
            $ticketLink = !empty($event['ticket_link']) ? htmlspecialchars($event['ticket_link']) : "#"; // Default link if empty
            ?>

        <div class="main-banner">
            <a href="/mma_organization/events/<?= $eventSlug ?>.php">
                <img src="<?= $bannerPath ?>" alt="<?= $eventTitle ?> Banner" class="banner-img">
            </a>
            <div class="banner-content">
                <p class="banner-title"><?= $eventName ?></p>
                <p class="event-time"><?= $eventDate ?></p>
                <div id="countdown" class="countdown-timer">
                    <div class="time">
                        <span class="time-num" id="days"></span>
                        <span class="time-label">Days</span>
                    </div>
                    <div class="time">
                        <span class="time-num" id="hours"></span>
                        <span class="time-label">Hours</span>
                    </div>
                    <div class="time">
                        <span class="time-num" id="minutes"></span>
                        <span class="time-label">Minutes</span>
                    </div>
                    <div class="time">
                        <span class="time-num" id="seconds"></span>
                        <span class="time-label">Seconds</span>
                    </div>
                </div>
            </div>
            <a href="<?= $ticketLink ?>" class="ticket-button" target="_blank">TICKETS</a>
        </div>


        <script>
            // Countdown Timer Logic
            function updateCountdown() {
                const eventDate = new Date("<?= htmlspecialchars($event['event_date']); ?>").getTime();
                const now = new Date().getTime();
                const distance = eventDate - now;

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById("days").innerText = days;
                document.getElementById("hours").innerText = hours;
                document.getElementById("minutes").innerText = minutes;
                document.getElementById("seconds").innerText = seconds;

                if (distance < 0) {
                    clearInterval(countdownInterval);
                    document.getElementById("countdown").innerHTML = "Event Started";
                }
            }

            const countdownInterval = setInterval(updateCountdown, 1000);
            updateCountdown(); // Initial call to display countdown immediately
        </script>
    </section>


    <!-- Fighters Section -->
    <section class="fighters">
        <h2>Meet the Fighters</h2>
        <div class="fighters-grid">
            <?php
                // Get 4 random fighters
                $stmt = $pdo->query("SELECT * FROM fighters ORDER BY RAND() LIMIT 4");
                while ($fighter = $stmt->fetch()) {
                    echo "<div class='fighter-card'>";
                    $slug = strtolower(str_replace(' ', '-', $fighter['name']));

                    if (!empty($fighter['photo'])) {
                        echo "<div class='fighter-photo-container'>";
                        echo "<a href='athletes/$slug.php'>";
                        echo "<img src='assets"  . htmlspecialchars($fighter['photo']) . "' alt='" . htmlspecialchars($fighter['name']) . "' class='fighter-photo'>";
                        echo "</a>";
                        echo "</div>";
                    }
                    echo "<div class='fighter-card-info'>";
                        echo "<h3><a href='athletes/$slug.php'>" . htmlspecialchars($fighter['name']) . "</a></h3>";
                        echo "<p>" . htmlspecialchars($fighter['nickname']) . "</p>";
                        echo "<p>Record: " . htmlspecialchars($fighter['wins']) . "-" . htmlspecialchars($fighter['losses']) . "-" . htmlspecialchars($fighter['draws']) . "</p>";
                    echo "</div>";
                    
                    echo "</div>";
                }
            ?>
        </div>
        <a href="fighters.php" class="btn">View All Fighters</a>
    </section>
    <!-- Latest News Section -->
    <section class="news">
        <h2>Latest News</h2>
        <div class="row">
            <?php
                // Fetch 4 latest active news
                $stmt = $pdo->query("SELECT * FROM news WHERE status = 'active' AND category = 'News' ORDER BY publish_date DESC LIMIT 4");
                while ($news = $stmt->fetch()) {
                    echo "<div class='col-md-3'>"; // Bootstrap column
                    echo "<div class='news-card'>";

                    $slug = htmlspecialchars($news['slug']);

                    if (!empty($news['image_url'])) {
                        echo "<div class='news-photo-container position-relative'>";
                        echo "<a href='news/$slug.php'>";
                        echo "<img src='assets/uploads/news_images/" . htmlspecialchars($news['image_url']) . "' alt='" . htmlspecialchars($news['title']) . "' class='img-fluid rounded'>";
                        echo "</a>";
                        echo "</div>";
                    }

                    echo "<div class='news-card-info mt-3'>";
                    echo "<h3 class='h5'><a href='/mma_organization/news/$slug.php'>" . htmlspecialchars($news['title']) . "</a></h3>";
                    echo "<p>" . htmlspecialchars(substr($news['content'], 0, 100)) . "...</p>";
                    echo "</div>";

                    echo "</div>";
                    echo "</div>";
                }
            ?>
        </div>
        <a href="news.php" class="btn">View All News</a>
    </section>

    <!-- Video Highlights Section -->
    <section class="highlights mt-5">
        <h2>VIDEOS</h2>
        <div class="row">
            <?php
                // Fetch 4 latest highlights
                $stmt = $pdo->query("SELECT * FROM news WHERE status = 'active' AND category = 'Highlights' ORDER BY publish_date DESC LIMIT 4");
                while ($highlight = $stmt->fetch()) {
                    echo "<div class='col-md-3'>"; // Bootstrap column
                    echo "<div class='highlight-card'>";

                    $slug = htmlspecialchars($highlight['slug']);

                    if (!empty($highlight['image_url'])) {
                        echo "<div class='highlight-photo-container position-relative'>";
                        echo "<a href='news/$slug.php'>";
                        echo "<img src='assets/uploads/news_images/" . htmlspecialchars($highlight['image_url']) . "' alt='" . htmlspecialchars($highlight['title']) . "' class='img-fluid rounded'>";
                        echo "<img src='assets/uploads/icon/video.svg' alt='Video Icon' class='video-icon'>";

                        echo "<div class='video-icon position-absolute top-50 start-50 translate-middle'>";

                        echo "<i class='bi bi-play-circle-fill text-white'></i>"; // Video icon
                        echo "</div>";
                        echo "</a>";
                        echo "</div>";
                    }

                    echo "<div class='highlight-card-info '>";

                    echo "<h3 class='h5'><a href='news/$slug.php'>" . htmlspecialchars($highlight['title']) . "</a></h3>";
                    echo "<p>" . htmlspecialchars(substr($highlight['content'], 0,50)) . "...</p>";
                    echo "</div>";

                    echo "</div>";
                    echo "</div>";
                }
            ?>
        </div>
        <a href="highlights.php" class="btn">Watch All Video</a>
    </section>



</body>
<?php include 'includes/footer.php'; ?>
</html>