<?php
require 'db.php';
require 'config.php';

$id = $_POST['id'];

// Consulta API MP
$ch = curl_init("https://api.mercadopago.com/v1/payments/$id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . MP_ACCESS_TOKEN]);
$response = curl_exec($ch);
curl_close($ch);
$mp = json_decode($response, true);

$status = $mp['status'] ?? 'pending';

if ($status == 'approved') {
    // Verifica duplicidade
    $check = $conn->query("SELECT status, account_name, coins_received FROM donations WHERE payment_id='$id'")->fetch_assoc();
    
    if ($check && $check['status'] != 'approved') {
        $coins = $check['coins_received'] * COIN_DECIMAL; // Garante que é número inteiro
        $acc = $check['account_name'];
        $varName = GAME_CURRENCY_VAR; // Pega do config.php (ex: PRIME_POINTS)

        // 1. Atualiza status da doação
        $conn->query("UPDATE donations SET status='approved' WHERE payment_id='$id'");
        
        // 2. LÓGICA DO GAME SERVER (account_gsdata)
        // Verifica se o registro já existe para essa conta e essa variável
        $checkGS = $conn->query("SELECT value FROM account_gsdata WHERE account_name='$acc' AND var='$varName'");
        
        if ($checkGS->num_rows > 0) {
            // Se já existe, SOMA ao valor atual
            $stmt = $conn->prepare("UPDATE account_gsdata SET value = value + ? WHERE account_name=? AND var=?");
            $stmt->bind_param("iss", $coins, $acc, $varName);
            $stmt->execute();
        } else {
            // Se não existe, CRIA a linha
            $stmt = $conn->prepare("INSERT INTO account_gsdata (account_name, var, value) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $acc, $varName, $coins);
            $stmt->execute();
        }
    }
}

echo $status;
?>