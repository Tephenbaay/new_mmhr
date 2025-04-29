<?php
include 'config.php';

// Get list of files
$files_query = "SELECT id, file_name FROM uploaded_files ORDER BY upload_date DESC";
$files_result = $conn->query($files_query);
$files = [];
while ($row = $files_result->fetch_assoc()) {
    $files[] = $row;
}

// Get selected file ID and sheet
$selected_file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : 0;
$selected_sheet = $_GET['sheet'] ?? '';

// Sheets from selected file
$sheets = [];
if ($selected_file_id) {
    $sheets_query = "SELECT DISTINCT sheet_name FROM patient_records WHERE file_id = $selected_file_id";
    $sheets_result = $conn->query($sheets_query);
    while ($row = $sheets_result->fetch_assoc()) {
        $sheets[] = $row['sheet_name'];
    }
}

// ICD summary query
$icd_summary = [];

if ($selected_file_id && $selected_sheet) {
    $query = "
        SELECT 
            lc.icd_10,
            SUM(CASE WHEN pr.member_category = 'N/A' THEN 1 ELSE 0 END) AS non_nhip_total,
            SUM(CASE WHEN pr.member_category != 'N/A' THEN 1 ELSE 0 END) AS nhip_total
        FROM leading_causes lc
        JOIN patient_records pr 
            ON lc.patient_name = pr.patient_name AND lc.file_id = pr.file_id
        WHERE lc.sheet_name = ? AND lc.file_id = ?
        GROUP BY lc.icd_10
        ORDER BY nhip_total DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $selected_sheet, $selected_file_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $icd_summary[] = $row;
    }
}
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMHR Census</title>
    <link rel="stylesheet" href="css/leading.css">
</head>
<body class="container">
    <nav class="navbar">
        <div class="nav-container">
            <img src="css/download-removebg-preview.png" alt="icon" class="logo">
            <div class="nav-text">
                <h1>BicutanMed</h1>
                <p>Caring For Life</p>
            </div>
            <div class="nav-links">
                <a href="dashboard.php">Home</a>
                <a href="#">Tools</a>
                <a href="#">Maintenance</a>
                <a href="https://bicutanmed.com/about-us">About us</a>
                <a href="#">Settings</a>
            </div>
            <a href="logout.php" class="logout-link">
                <img src="css/power-off.png" alt="logout" class="logout-icon">
            </a>
        </div>
    </nav>

<aside>
    <div class="sidebar" id="sidebar">
        <h3>Upload Excel File</h3>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="excelFile" accept=".xlsx, .xls">
            <button type="submit" class="btn1 btn-success">Upload</button>
        </form>
        <button class="btn btn-success no-print" onclick="window.print()">Print Table</button>
        <form action="display_summary.php" method="GET">
            <button type="submit" class="btn btn-primary btn-2">View MMHR Table</button>
        </form>
        <form action="census.php" method="GET">
            <button type="submit" class="btn btn-primary btn-3">View MMHR Census</button>
        </form>
        <button type="button" onclick="exportToExcel()" class="btn btn-success">Export to Excel</button>
    </div>
</aside>

</body>
</html>