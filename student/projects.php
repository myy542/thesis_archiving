<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["user_id"])) {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

$roleQuery = "SELECT role_id FROM user_table WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($roleQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userData || $userData['role_id'] != 2) {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$stmt = $conn->prepare("SELECT first_name, last_name FROM user_table WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$fullName = trim($user["first_name"] . " " . $user["last_name"]);
$initials = strtoupper(substr($user["first_name"], 0, 1) . substr($user["last_name"], 0, 1));

// Get the correct student_id from student_table
$student_id = $user_id;
$studentQuery = "SELECT student_id FROM student_table WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$studentResult = $stmt->get_result();
$studentData = $studentResult->fetch_assoc();
$stmt->close();

if ($studentData) {
    $student_id = $studentData['student_id'];
}

$projects = [];

try {
    // =============== SIMPLE WORKING QUERY (based on debug) ===============
    $query = "SELECT t.*, 
                     COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'Not Assigned') as adviser_name,
                     (SELECT COUNT(*) FROM feedback_table WHERE thesis_id = t.thesis_id) as feedback_count
              FROM thesis_table t
              LEFT JOIN user_table u ON t.adviser_id = u.user_id
              WHERE t.student_id = ?
              ORDER BY t.date_submitted DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
    $stmt->close();
    
    // Debug log (optional - remove later)
    error_log("Projects found: " . count($projects));
    
} catch (Exception $e) {
    error_log("Projects fetch error: " . $e->getMessage());
}

// =============== GET CERTIFICATES FROM certificates_table ===============
$certificates = [];
if (!empty($projects)) {
    $thesisIds = array_column($projects, 'thesis_id');
    if (!empty($thesisIds)) {
        $placeholders = implode(',', array_fill(0, count($thesisIds), '?'));
        
        $certQuery = "SELECT thesis_id, certificate_id, certificate_file, downloaded_count 
                      FROM certificates_table 
                      WHERE thesis_id IN ($placeholders)";
        $stmt = $conn->prepare($certQuery);
        $stmt->bind_param(str_repeat('i', count($thesisIds)), ...$thesisIds);
        $stmt->execute();
        $certResult = $stmt->get_result();
        
        while ($row = $certResult->fetch_assoc()) {
            $certificates[$row['thesis_id']] = $row;
        }
        $stmt->close();
    }
}

function calculateProgress($status, $feedback_count) {
    switch($status) {
        case 'approved':
            return 100;
        case 'rejected':
            return 30;
        case 'archived':
            return 100;
        case 'pending':
        default:
            $progress = 30 + min($feedback_count * 15, 55);
            return min($progress, 85);
    }
}

function getStatusClass($status) {
    switch($status) {
        case 'approved':
            return 'status-approved';
        case 'rejected':
            return 'status-rejected';
        case 'archived':
            return 'status-archived';
        case 'pending':
        default:
            return 'status-pending';
    }
}

function getStatusText($status) {
    switch($status) {
        case 'approved':
            return 'Approved';
        case 'rejected':
            return 'Rejected';
        case 'archived':
            return 'Archived';
        case 'pending':
        default:
            return 'Under Review';
    }
}

$pageTitle = "My Projects";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle) ?> - Theses Archiving System</title>
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
      background: #f5f5f5;
    }

    body.dark-mode {
      background: #2d2d2d;
      color: #e0e0e0;
    }

    .layout {
      min-height: 100vh;
      position: relative;
    }

    .main-content {
      padding: 2rem;
      max-width: 1400px;
      margin: 0 auto;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding: 1rem;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(110, 110, 110, 0.1);
    }

    body.dark-mode .topbar {
      background: #3a3a3a;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }

    .topbar h1 {
      font-size: 1.875rem;
      color: #732529;
    }

    body.dark-mode .topbar h1 {
      color: #FE4853;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .user-name {
      color: #6E6E6E;
    }

    body.dark-mode .user-name {
      color: #e0e0e0;
    }

    .avatar {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      background: linear-gradient(135deg, #FE4853 0%, #732529 100%);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 1rem;
      cursor: pointer;
      border: 2px solid white;
    }

    .avatar:hover {
      transform: scale(1.05);
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0;
      left: -300px;
      width: 280px;
      height: 100vh;
      background: linear-gradient(180deg, #FE4853 0%, #732529 100%);
      color: white;
      display: flex;
      flex-direction: column;
      z-index: 1000;
      transition: left 0.3s ease;
      box-shadow: 5px 0 20px rgba(0,0,0,0.3);
    }

    .sidebar.show {
      left: 0;
    }

    .sidebar-header {
      padding: 2rem 1.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .sidebar-header h2 {
      font-size: 1.5rem;
      margin-bottom: 0.25rem;
      color: white;
      font-weight: 700;
    }

    .sidebar-header p {
      font-size: 0.875rem;
      color: rgba(255, 255, 255, 0.9);
    }

    .sidebar-nav {
      flex: 1;
      padding: 1.5rem 0.5rem;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.875rem 1rem;
      color: rgba(255, 255, 255, 0.9);
      text-decoration: none;
      border-radius: 8px;
      margin-bottom: 0.25rem;
      transition: all 0.2s;
      font-weight: 500;
    }

    .nav-link i {
      width: 20px;
      color: white;
    }

    .nav-link:hover {
      background: rgba(255, 255, 255, 0.2);
      color: white;
    }

    .nav-link.active {
      background: rgba(255, 255, 255, 0.3);
      color: white;
      font-weight: 600;
    }

    .nav-link.active i {
      color: white;
    }

    .sidebar-footer {
      padding: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    .logout-btn {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.875rem 1rem;
      color: rgba(255, 255, 255, 0.9);
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.2s;
      font-weight: 500;
    }

    .logout-btn i {
      color: white;
    }

    .logout-btn:hover {
      background: rgba(255, 255, 255, 0.2);
      color: white;
    }

    /* Hamburger menu */
    .hamburger-menu {
      font-size: 1.5rem;
      cursor: pointer;
      color: #FE4853;
      width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.3s ease;
      display: none;
    }

    .hamburger-menu:hover {
      background: rgba(254, 72, 83, 0.1);
      color: #732529;
    }

    body.dark-mode .hamburger-menu {
      color: #FE4853;
    }

    /* Mobile menu button */
    .mobile-menu-btn {
      position: fixed;
      top: 16px;
      right: 16px;
      z-index: 1001;
      border: none;
      background: #FE4853;
      color: #fff;
      padding: 12px 15px;
      border-radius: 10px;
      cursor: pointer;
      display: none;
      font-size: 1.2rem;
      box-shadow: 0 4px 12px rgba(254, 72, 83, 0.3);
      border: 1px solid white;
    }

    /* Overlay */
    .overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 999;
    }

    .overlay.show {
      display: block;
    }

    /* Projects Container */
    .projects-container {
      display: flex;
      flex-direction: column;
      gap: 1.8rem;
      margin-top: 1.5rem;
    }

    .project-card {
      background: white;
      border-radius: 12px;
      padding: 1.8rem 2rem;
      box-shadow: 0 3px 14px rgba(0,0,0,0.07);
      transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.5s ease;
      scroll-margin-top: 100px;
    }

    body.dark-mode .project-card {
      background: #1e293b;
      box-shadow: 0 4px 16px rgba(0,0,0,0.35);
    }

    .project-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 24px rgba(0,0,0,0.12);
    }

    .project-card.highlight {
      background-color: #fff3cd;
      border-left: 4px solid #FE4853;
    }

    body.dark-mode .project-card.highlight {
      background-color: #4a3a2a;
      border-left-color: #FE4853;
    }

    .project-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1.4rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .project-header h2 {
      margin: 0;
      font-size: 1.28rem;
      line-height: 1.35;
      color: #732529;
    }

    body.dark-mode .project-header h2 {
      color: #FE4853;
    }

    .status {
      padding: 0.38rem 1rem;
      border-radius: 999px;
      font-size: 0.88rem;
      font-weight: 550;
    }

    .status-approved { 
      background: #d4edda; 
      color: #155724; 
    }
    .status-pending { 
      background: #fef3c7; 
      color: #92400e; 
    }
    .status-rejected { 
      background: #f8d7da; 
      color: #721c24; 
    }
    .status-archived { 
      background: #e2e8f0; 
      color: #475569; 
    }

    body.dark-mode .status-approved  { 
      background: #064e3b; 
      color: #86efac; 
    }
    body.dark-mode .status-pending { 
      background: #78350f; 
      color: #fcd34d; 
    }
    body.dark-mode .status-rejected { 
      background: #7f1d1d; 
      color: #fecaca; 
    }
    body.dark-mode .status-archived { 
      background: #334155; 
      color: #cbd5e1; 
    }

    .project-progress {
      margin: 1.2rem 0 1.6rem;
    }

    .progress-label {
      font-size: 0.94rem;
      margin-bottom: 0.5rem;
      color: #475569;
      display: flex;
      justify-content: space-between;
    }

    body.dark-mode .progress-label {
      color: #cbd5e1;
    }

    .progress-bar {
      height: 10px;
      background: #e2e8f0;
      border-radius: 999px;
      overflow: hidden;
      margin-bottom: 0.4rem;
    }

    body.dark-mode .progress-bar {
      background: #334155;
    }

    .progress-fill {
      height: 100%;
      background: #3b82f6;
      transition: width 0.5s ease;
    }

    .progress-percentage {
      font-size: 0.92rem;
      font-weight: 600;
      color: #2563eb;
      text-align: right;
    }

    .project-meta {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      font-size: 0.96rem;
      color: #475569;
      margin-bottom: 1.6rem;
    }

    body.dark-mode .project-meta {
      color: #cbd5e1;
    }

    .project-meta i {
      width: 20px;
      color: #FE4853;
      margin-right: 0.5rem;
    }

    .project-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.9rem;
    }

    .btn {
      padding: 0.6rem 1.2rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.9rem;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
      transition: all 0.3s;
    }

    .btn-primary {
      background: #FE4853;
      color: white;
    }

    .btn-primary:hover {
      background: #732529;
      transform: translateY(-2px);
    }

    .btn-secondary {
      background: #e2e8f0;
      color: #475569;
    }

    .btn-secondary:hover {
      background: #cbd5e1;
      transform: translateY(-2px);
    }

    .btn-success {
      background: #28a745;
      color: white;
    }

    .btn-success:hover {
      background: #218838;
      transform: translateY(-2px);
    }

    .btn-certificate {
      background: #f59e0b;
      color: white;
      padding: 0.6rem 1.2rem;
      border-radius: 6px;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s;
      border: none;
      cursor: pointer;
    }

    .btn-certificate:hover {
      background: #d97706;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }

    .certificate-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      background: #f59e0b;
      color: white;
      padding: 0.2rem 0.6rem;
      border-radius: 20px;
      font-size: 0.7rem;
      margin-left: 0.5rem;
    }

    .certificate-stats {
      font-size: 0.7rem;
      color: #f59e0b;
      margin-left: 0.3rem;
    }

    .btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .no-projects {
      text-align: center;
      padding: 4rem 2rem;
      background: white;
      border-radius: 12px;
      color: #6E6E6E;
      max-width: 600px;
      margin: 2rem auto;
    }

    body.dark-mode .no-projects {
      background: #1e293b;
    }

    .no-projects i {
      font-size: 4rem;
      color: #FE4853;
      margin-bottom: 1rem;
      opacity: 0.5;
    }

    .no-projects h3 {
      color: #732529;
      margin-bottom: 0.5rem;
      font-size: 1.5rem;
    }

    body.dark-mode .no-projects h3 {
      color: #FE4853;
    }

    .no-projects p {
      color: #6E6E6E;
      margin-bottom: 1.5rem;
      font-size: 1rem;
    }

    .feedback-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      background: #10b981;
      color: white;
      padding: 0.2rem 0.6rem;
      border-radius: 20px;
      font-size: 0.7rem;
      margin-left: 0.5rem;
    }

    /* Scroll to project styling */
    .project-card {
      scroll-margin-top: 100px;
    }

    .project-card.highlight {
      background-color: #fff3cd;
      border-left: 4px solid #FE4853;
      transition: background-color 0.5s ease;
    }

    body.dark-mode .project-card.highlight {
      background-color: #4a3a2a;
      border-left-color: #FE4853;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .main-content {
        padding: 1rem;
      }

      .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }

      .user-info {
        width: 100%;
        justify-content: flex-start;
      }

      .hamburger-menu {
        display: flex;
      }

      .mobile-menu-btn {
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.show {
        transform: translateX(0);
      }

      .project-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .project-actions {
        flex-direction: column;
        align-items: stretch;
      }

      .project-actions .btn,
      .project-actions .btn-certificate {
        width: 100%;
        justify-content: center;
      }

      .project-card {
        padding: 1.5rem;
      }
    }

    @media (max-width: 480px) {
      .topbar h1 {
        font-size: 1.3rem;
      }

      .avatar {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
      }

      .user-name {
        font-size: 0.9rem;
      }

      .no-projects {
        padding: 3rem 1rem;
      }
      
      .no-projects i {
        font-size: 3rem;
      }
      
      .no-projects h3 {
        font-size: 1.3rem;
      }
    }
  </style>
</head>
<body>

<!-- OVERLAY -->
<div class="overlay" id="overlay"></div>

<!-- MOBILE MENU BUTTON -->
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h2>Theses Archive</h2>
    <p>Student Portal</p>
  </div>

  <nav class="sidebar-nav">
    <a href="student_dashboard.php" class="nav-link">
      <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="projects.php" class="nav-link active">
      <i class="fas fa-folder-open"></i> My Projects
    </a>
    <a href="submission.php" class="nav-link">
      <i class="fas fa-upload"></i> Submit Thesis
    </a>
    <a href="archived.php" class="nav-link">
      <i class="fas fa-archive"></i> Archived Theses
    </a>
    <a href="profile.php" class="nav-link">
      <i class="fas fa-user-circle"></i> Profile
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="/ArchivingThesis/authentication/logout.php" class="logout-btn">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</aside>

<div class="layout">
  <main class="main-content">

    <header class="topbar">
      <div style="display: flex; align-items: center; gap: 1rem;">
        <!-- Three-line menu -->
        <div class="hamburger-menu" id="hamburgerBtn">
          <i class="fas fa-bars"></i>
        </div>
        <h1>My Current Projects</h1>
      </div>

      <div class="user-info">
        <span class="user-name"><?= htmlspecialchars($fullName) ?></span>
        <div class="avatar"><?= htmlspecialchars($initials) ?></div>
      </div>
    </header>

    <div class="projects-container">
      <?php if (empty($projects)): ?>
        <div class="no-projects">
          <i class="fas fa-folder-open"></i>
          <h3>No Projects Yet</h3>
          <p>You haven't submitted any thesis projects yet.</p>
          <a href="submission.php" class="btn btn-primary" style="margin-top: 1rem;">
            <i class="fas fa-upload"></i> Submit Your First Thesis
          </a>
        </div>
      <?php else: ?>
        <?php foreach ($projects as $project): 
          $progress = calculateProgress($project['status'], $project['feedback_count']);
          $statusClass = getStatusClass($project['status']);
          $statusText = getStatusText($project['status']);
          $hasCertificate = isset($certificates[$project['thesis_id']]);
          $adviserName = !empty($project['adviser_name']) ? $project['adviser_name'] : 'Not Assigned';
        ?>
          <!-- Project card with unique ID -->
          <div class="project-card" id="project-<?= $project['thesis_id'] ?>">
            <div class="project-header">
              <h2>
                <?= htmlspecialchars($project['title']) ?>
                <?php if ($project['feedback_count'] > 0): ?>
                  <span class="feedback-badge">
                    <i class="fas fa-comment"></i> <?= $project['feedback_count'] ?>
                  </span>
                <?php endif; ?>
                <?php if ($hasCertificate): ?>
                  <span class="certificate-badge">
                    <i class="fas fa-certificate"></i> Certified
                  </span>
                <?php endif; ?>
              </h2>
              <span class="status <?= $statusClass ?>"><?= $statusText ?></span>
            </div>

            <div class="project-progress">
              <div class="progress-label">
                <span>Overall Progress</span>
                <span><?= $progress ?>%</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $progress ?>%;"></div>
              </div>
            </div>

            <div class="project-meta">
                <div><i class="fas fa-user-tie"></i> <strong>Adviser:</strong> <?= htmlspecialchars($adviserName) ?></div>
                <div><i class="fas fa-tags"></i> <strong>Keywords:</strong> <?= htmlspecialchars($project['keywords'] ?? 'None') ?></div>
                <div><i class="fas fa-building"></i> <strong>Department:</strong> <?= htmlspecialchars($project['department'] ?? 'N/A') ?></div>
                <div><i class="fas fa-graduation-cap"></i> <strong>Course:</strong> <?= htmlspecialchars($project['course'] ?? 'N/A') ?></div>
                <div><i class="fas fa-calendar"></i> <strong>Year:</strong> <?= htmlspecialchars($project['year'] ?? 'N/A') ?></div>
                <div><i class="fas fa-calendar-alt"></i> <strong>Submitted:</strong> <?= date('F d, Y', strtotime($project['date_submitted'])) ?></div>
                
                <?php if (!empty($project['feedback_count'])): ?>
                    <div><i class="fas fa-comments"></i> <strong>Feedback Received:</strong> <?= $project['feedback_count'] ?></div>
                <?php endif; ?>
                
                <?php if ($hasCertificate): ?>
                    <div><i class="fas fa-download"></i> <strong>Certificate Downloaded:</strong> <?= $certificates[$project['thesis_id']]['downloaded_count'] ?> times</div>
                <?php endif; ?>
            </div>

            <div class="project-actions">
              <a href="view_project.php?id=<?= $project['thesis_id'] ?>" class="btn btn-primary">
                <i class="fas fa-eye"></i> View Details
              </a>
              
              <?php if (!empty($project['file_path'])): ?>
                <a href="../<?= htmlspecialchars($project['file_path']) ?>" class="btn btn-secondary" download>
                  <i class="fas fa-download"></i> Download Manuscript
                </a>
              <?php endif; ?>
              
              <?php if ($hasCertificate): ?>
                <a href="certificate.php?id=<?= $certificates[$project['thesis_id']]['certificate_id'] ?>" 
                   class="btn-certificate" 
                   target="_blank">
                  <i class="fas fa-certificate"></i> View Certificate
                </a>
              <?php endif; ?>
              
              <?php if ($project['status'] == 'pending'): ?>
                <span class="btn btn-secondary" style="opacity: 0.7; cursor: not-allowed;" title="Editing disabled while under review">
                  <i class="fas fa-edit"></i> Edit (Locked)
                </span>
              <?php endif; ?>
              
              <?php if ($project['status'] == 'approved'): ?>
                <a href="archive_thesis.php?id=<?= $project['thesis_id'] ?>" class="btn btn-success" onclick="return confirm('Archive this thesis?')">
                  <i class="fas fa-archive"></i> Archive
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- JavaScript for scroll to specific project -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a thesis_id in the URL
    const urlParams = new URLSearchParams(window.location.search);
    const thesisId = urlParams.get('thesis_id');
    
    // If thesis_id exists, scroll to that project
    if (thesisId) {
      const projectCard = document.getElementById('project-' + thesisId);
      
      if (projectCard) {
        // Scroll to the project smoothly
        projectCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Add highlight class
        projectCard.classList.add('highlight');
        
        // Remove highlight after 2 seconds
        setTimeout(() => {
          projectCard.classList.remove('highlight');
        }, 2000);
      }
    }
  });

  // Mobile menu toggle
  const mobileBtn = document.getElementById('mobileMenuBtn');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const hamburgerBtn = document.getElementById('hamburgerBtn');

  function toggleSidebar() {
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
    
    const mobileIcon = mobileBtn?.querySelector('i');
    const hamburgerIcon = hamburgerBtn?.querySelector('i');
    
    if (sidebar.classList.contains('show')) {
      if (mobileIcon) {
        mobileIcon.classList.remove('fa-bars');
        mobileIcon.classList.add('fa-times');
      }
      if (hamburgerIcon) {
        hamburgerIcon.classList.remove('fa-bars');
        hamburgerIcon.classList.add('fa-times');
      }
    } else {
      if (mobileIcon) {
        mobileIcon.classList.remove('fa-times');
        mobileIcon.classList.add('fa-bars');
      }
      if (hamburgerIcon) {
        hamburgerIcon.classList.remove('fa-times');
        hamburgerIcon.classList.add('fa-bars');
      }
    }
  }

  if (mobileBtn) mobileBtn.addEventListener('click', toggleSidebar);
  if (hamburgerBtn) hamburgerBtn.addEventListener('click', toggleSidebar);

  if (overlay) {
    overlay.addEventListener('click', function() {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
      
      const mobileIcon = mobileBtn?.querySelector('i');
      const hamburgerIcon = hamburgerBtn?.querySelector('i');
      
      if (mobileIcon) {
        mobileIcon.classList.remove('fa-times');
        mobileIcon.classList.add('fa-bars');
      }
      if (hamburgerIcon) {
        hamburgerIcon.classList.remove('fa-times');
        hamburgerIcon.classList.add('fa-bars');
      }
    });
  }

  const navLinks = document.querySelectorAll('.nav-link');
  navLinks.forEach(link => {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        
        const mobileIcon = mobileBtn?.querySelector('i');
        const hamburgerIcon = hamburgerBtn?.querySelector('i');
        
        if (mobileIcon) {
          mobileIcon.classList.remove('fa-times');
          mobileIcon.classList.add('fa-bars');
        }
        if (hamburgerIcon) {
          hamburgerIcon.classList.remove('fa-times');
          hamburgerIcon.classList.add('fa-bars');
        }
      }
    });
  });
</script>

</body>
</html>