<?php
// =================== CONFIGURATION ===================
$gridTitle      = "BloodMoon Grid Stats";
$website        = "https://bloodmoonpack.com/grid/";
$loginscreen    = "https://bloodmoonpack.com/grid/";
$robustURL      = "bloodmoonpack.com";
$robustPORT     = "8002";
$loginuri       = "http://".$robustURL.":".$robustPORT;

// Base de données
$host = "localhost";
$user = "dbuser";
$pass = "dbpassword";
$dbname = "robust";
$mysqli = new mysqli($host, $user, $pass, $dbname);

// Apparence personnalisable
$bgColor        = "#111";
$textColor      = "#eee";
$linkColor      = "#6699ff";
$accentColor    = "#ff6666";
$fontFamily     = "Arial, sans-serif";
$landDecimals   = 2;
$numberDecimals = 0;

// =================== ÉTAT GRILLE ===================
$socket = @fsockopen($robustURL, $robustPORT, $errno, $errstr, 1);
$gstatus = is_resource($socket) ? "ONLINE" : "OFFLINE";
$color = is_resource($socket) ? "green" : "red";
@fclose($socket);

// =================== STATS ===================
$monthago = time() - 2592000;

// Visiteurs HG actifs
$preshguser = 0;
$sql = "SELECT DISTINCT UserID FROM GridUser WHERE Logout > $monthago AND UserID LIKE '%http%'";
if ($res = $mysqli->query($sql)) {
    $preshguser = $res->num_rows;
}

// Locaux actifs
$pastmonth = 0;
$sql = "SELECT DISTINCT UserID FROM GridUser WHERE Logout > $monthago AND UserID NOT LIKE '%http%'";
if ($res = $mysqli->query($sql)) {
    $pastmonth = $res->num_rows;
}

// En ligne maintenant
$nowonlinescounter = 0;
if ($res = $mysqli->query("SELECT UserID FROM Presence")) {
    $nowonlinescounter = $res->num_rows;
}

// Comptes locaux
$totalaccounts = 0;
if ($res = $mysqli->query("SELECT * FROM UserAccounts")) {
    $totalaccounts = $res->num_rows;
}

// Régions et surface
$totalregions = $totalvarregions = $totalsingleregions = 0;
$totalsize = 0;
if ($res = $mysqli->query("SELECT sizeX, sizeY FROM regions")) {
    while ($r = $res->fetch_assoc()) {
        ++$totalregions;
        if ($r['sizeX'] == 256) {
            ++$totalsingleregions;
        } else {
            ++$totalvarregions;
        }
        $totalsize += ($r['sizeX'] * $r['sizeY']) / 1000000;
    }
}

// =================== DONNÉES À AFFICHER ===================
$arr = [
    'GridStatus'               => '<b><font color="'.$color.'">'.$gstatus.'</font></b>',
    'Online_Now'               => number_format($nowonlinescounter, $numberDecimals),
    'HG_Visitors_Last_30_Days' => number_format($preshguser,        $numberDecimals),
    'Local_Users_Last_30_Days' => number_format($pastmonth,         $numberDecimals),
    'Total_Active_Last_30_Days'=> number_format($pastmonth + $preshguser, $numberDecimals),
    'Registered_Users'         => number_format($totalaccounts,     $numberDecimals),
    'Regions'                  => number_format($totalregions,      $numberDecimals),
    'Var_Regions'              => number_format($totalvarregions,   $numberDecimals),
    'Single_Regions'           => number_format($totalsingleregions,$numberDecimals),
    'Total_LandSize(km2)'      => number_format($totalsize,         $landDecimals),
    'Login_URL'                => $loginuri,
    'Website'                  => '<i><a href="'.$website.'">'.$website.'</a></i>',
    'Login_Screen'             => '<i><a href="'.$loginscreen.'">'.$loginscreen.'</a></i>'
];

// =================== FORMATS ===================
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .card {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.6);
            padding: 40px;
            max-width: 600px;
            width: 90%;
            text-align: center;
            backdrop-filter: blur(4px);
        }
        h1 {
            font-size: 1.8em;
            margin-bottom: 20px;
        }
        p {
            margin: 10px 0;
            font-size: 1.1em;
        }
        b {
            color: <?php echo $accentColor; ?>;
        }
        a {
            color: <?php echo $linkColor; ?>;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>📊 <?php echo htmlspecialchars($gridTitle); ?></h1>
        <?php
        foreach ($arr as $k => $v) {
            echo '<p><b>'.$k.': </b>'.$v.'</p>';
        }
        ?>
    </div>
</body>
</html>

<?php
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
