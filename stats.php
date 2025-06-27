<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/* ========================================================
 *        PARAMÈTRES TECHNIQUES DE LA GRILLE
 * ====================================================== */
$gridTitle   = "BloodMoon Grid Stats";
$website     = "https://bloodmoonpack.com/grid/";
$loginscreen = "https://bloodmoonpack.com/grid/";
$robustURL   = "bloodmoonpack.com";
$robustPORT  = "8002";
$loginuri    = "http://".$robustURL.":".$robustPORT;

/* ---------  Base de données ---------- */
$host   = "localhost";
$user   = "dbuser";
$pass   = "dbpassword";
$dbname = "robust";
$mysqli = new mysqli($host, $user, $pass, $dbname);

/* ========================================================
 *        PARAMÈTRES D’AFFICHAGE (faciles à éditer)
 * ====================================================== */
$bgColor       = "#D3D3D3";        // Couleur de fond
$textColor     = "#00008B";        // Couleur du texte
$linkColor     = "#850606";     // Couleur des liens
$accentColor   = "#000000";     // Couleur des éléments en gras <b>
$fontFamily    = "Arial, sans-serif";
$landDecimals  = 2;             // Décimales pour la surface en km²
$numberDecimals= 0;             // Décimales pour les autres grands nombres

/* ========================================================
 *           RÉCUPÉRATION DES STATS
 * ====================================================== */
/* État de la grille */
$socket = @fsockopen($robustURL, $robustPORT, $errno, $errstr, 1);
if (is_resource($socket)) {
    $gstatus = "ONLINE";
    $color = "green";
} else {
    $gstatus = "OFFLINE";
    $color = "red";
}
@fclose($socket);

/* Période : 30 jours */
$monthago = time() - 2592000;

/* Visiteurs HG actifs */
$preshguser = 0;
$sql = "SELECT DISTINCT UserID
        FROM GridUser
        WHERE Logout > $monthago
          AND UserID LIKE '%http%'";
if ($res = $mysqli->query($sql)) {
    $preshguser = $res->num_rows;
}

/* Utilisateurs locaux actifs */
$pastmonth = 0;
$sql = "SELECT DISTINCT UserID
        FROM GridUser
        WHERE Logout > $monthago
          AND UserID NOT LIKE '%http%'";
if ($res = $mysqli->query($sql)) {
    $pastmonth = $res->num_rows;
}

/* En ligne maintenant */
$nowonlinescounter = 0;
if ($res = $mysqli->query("SELECT UserID FROM Presence")) {
    $nowonlinescounter = $res->num_rows;
}

/* Comptes locaux enregistrés */
$totalaccounts = 0;
if ($res = $mysqli->query("SELECT * FROM UserAccounts")) {
    $totalaccounts = $res->num_rows;
}

/* Régions et surface */
$totalregions = $totalvarregions = $totalsingleregions = 0;
$totalsize = 0;
if ($regiondb = $mysqli->query("SELECT sizeX, sizeY FROM regions")) {
    while ($r = $regiondb->fetch_assoc()) {
        ++$totalregions;
        if ($r['sizeX'] == 256) {
            ++$totalsingleregions;
        } else {
            ++$totalvarregions;
        }
        $totalsize += ($r['sizeX'] * $r['sizeY']) / 1_000_000; // km²
    }
}

/* Tableau final */
$arr = [
    'GridStatus'               => '<b><font color="'.$color.'">'.$gstatus.'</font></b>',
    'Online_Now'               => number_format($nowonlinescounter, $numberDecimals),
    'HG_Visitors_Last_30_Days'  => number_format($preshguser,        $numberDecimals),
    'Local_Users_Last_30_Days'  => number_format($pastmonth,         $numberDecimals),
    'Total_Active_Last_30_Days' => number_format($pastmonth + $preshguser, $numberDecimals),
    'Registered_Users'         => number_format($totalaccounts,     $numberDecimals),
    'Regions'                  => number_format($totalregions,      $numberDecimals),
    'Var_Regions'              => number_format($totalvarregions,   $numberDecimals),
    'Single_Regions'           => number_format($totalsingleregions,$numberDecimals),
    'Total_LandSize(km2)'      => number_format($totalsize,         $landDecimals),
    'Login_URL'                => $loginuri,
    'Website'                  => '<i><a href="'.$website.'">'.$website.'</a></i>',
    'Login_Screen'             => '<i><a href="'.$loginscreen.'">'.$loginscreen.'</a></i>'
];

/* ========================================================
 *      SORTIE : JSON, XML ou HTML
 * ====================================================== */
if (isset($_GET['format']) && $_GET['format'] === "json") {
    header('Content-type: application/json');
    echo json_encode($arr);
    exit;
}

if (isset($_GET['format']) && $_GET['format'] === "xml") {
    header('Content-type: text/xml');
    echo array2xml($arr);
    exit;
}

/* ----------  Page HTML par défaut ---------- */
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
            background: <?php echo $bgColor; ?>;
            color: <?php echo $textColor; ?>;
            font-family: <?php echo $fontFamily; ?>;
            padding: 20px;
        }
        b {
            color: <?php echo $accentColor; ?>;
        }
        a {
            color: <?php echo $linkColor; ?>;
        }
     body {
     background: <?php echo $bgColor; ?>;
     color: <?php echo $textColor; ?>;
     font-family: <?php echo $fontFamily; ?>;
     padding: 20px;
     display: flex;
     flex-direction: column;
     align-items: center;
     justify-content: flex-start;
     min-height: 100vh;
     text-align: center;
      }
    </style>
</head>
<body>
    <h1>📊 <?php echo htmlspecialchars($gridTitle); ?></h1>
    <?php
    foreach ($arr as $k => $v) {
        echo '<p><b>'.$k.': </b>'.$v.'</p>';
    }
    ?>
</body>
</html>
<?php
/* ========================================================
 *             Fonctions utilitaires
 * ====================================================== */
function array2xml($array, $wrap='Stats', $upper=true) {
    $xml = ($wrap !== null) ? "<$wrap>\n" : '';
    foreach ($array as $key => $value) {
        $tag = $upper ? strtoupper($key) : $key;
        $xml .= "<$tag>" . htmlspecialchars(trim($value)) . "</$tag>";
    }
    $xml .= ($wrap !== null) ? "\n</$wrap>" : '';
    return $xml;
}

$mysqli->close();
?>

