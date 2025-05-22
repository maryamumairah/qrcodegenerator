<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Not Hidden</title>
    <link rel="stylesheet" href="formnothide.css">
</head>
<body>
    <div class="container">
        <h1 class="page-title">Generate CSV with QR Code</h1>
        <form action="newqrcode2.php" method="post">
            <!-- Product Type Selection -->
            <div class="product-type-container">
                <label class="label">Select Product Type <span class="required">*</span></label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="productType" value="electrical" required> Electrical
                    </label>
                    <label>
                        <input type="radio" name="productType" value="oil" required> Oil
                    </label>
                </div>
            </div>

            <div class="serial-parent-container">
                <div class="serial-inputs">
                    <div class="input-group">
                        <label for="prefix">Prefix<span class="required">*</span></label>
                        <input type="text" id="prefix" name="prefix" required maxlength="3" placeholder="e.g., AB, TAA" autocomplete="off">
                    </div>
                    <span></span>
                    <div class="input-group">
                        <label for="runningChar">Running Character<span class="required">*</span></label>
                        <input type="text" id="runningChar" name="runningChar" required maxlength="1" placeholder="A-Z (excl. I & O)" autocomplete="off">
                    </div>
                    <span></span>
                    <div class="input-group">
                        <label for="postfix">Postfix</label>
                        <input type="text" id="postfix" name="postfix" placeholder="A-Z" maxlength="1" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="serial-parent-container">
                <div class="serial-inputs">
                    <div class="input-group">
                        <label for="startNumber">Start Number<span class="required">*</span></label>
                        <input type="number" id="startNumber" name="startNumber" min="0" max="9999999" placeholder="0-9999999">
                    </div>
                    <span></span>
                    <div class="input-group">
                        <label for="endNumber">End Number<span class="required">*</span></label>
                        <input type="number" id="endNumber" name="endNumber" min="0" max="9999999" placeholder="0-9999999">
                    </div>
                    <span></span>
                    <div class="input-group">
                        <label for="excludedNumbers">Excluded Serial Numbers</label>
                        <input type="text" id="excludedNumbers" name="excludedNumbers" placeholder="e.g., 100, 250, 789" 
                            oninput="this.value = this.value.replace(/[^0-9,]/g, '')">
                        <small>*Comma-separated</small>
                    </div>
                </div>
            </div>
            
            
            <div class="submit-container">                
                <!-- Submit Button -->
                <button type="submit">Generate QR Code</button>
            </div>

            <!-- <div class="serial-parent-container">
                <div class="serial-inputs">
                    <div class="input-group">
                        <label for="excludedNumbers">Excluded Serial Numbers</label>
                        <input type="text" id="excludedNumbers" name="excludedNumbers" 
                            placeholder="e.g., 100, 250, 789" 
                            oninput="this.value = this.value.replace(/[^0-9,]/g, '')">
                    </div>
                </div>
            </div> -->
        </form>
    </div>
    
    <script>
        document.querySelector("form").addEventListener("submit", function (e) {
            const selectedProduct = document.querySelector('input[name="productType"]:checked');
            if (!selectedProduct) {
                alert("Please select a product type.");
                e.preventDefault(); // Prevents form submission
            }

            const excludedNumbersInput = document.getElementById("excludedNumbers").value.trim();
            let startNumber = parseInt(document.getElementById("startNumber").value);
            let endNumber = parseInt(document.getElementById("endNumber").value);

            if (excludedNumbersInput) {
                let excludedNumbersArray = excludedNumbersInput.split(",").map(num => num.trim()).filter(num => num !== "");
                
                for (let num of excludedNumbersArray) {
                    let parsedNum = parseInt(num);
                    if (isNaN(parsedNum) || parsedNum < startNumber || parsedNum > endNumber) {
                        alert(`Excluded number ${num} is out of range.`);
                        event.preventDefault(); // Prevent form submission if invalid
                        return;
                    }
                }
            }
        });
    </script>
</body>
</html>
