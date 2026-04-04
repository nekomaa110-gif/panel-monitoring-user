<?php include __DIR__ . "/layout/header.php"; ?>

<div class="login-page">

    <div class="login-box">

        <div class="login-title">
            ZERO.Net PANEL
        </div>

        <form method="POST">

            <input class="form-control mb-3"
                name="user"
                placeholder="Username"
                required>

            <div class="password-box mb-3">

                <input id="password"
                    class="form-control"
                    type="password"
                    name="pass"
                    placeholder="Password"
                    required>

                <i id="eyeIcon"
                    class="bi bi-eye-slash password-toggle"
                    onclick="togglePass()"></i>

            </div>

            <button class="btn btn-primary w-100"
                name="login">
                Login </button>

        </form>

        <?php if ($error) { ?>

            <div id="errorBox" class="error-msg">
                <?php echo $error ?>
            </div>

        <?php } ?>

    </div>

</div>

<?php include __DIR__ . "/layout/footer.php"; ?>
