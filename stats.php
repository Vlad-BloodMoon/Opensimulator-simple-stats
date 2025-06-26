<?php

$website = "https://bloodmoonpack.com/grid/";
$loginscreen = "https://bloodmoonpack.com/grid/";
$robustURL   = "bloodmoonpack.com";
$robustPORT  = "8002";
$loginuri = "http://".$robustURL.":".$robustPORT;

// Connexion base de données
$host = "localhost";
$user = "dbuser";
$pass = "dbpassword";
$dbname = "robust";
$mysqli = new mysqli($host, $user, $pass, $dbname);

// Test Online/Offline
$socket = @fsockopen($robustURL, $robustPORT, $errno, $errstr, 1);
if (is_resource($socket)) {
    $gstatus = "ONLINE";
    $color = "green";
} else {
    $gstatus = "OFFLINE";
    $color = "red";
}
@fclose($socket);

// Calcul des utilisateurs actifs sur les 30 derniers jours
$monthago = time() - 2592000; // 30 jours en secondes

// Visiteurs hypergrid actifs
$preshguser = 0;
if ($res = $mysqli->query("SELECT DISTINCT UserID FROM GridUser WHERE Logout > $monthago AND UserID LIKE '%http%'")) {
    $preshguser = $res->num_rows;
}

// Utilisateurs locaux actifs
$pastmonth = 0;
if ($res = $mysqli->query("SELECT DISTINCT UserID FROM GridUser WHERE Logout > $monthago AND UserID NOT LIKE '%http%'")) {
    $pastmonth = $res->num_rows;
}

// Utilisateurs actuellement connectés
$nowonlinescounter = 0;
if ($preso = $mysqli->query("SELECT UserID FROM Presence")) {
    $nowonlinescounter = $preso->num_rows;
}

// Comptes enregistrés
$totalaccounts = 0;
if ($useraccounts = $mysqli->query("SELECT * FROM UserAccounts")) {
    $totalaccounts = $useraccounts->num_rows;
}

// Régions
$totalregions = 0;
$totalvarregions = 0;
$totalsingleregions = 0;
$totalsize = 0;
if ($regiondb = $mysqli->query("SELECT * FROM regions")) {
    while ($regions = $regiondb->fetch_array()) {
        ++$totalregions;
        if ($regions['sizeX'] == 256) {
            ++$totalsingleregions;
        } else {
            ++$totalvarregions;
        }
        $rsize = $regions['sizeX'] * $regions['sizeY'];
        $totalsize += $rsize / 1000000;
    }
}

// Données à afficher
$arr = [
    'GridStatus' => '<b><font color="'.$color.'">'.$gstatus.'</b></font>',
    'Online_Now' => number_format($nowonlinescounter),
    'HG_Visitors_Last_30_Days' => number_format($preshguser),
    'Local_Users_Last_30_Days' => number_format($pastmonth),
    'Total_Active_Last_30_Days' => number_format($pastmonth + $preshguser),
    'Registered_Users' => number_format($totalaccounts),
    'Regions' => number_format($totalregions),
    'Var_Regions' => number_format($totalvarregions),
    'Single_Regions' => number_format($totalsingleregions),
    //'Total_LandSize(km2)' => number_format($totalsize),
    'Total_LandSize(km2)' => number_format($totalsize, 2),
    'Login_URL' => $loginuri,
    'Website' => '<i><a href='.$website.'>'.$website.'</a></i>',
    'Login_Screen' => '<i><a href='.$loginscreen.'>'.$loginscreen.'</a></i>'
];

// Formats d'export
if ($_GET['format'] == "json") {
    header('Content-type: application/json');
    echo json_encode($arr);
} else if ($_GET['format'] == "xml") {
    function array2xml($array, $wrap='Stats', $upper=true) {
        $xml = '';
        if ($wrap != null) $xml .= "<$wrap>\n";
        foreach ($array as $key => $value) {
            if ($upper) $key = strtoupper($key);
            $xml .= "<$key>" . htmlspecialchars(trim($value)) . "</$key>";
        }
        if ($wrap != null) $xml .= "\n</$wrap>\n";
        return $xml;
    }
    header('Content-type: text/xml');
    echo array2xml($arr);
} else {
    // Affichage HTML par défaut
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Stats</title>
        <link rel="icon" href="./img/favicon.ico" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                background: #111;
                color: #eee;
                font-family: Arial, sans-serif;
                padding: 20px;
            }
            b {
                color: #ff6666;
            }
            a {
                color: #6699ff;
            }
        </style>
    </head>
    <body>
        <h1>📊 BloodMoon Grid Stats</h1>
        <?php
        foreach($arr as $k => $v) {
            echo '<p><b>'.$k.': </b>'.$v.'</p>';
        }
        ?>
    </body>
    </html>
    <?php
}
$mysqli->close();
?>
