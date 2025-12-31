<?php
session_start();
$host = '127.0.0.1'; //ip do seu banco
$user = 'root'; //nome de usuario do banco
$pass = 'SUASENHA'; // <--- COLOQUE SUA SENHA DO BANCO
$db   = 'l2jmobiusessence'; //nome do data bd do seu servidor

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Erro Fatal: " . $conn->connect_error);

// Função para Criptografia (SHA-1 que seu servidor usa) 
function encryptPass($password) {
    return base64_encode(sha1($password, true));
}
?>