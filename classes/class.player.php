<?php

class Player {

	private $exists = true;
	
	private $username = NULL;
	private $uuid = NULL;
	private $rankName = NULL;
	private $firstLogin = NULL;
	private $lastLogin = NULL;
	private $lastLogout = NULL;
	private $cached = NULL;
	private $achievements = NULL;

	function __construct($mysqli, $param) {
		
		$funcs = new Funcs($mysqli);
	
		if ($funcs->isUsername($param)) {
			$this->uuid = $funcs->getUUID($param);
		} else if (!$funcs->isUUID($param)) {
			$this->exists = false;
		}
		
		$data = $funcs->fetchPlayerData($this->uuid);
		$this->username = $data["username"];
		$this->uuid = $data["uuid"];
		$this->rankName = $data["rank"];
		$this->firstLogin = $data["firstLogin"];
		$this->lastLogin = $data["lastLogin"];
		$this->lastLogout = $data["lastLogout"];
		$this->cached = $data["cached"];
		$this->achievements = $data["achievements"];
		
	}
	
	function exists() {
		return $this->exists;
	}
	
	function getUUID() {
		return $this->uuid;
	}
	
	function getName() {
		return $this->username;
	}
	
	function toArray() {
		$arr = array(
			"username" =>$this->username, 
			"uuid" => $this->uuid, 
			"rankName" => $this->rankName, 
			"firstLogin" => $this->firstLogin, 
			"lastLogin" => $this->lastLogin, 
			"lastLogout" => $this->lastLogout,
			"cached" => $this->cached,
			"achievements" => $this->achievements
		);
		
		return $arr;
	}
	
	function getData() {
		echo json_encode($this->toArray());
	}

}