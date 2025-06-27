<?php
// =================== CONFIGURATION ===================
$gridTitle         = "BloodMoon Grid Stats";
$website           = "https://bloodmoonpack.com/grid/";
$loginscreen       = "https://bloodmoonpack.com/grid/";
$robustURL         = "bloodmoonpack.com";
$robustPORT        = "8002";
$loginuri          = "http://".$robustURL.":".$robustPORT;

// Base de données
$host = "localhost";
$user = "dbuser";        // à personnaliser
$pass = "dbpassword";    // à personnaliser
$dbname = "robust";
$mysqli = new mysqli($host, $user, $pass, $dbname);

// Apparence personnalisable
$bgColor          = "#111"; // fond de la page
$textColor        = "#eee"; // texte général
$linkColor        = "#6699ff"; // liens
$accentColor      = "#ff6666"; // couleur des <b>
$fontFamily       = "Arial, sans-serif";

$cardBgColor      = "rgba(255, 255, 255, 0.05)"; // fond carte
$cardTextAlign    = "center";
$cardBorderRadius = "16px";
$cardShadow       = "0 0 20px rgba(0, 0, 0, 0.6)";
$cardMaxWidth     = "600px";
$cardPadding      = "40px";

$landDecimals     = 2;  // km²
$numberDecimals   = 0;  // autres valeurs

// =================== STATS GRID ===================
$socket = @fsockopen($robustURL, $robustPORT, $errno, $errstr, 1);
$gstatus = is_resource($socket) ? "ONLINE" : "OFFLINE";
$color = is_resource($socket) ? "green" : "red";
@fclose($socket);

$monthago = time() - 2592000;

$preshguser = 0;
if ($res = $mysqli->query("SELECT DISTINCT UserID FROM GridUser WHERE Logout > $monthago AND UserID LIKE '%http%'")) {
    $preshguser = $res->num_rows;
}

$pastmonth = 0;
if ($res = $mysqli->query("SELECT DISTINCT UserID FROM GridUser WHERE Logout > $monthago AND UserID NOT LIKE '%http%'")) {
    $pastmonth = $res->num_rows;
}

$nowonlinescounter = 0;
if ($res = $mysqli->query("SELECT UserID FROM Presence")) {
    $nowonlinescounter = $res->num_rows;
}

$totalaccounts = 0;
if ($res = $mysqli->query("SELECT * FROM UserAccounts")) {
    $totalaccounts = $res->num_rows;
}

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
        $totalsize += ($r['sizeX'] * $r['sizeY']) / 1000000; // en km²
    }
}

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

// =================== SORTIES JSON / XML ===================
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
            background-color: <?php echo $cardBgColor; ?>;
            border-radius: <?php echo $cardBorderRadius; ?>;
            box-shadow: <?php echo $cardShadow; ?>;
            padding: <?php echo $cardPadding; ?>;
            max-width: <?php echo $cardMaxWidth; ?>;
            width: 90%;
            text-align: <?php echo $cardTextAlign; ?>;
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
