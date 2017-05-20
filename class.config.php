<?php

class config
{
    public $serverId = 1;              
    private $MiniGameMYSQLI;
    private $pluginName;
    
    /**
     * @param $mysqli
     * @param $plugin
     */
    function __construct($mysqli, $plugin)
    {
        $this->MiniGameMYSQLI = $mysqli;
        $this->pluginName = $plugin;
    }
    
    /**
     * @param string $option
     * @param string $value
     * @param string $plugin
     * @return bool
     */
    public function setDefault($option, $value, $plugin = "this")
    {
        if ($plugin == "this")
            $plugin = $this->pluginName;
        if (!$this->configExist($option, $plugin)) {
            $this->set($option, $value, $plugin);
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * @param string $option
     * @param string $plugin
     * @return bool
     */
    public function configExist($option, $plugin = "this")
    {
        if ($plugin == "this")
            $plugin = $this->pluginName;
            
        $mysqli = $this->MiniGameMYSQLI;
        $serverId = $this->serverId;
        $stmt = $mysqli->stmt_init();
        $num_returns = 0;
        
        if ($stmt->prepare("SELECT count(*) FROM games")) {
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $num_returns = $stmt->num_rows;	
            $stmt->close();
        }

        if ($num_returns > 0) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * @param string $option
     * @param string $value
     * @param string $plugin
     * @return bool
     */
    public function set($option, $value, $plugin = "this")
    {
        $value = json_encode($value);
        if ($plugin == "this")
            $plugin = $this->pluginName;
        $mysqli = $this->MiniGameMYSQLI;
        $stmt = $mysqli->stmt_init();
        $configExist = $this->configExist($option, $plugin);
        /* Update or Insert new config? */
        if ($configExist)
            $query = "UPDATE BlueStats_config SET `value`=? WHERE `server_id`=? and `plugin`=? AND `option`=?";
        else
            $query = "INSERT INTO BlueStats_config (`server_id`, `option`, `plugin`, `value`) VALUES (?, ?, ?, ?)";
        if ($stmt->prepare($query)) {
            if ($configExist)
                $stmt->bind_param("siss", $value, $this->serverId, $plugin, $option);
            else
                $stmt->bind_param("isss", $this->serverId, $option, $plugin, $value);
            $stmt->execute();
            $stmt->close();
            return true;
        }
        return false;
    }
    /**
     * @param $option
     * @param string $plugin
     * @return bool|mixed
     */
    public function get($option, $plugin = "this")
    {
        if ($plugin == "this")
            $plugin = $this->pluginName;
        $mysqli = $this->MiniGameMYSQLI;
        $stmt = $mysqli->stmt_init();
        $query = "SELECT value FROM BlueStats_config WHERE `server_id`=? and `plugin`=? AND `option`=?";
        if ($stmt->prepare($query)) {
            $stmt->bind_param("iss", $this->serverId, $plugin, $option);
            $stmt->execute();
            $stmt->bind_result($output);
            $stmt->fetch();
            $stmt->close();
            return json_decode($output, true);
        }
        return false;
    }
}