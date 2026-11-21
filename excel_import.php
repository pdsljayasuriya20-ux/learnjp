<?php
// excel_import.php - Upload and import grammar rules from Excel

require 'vendor/autoload.php'; // PhpSpreadsheet library
use PhpOffice\PhpSpreadsheet\IOFactory;

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learnjapanese_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];
    
    try {
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Skip header row
        $imported = 0;
        $errors = [];
        
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Validate row
            if (empty($row[0]) || empty($row[1])) {
                $errors[] = "Row " . ($i + 1) . " is missing required data";
                continue;
            }
            
            // Insert grammar rule
            $stmt = $conn->prepare("INSERT INTO grammar_rules (rule_number, title_ja, romaji, description_en, description_ja, description_si, explanation_en, explanation_ja, explanation_si) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("issssssss", 
                $row[0],  // rule_number
                $row[1],  // title_ja
                $row[2],  // romaji
                $row[3],  // description_en
                $row[4],  // description_ja
                $row[5],  // description_si
                $row[6],  // explanation_en
                $row[7],  // explanation_ja
                $row[8]   // explanation_si
            );
            
            if ($stmt->execute()) {
                $rule_id = $conn->insert_id;
                $imported++;
                
                // Import examples if they exist in columns 9+
                // Format: sentence_ja, romaji, translation_en, translation_ja, translation_si (repeat for multiple examples)
                $example_col = 9;
                $example_order = 1;
                
                while (isset($row[$example_col]) && !empty($row[$example_col])) {
                    $example_stmt = $conn->prepare("INSERT INTO grammar_examples (rule_id, sentence_ja, romaji, translation_en, translation_ja, translation_si, example_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    
                    $example_stmt->bind_param("isssssi",
                        $rule_id,
                        $row[$example_col],     // sentence_ja
                        $row[$example_col + 1], // romaji
                        $row[$example_col + 2], // translation_en
                        $row[$example_col + 3], // translation_ja
                        $row[$example_col + 4], // translation_si
                        $example_order
                    );
                    
                    $example_stmt->execute();
                    $example_stmt->close();
                    
                    $example_col += 5; // Move to next example
                    $example_order++;
                }
            } else {
                $errors[] = "Row " . ($i + 1) . " failed: " . $stmt->error;
            }
            
            $stmt->close();
        }
        
        $message = "Successfully imported $imported grammar rules.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(", ", $errors);
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Grammar Rules from Excel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #0a0a0a;
            color: white;
            padding: 40px;
        }
        .import-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .table-format {
            background: rgba(255,255,255,0.03);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid rgba(255,255,255,0.1);
        }
        th {
            background: rgba(255,0,110,0.2);
            color: #ff006e;
            font-weight: bold;
        }
        .btn-gradient {
            background: linear-gradient(45deg, #ff006e, #8338ec);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255,0,110,0.4);
        }
        .alert {
            border-radius: 15px;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="import-card">
        <h1 style="background: linear-gradient(45deg, #ff006e, #8338ec); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            Import Grammar Rules
        </h1>
        
        <?php if (isset($message)): ?>
            <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="table-format">
            <h3 style="color: #8338ec; margin-bottom: 15px;">Excel File Format</h3>
            <p>Your Excel file should have the following columns (first row should be headers):</p>
            <table>
                <thead>
                    <tr>
                        <th>Column</th>
                        <th>Field Name</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>A</td>
                        <td>rule_number</td>
                        <td>Rule number (1-30)</td>
                    </tr>
                    <tr>
                        <td>B</td>
                        <td>title_ja</td>
                        <td>Japanese title (e.g., 〜です/だ)</td>
                    </tr>
                    <tr>
                        <td>C</td>
                        <td>romaji</td>
                        <td>Romanization (e.g., ...desu/da)</td>
                    </tr>
                    <tr>
                        <td>D</td>
                        <td>description_en</td>
                        <td>English description</td>
                    </tr>
                    <tr>
                        <td>E</td>
                        <td>description_ja</td>
                        <td>Japanese description</td>
                    </tr>
                    <tr>
                        <td>F</td>
                        <td>description_si</td>
                        <td>Sinhala description</td>
                    </tr>
                    <tr>
                        <td>G</td>
                        <td>explanation_en</td>
                        <td>Detailed English explanation</td>
                    </tr>
                    <tr>
                        <td>H</td>
                        <td>explanation_ja</td>
                        <td>Detailed Japanese explanation</td>
                    </tr>
                    <tr>
                        <td>I</td>
                        <td>explanation_si</td>
                        <td>Detailed Sinhala explanation</td>
                    </tr>
                    <tr>
                        <td>J-N</td>
                        <td>Example 1</td>
                        <td>sentence_ja, romaji, translation_en, translation_ja, translation_si</td>
                    </tr>
                    <tr>
                        <td>O-S</td>
                        <td>Example 2</td>
                        <td>sentence_ja, romaji, translation_en, translation_ja, translation_si</td>
                    </tr>
                    <tr>
                        <td>T-X</td>
                        <td>Example 3</td>
                        <td>sentence_ja, romaji, translation_en, translation_ja, translation_si</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <form method="POST" enctype="multipart/form-data" class="mt-4">
            <div class="mb-3">
                <label for="excel_file" class="form-label">Choose Excel File (.xlsx, .xls)</label>
                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
            </div>
            <button type="submit" class="btn-gradient w-100">Upload and Import</button>
        </form>

        <div class="mt-4">
            <a href="n5grammer.php" class="btn btn-secondary">Back to Grammar Rules</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>