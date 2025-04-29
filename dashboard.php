<?php
//session_start();
//if (!isset($_SESSION["user_id"])) {
//    header("Location: index.php"); // Redirect to login if not authenticated
//    exit;
//}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mmhr";
$port = 3308; // Default MySQL port

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch uploaded files
$files = $conn->query("SELECT * FROM uploaded_files");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/dashboard.css">
    <title>MMHR Census</title>
</head>
<body class="body-bg">
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


    <div class="container">
    <main class="main-content">
        <section class="upload-section">
            <h2>Upload Excel File</h2>
            <form action="upload.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="excelFile" accept=".xlsx, .xls" class="file-input">
                <button type="submit" class="btn-upload">Upload</button>
            </form>
        </section>

        <section class="select-section">
            <h2>Select File & Sheet</h2>
            <label for="file">Select File:</label>
            <select name="file_id" id="file" class="file-dropdown">
                <?php while ($file = $files->fetch_assoc()): ?>
                    <option value="<?= $file['id'] ?>"><?= $file['file_name'] ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn-submit">Load Sheets</button>
        </section>

    </main>
</body>
</html>