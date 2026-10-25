<?php
declare(strict_types=1);

session_start();

// Check if the user is actually logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Only logout when confirmation is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Clear all session data
    $_SESSION = [];

    // Delete session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Destroy session
    session_destroy();

    // Redirect to homepage
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Logout</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .logout-box {
            background: white;
            padding: 30px;
            width: 350px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .logout-box h2 {
            color: #1e3a8a;
            margin-bottom: 10px;
        }

        .logout-box p {
            color: #555;
            margin-bottom: 25px;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        button,
        .cancel-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }

        .logout-btn {
            background: #2563eb;
            color: white;
        }

        .logout-btn:hover {
            background: #1d4ed8;
        }

        .cancel-btn {
            background: #e5e7eb;
            color: #333;
        }

        .cancel-btn:hover {
            background: #d1d5db;
        }
    </style>
</head>

<body>

<div class="logout-box">
    <h2>Confirm Logout</h2>

    <p>Are you sure you want to log out?</p>

    <div class="buttons">

        <form method="POST">
            <button type="submit" class="logout-btn">
                Yes, Log Out
            </button>
        </form>

        <a href="index.php" class="cancel-btn">
            Cancel
        </a>

    </div>
</div>

</body>
</html>
