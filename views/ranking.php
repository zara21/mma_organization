<?php
include __DIR__ . '/../config/db_connect.php';
include __DIR__ . '/../includes/header.php';

$query = "SELECT weight_class_id, name FROM weight_classes";
$weightClasses = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rankings</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <h1>Fighter Rankings</h1>
    <div class="weight-class-container">

        <?php foreach ($weightClasses as $class): ?>
            <div class="weight-class-card">
                <h2> Rankings: <?php echo htmlspecialchars($class['name']); ?></h2>

                <?php
                $stmt = $pdo->prepare("
                    SELECT fighter_id, name, nickname, ranking, photo, wins, losses, draws, slug
                    FROM fighters
                    WHERE weight_class_id = ?
                    ORDER BY 
                        CASE WHEN ranking = 0 THEN 0 ELSE 1 END, 
                        ranking ASC, name ASC
                ");
                $stmt->execute([$class['weight_class_id']]);
                $fighters = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if (count($fighters) > 0): ?>
                    

                    <div class="top-fighter">
                        <img src="../assets/<?php echo htmlspecialchars($fighters[0]['photo']); ?>" alt="Champion Fighter">
                        <div class="rame-meore-divi">
                            <div class="champion-name" >
                                
                                <?php if ($fighters[0]['ranking'] == 0): ?>
                                    <p style="color: #D7A63E; font-size: 20px; font-weight: 800;">CHAMPION:  </p>
                                    <a href="../athletes/<?php echo htmlspecialchars($fighters[0]['slug']); ?>.php" class="top-fighter-name">
                                    <?php echo htmlspecialchars($fighters[0]['name']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="records-fighter">
                                <?php echo htmlspecialchars($fighters[0]['wins']); ?>:<?php echo htmlspecialchars($fighters[0]['losses']); ?>:<?php echo htmlspecialchars($fighters[0]['draws']); ?>
                            </div>

                        </div>
                        
                    </div>
                    

                    <!-- Display list of all fighters in this weight class -->
                    <ul class="fighter-list">
                        <?php
                        $position = ($fighters[0]['ranking'] == 0) ? 1 : 2; // Champion is at the top if exists
                        $prevRanking = $fighters[0]['ranking'];

                        foreach ($fighters as $index => $fighter):
                            if ($index === 0) continue; // Skip the top fighter (already displayed)

                            // Calculate position
                            if ($fighter['ranking'] !== $prevRanking) {
                                $position++;
                            }
                        ?>
                            <li>
                                <strong><?php echo $position; ?> </strong>
                                <a href="../athletes/<?php echo htmlspecialchars($fighter['slug']); ?>.php">
                                    <?php echo htmlspecialchars($fighter['name']); ?>
                                </a>
                                <div class="records-fighter">
                                    <?php echo htmlspecialchars($fighters[0]['wins']); ?>:<?php echo htmlspecialchars($fighters[0]['losses']); ?>:<?php echo htmlspecialchars($fighters[0]['draws']); ?>
                                </div>
                            </li>
                        <?php 
                            $prevRanking = $fighter['ranking'];
                        endforeach; 
                        ?>
                    </ul>
                <?php else: ?>
                    <p>No fighters in this weight class.</p>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    </div>


    <?php include '../includes/footer.php'; ?>

</body>
</html>
