<?php
require 'db.php';
require 'config.php'; 

if (!isset($_SESSION['user'])) { header("Location: index.php"); exit; }
$user = $_SESSION['user'];
$msg = ""; $msgType = "";

// 1. BUSCAR SALDO ATUAL
$varName = GAME_CURRENCY_VAR;
$gsQuery = $conn->query("SELECT value FROM account_gsdata WHERE account_name='$user' AND var='$varName'");
$balance = ($gsQuery->num_rows > 0) ? $gsQuery->fetch_assoc()['value'] : 0;

// 2. BUSCAR HISTÓRICO DE COMPRAS (Soma total de packs comprados)
$purchaseHistory = [];
$histQuery = $conn->query("SELECT item_db_id, SUM(count) as total FROM site_shop_history WHERE login='$user' GROUP BY item_db_id");
while($h = $histQuery->fetch_assoc()) {
    $purchaseHistory[$h['item_db_id']] = $h['total'];
}

// Verifica GM
$checkGM = $conn->query("SELECT count(*) as total FROM characters WHERE account_name='$user' AND accesslevel >= 100");
$isGM = ($checkGM->fetch_assoc()['total'] > 0);

// --- ADMIN LOGIC ---
if ($isGM && isset($_POST['save_item'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $desc = $conn->real_escape_string($_POST['desc']);
    $icon = $conn->real_escape_string($_POST['icon']);
    $item_id = intval($_POST['item_id']);
    $count = intval($_POST['count']);
    $price = intval($_POST['price']);
    $stock = intval($_POST['stock']); 
    $limit = intval($_POST['limit']); 
    
    if (!empty($_POST['editing_id'])) {
        $editId = intval($_POST['editing_id']);
        $conn->query("UPDATE site_shop_items SET item_id=$item_id, count=$count, name='$name', description='$desc', price=$price, icon='$icon', stock=$stock, limit_count=$limit WHERE id=$editId");
        $msg = "Item atualizado!";
    } else {
        $conn->query("INSERT INTO site_shop_items (item_id, count, name, description, price, icon, stock, limit_count) VALUES ($item_id, $count, '$name', '$desc', $price, '$icon', $stock, $limit)");
        $msg = "Item criado!";
    }
    $msgType = "success";
}

if ($isGM && isset($_GET['del'])) {
    $delId = intval($_GET['del']);
    $conn->query("DELETE FROM site_shop_items WHERE id=$delId");
    header("Location: shop.php"); exit;
}

// --- BUY LOGIC (Processamento da Compra com Quantidade) ---
if (isset($_POST['buy_id'])) {
    $buyDbId = intval($_POST['buy_id']);
    $charId = intval($_POST['char_id']);
    // Pega a quantidade do input, garantindo que seja pelo menos 1
    $amount = isset($_POST['buy_amount']) ? max(1, intval($_POST['buy_amount'])) : 1;
    
    $itemQuery = $conn->query("SELECT * FROM site_shop_items WHERE id=$buyDbId");
    
    if ($itemQuery->num_rows > 0 && !empty($charId)) {
        $item = $itemQuery->fetch_assoc();
        
        $boughtCount = isset($purchaseHistory[$buyDbId]) ? $purchaseHistory[$buyDbId] : 0;
        
        // CÁLCULOS TOTAIS
        $totalPrice = $item['price'] * $amount;
        $totalItemsToGive = $item['count'] * $amount;

        // VALIDAÇÕES BACKEND
        if ($item['stock'] != -1 && $item['stock'] < $amount) { 
            $msg = "Estoque insuficiente para essa quantidade!"; $msgType = "error"; 
        }
        else if ($item['limit_count'] > 0 && ($boughtCount + $amount) > $item['limit_count']) { 
            $msg = "Você ultrapassaria o limite de compras com essa quantidade!"; $msgType = "error"; 
        }
        else {
            if ($balance >= $totalPrice) {
                $varName = GAME_CURRENCY_VAR;
                
                // 1. Desconta Saldo Total
                $conn->query("UPDATE account_gsdata SET value = value - $totalPrice WHERE account_name='$user' AND var='$varName'");
                
                // 2. Abate Estoque (se não for infinito)
                if ($item['stock'] > 0) {
                    $conn->query("UPDATE site_shop_items SET stock = stock - $amount WHERE id=$buyDbId");
                }
                
                // 3. Registra Histórico (Quantidade de COMPRAS/PACKS, não de itens unitários)
                $conn->query("INSERT INTO site_shop_history (login, item_db_id, count) VALUES ('$user', $buyDbId, $amount)");
                
                // 4. Entrega Item (Multiplica a qtd do pack pela qtd comprada)
                $stmt = $conn->prepare("INSERT INTO items_delayed (owner_id, item_id, count, payment_status, description) VALUES (?, ?, ?, 0, ?)");
                $desc = "Shop: " . $item['name'];
                $stmt->bind_param("iiis", $charId, $item['item_id'], $totalItemsToGive, $desc);
                $stmt->execute();
                
                $msg = "Compra de $amount unidade(s) realizada!"; $msgType = "success";
                
                // Atualiza visual
                $balance -= $totalPrice;
                // Atualiza histórico local
                if(isset($purchaseHistory[$buyDbId])) $purchaseHistory[$buyDbId] += $amount; 
                else $purchaseHistory[$buyDbId] = $amount;

            } else { 
                $msg = "Saldo insuficiente para essa quantidade."; $msgType = "error"; 
            }
        }
    } else { 
        $msg = "Selecione um personagem."; $msgType = "error"; 
    }
}

// Carrega dados
$chars = [];
$res = $conn->query("SELECT charId, char_name FROM characters WHERE account_name='$user'");
while($r = $res->fetch_assoc()) $chars[] = $r;

$shopItems = [];
$resShop = $conn->query("SELECT * FROM site_shop_items ORDER BY id DESC");
while($r = $resShop->fetch_assoc()) $shopItems[] = $r;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Shop - <?php echo $user; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilo para o Input de Quantidade */
        .shop-qty-input {
            width: 60px; padding: 5px; text-align: center; border: 1px solid #ccc; 
            border-radius: 4px; font-weight: bold; margin-right: 5px;
        }
        .shop-action-area {
            display: flex; justify-content: center; align-items: center; margin-top: 10px;
        }
    </style>
    <script>
    // Agora recebe maxQty (o máximo que ele pode comprar baseado em estoque/limite)
    function tryToBuy(itemId, unitPrice, userBalance, maxQty, isLimited) {
        
        // 1. Verifica Personagem
        var charSelect = document.querySelector('select[name="char_id"]');
        if (charSelect.value === "") {
            alert("Por favor, selecione um personagem primeiro.");
            charSelect.focus();
            return;
        }

        // 2. Se estiver bloqueado totalmente
        if (isLimited) {
            alert("Limite atingido ou estoque esgotado.");
            return;
        }

        // 3. Pega a quantidade digitada
        var qtyInput = document.getElementById('qty_' + itemId);
        var amount = parseInt(qtyInput.value);

        if (isNaN(amount) || amount < 1) {
            alert("Digite uma quantidade válida (mínimo 1).");
            return;
        }

        if (amount > maxQty) {
            alert("Você só pode comprar " + maxQty + " unidades deste item no momento (devido a estoque ou limite).");
            qtyInput.value = maxQty;
            return;
        }

        // 4. Calcula Preço Total
        var totalPrice = unitPrice * amount;

        // 5. Verifica Saldo
        if (totalPrice > userBalance) {
            // Ajuste visual para dividir por 100 na mensagem
            var visualPrice = (totalPrice / <?php echo COIN_DECIMAL; ?>).toFixed(2);
            var visualBalance = (userBalance / <?php echo COIN_DECIMAL; ?>).toFixed(2);
            
            alert("Saldo Insuficiente!\n\nTotal: " + visualPrice + " P\nSeu Saldo: " + visualBalance + " P");
            return;
        }

        // Se tudo ok, envia
        document.getElementById('buy_id_input').value = itemId;
        document.getElementById('buy_amount_input').value = amount; // Envia a quantidade
        document.getElementById('shopForm').submit();
    }

    function toggleAdmin() { document.getElementById('adminPanel').classList.toggle('hidden'); }
    
    function editItem(item) {
        document.getElementById('adminPanel').classList.remove('hidden');
        document.getElementById('edit_id').value = item.id;
        document.getElementById('edit_name').value = item.name;
        document.getElementById('edit_desc').value = item.description;
        document.getElementById('edit_iid').value = item.item_id;
        document.getElementById('edit_count').value = item.count;
        document.getElementById('edit_price').value = item.price;
        document.getElementById('edit_icon').value = item.icon;
        document.getElementById('edit_stock').value = item.stock;
        document.getElementById('edit_limit').value = item.limit_count;
    }
    </script>
</head>
<body>

<div class="main-logo">
    <img src="https://l2mundo.com/assets/images/logo2025234.png" alt="Logo">
</div>

<div class="panel-wrapper">
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
            <li class="menu-item" onclick="location.href='donate.php'"><i class="fa-solid fa-money-bill-1"></i> Doações</li>
            <li class="menu-item" onclick="location.href='ranking.php'"><i class="fa-solid fa-chart-simple"></i> Game Stats</li>
            <li class="menu-item active" onclick="location.href='shop.php'"><i class="fa-solid fa-shop"></i> Shop</li>
            <li class="menu-item" onclick="location.href='logout.php'"><i class="fa-solid fa-lock"></i> Sair</li>
        </ul>
    </div>

    <div class="panel-content">
        <div class="content-header">
            <i class="fa-solid fa-shop"></i> Marketplace
            <?php if($isGM): ?><button onclick="toggleAdmin()" style="margin-left:auto; background:none; border:none; color:#6a1b9a; cursor:pointer;"><i class="fa-solid fa-cog"></i> Admin</button><?php endif; ?>
        </div>
        
        <?php if($msg): ?><div class="msg-alert <?php echo $msgType; ?>"><?php echo $msg; ?></div><?php endif; ?>

        <?php if($isGM): ?>
        <div id="adminPanel" class="admin-box hidden">
            <h4 style="margin-top:0; color:#6a1b9a;">Gerenciar Item</h4>
            <form method="post">
                <input type="hidden" name="editing_id" id="edit_id">
                <input type="text" name="name" id="edit_name" placeholder="Nome" class="admin-input" required>
                <input type="text" name="desc" id="edit_desc" placeholder="Descrição" class="admin-input" required>
                <input type="number" name="item_id" id="edit_iid" placeholder="ID Jogo" class="admin-input" required>
                <input type="number" name="count" id="edit_count" placeholder="Qtd" class="admin-input" required>
                <input type="number" name="price" id="edit_price" placeholder="Preço (Inteiro)" class="admin-input" required>
                <input type="text" name="icon" id="edit_icon" placeholder="Ícone ou URL" class="admin-input" required>
                <input type="number" name="stock" id="edit_stock" placeholder="Estoque (-1 infinito)" class="admin-input" required>
                <input type="number" name="limit" id="edit_limit" placeholder="Limite p/ conta (0 sem limite)" class="admin-input" required>
                <button type="submit" name="save_item" class="btn-purple" style="width:100%;">SALVAR</button>
            </form>
        </div>
        <?php endif; ?>

        <form id="shopForm" method="post" style="margin-bottom:10px;">
            <select name="char_id" class="login-input" style="padding:10px;" required>
                <option value="">Selecione o Personagem...</option>
                <?php foreach($chars as $c): ?><option value="<?php echo $c['charId']; ?>"><?php echo $c['char_name']; ?></option><?php endforeach; ?>
            </select>
            <input type="hidden" name="buy_id" id="buy_id_input">
            <input type="hidden" name="buy_amount" id="buy_amount_input" value="1">
        </form>

        <div class="shop-grid">
            <?php foreach($shopItems as $item): ?>
                <?php 
                    $myCount = isset($purchaseHistory[$item['id']]) ? $purchaseHistory[$item['id']] : 0;
                    
                    // --- CALCULA QUANTIDADE MAXIMA PERMITIDA ---
                    // Começa com um número alto (infinito)
                    $maxBuyable = 9999;
                    
                    // Se tiver estoque, o máximo é o estoque
                    if ($item['stock'] != -1) {
                        $maxBuyable = $item['stock'];
                    }
                    
                    // Se tiver limite por conta, verifica quanto resta
                    if ($item['limit_count'] > 0) {
                        $remainingLimit = $item['limit_count'] - $myCount;
                        // O máximo é o menor valor entre Estoque e Limite Restante
                        $maxBuyable = min($maxBuyable, $remainingLimit);
                    }
                    
                    // Se o máximo for 0 ou menor, bloqueia
                    $isBlocked = ($maxBuyable <= 0);
                    
                    // Ajuste visual
                    $btnClass = $isBlocked ? "btn-gray" : "btn-purple";
                    $btnText = $isBlocked ? "INDISPONÍVEL" : "COMPRAR";
                    $btnDisabled = $isBlocked ? "disabled" : "";

                    // Casos especiais de texto
                    if ($item['stock'] == 0) $btnText = "ESGOTADO";
                    elseif ($item['limit_count'] > 0 && $myCount >= $item['limit_count']) $btnText = "LIMITE ATINGIDO";
                ?>
            <div class="shop-card">
                <?php if($isGM): ?>
                    <div style="position:absolute; top:5px; right:5px;">
                        <i class="fa-solid fa-pen" style="color:#f39c12; cursor:pointer;" onclick='editItem(<?php echo json_encode($item); ?>)'></i>
                        <a href="?del=<?php echo $item['id']; ?>" onclick="return confirm('Apagar?');"><i class="fa-solid fa-trash" style="color:#e74c3c;"></i></a>
                    </div>
                <?php endif; ?>
                
                <div class="item-icon">
                    <?php if (strpos($item['icon'], '/') !== false): ?>
                        <img src="<?php echo $item['icon']; ?>" style="width:100%; height:100%; object-fit:contain;">
                    <?php else: ?>
                        <i class="fa-solid <?php echo $item['icon']; ?>"></i>
                    <?php endif; ?>
                </div>
                
                <div class="item-name"><?php echo $item['name']; ?> (x<?php echo $item['count']; ?>)</div>
                <div class="item-desc"><?php echo $item['description']; ?></div>
                
                <?php if($item['limit_count'] > 0): ?>
                    <div style="font-size:10px; color:#555; margin-bottom:5px; background:#eee; padding:2px 5px; border-radius:4px; display:inline-block;">
                        <?php if($myCount >= $item['limit_count']): ?>
                            <span style="color:#c0392b; font-weight:bold;">Máximo Atingido (<?php echo $myCount; ?>/<?php echo $item['limit_count']; ?>)</span>
                        <?php else: ?>
                            <span>Seus: <b><?php echo $myCount; ?></b> / Máx: <b><?php echo $item['limit_count']; ?></b></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="item-price"><?php echo number_format($item['price'] / COIN_DECIMAL, 2, ',', '.'); ?> P</div>
                
                <div class="shop-action-area">
                    <?php if(!$isBlocked): ?>
                        <input type="number" id="qty_<?php echo $item['id']; ?>" class="shop-qty-input" value="1" min="1" max="<?php echo $maxBuyable; ?>">
                    <?php endif; ?>
                    
                    <button class="<?php echo $btnClass; ?>" 
                            style="padding:8px; font-size:11px; flex:1;" 
                            <?php echo $btnDisabled; ?> 
                            onclick="tryToBuy(<?php echo $item['id']; ?>, <?php echo $item['price']; ?>, <?php echo $balance; ?>, <?php echo $maxBuyable; ?>, <?php echo $isBlocked?1:0; ?>)">
                        <?php echo $btnText; ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</body>
</html>