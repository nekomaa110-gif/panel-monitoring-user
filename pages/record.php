<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

$search = trim($_GET['search'] ?? '');
$month  = $_GET['month'] ?? '';
$year   = $_GET['year'] ?? '';

$where  = [];
$params = [];
$types  = "";

/* =========================
   SEARCH (CASE INSENSITIVE)
========================= */
if ($search !== '') {
    $where[]  = "LOWER(username) LIKE LOWER(?)";
    $params[] = "%" . $search . "%";
    $types   .= "s";
}

/* =========================
   FILTER BULAN
========================= */
if ($month !== '' && is_numeric($month) && $month >= 1 && $month <= 12) {
    $where[]  = "MONTH(first_login) = ?";
    $params[] = (int)$month;
    $types   .= "i";
}

/* =========================
   FILTER TAHUN
========================= */
if ($year !== '' && is_numeric($year) && $year >= 2000 && $year <= date('Y') + 1) {
    $where[]  = "YEAR(first_login) = ?";
    $params[] = (int)$year;
    $types   .= "i";
}

/* =========================
   BUILD QUERY
========================= */
$sql = "
SELECT username, first_login, ip_address
FROM user_record
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY first_login DESC";

/* =========================
   EXECUTE
========================= */
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$q = $stmt->get_result();

/* =========================
   VIEW
========================= */
$pageTitle = 'Record User';
$navTitle  = 'Record User';

include __DIR__ . "/../views/record.view.php";