<?php
// Limpa erros visuais para não quebrar o JSON
error_reporting(0); 
ini_set('display_errors', 0);
ob_start();

require 'db.php';
require 'config.php';

ob_end_clean(); // Limpa buffer
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) { 
    echo json_encode(['error' => 'Sessão expirada.']); 
    exit; 
}

$coins = isset($_POST['coins']) ? intval($_POST['coins']) : 0;
if ($coins <= 0) { 
    echo json_encode(['error' => 'Valor inválido.']); 
    exit; 
}

$amount = $coins * COIN_PRICE;
$user = $_SESSION['user'];
$payerEmail = preg_replace('/[^a-zA-Z0-9]/', '', $user) . "@email.com";

// --- NOVIDADE: GERA A CHAVE ÚNICA (Idempotency Key) ---
$idempotencyKey = uniqid($user . '_', true);
// -----------------------------------------------------

$data = [
    "transaction_amount" => (float)$amount,
    "description" => "$coins Coins - $user",
    "payment_method_id" => "pix",
    "payer" => [
        "email" => $payerEmail,
        "first_name" => $user
    ]
];

$ch = curl_init("https://api.mercadopago.com/v1/payments");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// --- CABEÇALHOS CORRIGIDOS ---
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . MP_ACCESS_TOKEN,
    "X-Idempotency-Key: " . $idempotencyKey  // <--- O Mercado Pago exige isso agora!
]);
// -----------------------------

// Correção SSL Localhost
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'Erro Conexão MP: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);
$mp = json_decode($response, true);

if (isset($mp['id'])) {
    $stmt = $conn->prepare("INSERT INTO donations (account_name, amount, coins_received, status, payment_id) VALUES (?, ?, ?, 'pending', ?)");
    $stmt->bind_param("sdis", $user, $amount, $coins, $mp['id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'payment_id' => $mp['id'],
            'qr_code' => $mp['point_of_interaction']['transaction_data']['qr_code'],
            'qr_base64' => $mp['point_of_interaction']['transaction_data']['qr_code_base64']
        ]);
    } else {
        echo json_encode(['error' => 'Erro ao salvar no banco.']);
    }
} else {
    // Tratamento de erro detalhado
    $msg = isset($mp['message']) ? $mp['message'] : 'Erro desconhecido.';
    if(isset($mp['cause'][0]['description'])) {
        $msg .= " - " . $mp['cause'][0]['description'];
    }
    echo json_encode(['error' => "Mercado Pago recusou: $msg"]);
}
?>