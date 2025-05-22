<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $prefix = strtoupper(trim($_POST['prefix']));
    $runningChar = strtoupper(trim($_POST['runningChar']));
    $postfix = isset($_POST['postfix']) ? strtoupper(trim($_POST['postfix'])) : '';
    $startNumber = intval($_POST['startNumber']);
    $endNumber = intval($_POST['endNumber']);
    // $excludedNumbers = isset($_POST['excludedNumbers']) ? explode(',', str_replace(' ', '', $_POST['excludedNumbers'])) : [];
    $excludedNumbers = isset($_POST['excludedNumbers']) ? $_POST['excludedNumbers'] : ''; 
    $productType = $_POST['productType'];

    if (!is_array($excludedNumbers)) {
        $excludedNumbers = explode(',', $excludedNumbers);
    }

    // Convert running numbers into full serial numbers
    $excludedFullSerials = [];

    foreach ($excludedNumbers as $excludedNumber) {
        $excludedFullSerials[] = $prefix . $runningChar . str_pad(intval($excludedNumber), 7, '0', STR_PAD_LEFT) . $postfix;
    }

    // Convert array into a comma-separated string for database storage
    $excludedNumbersString = implode(',', $excludedFullSerials);


    $startSerial=$_POST['prefix'].$_POST['runningChar'].$_POST['startNumber'].$_POST['postfix'];
    $endSerial=$_POST['prefix'].$_POST['runningChar'].$_POST['endNumber'].$_POST['postfix'];
    $excludedNumbers=$_POST['excludedNumbers'];
    $userPC = gethostbyaddr($_SERVER['REMOTE_ADDR']);

    $_SESSION['form_data'] = $_POST;

    // Validation rules
    function showAlertAndRedirect($message) {
        echo "<script>alert('$message'); window.history.back();</script>";
        exit;
    }
    
    if (!preg_match('/^[A-Z]{1,3}$/', $prefix)) {
        showAlertAndRedirect("Invalid prefix format.");
    }

    if (!preg_match('/^[A-HJ-NP-Z]$/', $runningChar)) {
        showAlertAndRedirect("Invalid running character format. Only letters A-Z (except I and O) are allowed.");
    }
    
    if ($postfix !== '' && !preg_match('/^[A-Z]{1,3}$/', $postfix)) {
        showAlertAndRedirect("Invalid postfix format.");
    }
    
    
    if ($startNumber > $endNumber) {
        showAlertAndRedirect("Start number cannot be greater than end number.");
    }

    unset($_SESSION['form_data']); // Clear form data on success

    $csvDirectory = __DIR__ . "/generated_csv/";
    if (!file_exists($csvDirectory)) {
        mkdir($csvDirectory, 0777, true);
    }

    // $filename = "serial_numbers_" . time() . ".csv";
    $filename = "serial_numbers_" . $productType . "_" . time() . ".csv";
    $filePath = $csvDirectory . $filename;
    $output = fopen($filePath, 'w');

    if ($productType === "electrical") {
        $csvHeaders = ["SIRIM SERIAL NO.", "COA NUMBER", "MANUFACTURING DATE", "RATING", "SIZE"];
    } else {
        $csvHeaders = ["SIRIM SERIAL NO.", "BATCH NO.", "BRAND/TRADEMARK", "MODEL", "TYPE", "RATING", "SIZE"];
    }

    if (!$output) {
        die("Error: Failed to create CSV file. Check folder permissions.");
    }

    fputcsv($output, $csvHeaders);

    $currentPrefix = $prefix;
    $currentRunningChar = $runningChar;
    $currentNumber = $startNumber;
    $currentPostfix = $postfix;

    while (!(
        strcmp($currentPrefix, $prefix) > 0 ||
        (strcmp($currentPrefix, $prefix) == 0 && strcmp($currentRunningChar, $runningChar) > 0) ||
        (strcmp($currentPrefix, $prefix) == 0 && strcmp($currentRunningChar, $runningChar) == 0 && $currentNumber > $endNumber)
    )) {
        $serial = $currentPrefix . $currentRunningChar . str_pad($currentNumber, 7, '0', STR_PAD_LEFT) . $currentPostfix;

        // Ensure $excludedNumbers is an array before using in_array()
        if (!is_array($excludedNumbers)) {
            $excludedNumbers = explode(',', $excludedNumbers);
        }

        if (!in_array($currentNumber, array_map('intval', $excludedNumbers))) {
            $emptyColumns = array_fill(0, count($csvHeaders) - 1, ""); 
            fputcsv($output, array_merge([$serial], $emptyColumns));
        }


        $currentNumber++;
        if ($currentNumber > 9999999) {
            $currentNumber = 0;
            if ($currentRunningChar === 'Z') {
                $currentRunningChar = 'A';
                $currentPrefix = expandPrefix($currentPrefix);
            } else {
                $currentRunningChar = getNextValidChar($currentRunningChar);
            }
        }
    }

    fclose($output);

    $startSerial = $prefix . $runningChar . str_pad($startNumber, 7, '0', STR_PAD_LEFT) . $postfix;
    $endSerial = $prefix . $runningChar . str_pad($endNumber, 7, '0', STR_PAD_LEFT) . $postfix;

    // $localIp = "10.255.6.204"; // changes constantly
    $localIp = "10.255.11.234"; // changed 21/3
    $csvUrl = "http://" . $localIp . "/qrcodegenerator/generated_csv/" . $filename;
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($csvUrl);

    // Alternative QR code generator API
    // $qrCodeUrl = "https://quickchart.io/qr?text=" . urlencode($csvUrl) . "&size=300";

    $con = new mysqli("localhost", "root", "", "qr_code_generator");
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }

    // Fetch the QR code image from the URL
    $qrImageData = file_get_contents($qrCodeUrl);

    $sql = "INSERT INTO `transaction_log` (start_serial, end_serial, excluded_serials, user, qr_code_image) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param("sssss", $startSerial, $endSerial, $excludedNumbersString, $userPC, $qrImageData);
    $stmt->send_long_data(4, $qrImageData); // Store as BLOB
    $stmt->execute();
    $stmt->close();
    $con->close();
}

function getNextValidChar($char) {
    $validChars = array_values(array_diff(range('A', 'Z'), ['I', 'O']));
    $index = array_search($char, $validChars);
    return ($index !== false && isset($validChars[$index + 1])) ? $validChars[$index + 1] : false;
}

function expandPrefix($prefix) {
    if (strlen($prefix) < 3) {
        return $prefix . 'A';
    }
    return incrementPrefix($prefix);
}

function incrementPrefix($prefix) {
    $validChars = array_values(array_diff(range('A', 'Z'), ['I', 'O']));
    $prefixArr = str_split($prefix);
    $carry = true;

    for ($i = count($prefixArr) - 1; $i >= 0; $i--) {
        if ($carry) {
            $currentCharIndex = array_search($prefixArr[$i], $validChars);
            if ($currentCharIndex !== false && isset($validChars[$currentCharIndex + 1])) {
                $prefixArr[$i] = $validChars[$currentCharIndex + 1];
                $carry = false;
            } else {
                $prefixArr[$i] = $validChars[0];
                $carry = true;
            }
        }
    }

    if ($carry && count($prefixArr) < 3) {
        array_unshift($prefixArr, $validChars[0]);
    }

    return implode('', $prefixArr);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="output.css">
    <title>QR Code Generator</title>
</head>
<body>
    <div class="output-container">
        <?php if (!empty($csvUrl) && !empty($qrCodeUrl)): ?>
            <h3><strong>Scan the QR code to access the CSV file:</strong></h3>
            <img src="<?= $qrCodeUrl ?>" alt="QR Code" class="qr-code">
            <h3><a href="<?= $csvUrl ?>" download>Download CSV</a></h3>
            <h4><strong>Generated by:</strong> <?= htmlspecialchars($userPC) ?></h4>
        <?php endif; ?>
        <div class="btn-container">
            <button onclick="printQRCode();" class="btn">Print QR Code</button>
            <button class="btn view-report-btn">
                <a href="/qrcodegenerator/transaction_log.php" target="_blank">View Transaction Report</a>
            </button>
            <form action="newform2.php" method="get">
                <button type="submit" class="btn">Generate Another QR Code</button>
            </form>
        </div>
    </div>

    <script>
        function printQRCode() {
            let qrWindow = window.open('', '_blank');
            qrWindow.document.write(`
                <html>
                <head>
                    <title>Print QR Code</title>
                    <style>
                        @media print {
                            body {
                                text-align: start;
                                font-family: Arial, sans-serif;
                                margin: 20px;
                            }
                            .header {
                                font-size: 14px;
                                font-weight: bold;
                                margin-bottom: 20px;
                                text-align: center;
                            }
                            .footer {
                                font-size: 14px;
                                font-weight: bold;
                                margin-top: 20px;
                                text-align: center;
                            }
                            img {
                                width: 100%;
                                max-width: 1000px;
                                height: auto;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">Scan QR Code to Download CSV</div>
                    <img id="qrImage" src="<?= $qrCodeUrl ?>" alt="QR Code">
                    <div class="footer">From:<br><?= $startSerial ?><br>To:<br><?= $endSerial ?></div>
                </body>
                </html>
            `);
            qrWindow.document.close();

            let qrImage = qrWindow.document.getElementById('qrImage');
            qrImage.onload = function () {
                qrWindow.print();
                qrWindow.close();
            };
        }
    </script>
</body>
</html>
