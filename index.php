<?php
require 'db.php';
require 'config.php';
$msg = ""; $msgType = "";

function validarRecaptcha($response) {
    if (empty($response)) return false;
    $url = "https://www.google.com/recaptcha/api/siteverify?secret=".RECAPTCHA_SECRET_KEY."&response=$response&remoteip=".$_SERVER['REMOTE_ADDR'];
    $data = json_decode(file_get_contents($url));
    return $data->success;
}

if (isset($_POST['login_btn'])) {
    $login = $conn->real_escape_string($_POST['login']);
    $pass  = encryptPass($_POST['password']);
    if (!validarRecaptcha($_POST['g-recaptcha-response'])) { $msg = "Valide o Recaptcha."; $msgType = "error"; }
    else {
        $check = $conn->query("SELECT login FROM accounts WHERE login='$login' AND password='$pass'");
        if ($check->num_rows > 0) { $_SESSION['user'] = $login; header("Location: panel.php"); exit; }
        else { $msg = "Dados incorretos."; $msgType = "error"; }
    }
}

if (isset($_POST['register'])) {
    $login = $conn->real_escape_string($_POST['login']);
    $pass  = encryptPass($_POST['password']);
    if (!validarRecaptcha($_POST['g-recaptcha-response'])) { $msg = "Valide o Recaptcha."; $msgType = "error"; }
    else {
        $check = $conn->query("SELECT login FROM accounts WHERE login='$login'");
        if ($check->num_rows > 0) { $msg = "Conta já existe."; $msgType = "error"; }
        else { $conn->query("INSERT INTO accounts (login, password) VALUES ('$login', '$pass')"); $msg = "Criado com sucesso!"; $msgType = "success"; }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - GameAR Style</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

<div style="margin-bottom: 20px;"><img src="https://l2mundo.com/assets/images/logo2025234.png" width="300"></div>

<div class="login-container">
    <div class="login-art"></div>

    <div class="login-form-area">
        <?php if($msg): ?><div class="msg-alert <?php echo $msgType; ?>"><?php echo $msg; ?></div><?php endif; ?>

        <div id="login-box" style="width: 100%;">
            <h2 class="login-title">Painel de Conta</h2>
            <p class="login-subtitle">Para ter acesso ao painel, insira os dados necessários.</p>

            <form method="post">
                <div class="login-input-group">
                    <label class="login-label">Login:</label>
                    <input type="text" name="login" class="login-input" required>
                </div>
                <div class="login-input-group">
                    <label class="login-label">Senha:</label>
                    <input type="password" name="password" class="login-input" required>
                </div>

                <div style="font-size: 11px; text-align: right; margin-bottom: 15px;">
                    <a href="#" style="color: purple; text-decoration: none;">Esqueceu sua senha? <b>Recuperar</b></a>
                </div>

                <div style="margin-bottom: 10px; display:flex; justify-content:center;">
                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>" style="transform:scale(0.85);"></div>
                </div>

                <button type="submit" name="login_btn" class="btn-purple">LOGIN</button>
            </form>
            <button onclick="toggleForm()" class="btn-gray">Não possui conta? <b>Cadastrar</b></button>
        </div>

        <div id="register-box" class="hidden" style="width: 100%;">
            <h2 class="login-title">Criar Conta</h2>
            <p class="login-subtitle">Preencha os dados abaixo.</p>
            <form method="post">
                <div class="login-input-group"><label class="login-label">Usuário:</label><input type="text" name="login" class="login-input" required></div>
                <div class="login-input-group"><label class="login-label">Senha:</label><input type="password" name="password" class="login-input" required></div>
                
                <div style="margin-bottom: 10px; display:flex; justify-content:center;">
                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>" style="transform:scale(0.85);"></div>
                </div>

                <button type="submit" name="register" class="btn-purple">CRIAR CONTA</button>
            </form>
            <button onclick="toggleForm()" class="btn-gray">Voltar ao Login</button>
        </div>
    </div>
</div>

<div style="margin-top: 10px; color: #aaa; font-size: 10px;">© 2025 All Rights Reserved</div>

<script>
function toggleForm() {
    var log = document.getElementById('login-box');
    var reg = document.getElementById('register-box');
    if(log.classList.contains('hidden')) { log.classList.remove('hidden'); reg.classList.add('hidden'); }
    else { log.classList.add('hidden'); reg.classList.remove('hidden'); }
}
</script>
</body>
</html>