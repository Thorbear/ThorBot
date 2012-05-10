<?php
/**
 * ThorBot 6 (Started 23. June 2010)
 * MAngband IRC information bot
 * by Thorbear (thorbears@hotmail.com)
 *
 */

/* Step 1: Read all files, sort them into arrays */
/* Step 2: Connect to IRC */
/* Step 3: Respond to various commands */


/**************/
/*** STEP 1 ***/
/**************/

echo "Loading data... ";

/* Read all files into variables */
$monsters = makeArray(stripComments(file_get_contents('monster.txt')));
$monsterFlags = makeFlagArray(file_get_contents('monster-flags.txt'));
$artifacts = makeArray(stripComments(file_get_contents('artifact.txt')));
$egos = makeArray(stripComments(file_get_contents('ego_item.txt')));
$objects = makeArray(stripComments(file_get_contents('object.txt')));
$itemFlags = makeFlagArray(file_get_contents('item-flags.txt'));

/* Monster */
for($i = 0; $i <= count($monsters); $i++) {
	$string = $monsters[$i];
	unset($monsters[$i]);
	/* HACK - first monster is <player> */
	if($i != 0) {
		$j = $i - 1;
		$monsters[$j]['N'] = infoCS($string, 'N');
		$monsters[$j]['G'] = infoCS($string, 'G');
		$monsters[$j]['I'] = infoCS($string, 'I');
		$monsters[$j]['W'] = infoCS($string, 'W');
		$monsters[$j]['B'] = infoB($string);
		$monsters[$j]['S'] = infoS($string);
		$monsters[$j]['F'] = infoF($string);
	}
}
$monster = makeMonsterReadable($monsters, $monsterFlags);

/* Objects */
for($i = 0; $i < count($objects); $i++) {
	$string = $objects[$i];
	unset($objects[$i]);
	$objects[$i]['N'] = infoCS($string, 'N');
	$objects[$i]['G'] = infoCS($string, 'G');
	$objects[$i]['I'] = infoCS($string, 'I');
	$objects[$i]['W'] = infoCS($string, 'W');
	$objects[$i]['P'] = infoCS($string, 'P');
	$objects[$i]['A'] = infoCS($string, 'A');
	$objects[$i]['F'] = infoF($string);
}

/* Artifact */
for($i = 0; $i < count($artifacts); $i++) {
	$string = $artifacts[$i];
	unset($artifacts[$i]);
	$artifacts[$i]['N'] = infoCS($string, 'N');
	$artifacts[$i]['I'] = infoCS($string, 'I');
	$artifacts[$i]['W'] = infoCS($string, 'W');
	$artifacts[$i]['P'] = infoCS($string, 'P');
	$artifacts[$i]['F'] = infoF($string);
	$artifacts[$i]['A'] = infoCS($string, 'A');
}
$artifact = makeArtifactReadable($artifacts, $objects, $itemFlags);

/* Ego-item */
for($i = 0; $i < count($egos); $i++) {
	$string = $egos[$i];
	unset($egos[$i]);
	$egos[$i]['N'] = infoCS($string, 'N');
	$egos[$i]['X'] = infoCS($string, 'X');
	$egos[$i]['C'] = infoCS($string, 'C');
	$egos[$i]['W'] = infoCS($string, 'W');
	$egos[$i]['T'] = infoCS($string, 'T');
	$egos[$i]['F'] = infoF($string);
}
$ego = makeEgoReadable($egos, $objects, $itemFlags);

echo "Done\n\n";


/**************/
/*** STEP 2 ***/
/**************/

$IRC = array(
	"Host" => "chat.freenode.net",
	"Channel" => "#MAngband",
	"Port" => 6667,
	"Nick" => "ThorBot"
);
// Make it easyer to message the channel, and apply brown color
//$channelMessage = "PRIVMSG ".$IRC['Channel']." :\x0305";
/* remove colouring for readability in-game */
$channelMessage = "PRIVMSG ".$IRC['Channel']." :";

/* IRC Connection */
$IRCC = fsockopen($IRC['Host'], $IRC["Port"], $Errno, $Errstr, 30);

/* Connection Fail */
if(!$IRCC){
	exit($Errstr." (".$Errno.")\n\n");
}

/* Connection Success */
fwrite($IRCC, "NICK ".$IRC["Nick"]."\r\n");
fwrite($IRCC, "USER ".$IRC["Nick"]." ".$IRC["Host"]." bla :".$IRC["Nick"]."\r\n");
fwrite($IRCC, "JOIN ".$IRC["Channel"]."\r\n");

fwrite($IRCC, $channelMessage."ThorBot v6.0 at your service!\r\n"); //Say hello ThorBot


/**************/
/*** STEP 3 ***/
/**************/

/* Loop through each line */
while(!feof($IRCC)) {
	$line = fgets($IRCC, 128);
	IRClog($line, $IRC["Nick"]); // Log function, no file created.

	if(substr($line, 0, 6) == "PING :") {
		/* Respond to PING */
		fwrite($IRCC, "PONG ".substr($line, 6)."\r\n");
	} else {
		/* Respond to COMMANDS */
		$lineArray = explode("$", $line); // If $ is entered
		$com = (isset($lineArray[1])) ? explode(" ", $lineArray[1]) : array(""); // Separate by spaces

		if ($com[0] != "") {
			$com[0] = strtolower($com[0]);
			echo "COMMAND: ".$com[0]."\n"; // Echo command to console

			switch($com[0]) {
				case "thorbot\r\n": // WHOIS - Fix needed
					fwrite($IRCC, $channelMessage."ThorBot v6.0 at your service!\r\n");
					break;

				case "mon": // MONSTER
				//case "monster":
					commandMon($IRCC, $channelMessage, $com, $monster, $monsterFlags);
					break;

				case "art": // ARTIFACT
				//case "artifact": 
					commandArt($IRCC, $channelMessage, $com, $artifact);
					break;

				case "ego": // EGO ITEMS
					commandEgo($IRCC, $channelMessage, $com, $ego);
					break;

				case "quit\r\n": // QUIT
					if (substr($lineArray[0], 1, 9) == "Thorbear!" || substr($lineArray[0], 1, 8) == "Domiano!") { // ONLY ME (and Domiano)
						fwrite($IRCC, $channelMessage."Leaving!\r\n"); // Say bye ThorBot
						fwrite($IRCC, "QUIT :Bye\r\n");
						exit("ThorBot has stopped"); // Echo successful quit message to console
					} else {
						fwrite($IRCC, $channelMessage."You are not my master!\r\n"); // If not me
					}
					break;
			}
		}
	}
}
fclose($IRCC); // Close the connection to IRC
exit("ThorBot has stopped (Problem?)"); // Upon unexpected program stop


/**************/
/***  DONE  ***/
/**************/

/* Functions */
function stripComments($string) {
	return trim(preg_replace('/^#.+/m', '', $string));
}
function makeArray($string) {
	preg_match_all('/^N:((?>[^\n])|\n(?!N)(?!:))*/m', $string, $result, PREG_PATTERN_ORDER);
	return $result[0];
}
function makeFlagArray($string) {
	preg_match_all('/^# (.+)((\n(?!#).*)*)/m', $string, $result, PREG_PATTERN_ORDER);
	for($i = 0; $i < count($result[1]); $i++) {
		unset($XX);
		preg_match_all('/^.*/m', $result[2][$i], $X, PREG_PATTERN_ORDER);
		for($j = 0; $j < count($X[0]); $j++) {
			$XX[$j] = explode(' | ', $X[0][$j]);
			$XX[$j][0] = trim($XX[$j][0]);
			$XX[$j][1] = trim($XX[$j][1]);
			
		}
		$return[trim($result[1][$i])] = $XX;
	}
	return $return;
}
function makeMonsterReadable($monsters, $flagArray) {
	$X = array('N', 'G', 'I', 'W', 'B', 'S', 'F');
	for($i = 0; $i < count($monsters); $i++) {
		if(is_array($monsters[$i]['F']) && in_array("FORCE_MAXHP", $monsters[$i]['F'])) {
			$monsters[$i]['I'][2] = explode("d", $monsters[$i]['I'][2]);
			$HP = $monsters[$i]['I'][2][0] * $monsters[$i]['I'][2][1];
		} else {
			$HP = $monsters[$i]['I'][2];
		}
		$summons = "";
		$spells = "";
		$breaths = "";
		$melee = "";
		$flags = "";
		$frequency = "";
		$resists = "";
		if(is_array($monsters[$i]['S'])) {
			foreach($monsters[$i]['S'] as $value) {
				for($j = 0; $j < count($flagArray['Summons']); $j++) {
					if(strcmp($flagArray['Summons'][$j][0], $value) === 0) {
						$summons .= (($summons != "")?", ":" ").$flagArray['Summons'][$j][1];
						break;
					}
				}
				for($j = 0; $j < count($flagArray['Spells']); $j++) {
					if(strcmp($flagArray['Spells'][$j][0], $value) === 0) {
						$spells .= (($spells != "")?", ":" ").$flagArray['Spells'][$j][1];
						break;
					}
				}
				for($j = 0; $j < count($flagArray['Breaths']); $j++) {
					if(strcmp($flagArray['Breaths'][$j][0], $value) === 0) {
						$breaths .= (($breaths != "")?", ":" ").$flagArray['Breaths'][$j][1];
						break;
					}
				}
				for($j = 0; $j < count($flagArray['Frequency']); $j++) {
					if(strcmp($flagArray['Frequency'][$j][0], $value) === 0) {
						$frequency .= ", ".$flagArray['Frequency'][$j][1];
						break;
					}
				}
			}
		}
		if(is_array($monsters[$i]['B'])) {
			foreach($monsters[$i]['B'] as $m) {
				for($j = 0; $j < count($flagArray['Melee 1']); $j++) {
					if(strcmp($flagArray['Melee 1'][$j][0], $m[1]) === 0) {
						$melee .= (($melee != "")?", ":" ").$flagArray['Melee 1'][$j][1];
						break;
					}
				}
				for($j = 0; $j < count($flagArray['Melee 2']); $j++) {
					if(strcmp($flagArray['Melee 2'][$j][0], $m[2]) === 0) {
						$melee .= (($melee != "")?" ":"").$flagArray['Melee 2'][$j][1];
						break;
					}
				}
				$melee .= ($m[3] != "") ? " ".$m[3] : "";
			}
		}
		if(is_array($monsters[$i]['F'])) {
			foreach($monsters[$i]['F'] as $value) {
				for($j = 0; $j < count($flagArray['Resists']); $j++) {
					if(strcmp($flagArray['Resists'][$j][0], $value) === 0) {
						$resists .= (($resists != "")?", ":" ").$flagArray['Resists'][$j][1];
						break;
					}
				}
				for($j = 0; $j < count($flagArray['Flags']); $j++) {
					if(strcmp($flagArray['Flags'][$j][0], $value) === 0) {
						$flags .= (($flags != "")?", ":" ").$flagArray['Flags'][$j][1];
						break;
					}
				}
			}
		}
		$monsters[$i]['name'] = $monsters[$i]['N'][2];
		$monsters[$i]['info'] = "Depth ".(($monsters[$i]['W'][1])*50)."ft |Speed ".(($monsters[$i]['I'][1] - 110 < 0) ? ($monsters[$i]['I'][1] - 110) : "+".($monsters[$i]['I'][1] - 110))." |Hitpoints ".$HP." |AC ".$monsters[$i]['I'][4]." |Exp ".$monsters[$i]['W'][4];
		$monsters[$i]['melee'] = "|MELEE ".$melee;
		$monsters[$i]['summons'] = "|SUMMONS ".$summons;
		$monsters[$i]['spells'] = "|SPELLS ".$spells;
		$monsters[$i]['breaths'] = "|BREATHS ".$breaths;
		$monsters[$i]['flags'] = "| ".$flags;
		$monsters[$i]['frequency'] = $frequency;
		$monsters[$i]['resists'] = "|RESISTS ".$resists;
	}
	return $monsters;
}
function makeArtifactReadable($artifacts, $objects, $flagArray) {
	$isWeapon = array(16,17,18,20,21,22,23);
	$isArmor = array(30,31,32,33,34,35,36,37,38);
	$isBow = array(19);
	for($i = 0; $i < count($artifacts); $i++) {
		$name = "";
		$resists = "";
		$slays = "";
		$stats = "";
		$nameSuffix = "";
		$activate = "";
		$flags = "";
		for($j = 0; $j < count($objects); $j++) {
			if($objects[$j]['I'][1] == $artifacts[$i]['I'][1] && $objects[$j]['I'][2] == $artifacts[$i]['I'][2]) {
				$name = preg_replace('/[&~]/', '', $objects[$j]['N'][2]);
				break;
			}
		}
		if(in_array($artifacts[$i]['I'][1], $isWeapon)) {
			$nameSuffix .= "(".$artifacts[$i]['P'][2].")";
			$nameSuffix .= ($artifacts[$i]['P'][3] != 0 || $artifacts[$i]['P'][4] != 0) ? " (".(($artifacts[$i]['P'][3] < 0) ? $artifacts[$i]['P'][3] : "+".$artifacts[$i]['P'][3]).",".(($artifacts[$i]['P'][4] < 0) ? $artifacts[$i]['P'][4] : "+".$artifacts[$i]['P'][4]).")" : "";
			$nameSuffix .= ($artifacts[$i]['P'][5] != 0) ? " [".(($artifacts[$i]['P'][5] < 0) ? $artifacts[$i]['P'][5] : "+".$artifacts[$i]['P'][5])."]" : "";
			$nameSuffix .= ($artifacts[$i]['I'][3] != 0) ? " (".(($artifacts[$i]['I'][3] < 0) ? $artifacts[$i]['I'][3] : "+".$artifacts[$i]['I'][3]).")" : "";
		} elseif(in_array($artifacts[$i]['I'][1], $isArmor)) {
			if($artifacts[$i]['P'][3] != 0 && $artifacts[$i]['P'][4] != 0) {
				$nameSuffix .= "(".(($artifacts[$i]['P'][3] < 0) ? $artifacts[$i]['P'][3] : "+".$artifacts[$i]['P'][3]).",".(($artifacts[$i]['P'][4] < 0) ? $artifacts[$i]['P'][4] : "+".$artifacts[$i]['P'][4]).")";
			} elseif($artifacts[$i]['P'][3] != 0) {
				$nameSuffix .= "(".(($artifacts[$i]['P'][3] < 0) ? $artifacts[$i]['P'][3] : "+".$artifacts[$i]['P'][3]).")";
			}
			$nameSuffix .= " [".$artifacts[$i]['P'][1].",".(($artifacts[$i]['P'][5] < 0) ? $artifacts[$i]['P'][5] : "+".$artifacts[$i]['P'][5])."]";
			$nameSuffix .= ($artifacts[$i]['I'][3] != 0) ? " (".(($artifacts[$i]['I'][3] < 0) ? $artifacts[$i]['I'][3] : "+".$artifacts[$i]['I'][3]).")" : "";
		} elseif(in_array($artifacts[$i]['I'][1], $isBow)) {
			/* HACK */
			$power = (in_array("MIGHT", $artifacts[$i]['F'])) ? ($artifacts[$i]['I'][2] % 10) + 1: $artifacts[$i]['I'][2] % 10;
			$nameSuffix .= "(x".$power.")";
			$nameSuffix .= ($artifacts[$i]['P'][3] != 0 || $artifacts[$i]['P'][4] != 0) ? " (".(($artifacts[$i]['P'][3] < 0) ? $artifacts[$i]['P'][3] : "+".$artifacts[$i]['P'][3]).",".(($artifacts[$i]['P'][4] < 0) ? $artifacts[$i]['P'][4] : "+".$artifacts[$i]['P'][4]).")" : "";
			$nameSuffix .= ($artifacts[$i]['P'][5] != 0) ? " [".(($artifacts[$i]['P'][5] < 0) ? $artifacts[$i]['P'][5] : "+".$artifacts[$i]['P'][5])."]" : "";
			$nameSuffix .= ($artifacts[$i]['I'][3] != 0) ? " (".(($artifacts[$i]['I'][3] < 0) ? $artifacts[$i]['I'][3] : "+".$artifacts[$i]['I'][3]).")" : "";
		} else {
			$nameSuffix .= ($artifacts[$i]['P'][3] != 0 || $artifacts[$i]['P'][4] != 0) ? " (".(($artifacts[$i]['P'][3] < 0) ? $artifacts[$i]['P'][3] : "+".$artifacts[$i]['P'][3]).",".(($artifacts[$i]['P'][4] < 0) ? $artifacts[$i]['P'][4] : "+".$artifacts[$i]['P'][4]).")" : "";
			$nameSuffix .= ($artifacts[$i]['P'][5] != 0) ? " [".(($artifacts[$i]['P'][5] < 0) ? $artifacts[$i]['P'][5] : "+".$artifacts[$i]['P'][5])."]" : "";
			$nameSuffix .= ($artifacts[$i]['I'][3] != 0) ? " (".(($artifacts[$i]['I'][3] < 0) ? $artifacts[$i]['I'][3] : "+".$artifacts[$i]['I'][3]).")" : "";
		}
		if(is_array($artifacts[$i]['F'])) {
			foreach($artifacts[$i]['F'] as $flag) {
				for($j = 0; $j < count($flagArray['Resists']); $j++) {
					if(strcmp($flagArray['Resists'][$j][0], $flag) === 0 && $flag != "") {
						$resists .= (($resists != "")?", ":" ").$flagArray['Resists'][$j][1];
						break;
					}
				}
				for($j = 0; $j < count($flagArray['Slays']); $j++) {
					if(strcmp($flagArray['Slays'][$j][0], $flag) === 0 && $flag != "") {
						$slays .= (($slays != "")?", ":" ").$flagArray['Slays'][$j][1];
						break;
					}
				}
				for($j = 0; $j < count($flagArray['Stats']); $j++) {
					if(strcmp($flagArray['Stats'][$j][0], $flag) === 0 && $flag != "") {
						$stats .= (($stats != "")?", ":" ").$flagArray['Stats'][$j][1];
						break;
					}
				}
				for($j = 0; $j < count($flagArray['Flags']); $j++) {
					if(strcmp($flagArray['Flags'][$j][0], $flag) === 0 && $flag != "") {
						$flags .= (($flags != "")?", ":" ").$flagArray['Flags'][$j][1];
						break;
					}
				}
			}
			if(in_array("ACTIVATE", $artifacts[$i]['F'])) {
				for($j = 0; $j < count($flagArray['Activate']); $j++) {
					if(strcmp($flagArray['Activate'][$j][0], $artifacts[$i]['A'][1]) === 0) {
						$activate = "|Activates for ".$flagArray['Activate'][$j][1]." every ".$artifacts[$i]['A'][2].(($artifacts[$i]['A'][3] != 0) ? " +1d".$artifacts[$i]['A'][3] : "")." turns";
						break;
					}
				}
			}
		}
		$artifacts[$i]['name'] = "The".((substr($name, 0, 1) == " ")?"":" ").$name." ".$artifacts[$i]['N'][2];
		$artifacts[$i]['suffix'] = " ".$nameSuffix;
		$artifacts[$i]['info'] = "Depth ".($artifacts[$i]['W'][1] * 50)."ft |Rarity ".$artifacts[$i]['W'][2]." |Weight ".($artifacts[$i]['W'][3] / 10)."lb |Cost ".$artifacts[$i]['W'][4];
		$artifacts[$i]['resists'] = "|RESISTS".$resists;
		$artifacts[$i]['slays'] = "|SLAYS".$slays;
		$artifacts[$i]['stats'] = "|STATS".$stats;
		$artifacts[$i]['activate'] = $activate;
		$artifacts[$i]['flags'] = $flags;
	}
	return $artifacts;
}
function makeEgoReadable($egos, $objects, $flagArray) {
	$isWeapon = array(16,17,18,20,21,22,23);
	$isArmor = array(30,31,32,33,34,35,36,37,38);
	$isBow = array(19);
	$isCrown = array(33);
	/* Make arrays for different item-groups where all must match all, those with no group are named as before */
	for($i = 0; $i < count($egos); $i++) {
		$name = "";
		$extra = "";
		$activate = "";
		$resists = "";
		$slays = "";
		$stats = "";
		$flags = "";
		for($j = 0; $j < count($objects); $j++) {
			if($objects[$j]['I'][1] == $egos[$i]['T'][1] && ($objects[$j]['I'][2] >= $egos[$i]['T'][2] && $objects[$j]['I'][2] <= $egos[$i]['T'][3])) {
				$name = trim(preg_replace('/[&~]/', '', $objects[$j]['N'][2]))." ".$egos[$i]['N'][2];
				break;
			}
		}
		switch($egos[$i]['X'][2]) {
			case 0:
				$extra = "";
				break;
			case 1:
				$extra = "one random extra sustain";
				break;
			case 2:
				$extra = "one random extra resist";
				break;
			case 3:
				$extra = "one random extra ability";
				break;
		}
		if(is_array($egos[$i]['F'])) {
			foreach($egos[$i]['F'] as $flag) {
				for($j = 0; $j < count($flagArray['Resists']); $j++) {
					if(strcmp($flagArray['Resists'][$j][0], $flag) === 0 && $flag != "") {
						$resists .= (($resists != "")?", ":" ").$flagArray['Resists'][$j][1];
						break;
					}
				}
				for($j = 0; $j < count($flagArray['Slays']); $j++) {
					if(strcmp($flagArray['Slays'][$j][0], $flag) === 0 && $flag != "") {
						$slays .= (($slays != "")?", ":" ").$flagArray['Slays'][$j][1];
						break;
					}
				}
				for($j = 0; $j < count($flagArray['Stats']); $j++) {
					if(strcmp($flagArray['Stats'][$j][0], $flag) === 0 && $flag != "") {
						$stats .= (($stats != "")?", ":" ").$flagArray['Stats'][$j][1];
						break;
					}
				}
				for($j = 0; $j < count($flagArray['Flags']); $j++) {
					if(strcmp($flagArray['Flags'][$j][0], $flag) === 0 && $flag != "") {
						$flags .= (($flags != "")?", ":" ").$flagArray['Flags'][$j][1];
						break;
					}
				}
			}
			if(in_array("ACTIVATE", $egos[$i]['F'])) {
				$activate = "|Activates for resistance";
			}
		}
		$egos[$i]['name'] = $name;
		$egos[$i]['extra'] = $extra;
		$egos[$i]['resists'] = "|RESISTS".$resists;
		$egos[$i]['slays'] = "|SLAYS".$slays;
		$egos[$i]['stats'] = "|STATS".$stats;
		$egos[$i]['activate'] = $activate;
		$egos[$i]['flags'] = $flags;
	}
	return $egos;
}
function infoB($string) {
	preg_match_all('/^B:.+/m', $string, $result, PREG_PATTERN_ORDER);
	for($i = 0; $i < count($result[0]); $i++) {
		$result[0][$i] = explode(':', $result[0][$i]);
	}
	return $result[0];
}
function infoS($string) {
	preg_match_all('/^S:.+/m', $string, $result, PREG_PATTERN_ORDER);
	for($i = 0; $i < count($result[0]); $i++) {
		$result[0][$i] = preg_replace('/S:/', '', $result[0][$i]);
		$result[0][$i] = explode(' | ', $result[0][$i]);
		foreach($result[0][$i] as $s) {
			$infoS[] = $s;
		}
	}
	return $infoS;
}
function infoF($string) {
	preg_match_all('/^F:.+/m', $string, $result, PREG_PATTERN_ORDER);
	for($i = 0; $i < count($result[0]); $i++) {
		$result[0][$i] = preg_replace('/F:/', '', $result[0][$i]);
		$result[0][$i] = explode(' |', $result[0][$i]);
		foreach($result[0][$i] as $f) {
			$infoF[] = trim($f);
		}
	}
	return $infoF;
}
function infoCS($string, $char) {
	$result = (preg_match(sprintf('/^%s:.+/m', $char), $string, $regs)) ? $regs[0] : "";
	return explode(":", $result);
}
function IRClog($Line, $Nick) {
	if(substr($Line, 0, 6) == "NOTICE") {
		$Msg = "=== ";
		$MsgStart = strpos($Line, ":", 5);
		$Msg .= substr($Line, $MsgStart);
	}
	elseif(substr($Line, 0, 1) == ":") {
		$LineX = explode(":", $Line);
		if(strstr($LineX[1], "!")) { //Message not from server
			$NickEnd = strpos($LineX[1], "!");
			$Nick = substr($Line, 1, $NickEnd);
			if(strstr($LineX[1], "PRIVMSG")) { // Normal message
				$MsgStart = strpos($Line, ":", 5);
				$Msg = "<".$Nick."> ".substr($Line, ($MsgStart+1));
			}
			elseif(strstr($LineX[1], "NOTICE")) { // "Notice"
				$MsgStart = strpos($Line, ":", 5);
				$Msg = "*".$Nick."* ".substr($Line, ($MsgStart+1));
			}
			elseif(strstr($LineX[1], "JOIN")) { // User joins
				$Msg = $Nick." has joined the channel.".substr($Line, -2);
			}
			elseif(strstr($LineX[1], "PART")) { // User leaves
				$Msg = $Nick." has left the channel.".substr($Line, -2);
			}
			elseif(strstr($LineX[1], "QUIT")) { // User leaves IRC
				$Msg = $Nick." has left IRC.".substr($Line, -2);
			}
			elseif(strstr($LineX[1], "NICK")) { // User changes nickname
				$MsgStart = strpos($Line, ":", 5);
				$Msg = $Nick." has changed nick to ".substr($Line, ($MsgStart+1));
			}
			else {
				$Msg = $Line;
			}
		}
		else { //Message from server
			$Msg = "=== ";
			$MsgStart = strpos($Line, $Nick);
			$Msg .= substr($Line, $MsgStart);
		}
	}
	elseif(substr($Line, 0, 6) == "PING :") {
		$Msg = "";
	}
	else {
		$Msg = $Line;
	}
	echo $Msg;
}
function commandMon($IRCC, $channelMessage, $com, $monster, $monsterFlags) {
	$name = "";
	switch(strtolower($com[1])) {
		case "melee":
		case "summon":
		case "summons":
		case "spell":
		case "spells":
		case "breath":
		case "breaths":
		case "resist":
		case "resists":
			for($i = 2; $i < count($com); $i++) {
				$name .= (($name != "")?" ":"").trim($com[$i]);
			}
			echo "SPESIFIC: ".$com[1]."\n"; // Spesific command to console
			echo "MONSTER: ".$name."\n"; // Monster search word to console
			break;
		case "search":
			echo "SPESIFIC: ".$com[1]."\n"; // Spesific command to console
			$char = trim(array_pop($com));
			$color = "";
			$realColor = "";
			$blinking = false;
			$searchArray = array("");
			$list = "";
			for($i = 2; $i < count($com); $i++) {
				$color .= (($color != "")?" ":"").strtolower($com[$i]);
			}
			for($i = 0; $i < count($monsterFlags['Color']); $i++) {
				if($monsterFlags['Color'][$i][0] == $color) {
					$realColor = $monsterFlags['Color'][$i][1];
				}
			}
			if($realColor == "") {
				fwrite($IRCC, $channelMessage."No such color\r\n");
				return;
			} elseif($realColor == "ATTR_MULTI") {
				$realColor = "v";
				$blinking = true;
			}
			$searchArray = array("G", $char, $realColor);
			foreach($monster as $monster) {
				$monsterIsBlinking = in_array("ATTR_MULTI", $monster['F']);
				$isSameSymbol = (strcmp($monster['G'][1], $searchArray[1]) === 0);
				$isSameColor = (strcmp($monster['G'][2], $searchArray[2]) === 0);
				if($isSameSymbol && $isSameColor && ($blinking == $monsterIsBlinking)) {
					$list .= (($list != "")?", ":"")."(".$monster['name'].")";
				}
			}
			fwrite($IRCC, $channelMessage.(($list != "")?$list:"Sorry, not found")."\r\n");
			return;
		default:
			for($i = 1; $i < count($com); $i++) {
				$name .= (($name != "")?" ":"").trim($com[$i]);
			}
			echo "MONSTER: ".$name."\n"; // Monster search word to console
			break;
	}
	$numMonstersFound = 0;
	for($i = 0; $i < count($monster); $i++) {
		if(stripos($monster[$i]['name'], $name) !== false) {
			$hits[$numMonstersFound]['number'] = $i;
			$hits[$numMonstersFound]['name'] = $monster[$i]['name'];
			$numMonstersFound++;
		}
		if(strcmp(strtolower($monster[$i]['name']), strtolower($name)) === 0) {
			$hits[0]['number'] = $i;
			$hits[0]['name'] = $monster[$i]['name'];
			$numMonstersFound = 1;
			break; // If exact match
		}
	}
	if($numMonstersFound > 1) { // More than 1 monster found
		$listed = "";
		for($i = 0; $i < $numMonstersFound && $i < 3; $i++) {
			$listed .= " (".$hits[$i]['name'].")";
		}
		fwrite($IRCC, $channelMessage."Which?".$listed."...\r\n");
		return;
	} elseif($numMonstersFound == 0) { // No monsters found
		fwrite($IRCC, $channelMessage."No match\r\n");
		return;
	} else {
		echo "MATCH: ".$hits[0]['number']."|".$hits[0]['name']."\n\n"; //Match to console
	}
	switch(strtolower($com[1])) {
		case "melee":
			fwrite($IRCC, $channelMessage.$hits[0]['name']."\r\n");
			fwrite($IRCC, $channelMessage.(($monster[$hits[0]['number']]['melee'] != "|MELEE ")?$monster[$hits[0]['number']]['melee']:"This monster has no melee attacks")."\r\n");
			break;
		case "summon":
		case "summons":
			fwrite($IRCC, $channelMessage.$hits[0]['name']."\r\n");
			fwrite($IRCC, $channelMessage.(($monster[$hits[0]['number']]['summons'] != "|SUMMONS ")?$monster[$hits[0]['number']]['summons']." ".$monster[$hits[0]['number']]['frequency']:"This monster doesn't summon")."\r\n");
			break;
		case "spell":
		case "spells":
			fwrite($IRCC, $channelMessage.$hits[0]['name']."\r\n");
			fwrite($IRCC, $channelMessage.(($monster[$hits[0]['number']]['spells'] != "|SPELLS ")?$monster[$hits[0]['number']]['spells']." ".$monster[$hits[0]['number']]['frequency']:"This monster doesn't cast spells")."\r\n");
			break;
		case "breath":
		case "breaths":
			fwrite($IRCC, $channelMessage.$hits[0]['name']."\r\n");
			fwrite($IRCC, $channelMessage.(($monster[$hits[0]['number']]['breaths'] != "|BREATHS ")?$monster[$hits[0]['number']]['breaths']." ".$monster[$hits[0]['number']]['frequency']:"This monster doesn't breathe!")."\r\n");
			break;
		case "resist":
		case "resists":
			fwrite($IRCC, $channelMessage.$hits[0]['name']."\r\n");
			fwrite($IRCC, $channelMessage.(($monster[$hits[0]['number']]['resists'] != "|RESISTS ")?$monster[$hits[0]['number']]['resists']:"This monster doesn't resist anything!")."\r\n");
			break;
		default:
			fwrite($IRCC, $channelMessage.$hits[0]['name']."\r\n");
			fwrite($IRCC, $channelMessage.$monster[$hits[0]['number']]['info']."\r\n");
			if($monster[$hits[0]['number']]['melee'] != "|MELEE ") {fwrite($IRCC, $channelMessage.$monster[$hits[0]['number']]['melee']."\r\n");}
			if($monster[$hits[0]['number']]['summons'] != "|SUMMONS ") {fwrite($IRCC, $channelMessage.$monster[$hits[0]['number']]['summons'].(($monster[$hits[0]['number']]['spells'] == "|SPELLS " && $monster[$hits[0]['number']]['breaths'] == "|BREATHS ")?" ".$monster[$hits[0]['number']]['frequency']:"")."\r\n");}
			if($monster[$hits[0]['number']]['spells'] != "|SPELLS ") {fwrite($IRCC, $channelMessage.$monster[$hits[0]['number']]['spells']." ".$monster[$hits[0]['number']]['frequency']."\r\n");}
			if($monster[$hits[0]['number']]['breaths'] != "|BREATHS ") {fwrite($IRCC, $channelMessage.$monster[$hits[0]['number']]['breaths'].(($monster[$hits[0]['number']]['spells'] == "|SPELLS ")?" ".$monster[$hits[0]['number']]['frequency']:"")."\r\n");}
			if($monster[$hits[0]['number']]['flags'] != "| ") {fwrite($IRCC, $channelMessage.$monster[$hits[0]['number']]['flags']."\r\n");}
			break;
	}
	return;
}
function commandArt($IRCC, $channelMessage, $com, $artifact) {
	$name = "";
	for($i = 1; $i < count($com); $i++) {
		$name .= (($name != "")?" ":"").trim($com[$i]);
	}
	$numArtifactsFound = 0;
	for($i = 0; $i < count($artifact); $i++) {
		if(stripos($artifact[$i]['name'], $name) !== false) {
			$hits[$numArtifactsFound]['number'] = $i;
			$hits[$numArtifactsFound]['name'] = $artifact[$i]['name'];
			$numArtifactsFound++;
		}
		if(strcmp(strtolower($artifact[$i]['name']), strtolower($name)) === 0) {
			$hits[0]['number'] = $i;
			$hits[0]['name'] = $artifact[$i]['name'];
			$numArtifactsFound = 1;
			break; // If exact match
		}
	}
	if($numArtifactsFound > 1) { // More than 1 artifact found
		$listed = "";
		for($i = 0; $i < $numArtifactsFound && $i < 3; $i++) {
			$listed .= " (".$hits[$i]['name'].")";
		}
		fwrite($IRCC, $channelMessage."Which?".$listed."...\r\n");
		return;
	} elseif($numArtifactsFound == 0) { // No monsters found
		fwrite($IRCC, $channelMessage."No match\r\n");
		return;
	} else {
		echo "MATCH: ".$hits[0]['number']."|".$hits[0]['name']."\n\n"; //Match to console
	}
	fwrite($IRCC, $channelMessage.$hits[0]['name'].$artifact[$hits[0]['number']]['suffix']."\r\n");
	fwrite($IRCC, $channelMessage.$artifact[$hits[0]['number']]['info']."\r\n");
	if($artifact[$hits[0]['number']]['resists'] != "|RESISTS") {fwrite($IRCC, $channelMessage.$artifact[$hits[0]['number']]['resists']."\r\n");}
	if($artifact[$hits[0]['number']]['slays'] != "|SLAYS") {fwrite($IRCC, $channelMessage.$artifact[$hits[0]['number']]['slays']."\r\n");}
	if($artifact[$hits[0]['number']]['stats'] != "|STATS") {fwrite($IRCC, $channelMessage.$artifact[$hits[0]['number']]['stats']."\r\n");}
	if($artifact[$hits[0]['number']]['activate'] != "") {fwrite($IRCC, $channelMessage.$artifact[$hits[0]['number']]['activate']."\r\n");}
	if($artifact[$hits[0]['number']]['flags'] != "") {fwrite($IRCC, $channelMessage.$artifact[$hits[0]['number']]['flags']."\r\n");}
	return;
}
function commandEgo($IRCC, $channelMessage, $com, $ego) {
	$name = "";
	for($i = 1; $i < count($com); $i++) {
		$name .= (($name != "")?" ":"").trim($com[$i]);
	}
	$numEgosFound = 0;
	for($i = 0; $i < count($ego); $i++) {
		if(stripos($ego[$i]['name'], $name) !== false) {
			$hits[$numEgosFound]['number'] = $i;
			$hits[$numEgosFound]['name'] = $ego[$i]['name'];
			$numEgosFound++;
		}
		if(strcmp(strtolower($ego[$i]['name']), strtolower($name)) === 0) {
			$hits[0]['number'] = $i;
			$hits[0]['name'] = $ego[$i]['name'];
			$numEgosFound = 1;
			break; // If exact match
		}
	}
	if($numEgosFound > 1) { // More than 1 ego found
		$listed = "";
		for($i = 0; $i < $numEgosFound && $i < 3; $i++) {
			$listed .= " (".$hits[$i]['name'].")";
		}
		fwrite($IRCC, $channelMessage."Which?".$listed."...\r\n");
		return;
	} elseif($numEgosFound == 0) { // No monsters found
		fwrite($IRCC, $channelMessage."No match\r\n");
		return;
	} else {
		echo "MATCH: ".$hits[0]['number']."|".$hits[0]['name']."\n\n"; //Match to console
	}
	fwrite($IRCC, $channelMessage.$hits[0]['name']."\r\n");
	if($ego[$hits[0]['number']]['resists'] != "|RESISTS") {fwrite($IRCC, $channelMessage.$ego[$hits[0]['number']]['resists']."\r\n");}
	if($ego[$hits[0]['number']]['slays'] != "|SLAYS") {fwrite($IRCC, $channelMessage.$ego[$hits[0]['number']]['slays']."\r\n");}
	if($ego[$hits[0]['number']]['stats'] != "|STATS") {fwrite($IRCC, $channelMessage.$ego[$hits[0]['number']]['stats']."\r\n");}
	if($ego[$hits[0]['number']]['activate'] != "") {fwrite($IRCC, $channelMessage.$ego[$hits[0]['number']]['activate']."\r\n");}
	if($ego[$hits[0]['number']]['flags'] != "") {fwrite($IRCC, $channelMessage.$ego[$hits[0]['number']]['flags']."\r\n");}
	if($ego[$hits[0]['number']]['extra'] != "") {fwrite($IRCC, $channelMessage.$ego[$hits[0]['number']]['extra']."\r\n");}
	return;
}

?>
