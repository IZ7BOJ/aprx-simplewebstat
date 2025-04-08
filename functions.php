<?php
/******************************************************************************************
This file is a part of SIMPLE WEB STATICSTICS GENERATOR FROM APRX LOG FILE
It's very simple and small APRX statictics generator in PHP. It's parted to smaller files and they will work independent from each other (but you always need chgif.php).
This script may have a lot of bugs, problems and it's written in very non-efficient way without a lot of good programming rules. But it works for me.
Author: Peter SQ8VPS, sq8vps[--at--]gmail.com & Alfredo IZ7BOJ
You can modify this program, but please give a credit to original authors. Program is free for non-commercial use only.
(C) Peter SQ8VPS & Alfredo IZ7BOJ 2017-2018

*******************************************************************************************/

function stationparse($frame) //function for parsing station information
{
	global $receivedstations;
	global $staticstations;
	global $movingstations;
	global $otherstations;
	global $viastations; //stations received via digi
	global $directstations; //stations received directly
	global $callraw;
	global $time;
	global $cntalias;
	$fg = 0;

	if((strpos($frame, $callraw." R"))OR(strpos($frame, $callraw." d")))//if frame received by RF
	{
		$uu = substr($frame, 0, 19);  //take only the part of the line, where date and time is
		$uu = strtotime($uu) + date('Z'); //convert string with date and time to the Unix timestamp and add a timezone shift
		if($uu > $time) //if frame was received in out time range
		{
			$aa = explode(">", $frame); //divide frame from > separator to get station's callsign
			if(strpos($aa[0]," R ")) //if it's received frame
				{
				$stationcall = substr($aa[0], strpos($aa[0], $callraw." R ") + strlen($callraw." R ")); //remove date and time, interface call up to the received station's call, so that we get only station's call
				}
			if(strpos($aa[0]," d ")) { //if it's a "d" frame
				if(strpos($aa[0]," d *"))
					{
                                        $stationcall = substr($aa[0], strpos($aa[0], $callraw." d *") + strlen($callraw." d *")); //remove date and time, interface call up to the received $
                                     	}
				else
					{
					$stationcall = substr($aa[0], strpos($aa[0], $callraw." d ") + strlen($callraw." d ")); //remove date and time, interface call up to the received station's call, so that we get only station's call
					}
			}
			if(array_key_exists($stationcall, $receivedstations)) //if this callsign is already on stations list
			{
				$receivedstations[$stationcall][0]++; //increment the number of frames from this station
			} else //if this callsign is not on the list
			{
				$receivedstations[$stationcall][0] = 1; //add callsign to the list
			}
			$receivedstations[$stationcall][1] = $uu; //add last time
			$bb = substr($frame, 46); //let's cut temporarily some part of a frame to make sure, that there is no : character, because we want it only as a separator between frame path and info field
			//------DEBUG-----^^^^^^ this can make some problems, beacuse it's very primitive
			$bb = substr($bb, strpos($bb, ":") + 1); //get whole date from the frame after a : character, to get info field
			if(($bb[0] === "@") or ($bb[0] === "!") or ($bb[0] === "=") or ($bb[0] === "/") or (ord($bb[0]) === 96) or (ord($bb[0]) === 39)) //if it's a frame with position or Mic-E position
			{
				if($bb[7] === 'z') //if the positions contains timestamp shift reading symbol data by 7 characters
				{
					$fg = 26;
				} else
				{
					$fg = 19;
				}
				if((ord($bb[0]) === 96) or (ord($bb[0]) === 39)) //special case - if Mic-E postion
				{
					$bb = str_replace("<0x1c>", chr(28), $bb); //replace unprintable characters written as <0xAA> with it's real value
					$bb = str_replace("<0x1d>", chr(29), $bb); //replace unprintable characters written as <0xAA> with it's real value
					$bb = str_replace("<0x1e>", chr(30), $bb); //replace unprintable characters written as <0xAA> with it's real value
					$bb = str_replace("<0x1f>", chr(31), $bb); //replace unprintable characters written as <0xAA> with it's real value
					$bb = str_replace("<0x7f>", chr(127), $bb); //replace unprintable characters written as <0xAA> with it's real value
					$fg = 7; //set symbol place to 7
				}
					if(in_array($bb[$fg], array('!', '#', '%', '&', '+', ',', '-', '.', '/', ':', ';', '?', '@', 'A', 'B', 'G', 'H', 'I', 'K', 'L', 'M', 'N', 'T', 'V', 'W', 'Z', '\\', ']', '_', '`', 'c', 'd', 'h', 'i', 'l', 'm', 'n', 'o', 'q', 'r', 't', 'w', 'x', 'y', 'z', '}')))
					{
						if(!in_array($stationcall, $staticstations))
						{
							$staticstations[] = $stationcall;
						}
					}
					elseif(in_array($bb[$fg], array('$', '\'', '(', ')', '*', '<', '=', '>', 'C', 'F', 'P', 'R', 'U', 'X', 'Y', '[', '^', 'a', 'b', 'f', 'g', 'j', 'k', 'p', 's', 'u', 'v')))
					{
						if(!in_array($stationcall, $movingstations))
						{
							$movingstations[] = $stationcall;
						}
					}
					else
					{
						if(!in_array($stationcall, $otherstations))
						{
							$otherstations[] = $stationcall;
						}
					}
			}

			$cc = substr($frame, strpos($frame, ">")); //temporarily get everything after > symbol (after received station callsign)
			$cc = substr($cc, 0, strpos($cc, ":")); //and then everything before info field separator, so that we have only frame path right now
			if(strpos($cc, '*') !== false) //if there is a * the frame was definitely not heard directly
			{
				if(!in_array($stationcall, $viastations))
				{
					$viastations[] = $stationcall;
				}
			} else //if there is no *
			{
				if($cntalias == "") //if no national alias selected, take frame as not direct
				{
						if(!in_array($stationcall, $viastations))
						{
							$viastations[] = $stationcall;
						}
						return;
				}
				$cntpos = strpos($cc, $cntalias);
				if((strpos($cc, $cntalias) !== false) and ($cc[$cntpos + 3] == "-")) //if there is national untraced alias without *, the frame still can be heard indirectly
				{
					if($cc[$cntpos + 2] == $cc[$cntpos + 4]) //if this path element has n=N, for example SP2-2, it was heard directly
					{
						if(!in_array($stationcall, $directstations))
						{
							$directstations[] = $stationcall;
						}
					} else //else if n!=N, for example SP2-1, the frame was PROBABLY heard via digi
					{
						if(!in_array($stationcall, $viastations))
						{
							$viastations[] = $stationcall;
						}
					}
				} else //if there is no national alias, it was heard directly
				{
					if(!in_array($stationcall, $directstations))
					{
						$directstations[] = $stationcall;
					}
				}
			}
		}
	}

}

function miceDecode( $in )
		{
			if ( strlen( $in ) > 1 ) return false;
			$v = ord( $in );
			$r = array();

			if ( ($v > 47 && $v < 58) || $v == 76 )
			{
				if ( $v == 76 ) $r['dig'] = '';
				else $r['dig'] = $v-48;
				$r['msg'] = '0';
				$r['ns']  = 'S';
				$r['off'] = 0;
				$r['we']  = 'E';
			}
			if ( $v > 64 && $v < 76 )
			{
				if ( $v == 75 ) $r['dig'] = '';
				else $r['dig'] = $v-65;
				$r['msg'] = '1 Custom';
				$r['ns']  = '';
				$r['off'] = '';
				$r['we']  = '';
			}
			if ( $v > 79 && $v < 91 )
			{
				if ( $v == 90 ) $r['dig'] = '';
				else $r['dig'] = $v-80;
				$r['msg'] = '1 Std';
				$r['ns']  = 'N';
				$r['off'] = 100;
				$r['we']  = 'W';
			}
			return $r;
		}


function frameparse($frame) //inspired to https://github.com/geeojr/php-aprs
{
	global $scall;
	global $posframefound;
	global $lastpath;
	global $noofframes;
	global $symbol;
	global $stationlat;
	global $stationlon;
	global $declat;
	global $declon;
	global $posdate;
	global $postime;
	global $comment;
	global $status;
	global $distance;
	global $bearing;
	global $station_type;
	global $frame_type;
	global $alt;
	global $course;
	global $speed;
	global $telem;

	$packet = substr($frame, 36); //get only frame, without interface call, date etc.

	$packet = str_replace("<0x1c>", chr(28), $packet); //replace unprintable characters written as <0xAA> with it's real value
	$packet = str_replace("<0x1d>", chr(29), $packet); //replace unprintable characters written as <0xAA> with it's real value
	$packet = str_replace("<0x1e>", chr(30), $packet); //replace unprintable characters written as <0xAA> with it's real value
	$packet = str_replace("<0x1f>", chr(31), $packet); //replace unprintable characters written as <0xAA> with it's real value
	$packet = str_replace("<0x7f>", chr(127), $packet); //replace unprintable characters written as <0xAA> with it's real value

	$packet_exploded = explode(">", $packet); //get the callsign
        $packet_call=$packet_exploded[0];
	if(substr($packet_call,0,1)=="*") {
		$packet_call=str_replace("*","",$packet_call);
		}
	if($packet_call == $scall)
	{
		$noofframes++;

		if($posframefound) return; //if we have already position frame parsed, just skip it

		$station_type = false;
		$symbol = false;
		$lat = false;
		$lon = false;
		$msg_to = false;
		$msg = false;
		$ack = false;
		$comment = false;
		$status = false;
		$capabilities = false;
		$kill = false;
		$alt = false;
		$course = false;
		$speed = false;
		$telem = false;

		$posdate = substr($frame, 0, 10); //extract date
		$postime = substr($frame, 11, 8); //extract time

		$path = explode(":", $packet_exploded[1]); //take everything after station callsign and before info field (see bb[0])
		$lastpath = $path[0];

		$posframefound = 1; //newest position frame found

		$src = substr( $packet , 0 , strpos( $packet , '>' ) );
		$packet = substr( $packet , strlen($src)+1 );

		$destination = substr( $packet , 0 , strpos( $packet , ',' ) );
		$packet = substr( $packet , strlen($destination)+1 );

		$path = substr( $packet , 0 , strpos( $packet , ':' ) );
		$packet = substr( $packet , strlen($path)+1 );

		$type = substr( $packet , 0 , 1 );
		$packet = substr( $packet , 1 );
		$m = false;
		switch( $type )
		{
			case '!':
				$t = 'position';
				$frame_type = 'Position w/o timestamp';
				$station_type = 'station';
				break;
			case '=':
				$t = 'position';
				$frame_type = 'Position w/o timestamp - msg capable';
				$station_type = 'station';
				$m = true;
				break;
			case '/':
				$t = 'position_time';
				$frame_type = 'Position w/ timestamp';
				$station_type = 'station';
				break;
			case '@':
				$t = 'position_time';
				$frame_type = 'Position w/ timestamp - msg capable';
				$station_type = 'station';
				$m = true;
				break;
			case '>':
				$t = 'status';
				$frame_type = 'Status';
				break;
			case '<':
				$t = 'capabilities';
				$frame_type = 'Station Capabilities';
				break;
			case '#':
			case '*':
				$t = 'weather';
				$frame_type = 'WX';
				break;
			case '_':
				$t = 'weather';
				$frame_type = 'WX w/o position';
				break;
			case '$':
				$t = 'gps';
				$frame_type = 'Raw GPS';
				break;
			case ')':
				$t = 'item';
				$frame_type = 'Item';
				$station_type = 'item';
				break;
			case ';':
				$t = 'object';
				$frame_type = 'Object';
				$station_type = 'object';
				break;
			case ':':
				$t = 'message';
				$frame_type = 'Message';
				break;
			case '`':
				$t = 'mic-e';
				$frame_type = 'Mic-E Data (current)';
				break;
			case "'":
				$t = 'mic-e';
				$frame_type = 'Mic-E Data (old/D-700)';
				break;
			default:
				$t = '';
				$frame_type = 'OTHER/UNKNOWN';
		}

		if ( $t == 'position' )
		{
			if ( is_numeric( substr( $packet , 0 , 1 ) ) )
			{
				// 3901.00N/09433.47WhPHG7330 W2, MOn-N RMC, mary.young@hcamidwest.com
				$lat = intval(substr( $packet , 0 , 2 )) + substr( $packet , 2 , 5 )/60;
				if ( substr( $packet , 7 , 1 ) == 'S' ) $lat = -$lat;

				$lon = intval(substr( $packet , 9 , 3 )) + substr( $packet , 12 , 5 )/60;
				if ( substr( $packet , 17 , 1 ) == 'W' ) $lon = -$lon;

				$symbol = substr( $packet , 8 , 1 ).substr( $packet , 18 , 1 );
				$comment = substr( $packet , 19 );
			}
			else
			{ // /:\{s6T`U>R:G/A=001017 13.8V Jeremy kd0eav@clear-sky.net
				$clat = substr( $packet , 1 , 4 );
				$lat = 90 - ( (ord($clat[0])-33)*pow(91,3) + (ord($clat[1])-33)*pow(91,2) + (ord($clat[2])-33)*91 + ord($clat[3])-33 ) / 380926;

				$clon = substr( $packet , 5 , 4 );
				$lon= -180 + ( (ord($clon[0])-33)*pow(91,3) + (ord($clon[1])-33)*pow(91,2) + (ord($clon[2])-33)*91 + ord($clon[3])-33 ) / 190463;

				// check if we have sane values
				if ( abs($lon) > 180 || abs($lat) > 90 )
				{
					$lon = false;
					$lat = false;
				}

				$symbol = substr( $packet , 0 , 1 ).substr( $packet , 9 , 1 );

				$cs = substr( $packet , 10 , 2 );
				if ( substr( $cs , 0 , 1 ) != ' ' )
				{
					// TODO: figure out cOMPRessed course/speed or alt or range
					$ctype = substr( $packet , 12 , 1 );

				}
				$comment = substr( $packet , 13 );
			}
		}
		if ( $t == 'position_time' )
		{
			// 202051z3842.05N/09317.07W_308/009g017t026r000p000P000h75b10173L021.DsVP
			$lat = intval(substr( $packet , 7 , 2 )) + substr( $packet , 9 , 5 )/60;
			if ( substr( $packet , 15 , 1 ) == 'S' ) $lat = -$lat;

			$lon = intval(substr( $packet , 16 , 3 )) + substr( $packet , 19 , 5 )/60;
			if ( substr( $packet , 24 , 1 ) == 'W' ) $lon = -$lon;

			$symbol = substr( $packet , 15 , 1 ).substr( $packet , 25 , 1 );
			$comment = substr( $packet , 26 );
		}
		if ( $t == 'object' )
		{ // 146.79-KC*202142z3917.54N/09434.49WrKC Northland ARES / Clay Co ARC T107.2

			$lat = intval(substr( $packet , 17 , 2 )) + substr( $packet , 19 , 5 )/60;
			if ( substr( $packet , 25 , 1 ) == 'S' ) $lat = -$lat;

			$lon = intval(substr( $packet , 26 , 3 )) + substr( $packet , 29 , 5 )/60;
			if ( substr( $packet , 34 , 1 ) == 'W' ) $lon = -$lon;

			$symbol = substr( $packet , 25 , 1 ).substr( $packet , 35 , 1 );
			$comment = substr( $packet , 36 );
		}
		if ( $t == 'item' )
		{ // 146.79-KC!3917.54N/09434.49WrKC Northland ARES / Clay Co ARC T107.2

			$offset = strpos( $packet , '!' );
			if ( $offset === false )
			{
				$offset = strpos( $packet , '_' );
				if ( $offset === false ) return false;
				else $kill = true;
			}

			$lat = intval(substr( $packet , $offset+1 , 2 )) + substr( $packet , $offset+3 , 5 )/60;
			if ( substr( $packet , $offset+8 , 1 ) == 'S' ) $lat = -$lat;

			$lon = intval(substr( $packet , $offset+10 , 3 )) + substr( $packet , $offset+13 , 5 )/60;
			if ( substr( $packet , $offset+18 , 1 ) == 'W' ) $lon = -$lon;

			$symbol = substr( $packet , $offset+9 , 1 ).substr( $packet , $offset+19 , 1 );
			$comment = substr( $packet , $offset+20 );
		}
		if ( $t == 'message' )
		{
			$msg_to = trim(substr( $packet , 0 , 9 ));
			if ( substr( $msg_to , 0 , 3 ) == 'BLN' )
			{
				if ( is_numeric(substr( $msg_to , 3 , 1 ) ) )
					$frame_type = 'Bulletin';
				else
					$frame_type = 'Announcement';
				$msg = substr( $packet , 10 );
			}
			else
			{
				$pos = strpos( $packet , '{' );
				if ( $pos !== false ) $ack = substr( $packet , $pos+1 );
				else $ack = '';
				$msg = substr( $packet , 10 , -(strlen($ack)+1) );

				if ( substr( $msg , 0 , 3 ) == 'ack' ) $frame_type = 'Message Acknowledge';
				if ( substr( $msg , 0 , 3 ) == 'rej' ) $frame_type = 'Message Reject';
				if ( $frame_type != 'Message' ) $ack = substr( $msg , 3 );
			}
		}
		if ( $t == 'mic-e' )
		{
			for( $i=0 ; $i<7 ; $i++ )
				$lat_dig[$i] = miceDecode(substr( $destination , $i , 1 ));

			$lat = intval($lat_dig[0]['dig'].$lat_dig[1]['dig'])
							+ ($lat_dig[2]['dig'].$lat_dig[3]['dig'].'.'.$lat_dig[4]['dig'].$lat_dig[5]['dig'])/60;
			if ( $lat_dig[3]['ns'] == 'S' ) $lat = -$lat;

			$lon = (ord(substr( $packet , 0 , 1 ))-28) + $lat_dig[4]['off']
							+ ((ord(substr( $packet , 1 , 1 ))-28) +
								((ord(substr( $packet , 2 , 1 ))-28) * .01) ) /60;
			if ( $lat_dig[5]['we'] == 'W' ) $lon = -$lon;

			$symbol = substr( $packet , 7 , 1 ).substr( $packet , 6 , 1 );
			$comment = substr( $packet , 8 );

			// not sure on the format on some packets; here we're just assuming it's telem if there's more than a couple commas
			$telem = explode( ',' , $comment );
			if ( count( $telem ) < 2 ) $telem = false;

			// check if the first telem has some unknown data in it - not sure what this is yet.
			if ( is_array( $telem ) )
				if ( substr( $telem[0] , 3 , 1 ) == '}' )
					$telem[0] = substr( $telem[0] , 4 );

		}

		// check if we have course/speed data
		if ( substr( $comment , 3 , 1 ) == '/' )
		{
			$course = intval(substr( $comment , 0 , 3 ));
			if ( $course > 360 ) $course = false;

			$speed = substr( $comment , 4 , 3 ) * 1.152; //knots to Mph
		}

		if ( strpos( $comment , '/A=' ) !== false )
		{
			$alt = intval(substr( $comment , strpos( $comment , '/A=' )+3,6));
			//$comment = substr( $packet , 8 , strpos( $comment , '/A=' ) ) . substr( $packet , strpos( $comment , '/A=' )+22 );
			if ( $alt == 0 ) $alt = false;
		}

		// http://he.fi/doc/aprs-base91-comment-telemetry.txt
		if ( strpos( $comment , '|' ) !== false )
		{
			$telem_string = substr( $comment , strpos( $comment , '|' ) + 1 , strrpos( $comment , '|' )-1 );

			for( $i=0 ; $i < strlen( $telem_string )/2 ; $i++ )
			{
				$telem[] = (ord($telem_string[$i*2])-33)*pow(91,1) + (ord($telem_string[($i*2)+1])-33);
			}
			//$comment = '';
		}

		if ( $t == 'status' )
			$status = $packet;

		if ( $t == 'capabilities' )
			$capabilities = $packet;

		//fine parte incollata

		//haversine formula for distance calculation

		$latFrom = deg2rad($stationlat);
		$lonFrom = deg2rad($stationlon);
		$latTo = deg2rad($lat);
		$lonTo = deg2rad($lon);

		$latDelta = $latTo - $latFrom;
		$lonDelta = $lonTo - $lonFrom;

		$bearing = rad2deg(atan2(sin($lonDelta)*cos($latTo), cos($latFrom)*sin($latTo)-sin($latFrom)*cos($latTo)*cos($latDelta)));
		if($bearing < 0) $bearing += 360;

		$angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
		cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
		$distance = round($angle * 6371, 2); //gives result in km rounded to 2 digits after comma

		$declat = round($lat, 5);
		$declon = round($lon, 5);
		$bearing = round($bearing, 1);


	}

} //close frameparse function

//function for load calc

function rxload() {
global $logfile;
global $callraw;
global $lines;
global $rxframespermin;

$count=0;
$index1=1;
//find the time of last rx packet in log
while (($index1<$lines)AND(!((strpos($logfile[$lines - $index1],$callraw." R"))OR(strpos($logfile[$lines - $index1],$callraw." d"))))) {
        $index1++;
        }
$time1 = strtotime(substr($logfile[$lines - $index1], 0, 19));

$index2=$index1+1;

//go back to last-20  received packets and take time
while (($index2<$lines)AND($count<19)) {
        if((strpos($logfile[$lines - $index2],$callraw." R"))OR(strpos($logfile[$lines - $index2],$callraw." d"))) {
                $time2 = strtotime(substr($logfile[$lines - $index2], 0, 19));
                $count++;
                }
	$index2++;
}
$rxframespermin = $count / (($time1 - $time2) / 60);
//echo $count."<br>";//debug line
//echo $index1."<br>";//debug line
//echo $index2."<br>";//debug line
return $rxframespermin;
}

//maybe it's possible to merge these two functions...

function txload() {
global $logfile;
global $callraw;
global $lines;
global $txframespermin;

$count=0;
$index1=1;
//find the time of last tx packet in log
while (($index1<$lines)AND(!(strpos($logfile[$lines - $index1],$callraw." T")))) {
        $index1++;
        }
$time1 = strtotime(substr($logfile[$lines - $index1], 0, 19));

$index2=$index1+1;

//go back to last-20  tx packets and take time
while (($index2<$lines)AND($count<19)) {
        if(strpos($logfile[$lines - $index2],$callraw." T")) {
                $time2 = strtotime(substr($logfile[$lines - $index2], 0, 19));
                $count++;
                }
        $index2++;
}
$txframespermin = $count / (($time1 - $time2) / 60);
//echo $count."<br>"; // debug line
//echo $index1."<br>"; //debug line
//echo $index2."<br>"; //debug line
return $txframespermin;
}

function device() {
	global $lastpath;
	global $device;
	global $frame_type;
	global $comment;
	global $station_class;

	$match=false;
	$device=false;
	$station_class=false;

	//$tocalls_json=json_decode(file_get_contents("tocalls.pretty.json"));
	$tocalls_json=json_decode(utf8_encode(file_get_contents("https://github.com/aprsorg/aprs-deviceid/raw/refs/heads/main/generated/tocalls.pretty.json")));
	if ($frame_type=='Mic-E Data (current)') { //mic-e
		$tocall=substr($comment,strlen($comment)-3,2); // last character is space, must be deleted
       		if (isset($tocalls_json->mice->$tocall)) { //if key exists
			$device=$tocalls_json->mice->$tocall->vendor." ".$tocalls_json->mice->$tocall->model; //output is concatenated string: model-vendor
			if (isset($tocalls_json->mice->$tocall->class))
				$station_class=$tocalls_json->mice->$tocall->class;
			$match=True;
		}
	} elseif ($frame_type=='Mic-E Data (old/D-700)') { //mic-e legacy
		$tocall=substr($comment,0,1).substr($comment,strlen($comment)-2,1); // in legacy devices, take last and first character of the comment
		if (isset($tocalls_json->micelegacy->$tocall)) { //try match with both character first
			$device=$tocalls_json->micelegacy->$tocall->vendor." ".$tocalls_json->micelegacy->$tocall->model;
			if (isset($tocalls_json->micelegacy->$tocall->class))
				$station_class=$tocalls_json->micelegacy->$tocall->class;
			$match=True;
		} else { // try only first character match
			$tocall=substr($comment,0,1);
			if (isset($tocalls_json->micelegacy->$tocall)) {
                        	$device=$tocalls_json->micelegacy->$tocall->vendor." ".$tocalls_json->micelegacy->$tocall->model;
				if (isset($tocalls_json->micelegacy->$tocall->class))
					$station_class=$tocalls_json->micelegacy->$tocall->class;
                        	$match=True;
			}
		}
	} else { // not mic-e
		$tocall = substr($lastpath,0,strpos($lastpath,",")); //cut destination call from lastpath
		$w=0; //number of wildcards. Try exact match first
		while ($w<=strlen($tocall)-3){ //3 wildcards for 6characters tocall, 2wilds for 5characters and 1wild for 4character
			$tocall_wild=substr($tocall,0,strlen($tocall)-$w).str_repeat("?",$w); //start without wilds, then replace last part with "?"
			if (isset($tocalls_json->tocalls->$tocall_wild)) { //compare destination call + wilds with json file
				$device=$tocalls_json->tocalls->$tocall_wild->vendor." ".$tocalls_json->tocalls->$tocall_wild->model;
				if (isset($tocalls_json->tocalls->$tocall->class))
					$station_class=$tocalls_json->tocalls->$tocall_wild->class;
				$match=True; //set match flag
				break; //exit of match
				}
			$tocall_wild=substr($tocall,0,strlen($tocall)-$w).str_repeat("*",$w); //start without wilds, then replace last part with "*"
			if (isset($tocalls_json->tocalls->$tocall_wild)) { //compare destination call + wilds with json file
                                $device=$tocalls_json->tocalls->$tocall_wild->vendor." ".$tocalls_json->tocalls->$tocall_wild->model;
				if (isset($tocalls_json->toclass->$tocall->class))
					$station_class=$tocalls_json->tocalls->$tocall_wild->class;
                                $match=True; //set match flag
                                break; //exit of match
                                }
                        $tocall_wild=substr($tocall,0,strlen($tocall)-$w).str_repeat("n",$w); //start without wilds, then replace last part with "n"
                        if (isset($tocalls_json->tocalls->$tocall_wild)) { //compare destination call + wilds with json file
                                $device=$tocalls_json->tocalls->$tocall_wild->vendor." ".$tocalls_json->tocalls->$tocall_wild->model;
				if (isset($tocalls_json->tocalls->$tocall->class))
					$station_class=$tocalls_json->tocalls->$tocall_wild->class;
                                $match=True; //set match flag
                                break; //exit of match
                                }
			$w++;
		}
	} //closes not mic-e case
	if ($match==false) {
		$device="Unknown";
		}
} //closes device() function
?>
