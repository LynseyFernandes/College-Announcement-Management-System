<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

/* ---------------- AJAX CRUD ---------------- */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action == 'add') {
        $title = mysqli_real_escape_string($conn, trim($_POST['title']));
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $category = $_POST['category'];
        $year = $_POST['year'];

        if ($title && $description && $category && $year) {
            $sql = "INSERT INTO announcements(title, description, category, year)
                    VALUES('$title','$description','$category','$year')";
            echo json_encode(['success' => $conn->query($sql)]);
        }
        exit();
    }

    if ($action == 'delete') {
        $id = intval($_POST['id']);
        $sql = "DELETE FROM announcements WHERE id=$id";
        echo json_encode(['success' => $conn->query($sql)]);
        exit();
    }
}

/* ---------------- LOAD ANNOUNCEMENTS ---------------- */
if (isset($_GET['load'])) {
    $year = $_GET['year'] ?? '';

    $sql = "SELECT * FROM announcements WHERE 1";

    // Show selected year + common notices
    if ($year) {
        $sql .= " AND (year='$year' OR year='All Years')";
    }

    $sql .= " ORDER BY created_at DESC";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $color = match ($row['category']) {
            'Exam' => '#ff4d4d',
            'Event' => '#4d79ff',
            default => '#28a745'
        };

        echo "
        <div class='card' style='border-left: 6px solid $color'>
            <h3>{$row['title']}</h3>
            <p>{$row['description']}</p>
            <small>{$row['category']}</small>
            <div class='btn-group'>
                <button onclick='deleteAnnouncement({$row['id']})'>Delete</button>
            </div>
        </div>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>College Announcement Dashboard</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f6fb;
        }

        .header {
            text-align: center;
            padding: 25px;
            color: white;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 0 0 20px 20px;
        }

        .container {
            width: 85%;
            margin: auto;
            padding: 20px;
        }

        .form-box {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        input,
        textarea,
        select,
        button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 12px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }

        button {
            background: #667eea;
            color: white;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            transform: translateY(-2px);
        }

        .year-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .year-btn {
            width: auto;
            padding: 12px 20px;
            border-radius: 12px;
            background: #667eea;
            color: white;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }

        .year-btn:hover {
            transform: translateY(-2px);
        }

        .year-btn.active {
            background: #764ba2;
            box-shadow: 0 4px 10px rgba(118, 75, 162, 0.3);
            transform: scale(1.03);
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 18px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-4px);
        }

        .btn-group button {
            width: auto;
            padding: 10px 15px;
            margin-top: 10px;
            background: #dc3545;
        }

        small {
            display: block;
            margin-top: 10px;
            color: #666;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>College Announcement Dashboard</h1>
    </div>

    <div class="container">

        <div class="form-box">
            <h2>Add Announcement</h2>

            <form id="addForm">
                <input type="text" id="title" placeholder="Title" required>
                <textarea id="description" placeholder="Description" required></textarea>

                <select id="category" required>
                    <option value="">Select Category</option>
                    <option value="Exam">Exam</option>
                    <option value="Event">Event</option>
                    <option value="General">General</option>
                </select>

                <select id="year" required>
                    <option value="">Select Year</option>
                    <option>1st Year</option>
                    <option>2nd Year</option>
                    <option>3rd Year</option>
                    <option>4th Year</option>
                    <option>All Years</option>
                </select>

                <button type="submit">Add Announcement</button>
            </form>
        </div>

        <div class="year-buttons">
            <button class="year-btn active" onclick="filterYear('1st Year', this)">1st Year</button>
            <button class="year-btn" onclick="filterYear('2nd Year', this)">2nd Year</button>
            <button class="year-btn" onclick="filterYear('3rd Year', this)">3rd Year</button>
            <button class="year-btn" onclick="filterYear('4th Year', this)">4th Year</button>
        </div>

        <div id="announcements" class="cards"></div>
    </div>

    <script>
        $(document).ready(function() {
            loadAnnouncements('1st Year');

            $('#addForm').submit(function(e) {
                e.preventDefault();

                $.post('', {
                    action: 'add',
                    title: $('#title').val(),
                    description: $('#description').val(),
                    category: $('#category').val(),
                    year: $('#year').val()
                }, function(response) {
                    if (response.success) {
                        $('#addForm')[0].reset();
                        loadAnnouncements('1st Year');
                    }
                }, 'json');
            });
        });

        function loadAnnouncements(yearValue) {
            $.get('', {
                load: 1,
                year: yearValue
            }, function(data) {
                $('#announcements').html(data);
            });
        }

        function filterYear(yearValue, btn) {
            $('.year-btn').removeClass('active');
            $(btn).addClass('active');
            loadAnnouncements(yearValue);
        }

        function deleteAnnouncement(id) {
            if (confirm("Delete this announcement?")) {
                $.post('', {
                    action: 'delete',
                    id: id
                }, function(response) {
                    if (response.success) {
                        let activeYear = $('.year-btn.active').text();
                        loadAnnouncements(activeYear);
                    }
                }, 'json');
            }
        }
    </script>

</body>

</html>