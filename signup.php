<?php
require_once 'config.php';
require_once 'auth.php';

// Signup page is now admin-only account creation.
 requireAdmin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = '<div class="alert error">Shyiramo ibyo byose</div>';
    } elseif ($password !== $confirm_password) {
        $message = '<div class="alert error">Password zitagaranye</div>';
    } elseif (strlen($password) < 6) {
        $message = '<div class="alert error">Password igomba kugira nibura 6 characters</div>';
    } else {
        $result = createUserByAdmin($pdo, $username, $password, 'admin', $email);
        if ($result['success']) {
            $message = '<div class="alert success">' . htmlspecialchars($result['message']) . '</div>';
        } else {
            $message = '<div class="alert error">' . htmlspecialchars($result['message']) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kwiyandikisha - elamSystem</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .signup-container {
            max-width: 520px;
            padding: 28px;
        }
        .auth-slider {
            position: relative;
            height: 170px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.18);
        }
        .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 0.9s ease;
            background-size: cover;
            background-position: center;
        }
        .slide.active { opacity: 1; }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="brand-header">
            <img class="brand-logo" src="assets/sda.png" alt="Adventist logo">
            <div class="brand-text">
                <h2>Adventist ELAM</h2>
                <small>Create your account to continue</small>
            </div>
        </div>
        <div class="auth-slider" id="signupSlider">
            <div class="slide active" style="background-image:url('https://images.unsplash.com/photo-1511988617509-a57c8a288659?auto=format&fit=crop&w=1200&q=80')"></div>
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1478147427282-58a87a120781?auto=format&fit=crop&w=1200&q=80')"></div>
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1509099836639-18ba1795216d?auto=format&fit=crop&w=1200&q=80')"></div>
        </div>
        <h1>Create Admin Account</h1>
        <p class="subtitle">Only logged-in admins can use this page</p>
        
        <?php echo $message; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Emeza Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" name="signup" class="btn">Create Admin Account</button>
        </form>
        
        <div class="links">
            <p><a href="admin.php">Subira kuri Admin</a></p>
        </div>
    </div>
    <script>
        (function () {
            const slides = document.querySelectorAll('#signupSlider .slide');
            if (!slides.length) return;
            let idx = 0;
            setInterval(() => {
                slides[idx].classList.remove('active');
                idx = (idx + 1) % slides.length;
                slides[idx].classList.add('active');
            }, 3500);
        })();
    </script>
</body>
</html>