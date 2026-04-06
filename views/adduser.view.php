<?php include __DIR__ . "/layout/header.php"; ?>

<?php include __DIR__ . "/layout/sidebar.php"; ?>

<?php include __DIR__ . "/layout/navbar.php"; ?>

<div class="content p-4">

    <h3 class="mb-4">Tambah User active</h3>

    <?php if ($msg) { ?>
        <div class="alert alert-info"><?php echo $msg ?></div>
    <?php } ?>

    <form method="POST">

        <!-- USERNAME -->
        <div class="mb-3">
            <label>Username</label>
            <input name="username" class="form-control" required>
        </div>

        <!-- PASSWORD -->
        <div class="mb-3">
            <label>Password</label>
            <input name="password" class="form-control" required>
        </div>

        <!-- PROFIL -->
        <div class="mb-3">
            <label>Profil</label>

            <select name="profile" class="form-control" required>

                <?php while ($p = $profiles->fetch_assoc()) { ?>
                    <option value="<?php echo $p['groupname']; ?>">
                        <?php echo $p['groupname']; ?>
                    </option>
                <?php } ?>

            </select>

        </div>

        <!-- MASA AKTIF -->
        <div class="mb-3">
            <label>Masa Aktif (Hari)</label>
            <input type="number" name="hari" value="30" class="form-control">
        </div>

        <button name="save" class="btn btn-primary w-100">
            Simpan
        </button>

    </form>

</div>

<?php include __DIR__ . "/layout/footer.php"; ?>
