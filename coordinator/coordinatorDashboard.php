<?php
// TEMPORARILY DISABLED LOGIN VALIDATION - FOR DESIGN PREVIEW ONLY
// session_start();
// require_once '../config/db.php';

// Temporary dummy data para lang naay makita sa design
$user_name = "John Coordinator";
$user_email = "coordinator@thesismanager.com";

// Dummy statistics
$stats = [
    'total_projects' => 156,
    'total_students' => 89,
    'pending_reviews' => 23,
    'archived_count' => 34,
    'total_income' => 45600
];

// Dummy projects data
$dummy_projects = [
    ['id' => 1, 'title' => 'AI-Powered Thesis Recommendation System', 'author_name' => 'Maria Santos', 'department' => 'Computer Science', 'created_at' => '2024-03-15', 'status' => 'pending'],
    ['id' => 2, 'title' => 'Mobile App for Campus Navigation', 'author_name' => 'Juan Dela Cruz', 'department' => 'Information Technology', 'created_at' => '2024-03-14', 'status' => 'in-progress'],
    ['id' => 3, 'title' => 'E-Learning Platform for Mathematics', 'author_name' => 'Ana Lopez', 'department' => 'Education', 'created_at' => '2024-03-13', 'status' => 'completed'],
    ['id' => 4, 'title' => 'IoT-Based Classroom Monitoring', 'author_name' => 'Pedro Reyes', 'department' => 'Engineering', 'created_at' => '2024-03-12', 'status' => 'pending'],
    ['id' => 5, 'title' => 'Blockchain for Student Records', 'author_name' => 'Lisa Garcia', 'department' => 'Computer Science', 'created_at' => '2024-03-11', 'status' => 'archived'],
    ['id' => 6, 'title' => 'Virtual Reality Campus Tour', 'author_name' => 'Mark Santiago', 'department' => 'Multimedia Arts', 'created_at' => '2024-03-10', 'status' => 'in-progress'],
    ['id' => 7, 'title' => 'Automated Grading System', 'author_name' => 'Karen Villanueva', 'department' => 'Information Technology', 'created_at' => '2024-03-09', 'status' => 'pending'],
    ['id' => 8, 'title' => 'Student Portfolio Generator', 'author_name' => 'Paul Mendoza', 'department' => 'Computer Science', 'created_at' => '2024-03-08', 'status' => 'completed'],
];

// Dummy users data
$dummy_users = [
    ['name' => 'Maria Santos', 'email' => 'maria.santos@student.com', 'role' => 'student'],
    ['name' => 'Dr. Juan Dela Cruz', 'email' => 'juan.delacruz@faculty.com', 'role' => 'faculty'],
    ['name' => 'Ana Lopez', 'email' => 'ana.lopez@student.com', 'role' => 'student'],
    ['name' => 'Prof. Pedro Reyes', 'email' => 'pedro.reyes@faculty.com', 'role' => 'faculty'],
    ['name' => 'Lisa Garcia', 'email' => 'lisa.garcia@student.com', 'role' => 'student'],
    ['name' => 'Mark Santiago', 'email' => 'mark.santiago@student.com', 'role' => 'student'],
];

// Dummy activities
$dummy_activities = [
    ['icon' => 'upload', 'description' => 'Maria Santos submitted a new thesis: "AI-Powered System"', 'created_at' => '2024-03-15 10:30 AM'],
    ['icon' => 'check-circle', 'description' => 'Dr. Juan Dela Cruz reviewed a thesis proposal', 'created_at' => '2024-03-15 09:15 AM'],
    ['icon' => 'user-plus', 'description' => 'New student registered: Ana Lopez', 'created_at' => '2024-03-14 04:20 PM'],
    ['icon' => 'archive', 'description' => '2 projects were archived', 'created_at' => '2024-03-14 02:10 PM'],
    ['icon' => 'comment', 'description' => 'Prof. Pedro Reyes left feedback on "Mobile App" project', 'created_at' => '2024-03-14 11:45 AM'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard | Thesis Management System</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Scrollbar Styles */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #ff1a1a;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #d32f2f;
        }

        /* SIDEBAR - Using #D32F2F */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #b71c1c 0%, #d32f2f 50%, #e57373 100%);
            color: white;
            padding: 25px 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(211, 47, 47, 0.3);
        }

        .logo-container {
            padding: 0 25px;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #ffcdd2 0%, #ffebee 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .logo span {
            background: linear-gradient(135deg, #ffffff 0%, #ffebee 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-sub {
            font-size: 12px;
            color: #ffcdd2;
            margin-top: 5px;
            letter-spacing: 1px;
        }

        .nav-menu {
            flex: 1;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            margin: 5px 10px;
            border-radius: 12px;
            color: #ffebee;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }

        .nav-item i {
            width: 24px;
            font-size: 1.2rem;
            margin-right: 15px;
        }

        .nav-item span {
            font-size: 0.95rem;
            font-weight: 500;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .nav-item.active {
            background: #b71c1c;
            color: white;
            box-shadow: 0 10px 20px rgba(183, 28, 28, 0.4);
        }

        .nav-footer {
            padding: 20px 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            color: #ffebee;
            text-decoration: none;
            padding: 10px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .logout-btn i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .logout-btn:hover {
            background: rgba(211, 47, 47, 0.5);
            color: white;
            transform: translateX(5px);
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 25px 35px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        /* TOP BAR */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            background: white;
            padding: 10px 20px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.08);
            border: 1px solid #ffcdd2;
        }

        .search-area {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 15px;
            width: 350px;
        }

        .search-area i {
            color: #d32f2f;
            margin-right: 10px;
            font-size: 0.9rem;
        }

        .search-area input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 0.95rem;
        }

        .search-area input::placeholder {
            color: #fc5454;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification-icon {
            position: relative;
            width: 45px;
            height: 45px;
            background: #f8f9fa;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .notification-icon:hover {
            background: #ffcdd2;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #d32f2f;
            color: white;
            font-size: 10px;
            padding: 3px 6px;
            border-radius: 10px;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 5px 5px 5px 15px;
            background: #f8f9fa;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-info:hover {
            background: #ffcdd2;
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: #b71c1c;
            font-size: 0.95rem;
        }

        .user-role {
            font-size: 0.75rem;
            color: #d32f2f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .avatar {
            width: 45px;
            height: 45px;
            background: #d32f2f;
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.3);
        }
        .welcome-banner {
            background: linear-gradient(135deg, #b71c1c 0%, #d32f2f 50%, #e57373 100%);
            border-radius: 25px;
            padding: 30px 35px;
            margin-bottom: 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 20px 30px rgba(211, 47, 47, 0.2);
        }

        .welcome-text h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .welcome-text p {
            font-size: 1rem;
            opacity: 0.9;
        }

        .welcome-stats {
            display: flex;
            gap: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.85rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #ffcdd2;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(211, 47, 47, 0.15);
            border-color: #d32f2f;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: #d32f2f;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
        }

        .stat-icon.pending {
            background: #f93636;
        }

        .stat-icon.archived {
            background: #b71c1c;
        }

        .stat-icon.income {
            background: #ffcdd2;
            color: #b71c1c;
        }

        .stat-details h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #b71c1c;
            margin-bottom: 5px;
        }

        .stat-details p {
            color: #d32f2f;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* CHARTS SECTION */
        .charts-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.08);
            border: 1px solid #ffcdd2;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h3 {
            color: #b71c1c;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .chart-header select {
            padding: 8px 15px;
            border: 1px solid #ffcdd2;
            border-radius: 10px;
            outline: none;
            font-size: 0.9rem;
            color: #b71c1c;
            background: #f8f9fa;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        /* SECTION TITLES */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #b71c1c;
        }

        .view-all {
            color: #d32f2f;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .view-all:hover {
            gap: 10px;
            color: #ef9a9a;
        }

        /* PROJECTS TABLE */
        .projects-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.08);
            border: 1px solid #ffcdd2;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 10px;
            color: #d32f2f;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #ffcdd2;
        }

        td {
            padding: 15px 10px;
            border-bottom: 1px solid #ffebee;
            color: #da2b2b;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .status-dot.pending { 
            background: #e92323; 
            box-shadow: 0 0 0 3px rgba(239, 154, 154, 0.2);
        }
        
        .status-dot.in-progress { 
            background: #d32f2f; 
            box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.2);
        }
        
        .status-dot.completed { 
            background: #ffcdd2; 
            box-shadow: 0 0 0 3px rgba(255, 205, 210, 0.2);
        }
        
        .status-dot.archived { 
            background: #b71c1c; 
            box-shadow: 0 0 0 3px rgba(183, 28, 28, 0.2);
        }

        .status-text {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-text.pending { color: #ed2b2b; }
        .status-text.in-progress { color: #d32f2f; }
        .status-text.completed { color: #b71c1c; }
        .status-text.archived { color: #b71c1c; }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .btn-view {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #ffebee;
            color: #b71c1c;
        }

        .btn-review {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #d32f2f;
            color: white;
        }

        .btn-view:hover, .btn-review:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(211, 47, 47, 0.2);
        }

        /* USERS AND ACTIVITIES GRID */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .users-section, .activities-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.08);
            border: 1px solid #ffcdd2;
        }

        .user-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #ffebee;
        }

        .user-item:last-child {
            border-bottom: none;
        }

        .user-info-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar-sm {
            width: 45px;
            height: 45px;
            background: #d32f2f;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
        }

        .user-details-sm h4 {
            color: #b71c1c;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .user-details-sm p {
            color: #d32f2f;
            font-size: 0.85rem;
        }

        .user-role-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-student {
            background: #ffebee;
            color: #d32f2f;
        }

        .role-faculty {
            background: #ffcdd2;
            color: #b71c1c;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #ffebee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: #ffebee;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d32f2f;
            font-size: 1.2rem;
        }

        .activity-details {
            flex: 1;
        }

        .activity-text {
            color: #b71c1c;
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .activity-time {
            color: #d32f2f;
            font-size: 0.8rem;
        }

        /* QUICK ACTIONS */
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .quick-action-btn {
            flex: 1;
            background: white;
            border: 1px solid #ffcdd2;
            border-radius: 15px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #b71c1c;
        }

        .quick-action-btn:hover {
            border-color: #d32f2f;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(211, 47, 47, 0.15);
        }

        .quick-action-btn i {
            font-size: 1.8rem;
            color: #d32f2f;
        }

        .quick-action-btn span {
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo">Thesis<span>Manager</span></div>
            <div class="logo-sub">COORDINATOR PORTAL</div>
        </div>
        
        <div class="nav-menu">
            <a href="#" class="nav-item active">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Faculty</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-archive"></i>
                <span>Archived</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-comment"></i>
                <span>Feedback</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>
        
        <div class="nav-footer">
            <a href="#" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="search-area">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search projects, students, faculty...">
            </div>
            <div class="user-profile">
                <div class="notification-icon">
                    <i class="far fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-info">
                    <div class="user-details">
                        <div class="user-name"><?php echo $user_name; ?></div>
                        <div class="user-role">COORDINATOR</div>
                    </div>
                    <div class="avatar">
                        JC
                    </div>
                </div>
            </div>
        </div>

        <!-- WELCOME BANNER -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h1>Welcome back, John! 🎉</h1>
                <p>Here's what's happening with your thesis projects today.</p>
            </div>
            <div class="welcome-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['pending_reviews']; ?></div>
                    <div class="stat-label">PENDING REVIEWS</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['total_projects']; ?></div>
                    <div class="stat-label">TOTAL PROJECTS</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                    <div class="stat-label">ACTIVE STUDENTS</div>
                </div>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['total_projects']; ?></h3>
                    <p>Total Projects</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['pending_reviews']; ?></h3>
                    <p>Pending Review</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon archived">
                    <i class="fas fa-archive"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['archived_count']; ?></h3>
                    <p>Archived</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon income">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-details">
                    <h3>$<?php echo number_format($stats['total_income']); ?></h3>
                    <p>Total Income</p>
                </div>
            </div>
        </div>

        <!-- CHARTS SECTION -->
        <div class="charts-section">
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Project Overview</h3>
                    <select>
                        <option>This Week</option>
                        <option>This Month</option>
                        <option>This Year</option>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="projectsChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Status Distribution</h3>
                </div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- RECENT PROJECTS -->
        <div class="projects-section">
            <div class="section-header">
                <h2 class="section-title">Recent Projects</h2>
                <a href="#" class="view-all">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>PROJECT TITLE</th>
                            <th>AUTHOR</th>
                            <th>DEPARTMENT</th>
                            <th>DATE SUBMITTED</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dummy_projects as $project): ?>
                        <tr>
                            <td><?php echo $project['title']; ?></td>
                            <td><?php echo $project['author_name']; ?></td>
                            <td><?php echo $project['department']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                            <td>
                                <div class="status">
                                    <span class="status-dot <?php echo $project['status']; ?>"></span>
                                    <span class="status-text <?php echo $project['status']; ?>">
                                        <?php echo ucfirst(str_replace('-', ' ', $project['status'])); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="#" class="btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="#" class="btn-review">
                                        <i class="fas fa-check-circle"></i> Review
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- BOTTOM GRID: NEW USERS AND ACTIVITIES -->
        <div class="bottom-grid">
            <!-- NEW USERS SECTION -->
            <div class="users-section">
                <div class="section-header">
                    <h2 class="section-title">New Users</h2>
                    <a href="#" class="view-all">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="users-list">
                    <?php foreach ($dummy_users as $user): ?>
                    <div class="user-item">
                        <div class="user-info-left">
                            <div class="user-avatar-sm">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <div class="user-details-sm">
                                <h4><?php echo $user['name']; ?></h4>
                                <p><?php echo $user['email']; ?></p>
                            </div>
                        </div>
                        <div class="user-role-badge role-<?php echo $user['role']; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- RECENT ACTIVITIES -->
            <div class="activities-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Activities</h2>
                </div>
                <div class="activities-list">
                    <?php foreach ($dummy_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-text">
                                <?php echo $activity['description']; ?>
                            </div>
                            <div class="activity-time">
                                <?php echo $activity['created_at']; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="quick-actions">
            <a href="#" class="quick-action-btn">
                <i class="fas fa-plus-circle"></i>
                <span>New Project</span>
            </a>
            <a href="#" class="quick-action-btn">
                <i class="fas fa-user-plus"></i>
                <span>Assign Project</span>
            </a>
            <a href="#" class="quick-action-btn">
                <i class="fas fa-file-pdf"></i>
                <span>Generate Report</span>
            </a>
            <a href="#" class="quick-action-btn">
                <i class="fas fa-calendar-plus"></i>
                <span>Schedule Meeting</span>
            </a>
        </div>
    </div>

    <!-- CHARTS JAVASCRIPT -->
    <script>
        // Projects Overview Chart
        const ctx1 = document.getElementById('projectsChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Projects Submitted',
                    data: [12, 19, 15, 17, 14, 13, 15, 20, 18, 22, 25, 23],
                    borderColor: '#d32f2f',
                    backgroundColor: 'rgba(211, 47, 47, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(183, 28, 28, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Status Distribution Chart
        const ctx2 = document.getElementById('statusChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Progress', 'Completed', 'Archived'],
                datasets: [{
                    data: [15, 25, 30, 10],
                    backgroundColor: [
                        '#f63c3c',
                        '#d32f2f',
                        '#f32338',
                        '#b71c1c'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>