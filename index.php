<?php
session_start();

// Configuration
$login_url = 'https://wallet.stormsurge.xyz/login';
$target_url = 'https://wallet.stormsurge.xyz/transaction';
$cooldown_duration = 24 * 60 * 60; // 1 day in seconds

// reCAPTCHA configuration
$recaptcha_site_key = '6LeRIZcqAAAAAJh78NwPtCdpqPbOLGgiVelnL4-B';
$recaptcha_secret_key = '6LeRIZcqAAAAAKcEsCfXQD2tk-QJJRBkacF9DYKy';

// Verify reCAPTCHA
function verify_recaptcha($secret_key, $response) {
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secret_key,
        'response' => $response
    ];
    
    $ch = curl_init($verify_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

// Helper functions
function get_timestamp() {
    return time();
}

function read_cooldown_data() {
    $cooldown_data = [];
    if (file_exists('cooldown.txt')) {
        $lines = file('cooldown.txt', FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            $parts = explode(',', $line);
            if (count($parts) == 2) {
                $cooldown_data[$parts[0]] = (int)$parts[1];
            }
        }
    }
    return $cooldown_data;
}

function write_cooldown_data($cooldown_data) {
    $content = '';
    foreach ($cooldown_data as $username => $timestamp) {
        $content .= "$username,$timestamp\n";
    }
    file_put_contents('cooldown.txt', $content);
}

function format_remaining_time($remaining_time) {
    $days = floor($remaining_time / (24 * 60 * 60));
    $hours = floor(($remaining_time % (24 * 60 * 60)) / (60 * 60));
    $minutes = floor(($remaining_time % (60 * 60)) / 60);
    $seconds = $remaining_time % 60;
    return "You are on cooldown for $days days, $hours hours, $minutes minutes, and $seconds seconds!";
}

function get_faucet_balance() {
    $ch = curl_init();
    
    // Login first
    curl_setopt($ch, CURLOPT_URL, 'https://wallet.stormsurge.xyz/login');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => 'ENTER-ELAP-USERNAME-HERE',
        'password' => 'ELAP-PASSWORD-HERE'
    ]));
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $login_response = curl_exec($ch);
    
    // Now fetch the dashboard
    curl_setopt($ch, CURLOPT_URL, 'https://wallet.stormsurge.xyz/dashboard');
    curl_setopt($ch, CURLOPT_POST, 0);
    $dashboard_html = curl_exec($ch);
    curl_close($ch);
    
    // Parse the balance from the dashboard HTML
    if (preg_match('/Balance:\s*([0-9.]+)/', $dashboard_html, $matches)) {
        return $matches[1];
    }
    
    return 'Unknown';
}

// Get the balance before the HTML output
$faucet_balance = get_faucet_balance();

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify reCAPTCHA first
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $recaptcha_result = verify_recaptcha($recaptcha_secret_key, $recaptcha_response);
    
    if (!$recaptcha_result['success']) {
        $message = "Please complete the reCAPTCHA verification.";
    } else {
        $cooldown_data = read_cooldown_data();
        $username = $_POST['username'] ?? '';
        
        // Check username cooldown
        if (isset($cooldown_data[$username])) {
            $remaining_time = $cooldown_duration - (get_timestamp() - $cooldown_data[$username]);
            if ($remaining_time > 0) {
                $message = format_remaining_time($remaining_time);
            }
        }
        
        if (empty($message)) {
            // Initialize cURL session
            $ch = curl_init();
            
            // Enable SSL verification settings
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            // Login first
            curl_setopt($ch, CURLOPT_URL, $login_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'username' => 'gamecat999',
                'password' => 'Jbllc100'
            ]));
            curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
            curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            
            $login_response = curl_exec($ch);
            $login_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($login_http_code == 200) {
                // Submit faucet request
                curl_setopt($ch, CURLOPT_URL, $target_url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'username' => $username,
                    'password' => 'PASS_WORD_HERE',
                    'address' => 'USERNAMEHERE',
                    'amount' => '0.75',
                ]));
                
                $faucet_response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if ($http_code == 200) {
                    // Update cooldown for username
                    $cooldown_data[$username] = get_timestamp();
                    write_cooldown_data($cooldown_data);
                    
                    $message = "Faucet transaction successful!";
                } else {
                    $message = "Faucet transaction failed!";
                }
            } else {
                $message = "Login failed!";
            }
            
            curl_close($ch);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="keywords" content="elaptacoin, elaptacoin faucet, wallet.stormsurge.xyz, stormsurge, katfaucet, elap, DuckyPolice, gamecat999, elapta, elaptic">
    <meta name="description" content="Faucet for wallet.stormsurge.xyz. Get 0.75 Elaptacoin for free every day!">
    <title>Elaptacoin Faucet</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        :root {
            --primary-color: #2196F3;
            --secondary-color: #1976D2;
            --background-color: #f8f9fa;
            --card-background: #ffffff;
            --text-color: #333333;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        .card {
            background: var(--card-background);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.9em;
        }

        input[type="text"] {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        input[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: var(--secondary-color);
        }

        input[type="submit"]:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .message {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .g-recaptcha {
            margin: 20px 0;
            display: flex;
            justify-content: center;
        }

        /* Dark mode */
        @media (prefers-color-scheme: dark) {
            :root {
                --background-color: #121212;
                --card-background: #1e1e1e;
                --text-color: #ffffff;
            }

            input[type="text"] {
                background-color: #2d2d2d;
                color: white;
                border-color: #404040;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            let lastClickTime = 0;
            let captchaCompleted = false;

            // Add reCAPTCHA callback
            window.onRecaptchaSuccess = function() {
                captchaCompleted = true;
            };

            document.addEventListener('click', (e) => {
                const currentTime = new Date().getTime();
                
                // Only apply click limiting after captcha is completed
                if (captchaCompleted) {
                    if (currentTime - lastClickTime < 2000) { // 2 seconds
                        e.stopImmediatePropagation();
                        e.preventDefault();
                    } else {
                        lastClickTime = currentTime;
                    }
                }
            }, true);
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>Elaptacoin Faucet</h1>
        <p>Get .75 elap free a day!</p>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'successful') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST">
                <div class="form-group">
                    <label for="username">Elapcoin Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="g-recaptcha" 
                     data-sitekey="<?php echo htmlspecialchars($recaptcha_site_key); ?>"
                     data-callback="onRecaptchaSuccess"></div>
                
                <input type="submit" value="Claim">
            </form>
        </div>
    </div>
</body>
</html> 
