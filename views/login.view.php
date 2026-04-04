<?php
$extraCss = <<<'CSS'
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    .login-page {
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #f4f6f9;
    }

    .login-box {
        width: 360px;
        background: white;
        padding: 35px;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .login-title {
        text-align: center;
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 25px;
    }

    .password-box {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        font-size: 20px;
        color: #6c757d;
        transition: transform .15s;
    }

    .password-toggle:hover {
        transform: translateY(-50%) scale(1.15);
    }

    .error-msg {
        margin-top: 15px;
        color: #dc3545;
        text-align: center;
        font-style: italic;
        font-size: 16px;
        font-weight: 400;
        animation: shake .35s;
        transition: opacity .5s;
    }

    .hide-error {
        opacity: 0;
    }

    @keyframes shake {
        0%   { transform: translateX(0) }
        25%  { transform: translateX(-6px) }
        50%  { transform: translateX(6px) }
        75%  { transform: translateX(-6px) }
        100% { transform: translateX(0) }
    }
</style>
CSS;
?>
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

<?php
$extraJs = <<<'JS'
<script>
    function togglePass() {

        let p = document.getElementById("password");
        let eye = document.getElementById("eyeIcon");

        if (p.type === "password") {

            p.type = "text";
            eye.classList.remove("bi-eye-slash");
            eye.classList.add("bi-eye");

        } else {

            p.type = "password";
            eye.classList.remove("bi-eye");
            eye.classList.add("bi-eye-slash");

        }

    }

    let err = document.getElementById("errorBox");

    if (err) {

        setTimeout(function() {
            err.classList.add("hide-error");
        }, 1200);

    }
</script>
JS;
?>
<?php include __DIR__ . "/layout/footer.php"; ?>
