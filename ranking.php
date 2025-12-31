<?php
require 'db.php';
require 'config.php'; 

if (!isset($_SESSION['user'])) { header("Location: index.php"); exit; }
$user = $_SESSION['user'];

// --- BUSCAR SALDO NOVO ---
$varName = GAME_CURRENCY_VAR;
$gsQuery = $conn->query("SELECT value FROM account_gsdata WHERE account_name='$user' AND var='$varName'");
$balance = ($gsQuery->num_rows > 0) ? $gsQuery->fetch_assoc()['value'] : 0;
// -------------------------

// TOP 10 PvP
$sqlPvP = "SELECT char_name, level, pvpkills, pkkills, base_class FROM characters WHERE accesslevel=0 ORDER BY pvpkills DESC LIMIT 10";
$resPvP = $conn->query($sqlPvP);

// TOP 10 PK
$sqlPK = "SELECT char_name, level, pvpkills, pkkills, base_class FROM characters WHERE accesslevel=0 ORDER BY pkkills DESC LIMIT 10";
$resPK = $conn->query($sqlPK);

function getClassName($id) {
    $c = [
        0 => 'Human Fighter', 1 => 'Warrior', 2 => 'Gladiator', 3 => 'Warlord', 
        4 => 'Human Knight', 5 => 'Paladin', 6 => 'Dark Avenger', 7 => 'Rogue', 
        8 => 'Treasure Hunter', 9 => 'Hawkeye', 10 => 'Human Mystic', 11 => 'Human Wizard', 
        12 => 'Sorcerer', 13 => 'Necromancer', 14 => 'Warlock', 15 => 'Cleric', 
        16 => 'Bishop', 17 => 'Prophet', 18 => 'Elven Fighter', 19 => 'Elven Knight', 
        20 => 'Temple Knight', 21 => 'Sword Singer', 22 => 'Elven Scout', 23 => 'Plains Walker', 
        24 => 'Silver Ranger', 25 => 'Elven Mystic', 26 => 'Elven Wizard', 27 => 'Spellsinger', 
        28 => 'Elemental Summoner', 29 => 'Elven Oracle', 30 => 'Elven Elder', 31 => 'Dark Fighter', 
        32 => 'Palus Knight', 33 => 'Shillien Knight', 34 => 'Bladedancer', 35 => 'Assassin', 
        36 => 'Abyss Walker', 37 => 'Phantom Ranger', 38 => 'Dark Mystic', 39 => 'Dark Wizard', 
        40 => 'Spellhowler', 41 => 'Phantom Summoner', 42 => 'Shillien Oracle', 43 => 'Shillien Elder', 
        44 => 'Orc Fighter', 45 => 'Orc Raider', 46 => 'Destroyer', 47 => 'Monk', 
        48 => 'Tyrant', 49 => 'Orc Mystic', 50 => 'Orc Shaman', 51 => 'Overlord', 
        52 => 'Warcryer', 53 => 'Dwarf Fighter', 54 => 'Scavenger', 55 => 'Bounty Hunter', 
        56 => 'Artisan', 57 => 'Warsmith', 
        
        // 3rd Classes
        88 => 'Duelist', 89 => 'Dreadnought', 90 => 'Phoenix Knight', 91 => 'Hell Knight', 
        92 => 'Sagittarius', 93 => 'Adventurer', 94 => 'Archmage', 95 => 'Soultaker', 
        96 => 'Arcana Lord', 97 => 'Cardinal', 98 => 'Hierophant', 99 => "Eva's Templar", 
        100 => 'Sword Muse', 101 => 'Wind Rider', 102 => 'Moonlight Sentinel', 103 => 'Mystic Muse', 
        104 => 'Elemental Master', 105 => "Eva's Saint", 106 => 'Shillien Templar', 107 => 'Spectral Dancer', 
        108 => 'Ghost Hunter', 109 => 'Ghost Sentinel', 110 => 'Storm Screamer', 111 => 'Spectral Master', 
        112 => 'Shillien Saint', 113 => 'Titan', 114 => 'Grand Khavatari', 115 => 'Dominator', 
        116 => 'Doom Cryer', 117 => 'Fortune Seeker', 118 => 'Maestro', 
        
        // Kamael / New Classes
        125 => 'Trooper', 126 => 'Warden', 127 => 'Beserker', 128 => 'Soul Breaker', 129 => 'Berserker',
        130 => 'Arbalester', 131 => 'Doombringer', 134 => 'Trickster', 
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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ranking - <?php echo $user; ?></title>
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
            <li class="menu-item active" onclick="location.href='ranking.php'"><i class="fa-solid fa-trophy"></i> Ranking</li>
            <li class="menu-item" onclick="location.href='shop.php'"><i class="fa-solid fa-shop"></i> Shop</li>
            <li class="menu-item" onclick="location.href='logout.php'"><i class="fa-solid fa-lock"></i> Sair</li>
        </ul>
    </div>

    <div class="panel-content">
        <div class="content-header"><i class="fa-solid fa-trophy"></i> Ranking</div>

        <div class="info-grid">
            <div class="info-box">
                <div class="box-title"><i class="fa-solid fa-khanda"></i> Top 10 PvP</div>
                <div class="data-row" style="background:#eee; font-weight:bold;">
                    <span># Nome</span> <span>PvPs</span>
                </div>
                <?php $i=1; while($row = $resPvP->fetch_assoc()): ?>
                <div class="data-row">
                    <span>
                        <b style="color:#6a1b9a;"><?php echo $i++; ?>.</b> 
                        <?php echo $row['char_name']; ?> 
                        <span style="font-size:10px; color:#999;">(<?php echo getClassName($row['base_class']); ?>)</span>
                    </span>
                    <b style="color:#27ae60;"><?php echo $row['pvpkills']; ?></b>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="info-box">
                <div class="box-title"><i class="fa-solid fa-skull"></i> Top 10 PK</div>
                <div class="data-row" style="background:#eee; font-weight:bold;">
                    <span># Nome</span> <span>PKs</span>
                </div>
                <?php $i=1; while($row = $resPK->fetch_assoc()): ?>
                <div class="data-row">
                    <span>
                        <b style="color:#c0392b;"><?php echo $i++; ?>.</b> 
                        <?php echo $row['char_name']; ?> 
                         <span style="font-size:10px; color:#999;">(<?php echo getClassName($row['base_class']); ?>)</span>
                    </span>
                    <b style="color:#c0392b;"><?php echo $row['pkkills']; ?></b>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>