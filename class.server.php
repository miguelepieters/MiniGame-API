<?php

class Server {
	
	private $mysqli = NULL;
	private $funcs = NULL;
	
	function __construct($mysqli) {
		$this->mysqli = $mysqli;
		
		$funcs = new Funcs($this->mysqli);
		$this->funcs = $funcs;
	}
	
	function getOnlinePlayers() {
		
		$online = $this->funcs->onlinePlayers(SERVER_IP, 25565);
				
		if (!is_numeric($online)) {
			return json_encode(array(
				'code' => '404',
				'type' => 'noserver',
				'description' => "the server is currently offline"
			));
		}
		
		return json_encode(array('count' => $online));
	}
	
	function getUniquePlayers() {
		return $this->funcs->getUniquePlayers();
	}
	
	function getAchievements() {
		return $this->funcs->getAllAchievements();
	}
}

?>	