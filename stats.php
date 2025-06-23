<?php

$website = "https://bloodmoonpack.com/grid/";
$loginscreen = "https://bloodmoonpack.com/grid/";
$robustURL   = "bloodmoonpack.com"; //FQDN or IP to your grid/robust server
$robustPORT = "8002"; //port for your robust
$website = "https://bloodmoonpack.com/grid/";
$loginuri = "http://".$robustURL.":".$robustPORT."";
//your database info
$host = "localhost";
$user = "dbuser";
$pass = "dbpassword";
$dbname = "robust";


// Online / Offline with socket
$socket = @fsockopen($robustURL, $robustPORT, $errno, $errstr, 1);
if (is_resource($socket))
{
$gstatus = "ONLINE";
$color = "green";
}
else {
$gstatus = "OFFLINE";
$color = "red";
}
@fclose($socket);



$mysqli = new mysqli($host,$user,$pass,$dbname);
$presenceuseraccount = 0;

$monthago = time() - 2592000; 

if ($hguser = $mysqli->query("SELECT UserID, Login FROM GridUser WHERE UserID LIKE '%htt%' AND Login < 'time() - 2592000'")) 
		{
			$preshguser= $hguser->num_rows;
		}
	


$nowonlinescounter = 0;
if ($preso = $mysqli->query("SELECT UserID FROM Presence")) {
	$nowonlinescounter = $preso->num_rows;
}
$pastmonth = 0;
if ($tpres = $mysqli->query("SELECT DISTINCT * FROM GridUser WHERE Logout < '".$monthago."'")) {
	$pastmonth = $tpres->num_rows;
}
$totalaccounts = 0;
if ($useraccounts = $mysqli->query("SELECT * FROM UserAccounts")) {
	$totalaccounts = $useraccounts->num_rows;
}
$totalregions = 0;
$totalvarregions = 0;
$totalsingleregions = 0;
$totalsize = 0;
if($regiondb = $mysqli->query("SELECT * FROM regions")) {
	while ($regions = $regiondb->fetch_array()) {
		++$totalregions;
		if ($regions['sizeX'] == 256) {
			++$totalsingleregions;
		}else{
			++$totalvarregions;
		}
		$rsize = $regions['sizeX'] * $regions['sizeY'];
		$totalsize += $rsize / 1000;
	}
}
$arr = ['GridStatus' => '<b><font color="'.$color.'">'.$gstatus.'</b></font>',
	'Online_Now' => number_format($nowonlinescounter),
	'HG_Visitors_Last_30_Days' => number_format($preshguser),
	'Local_Users_Last_30_Days' => number_format($pastmonth),
	'Total_Active_Last_30_Days' => number_format($pastmonth + $preshguser),
	'Registered_Users' => number_format($totalaccounts),
	'Regions' => number_format($totalregions),
	'Var_Regions' => number_format($totalvarregions),
	'Single_Regions' => number_format($totalsingleregions),
	'Total_LandSize(km2)' => number_format($totalsize),
	'Login_URL' => $loginuri,
	'Website' => '<i><a href='.$website.'>'.$website.'</a></i>',
	'Login_Screen' => '<i><a href='.$loginscreen.'>'.$loginscreen.'</a></i>'];
	
if ($_GET['format'] == "json") {
	header('Content-type: application/json');
	echo json_encode($arr);
}else if ($_GET['format'] == "xml") {
	function array2xml($array, $wrap='Stats', $upper=true) {
	    $xml = '';
	    if ($wrap != null) {
	        $xml .= "<$wrap>\n";
	    }
	    foreach ($array as $key=>$value) {
	        if ($upper == true) {
	            $key = strtoupper($key);
	        }
	        $xml .= "<$key>" . htmlspecialchars(trim($value)) . "</$key>";
	    }
	    if ($wrap != null) {
	        $xml .= "\n</$wrap>\n";
	    }
	    return $xml;
	}
	header('Content-type: text/xml');
	print array2xml($arr);
}else{
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