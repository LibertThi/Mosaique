<?php
define("USERS_REQUEST_URL", "https://api.github.com/users?per_page=100");
define("IMG_PATH", "F:\img");
define("FETCH_LIMIT", 30000);

class Fetch extends Threaded{
    private $url;
    
    public function __construct(string $url){
        $this->url = $url;
    }

    private function getUsersInfo($url){
        // initiate curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // ONLY IN DEV ENVIRONMENT !!!
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, "LibertThi:monmotdepassededingo");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // Execute the curl command
        try{
            $body = curl_exec($ch);
        }
        catch(CURLException $e){
            $body = 'Curl timeout';
        }
        
        // Returns the error if encountered
        if (curl_errno($ch)) {
            print 'Error:' . curl_error($ch);
            $body = null;         
        }
        // Close curl
        curl_close ($ch);
        return $body;         
    }

    public function run(){
        $json = $this->getUsersInfo($this->url);
        $usersArray = json_decode($json);
        foreach ($usersArray as $user){
            $id = $user->id;
            $avatarUrl = $user->avatar_url;
            $localImgPath = IMG_PATH . "\\$id.png";
            if (!file_exists($localImgPath)){
                copy($avatarUrl, $localImgPath);
                echo "Copied $id\n";
            }
            else{
                echo "Skipped $id : already exists\n";
            }
        }
    }
}
// create directory if needed
if (!file_exists(IMG_PATH)){
	mkdir(IMG_PATH);
}

// create a pool of workers
$pool = new Pool(16);

// start at user 0
$i = 0;
// fetch until set limit or disk 80% full
while(($i < FETCH_LIMIT) and 
(round(disk_free_space(IMG_PATH) / disk_total_space(IMG_PATH) * 100) > 20)){
    $nextUrl = USERS_REQUEST_URL . "&since=$i";
    $pool->submit(new Fetch($nextUrl));
    $i += 100;
}

while ($pool->collect());
$pool->shutdown();
?>