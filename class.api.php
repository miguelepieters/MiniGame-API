<?php

class API
{
    public $version = "Beta 1.0";
    public $name = "MiniGame API";
    public $appPath = "";
    public $config;
    public $mysqli;
    public $request = array();
    
    function __construct($mysqli, $appPath)
    {
        $this->setUpGlobals();
        $this->mysqli = $mysqli;
        $this->config = new config($mysqli, $this->name);
        $this->appPath = $appPath;
    }
    
    private function setUpGlobals()
    {
        foreach ($_GET as $key => $value) {
            $this->request["get"][$key] = urldecode($value);
        }
        foreach ($_POST as $key => $value) {
            $this->request["post"][$key] = urldecode($value);
        }
    }
}