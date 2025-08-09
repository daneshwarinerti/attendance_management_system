<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

include("includes/db.php");

$student_id = $_SESSION['user']['student_id'];

// Fetch all subjects (not just attended ones)
$subjectsQuery = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name ASC");

$subjects = [];
while ($row = $subjectsQuery->fetch_assoc()) {
    $subjects[] = $row;
}

// Fetch all attendance data for the student
$query = $conn->query("
    SELECT a.date, s.subject_name, a.subject_id, a.status 
    FROM attendance a 
    JOIN subjects s ON a.subject_id = s.id 
    WHERE a.student_id = $student_id 
    ORDER BY a.date DESC
");

if (!$query) {
    die("Query Error: " . $conn->error);
}

$attendanceData = [];
while ($row = $query->fetch_assoc()) {
    $attendanceData[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f7fc;
        }
        .container {
            margin-top: 70px;
        }
        .card {
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.1);
            background-color: #ffffff;
        }
        .badge-present {
            background-color: #28a745;
        }
        .badge-absent {
            background-color: #dc3545;
        }
        .stats-box {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .stats-item {
            text-align: center;
        }
        .stats-item h5 {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .no-record {
            text-align: center;
            color: #dc3545;
            font-weight: bold;
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h3 class="mb-4 text-center">📋 My Attendance</h3>

        <!-- Subject Dropdown -->
        <div class="mb-3">
            <label class="form-label">Select Subject:</label>
            <select id="subjectFilter" class="form-select">
                <option value="">-- Select a Subject --</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Attendance Stats -->
        <div class="stats-box" id="attendanceStats" style="display:none;">
            <div class="stats-item">
                <h5 id="totalAttended">0</h5>
                <p>Classes Attended</p>
            </div>
            <div class="stats-item">
                <h5 id="totalMissed">0</h5>
                <p>Classes Missed</p>
            </div>
            <div class="stats-item">
                <h5 id="attendancePercentage">0%</h5>
                <p>Attendance Percentage</p>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive mt-4">
            <table class="table table-hover text-center align-middle" id="attendanceTable" style="display:none;">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Subject</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="attendanceBody"></tbody>
            </table>
            <div id="noRecordsMessage" class="no-record" style="display:none;">
                🚫 You have not attended any classes for this subject.
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="student_dashboard.php" class="btn btn-secondary">🔙 Back to Dashboard</a>
        </div>
    </div>
</div>

<script>
    const attendanceData = <?= json_encode($attendanceData) ?>;

    document.getElementById('subjectFilter').addEventListener('change', function () {
        const subjectId = this.value;
        const tbody = document.getElementById('attendanceBody');
        const table = document.getElementById('attendanceTable');
        const stats = document.getElementById('attendanceStats');
        const noRecords = document.getElementById('noRecordsMessage');

        tbody.innerHTML = '';

        if (!subjectId) {
            table.style.display = 'none';
            stats.style.display = 'none';
            noRecords.style.display = 'none';
            return;
        }

        // Filter attendance records for selected subject
        const filtered = attendanceData.filter(row => row.subject_id == subjectId);

        if (filtered.length === 0) {
            table.style.display = 'none';
            stats.style.display = 'none';
            noRecords.style.display = 'block';
            return;
        }

        noRecords.style.display = 'none';
        table.style.display = 'table';

        // Populate table
        filtered.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.date}</td>
                <td>${row.subject_name}</td>
                <td><span class="badge ${row.status === 'Present' ? 'badge-present' : 'badge-absent'} px-3 py-2">${row.status}</span></td>
            `;
            tbody.appendChild(tr);
        });

        // Calculate stats
        const totalAttended = filtered.filter(r => r.status === 'Present').length;
        const totalMissed = filtered.filter(r => r.status === 'Absent').length;
        const percentage = filtered.length > 0 ? ((totalAttended / filtered.length) * 100).toFixed(2) : 0;

        document.getElementById('totalAttended').textContent = totalAttended;
        document.getElementById('totalMissed').textContent = totalMissed;
        document.getElementById('attendancePercentage').textContent = percentage + '%';

        // Show stats
        stats.style.display = 'flex';
    });
</script>

</body>
</html>
