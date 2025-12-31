<?php
require 'db.php';
require 'config.php';

if (!isset($_SESSION['user'])) { header("Location: index.php"); exit; }
$user = $_SESSION['user'];

// 1. BUSCAR DADOS DA CONTA
$acc = $conn->query("SELECT * FROM accounts WHERE login='$user'")->fetch_assoc();
$email = isset($acc['email']) ? $acc['email'] : 'Não cadastrado';

// 2. BUSCAR SALDO
$varName = GAME_CURRENCY_VAR;
$gsQuery = $conn->query("SELECT value FROM account_gsdata WHERE account_name='$user' AND var='$varName'");
$balance = ($gsQuery->num_rows > 0) ? $gsQuery->fetch_assoc()['value'] : 0;

// Formatação das Datas
$lastAccessTime = isset($acc['last_access']) ? $acc['last_access'] : (isset($acc['lastAccess']) ? $acc['lastAccess'] : 0);
$lastDate = $lastAccessTime ? date("d/m/Y H:i", $lastAccessTime / 1000) : "Nunca";
if ($lastAccessTime > 0 && $lastAccessTime < 2000000000) $lastDate = date("d/m/Y H:i", $lastAccessTime);

$cTime = isset($acc['created_time']) ? $acc['created_time'] : null;
$createdDateAcc = "Desconhecido";
if ($cTime) {
    $createdDateAcc = is_numeric($cTime) ? date("d/m/Y", $cTime / 1000) : date("d/m/Y", strtotime($cTime));
}

$lastIP = $_SERVER['REMOTE_ADDR'];

// 3. BUSCAR PERSONAGENS
$chars = [];
$res = $conn->query("SELECT * FROM characters WHERE account_name='$user'");
while($r = $res->fetch_assoc()) $chars[] = $r;

// 4. SELECIONAR PERSONAGEM
$selectedChar = null;
if (isset($_GET['select'])) {
    foreach($chars as $c) { if ($c['charId'] == $_GET['select']) { $selectedChar = $c; break; } }
} else { if (count($chars) > 0) $selectedChar = $chars[0]; }

// --- FUNÇÃO DE CLASSES COMPLETA ---
function getClassName($id) {
    $c = [
        0 => 'Human Fighter', 1 => 'Warrior', 2 => 'Gladiator', 3 => 'Warlord', 
        4 => 'Human Knight', 5 => 'Paladin', 6 => 'Dark Avenger', 7 => 'Rogue', 
        8 => 'Treasure Hunter', 9 => 'Hawkeye', 10 => 'Human Mystic', 11 => 'Human Wizard', 
        12 => 'Sorcerer', 13 => 'Necromancer', 14 => 'Warlock', 15 => 'Cleric', 
        16 => 'Bishop', 17 => 'Prophet',
        18 => 'Elven Fighter', 19 => 'Elven Knight', 20 => 'Temple Knight', 21 => 'Sword Singer', 
        22 => 'Elven Scout', 23 => 'Plains Walker', 24 => 'Silver Ranger', 25 => 'Elven Mystic', 
        26 => 'Elven Wizard', 27 => 'Spellsinger', 28 => 'Elemental Summoner', 29 => 'Elven Oracle', 
        30 => 'Elven Elder',
        31 => 'Dark Fighter', 32 => 'Palus Knight', 33 => 'Shillien Knight', 34 => 'Bladedancer', 
        35 => 'Assassin', 36 => 'Abyss Walker', 37 => 'Phantom Ranger', 38 => 'Dark Mystic', 
        39 => 'Dark Wizard', 40 => 'Spellhowler', 41 => 'Phantom Summoner', 42 => 'Shillien Oracle', 
        43 => 'Shillien Elder',
        44 => 'Orc Fighter', 45 => 'Orc Raider', 46 => 'Destroyer', 47 => 'Monk', 
        48 => 'Tyrant', 49 => 'Orc Mystic', 50 => 'Orc Shaman', 51 => 'Overlord', 
        52 => 'Warcryer',
        53 => 'Dwarf Fighter', 54 => 'Scavenger', 55 => 'Bounty Hunter', 56 => 'Artisan', 
        57 => 'Warsmith',
        88 => 'Duelist', 89 => 'Dreadnought', 90 => 'Phoenix Knight', 91 => 'Hell Knight', 
        92 => 'Sagittarius', 93 => 'Adventurer', 94 => 'Archmage', 95 => 'Soultaker', 
        96 => 'Arcana Lord', 97 => 'Cardinal', 98 => 'Hierophant', 99 => "Eva's Templar", 
        100 => 'Sword Muse', 101 => 'Wind Rider', 102 => 'Moonlight Sentinel', 103 => 'Mystic Muse', 
        104 => 'Elemental Master', 105 => "Eva's Saint", 106 => 'Shillien Templar', 107 => 'Spectral Dancer', 
        108 => 'Ghost Hunter', 109 => 'Ghost Sentinel', 110 => 'Storm Screamer', 111 => 'Spectral Master', 
        112 => 'Shillien Saint', 113 => 'Titan', 114 => 'Grand Khavatari', 115 => 'Dominator', 
        116 => 'Doom Cryer', 117 => 'Fortune Seeker', 118 => 'Maestro', 
        125 => 'Trooper', 126 => 'Warden', 127 => 'Beserker', 128 => 'Soul Breaker', 
        129 => 'Soul Breaker', 130 => 'Arbalester', 131 => 'Doombringer', 132 => 'Male Soul Breaker', 
        133 => 'Female Soul Breaker', 134 => 'Trickster', 135 => 'Inspector', 136 => 'Judicator',
        192 => 'Jin Kamael Soldier', 193 => 'Soul Finder', 194 => 'Soul Breaker', 195 => 'Soul Hound',
        196 => 'Death Pilgrim', 197 => 'Death Blade', 198 => 'Death Messenger', 199 => 'Death Knight',
        208 => 'Sylph Gunner', 209 => 'Sharpshooter', 210 => 'Wind Sniper', 211 => 'Storm Blaster',
        217 => 'Orc Lancer', 218 => 'Rider', 219 => 'Dragoon', 220 => 'Vanguard Rider'
    ];
    if ($id >= 221 && $id <= 228) return 'Assassin';
    if ($id >= 236 && $id <= 239) return 'Element Weaver';
    if ($id >= 240 && $id <= 243) return 'Divine Templar';
    if ($id >= 247 && $id <= 250) return 'Warg';
    if ($id >= 251 && $id <= 254) return 'Blood Rose';
    return isset($c[$id]) ? $c[$id] : "ClassID: $id";
}

function getRaceAvatar($race, $sex) {
    $key = $race . '_' . $sex;
    $avatars = [
        '0_0'=>'https://i.imgur.com/IqOJB7P.jpeg', '0_1'=>'https://i.imgur.com/ZYNVeQG.jpeg', 
        '1_0'=>'https://i.imgur.com/o003ySK.jpeg', '1_1'=>'https://l2free.org/wp-content/uploads/2024/01/blog3.jpg',
        '2_0'=>'https://i.imgur.com/nPnlbKG.jpeg', '2_1'=>'https://i.imgur.com/dbBPFx0.jpeg', 
        '3_0'=>'https://i.imgur.com/qQE158N.jpeg', '3_1'=>'https://i.imgur.com/UQF6bPe.jpeg', 
        '4_0'=>'https://i.imgur.com/KPE8zmc.jpeg', '4_1'=>'https://i.imgur.com/9m9gMCd.jpeg', 
        '5_0'=>'https://i.imgur.com/QOKM3JT.jpeg', '5_1'=>'https://i.imgur.com/dZfkble.jpeg', 
    ];
    return isset($avatars[$key]) ? $avatars[$key] : 'https://i.imgur.com/G4fPj1x.png';
}

function formatTime($s) { return floor($s/3600)."h ".floor(($s%3600)/60)."m"; }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - <?php echo $user; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <span style="font-size:11px; color:#666;">Saldo (L-Coins):</span><br>
               <span class="balance-text"><?php echo number_format($balance / COIN_DECIMAL, 2, ',', '.'); ?> P</span>
            </div>
            <div style="display:flex; gap:5px;">
                <button class="btn-purple" style="font-size:10px; padding:5px; margin:0;" onclick="location.href='donate.php'">Adicionar</button>
            </div>
        </div>
        <ul class="menu-list">
            <li class="menu-item active" onclick="location.href='panel.php'"><i class="fa-solid fa-house"></i> Home</li>
            <li class="menu-item" onclick="location.href='donate.php'"><i class="fa-solid fa-money-bill-1"></i> Doações</li>
            <li class="menu-item" onclick="location.href='ranking.php'"><i class="fa-solid fa-trophy"></i> Ranking</li>
            <li class="menu-item" onclick="location.href='shop.php'"><i class="fa-solid fa-shop"></i> Shop</li>
            <li class="menu-item" onclick="location.href='logout.php'"><i class="fa-solid fa-lock"></i> Sair</li>
        </ul>
    </div>

    <div class="panel-content">
        <div class="content-header"><i class="fa-solid fa-house"></i> Home</div>

        <div class="info-grid">
            <div class="info-box">
                <div class="box-title"><i class="fa-solid fa-circle-info"></i> Informações da conta</div>
                <div class="data-row"><span>Account</span> <b><?php echo $user; ?></b></div>
                <div class="data-row"><span>E-mail</span> <b><?php echo $email; ?></b></div>
                <div class="data-row"><span>Criada em</span> <b><?php echo $createdDateAcc; ?></b></div>
                <div class="data-row"><span>Último login</span> <b><?php echo $lastDate; ?></b></div>
                <div class="data-row"><span>Personagens</span> <b><?php echo count($chars); ?></b></div>
            </div>

            <div class="info-box">
                <div class="box-title"><i class="fa-solid fa-clock-rotate-left"></i> Últimos acessos</div>
                <div style="padding:10px; font-weight:bold; font-size:11px; background:#ddd; display:flex; justify-content:space-between;">
                    <span>IP</span> <span>Data</span>
                </div>
                <div class="data-row"><span><?php echo $lastIP; ?></span> <span><?php echo date("d/m/Y H:i"); ?></span></div>
            </div>
        </div>

        <div class="info-box">
            <div class="box-title"><i class="fa-solid fa-users"></i> Personagens</div>
            <div style="padding:15px;">
                <div class="char-list">
                    <?php if(empty($chars)) echo "Nenhum personagem criado."; ?>
                    <?php foreach($chars as $c): ?>
                        <?php 
                            $active = ($selectedChar && $selectedChar['charId'] == $c['charId']) ? 'active' : ''; 
                            $avatarImg = getRaceAvatar($c['race'], $c['sex']);
                        ?>
                        <a href="?select=<?php echo $c['charId']; ?>" class="char-thumb <?php echo $active; ?>">
                            <img src="<?php echo $avatarImg; ?>" title="<?php echo $c['char_name']; ?>">
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if($selectedChar): ?>
                <div class="info-grid" style="border-top:1px solid #eee; padding-top:15px;">
                    <div style="font-size:12px; color:#555;">
                        <div class="data-row"><span>Nome</span> <b><?php echo $selectedChar['char_name']; ?></b></div>
                        <div class="data-row"><span>Título</span> <b><?php echo isset($selectedChar['title']) ? $selectedChar['title'] : '-'; ?></b></div>
                        
                        <div class="data-row"><span>Criado em</span> 
                            <b><?php echo isset($selectedChar['createDate']) ? date("d/m/Y", strtotime($selectedChar['createDate'])) : '-'; ?></b>
                        </div>
                        
                        <div class="data-row"><span>Classe</span> <b><?php echo getClassName($selectedChar['base_class']); ?></b></div>
                        <div class="data-row"><span>Nível</span> <b><?php echo $selectedChar['level']; ?></b></div>
                    </div>
                    <div style="font-size:12px; color:#555;">
                        <div class="data-row"><span>Gênero</span> <b><?php echo $selectedChar['sex']==0?'Masculino':'Feminino'; ?></b></div>
                        <div class="data-row"><span>PvP / PK</span> <b style="color:#c0392b;"><?php echo $selectedChar['pvpkills'].' / '.$selectedChar['pkkills']; ?></b></div>
                        
                        <div class="data-row"><span>Karma</span> <b style="color:#c0392b;"><?php echo isset($selectedChar['karma']) ? $selectedChar['karma'] : 0; ?></b></div>
                        
                        <div class="data-row"><span>Nobre</span> <b><?php echo isset($selectedChar['nobless']) && $selectedChar['nobless'] ?'Sim':'Não'; ?></b></div>
                        <div class="data-row"><span>Tempo Online</span> <b><?php echo formatTime($selectedChar['onlinetime']); ?></b></div>
                        <div class="data-row"><span>Status</span> <b style="color:<?php echo $selectedChar['online']?'green':'red';?>"><?php echo $selectedChar['online']?'Online':'Offline'; ?></b></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>