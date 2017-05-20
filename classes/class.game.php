<?php

class Game {
	
	private $funcs = NULL;
	
	private $callsign = NULL;
	private $name = NULL;
	private $uniqueplayers = 0;
	
	function __construct($mysqli, $callsign) {
	
		$this->funcs = new Funcs($mysqli);
		
		$data = $this->funcs->getGame($callsign);
		
		$this->callsign = $data["callsign"];
		$this->name = $data["name"];
		$this->uniqueplayers = $data["uniqueplayers"];
	}
	
	function toArray() {
	
		$arr = array( 
			"uniqueplayers" => $this->uniqueplayers,
			"achievements" => $this->funcs->fetchAchievements($this->name)
		);
		
		return $arr;
	}
	
	function getData() {
		echo json_encode($this->toArray());
	}
}

class GameManager {

	private $funcs = NULL;
	
	function __construct($mysqli) {
		$this->funcs = new Funcs($mysqli);
	}
	
	function isGame($game) {
	
		if ($this->funcs == NULL) {
			return false;
		} else {
			return $this->funcs->isValidGame(strtoupper($game));
		}
	}
	
	function getAllGames() {
		return $this->funcs->getAllGames();
	}
	
	function getPlayerStatistics($player, $game) {
		return json_encode($this->funcs->fetchAchievementsServerSpecific($player, strtoupper($game)));
	}
}