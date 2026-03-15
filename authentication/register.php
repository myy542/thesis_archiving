<?php
session_start();
include("../config/db.php");

$message = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $role_id     = (int)($_POST["role_id"] ?? 2);

    $first_name  = trim($_POST["first_name"] ?? "");
    $last_name   = trim($_POST["last_name"] ?? "");
    $email       = trim($_POST["email"] ?? "");
    $username    = trim($_POST["username"] ?? "");
    $password    = $_POST["password"] ?? "";
    $cpassword   = $_POST["cpassword"] ?? "";
    $department  = trim($_POST["department"] ?? "");
    $birth_date  = trim($_POST["birth_date"] ?? "");
    $address     = trim($_POST["address"] ?? "");
    $contact_number = trim($_POST["contact_number"] ?? "");
    $status      = "1";

    if ($first_name === "" || $last_name === "" || $email === "" || $username === "" || $password === "" || $cpassword === "" || $contact_number === "") {
        $message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif ($password !== $cpassword) {
        $message = "Password and Confirm Password do not match.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } elseif (!ctype_digit($contact_number) || strlen($contact_number) < 10) {
        $message = "Contact number must be numeric and valid.";
    } elseif (!in_array($role_id, [1,2,3,4], true)) {
        $message = "Invalid role selected.";
    } else {
        $check = $conn->prepare("SELECT user_id FROM user_table WHERE username = ? OR email = ? LIMIT 1");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $exists = $check->get_result();

        if ($exists && $exists->num_rows > 0) {
            $message = "Username or Email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $profile_picture = "default.png";

            $stmt = $conn->prepare("
                INSERT INTO user_table
                (role_id, first_name, last_name, email, username, password, department, birth_date, address, contact_number, status, profile_picture)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isssssssssss",
                $role_id,
                $first_name,
                $last_name,
                $email,
                $username,
                $hashed,
                $department,
                $birth_date,
                $address,
                $contact_number,
                $status,
                $profile_picture
            );

            if ($stmt->execute()) {
                $success = "Registered successfully! You can now login.";
            } else {
                $message = "Register failed: " . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Thesis Archiving System</title>
    <!-- Google Material Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0,1" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        }

        body {
            background: white;
            min-height: 100vh;
            padding: 20px;
            padding-top: 120px; /* Space for fixed navbar */
        }

        /* NAVBAR - SAME SIZE SA PICTURE */
        .navbar {
            background: linear-gradient(135deg, #FE4853 0%, #732529 100%);
            box-shadow: 0 4px 15px rgba(254, 72, 83, 0.3);
            padding: 18px 0;  
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1300px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 40px; 
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            font-size: 1.4rem; 
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .logo .material-symbols-outlined {
            font-size: 34px;  /* Same icon size */
            color: white;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 40px;  /* Same gap between links */
        }

        .nav-links li a {
            text-decoration: none;
            color: white;
            font-weight: 500;
            font-size: 1.2rem;  
            padding: 8px 0;
            transition: all 0.2s;
            position: relative;
            opacity: 0.95;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(255, 255, 255, 0.2);
            opacity: 1;
        }

        .nav-links a.active {
            background: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }


        /* Main container */
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 32px;
            padding: 45px 50px;
            box-shadow: 0 15px 35px rgba(254, 72, 83, 0.15);
            border: 1px solid #ffd9db;
        }

        /* Header */
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #732529;
            margin-bottom: 35px;
            text-align: left;
            letter-spacing: -0.5px;
            border-left: 7px solid #FE4853;
            padding-left: 20px;
        }

        /* Alerts */
        .alert, .success {
            padding: 14px 18px;
            border-radius: 16px;
            margin-bottom: 25px;
            font-size: 0.95rem;
        }
        .alert {
            background: #ffeeee;
            border: 1px solid #b7b3b3;
            color:#732529;
        }
        .success {
            background: #f0fff0;
            border: 1px solid #c9ffc9;
            color: #2d7a2d;
        }

        /* Form groups */
        .form-group {
            margin-bottom: 22px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #732529;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }

        label span {
            color:#732529;
            margin-left: 3px;
        }

        input, select, textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #6E6E6E;
            border-radius: 18px;
            font-size: 0.95rem;
            background-color: white;
            color: #000000;
            transition: all 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #FE4853;
            outline: none;
            box-shadow: 0 0 0 4px rgba(254, 72, 83, 0.1);
        }

        input::placeholder, textarea::placeholder {
            color: #a0a0a0;
            font-style: italic;
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23FE4853' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 18px;
            color: #000000;
        }

        select option {
            background: white;
            color: #000000;
        }

        textarea {
            resize: none;
            min-height: 100px;
            color: #000000;
        }

        .btn-register {
            width: 100%;
            background: #FE4853;
            color: white;
            border: none;
            padding: 18px;
            border-radius: 40px;
            font-size: 1.3rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            margin: 25px 0 20px;
            box-shadow: 0 10px 25px rgba(254, 72, 83, 0.25);
            letter-spacing: 1px;
        }

        .btn-register:hover {
            background: #732529;
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(254, 72, 83, 0.35);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        /* OR section */
        .or-section {
            text-align: center;
            margin: 20px 0 15px;
            color: #6E6E6E;
            font-weight: 500;
            position: relative;
            font-size: 0.9rem;
        }

        .or-section::before,
        .or-section::after {
            content: "";
            display: inline-block;
            width: 40%;
            height: 1px;
            background-color: #ffd9db;
            vertical-align: middle;
            margin: 0 10px;
        }

        /* Login link */
        .login-link {
            text-align: center;
            margin-top: 10px;
            color: #8f6b6b;
            font-size: 1rem;
        }

        .login-link a {
            color: #FE4853;
            font-weight: 700;
            text-decoration: none;
            border-bottom: 2px solid #ffd9db;
            padding-bottom: 2px;
            margin-left: 5px;
        }

        .login-link a:hover {
            border-bottom-color: #FE4853;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .nav-container {
                flex-direction: column;
                gap: 15px;
                padding: 0 20px;
            }
            
            .nav-links {
                gap: 25px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-links li a {
                font-size: 1.1rem;
            }
            
            body {
                padding-top: 140px;
            }
        }

        @media (max-width: 550px) {
            .card {
                padding: 30px 25px;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            h1 {
                font-size: 2rem;
            }
            
            .logo {
                font-size: 1.2rem;
            }
            
            .logo .material-symbols-outlined {
                font-size: 28px;
            }
            
            body {
                padding-top: 160px;
            }
        }

        /* Date input */
        input[type="date"] {
            color-scheme: light;
            color: #000000;
        }
    </style>
</head>
<body>

    <!-- NAVBAR - SAME SIZE SA PICTURE -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="homepage.php" class="logo">
                <span class="material-symbols-outlined">book</span>
                Web-Based Thesis Archiving System
            </a>
            <ul class="nav-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="browse.php">Browse Thesis</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php" class="active">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <h1>Create Account</h1>

            <?php if ($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <!-- Role -->
                <div class="form-group">
                    <label>Role <span></span></label>
                    <select name="role_id" required>
                        <option value="" disabled selected>Select role</option>
                        <option value="1">Admin</option>
                        <option value="2">Student</option>
                        <option value="3">Researcher Adviser</option>
                        <option value="4">Researcher Coordinator</option>
                        <option value="5">Department Dean</option>
                    </select>
                </div>

                <!-- First Name & Last Name row -->
                <div class="form-row">
                    <div>
                        <label>First Name <span></span></label>
                        <input type="text" name="first_name" placeholder="Enter first name" required>
                    </div>
                    <div>
                        <label>Last Name <span></span></label>
                        <input type="text" name="last_name" placeholder="Enter last name" required>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label>Email <span></span></label>
                    <input type="email" name="email" placeholder="Enter email" required>
                </div>

                <!-- Username -->
                <div class="form-group">
                    <label>Username <span></span></label>
                    <input type="text" name="username" placeholder="Enter username" required>
                </div>

                <!-- Password & Confirm Password row -->
                <div class="form-row">
                    <div>
                        <label>Password <span></span></label>
                        <input type="password" name="password" placeholder="Enter password" required>
                    </div>
                    <div>
                        <label>Confirm Password <span></span></label>
                        <input type="password" name="cpassword" placeholder="Confirm password" required>
                    </div>
                </div>

                <!-- Department & Birth Date row -->
                <div class="form-row">
                    <div>
                        <label>Department</label>
                        <input type="text" name="department" placeholder="Department">
                    </div>
                    <div>
                        <label>Birth Date</label>
                        <input type="date" name="birth_date">
                    </div>
                </div>

                <!-- Contact Number -->
                <div class="form-group">
                    <label>Contact Number <span></span></label>
                    <input type="text" name="contact_number" placeholder="09xxxxxxxxx" required>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" placeholder="Enter address"></textarea>
                </div>

                <!-- Register Button -->
                <button type="submit" class="btn-register">Register</button>

                <!-- OR -->
                <div class="or-section">OR</div>

                <!-- Login Link -->
                <div class="login-link">
                    Already have an account? <a href="login.php">Login</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>