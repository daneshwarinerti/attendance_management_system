<?php
require 'config.php';

$subjects = $conn->query("SELECT * FROM subjects");
$classes = $conn->query("SELECT MIN(id) as id, class_name FROM classes GROUP BY class_name");

$attendanceData = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject_id = $_POST['subject_id'];
    $class_id = $_POST['class_id'];
    $date = $_POST['date'];

    $sql = "SELECT a.date, s.usn, s.student_name AS student, sub.subject_name AS subject, a.status
            FROM attendance a
            JOIN students s ON a.student_id = s.id
            JOIN subjects sub ON a.subject_id = sub.id
            WHERE a.subject_id = ? AND s.class_id = ? AND a.date = ?
            ORDER BY s.usn";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL error: " . $conn->error);
    }
    $stmt->bind_param("iis", $subject_id, $class_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendanceData = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen p-6">

    <div class="max-w-5xl mx-auto bg-white shadow-lg rounded-xl p-8">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">📊 View Attendance</h2>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-gray-700 font-semibold mb-1">Select Subject</label>
                <select name="subject_id" required class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">-- Select Subject --</option>
                    <?php while ($row = $subjects->fetch_assoc()) { ?>
                        <option value="<?= $row['id'] ?>"><?= $row['subject_name'] ?></option>
                    <?php } ?>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">Select Class</label>
                <select name="class_id" required class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">-- Select Class --</option>
                    <?php while ($row = $classes->fetch_assoc()) { ?>
                        <option value="<?= $row['id'] ?>"><?= $row['class_name'] ?></option>
                    <?php } ?>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">Select Date</label>
                <input type="date" name="date" required class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div class="md:col-span-3 flex justify-center">
                <button type="submit" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-2 rounded-lg shadow-md transition-all">
                    🔍 View Attendance
                </button>
            </div>
        </form>

        <?php if (!empty($attendanceData)) { ?>
            <div class="overflow-x-auto mt-10">
                <table class="min-w-full text-sm text-center border border-gray-300 bg-white shadow-md rounded-lg">
                    <thead class="bg-blue-100 text-blue-900">
                        <tr>
                            <th class="py-3 px-4 border-b">Date</th>
                            <th class="py-3 px-4 border-b">USN</th>
                            <th class="py-3 px-4 border-b">Student</th>
                            <th class="py-3 px-4 border-b">Subject</th>
                            <th class="py-3 px-4 border-b">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <?php foreach ($attendanceData as $index => $row) { ?>
                            <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-gray-100">
                                <td class="py-3 px-4 border-b"><?= $row['date'] ?></td>
                                <td class="py-3 px-4 border-b"><?= $row['usn'] ?></td>
                                <td class="py-3 px-4 border-b"><?= $row['student'] ?></td>
                                <td class="py-3 px-4 border-b"><?= $row['subject'] ?></td>
                                <td class="py-3 px-4 border-b">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-medium 
                                        <?= $row['status'] == 'Present' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } elseif ($_SERVER["REQUEST_METHOD"] == "POST") { ?>
            <p class="text-center text-red-600 font-medium mt-6">🚫 No attendance data found for the selected inputs.</p>
        <?php } ?>
    </div>

</body>
</html>
