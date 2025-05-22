<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $startPrefix = isset($_POST['startPrefix']) ? strtoupper(trim($_POST['startPrefix'])) : '';
    $startRunningChar = isset($_POST['startRunningChar']) ? strtoupper(trim($_POST['startRunningChar'])) : '';
    $startNumber = isset($_POST['startNumber']) ? intval(trim($_POST['startNumber'])) : 0;
    $startPostfix = isset($_POST['startPostfix']) ? strtoupper(trim($_POST['startPostfix'])) : '';

    $endPrefix = isset($_POST['endPrefix']) ? strtoupper(trim($_POST['endPrefix'])) : '';
    $endRunningChar = isset($_POST['endRunningChar']) ? strtoupper(trim($_POST['endRunningChar'])) : '';
    $endNumber = isset($_POST['endNumber']) ? intval(trim($_POST['endNumber'])) : 0;
    $endPostfix = isset($_POST['endPostfix']) ? strtoupper(trim($_POST['endPostfix'])) : '';

    $excludedSerials = isset($_POST['excludedSerials']) ? $_POST['excludedSerials'] : ''; 

    // Ensure it's an array before using implode
    if (!is_array($excludedSerials)) {
        $excludedSerials = explode(',', $excludedSerials);
    }

    // Convert to comma-separated string for database storage
    $excludedSerialsString = implode(',', $excludedSerials);


    $startSerial=$_POST['startPrefix'].$_POST['startRunningChar'].$_POST['startNumber'].$_POST['startPostfix'];
    $endSerial=$_POST['endPrefix'].$_POST['endRunningChar'].$_POST['endNumber'].$_POST['endPostfix'];
    $excludedSerials=$_POST['excludedSerials'];
    $userPC = gethostbyaddr($_SERVER['REMOTE_ADDR']);

    $_SESSION['form_data'] = $_POST;

    // Validation rules
    $validChars = array_values(array_diff(range('A', 'Z'), ['I', 'O']));
    $validPrefixPattern = '/^[A-Z]{1,3}$/';
    $validPostfixPattern = '/^[A-Z]*$/';

    // Check for invalid prefix
    if (!preg_match($validPrefixPattern, $startPrefix) || !preg_match($validPrefixPattern, $endPrefix)) {
        $_SESSION['error'] = "Error: Prefix must be A-Z only.";
        header("Location: form.php");
        exit();
    }

    // Check for invalid running character
    if (!in_array($startRunningChar, $validChars) || !in_array($endRunningChar, $validChars)) {
        $_SESSION['error'] = "Error: Running character must be A-Z except 'I' and 'O'.";
        header("Location: form.php");
        exit();
    }

    // Check for invalid postfix
    if (!preg_match($validPostfixPattern, $startPostfix) || !preg_match($validPostfixPattern, $endPostfix)) {
        $_SESSION['error'] = "Error: Postfix must be A-Z only.";
        header("Location: form.php");
        exit();
    }

    // Check if Start serials are greater than End serials
    // if ($startPrefix > $endPrefix || 
    //     ($startPrefix === $endPrefix && $startRunningChar > $endRunningChar) || 
    //     ($startPrefix === $endPrefix && $startRunningChar === $endRunningChar && $startNumber > $endNumber)) {
    //     $_SESSION['error'] = "Error: Start serials cannot be greater than end serials.";
    //     header("Location: form.php");
    //     exit();
    // }

    // Check if Start serial prefix is greater than End serial prefix
    if ($startPrefix > $endPrefix) {
        $_SESSION['error'] = "Error: Start serial prefix cannot be greater than end serial prefix.";
        header("Location: form.php");
        exit();
    }

    // Check if Start serial running character is greater than End serial running character
    if ($startPrefix === $endPrefix && $startRunningChar > $endRunningChar) {
        $_SESSION['error'] = "Error: Start serial running character cannot be greater than end serial running character.";
        header("Location: form.php");
        exit();
    }

    // Check if Start serial running number is greater than End serial running number
    if ($startPrefix === $endPrefix && $startRunningChar === $endRunningChar && $startNumber > $endNumber) {
        $_SESSION['error'] = "Error: Start serial running number cannot be greater than end serial running number.";
        header("Location: form.php");
        exit();
    }

    // Check if postfixes are different
    if ($startPostfix !== $endPostfix) {
        $_SESSION['error'] = "Error: Start serial postfix and end serial postfix must be the same.";
        header("Location: form.php");
        exit();
    }

    unset($_SESSION['form_data']); // Clear form data on success

    $csvDirectory = __DIR__ . "/generated_csv/";
    if (!file_exists($csvDirectory)) {
        mkdir($csvDirectory, 0777, true);
    }

    $filename = "serial_numbers_" . time() . ".csv";
    $filePath = $csvDirectory . $filename;
    $output = fopen($filePath, 'w');

    if (!$output) {
        die("Error: Failed to create CSV file. Check folder permissions.");
    }

    fputcsv($output, ['SIRIM SERIAL NO.', 'BATCH NO.', 'BRAND/TRADEMARK', 'MODEL', 'TYPE', 'RATING', 'SIZE']);

    $currentPrefix = $startPrefix;
    $currentRunningChar = $startRunningChar;
    $currentNumber = $startNumber;
    $currentPostfix = $startPostfix;

    while (!(
        strcmp($currentPrefix, $endPrefix) > 0 ||
        (strcmp($currentPrefix, $endPrefix) == 0 && strcmp($currentRunningChar, $endRunningChar) > 0) ||
        (strcmp($currentPrefix, $endPrefix) == 0 && strcmp($currentRunningChar, $endRunningChar) == 0 && $currentNumber > $endNumber)
    )) {
        $serial = $currentPrefix . $currentRunningChar . str_pad($currentNumber, 7, '0', STR_PAD_LEFT) . $currentPostfix;

        // Ensure $excludedSerials is an array before using in_array()
        if (!is_array($excludedSerials)) {
            $excludedSerials = explode(',', $excludedSerials);
        }

        if (!in_array($serial, $excludedSerials)) {
            fputcsv($output, [$serial, '', '', '', '', '', '']);
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

    $startSerial = $startPrefix . $startRunningChar . str_pad($startNumber, 7, '0', STR_PAD_LEFT) . $startPostfix;
    $endSerial = $endPrefix . $endRunningChar . str_pad($endNumber, 7, '0', STR_PAD_LEFT) . $endPostfix;

    // $localIp = "10.255.6.204"; // changes constantly
    $localIp = "10.255.11.234"; // changed 21/3
    // $localIp = $_SERVER['SERVER_ADDR']; // changed 21/3
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
    $stmt->bind_param("sssss", $startSerial, $endSerial, $excludedSerialsString, $userPC, $qrImageData);
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
            <form action="form.php" method="get">
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
