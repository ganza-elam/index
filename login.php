<?php
require_once 'config.php';
require_once 'auth.php';
ensureGuestAccount($pdo);

$message = '';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $message = '<div class="alert error">Shyiramo username na password</div>';
    } else {
        $result = loginUser($pdo, $username, $password);
        if ($result['success']) {
            header('Location: index.php');
            exit;
        } else {
            $message = '<div class="alert error">' . htmlspecialchars($result['message']) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ingiro - Church Ledger</title>
    <link rel="icon" type="image/png" href="sda.png">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 500px;
            padding: 28px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 14px 35px rgba(0, 0, 0, 0.28);
            position: relative;
            z-index: 2;
        }
        .auth-slider {
            position: relative;
            width: 100%;
            min-height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
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
        .slide::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.38);
        }
        .slide-caption {
            position: absolute;
            left: 12px;
            right: 12px;
            bottom: 10px;
            color: #fff;
            font-size: 13px;
            z-index: 2;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="auth-slider" id="authSlider">
        <div class="slide active" style="background-image:url('https://images.unsplash.com/photo-1465146344425-f00d5f5c8f07?auto=format&fit=crop&w=1200&q=80')">
            <div class="slide-caption">Welcome to ELAM system</div>
        </div>
        <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1504052434569-70ad5836ab65?auto=format&fit=crop&w=1200&q=80')">
            <div class="slide-caption">Track church offerings accurately</div>
        </div>
        <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1529070538774-1843cb3265df?auto=format&fit=crop&w=1200&q=80')">
            <div class="slide-caption">Serve with transparency and trust</div>
        </div>
        <div class="login-container">
            <div class="brand-header">
                <img class="brand-logo" src="sda.png" alt="Adventist logo">
                <div class="brand-text">
                    <h2>Seventh Day Adventist Church</h2>
                    <small>Faithful stewardship reporting</small>
                </div>
            </div>
            <h1>login</h1>
            <p class="subtitle">login in stewardship reporting System</p>
            
            <?php echo $message; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username cyangwa Email</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="password-toggle" data-shown="false" aria-label="Show password" title="Show password">
                            <svg class="icon-show" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn">login</button>
            </form>
            
            <div class="links">
                <!-- <p>is there any account? <a href="signup.php">create account</a></p> -->
                <p style="margin-top:10px;color:#666;">
                1 Corinthians 4:2 (ESV): Emphasizes that faithfulness is required of stewards.
                    <!-- Guest (view reports only): <b><?= htmlspecialchars(GUEST_USERNAME) ?></b> / <b><?= htmlspecialchars(GUEST_PASSWORD) ?></b> -->
                </p>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const slides = document.querySelectorAll('#authSlider .slide');
            if (!slides.length) return;
            let idx = 0;
            setInterval(() => {
                slides[idx].classList.remove('active');
                idx = (idx + 1) % slides.length;
                slides[idx].classList.add('active');
            }, 3500);
        })();

        document.querySelectorAll('.password-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = btn.closest('.password-wrapper').querySelector('input');
                const shown = input.type === 'text';
                input.type = shown ? 'password' : 'text';
                btn.setAttribute('data-shown', shown ? 'false' : 'true');
                const label = shown ? 'Show password' : 'Hide password';
                btn.setAttribute('aria-label', label);
                btn.setAttribute('title', label);
            });
        });
    </script>
</body>
</html>
