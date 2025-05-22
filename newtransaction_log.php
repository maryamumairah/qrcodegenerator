<?php
$con = new mysqli("localhost", "root", "", "qr_code_generator");
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Get product types for the checkbox list
$productTypesResult = $con->query("SELECT DISTINCT product_type FROM transaction_log");
$productTypes = [];
while ($row = $productTypesResult->fetch_assoc()) {
    $productTypes[] = $row['product_type'];
}

// Default: no filters
$filterResults = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $from = explode('-', $_POST['from_month']);
    $to = explode('-', $_POST['to_month']);

    $fromYear = $from[0];
    $fromMonth = $from[1];
    $toYear = $to[0];
    $toMonth = $to[1];

    $selectedTypes = isset($_POST['product_types']) ? $_POST['product_types'] : [];

    $startDate = "$fromYear-$fromMonth-01";
    $endDate = date("Y-m-t", strtotime("$toYear-$toMonth-01"));

    $sql = "SELECT id, product_type, start_serial, end_serial, excluded_serials, generated_at, user, qr_code_image 
            FROM transaction_log 
            WHERE generated_at BETWEEN ? AND ?";

    if (!empty($selectedTypes)) {
        $placeholders = implode(',', array_fill(0, count($selectedTypes), '?'));
        $sql .= " AND product_type IN ($placeholders)";
    }

    $sql .= " ORDER BY generated_at DESC";

    $stmt = $con->prepare($sql);
    $types = "ss" . str_repeat("s", count($selectedTypes));
    $params = array_merge([$startDate, $endDate], $selectedTypes);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $filterResults = $stmt->get_result();
}
$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transaction Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 50px 20px;
            background: #f5f5f5 url('images/SIRIMbg.jpg') no-repeat center top fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
        }

        .transaction-container {
            width: 100%;
            max-width: 750px;
            margin: auto;
            text-align: center;
        }

        h2 {
            text-align: center;
            margin-bottom: 40px;
        }

        form {
            background: #ffffffcc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: left;
        }

        .form-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: nowrap;
            margin-bottom: 15px;
        }

        .input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .input-group label {
            font-weight: bold;
            margin-right: 10px;
        }

        .input-group input[type="month"] {
            max-width: 150px;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-family: arial;
            font-size: 15px;
        }

        label {
            display: inline-block;
        }

        .checkbox-group-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 30px;
        }

        .product-types-label {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .checkbox-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }

        button {
            background-color: #274794;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 700;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .button-group button[type="submit"] {
            background-color: #0056b3;
        }

        .button-group button[type="submit"]:hover {
            background-color: #004494;
        }

        .button-group [type="reset"] {
            background-color: #999999;
        }

        .button-group [type="reset"]:hover {
            background-color: 	#555555;
        }

        .transaction-card {
            padding: 15px;
            border-radius: 10px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            margin-bottom: 15px;
            text-align: left;
        }

        .transaction-info p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="transaction-container">
        <h2>Transaction Report</h2>

        <form method="POST">
            <div class="form-row">
                <div class="input-group">
                    <label for="from">From date:</label>
                    <input type="month" name="from_month" value="<?= isset($_POST['from_month']) ? $_POST['from_month'] : '' ?>" required>
                </div>
                <div class="input-group">
                    <label for="to">To date:</label>
                    <input type="month" name="to_month" value="<?= isset($_POST['to_month']) ? $_POST['to_month'] : '' ?>" required>
                </div>
            </div>

            <div class="checkbox-group-wrapper">
                <label class="product-types-label">Product Type:</label>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="product_types[]" value="Electrical"
                            <?= (isset($_POST['product_types']) && in_array('Electrical', $_POST['product_types'])) ? 'checked' : '' ?>>
                        Electrical
                    </label>
                    <label>
                        <input type="checkbox" name="product_types[]" value="Engine Oil"
                            <?= (isset($_POST['product_types']) && in_array('Engine Oil', $_POST['product_types'])) ? 'checked' : '' ?>>
                        Engine Oil
                    </label>
                </div>
            </div>

            <br>
            <div class="button-group">
                <button type="submit">Filter</button>
                <button type="reset" onclick="window.location.href='newtransaction_log.php'">Reset</button>
            </div>
        </form>


        <?php if ($filterResults !== null): ?>
            <?php if ($filterResults->num_rows > 0): ?>
                <?php while ($row = $filterResults->fetch_assoc()): ?>
                    <div class="transaction-card">
                        <div class="transaction-info">
                            <p><strong>Product Type:</strong> <?= htmlspecialchars($row['product_type']) ?></p>
                            <p><strong>Start Serial:</strong> <?= htmlspecialchars($row['start_serial']) ?></p>
                            <p><strong>End Serial:</strong> <?= htmlspecialchars($row['end_serial']) ?></p>
                            <p><strong>Excluded Serials:</strong> <?= htmlspecialchars($row['excluded_serials']) ?></p>
                            <p><strong>Timestamp:</strong> <?= htmlspecialchars($row['generated_at']) ?></p>
                            <p><strong>Generated By:</strong> <?= htmlspecialchars($row['user']) ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center;">No transactions found for this filter.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input[name="product_types[]"]');
            let atLeastOneChecked = false;
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    atLeastOneChecked = true;
                }
            });

            if (!atLeastOneChecked) {
                e.preventDefault(); // stop form from submitting
                alert('Please select at least one product type.');
            }
        });
    </script>
</body>
</html>
