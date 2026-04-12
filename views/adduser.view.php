<?php include __DIR__ . "/layout/header.php"; ?>
<?php include __DIR__ . "/layout/sidebar.php"; ?>
<?php include __DIR__ . "/layout/navbar.php"; ?>

<div class="content p-4">

    <h3 class="mb-4">Tambah User Active</h3>

    <!-- NOTIF -->
    <?php if (!empty($msg) && is_array($msg)) { ?>
        <div class="alert alert-<?php echo $msg['type']; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($msg['text']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <form method="POST">

        <!-- USERNAME -->
        <div class="mb-3">
            <label>Username</label>
            <input 
                name="username"
                class="form-control"
                required
                value="<?php echo htmlspecialchars($_POST['username'] ?? '') ?>"
                placeholder="contoh: user01 (tanpa spasi)">
        </div>

        <!-- PASSWORD -->
        <div class="mb-3">
            <label>Password</label>
            <input 
                name="password"
                class="form-control"
                required
                value="<?php echo htmlspecialchars($_POST['password'] ?? '') ?>">
        </div>

        <!-- PROFIL -->
        <div class="mb-3">
            <label>Profil</label>

            <select name="profile" class="form-control" required>
                <?php 
                $selectedProfile = $_POST['profile'] ?? '';
                while ($p = $profiles->fetch_assoc()) { 
                ?>
                    <option 
                        value="<?php echo htmlspecialchars($p['groupname']); ?>"
                        <?php if ($selectedProfile == $p['groupname']) echo 'selected'; ?>
                    >
                        <?php echo htmlspecialchars($p['groupname']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <!-- MASA AKTIF -->
        <div class="mb-3">
            <label>Masa Aktif (Hari)</label>
            <input 
                type="number"
                name="hari"
                class="form-control"
                value="<?php echo htmlspecialchars($_POST['hari'] ?? 30) ?>"
                min="1"
                max="3650">
        </div>

        <button name="save" class="btn btn-primary w-100">
            Simpan
        </button>

    </form>

</div>

<?php include __DIR__ . "/layout/footer.php"; ?>