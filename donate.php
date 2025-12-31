<?php
require 'db.php';
require 'config.php';

if (!isset($_SESSION['user'])) { header("Location: index.php"); exit; }
$user = $_SESSION['user'];

// BUSCA SALDO NOVO
$varName = GAME_CURRENCY_VAR;
$gsData = $conn->query("SELECT value FROM account_gsdata WHERE account_name='$user' AND var='$varName'");
$balance = ($gsData->num_rows > 0) ? $gsData->fetch_assoc()['value'] : 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Doar - <?php echo $user; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .donate-terms-box {
            width: 100%; height: 150px; overflow-y: scroll; 
            background: #e9e9e9; border: 1px solid #ccc; 
            padding: 15px; font-size: 12px; color: #555; 
            margin-bottom: 15px; border-radius: 4px;
            text-align: justify;
        }
        .donate-label { font-weight: bold; font-size: 13px; color: #333; display: block; margin-bottom: 5px; }
        .donate-checkbox-wrapper { display: flex; align-items: center; gap: 8px; margin-bottom: 25px; font-size: 13px; font-weight: bold; }
        .payment-table {
            width: 100%; border-collapse: collapse; margin-bottom: 20px;
            background: #fdfdfd; border: 1px solid #ddd;
        }
        .payment-table th {
            background: #e0d4e3; color: #333; text-align: left; 
            padding: 10px; font-size: 13px; border-bottom: 1px solid #ccc;
        }
        .payment-table td { padding: 15px; border-right: 1px solid #eee; }
        .payment-table td:last-child { border-right: none; }
        .donate-select {
            width: 100%; padding: 8px; border: 2px solid #333; border-radius: 4px; font-weight: bold;
        }
        .total-price { color: #27ae60; font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>

<div class="main-logo">
    <img src="https://l2mundo.com/assets/images/logo2025234.png" alt="Logo">
</div>

<div class="panel-wrapper">
    <div class="flags">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a4/Flag_of_the_United_States.svg/20px-Flag_of_the_United_States.svg.png">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/05/Flag_of_Brazil.svg/20px-Flag_of_Brazil.svg.png">
    </div>

    <div class="panel-sidebar">
        <div class="user-card">
            <div class="user-header"><span><i class="fa-solid fa-user"></i> <?php echo $user; ?></span></div>
            <div class="balance-box">
                <span style="font-size:11px; color:#666;">Saldo:</span><br>
                <span class="balance-text"><?php echo number_format($balance / COIN_DECIMAL, 2, ',', '.'); ?> P</span>
            </div>
            <div style="display:flex; gap:5px;">
                <button class="btn-purple" style="font-size:10px; padding:5px; margin:0;" onclick="location.href='donate.php'">Adicionar</button>
            </div>
        </div>
        <ul class="menu-list">
            <li class="menu-item" onclick="location.href='panel.php'"><i class="fa-solid fa-house"></i> Home</li>
            <li class="menu-item active" onclick="location.href='donate.php'"><i class="fa-solid fa-money-bill-1"></i> Doações</li>
            <li class="menu-item" onclick="location.href='ranking.php'"><i class="fa-solid fa-trophy"></i> Ranking</li>
            <li class="menu-item" onclick="location.href='shop.php'"><i class="fa-solid fa-shop"></i> Shop</li>
            <li class="menu-item" onclick="location.href='logout.php'"><i class="fa-solid fa-lock"></i> Sair</li>
        </ul>
    </div>

    <div class="panel-content">
        <h2 style="font-size: 18px; margin-bottom: 10px; color: #333;">Adquirir Coins</h2>
        
        <p style="font-size: 12px; color: #666; margin-bottom: 15px; line-height: 1.5;">
            Ajude o servidor a se manter online e receba recompensas em L-Coins/N-Coins. <br>
            A entrega é <b>Automática</b> e creditada diretamente na sua conta do jogo.
        </p>

        <div class="donate-terms-box">
            <h4 style="margin-top:0;">Termos de Doação</h4>
            <p>1. Doações não são reembolsáveis.</p>
            <p>2. A entrega é realizada automaticamente após a confirmação do gateway de pagamento.</p>
            <p>3. Pontos (Coins) são entregues na conta do site e do jogo simultaneamente.</p>
            <p>4. Em caso de problemas, contate o suporte.</p>
        </div>

        <div class="donate-checkbox-wrapper">
            <input type="checkbox" id="acceptTerms" onchange="toggleButton()">
            <label for="acceptTerms" style="cursor:pointer;">Eu li e aceito os Termos.</label>
        </div>

        <table class="payment-table">
            <thead>
                <tr>
                    <th style="width: 30%;"><i class="fa-solid fa-credit-card"></i> Método</th>
                    <th style="width: 50%;"><i class="fa-solid fa-cubes"></i> Pacote</th>
                    <th style="width: 20%;"><i class="fa-solid fa-dollar-sign"></i> Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select class="donate-select" disabled>
                            <option>MercadoPago (PIX Auto)</option>
                        </select>
                    </td>
                    <td>
                        <select id="coinSelect" class="donate-select">
                            <option value="0">Selecione</option>
                            <?php 
                                for($i=10; $i<=200; $i+=10) echo "<option value='$i'>$i Coins</option>";
                                echo "<option value='300'>300 Coins</option>";
                                echo "<option value='500'>500 Coins</option>";
                                echo "<option value='1000'>1000 Coins</option>";
                            ?>
                        </select>
                    </td>
                    <td style="text-align:center; background:#f9f2f9;">
                        <span id="totalDisplay" class="total-price">R$ 0,00</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="text-align: center;">
            <button id="btnOrder" class="btn-purple" style="width: 200px; background: #5e0b75;" disabled onclick="processOrder()">GERAR PIX</button>
        </div>

        <div id="qrModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:999; align-items:center; justify-content:center;">
            <div style="background:#fff; padding:30px; border-radius:10px; width:400px; text-align:center;">
                <h3 style="color:#5e0b75; margin-top:0;">Pagamento PIX</h3>
                <p>Escaneie o QR Code abaixo para pagar.</p>
                <img id="qrImage" src="" style="width:200px; height:200px; margin:10px 0;">
                <br>
                <textarea id="copyPaste" style="width:100%; height:60px; font-size:10px; margin-bottom:10px;"></textarea>
                <button class="btn-purple" onclick="copyCode()">Copiar Código</button>
                <button class="btn-gray" onclick="location.reload()">Fechar</button>
                <div id="statusCheck" style="margin-top:10px; font-weight:bold; color:#27ae60;">Aguardando pagamento...</div>
            </div>
        </div>

    </div>
</div>

<script>
    const coinPrice = <?php echo COIN_PRICE; ?>;

    $('#coinSelect').change(function() {
        let coins = parseInt($(this).val());
        if(coins > 0) {
            let total = coins * coinPrice;
            $('#totalDisplay').text('R$ ' + total.toFixed(2).replace('.', ','));
        } else {
            $('#totalDisplay').text('R$ 0,00');
        }
        toggleButton();
    });

    function toggleButton() {
        let accepted = $('#acceptTerms').is(':checked');
        let val = parseInt($('#coinSelect').val());
        $('#btnOrder').prop('disabled', !(accepted && val > 0));
    }

    function processOrder() {
        let coins = $('#coinSelect').val();
        let btn = $('#btnOrder');
        
        btn.prop('disabled', true).text('Processando...');

        $.post('api_pix.php', { coins: coins }, function(data) {
            try {
                if(data.error) {
                    alert(data.error);
                    btn.prop('disabled', false).text('GERAR PIX');
                } else {
                    $('#qrModal').css('display', 'flex');
                    $('#qrImage').attr('src', 'data:image/jpeg;base64,' + data.qr_base64);
                    $('#copyPaste').val(data.qr_code);
                    startPolling(data.payment_id);
                }
            } catch(e) {
                alert("Erro ao processar resposta.");
                btn.prop('disabled', false).text('TENTAR NOVAMENTE');
            }
        });
    }

    function copyCode() {
        let copyText = document.getElementById("copyPaste");
        copyText.select();
        document.execCommand("copy");
        alert("Código PIX Copiado!");
    }

    function startPolling(paymentId) {
        let interval = setInterval(function() {
            $.post('check_status.php', { id: paymentId }, function(resp) {
                if(resp.trim() == 'approved') {
                    clearInterval(interval);
                    $('#statusCheck').html('PAGAMENTO APROVADO!<br>Redirecionando...');
                    setTimeout(() => location.href = "panel.php", 2000);
                }
            });
        }, 5000);
    }
</script>
</body>
</html>