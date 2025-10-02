<?php
session_start();
require 'db/db.php';

$message = "";

// Admin email
$admin_email = "doamuragijimana@gmail.com";
$default_password = "80100";

// Register
if (isset($_POST['register'])) {
    $email = trim($_POST['email']);
    $pass = trim($_POST['password']) ?: $default_password;
    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "⚠️ Email already registered!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (email,password) VALUES (?,?)");
        $stmt->bind_param("ss", $email, $hashed_pass);
        if ($stmt->execute()) {
            mail($admin_email, "New User Registration", "New user registered with email: $email");
            $message = "✅ Account created successfully!";
        }
    }
}

// Login
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $message = "🎉 Welcome " . $user['email'];
    } else {
        $message = "❌ Invalid email or password!";
    }
}

// Admin action: Add coins
if (isset($_POST['add_coins']) && isset($_SESSION['email']) && $_SESSION['email'] === $admin_email) {
    $user_id = $_POST['user_id'];
    $coins = $_POST['coins'];
    $stmt = $conn->prepare("UPDATE users SET coins = coins + ? WHERE id=?");
    $stmt->bind_param("ii", $coins, $user_id);
    $stmt->execute();

    mail($admin_email, "Coins Updated", "Added $coins coins to user ID: $user_id");
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: account.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ACCOUNT [VIP]</title>
<style>
body { margin:0; font-family:Arial; background:#111; color:white; }
.wrap { display:flex; height:100vh; }
.side-bar { width:220px; background:#222; padding:20px; }
.side-bar h2 { color:yellow; }
.button-side, .button-sideactive { display:block; width:100%; margin:10px 0; padding:10px; border:none; cursor:pointer; color:white; }
.button-sideactive { background:darkorange; }
.right-bar { flex:1; padding:20px; }
.stats { background: rgba(0,0,0,0.6); padding:20px; border-radius:10px; }
.btn { background: darkorange; border:none; padding:10px 20px; margin:5px; cursor:pointer; font-weight:bold; border-radius:5px; }
.btn:hover { background: orange; }
.stutus { margin-top:20px; }
input { padding:10px; margin:5px 0; border-radius:5px; width:80%; }
</style>
</head>
<body>
<div class="wrap">
<div class="side-bar">
  <h2>[OSF][VIP]</h2>
  <button class="button-side" onclick="window.location='home.html'">🏡HOME</button>
  <button class="button-side" onclick="window.location='video.html'">➕VIDEO</button>
  <button class="button-side" onclick="window.location='addvideo.html'">➕CAMPENY</button>
  <button class="button-side" onclick="window.location='ad.html'">⁛ADS</button>
  <button class="button-side" onclick="window.location='shar.html'">🥏SHARE</button>
  <button class="button-sideactive">🐦‍⬛ACCOUNT</button>
</div>

<div class="right-bar">
<main class="content">
  <div class="stats">
    <h2>Account Login/Register</h2>
    <form method="POST" action="">
        <input type="email" name="email" placeholder="Enter your email" required>
        <input type="password" name="password" placeholder="Enter password" value="80100">
        <br>
        <button type="submit" name="login" class="btn">Login</button>
        <button type="submit" name="register" class="btn">Register</button>
    </form>
    <div style="margin-top:15px; font-weight:bold; color:yellow;">
        <?php echo $message; ?>
    </div>

    <?php if(isset($_SESSION['user_id'])): ?>
        <div class="stutus"><h3>Logged in as: <span><?php echo $_SESSION['email']; ?></span></h3></div>
        <?php if($_SESSION['email'] === $admin_email): ?>
            <form method="POST" action="">
                <input type="number" name="user_id" placeholder="User ID to add coins">
                <input type="number" name="coins" placeholder="Coins to add">
                <button type="submit" name="add_coins" class="btn">Add Coins</button>
            </form>
        <?php endif; ?>
        <a href="?logout=1" class="btn">Logout</a>
    <?php endif; ?>
  </div>
</main>
</div>
</div>
</body>
</html>
