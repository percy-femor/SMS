<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - EduManage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Similar styling as admin dashboard */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        :root { --primary: #2563eb; --primary-dark: #1d4ed8; --secondary: #f0f9ff; --accent: #f59e0b; --text: #1f2937; --text-light: #6b7280; --white: #ffffff; --gray: #f8fafc; --border: #e5e7eb; }
        
        body { background: #f5f5f5; }
        .header { background: var(--primary); color: var(--white); padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: var(--white); padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); text-align: center; }
        .card i { font-size: 2.5rem; color: var(--primary); margin-bottom: 15px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .logout { float: right; background: var(--accent); color: var(--white); }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-user-graduate"></i> Student Dashboard</h1>
            <p>Welcome, <?php echo $_SESSION['student_name']; ?>!</p>
            <a href="logout.php" class="btn logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="dashboard-cards">
            <div class="card">
                <i class="fas fa-book"></i>
                <h3>My Classes</h3>
                <p>View Schedule</p>
            </div>
            <div class="card">
                <i class="fas fa-chart-line"></i>
                <h3>Grades</h3>
                <p>View Performance</p>
            </div>
            <div class="card">
                <i class="fas fa-calendar"></i>
                <h3>Assignments</h3>
                <p>Due Soon</p>
            </div>
        </div>
    </div>
</body>
</html>