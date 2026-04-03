<?php
require "auth.php";
require "config/db.php";

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

function parseDuration(string $input)
{
    $value = trim(strtolower($input));

    if (preg_match('/^(\d+)$/', $value, $m)) {
        return (string)$m[1];
    }

    if (preg_match('/^(\d+)\s*(detik|second|seconds|sec|s)$/', $value, $m)) {
        return (string)$m[1];
    }

    if (preg_match('/^(\d+)\s*(menit|minute|minutes|min|m)$/', $value, $m)) {
        return (string)($m[1] * 60);
    }

    if (preg_match('/^(\d+)\s*(jam|hour|hours|h)$/', $value, $m)) {
        return (string)($m[1] * 3600);
    }

    if (preg_match('/^(\d+)\s*(hari|day|days|d)$/', $value, $m)) {
        return (string)($m[1] * 86400);
    }

    return false;
}

function parseExpiration(string $input)
{
    $value = trim($input);
    if ($value === '') {
        return false;
    }

    if (preg_match('/^\+(\d+)\s*(hari|day|days)$/i', $value, $m)) {
        $days = (int)$m[1];
        if ($days <= 0) {
            return false;
        }
        return date('d M Y', strtotime("+{$days} days"));
    }

    if (preg_match('/^\+(\d+)\s*(jam|hour|hours)$/i', $value, $m)) {
        $hours = (int)$m[1];
        if ($hours <= 0) {
            return false;
        }
        return date('d M Y H:i:s', strtotime("+{$hours} hours"));
    }

    $timestamp = strtotime($value);
    if ($timestamp !== false) {
        return date('d M Y H:i:s', $timestamp);
    }

    return false;
}

function parseRate(string $input)
{
    $value = trim(strtolower($input));
    if ($value === '') {
        return false;
    }

    if (preg_match('/^(\d+)\s*mbps$/i', $value, $m)) {
        return $m[1] . "M/" . $m[1] . "M";
    }

    if (preg_match('/^(\d+)\s*kbps$/i', $value, $m)) {
        return $m[1] . "k/" . $m[1] . "k";
    }

    if (preg_match('/^\d+[kKmMgG]\/\d+[kKmMgG]$/', $input)) {
        return $input;
    }

    return false;
}

function valuePlaceholder(string $attribute): string
{
    if ($attribute === 'Session-Timeout' || $attribute === 'Max-All-Session') {
        return '4 jam / 1 hari';
    }
    if ($attribute === 'Expiration') {
        return '+7 hari';
    }
    if ($attribute === 'Mikrotik-Rate-Limit') {
        return '2 Mbps';
    }
    return 'Isi value';
}

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

                    header("Location: profile.php?updated=1");
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
?>
<!DOCTYPE html>
<html>

<head>
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>

<body>

    <?php include "sidebar.php"; ?>

    <nav class="navbar navbar-light bg-white shadow-sm px-4 d-flex justify-content-between">
        <b>Edit Profile</b>
        <a href="logout.php" class="btn btn-danger d-flex align-items-center gap-2">
            Logout
        </a>
    </nav>

    <div class="content p-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Edit Profile: <?php echo htmlspecialchars($groupname); ?></h5>

                <?php if ($error !== '') { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php } ?>

                <?php if ($error === '' && !empty($attributes)) { ?>
                    <form method="POST" id="formEditProfile">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="attributesTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Attribute</th>
                                        <th>Op</th>
                                        <th>Value</th>
                                        <th width="120">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attributes as $i => $attr) { ?>
                                        <tr>
                                            <td>
                                                <select name="attribute[]" class="form-select attribute-select" required>
                                                    <option value="">-- Pilih Attribute --</option>
                                                    <?php foreach ($attributeWhitelist as $aw) { ?>
                                                        <option value="<?php echo htmlspecialchars($aw); ?>"
                                                            <?php echo $attr['attribute'] === $aw ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($aw); ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="op[]" class="form-control"
                                                    value="<?php echo htmlspecialchars($attr['op']); ?>" placeholder=":=" required>
                                            </td>
                                            <td>
                                                <input type="text" name="value[]" class="form-control value-input"
                                                    value="<?php echo htmlspecialchars($attr['value']); ?>"
                                                    placeholder="<?php echo htmlspecialchars(valuePlaceholder($attr['attribute'])); ?>" required>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm btn-remove-row">Hapus Baris</button>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-outline-primary" id="btnAddRow">Tambah Baris</button>
                        <button type="submit" class="btn btn-primary">SIMPAN</button>
                        <a href="profile.php" class="btn btn-secondary">Kembali</a>
                    </form>
                <?php } else { ?>
                    <a href="profile.php" class="btn btn-secondary">Kembali</a>
                <?php } ?>
            </div>
        </div>
    </div>

    <template id="rowTemplate">
        <tr>
            <td>
                <select name="attribute[]" class="form-select attribute-select" required>
                    <option value="">-- Pilih Attribute --</option>
                    <?php foreach ($attributeWhitelist as $aw) { ?>
                        <option value="<?php echo htmlspecialchars($aw); ?>"><?php echo htmlspecialchars($aw); ?></option>
                    <?php } ?>
                </select>
            </td>
            <td>
                <input type="text" name="op[]" class="form-control" value=":=" placeholder=":=" required>
            </td>
            <td>
                <input type="text" name="value[]" class="form-control value-input" placeholder="Isi value" required>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm btn-remove-row">Hapus Baris</button>
            </td>
        </tr>
    </template>

    <script>
        function placeholderByAttribute(attribute) {
            if (attribute === 'Session-Timeout' || attribute === 'Max-All-Session') {
                return '4 jam / 1 hari';
            }
            if (attribute === 'Expiration') {
                return '+7 hari';
            }
            if (attribute === 'Mikrotik-Rate-Limit') {
                return '2 Mbps';
            }
            return 'Isi value';
        }

        function bindRowEvents(row) {
            const removeBtn = row.querySelector('.btn-remove-row');
            const select = row.querySelector('.attribute-select');
            const valueInput = row.querySelector('.value-input');

            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    const tbody = document.querySelector('#attributesTable tbody');
                    if (tbody.querySelectorAll('tr').length > 1) {
                        row.remove();
                    }
                });
            }

            if (select && valueInput) {
                select.addEventListener('change', function() {
                    valueInput.placeholder = placeholderByAttribute(select.value);
                });
                valueInput.placeholder = placeholderByAttribute(select.value);
            }
        }

        document.querySelectorAll('#attributesTable tbody tr').forEach(function(row) {
            bindRowEvents(row);
        });

        document.getElementById('btnAddRow')?.addEventListener('click', function() {
            const tpl = document.getElementById('rowTemplate');
            const clone = tpl.content.firstElementChild.cloneNode(true);
            document.querySelector('#attributesTable tbody').appendChild(clone);
            bindRowEvents(clone);
        });
    </script>
</body>

</html>
