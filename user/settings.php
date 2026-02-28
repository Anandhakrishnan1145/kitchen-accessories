<?php
session_start();
include '../config.php'; // adjust path if needed

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme_preference'];
    $font_size = $_POST['font_size'];
    $primary = $_POST['primary_color'];
    $secondary = $_POST['secondary_color'];
    $font_color = $_POST['font_color'];

    $sql = "UPDATE users SET
        theme_preference = '$theme',
        font_size = '$font_size',
        primary_color = '$primary',
        secondary_color = '$secondary',
        font_color = '$font_color'
        WHERE id = $user_id";

    mysqli_query($conn, $sql);
    header('Location: settings.php?success=1');
    exit();
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
include 'header.php';
?>


<div class="card">
    <h2>Theme Settings</h2>
    <?php if (isset($_GET['success'])) echo "<p style='color: green;'>Settings updated!</p>"; ?>

    <form method="POST">
        <label>Theme:</label>
        <select name="theme_preference">
            <option value="light" <?= $user['theme_preference'] == 'light' ? 'selected' : '' ?>>Light</option>
            <option value="dark" <?= $user['theme_preference'] == 'dark' ? 'selected' : '' ?>>Dark</option>
        </select><br><br>

        <label>Font Size:</label>
        <select name="font_size">
            <option value="14px" <?= $user['font_size'] == '14px' ? 'selected' : '' ?>>Small</option>
            <option value="16px" <?= $user['font_size'] == '16px' ? 'selected' : '' ?>>Medium</option>
            <option value="18px" <?= $user['font_size'] == '18px' ? 'selected' : '' ?>>Large</option>
        </select><br><br>

        <label>Primary Color:</label>
        <input type="color" name="primary_color" value="<?= $user['primary_color'] ?>"><br><br>

        <label>Secondary Color:</label>
        <input type="color" name="secondary_color" value="<?= $user['secondary_color'] ?>"><br><br>

        <label>Font Color:</label>
        <input type="color" name="font_color" value="<?= $user['font_color'] ?>"><br><br>

        <button type="submit" class="btn">Save Settings</button>
    </form>
</div>

</body>
</html>
