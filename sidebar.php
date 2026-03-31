<?php
$page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">

    <h4>ZERO NET</h4>
    <a href="index.php" class="<?= $page == 'index.php' ? 'active' : '' ?>">Dashboard</a>

    <a href="users.php" class="<?= $page == 'users.php' ? 'active' : '' ?>">Pelanggan</a>

    <a href="adduser.php" class="<?= $page == 'adduser.php' ? 'active' : '' ?>">Tambah User</a>

    <a href="profile.php" class="<?= $page == 'profile.php' ? 'active' : '' ?>">Profile</a>

    <a href="edit_user.php" class="<?= $page == 'edit_user.php' ? 'active' : '' ?>">Edit User</a>

    <a href="log.php" class="<?= $page == 'log.php' ? 'active' : '' ?>">Log</a>

</div>