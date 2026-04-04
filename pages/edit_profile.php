<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";
require __DIR__ . "/../core/helpers.php";

$attributeWhitelist = [
    'Simultaneous-Use',
    'Session-Timeout',
    'Max-All-Session',
    'Expiration',
    'Mikrotik-Rate-Limit',
    'Mikrotik-Group',
    'Idle-Timeout'
];

$checkAttributes = [
    'Simultaneous-Use',
    'Session-Timeout',
    'Max-All-Session',
    'Expiration',
    'Idle-Timeout'
];

$replyAttributes = [
    'Mikrotik-Rate-Limit',
    'Mikrotik-Group'
];

$groupname = isset($_GET['name']) ? trim($_GET['name']) : '';
$error = '';
$attributes = [];

if ($groupname === '') {
    $error = "Parameter profile tidak valid.";
} else {
    $stmtCheck = $conn->prepare("SELECT attribute, op, value FROM radgroupcheck WHERE groupname = ? ORDER BY attribute");
    if ($stmtCheck) {
        $stmtCheck->bind_param("s", $groupname);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        while ($row = $resCheck->fetch_assoc()) {
            if (in_array($row['attribute'], $attributeWhitelist, true)) {
                $attributes[] = [
                    'attribute' => $row['attribute'],
                    'op' => $row['op'],
                    'value' => $row['value']
                ];
            }
        }
        $stmtCheck->close();
    } else {
        $error = "Gagal menyiapkan query radgroupcheck.";
    }

    if ($error === '') {
        $stmtReply = $conn->prepare("SELECT attribute, op, value FROM radgroupreply WHERE groupname = ? ORDER BY attribute");
        if ($stmtReply) {
            $stmtReply->bind_param("s", $groupname);
            $stmtReply->execute();
            $resReply = $stmtReply->get_result();
            while ($row = $resReply->fetch_assoc()) {
                if (in_array($row['attribute'], $attributeWhitelist, true)) {
                    $attributes[] = [
                        'attribute' => $row['attribute'],
                        'op' => $row['op'],
                        'value' => $row['value']
                    ];
                }
            }
            $stmtReply->close();
        } else {
            $error = "Gagal menyiapkan query radgroupreply.";
        }
    }

    if ($error === '' && empty($attributes)) {
        $error = "Profile tidak ditemukan.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $postedAttr = $_POST['attribute'] ?? [];
    $postedOp = $_POST['op'] ?? [];
    $postedVal = $_POST['value'] ?? [];

    if (!is_array($postedAttr) || !is_array($postedOp) || !is_array($postedVal)) {
        $error = "Data submit tidak valid.";
    } elseif (count($postedAttr) !== count($postedOp) || count($postedOp) !== count($postedVal) || count($postedAttr) === 0) {
        $error = "Jumlah data tidak valid.";
    } else {
        $normalizedRows = [];
        $seenAttributes = [];

        for ($i = 0; $i < count($postedAttr); $i++) {
            $attribute = trim($postedAttr[$i]);
            $op = trim($postedOp[$i]);
            $value = trim($postedVal[$i]);

            if (!in_array($attribute, $attributeWhitelist, true)) {
                $error = "Attribute tidak valid: " . htmlspecialchars($attribute);
                break;
            }

            if (isset($seenAttributes[$attribute])) {
                $error = "Attribute duplicate tidak diperbolehkan: " . htmlspecialchars($attribute);
                break;
            }

            if ($value === '') {
                $error = "Value tidak boleh kosong untuk attribute: " . htmlspecialchars($attribute);
                break;
            }

            if ($op === '') {
                $op = ':=';
            }

            if ($attribute === 'Session-Timeout' || $attribute === 'Max-All-Session') {
                $parsed = parseDuration($value);
                if ($parsed === false) {
                    $error = "Format duration tidak valid untuk {$attribute}. Contoh: 4 jam, 1 hari.";
                    break;
                }
                $value = $parsed;
            } elseif ($attribute === 'Expiration') {
                $parsed = parseExpiration($value);
                if ($parsed === false) {
                    $error = "Format expiration tidak valid. Contoh: +7 hari.";
                    break;
                }
                $value = $parsed;
            } elseif ($attribute === 'Mikrotik-Rate-Limit') {
                $parsed = parseRate($value);
                if ($parsed === false) {
                    $error = "Format rate limit tidak valid. Contoh: 2 Mbps atau 2M/2M.";
                    break;
                }
                $value = $parsed;
            }

            $tableTarget = in_array($attribute, $checkAttributes, true) ? 'radgroupcheck' : 'radgroupreply';

            $normalizedRows[] = [
                'attribute' => $attribute,
                'op' => $op,
                'value' => $value,
                'table' => $tableTarget
            ];
            $seenAttributes[$attribute] = true;
        }

        if ($error === '') {
            $stmtDelCheck = $conn->prepare("DELETE FROM radgroupcheck WHERE groupname = ?");
            $stmtDelReply = $conn->prepare("DELETE FROM radgroupreply WHERE groupname = ?");
            $stmtInsCheck = $conn->prepare("INSERT INTO radgroupcheck (groupname, attribute, op, value) VALUES (?, ?, ?, ?)");
            $stmtInsReply = $conn->prepare("INSERT INTO radgroupreply (groupname, attribute, op, value) VALUES (?, ?, ?, ?)");

            if (!$stmtDelCheck || !$stmtDelReply || !$stmtInsCheck || !$stmtInsReply) {
                $error = "Gagal menyiapkan query simpan.";
            } else {
                $conn->begin_transaction();
                try {
                    $stmtDelCheck->bind_param("s", $groupname);
                    if (!$stmtDelCheck->execute()) {
                        throw new Exception("Gagal hapus radgroupcheck.");
                    }

                    $stmtDelReply->bind_param("s", $groupname);
                    if (!$stmtDelReply->execute()) {
                        throw new Exception("Gagal hapus radgroupreply.");
                    }

                    foreach ($normalizedRows as $row) {
                        if ($row['table'] === 'radgroupcheck') {
                            $stmtInsCheck->bind_param("ssss", $groupname, $row['attribute'], $row['op'], $row['value']);
                            if (!$stmtInsCheck->execute()) {
                                throw new Exception("Gagal insert radgroupcheck.");
                            }
                        } else {
                            $stmtInsReply->bind_param("ssss", $groupname, $row['attribute'], $row['op'], $row['value']);
                            if (!$stmtInsReply->execute()) {
                                throw new Exception("Gagal insert radgroupreply.");
                            }
                        }
                    }

                    $conn->commit();

                    $stmtDelCheck->close();
                    $stmtDelReply->close();
                    $stmtInsCheck->close();
                    $stmtInsReply->close();

                    header("Location: profile?updated=1");
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    $stmtDelCheck->close();
                    $stmtDelReply->close();
                    $stmtInsCheck->close();
                    $stmtInsReply->close();
                    $error = "Gagal menyimpan perubahan profile.";
                }
            }
        }
    }

    if ($error === '') {
        $attributes = [];
        for ($i = 0; $i < count($postedAttr); $i++) {
            $attributes[] = [
                'attribute' => trim($postedAttr[$i]),
                'op' => trim($postedOp[$i]) === '' ? ':=' : trim($postedOp[$i]),
                'value' => trim($postedVal[$i])
            ];
        }
    } else {
        $attributes = [];
        for ($i = 0; $i < count($postedAttr); $i++) {
            $attributes[] = [
                'attribute' => trim($postedAttr[$i]),
                'op' => trim($postedOp[$i]),
                'value' => trim($postedVal[$i])
            ];
        }
    }
}

$pageTitle = 'Edit Profile';
$navTitle  = 'Edit Profile';

include __DIR__ . "/../views/edit_profile.view.php";
