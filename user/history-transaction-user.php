<?php
include('../server/connection.php');
session_start();

$user_id = $_SESSION['id_user'] ?? $_GET['id_user'] ?? $_POST['id_user'] ?? 0;
$user_id = filter_var($user_id, FILTER_VALIDATE_INT) ?: 0;

$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_id = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);

    if (isset($_POST['claim_refund'])) {
        $query_price_balance = "SELECT g.game_price, u.saldo 
                                FROM transaction t 
                                JOIN game g ON g.game_id = t.game_id 
                                JOIN user u ON u.id_user = t.id_user 
                                WHERE t.transaction_id=?";
        $stmt = $conn->prepare($query_price_balance);
        $stmt->bind_param('i', $transaction_id);
        $stmt->execute();
        $stmt->bind_result($game_price, $user_balance);
        $stmt->fetch();
        $stmt->close();

        if ($game_price !== null && $user_balance !== null) {
            $new_balance = $user_balance + $game_price;

            $query_update_balance = "UPDATE user SET saldo=? WHERE id_user=?";
            $stmt = $conn->prepare($query_update_balance);
            $stmt->bind_param('di', $new_balance, $user_id);
            if ($stmt->execute()) {
                $stmt->close();

                $query_update_status = "UPDATE transaction SET Status='claimed' WHERE transaction_id=?";
                $stmt = $conn->prepare($query_update_status);
                $stmt->bind_param('i', $transaction_id);
                $success = $stmt->execute();
                $stmt->close();
            } else {
                $success = false;
            }
        } else {
            $success = false;
        }
    }
}

$query_view = "SELECT t.transaction_id, g.header, g.game_name, g.game_price, u.email, u.username, t.Status 
               FROM transaction t 
               JOIN game g ON g.game_id = t.game_id
               JOIN user u ON u.id_user = t.id_user 
               WHERE u.id_user = ?";
$stmt = $conn->prepare($query_view);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Transaction</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/history-transaction.css">
</head>

<body>
    <div class="container">
        <button class="btn btn-primary mt-3" onclick="window.location.href='homepage.php'">
            <i class="bi bi-arrow-left-circle-fill"></i>
        </button>
        <h1 class="mt-3">History Transaction</h1>
        <table class="table table-striped mt-3">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Game Header</th>
                    <th>Game Name</th>
                    <th>Game Price</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0) { ?>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                            <td><img src="../images/game-images/header/<?php echo htmlspecialchars($row['header']); ?>" alt="<?php echo htmlspecialchars($row['game_name']); ?>" width="100"></td>
                            <td><?php echo htmlspecialchars($row['game_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['game_price']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['Status']); ?></td>
                            <td>
                                <?php if ($row['Status'] === 'Verified Refund') { ?>
                                    <form id="refundForm">
                                        <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($row['transaction_id']); ?>">
                                        <button type="submit" class="btn btn-success" name="claim_refund">Claim Refund</button>
                                    </form>
                                    <!-- Success Modal -->
                                    <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="successModalLabel">Success</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    Your refund claim was successful.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Failure Modal -->
                                    <div class="modal fade" id="failureModal" tabindex="-1" role="dialog" aria-labelledby="failureModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="failureModalLabel">Failure</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    Your refund claim failed. Please try again.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } elseif ($row['Status'] !== 'refund' && $row['Status'] !== 'claimed') { ?>
                                    <button class="btn btn-danger" data-toggle="modal" data-target="#refundModal<?php echo htmlspecialchars($row['transaction_id']); ?>">Refund</button>
                                <?php } ?>
                            </td>
                        </tr>
                        <!-- Modal -->
                        <div class="modal fade" id="refundModal<?php echo htmlspecialchars($row['transaction_id']); ?>" tabindex="-1" aria-labelledby="refundModalLabel<?php echo htmlspecialchars($row['transaction_id']); ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="refundModalLabel<?php echo htmlspecialchars($row['transaction_id']); ?>">Refund Confirmation</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to refund this transaction?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <form method="POST" action="../Action/cancel-refund.php">
                                            <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($row['transaction_id']); ?>">
                                            <button type="submit" class="btn btn-danger" name="refund">Refund</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="8">No transactions found.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#refundForm').on('submit', function(event) {
                event.preventDefault(); // Mencegah form dikirim secara default

                var formData = $(this).serialize();

                $.ajax({
                    type: 'POST',
                    url: 'process_refund.php', // URL untuk memproses klaim refund
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#successModal').modal('show');
                        } else {
                            $('#failureModal').modal('show');
                        }
                    },
                    error: function() {
                        $('#failureModal').modal('show');
                    }
                });
            });
        });
    </script>
</body>


</html>