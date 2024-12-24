<?php
include __DIR__ . '/../config/db_connect.php';
?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News - MMA Organization</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/news.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="news-container">
        <h1>Latest News</h1>
        <div id="news-content">
            <!-- აქ იტვირთება პირველი სიახლეები -->
        </div>

        <div class="pagination">
            <button id="load-more" class="btn" data-offset="10">Load More</button>
            <button id="back" class="btn" style="display: none;">Back</button>
            <button id="back-to-top" class="btn" style="display: none;"> <img src="/mma_organization/assets/uploads/icon/back.svg" alt="TOP"> </button>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>


    <script>
        $(document).ready(function() {
            // Back to Top visibility
            $(window).scroll(function() {
                if ($(this).scrollTop() > 300) {
                    $('#back-to-top').fadeIn();
                } else {
                    $('#back-to-top').fadeOut();
                }
            });

            // Back to Top functionality
            $('#back-to-top').click(function() {
                $('html, body').animate({ scrollTop: 0 }, 800); // Smooth scroll to top
            });

            // Load More functionality
            function loadNews(offset) {
                $.ajax({
                    url: '../includes/load_news.php',
                    type: 'POST',
                    data: { offset: offset },
                    success: function(data) {
                        $('#news-content').html(data); // Update content with new news
                        $('#load-more').data('offset', offset + 10); // Update offset

                        // Show or hide "Back" button
                        if (offset > 0) {
                            $('#back').show();
                        } else {
                            $('#back').hide();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        alert('Failed to load news. Please check the console for more information.');
                    }
                });
            }

            // Initial load
            loadNews(0);

            // Load More button functionality
            $('#load-more').click(function() {
                const offset = $(this).data('offset');
                loadNews(offset);
            });

            // Back button functionality
            $('#back').click(function() {
                loadNews(0); // Reload initial 10 news items
                $('#load-more').data('offset', 10); // Reset Load More offset
            });
        });

    </script>
</body>
</html>
