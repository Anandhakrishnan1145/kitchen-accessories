<?php
session_start();
require_once 'config.php';

// Cache control headers to prevent back button navigation to logged-in pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Clear any existing session data if coming from a timeout
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Start a new session for the login form
    session_start();
}

// Initialize variables
$error = "";
$login_disabled = false;
$remaining_time = 0;

// Check if login is temporarily disabled due to too many attempts
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 5) {
    $last_attempt_time = $_SESSION['last_attempt_time'] ?? 0;
    $current_time = time();
    $lockout_duration = 30; // 30 seconds lockout
    
    if (($current_time - $last_attempt_time) < $lockout_duration) {
        $login_disabled = true;
        $remaining_time = $lockout_duration - ($current_time - $last_attempt_time);
    } else {
        // Reset attempts after lockout period
        unset($_SESSION['login_attempts']);
        unset($_SESSION['last_attempt_time']);
    }
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$login_disabled) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid form submission";
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_attempt_time'] = time();
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Validate inputs
        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password";
        } elseif (strlen($username) > 50 || strlen($password) > 255) {
            $error = "Invalid input length";
        } else {
            // Sanitize username (email or username)
            $username = filter_var($username, FILTER_SANITIZE_STRING);
            
            // Check user credentials with prepared statement (using your original query)
            $stmt = $conn->prepare("SELECT id, username, email, user_type, password, profile_image FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Password is correct, reset attempts
                    unset($_SESSION['login_attempts']);
                    unset($_SESSION['last_attempt_time']);
                    
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Store data in session variables
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['last_activity'] = time();
                    
                    // Set auto logout time (default to 5 minutes if not set)
                    $_SESSION['auto_logout_time'] = $_SESSION['auto_logout_time'] ?? 5;
                    
                    // Reset CSRF token after successful login
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    
                    // Redirect user to appropriate dashboard
                    if ($user['user_type'] === 'admin') {
                        header("Location: admin/admin_dashboard.php");
                    } else {
                        header("Location: user/index.php");
                    }
                    exit;
                } else {
                    // Increment failed login attempts (using session variables)
                    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                    $_SESSION['last_attempt_time'] = time();
                    
                    $remaining_attempts = 5 - $_SESSION['login_attempts'];
                    if ($remaining_attempts > 0) {
                        $error = "Invalid password. You have {$remaining_attempts} attempts remaining.";
                    } else {
                        $error = "Too many failed attempts. Account locked for 30 seconds.";
                        $login_disabled = true;
                        $remaining_time = 30;
                    }
                }
            } else {
                // Username not found, but don't reveal this to user
                $error = "Invalid username or password";
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                $_SESSION['last_attempt_time'] = time();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Prevent caching -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #8BC6EC 0%, #9599E2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .form-container {
            max-width: 450px;
            margin: 0 auto;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-radius: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }
        .form-container::before {
            content: "";
            position: absolute;
            top: -50px;
            left: -50px;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
            border-radius: 50%;
            opacity: 0.2;
            z-index: 0;
        }
        .form-container::after {
            content: "";
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
            border-radius: 50%;
            opacity: 0.2;
            z-index: 0;
        }
        .form-title {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            color: #3a3a3a;
            position: relative;
            z-index: 1;
        }
        .form-logo {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        .form-logo img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border: 4px solid white;
            transition: transform 0.3s ease;
        }
        .form-logo img:hover {
            transform: scale(1.05);
        }
        .input-group {
            position: relative;
            z-index: 1;
        }
        .form-control {
            border: none;
            border-radius: 10px;
            padding: 12px;
            background-color: rgba(241, 241, 241, 0.8);
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
            background-color: rgba(255, 255, 255, 0.9);
        }
        .btn-primary {
            border-radius: 30px;
            padding: 10px 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .btn-primary:disabled {
            background: linear-gradient(135deg, #cccccc 0%, #999999 100%);
            cursor: not-allowed;
        }
        .btn-outline-secondary {
            border-radius: 0 10px 10px 0;
            border: none;
            background-color: rgba(241, 241, 241, 0.8);
        }
        .btn-outline-secondary:hover {
            background-color: rgba(230, 230, 230, 0.9);
        }
        .form-check-input:checked {
            background-color: #764ba2;
            border-color: #764ba2;
        }
        .alert-danger {
            background-color: rgba(255, 205, 210, 0.8);
            border: none;
            border-radius: 10px;
            color: #c62828;
            position: relative;
            z-index: 1;
        }
        .card-link {
            padding: 5px 0;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 500;
            color: #6c63ff;
        }
        .card-link:hover {
            color: #4b45b2;
            text-decoration: underline;
        }
        .links-divider {
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0,0,0,0.1), transparent);
            margin: 15px 0;
        }
        .countdown {
            font-weight: bold;
            color: #c62828;
        }
        
        /* For timeout notification */
        .timeout-alert {
            background-color: rgba(255, 229, 100, 0.9);
            color: #664d03;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
            border-left: 5px solid #f5c001;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-logo">
                <img src="uploads/profilephoto/logo.jpg" alt="Logo" onerror="this.src='assets/default-logo.png'">
            </div>
            <h2 class="form-title">Welcome Back</h2>
            
            <?php if(isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
                <div class="timeout-alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Your session has expired due to inactivity. Please log in again.
                </div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                    <?php if($login_disabled): ?>
                        <div class="mt-2">Please wait <span class="countdown"><?php echo $remaining_time; ?></span> seconds before trying again.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="mb-4">
                    <label for="username" class="form-label">Username or Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-0"><i class="fas fa-user text-muted"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username or email" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required <?php echo $login_disabled ? 'disabled' : ''; ?>>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-0"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required <?php echo $login_disabled ? 'disabled' : ''; ?>>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" <?php echo $login_disabled ? 'disabled' : ''; ?>>
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember" <?php echo $login_disabled ? 'disabled' : ''; ?>>
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                
                <div class="d-grid gap-2 mb-4">
                    <button type="submit" class="btn btn-primary" <?php echo $login_disabled ? 'disabled' : ''; ?>>
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </div>
                
                <div class="text-center">
                    <div class="links-divider"></div>
                    <p>Don't have an account? <a href="register.php" class="card-link">Register here</a></p>
                    <p><a href="forgot_password.php" class="card-link">Forgot Password?</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent back button navigation after logout
        window.onload = function() {
            if (window.history && window.history.pushState) {
                window.history.pushState('forward', null, '');
                window.onpopstate = function() {
                    window.history.pushState('forward', null, '');
                };
            }
        };
        
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        <?php if($login_disabled): ?>
        // Update countdown timer
        let timeLeft = <?php echo $remaining_time; ?>;
        const countdownElement = document.querySelector('.countdown');
        
        const timer = setInterval(() => {
            timeLeft--;
            countdownElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                // Reload the page when timer ends
                window.location.reload();
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>