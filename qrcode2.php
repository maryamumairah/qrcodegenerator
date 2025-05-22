<?php
$filename = ''; // Add this at the top of the file

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $prefix = strtoupper(trim($_POST['prefix']));
    $runningChar = strtoupper(trim($_POST['runningChar']));
    $postfix = isset($_POST['postfix']) ? strtoupper(trim($_POST['postfix'])) : '';
    $startNumber = intval($_POST['startNumber']);
    $endNumber = intval($_POST['endNumber']);
    $excludedNumbers = isset($_POST['excludedNumbers']) ? explode(',', str_replace(' ', '', $_POST['excludedNumbers'])) : [];
    $productType = $_POST['productType'];

    // Validate input
    function showAlertAndRedirect($message) {
        echo "<script>alert('$message'); window.history.back();</script>";
        exit;
    }
    
    if (!preg_match('/^[A-Z]{1,3}$/', $prefix)) {
        showAlertAndRedirect("Invalid prefix format.");
    }

    if (!preg_match('/^[A-HJ-NP-Z]$/', $runningChar)) {
        showAlertAndRedirect("Invalid running character format.");
    }
    
    if (!preg_match('/^[A-Z]$/', $postfix)) {
        showAlertAndRedirect("Invalid postfix format.");
    }
    
    if ($startNumber > $endNumber) {
        showAlertAndRedirect("Start number cannot be greater than end number.");
    }
    
    
    $excludedNumbers = array_map('intval', $excludedNumbers);

    // Generate serial numbers
    $serialNumbers = [];
    for ($i = $startNumber; $i <= $endNumber; $i++) {
        if (!in_array($i, $excludedNumbers)) {
            $serialNumbers[] = sprintf("%s%s%07d%s", $prefix, $runningChar, $i, $postfix);
        }
    }
    
    if (empty($serialNumbers)) {
        die("No valid serial numbers generated.");
    }

    // Define CSV headers based on product type
    if ($productType === "electrical") {
        $csvHeaders = ["SIRIM SERIAL NO.", "COA NUMBER", "MANUFACTURING DATE", "RATING", "SIZE"];
    } else {
        $csvHeaders = ["SIRIM SERIAL NO.", "BATCH NO.", "BRAND/TRADEMARK", "MODEL", "TYPE", "RATING", "SIZE"];
    }


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

    fputcsv($output, $csvHeaders);

    $currentPrefix = $prefix;
    $currentRunningChar = $runningChar;
    $currentNumber = $startNumber;
    $currentPostfix = $postfix;

    while ($currentNumber <= $endNumber) {
        $serial = $currentPrefix . $currentRunningChar . str_pad($currentNumber, 7, '0', STR_PAD_LEFT) . $currentPostfix;

        // Ensure $excludedSerials is an array before using in_array()
        if (!is_array($excludedNumbers)) {
            $excludedNumbers = explode(',', $excludedNumbers);
        }

        // Extract only the number portion of the serial for exclusion check
        $serialNumberOnly = (int) $currentNumber; 

        if (!in_array($serial, $excludedSerials)) {
            // Adjust the row length based on the number of CSV headers
            $emptyColumns = array_fill(0, count($csvHeaders) - 1, ""); 
            fputcsv($output, array_merge([$serial], $emptyColumns));
        }

        $currentNumber++;
    }


    fclose($output);

    $localIp = "10.255.11.234"; // changed 21/3
    $csvUrl = "http://" . $localIp . "/qrcodegenerator/generated_csv/" . $filename;
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($csvUrl);

    $csvFileName = "serial_numbers_" . $productType . "_" . time() . ".csv";
    $csvFilePath = $csvDirectory . $csvFileName;
    
    $csvFile = fopen($csvFilePath, "w");
    fputcsv($csvFile, $csvHeaders);
    foreach ($serialNumbers as $serial) {
        fputcsv($csvFile, array_merge([$serial], array_fill(0, count($csvHeaders) - 1, "")));
    }
    fclose($csvFile);

    // Generate QR code
    $localIp = '10.255.11.234';
    if (!empty($filename)) {
        $csvUrl = "http://" . $localIp . "/qrcodegenerator/generated_csv/" . $filename;
    } else {
        $csvUrl = ''; // Prevent further errors
    }    
    $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($csvUrl);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="output2.css">
    <title>QR Code Generated</title>
</head>
<body>
<div class="output-container">
        <?php if (!empty($csvUrl) && !empty($qrCodeUrl)): ?>
            <h3><strong>Scan the QR code to access the CSV file:</strong></h3>
            <img src="<?= $qrCodeUrl ?>" alt="QR Code" class="qr-code">
            <h3><a href="<?= $csvUrl ?>" download>Download CSV</a></h3>
            <!-- <h4><strong>Generated by:</strong> <?= htmlspecialchars($userPC) ?></h4> -->
        <?php endif; ?>
        <div class="btn-container">
            <!-- <button onclick="printQRCode();" class="btn">Print QR Code</button>
            <button class="btn view-report-btn">
                <a href="/qrcodegenerator/transaction_log.php" target="_blank">View Transaction Report</a>
            </button> -->
            <form action="form2.php" method="get">
                <button type="submit" class="btn">Generate Another QR Code</button>
            </form>
        </div>
    </div>
</body>
</html>
