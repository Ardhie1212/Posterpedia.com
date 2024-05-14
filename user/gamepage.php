<?php

include('../server/connection.php');

// Directory to save uploaded files
$target_dir = "../images/game-images/";

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add'])) {
        // Create
        $game_name = $_POST['game_name'];
        $stmt = $conn->prepare("INSERT INTO game (game_name) VALUES (?)");
        $stmt->bind_param("s", $game_name);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        // Update
        $id = $_POST['id'];
        $game_name = $_POST['game_name'];
        $game_desc = $_POST['game_desc'];
        $game_category = $_POST['game_category'];
        $game_company = $_POST['game_company'];
        $size = $_POST['size'];
        $release_date = $_POST['release_date'];
        $rating = $_POST['rating'];
        $header = $_POST['header'];
        $sector = $_POST['sector'];
        $game_price = $_POST['game_price'];

        // Handle file uploads
        $photo1 = $photo2 = $photo3 = $video = null;

        if ($_FILES['photo1']['name']) {
            $photo1 = $target_dir . basename($_FILES['photo1']['name']);
            move_uploaded_file($_FILES['photo1']['tmp_name'], $photo1);
        }
        if ($_FILES['photo2']['name']) {
            $photo2 = $target_dir . basename($_FILES['photo2']['name']);
            move_uploaded_file($_FILES['photo2']['tmp_name'], $photo2);
        }
        if ($_FILES['photo3']['name']) {
            $photo3 = $target_dir . basename($_FILES['photo3']['name']);
            move_uploaded_file($_FILES['photo3']['tmp_name'], $photo3);
        }
        if ($_FILES['video']['name']) {
            $video = $target_dir . basename($_FILES['video']['name']);
            move_uploaded_file($_FILES['video']['tmp_name'], $video);
        }

        $stmt = $conn->prepare("UPDATE game SET game_name = ?, game_desc = ?, game_category = ?, game_company = ?, size = ?, release_date = ?, rating = ?, header = ?, photo1 = ?, photo2 = ?, photo3 = ?, video = ?, sector = ?, game_price = ? WHERE game_id = ?");
        $stmt->bind_param("ssssssssssssssi", $game_name, $game_desc, $game_category, $game_company, $size, $release_date, $rating, $header, $photo1, $photo2, $photo3, $video, $sector, $game_price, $id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        // Delete
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM game WHERE game_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch data for the chart
$query_visual = "SELECT g.game_name, COUNT(t.transaction_id) as count
FROM transaction t
JOIN game g ON t.game_id = g.game_id
GROUP BY g.game_name";
$result = $conn->query($query_visual);
$dataPoints = array();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dataPoints[] = array("label" => $row['game_name'], "y" => $row['count']);
    }
}

// Get total number of users
$query_users = "SELECT COUNT(*) as total_users FROM user";
$result_users = $conn->query($query_users);
$total_users = 0;
if ($result_users) {
    $row = $result_users->fetch_assoc();
    $total_users = $row['total_users'];
}

// Get total income
$query_income = "SELECT SUM(amount) as total_income FROM transaction";
$result_income = $conn->query($query_income);
$total_income = 0.0;
if ($result_income) {
    $row = $result_income->fetch_assoc();
    $total_income = $row['total_income'];
}

$query_games = "SELECT game_id, game_name, game_desc, game_category, game_company, size, release_date, rating, header, photo1, photo2, photo3, video, sector, game_price FROM game";
$result_games = $conn->query($query_games);
$games = array();
if ($result_games) {
    while ($row = $result_games->fetch_assoc()) {
        $games[] = $row;
    }
}

$conn->close();

?>
<!DOCTYPE HTML>
<html>

<head>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Style for sidebar */
        body {
            font-family: Arial, sans-serif;
        }

        .sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            top: 0;
            right: 0;
            background-color: #111;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 60px;
            z-index: 1;
        }

        .sidebar a {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 22px;
            color: #818181;
            display: block;
            transition: 0.3s;
        }

        .sidebar a:hover {
            color: #f1f1f1;
        }

        .sidebar .closebtn {
            position: absolute;
            top: 0;
            right: 25px;
            font-size: 36px;
            margin-left: 50px;
        }

        .hamburger {
            font-size: 30px;
            cursor: pointer;
            position: fixed;
            top: 15px;
            right: 20px;
            z-index: 2;
            color: #111;
        }
    </style>
    <script>
        function openNav() {
            document.getElementById("mySidebar").style.width = "250px";
        }

        function closeNav() {
            document.getElementById("mySidebar").style.width = "0";
        }

        function openUpdateModal(game) {
            document.getElementById('updateForm').reset();

            document.getElementById('update_id').value = game.game_id;
            document.getElementById('update_game_name').value = game.game_name;
            document.getElementById('update_game_desc').value = game.game_desc;
            document.getElementById('update_game_category').value = game.game_category;
            document.getElementById('update_game_company').value = game.game_company;
            document.getElementById('update_size').value = game.size;
            document.getElementById('update_release_date').value = game.release_date;
            document.getElementById('update_rating').value = game.rating;
            document.getElementById('update_header').value = game.header;
            document.getElementById('update_photo1').value = game.photo1;
            document.getElementById('update_photo2').value = game.photo2;
            document.getElementById('update_photo3').value = game.photo3;
            document.getElementById('update_video').value = game.video;
            document.getElementById('update_sector').value = game.sector;
            document.getElementById('update_game_price').value = game.game_price;

            $('#updateModal').modal('show');
        }

        window.onload = function() {
            var dataPoints = <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>;
            console.log(dataPoints); // Debugging: Print data points in the browser console

            var chart = new CanvasJS.Chart("chartContainer", {
                animationEnabled: true,
                theme: "light2",
                title: {
                    text: "Penjualan Tertinggi"
                },
                axisY: {
                    title: "Jumlah Pembelian"
                },
                data: [{
                    type: "column",
                    yValueFormatString: "#,##0.##",
                    dataPoints: dataPoints
                }]
            });
            chart.render();
        }
    </script>
</head>

<body>
    <div class="hamburger" onclick="openNav()">&#9776;</div>
    <div id="mySidebar" class="sidebar">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <a href="transactions_list.php">List Transaksi</a>
        <a href="dashboard-admin.php">List Games</a>
    </div>

    <div style="display: flex;">
        <div id="chartContainer" style="height: 300px; width: 50%;"></div>
        <div style="margin-left: 20px;">
            <h3>Total Registered Users: <?php echo $total_users; ?></h3>
            <h3>Total Income: Rp <?php echo number_format($total_income, 2, ',', '.'); ?></h3>
        </div>
    </div>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>

    <!-- CRUD Form for Adding a New Game -->
    <h2>Add New Game</h2>
    <form method="POST" enctype="multipart/form-data">
        <label for="game_name">Game Name:</label>
        <input type="text" name="game_name" id="game_name" required>
        <button type="submit" name="add">Add</button>
    </form>

    <!-- Games List -->
    <h2>Games List</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Game Name</th>
            <th>Description</th>
            <th>Category</th>
            <th>Size</th>
            <th>Release</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($games as $game) : ?>
            <tr>
                <td><?php echo $game['game_id']; ?></td>
                <td><?php echo $game['game_name']; ?></td>
                <td><?php echo $game['game_desc']; ?></td>
                <td><?php echo $game['game_category']; ?></td>
                <td><?php echo $game['size']; ?></td>
                <td><?php echo $game['release_date']; ?></td>
                <td>
                    <!-- Update Button -->
                    <button type="button" onclick='openUpdateModal(<?php echo json_encode($game); ?>)'>Update</button>
                    <!-- Delete Form -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo $game['game_id']; ?>">
                        <button type="submit" name="delete">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Update Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="updateForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateModalLabel">Update Game</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="update_id">
                        <div class="form-group">
                            <label for="update_game_name">Game Name</label>
                            <input type="text" class="form-control" id="update_game_name" name="game_name" required>
                        </div>
                        <div class="form-group">
                            <label for="update_game_desc">Game Description</label>
                            <input type="text" class="form-control" id="update_game_desc" name="game_desc" required>
                        </div>
                        <div class="form-group">
                            <label for="update_game_category">Game Category</label>
                            <input type="text" class="form-control" id="update_game_category" name="game_category" required>
                        </div>
                        <div class="form-group">
                            <label for="update_game_company">Game Company</label>
                            <input type="text" class="form-control" id="update_game_company" name="game_company" required>
                        </div>
                        <div class="form-group">
                            <label for="update_size">Size</label>
                            <input type="text" class="form-control" id="update_size" name="size" required>
                        </div>
                        <div class="form-group">
                            <label for="update_release_date">Release Date</label>
                            <input type="date" class="form-control" id="update_release_date" name="release_date" required>
                        </div>
                        <div class="form-group">
                            <label for="update_rating">Rating</label>
                            <input type="number" class="form-control" id="update_rating" name="rating" step="0.1" required>
                        </div>
                        <div class="form-group">
                            <label for="update_header">Header</label>
                            <input type="text" class="form-control" id="update_header" name="header" required>
                        </div>
                        <div class="form-group">
                            <label for="update_photo1">Photo 1</label>
                            <input type="file" class="form-control" id="update_photo1" name="photo1">
                        </div>
                        <div class="form-group">
                            <label for="update_photo2">Photo 2</label>
                            <input type="file" class="form-control" id="update_photo2" name="photo2">
                        </div>
                        <div class="form-group">
                            <label for="update_photo3">Photo 3</label>
                            <input type="file" class="form-control" id="update_photo3" name="photo3">
                        </div>
                        <div class="form-group">
                            <label for="update_video">Video</label>
                            <input type="file" class="form-control" id="update_video" name="video">
                        </div>
                        <div class="form-group">
                            <label for="update_sector">Sector</label>
                            <input type="text" class="form-control" id="update_sector" name="sector" required>
                        </div>
                        <div class="form-group">
                            <label for="update_game_price">Game Price</label>
                            <input type="number" class="form-control" id="update_game_price" name="game_price" step="0.01" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="update" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap and jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>
