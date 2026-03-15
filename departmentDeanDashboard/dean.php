<?php
// TEMPORARILY DISABLED LOGIN VALIDATION - FOR DESIGN PREVIEW ONLY
session_start();
// require_once '../config/db.php';

// Temporary dummy data para department dean
$user_name = "Dr. Maria Santos";
$user_email = "maria.santos@dean.cas.edu";
$department = "College of Arts and Sciences";
$dean_since = "2022-06-15";

// Dummy statistics for department dean (department-specific lang)
$stats = [
    'total_students' => 342,
    'total_faculty' => 28,
    'total_projects' => 87,
    'ongoing_projects' => 34,
    'completed_projects' => 42,
    'pending_reviews' => 11,
    'archived_count' => 15,
    'theses_approved' => 23,
    'theses_pending' => 8
];

// Faculty members under the department
$faculty_members = [
    ['id' => 1, 'name' => 'Prof. Juan Dela Cruz', 'specialization' => 'Computer Science', 'projects_supervised' => 8, 'status' => 'active'],
    ['id' => 2, 'name' => 'Dr. Ana Lopez', 'specialization' => 'Mathematics', 'projects_supervised' => 6, 'status' => 'active'],
    ['id' => 3, 'name' => 'Prof. Pedro Reyes', 'specialization' => 'Physics', 'projects_supervised' => 4, 'status' => 'active'],
    ['id' => 4, 'name' => 'Dr. Lisa Garcia', 'specialization' => 'Chemistry', 'projects_supervised' => 5, 'status' => 'on-leave'],
    ['id' => 5, 'name' => 'Prof. Mark Santiago', 'specialization' => 'Biology', 'projects_supervised' => 7, 'status' => 'active'],
    ['id' => 6, 'name' => 'Dr. Karen Villanueva', 'specialization' => 'Literature', 'projects_supervised' => 3, 'status' => 'active'],
];

// Department projects
$department_projects = [
    ['id' => 1, 'title' => 'AI-Powered Thesis Recommendation System', 'student' => 'Maria Santos', 'adviser' => 'Prof. Juan Dela Cruz', 'submitted' => '2024-03-15', 'status' => 'pending', 'defense_date' => null],
    ['id' => 2, 'title' => 'Mobile App for Campus Navigation', 'student' => 'Juan Dela Cruz', 'adviser' => 'Dr. Ana Lopez', 'submitted' => '2024-03-14', 'status' => 'in-progress', 'defense_date' => '2024-04-20'],
    ['id' => 3, 'title' => 'E-Learning Platform for Mathematics', 'student' => 'Ana Lopez', 'adviser' => 'Prof. Pedro Reyes', 'submitted' => '2024-03-13', 'status' => 'completed', 'defense_date' => '2024-03-30'],
    ['id' => 4, 'title' => 'IoT-Based Classroom Monitoring', 'student' => 'Pedro Reyes', 'adviser' => 'Dr. Lisa Garcia', 'submitted' => '2024-03-12', 'status' => 'pending', 'defense_date' => null],
    ['id' => 5, 'title' => 'Blockchain for Student Records', 'student' => 'Lisa Garcia', 'adviser' => 'Prof. Mark Santiago', 'submitted' => '2024-03-11', 'status' => 'archived', 'defense_date' => '2024-02-15'],
    ['id' => 6, 'title' => 'Virtual Reality Campus Tour', 'student' => 'Mark Santiago', 'adviser' => 'Dr. Karen Villanueva', 'submitted' => '2024-03-10', 'status' => 'in-progress', 'defense_date' => '2024-05-10'],
    ['id' => 7, 'title' => 'Automated Grading System', 'student' => 'Karen Villanueva', 'adviser' => 'Prof. Juan Dela Cruz', 'submitted' => '2024-03-09', 'status' => 'pending', 'defense_date' => null],
    ['id' => 8, 'title' => 'Student Portfolio Generator', 'student' => 'Paul Mendoza', 'adviser' => 'Dr. Ana Lopez', 'submitted' => '2024-03-08', 'status' => 'completed', 'defense_date' => '2024-03-25'],
    ['id' => 9, 'title' => 'Data Mining for Student Performance', 'student' => 'Jose Rizal', 'adviser' => 'Prof. Pedro Reyes', 'submitted' => '2024-03-07', 'status' => 'in-progress', 'defense_date' => '2024-05-05'],
    ['id' => 10, 'title' => 'Mobile Learning App', 'student' => 'Gabriela Silang', 'adviser' => 'Dr. Lisa Garcia', 'submitted' => '2024-03-06', 'status' => 'pending', 'defense_date' => '2024-05-15'],
];

// Upcoming defenses (schedule) - Updated to match image format
$upcoming_defenses = [
    [
        'id' => 1, 
        'student' => 'Juan Dela Cruz', 
        'title' => 'Mobile App for Campus Navigation', 
        'date' => '2024-04-20', 
        'time' => '10:00 AM', 
        'panelists' => 'Dr. Ana Lopez, Prof. Pedro Reyes'
    ],
    [
        'id' => 2, 
        'student' => 'Mark Santiago', 
        'title' => 'Virtual Reality Campus Tour', 
        'date' => '2024-05-10', 
        'time' => '2:00 PM', 
        'panelists' => 'Dr. Karen Villanueva, Prof. Juan Dela Cruz'
    ],
    [
        'id' => 3, 
        'student' => 'Jose Rizal', 
        'title' => 'Data Mining for Student Performance', 
        'date' => '2024-05-05', 
        'time' => '1:30 PM', 
        'panelists' => 'Prof. Pedro Reyes, Dr. Lisa Garcia'
    ],
    [
        'id' => 4, 
        'student' => 'Gabriela Silang', 
        'title' => 'Mobile Learning App', 
        'date' => '2024-05-15', 
        'time' => '9:00 AM', 
        'panelists' => 'Dr. Lisa Garcia, Prof. Mark Santiago'
    ],
];

// Recent activities (department-specific)
$department_activities = [
    ['icon' => 'check-circle', 'description' => 'Thesis proposal approved: "AI-Powered System"', 'user' => 'Prof. Juan Dela Cruz', 'created_at' => '2024-03-15 10:30 AM'],
    ['icon' => 'calendar-check', 'description' => 'Defense scheduled for "Mobile App" project', 'user' => 'Dr. Ana Lopez', 'created_at' => '2024-03-15 09:15 AM'],
    ['icon' => 'file-pdf', 'description' => 'Monthly department report generated', 'user' => 'System', 'created_at' => '2024-03-15 08:00 AM'],
    ['icon' => 'user-graduate', 'description' => 'New student registered in department', 'user' => 'Maria Santos', 'created_at' => '2024-03-14 04:20 PM'],
    ['icon' => 'comment', 'description' => 'Feedback submitted for "IoT-Based Classroom"', 'user' => 'Dr. Lisa Garcia', 'created_at' => '2024-03-14 11:45 AM'],
    ['icon' => 'award', 'description' => 'Project "E-Learning Platform" completed', 'user' => 'Prof. Pedro Reyes', 'created_at' => '2024-03-14 10:30 AM'],
];

// Faculty workload distribution
$workload_stats = [
    'max_supervised' => 8,
    'avg_supervised' => 5.5,
    'under_load' => 2,
    'over_load' => 1
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Dean Dashboard | Thesis Management System</title>
    
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
            background: #ef9a9a;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #d32f2f;
        }

        /* SIDEBAR - Same red theme */
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
            color: #333333;
        }

        .search-area input::placeholder {
            color: #999999;
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
            color: #333333;
            font-size: 0.95rem;
        }

        .user-role {
            font-size: 0.75rem;
            color: #666666;
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

        /* DEPARTMENT INFO BANNER */
        .dept-banner {
            background: linear-gradient(135deg, #b71c1c 0%, #d32f2f 100%);
            border-radius: 25px;
            padding: 30px 35px;
            margin-bottom: 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 20px 30px rgba(211, 47, 47, 0.2);
        }

        .dept-info h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: white;
        }

        .dept-info p {
            font-size: 1rem;
            opacity: 0.9;
            color: white;
        }

        .dean-info {
            text-align: right;
        }

        .dean-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
        }

        .dean-since {
            font-size: 0.9rem;
            opacity: 0.8;
            color: white;
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

        .stat-icon.secondary {
            background: #ef9a9a;
        }

        .stat-icon.tertiary {
            background: #ffcdd2;
            color: #b71c1c;
        }

        .stat-details h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #333333;
            margin-bottom: 5px;
        }

        .stat-details p {
            color: #666666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* SECOND ROW STATS - Department specific */
        .dept-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .dept-stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.08);
            border: 1px solid #ffcdd2;
        }

        .dept-stat-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #333333;
            font-weight: 600;
        }

        .dept-stat-header i {
            color: #d32f2f;
        }

        .dept-stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333333;
        }

        .dept-stat-label {
            color: #666666;
            font-size: 0.85rem;
        }

        /* CHARTS SECTION */
        .charts-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
            color: #333333;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .chart-header select {
            padding: 8px 15px;
            border: 1px solid #ffcdd2;
            border-radius: 10px;
            outline: none;
            font-size: 0.9rem;
            color: #333333;
            background: #f8f9fa;
        }

        .chart-container {
            height: 250px;
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
            color: #333333;
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

        /* FACULTY LIST */
        .faculty-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #ffcdd2;
        }

        .faculty-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .faculty-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            border: 1px solid #ffebee;
            transition: all 0.3s ease;
        }

        .faculty-card:hover {
            border-color: #d32f2f;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(211, 47, 47, 0.1);
        }

        .faculty-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .faculty-avatar {
            width: 50px;
            height: 50px;
            background: #d32f2f;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .faculty-name {
            font-weight: 600;
            color: #333333;
            margin-bottom: 3px;
        }

        .faculty-spec {
            font-size: 0.85rem;
            color: #666666;
        }

        .faculty-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ffebee;
        }

        .faculty-stat {
            text-align: center;
        }

        .faculty-stat-value {
            font-weight: 700;
            color: #333333;
        }

        .faculty-stat-label {
            font-size: 0.7rem;
            color: #999999;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-badge.active {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .status-badge.on-leave {
            background: #ffecb3;
            color: #b76e1c;
        }

        /* PROJECTS TABLE */
        .projects-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
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
            color: #666666;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #ffcdd2;
        }

        td {
            padding: 15px 10px;
            border-bottom: 1px solid #ffebee;
            color: #333333;
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
            background: #ef9a9a; 
            box-shadow: 0 0 0 3px rgba(239, 154, 154, 0.2);
        }
        
        .status-dot.in-progress { 
            background: #d32f2f; 
            box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.2);
        }
        
        .status-dot.completed { 
            background: #81c784; 
            box-shadow: 0 0 0 3px rgba(129, 199, 132, 0.2);
        }
        
        .status-dot.archived { 
            background: #b71c1c; 
            box-shadow: 0 0 0 3px rgba(183, 28, 28, 0.2);
        }

        .status-text {
            font-size: 0.9rem;
            font-weight: 500;
            color: #333333;
        }

        .defense-date {
            font-size: 0.85rem;
            color: #666666;
        }

        .defense-date i {
            color: #d32f2f;
            margin-right: 5px;
        }

        .btn-view {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #ffebee;
            color: #333333;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(211, 47, 47, 0.2);
            background: #ffcdd2;
        }

        /* UPCOMING DEFENSES - Updated format */
        .defenses-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #ffcdd2;
        }

        .defense-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            border-bottom: 1px solid #ffebee;
        }

        .defense-item:last-child {
            border-bottom: none;
        }

        .defense-date-box {
            min-width: 70px;
            text-align: center;
            background: #ffebee;
            padding: 10px 5px;
            border-radius: 12px;
            border: 1px solid #ffcdd2;
        }

        .defense-day {
            font-size: 1.8rem;
            font-weight: 700;
            color: #b71c1c;
            line-height: 1.2;
        }

        .defense-month {
            font-size: 0.8rem;
            color: #666666;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .defense-details {
            flex: 1;
        }

        .defense-title {
            font-weight: 600;
            color: #333333;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .defense-meta {
            display: flex;
            gap: 20px;
            font-size: 0.9rem;
            color: #666666;
            margin-bottom: 5px;
        }

        .defense-meta i {
            color: #d32f2f;
            margin-right: 5px;
            width: 16px;
        }

        .defense-panel {
            font-size: 0.85rem;
            color: #666666;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
        }

        /* BOTTOM GRID */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .activities-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            border: 1px solid #ffcdd2;
        }

        .workload-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            border: 1px solid #ffcdd2;
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
            color: #333333;
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .activity-meta {
            display: flex;
            gap: 15px;
            color: #999999;
            font-size: 0.8rem;
        }

        .activity-user {
            color: #d32f2f;
            font-weight: 500;
        }

        /* WORKLOAD INDICATORS */
        .workload-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ffebee;
        }

        .workload-label {
            color: #666666;
        }

        .workload-value {
            font-weight: 700;
            color: #333333;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #ffebee;
            border-radius: 4px;
            margin-top: 5px;
        }

        .progress-fill {
            height: 8px;
            background: #d32f2f;
            border-radius: 4px;
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
            color: #333333;
        }

        .quick-action-btn:hover {
            border-color: #d32f2f;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(211, 47, 47, 0.15);
            background: #fff5f5;
        }

        .quick-action-btn i {
            font-size: 1.8rem;
            color: #d32f2f;
        }

        .quick-action-btn span {
            font-size: 0.9rem;
            font-weight: 600;
            color: #333333;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo">Thesis<span>Manager</span></div>
            <div class="logo-sub">DEPARTMENT DEAN</div>
        </div>
        
        <div class="nav-menu">
            <a href="#" class="nav-item active">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Faculty</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-calendar-check"></i>
                <span>Defenses</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-archive"></i>
                <span>Archive</span>
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
                <input type="text" placeholder="Search faculty, students, projects...">
            </div>
            <div class="user-profile">
                <div class="notification-icon">
                    <i class="far fa-bell"></i>
                    <span class="notification-badge">4</span>
                </div>
                <div class="user-info">
                    <div class="user-details">
                        <div class="user-name"><?php echo $user_name; ?></div>
                        <div class="user-role">DEPARTMENT DEAN</div>
                    </div>
                    <div class="avatar">
                        MS
                    </div>
                </div>
            </div>
        </div>

        <!-- DEPARTMENT INFO BANNER -->
        <div class="dept-banner">
            <div class="dept-info">
                <h1><?php echo $department; ?></h1>
                <p>Department Dashboard • Overview of faculty, students, and projects</p>
            </div>
            <div class="dean-info">
                <div class="dean-name">Dr. Maria Santos</div>
                <div class="dean-since">Dean since June 2022</div>
            </div>
        </div>

        <!-- STATS CARDS - Row 1 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['total_students']; ?></h3>
                    <p>Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['total_faculty']; ?></h3>
                    <p>Faculty</p>
                </div>
            </div>
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
                <div class="stat-icon secondary">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['pending_reviews']; ?></h3>
                    <p>Pending Reviews</p>
                </div>
            </div>
        </div>

        <!-- Department Stats - Row 2 -->
        <div class="dept-stats">
            <div class="dept-stat-card">
                <div class="dept-stat-header">
                    <i class="fas fa-check-circle"></i>
                    <span>Completed</span>
                </div>
                <div class="dept-stat-value"><?php echo $stats['completed_projects']; ?></div>
                <div class="dept-stat-label">theses & projects</div>
            </div>
            <div class="dept-stat-card">
                <div class="dept-stat-header">
                    <i class="fas fa-spinner"></i>
                    <span>Ongoing</span>
                </div>
                <div class="dept-stat-value"><?php echo $stats['ongoing_projects']; ?></div>
                <div class="dept-stat-label">active projects</div>
            </div>
            <div class="dept-stat-card">
                <div class="dept-stat-header">
                    <i class="fas fa-gavel"></i>
                    <span>Defenses</span>
                </div>
                <div class="dept-stat-value"><?php echo count($upcoming_defenses); ?></div>
                <div class="dept-stat-label">upcoming defenses</div>
            </div>
            <div class="dept-stat-card">
                <div class="dept-stat-header">
                    <i class="fas fa-check-double"></i>
                    <span>Approved</span>
                </div>
                <div class="dept-stat-value"><?php echo $stats['theses_approved']; ?></div>
                <div class="dept-stat-label">theses this sem</div>
            </div>
        </div>

        <!-- CHARTS SECTION -->
        <div class="charts-section">
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Project Status Distribution</h3>
                </div>
                <div class="chart-container">
                    <canvas id="projectStatusChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Faculty Workload</h3>
                    <select>
                        <option>This Semester</option>
                        <option>Last Semester</option>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="workloadChart"></canvas>
                </div>
            </div>
        </div>

        <!-- FACULTY SECTION -->
        <div class="faculty-section">
            <div class="section-header">
                <h2 class="section-title">Department Faculty</h2>
                <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="faculty-grid">
                <?php foreach ($faculty_members as $faculty): ?>
                <div class="faculty-card">
                    <div class="faculty-header">
                        <div class="faculty-avatar">
                            <?php echo strtoupper(substr($faculty['name'], 0, 1) . substr(explode(' ', $faculty['name'])[1] ?? '', 0, 1)); ?>
                        </div>
                        <div>
                            <div class="faculty-name"><?php echo $faculty['name']; ?></div>
                            <div class="faculty-spec"><?php echo $faculty['specialization']; ?></div>
                        </div>
                    </div>
                    <div class="faculty-stats">
                        <div class="faculty-stat">
                            <div class="faculty-stat-value"><?php echo $faculty['projects_supervised']; ?></div>
                            <div class="faculty-stat-label">Projects</div>
                        </div>
                        <div class="faculty-stat">
                            <div class="faculty-stat-value">
                                <span class="status-badge <?php echo $faculty['status']; ?>">
                                    <?php echo ucfirst($faculty['status']); ?>
                                </span>
                            </div>
                            <div class="faculty-stat-label">Status</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RECENT PROJECTS -->
        <div class="projects-section">
            <div class="section-header">
                <h2 class="section-title">Recent Department Projects</h2>
                <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>PROJECT TITLE</th>
                            <th>STUDENT</th>
                            <th>ADVISER</th>
                            <th>DEFENSE DATE</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($department_projects, 0, 5) as $project): ?>
                        <tr>
                            <td><?php echo $project['title']; ?></td>
                            <td><?php echo $project['student']; ?></td>
                            <td><?php echo $project['adviser']; ?></td>
                            <td>
                                <?php if ($project['defense_date']): ?>
                                    <span class="defense-date">
                                        <i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($project['defense_date'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="defense-date">Not scheduled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="status">
                                    <span class="status-dot <?php echo $project['status']; ?>"></span>
                                    <span class="status-text <?php echo $project['status']; ?>">
                                        <?php echo ucfirst(str_replace('-', ' ', $project['status'])); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <a href="#" class="btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- UPCOMING DEFENSES - Updated format -->
        <div class="defenses-section">
            <div class="section-header">
                <h2 class="section-title">Upcoming Thesis Defenses</h2>
                <a href="#" class="view-all">Schedule New <i class="fas fa-plus"></i></a>
            </div>
            
            <?php foreach ($upcoming_defenses as $defense): ?>
            <div class="defense-item">
                <div class="defense-date-box">
                    <div class="defense-day"><?php echo date('d', strtotime($defense['date'])); ?></div>
                    <div class="defense-month"><?php echo strtoupper(date('M', strtotime($defense['date']))); ?></div>
                </div>
                <div class="defense-details">
                    <div class="defense-title"><?php echo $defense['title']; ?></div>
                    <div class="defense-meta">
                        <span><i class="fas fa-user-graduate"></i> <?php echo $defense['student']; ?></span>
                        <span><i class="far fa-clock"></i> <?php echo $defense['time']; ?></span>
                    </div>
                    <div class="defense-panel">
                        <i class="fas fa-users"></i> Panel: <?php echo $defense['panelists']; ?>
                    </div>
                </div>
                <a href="#" class="btn-view">
                    <i class="fas fa-calendar-check"></i> Details
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- BOTTOM GRID: Activities and Workload -->
        <div class="bottom-grid">
            <!-- RECENT ACTIVITIES -->
            <div class="activities-section">
                <div class="section-header">
                    <h2 class="section-title">Department Activities</h2>
                    <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="activities-list">
                    <?php foreach ($department_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-text">
                                <?php echo $activity['description']; ?>
                            </div>
                            <div class="activity-meta">
                                <span><i class="far fa-clock"></i> <?php echo $activity['created_at']; ?></span>
                                <span class="activity-user"><i class="fas fa-user"></i> <?php echo $activity['user']; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- FACULTY WORKLOAD SUMMARY -->
            <div class="workload-section">
                <div class="section-header">
                    <h2 class="section-title">Faculty Workload Summary</h2>
                    <a href="#" class="view-all">Details <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="workload-item">
                    <span class="workload-label">Average Projects per Faculty</span>
                    <span class="workload-value"><?php echo $workload_stats['avg_supervised']; ?></span>
                </div>
                <div class="workload-item">
                    <span class="workload-label">Maximum Projects Supervised</span>
                    <span class="workload-value"><?php echo $workload_stats['max_supervised']; ?></span>
                </div>
                <div class="workload-item">
                    <span class="workload-label">Faculty Under Load (&lt; 3 projects)</span>
                    <span class="workload-value"><?php echo $workload_stats['under_load']; ?></span>
                </div>
                <div class="workload-item">
                    <span class="workload-label">Faculty Over Load (&gt; 6 projects)</span>
                    <span class="workload-value"><?php echo $workload_stats['over_load']; ?></span>
                </div>
                
                <div style="margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span class="workload-label">Workload Distribution</span>
                        <span class="workload-value">70%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 70%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="quick-actions">
            <a href="#" class="quick-action-btn">
                <i class="fas fa-calendar-plus"></i>
                <span>Schedule Defense</span>
            </a>
            <a href="#" class="quick-action-btn">
                <i class="fas fa-file-pdf"></i>
                <span>Department Report</span>
            </a>
            <a href="#" class="quick-action-btn">
                <i class="fas fa-chart-line"></i>
                <span>View Analytics</span>
            </a>
            <a href="#" class="quick-action-btn">
                <i class="fas fa-user-plus"></i>
                <span>Add Faculty</span>
            </a>
            <a href="#" class="quick-action-btn">
                <i class="fas fa-envelope"></i>
                <span>Announcement</span>
            </a>
        </div>
    </div>

    <!-- CHARTS JAVASCRIPT -->
    <script>
        // Project Status Chart
        new Chart(document.getElementById('projectStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Progress', 'Completed', 'Archived'],
                datasets: [{
                    data: [<?php echo $stats['pending_reviews']; ?>, <?php echo $stats['ongoing_projects']; ?>, <?php echo $stats['completed_projects']; ?>, <?php echo $stats['archived_count']; ?>],
                    backgroundColor: [ '#d32f2f', '#d32f2f', '#81c784', '#b71c1c'],
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
                            pointStyle: 'circle',
                            color: '#333333'
                        }
                    }
                },
                cutout: '70%'
            }
        });

        // Workload Chart
        new Chart(document.getElementById('workloadChart'), {
            type: 'bar',
            data: {
                labels: ['Prof. Dela Cruz', 'Dr. Lopez', 'Prof. Reyes', 'Dr. Garcia', 'Prof. Santiago', 'Dr. Villanueva'],
                datasets: [{
                    label: 'Projects Supervised',
                    data: [8, 6, 4, 5, 7, 3],
                    backgroundColor: '#d32f2f',
                    borderRadius: 6
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
                        max: 10,
                        grid: {
                            color: 'rgba(183, 28, 28, 0.05)'
                        },
                        ticks: {
                            color: '#333333'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#333333'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>