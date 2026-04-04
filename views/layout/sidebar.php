<?php
$page = basename($_SERVER['PHP_SELF'], '.php');
?>

<div class="sidebar">

    <h4>ZERO NET</h4>
    <a href="index" class="<?= $page == 'index' ? 'active' : '' ?>">Dashboard</a>

    <a href="users" class="<?= $page == 'users' ? 'active' : '' ?>">Pelanggan</a>

    <a href="adduser" class="<?= $page == 'adduser' ? 'active' : '' ?>">Tambah User</a>

    <a href="profile" class="<?= $page == 'profile' ? 'active' : '' ?>">Profile</a>

    <a href="edit_user" class="<?= $page == 'edit_user' ? 'active' : '' ?>">Edit User</a>

    <a href="voucher" class="<?= $page == 'voucher' ? 'active' : '' ?>">Voucher</a>

    <a href="log" class="<?= $page == 'log' ? 'active' : '' ?>">Log</a>

</div>
