<?php
session_start();
include '../database/db_connect.php';

// stuff that needs to happen serverside
// check if admin (function probably) TASK 1
// if admin then display the match input TASK 2
// for everyone get all matches and put them on screen. you can scroll down (no pagination i dont wanna its fine) TASK 3
// make it so clicking on users takes to user page TASK 4

// confirm user is logged in otherwise kick them out
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$is_admin = false;

// admin check
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

// admin check yup
if ($res->num_rows === 1) {
    $row = $res->fetch_assoc();
    $is_admin = (bool)$row['is_admin'];
}
$stmt->close();

// MATCH SUBMISSION (ADMIN ONLY). extra check for is not edit. not clean but it works so its fine for now
// if i did more with this id change it
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['edit_match']) && $is_admin) {
    $kills = $_POST['kills'];
    $deaths = $_POST['deaths'];
    $playstyle = $_POST['playstyle'];

    // kills and deaths cant be negative
    if ($kills < 0 || $deaths < 0) {
        $error = "Kills and Deaths cannot be negative.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO matches (user_id, kills, deaths, playstyle)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiis", $user_id, $kills, $deaths, $playstyle);
        $stmt->execute();
        $stmt->close();

        // refresh page if user is admin to add thing
        header("Location: dashboard.php");
        exit();
    }
}

// MATCH EDIT. only admins that posted the match can edit it
if (isset($_GET['delete']) && $is_admin) {
    $match_id = intval($_GET['delete']);

    // verify match belongs to user
    $stmt = $conn->prepare("SELECT user_id FROM matches WHERE id = ?");
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $match = $res->fetch_assoc();
    $stmt->close();

    if ($match && $match['user_id'] == $user_id) {
        $stmt = $conn->prepare("DELETE FROM matches WHERE id = ?");
        $stmt->bind_param("i", $match_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dashboard.php");
    exit();
}


// MATCH DELETE. only admins that posted the match can delete it
if (isset($_POST['edit_match']) && $is_admin) {

    $match_id = intval($_POST['match_id']);
    $kills = intval($_POST['kills']);
    $deaths = intval($_POST['deaths']);
    $playstyle = $_POST['playstyle'];

    // verify match belongs to admin user
    $stmt = $conn->prepare("SELECT user_id FROM matches WHERE id = ?");
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $match = $res->fetch_assoc();
    $stmt->close();

    if ($match && $match['user_id'] == $user_id) {

        $stmt = $conn->prepare("
            UPDATE matches
            SET kills = ?, deaths = ?, playstyle = ?
            WHERE id = ?
        ");
        $stmt->bind_param("iisi", $kills, $deaths, $playstyle, $match_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dashboard.php");
    exit();
}


// get all matches for everyone and sort them by played at data (recent first)
$matches = $conn->query("
    SELECT m.*, u.username 
    FROM matches m
    JOIN users u ON m.user_id = u.id
    ORDER BY m.played_at DESC
");

// stuff that needs to happen serverside
// check if admin (function probably) TASK 1
// if admin then display the match input TASK 2
// for everyone get all matches and put them on screen. you can scroll down (no pagination i dont wanna its fine) TASK 3
// make it so clicking on users takes to user page TASK 4
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Halo: ST - Dashboard</title>
    <link rel="stylesheet" href="../public/css/styles.css">
</head>

<body>

    <div class="dashboard-wrapper">

        <h1 class="site-title">Halo: Stat Tracker</h1>

        <div class="dashboard-header">
            <h2>Welcome, <?php echo htmlspecialchars($username); ?></h2>
            <a class="logout-link" href="../auth/logout.php">Logout</a>
        </div>

        <?php if ($is_admin): ?>
            <div class="admin-panel">
                <h3>Submit New Match</h3>
                <form method="POST">
                    <input type="number" name="kills" placeholder="Kills" required min="0">
                    <input type="number" name="deaths" placeholder="Deaths" required min="0">

                    <select name="playstyle" required>
                        <option value="" disabled selected>Playstyle</option>
                        <option value="infantry">Infantry</option>
                        <option value="vehicle">Vehicle</option>
                        <option value="mixed">Mixed</option>
                    </select>

                    <button type="submit">Post Match</button>
                </form>
            </div>
        <?php endif; ?>

        <h2 class="section-title">Recent Matches</h2>

        <div class="match-list">
        <?php while ($m = $matches->fetch_assoc()): ?>
            <div class="match-card">

                <div class="match-card-header">
                    <a class="match-username" href="user.php?id=<?php echo $m['user_id']; ?>">
                        <?php echo htmlspecialchars($m['username']); ?>
                    </a>
                    <span class="match-date"><?php echo $m['played_at']; ?></span>
                </div>

                <div class="match-stats-grid">
                    <div>Kills: <?php echo $m['kills']; ?></div>
                    <div>Deaths: <?php echo $m['deaths']; ?></div>
                    <div>KD: <?php echo round($m['kills'] / max($m['deaths'],1), 2); ?></div>
                    <div>Playstyle: <?php echo ucfirst($m['playstyle']); ?></div>
                </div>

                <?php if ($is_admin && $m['user_id'] == $user_id): ?>
                    <div class="match-actions">

                        <!-- edit drop down. details is kinda a fun way to do it. idk its easy this way -->
                        <details>
                            <summary>Edit Match</summary>
                            <form method="POST">
                                <input type="hidden" name="match_id" value="<?php echo $m['id']; ?>">
                                <input type="hidden" name="edit_match" value="1">

                                <input type="number" name="kills" value="<?php echo $m['kills']; ?>" required min="0">
                                <input type="number" name="deaths" value="<?php echo $m['deaths']; ?>" required min="0">

                                <select name="playstyle" required>
                                    <option value="infantry" <?php if ($m['playstyle'] == 'infantry') echo 'selected'; ?>>Infantry</option>
                                    <option value="vehicle" <?php if ($m['playstyle'] == 'vehicle') echo 'selected'; ?>>Vehicle</option>
                                    <option value="mixed" <?php if ($m['playstyle'] == 'mixed') echo 'selected'; ?>>Mixed</option>
                                </select>

                                <button class="save-button" type="submit">Save</button>
                            </form>
                        </details>

                        <!-- delete button -->
                        <a href="dashboard.php?delete=<?php echo $m['id']; ?>" class="delete-btn" onclick="return confirm('Delete this match?');">
                            Delete
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        <?php endwhile; ?>
        </div>

    </div>

</body>

</html>
