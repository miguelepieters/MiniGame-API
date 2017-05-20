<?php

class Funcs {
	
	private $mysqli;
	
	function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	function onlinePlayers($host = "127.0.0.1", $port = 25565) {
	
		if (@$_SESSION["con"]["time"] > time()) {
			return $_SESSION["con"]["data"];
		}
		
		$h = @fsockopen($host, $port, $errno, $errstr, 3);
		
		if (!$h) {
			return "Error $errno ($errstr)";
		}
		
		fwrite($h, "\xFE");
		
		list($motd, $p, $mp) = explode("\xA7", mb_convert_encoding(substr(fread($h, 1024), 3), 'auto', 'UCS-2'));
		return $p;
	}

	
	function isValidGame($string) {
	
		$stmt = $this->mysqli->prepare(
			"SELECT `name` 
			FROM `games` 
			WHERE `callsign` = ? 
			LIMIT 1"
		) or die ("Error: " . mysqli_error($this->mysqli));
		
		$stmt->bind_param("s", $string);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;	
		$stmt->close();
				
		return ($num_returns == 1);
	}
	
	function getGame($callsign) {
	
		$stmt = $this->mysqli->prepare("SELECT 
			`callsign`, 
			`name`, 
			`uniqueplayers` 
			FROM `games` 
			WHERE `callsign` = ? 
			LIMIT 1"
		) or die ("Error: " . mysqli_error($this->mysqli));
		
		$stmt->bind_param("s", $callsign);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($id, $name, $uniqueplayers);
		while ($stmt->fetch()){
			$data = array(
				'callsign' => $callsign, 
				'name' => $name, 
				'uniqueplayers' => $uniqueplayers
			);
		}
		$stmt->close();
		return $data;
	}
	
	function isUsername($string) {
		
		$stmt = $this->mysqli->prepare("SELECT 
			`id` 
			FROM `players` 
			WHERE `username` = ? 
			LIMIT 1"
		) or die ("Error: " . mysqli_error($this->mysqli));
		
		$stmt->bind_param("s", $string);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;	
		$stmt->close();
				
		return ($num_returns == 1);
	}
	
	function isUUID($string) {
	
		$stmt = $this->mysqli->prepare("SELECT 
			`id` 
			FROM 
			`players` 
			WHERE 
			`uuid` = ? 
			LIMIT 1"
		) or die ("Error: " . mysqli_error($this->mysqli));
		
		$stmt->bind_param("s", $string);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;	
		$stmt->close();
				
		return ($num_returns == 1);
	}
	
	function getUUID($string) {
		
		$stmt = $this->mysqli->prepare("SELECT 
			`uuid` 
			FROM `players` 
			WHERE `username` = ? 
			LIMIT 1"
		) or die ("Error: " . mysqli_error($this->mysqli));
		
		$stmt->bind_param("s", $string);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($uuid);
		while ($stmt->fetch()){
			return $uuid;
		}
		$stmt->close();
	}
	
	function fetchPlayerData($uuid) {
	
		$achievements = $this->fetchPlayerAchievements($uuid);
		$data = NULL;
		
		$stmt = $this->mysqli->prepare("SELECT  
			`username`, 
			`uuid`, 
			`rank`, 
			`firstlogin`, 
			`lastlogin`, 
			`lastlogout`, 
			`cached` 
			FROM `players` 
			WHERE `uuid` = ? 
			LIMIT 1"
		) or die ("Error: " . mysqli_error($this->mysqli));
		
		$stmt->bind_param("s", $uuid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($username, $uuid, $rank, $firstlogin, $lastlogin, $lastlogout, $cached);
		while ($stmt->fetch()){
			$data = array(
				'username' => $username, 
				'uuid' => $uuid, 
				'rank' => $rank, 
				'firstLogin' => $firstlogin, 
				'lastLogin' => $lastlogin, 
				'lastLogout' => $lastlogout, 
				'cached' => $cached, 
				'achievements' => $achievements
			);
		}
		$stmt->close();
		return $data;
	}
	
	function fetchPlayerAchievements($uuid) {
	
		$row = array();
	
		$stmt = $this->mysqli->prepare("SELECT 
			`achievement`, 
			`progress`, 
			`unlocked` 
			FROM `player_achievements` 
			WHERE `uuid` = ?"
		) or die ("Error: " . mysqli_error($this->mysqli));
		
		$stmt->bind_param("s", $uuid);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($achievement, $progress, $unlocked);
		while ($stmt->fetch()){
			$row[] = array(
				"name" => $achievement, 
				"progress" => $progress, 
				"unlocked" => $unlocked
			);
		}
		$stmt->close();
		return ($row);
	}
	
	function fetchAchievements($game) {
		$row = array();
	
		$stmt = $this->mysqli->prepare("SELECT 
			`name`, 
			`publicname`, 
			`description`, 
			`stages`, 
			`secret`, 
			`disabled`, 
			`rewardtype`, 
			`rewardcount` 
			FROM `achievements` 
			WHERE (`game` = ? OR `game` = 'Global')
			AND `achievements`.`secret` <> 1"
		) or die ("Error: " . mysqli_error($this->mysqli));
		
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($name, $publicname, $description, $stages, $secret, $disabled, $rewardtype, $rewardcount);
		while ($stmt->fetch()){
			$row[] = array(
				"name" => $name, 
				"publicname" => $publicname, 
				"description" => $description, 
				"stages" => $stages, 
				"secret" => $secret, 
				"disabled" => $disabled, 
				"rewardtype" => $rewardtype, 
				"rewardcount" => $rewardcount
			);
		}
		$stmt->close();
		return ($row);
	}
	
	function fetchAchievementsServerSpecific($player, $game) {
	
		$d_uuid = $player->getUUID();
		$d_game = NULL;
		$d_total_points = 0;
		$d_games_played = 0;
		$d_victories = 0;
		$d_kills = 0;
		$d_deaths = 0;
		$d_cached = 0;
		$d_firstLogin = 0;
		$d_lastLogin = 0;
		$d_title = 0;
		
		$dat = array();
	
		$stmt = $this->mysqli->prepare("SELECT  
			`player_data`.`UUID`, 
            `player_data`.`game`, 
            `player_data`.`total_points`, 
            `player_data`.`games_played`, 
            `player_data`.`victories`, 
            `player_data`.`kills`, 
            `player_data`.`deaths`, 
        	`player_data`.`cached`, 
            `player_data`.`firstLogin`, 
            `player_data`.`lastLogin`, 
            `player_data`.`title`,
			`player_achievements`.`achievement`, 
            `player_achievements`.`progress`, 
            `player_achievements`.`unlocked`
			FROM  `player_data`, `player_achievements` 
			WHERE  `achievement` IN 
				( 
					SELECT  `name` 
					FROM  `achievements` 
					WHERE  (`game` =  ? OR `game` =  'Global') 
				) 
			AND `player_data`.`UUID` = `player_achievements`.`UUID` 
			AND `player_data`.`UUID` = ? 
			AND `player_data`.`game` = ? "
		) or die ("Error: " . mysqli_error($this->mysqli));
		
		$stmt->bind_param("sss", $game, $d_uuid, $game);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;	
		$stmt->bind_result(
			$uuid, 
			$game, 
			$total_points, 
			$games_played, 
			$victories, 
			$kills, 
			$deaths, 
			$cached, 
			$firstLogin, 
			$lastLogin, 
			$title, 
			$achievement, 
			$progress,
			$unlocked
		);
		
		if ($num_returns == 0) {
			$error = array(
				'code' => '404',
				'type' => 'noprofile',
				'description' => "no ".strtolower($game)." profile exists for ".$player->getName()
			);
			
			return $error;
		}
		
		while ($stmt->fetch()) {
			if ($game == NULL) {
				$uuid = $d_uuid;
				$game = $d_game;
				$total_points = $d_total_points;
				$games_played = $d_games_played;
				$victories = $d_victories;
				$kills = $d_kills;
				$deaths = $d_deaths;
				$cached = $d_cached;
				$firstLogin = $d_firstLogin;
				$lastLogin = $d_lastLogin;
				$title = $d_title;
			}
			
			$dat[] = array('name' => $achievement, 'progress' => $progress, 'unlocked' => $unlocked);
			
		}
		$stmt->close();
		
		$array = array(
			'UUID' => $uuid,
			'game' => $game,
			'total_points' => $total_points,
			'games_played' => $games_played,
			'victories' => $victories,
			'kills' => $kills,
			'deaths' => $deaths,
			'cached' => $cached,
			'firstLogin' => $firstLogin,
			'lastLogin' => $lastLogin,
			'title' => $title, 
			'achievements' => array_merge($dat));
						
		return $array;
	}
	
	function getAllGames() {
		$row = array();
	
		$stmt = $this->mysqli->prepare("SELECT 
			`callsign`, 
			`name` 
			FROM 
			`games`"
		) or die ("Error: " . mysqli_error($this->mysqli));
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($callsign, $name);
		while ($stmt->fetch()){
			$row[] = array($callsign => $name);
		}
		$stmt->close();
		return ($row);
	}	
	
	function getUniquePlayers() {
		$data = 0;
	
		$stmt = $this->mysqli->prepare("SELECT 
			SUM(`uniqueplayers`) 
			FROM `games`"
		) or die ("Error: " . mysqli_error($this->mysqli));
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($uniquePlayers);
		while ($stmt->fetch()){
			$data = $uniquePlayers;
		}
		$stmt->close();
		return json_encode(array("count" => $data));
	}
	
	function getAllAchievements() {
		$row = array();
	
		$stmt = $this->mysqli->prepare("SELECT 
			`name`, 
			`publicname`, 
			`description`, 
			`stages`, 
			`secret`, 
			`disabled`, 
			`rewardtype`, 
			`rewardcount` 
			FROM `achievements`
			WHERE `secret` <> 1"
		) or die ("Error: " . mysqli_error($this->mysqli));
		
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($name, $publicname, $description, $stages, $secret, $disabled, $rewardtype, $rewardcount);
		while ($stmt->fetch()){
			$row[] = array(
				"name" => $name, 
				"publicname" => $publicname, 
				"description" => $description, 
				"stages" => $stages, 
				"secret" => $secret, 
				"disabled" => $disabled, 
				"rewardtype" => $rewardtype, 
				"rewardcount" => $rewardcount
			);
		}
		
		$stmt->close();
		return (json_encode($row));
	}
	
}
