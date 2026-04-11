<?php include __DIR__ . "/layout/header.php"; ?>

<?php include __DIR__ . "/layout/sidebar.php"; ?>

<?php include __DIR__ . "/layout/navbar.php"; ?>

<div class="content p-4">

    <!-- NOTIF BERSIH -->
    <?php if (isset($_SESSION['msg'])) { ?>
        <div class="alert alert-<?php echo $_SESSION['msg']['type']; ?>">
            <?php echo $_SESSION['msg']['text']; ?>
        </div>
    <?php unset($_SESSION['msg']);
    } ?>

    <h3>Cari User</h3>

    <form method="GET" class="mb-4">
        <input type="text" name="username" class="form-control mb-2" placeholder="Masukkan username">
        <button name="find" class="btn btn-primary">Cari</button>
    </form>

    <?php if ($user != "") { ?>

        <h3>Edit User: <?php echo $user ?></h3>

        <?php if (isset($current_profile)) { ?>
        <div class="alert alert-info mb-3">
            <strong>Profile:</strong> <?php echo htmlspecialchars($current_profile); ?> 
        </div>
        <?php } ?>

        <form method="POST">

            <input type="hidden" name="user" value="<?php echo $user ?>">

            <div class="mb-3">
                <label>Password</label>
                <input name="password" value="<?php echo $password ?>" class="form-control">
            </div>

            <div class="mb-3">
                <label>Expiration</label>
                <input name="expiration" value="<?php echo $expiration ?>" class="form-control" placeholder="Contoh: 08 May 2026 23:59">

                <small class="form-text text-muted">+ Format wajib : <b>DD MMM    YYYY HH:MM:ss</b>
                    <br>+ Contoh : <b>08 May 2026 23:59:59</b>
                    <br>+ Bulan harus dalam format bahasa Inggris : <b>Jan, Feb, Mar, Apr, May, Jun, Jul, Aug, Sep, Oct, Nov, Dec</b>  
                 </small>
            </div>

            <button name="save" class="btn btn-success">Simpan</button>

        </form>

    <?php } ?>

</div>

<?php include __DIR__ . "/layout/footer.php"; ?>
