<?php

session_start();

// Check if this is a hard refresh 
if (!isset($_SESSION['form_data_persisted'])) {
    $_SESSION['form_data'] = [];
    $_SESSION['form_data_persisted'] = true; 
}

// Retrieve error message if exists
if (isset($_SESSION['error'])) {
    echo "<script>alert('" . addslashes($_SESSION['error']) . "');</script>";
    unset($_SESSION['error']);
}

// Retrieve stored input values
$formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];

// Retrieve excluded serials
$excludedSerials = isset($formData['excludedSerials']) ? explode(',', $formData['excludedSerials']) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate CSV with QR Code</title>
    <link rel="stylesheet" href="newstyles.css">
</head>
<body>
    <div class="container">
        <h1 class="page-title">Generate CSV with QR Code</h1>
        <form action="newqrcode.php" method="post">
            <div class="product-type-container">
                <label class="label">Select Product Type <span class="required">*</span></label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="productType" value="Electrical" required> Electrical
                    </label>
                    <span></span>
                    <label>
                        <input type="radio" name="productType" value="Engine Oil" required> Engine Oil
                    </label>
                </div>
            </div>

            <!-- Start Serial -->
            <div class="serial-parent-container">
                <label class="label">Start Serial</label>
                <div class="serial-inputs">
                    <div class="input-group">
                        <label for="startPrefix">Prefix<span class="required">*</span></label>
                        <input type="text" id="startPrefix" name="startPrefix" required maxlength="3" placeholder="e.g., AB, TAA" autocomplete="off"
                            value="<?= htmlspecialchars($formData['startPrefix'] ?? '') ?>">
                    </div>
                    <span></span>
                    <div class="input-group">
                        <label for="startRunningChar">Running Character<span class="required">*</span></label>
                        <input type="text" id="startRunningChar" name="startRunningChar" required maxlength="1" placeholder="A-Z (excl. I & O)" autocomplete="off"
                            value="<?= htmlspecialchars($formData['startRunningChar'] ?? '') ?>">
                    </div>
                    <span></span>
                    <div class="input-group">
                        <label for="startNumber">Running Number<span class="required">*</span></label>
                        <input type="number" id="startNumber" name="startNumber" min="0" max="9999999" placeholder="0-9999999" 
                            oninput="this.value = this.value.slice(0, 7)" 
                            value="<?= htmlspecialchars($formData['startNumber'] ?? '') ?>">
                    </div>
                    <span></span>
                    <div class="input-group">
                        <label for="startPostfix">Postfix</label>
                        <input type="text" id="startPostfix" name="startPostfix" placeholder="A-Z" maxlength="1" autocomplete="off"
                            value="<?= htmlspecialchars($formData['startPostfix'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- End Serial -->
            <div class="serial-parent-container">
                <label class="label">End Serial</label>
                <div class="serial-inputs">
                    <div class="input-group">
                        <label for="endPrefix">Prefix<span class="required">*</span></label>
                        <input type="text" id="endPrefix" name="endPrefix" required maxlength="3" placeholder="e.g., AB, TAA" autocomplete="off"
                            value="<?= htmlspecialchars($formData['endPrefix'] ?? '') ?>">
                    </div>
                    <span></span>
                    <div class="input-group">
                        <label for="endRunningChar">Running Character<span class="required">*</span></label>
                        <input type="text" id="endRunningChar" name="endRunningChar" required maxlength="1" placeholder="A-Z (excl. I & O)" autocomplete="off"
                            value="<?= htmlspecialchars($formData['endRunningChar'] ?? '') ?>">
                    </div>
                    <span></span>
                    <div class="input-group">
                        <label for="endNumber">Running Number<span class="required">*</span></label>
                        <input type="number" id="endNumber" name="endNumber" min="0" max="9999999" placeholder="0-9999999" 
                            oninput="this.value = this.value.slice(0, 7)" 
                            value="<?= htmlspecialchars($formData['endNumber'] ?? '') ?>">
                    </div>
                    <span></span>
                    <div class="input-group">
                        <label for="endPostfix">Postfix</label>
                        <input type="text" id="endPostfix" name="endPostfix" placeholder="A-Z" maxlength="1" autocomplete="off"
                            value="<?= htmlspecialchars($formData['endPostfix'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Exclude Serials -->
            <div class="serial-parent-container">
                <label class="label">Exclude Serials</label>
                <div class="serial-inputs-2">
                    <div class="input-group-2">
                        <label for="excludedPrefix">Prefix</label>
                        <input type="text" id="excludedPrefix" name="excludedPrefix" maxlength="3" placeholder="e.g., AB, TAA">
                    </div>
                    <span></span>
                    <div class="input-group-2">
                        <label for="excludedRunningChar">Running Character</label>
                        <input type="text" id="excludedRunningChar" name="excludedRunningChar" maxlength="1" placeholder="A-Z (excl. I & O)">
                    </div>
                    <span></span>
                    <div class="input-group-2">
                        <label for="excludedNumber">Running Number</label>
                        <input type="number" id="excludedNumber" name="excludedNumber" min="0" max="9999999" placeholder="0-9999999" 
                            oninput="this.value = this.value.slice(0, 7)">
                    </div>
                    <span></span>
                    <div class="input-group-2">
                        <label for="excludedPostfix">Postfix</label>
                        <input type="text" id="excludedPostfix" name="excludedPostfix" placeholder="A-Z" maxlength="1">
                    </div>
                    <!-- Add Excluded Serial Button -->
                    <button type="button" id="addExcludedSerial" class="exclude-btn">Add</button>
                </div>
                <!-- Hidden Input Field to Store Excluded Serials -->
                <input type="hidden" name="excludedSerials" id="excludedSerialsHidden">
                <!-- Display List of Excluded Serials -->
                <ul id="excludedSerialsList"></ul>
            </div>
            
            <div class="submit-container">                
                <!-- Submit Button -->
                <button type="submit">Generate QR Code</button>
            </div>
        </form>
    </div>
    
    <script>
        document.querySelector("form").addEventListener("submit", function (e) {
            const selectedProduct = document.querySelector('input[name="productType"]:checked');
            if (!selectedProduct) {
                alert("Please select a product type.");
                e.preventDefault(); // Prevents form submission
            }
        });
        
        document.addEventListener("DOMContentLoaded", function () {
            const addExcludedSerialBtn = document.getElementById("addExcludedSerial");
            const excludedSerialsList = document.getElementById("excludedSerialsList");
            const excludedSerialsHidden = document.getElementById("excludedSerialsHidden");

            const startPrefix = document.querySelector("input[name='startPrefix']");
            const startRunningChar = document.querySelector("input[name='startRunningChar']");
            const startNumber = document.querySelector("input[name='startNumber']");
            const endPrefix = document.querySelector("input[name='endPrefix']");
            const endRunningChar = document.querySelector("input[name='endRunningChar']");
            const endNumber = document.querySelector("input[name='endNumber']");

            const excludedPrefix = document.getElementById("excludedPrefix");
            const excludedRunningChar = document.getElementById("excludedRunningChar");
            const excludedNumber = document.getElementById("excludedNumber");
            const excludedPostfix = document.getElementById("excludedPostfix");

            const allInputs = document.querySelectorAll("input"); // Select all input fields

            let excludedSerialsArray = <?= json_encode($excludedSerials) ?> || []; 

            // Only keep non-empty values for excluded serials
            excludedSerialsArray = excludedSerialsArray.filter(serial => serial.trim() !== "");

            // Check if this is a hard refresh
            const navigationEntries = performance.getEntriesByType("navigation");
            if (navigationEntries.length > 0 && navigationEntries[0].type === "reload") {
                excludedSerialsArray = []; // Reset array on hard refresh
                excludedSerialsHidden.value = "";

                // Reset all input fields
                allInputs.forEach(input => {
                    if (input.type === "text" || input.type === "number") {
                        input.value = "";
                    }
                });
            } else {
                // Restore excluded serials from hidden input (if any)
                if (excludedSerialsHidden.value) {
                    excludedSerialsArray = excludedSerialsHidden.value.split(",").filter(serial => serial.trim() !== "");
                    updateExcludedSerialsList();
                }
            }

            function areSerialInputsFilled() {
                return (
                    startPrefix.value.trim() !== "" &&
                    startRunningChar.value.trim() !== "" &&
                    startNumber.value.trim() !== "" &&
                    endPrefix.value.trim() !== "" &&
                    endRunningChar.value.trim() !== "" &&
                    endNumber.value.trim() !== ""
                );
            }

            addExcludedSerialBtn.addEventListener("click", function () {
                const prefix = excludedPrefix.value.trim();
                const runningChar = excludedRunningChar.value.trim();
                const number = excludedNumber.value.trim();
                const postfix = excludedPostfix.value.trim();

                // Format excluded serial like: ABA0000001A
                const formattedSerial = `${prefix}${runningChar}${number.padStart(7, '0')}${postfix}`;

                // Get start and end serials
                const startPostfixValue = startPostfix.value.trim();
                const endPostfixValue = endPostfix.value.trim();

                const startSerial = `${startPrefix.value.trim()}${startRunningChar.value.trim()}${startNumber.value.trim().padStart(7, '0')}${startPostfixValue}`;
                const endSerial = `${endPrefix.value.trim()}${endRunningChar.value.trim()}${endNumber.value.trim().padStart(7, '0')}${endPostfixValue}`;

                if (!areSerialInputsFilled()) {
                    alert("Please fill in the start and end serials before adding excluded serials.");
                    return;
                } else if (!prefix || !runningChar || !number) {
                    alert("Enter all required fields (Prefix, Running Character, Running Number) in order to add excluded serial.");
                    return;
                }

                // Check if excluded serial has a postfix while start or end does not
                if ((postfix && !startPostfixValue) || (postfix && !endPostfixValue)) {
                    alert("No serial number found within the start serial and end serial range.");
                    return;
                }

                // Check if start and end serials have a postfix, but the excluded serial does not
                if ((!postfix && startPostfixValue) || (!postfix && endPostfixValue)) {
                    alert("No serial number found within the start serial and end serial range.");
                    return;
                }

                // Ensure the excluded serial is within the range
                if (formattedSerial < startSerial || formattedSerial > endSerial) {
                    alert("No serial number found within the start serial and end serial range.");
                    return; // Prevents adding to the list
                }

                if (!excludedSerialsArray.includes(formattedSerial)) {
                    excludedSerialsArray.push(formattedSerial);
                    updateExcludedSerialsList();
                    
                    // Clear inputs
                    excludedPrefix.value = "";
                    excludedRunningChar.value = "";
                    excludedNumber.value = "";
                    excludedPostfix.value = "";
                } else {
                    alert("This serial number is already added.");
                }
            });

            function updateExcludedSerialsList() {
                excludedSerialsList.innerHTML = "";

                if (excludedSerialsArray.length === 0) {
                    excludedSerialsList.style.display = "none"; // Hide the list if empty
                } else {
                    excludedSerialsList.style.display = "block"; // Show the list if items exist
                }

                excludedSerialsArray.forEach((serial, index) => {
                    const listItem = document.createElement("li");
                    listItem.textContent = serial;

                    const removeBtn = document.createElement("button");
                    removeBtn.textContent = "Remove";
                    removeBtn.onclick = function () {
                        excludedSerialsArray.splice(index, 1);
                        updateExcludedSerialsList();
                    };

                    listItem.appendChild(removeBtn);
                    excludedSerialsList.appendChild(listItem);
                });

                // Update hidden input field
                excludedSerialsHidden.value = excludedSerialsArray.join(",");
            }

            // Ensure excluded serials list is preserved when navigating back
            window.addEventListener("pageshow", function () {
                updateExcludedSerialsList(); 
            });

            updateExcludedSerialsList(); // Ensure list updates on page load
        });
    </script>
</body>
</html>
