<?php include __DIR__ . "/layout/header.php"; ?>

<?php include __DIR__ . "/layout/sidebar.php"; ?>

<?php include __DIR__ . "/layout/navbar.php"; ?>

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

<?php
$extraJs = <<<'JS'
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
JS;
?>

<?php include __DIR__ . "/layout/footer.php"; ?>
