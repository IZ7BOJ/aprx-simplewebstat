<?php
/******************************************************************************************
This file is a part of SIMPLE WEB STATICSTICS GENERATOR FROM APRX LOG FILE
It's very simple and small APRX statictics generator in PHP. It's parted to smaller files and they will work independent from each other (but you always need chgif.php).
This script may have a lot of bugs, problems and it's written in very non-efficient way without a lot of good programming rules. But it works for me.
Author: Peter SQ8VPS, sq8vps[--at--]gmail.com & Alfredo IZ7BOJ
You can modify this program, but please give a credit to original author. Program is free for non-commercial use only.
(C) Peter SQ8VPS & Alfredo IZ7BOJ 2017-2018

*******************************************************************************************/

include 'config.php';
include 'common.php';
include 'functions.php';

logexists();
session_start();
if(!isset($_SESSION['if'])) //if not in session
{
	header('Refresh: 0; url=chgif.php?chgif=1'); //go to the interface change page
	die();
}
$call = $_SESSION['call'];
$callraw = $_SESSION['if'];
$lang = $_SESSION['lang'];


$posframefound = 0; //true if position frame of the station already found
$otherframefound = 0; //true if any other frame of the station already found
$scall = "";
$noofframes = 0;

$posdate = "";
$postime = "";

$posframe = "";

$lastpath = "";

$symboltab = "";
$symbol = "";

$comment = "";
$status = "";

$distance = 0;
$bearing = 0;

$mice = 0;

$declat = 0;
$declon = 0;

$tocall = "";

if(isset($_GET['getcall']) && ($_GET['getcall'] != ""))
{
	$scall = strtoupper($_GET['getcall']);
	$logfile = file($logpath); //read log file
	$linesinlog = count($logfile);
	$lines = $linesinlog - 1;
	while ($lines > 0) { //read line by line but starting from the newest frame!
		$line = $logfile[$lines];
		if((strpos($line, $callraw." R ")!== false)OR(strpos($line, $callraw." d ")!== false)) {
		frameparse($line);
		}
		$lines--;
	}
device();
}

if($lang == "en")
{
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="Description" content="APRX statistics" />
<meta name="Keywords" content="" />
<meta name="Author" content="SQ8VPS" />
<title>APRX statistics - stations' info</title>
</head>
<body>
<?php
if(file_exists($logourl)){
?>
<center><img src="<?php echo $logourl ?>" width="100px" height="100px" align="middle"></center><br>
<?php
}
?>
<center><font size="20"><b>APRX statistics</b></font>
<h2>for interface <font color="red"><b><?php echo $call; ?></b></font> - station's details</h2> <a href="chgif.php?chgif=1">Change interface</a>
<br>
<br><b>Show:</b> <a href="summary.php">Summary (main)</a> - <a href="frames.php">RAW frames from specified station</a> - <a href="details.php">Details of a specified station</a><br><br>
<hr>
</center>
<br>

<form action="details.php" method="get">
Show details of station: <input type="text" name="getcall" <?php if(isset($_GET['getcall'])) echo 'value="'.$_GET['getcall'].'"'; ?>>
<input type="submit" value="Show">
</form>
<br>

<?php
if(isset($_GET['getcall']) && ($_GET['getcall'] != ""))
{
	if($posframefound == 0)
	{
		echo '<font size="6">No frames found for station <b>'.$scall.'</b>.</font>';
	}
	else
	{
		echo '<b><font color="blue" size="8">'.$scall.'</font></b>';
		echo '<br><a href="https://aprs.fi/?call='.$scall.'" target="_blank">Show on aprs.fi</a><br><br>';
		echo '<b>Frames heard: </b><a href="frames.php?getcall='.$_GET['getcall'].'" target="_blank">'.$noofframes.'</a><br>';
		echo '<br><br><font color="blue"><b>Last position frame heard:</b> '.$posdate.' '.$postime.' GMT (';
		$dc = time() -date('Z') - strtotime($posdate.' '.$postime);
		echo (int)($dc / 86400).'d '.(int)(($dc % 86400) / 3600).'h '.(int)(($dc % 3600) / 60).'m '.(int)($dc % 60).'s ago)</font>';
		if ($frame_type !== 'Station Capabilities' and $frame_type !=='Status' and $frame_type !== 'Bulletin' and $frame_type !== 'Announcement' and $frame_type !== 'Message' and $frame_type !== 'OTHER/UNKNOWN' and $declat !== 0.0 and $declon !== 0.0) {
			echo '<br><font color="red"><b>Station position: </b>'.$declat.'°, '.$declon.'° - <b>'.$distance.' km '.$bearing.'° from your location</b></font>';
		}
		echo '<br><font color="green"><b>Frame comment: </b>'.$comment.'</font>';
		echo '<br><br><b>Frame type: </b>'.$frame_type;
		if ($station_type !== false) {
			echo '<br><b>Station type:</b>'.$station_type;
		}
		if (($station_class !== false) and ($station_class !== ' ')) {
                        echo '<br><b>Station class:</b>'.$station_class;
                }
		echo '<br><b>Station symbol:</b> '.$symbol;
		echo '<br><b>Frame path:</b> '.$scall.'>'.$lastpath;
		echo '<br><b>Device:</b> '.$device;
		if ($alt !== false) {
                        echo '<br><b>Altitude: </b>'.$alt.'Km';
                }
		if ($speed !== false) {
                        echo '<br><b>Speed: </b>'.$speed.'Km/h';
                }
		if ($course !== false) {
                        echo '<br><b>Course: </b>'.$course.'°';
                }
		if ($telem !== false) {
                        echo '<br><b>Telemetry: </b>'.$telem;
                }

	}
}

}else { //Polski language
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="Description" content="Statystyki APRX" />
<meta name="Keywords" content="" />
<meta name="Author" content="SQ8VPS" />
<title>Statystyki APRX - informacje o stacjach</title>
</head>
<body>
<?php
if(file_exists($logourl)){
?>
<center><img src="<?php echo $logourl ?>" width="100px" height="100px" align="middle"></center><br>
<?php
}
?>
<center><font size="20"><b>Statystyki APRX</b></font>
<h2>dla interfejsu <font color="red"><b><?php echo $call; ?></b></font> - szczegóły stacji</h2> <a href="chgif.php?chgif=1">Zmień interfejs</a>
<br>
<br><b>Pokaż:</b> <a href="summary.php">Podsumowanie (główna)</a> - <a href="frames.php">Surowe ramki wybranej stacji</a> - <a href="details.php">Szczegóły wybranej stacji</a><br><br>
<hr>
</center>
<br>
<form action="details.php" method="get">
Pokaż szczegóły stacji: <input type="text" name="getcall" <?php if(isset($_GET['getcall'])) echo 'value="'.$_GET['getcall'].'"'; ?>>
<input type="submit" value="Pokaż">
</form>
<br>





<?php

if(isset($_GET['getcall']) && ($_GET['getcall'] != ""))
{
	if ($posframefound == 0)
	{
		echo '<font size="6">Brak odebranych ramek od stacji <b>'.$scall.'</b>.</font>';
	}
	else
	{
		echo '<b><font color="blue" size="8">'.$scall.'</font></b>';
		echo '<br><a href="https://aprs.fi/?call='.$scall.'" target="_blank">Pokaż na aprs.fi</a><br><br>';
		echo '<b>Frames heard: </b><a href="frames.php?getcall='.$_GET['getcall'].'" target="_blank">'.$noofframes.'</a><br>';
		echo '<br><br><font color="blue"><b>Ostatnia ramka z pozycją:</b> '.$posdate.' '.$postime.' GMT (';
		$dc = time() -date('Z') - strtotime($posdate.' '.$postime);
		echo (int)($dc / 86400).'d '.(int)(($dc % 86400) / 3600).'h '.(int)(($dc % 3600) / 60).'m '.(int)($dc % 60).'s ago)</font>';
		if ($frame_type !== 'Station Capabilities' and $frame_type !=='Status' and $frame_type !== 'Bulletin' and $frame_type !== 'Announcement' and $frame_type !== 'OTHER/UNKNOWN' and $declat !== 0.0 and $declon !== 0.0) {
			echo '<br><font color="red"><b>Pozycja: </b>'.$declat.'°, '.$declon.'° - <b>'.$distance.' km '.$bearing.'° od twojej lokalizacji</b></font>';
		}
                if ($station_type !== false) {
                        echo '<br><b>Typ stacja:</b> '.$station_type;
                }
                if (($station_class !== false) and ($station_class !== ' ')) {
                        echo '<br><b>Klasa stacja:</b> '.$station_class;
                }
		echo '<br><font color="green"><b>Komentarz: </b>'.$comment.'</font>';
		echo '<br><br><b>Typ ramki:</b> '.$frame_type;
		echo '<br><b>Symbol:</b> '.$symbol;
		echo '<br><b>Ścieżka:</b> '.$scall.'>'.$lastpath;
		echo '<br><b>Urządzenie:</b> '.$device;
                if ($alt !== false) {
                        echo '<br><b>Wysokość: </b>'.$alt.'Km';
                }
                if ($speed !== false) {
                        echo '<br><b>Prędkość: </b>'.$speed.'Km/h';
                }
                if ($course !== false) {
                        echo '<br><b>Kierunek: </b>'.$course.'°';
                }
                if ($telem !== false) {
                        echo '<br><b>Telemtry: </b>'.$telem;
                }

	}

}



} //close Polski language
?>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<center><a href="https://github.com/sq8vps/aprx-simplewebstat" target="_blank">APRX Simple Webstat version <?php echo $asw_version; ?></a> by Peter SQ8VPS and Alfredo IZ7BOJ</center>
</body>
</html>
