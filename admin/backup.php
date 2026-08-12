<?php
// ===== VERSION: v2026-08-11-FINAL ===== (यह Line दिखे तो File Latest है)
/**
 * FILE: admin/backup.php
 * Simple database backup - exports all tables to a downloadable .sql file
 */
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Backup';
$current = 'backup';

if (isset($_GET['download'])) {
    $tables = [];
    $result = $conn->query('SHOW TABLES');
    while ($row = $result->fetch_row()) $tables[] = $row[0];

    $sqlDump = "-- Database Backup - Generated on " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $createRes = $conn->query("SHOW CREATE TABLE `$table`");
        $createRow = $createRes->fetch_row();
        $sqlDump .= "\nDROP TABLE IF EXISTS `$table`;\n" . $createRow[1] . ";\n\n";

        $dataRes = $conn->query("SELECT * FROM `$table`");
        while ($row = $dataRes->fetch_assoc()) {
            $columns = array_map(fn($c) => "`$c`", array_keys($row));
            $values = array_map(function ($v) use ($conn) {
                if ($v === null) return 'NULL';
                return "'" . $conn->real_escape_string($v) . "'";
            }, array_values($row));
            $sqlDump .= "INSERT INTO `$table` (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ");\n";
        }
    }

    $filename = 'backup_' . date('Y-m-d_His') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $sqlDump;
    exit();
}

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h3 style="margin-bottom:16px;">Database Backup</h3>
    <p style="margin-bottom:16px;color:var(--text-light);">पूरे Database का Backup एक .sql फाइल में Download करें। इसे सुरक्षित जगह रखें।</p>
    <a href="backup.php?download=1" class="btn btn-primary">⬇️ Backup Download करें</a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
