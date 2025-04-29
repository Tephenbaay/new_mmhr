<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mmhr_census";
$port = 3308;

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$files_query = "SELECT id, file_name FROM uploaded_files ORDER BY upload_date DESC";
$files_result = $conn->query($files_query);
$files = [];
while ($row = $files_result->fetch_assoc()) {
    $files[] = $row;
}
$selected_file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : 0;

$sheets = [];
if ($selected_file_id) {
    $sheets_query = "SELECT DISTINCT sheet_name FROM patient_records WHERE file_id = $selected_file_id";
    $sheets_result = $conn->query($sheets_query);
    while ($row = $sheets_result->fetch_assoc()) {
        $sheets[] = $row['sheet_name'];
    }

    $sheets_query_2 = "SELECT DISTINCT sheet_name_2 FROM patient_records_2 WHERE file_id = $selected_file_id";
    $sheets_result_2 = $conn->query($sheets_query_2);
    $sheets_2 = [];
    while ($row = $sheets_result_2->fetch_assoc()) {
        $sheets_2[] = $row['sheet_name_2'];
    }

    $sheets_query_3 = "SELECT DISTINCT sheet_name_3 FROM patient_records_3 WHERE file_id = $selected_file_id";
    $sheets_result_3 = $conn->query($sheets_query_3);
    $sheets_3 = [];
    while ($row = $sheets_result_3->fetch_assoc()) {
        $sheets_3[] = $row['sheet_name_3'];
    }
}

$selected_sheet_1 = isset($_GET['sheet_1']) ? $_GET['sheet_1'] : '';
$selected_sheet_2 = isset($_GET['sheet_2']) ? $_GET['sheet_2'] : '';
$selected_sheet_3 = isset($_GET['sheet_3']) ? $_GET['sheet_3'] : '';

$all_patient_data = [];

$all_sheets_query = "SELECT admission_date, discharge_date, member_category, sheet_name 
                     FROM patient_records 
                     WHERE file_id = $selected_file_id";
$all_sheets_result = $conn->query($all_sheets_query);

$summary = array_fill(1, 31, [
    'govt' => 0, 'private' => 0, 'self_employed' => 0, 'ofw' => 0,
    'owwa' => 0, 'sc' => 0, 'pwd' => 0, 'indigent' => 0, 'pensioners' => 0,
    'nhip' => 0, 'non_nhip' => 0, 'total_admissions' => 0, 'total_discharges_nhip' => 0,
    'total_discharges_non_nhip' => 0,'lohs_nhip' => 0, 'lohs_non_nhip' => 0
]);

    #column 1-5
    while ($row = $all_sheets_result->fetch_assoc()) {
        $admit = DateTime::createFromFormat('Y-m-d', trim($row['admission_date']))->setTime(0, 0, 0);
        $discharge = DateTime::createFromFormat('Y-m-d', trim($row['discharge_date']))->setTime(0, 0, 0);
        $category = trim(strtolower($row['member_category']));
    
        $selected_year = 2025;
        $month_numbers = [
            'JANUARY' => 1, 'FEBRUARY' => 2, 'MARCH' => 3, 'APRIL' => 4, 'MAY' => 5, 'JUNE' => 6,
            'JULY' => 7, 'AUGUST' => 8, 'SEPTEMBER' => 9, 'OCTOBER' => 10, 'NOVEMBER' => 11, 'DECEMBER' => 12
        ];
    
        $selected_month_name = strtoupper($selected_sheet_1);
        if (!isset($month_numbers[$selected_month_name])) {
            continue;
        }
        $selected_month = $month_numbers[$selected_month_name];
    
        $first_day_of_month = new DateTime("$selected_year-$selected_month-01");
        $last_day_of_month = new DateTime("$selected_year-$selected_month-" . cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year));
    
        if ($admit == $discharge) {
            continue;
        }
    
        // If the patient has days in this selected month
        if ($discharge >= $first_day_of_month && $admit <= $last_day_of_month) {
            $startDay = max($first_day_of_month, $admit)->format('d');
            $endDay = min($last_day_of_month, (clone $discharge)->modify('-1 day'))->format('d');
    
            for ($day = (int)$startDay; $day <= (int)$endDay; $day++) {
                if (!isset($summary[$day])) {
                    $summary[$day] = [
                        'govt' => 0, 'private' => 0, 'self_employed' => 0, 'ofw' => 0,
                        'owwa' => 0, 'sc' => 0, 'pwd' => 0, 'indigent' => 0, 'pensioners' => 0
                    ];
                }
    
                // Categorizing patients
                if (stripos($category, 'formal-government') !== false || stripos($category, 'sponsored- local govt unit') !== false) {
                    $summary[$day]['govt'] += 1;
                } elseif (stripos($category, 'formal-private') !== false) {
                    $summary[$day]['private'] += 1;
                } elseif (stripos($category, 'self earning individual') !== false || stripos($category, 'indirect contributor') !== false
                    || stripos($category, 'informal economy- informal sector') !== false) {
                    $summary[$day]['self_employed'] += 1;
                } elseif (stripos($category, 'migrant worker') !== false) {
                    $summary[$day]['ofw'] += 1;
                } elseif (stripos($category, 'direct contributor') !== false) {
                    $summary[$day]['owwa'] += 1;
                } elseif (stripos($category, 'senior citizen') !== false) {
                    $summary[$day]['sc'] += 1;
                } elseif (stripos($category, 'pwd') !== false) {
                    $summary[$day]['pwd'] += 1;
                } elseif (stripos($category, 'indigent') !== false || stripos($category, 'sponsored- pos financially incapable') !== false
                    || stripos($category, '4ps/mcct') !== false) {
                    $summary[$day]['indigent'] += 1;
                } elseif (stripos($category, 'lifetime member') !== false) {
                    $summary[$day]['pensioners'] += 1;
                }
            }
        }
    }

    #nhip column
    foreach ($summary as $day => $row) {
        $summary[$day]['nhip'] = 
            $row['govt'] + $row['private'] + $row['self_employed'] + 
            $row['ofw'] + $row['owwa'] + $row['sc'] + 
            $row['pwd'] + $row['indigent'] + $row['pensioners'];
    }    

    #column 9 non-nhip
    foreach ($summary as $day => $row) {
        $summary[$day]['lohs_nhip'] = 
            $row['govt'] + $row['private'] + $row['self_employed'] + 
            $row['ofw'] + $row['owwa'] + $row['sc'] + 
            $row['pwd'] + $row['indigent'] + $row['pensioners'];
    }  

    #non-nhip column
    $non_nhip_query = "SELECT date_admitted, date_discharge, category, sheet_name_3 
                   FROM patient_records_3 
                   WHERE sheet_name_3 = '$selected_sheet_3' AND file_id = $selected_file_id";
    $non_nhip_result = $conn->query($non_nhip_query);

    while ($row = $non_nhip_result->fetch_assoc()) {
        $admit = new DateTime($row['date_admitted']);
        $discharge = new DateTime($row['date_discharge']);
        $category = strtolower($row['category']);

        if (!(stripos($category, 'n/a') !== false)) {
            continue;
        }

        if ($admit->format('Y-m-d') === $discharge->format('Y-m-d')) {
            continue;
        }

        if ((int) $discharge->format('d') === 1) {
            continue;
        }

        $selected_month_name = date('F', mktime(0, 0, 0, $selected_month, 1, $selected_year));

        $monthStart = new DateTime("first day of $selected_month_name $selected_year");
        $monthEnd = new DateTime("last day of $selected_month_name $selected_year");

        $startDay = max(1, (int) $admit->format('d'));
        if ($admit < $monthStart) {
            $startDay = 1;
        }

        $endDay = min((int) $discharge->format('d') - 1, (int) $monthEnd->format('d'));

        if ($startDay <= $endDay) {
            for ($day = $startDay; $day <= $endDay; $day++) {
                $summary[$day]['non_nhip'] += 1;
            }
        }
    }

    #total admission column
    $admission_query = "SELECT admission_date_2 FROM patient_records_2 
                    WHERE sheet_name_2 = '$selected_sheet_2' AND file_id = $selected_file_id";
    $admission_result = $conn->query($admission_query);

    while ($row = $admission_result->fetch_assoc()) {
        $admit_day = (int)date('d', strtotime($row['admission_date_2']));

        if ($admit_day >= 1 && $admit_day <= 31) {
            $summary[$admit_day]['total_admissions'] += 1;
        }
    }

    $discharge_query = "SELECT date_discharge, category FROM patient_records_3 
                    WHERE sheet_name_3 = '$selected_sheet_3' AND file_id = $selected_file_id";
    $discharge_result = $conn->query($discharge_query);

    while ($row = $discharge_result->fetch_assoc()) {
        $discharge_day = (int)date('d', strtotime($row['date_discharge'])); 
        $category = strtolower($row['category']);

        if ($discharge_day >= 1 && $discharge_day <= 31) {
            if (!isset($summary[$discharge_day])) {
                $summary[$discharge_day] = [
                    'total_discharges_non_nhip' => 0,
                    'total_discharges_nhip' => 0
                ];
            }
            if (strpos($category, 'n/a') !== false || strpos($category, 'non phic') !== false || strpos($category, '#n/a') !== false) {
                $summary[$discharge_day]['total_discharges_non_nhip'] += 1;
            } else {
                $summary[$discharge_day]['total_discharges_nhip'] += 1;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMHR Census</title>
    <link rel="icon" href="sige/download-removebg-preview.png" type="image/png">
    <link rel="stylesheet" href="css\summary.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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

<div class="container1">                                
    <main class="main-content">
        <aside>
            <div class="sidebar" id="sidebar">
                <h3>Upload Excel File</h3>
                <form action="upload.php" method="POST" enctype="multipart/form-data">
                    <input type="file" name="excelFile" accept=".xlsx, .xls">
                    <button type="submit" class="btn1 btn-success">Upload</button>
                </form>
                <button onclick="printTable()" class="btn btn-success">Print Table</button>
                <form action="census.php" method="GET">
                    <button type="submit" class="btn btn-primary btn-2">View MMHR Census</button>
                </form>
                <form action="leading_causes.php" method="GET">
                    <button type="submit" class="btn btn-primary btn-3">View Leading Causes</button>
                </form>
                <button type="button" onclick="exportToExcel()" class="btn btn-success">Export to Excel</button>
            </div>
        </aside>

    <div class="container">

            <div class="table-responsive" id="content">
                <h2 class="text-center mb-4">MMHR Summary Table</h2>
                <form action="mmhr_census.php" method="GET">
                    <input type="hidden" name="sheet_1" value="<?php echo $selected_sheet_1; ?>">
                    <input type="hidden" name="sheet_2" value="<?php echo $selected_sheet_2; ?>">
                    <input type="hidden" name="sheet_3" value="<?php echo $selected_sheet_3; ?>">
                </form>

                <form method="GET" class="mb-3" id="filterForm">
                    <div class="sige">
                    <label for="file_id">Select File:</label>
                    <select name="file_id" id="file_id" onchange="document.getElementById('filterForm').submit()">
                        <option value="">-- Choose File --</option>
                        <?php foreach ($files as $file): ?>
                            <option value="<?= $file['id'] ?>" <?= $selected_file_id == $file['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($file['file_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($selected_file_id): ?>
                    <label class="col2-5"></label>
                    <select name="sheet_1" onchange="document.getElementById('filterForm').submit()" class="form-select mb-2">
                    <option value="" disabled selected>Select Month</option>
                        <?php foreach ($sheets as $sheet) { ?>
                            <option value="<?php echo $sheet; ?>" <?php echo $sheet === $selected_sheet_1 ? 'selected' : ''; ?>>
                                <?php echo $sheet; ?>
                            </option>
                        <?php } ?>
                    </select>

                    <label class="col7"></label>
                    <select name="sheet_2" onchange="document.getElementById('filterForm').submit()" class="form-select mb-2">
                    <option value="" disabled selected>Select Admission Sheet</option>
                        <?php foreach ($sheets_2 as $sheet) { ?>
                            <option value="<?php echo $sheet; ?>" 
                                <?php echo $sheet === $selected_sheet_2 ? 'selected' : ''; ?>>
                                <?php echo $sheet; ?>
                            </option>
                        <?php } ?>
                    </select>

                    <label class="col8"></label>
                    <select name="sheet_3" onchange="document.getElementById('filterForm').submit()" class="form-select mb-2">
                    <option value="" disabled selected>Select Discharge Sheet</option>
                    <?php foreach ($sheets_3 as $sheet): ?>
                        <option value="<?= $sheet ?>" <?= $sheet == $selected_sheet_3 ? 'selected' : '' ?>><?= $sheet ?></option>
                    <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive1" id="printable">
                    <table class="table table-bordered" id="summaryTable">
                        <thead class="table-dark text-center">
                        <tr class="th1">
                                <th colspan="1" style="background-color: black; color: white;">1</th>
                                <th colspan="2" style="background-color: black; color: white;">2</th>
                                <th colspan="5" style="background-color: black; color: white;">3</th>
                                <th rowspan="1" style="background-color: black; color: white;">4</th>
                                <th rowspan="1" style="background-color: black; color: white;">5</th>
                                <th colspan="2" style="background-color: black; color: white;">6</th>
                                <th rowspan="1" style="background-color: black; color: white;">7</th>
                                <th colspan="2" style="background-color: black; color: white;">8</th>
                                <th colspan="2" style="background-color: black; color: white;">9</th>
                            </tr>
                            <tr>
                                <th rowspan="2" style="background-color: #c7f9ff;">Date</th>
                                <th colspan="2" style="background-color: yellow;">Employed</th>
                                <th colspan="5" style="background-color: yellow;">Individual Paying</th>
                                <th rowspan="2" style="background-color: yellow;">Indigent</th>
                                <th rowspan="2" style="background-color: yellow;">Pensioners</th>
                                <th colspan="2" style="background-color: #c7f9ff;"> NHIP / NON-NHIP</th>
                                <th rowspan="2" style="background-color: yellow;">Total Admissions</th>
                                <th colspan="2" style="background-color: yellow;">Total Discharges</th>
                                <th colspan="2" style="background-color: yellow;">Accumulated Patients LOHS</th>
                            </tr>
                            <tr>
                                <th style="background-color: green; color: white;">Gov’t</th><th style="background-color: green; color: white;">Private</th>
                                <th style="background-color: green; color: white;">Self-Employed</th><th style="background-color: green; color: white;">OFW</th>
                                <th style="background-color: green; color: white;">OWWA</th><th style="background-color: green; color: white;">SC</th><th style="background-color: green; color: white;">PWD</th>
                                <th style="background-color:rgb(0, 0, 0); color: white;" id="th1">NHIP</th><th style="background-color: #c7f9ff;">NON-NHIP</th>
                                <th style="background-color: orange;">NHIP</th><th style="background-color: orange;">NON-NHIP</th>
                                <th style="background-color: #c7f9ff;">NHIP</th><th style="background-color: #c7f9ff;">NON-NHIP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totals = [
                                'govt' => 0, 'private' => 0, 'self_employed' => 0, 'ofw' => 0,
                                'owwa' => 0, 'sc' => 0, 'pwd' => 0, 'indigent' => 0, 'pensioners' => 0,
                                'nhip' => 0, 'non_nhip' => 0, 'total_admissions' => 0, 'total_discharges_nhip' => 0,
                                'total_discharges_non_nhip' => 0, 'lohs_nhip' => 0
                            ];
                        
                            foreach ($summary as $day => $row) { 
                                foreach ($totals as $key => &$total) {
                                    $total += $row[$key];
                                }
                            ?>
                                <tr class="tdata">
                                    <td class="text-center"> <?php echo $day; ?> </td> 
                                    <td class="text-center"> <?php echo $row['govt']; ?> </td>
                                    <td class="text-center"> <?php echo $row['private']; ?> </td>
                                    <td class="text-center"> <?php echo $row['self_employed']; ?> </td>
                                    <td class="text-center"> <?php echo $row['ofw']; ?> </td>
                                    <td class="text-center"> <?php echo $row['owwa']; ?> </td>
                                    <td class="text-center"> <?php echo $row['sc']; ?> </td>
                                    <td class="text-center"> <?php echo $row['pwd']; ?> </td>
                                    <td class="text-center"> <?php echo $row['indigent']; ?> </td>
                                    <td class="text-center"> <?php echo $row['pensioners']; ?> </td>
                                    <td class="text-center" style="background-color: black; color: white;"> <?php echo $row['nhip']; ?> </td>
                                    <td class="text-center"> <?php echo $row['non_nhip']; ?> </td>
                                    <td class="text-center"> <?php echo $row['total_admissions']; ?> </td>
                                    <td class="text-center"> <?php echo $row['total_discharges_nhip']; ?> </td>
                                    <td class="text-center"> <?php echo $row['total_discharges_non_nhip']; ?> </td>
                                    <td class="text-center"> <?php echo $row['lohs_nhip']; ?> </td>
                                    <td class="text-center"> <?php echo $row['non_nhip']; ?> </td>
                                </tr>
                            <?php } ?>
                            
                            <tfoot class="footer">
                            <tr class="table-dark text-center fw-bold">
                                <td style="background-color:rgb(0, 0, 0); color: white;">Total</td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['govt']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['private']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['self_employed']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['ofw']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['owwa']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['sc']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['pwd']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['indigent']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['pensioners']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['nhip']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['non_nhip']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['total_admissions']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['total_discharges_nhip']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['total_discharges_non_nhip']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['lohs_nhip']; ?></td>
                                <td style="background-color:rgb(0, 0, 0); color: white;"><?php echo $totals['non_nhip']; ?></td>
                            </tr>
                            </tfoot>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>

function printTable() {
    var printContents = document.getElementById("printable").innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;

    reinitializeEventListeners();
}

function exportToExcel() {
    var table = document.getElementById("summaryTable");

    if (!table) {
        console.log("Table not found!");
        return;
    }

    var ws = XLSX.utils.table_to_sheet(table);
    const range = XLSX.utils.decode_range(ws['!ref']);

    for (let R = range.s.r; R <= range.e.r; ++R) {
        for (let C = range.s.c; C <= range.e.c; ++C) {
            const cell_ref = XLSX.utils.encode_cell({ r: R, c: C });
            const cell = ws[cell_ref];
            if (!cell) continue;

            if (!cell.s) cell.s = {};
            cell.s.alignment = { horizontal: "center", vertical: "center" };

            if (R <= 2) {
                cell.s.font = { bold: true };

                if (R === 0) {
                    cell.s.fill = { fgColor: { rgb: "000000" } }; 
                    cell.s.font.color = { rgb: "FFFFFF" }; 
                } else if (R === 1) {
                    if (C === 0 || (C >= 10 && C <= 11)) {
                        cell.s.fill = { fgColor: { rgb: "c7f9ff" } };
                    } else {
                        cell.s.fill = { fgColor: { rgb: "FFFF00" } };
                    }
                } else if (R === 2) {
                    if (C >= 0 && C <= 6) {
                        cell.s.fill = { fgColor: { rgb: "008000" } }; 
                        cell.s.font.color = { rgb: "FFFFFF" };
                    } else if (C === 7) {
                        cell.s.fill = { fgColor: { rgb: "000000" } }; 
                        cell.s.font.color = { rgb: "FFFFFF" };
                    } else if (C === 8) {
                        cell.s.fill = { fgColor: { rgb: "c7f9ff" } };
                    } else if (C === 9 || C === 10) {
                        cell.s.fill = { fgColor: { rgb: "FFA500" } }; 
                    } else if (C === 11 || C === 12) {
                        cell.s.fill = { fgColor: { rgb: "0000FF" } }; 
                        cell.s.font.color = { rgb: "FFFFFF" };
                    }
                }
            }
        }
    }

    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "MMHR Summary");

    var wbout = XLSX.write(wb, {
        bookType: "xlsx",
        type: "binary",
        cellStyles: true 
    });

    var blob = new Blob([s2ab(wbout)], { type: "application/octet-stream" });
    var link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "MMHR_Summary.xlsx";
    link.click();
}

function s2ab(s) {
    var buf = new ArrayBuffer(s.length);
    var view = new Uint8Array(buf);
    for (var i = 0; i < s.length; i++) {
        view[i] = s.charCodeAt(i) & 0xff;
    }
    return buf;
}

function reinitializeEventListeners() {
    const toggleBtn = document.getElementById("toggleBtn");
    const sidebar = document.getElementById("sidebar");
    const content = document.getElementById("content");
    let isSidebarVisible = true;

    toggleBtn.addEventListener("click", () => {
        isSidebarVisible = !isSidebarVisible;
        if (isSidebarVisible) {
            sidebar.classList.remove("hidden");
            toggleBtn.style.left = "260px";
            content.style.marginLeft = "270px";
            content.style.marginRight = "0"; 
            toggleBtn.textContent = "Hide";
        } else {
            sidebar.classList.add("hidden");
            toggleBtn.style.left = "10px";
            content.style.marginLeft = "auto"; 
            content.style.marginRight = "auto"; 
            toggleBtn.textContent = "Show";
        }
    });
}

const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggleBtn");
const content = document.getElementById("content"); 
let isSidebarVisible = true;

toggleBtn.addEventListener("click", () => {
    isSidebarVisible = !isSidebarVisible;
    if (isSidebarVisible) {
        sidebar.classList.remove("hidden");
        toggleBtn.style.left = "260px";
        content.style.marginLeft = "270px"; 
        content.style.marginRight = "0"; 
        toggleBtn.textContent = "Hide";
    } else {
        sidebar.classList.add("hidden");
        toggleBtn.style.left = "10px";
        content.style.marginLeft = "auto"; 
        content.style.marginRight = "auto"; 
        toggleBtn.textContent = "Show";
    }
});
</script>

</body>
</html>