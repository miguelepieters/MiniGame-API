<?php

class Constants {

	public static function getException($code, $type, $description) {
		return json_encode(array('code' => $code, 'type' => $type, 'description' => $description));
	}

	function validateGet($string) {
   		
   		return ($string == "version" || $string == "player" || $string == "game");
   	}
}

abstract class MiniGameEnum {

	private static $constCacheArray = NULL;

	private static function getConstants() {
		if (self::$constCacheArray == NULL) {
			self::$constCacheArray = array();
		}
		
		$calledClass = get_called_class();
		if (!array_key_exists($calledClass, self::$constCacheArray)) {
			$reflect = new ReflectionClass($calledClass);
			self::$constCacheArray[$calledClass] = $reflect->getConstants();
		}
		return self::$constCacheArray[$calledClass];
	}

	public static function isValidGet($name, $strict = false) {
		$constants = self::getConstants();
	
		if ($strict) {
			if (array_key_exists($name, $constants)) {
				return $constants[$name];
			} else {
				return -1;
			}
		}
	
		$keys = array_map('strtolower', array_keys($constants));
		if (in_array(strtolower($name), $keys)) {
			return ($constants[strtolower($name)]);
		} else {
			return -1;
		}
	}
}

abstract class Get extends MiniGameEnum {
	const version = 0;
    const player = 1;
    const game = 2;
    const server = 3;
}