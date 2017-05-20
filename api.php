<?php 

$appPath = __DIR__;
$config = json_decode(file_get_contents("$appPath/config.json"), true);

define('DEBUG', true);
define('SERVER_IP', $config["server"]["ip"]);
define('PORT', $config["server"]["port"]);

if (!DEBUG){
    if (file_exists("./install")) {
        die("Please remove /install after installation");
    }
}

if (DEBUG) {
    error_reporting(-1);
    ini_set("display_errors", 'On');
} else {
    error_reporting(0);
    ini_set("display_errors", 0);
}


$mysqli = new mysqli(
    $config["sql"]["host"],
   	$config["sql"]["username"],
   	$config["sql"]["password"],
    $config["sql"]["dbname"]
);

require "$appPath/classes/class.config.php";
require "$appPath/classes/class.cache.php";
require "$appPath/classes/class.api.php";
require "$appPath/classes/class.game.php";
require "$appPath/classes/class.server.php";
require "$appPath/classes/class.player.php";
require "$appPath/models/constants.php";
require "$appPath/models/funcs.php";

$api = new API($mysqli, $appPath);
$cache = new cache($mysqli, $appPath);

$uri = $_SERVER["REQUEST_URI"];
$uri = str_replace("?recache&", "?", $uri);
$uri = str_replace("&recache", "", $uri);
$uri = str_replace("?recache", "", $uri);

if ($cache->reCache($uri)) {
    /* Include all classes and api if a new cache has to be created */
	
} else {
    echo $cache->getCache($uri);
}

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));

if ($request[0] == null) {
	echo Constants::getException("404", "exception", "get not found");
	die();
}

$constants = new Constants();

$validRequest = $constants->validateGet($request[0]);
$type = Get::isValidGet($request[0]);
$count = count($request);

$gameManager = new GameManager($mysqli);

switch($type) {
	case -1: 
	
		echo Constants::getException("404", "exception", "get not found");
		
		break;
	case 0: 	
		
		echo "<pre>".$api->name." running version ".$api->version."</pre>";
		
		break;
	case 1:
	
		if ($count < 2 || $count > 3) {
			echo Constants::getException("411", "syntax", "invalid syntax exception");
			break;
		}

		$player = new Player($mysqli, $request[1]);
		if (!$player->exists()) {
			echo Constants::getException("404", "noprofile", "player ".$request[1]." does not exist");
			break;
		}
		
		if ($count == 2) {
			echo $player->getData();
			break;
		} 
				
		if(!$gameManager->isGame($request[2])) {
			echo Constants::getException("404", "nogame", "game ".$request[2]." does not exist");
			break;
		} 
		
		echo $gameManager->getPlayerStatistics($player, $request[2]);
		
		break;
	
	case 2:
	
		if ($count <1 && $count > 5) {
			echo Constants::getException("411", "syntax", "invalid syntax exception");
			break;
		}
				
		if ($count == 1) {
			echo json_encode($gameManager->getAllGames());
			break;
		} else if ($count == 2) {
			if (!$gameManager->isGame($request[1])) {
				echo Constants::getException("404", "nogame", "game ".$request[1]." does not exist");
				break;
			}

			$game = new Game($mysqli, $request[1]);
			echo $game->getData();
			break;
		}
		
		break;
		
	case 3:
	
		if ($count != 2) {
			echo Constants::getException("411", "syntax", "invalid syntax exception");
			break;
		}
		
		$server = new Server($mysqli);
		
		if (strtolower($request[1]) == "unique") {
			echo $server->getUniquePlayers();
			break;
		} else if (strtolower($request[1]) == "online") {
			echo $server->getOnlinePlayers();
			break;
		} else if (strtolower($request[1]) == "achievements") {
			echo $server->getAchievements();
			break;
		} 
		
		echo Constants::getException("404", "syntax", "invalid request type");
		
		break;
}

?>