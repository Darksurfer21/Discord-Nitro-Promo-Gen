<?php
require_once 'vendor/autoload.php';


use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Counter {
    public $count = 0;
    public $mu;

    public function __construct() {
        $this->mu = new Mutex();
    }
}

class Mutex {
    public $sem;

    public function __construct() {
        $this->sem = sem_get(1);
    }

    public function lock() {
        sem_acquire($this->sem);
    }

    public function unlock() {
        sem_release($this->sem);
    }
}

function getTimestamp() {
    $timeIDK = date("H:i:s");
    $timestamp = "[\033[90m{$timeIDK}\033[0m]";
    return $timestamp;
}

function gen($proxy, $file) {
    global $counter;
    $client = new Client();

    while (true) {
        $url = "https://api.discord.gx.games/v1/direct-fulfillment";
        $headers = [
            "Content-Type" => "application/json",
            "Sec-Ch-Ua" => "\"Opera GX\";v=\"105\", \"Chromium\";v=\"119\", \"Not?A_Brand\";v=\"24\"",
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36 OPR/105.0.0.0",
        ];

        $data = [
            "partnerUserId" => Uuid::uuid4()->toString(),
        ];

        try {
            $response = $client->post($url, [
                'headers' => $headers,
                'body' => json_encode($data),
                'proxy' => $proxy,  // Set the proxy directly in the Guzzle request
            ]);

            $body = $response->getBody();
            if ($response->getStatusCode() == 200) {
                $result = json_decode($body, true);
                if (isset($result["token"])) {
                    $token = $result["token"];
                    if (!empty($token)) {
                        $counter->mu->lock();
                        $counter->count++;
                        $count = $counter->count;
                        $counter->mu->unlock();

                        $fullPromoLink = "https://discord.com/billing/partner-promotions/1180231712274387115/{$token}\n";
                        
fwrite($file, $fullPromoLink);
                        

                        echo getTimestamp() . " \033[32m(+)\033[0m Generated Promo Link {$count}: https://discord.com/*****" . substr($token, 0, 3) . substr($token, -3) . "\n";
                    }
                }
            } elseif ($response->getStatusCode() == 429) {
                echo getTimestamp() . " \033[33m(!)\033[0m You are being rate-limited!\n";
            } else {
                echo getTimestamp() . " \033[31m(-)\033[0m Request failed : " . $response->getStatusCode() . "\n";
            }
        } catch (RequestException $ex) {
            echo getTimestamp() . " \033[31m(-)\033[0m Request failed : " . $ex->getMessage() . "\n";
        }

        sleep(5);
    }
}

function main() {
    global $counter;

    $file = fopen("promos.txt", "a");
    if ($file === false) {
        echo getTimestamp() . " \033[31m(-)\033[0m Error opening promos file\n";
        return;
    }

    $proxies = file_get_contents("proxies.txt");
    if ($proxies === false) {
        echo getTimestamp() . " \033[31m(-)\033[0m Error reading proxies file\n";
        fclose($file);
        return;
    }
    $proxyList = explode("\n", $proxies);

    $counter = new Counter();

    echo getTimestamp() . " \033[34m(+)\033[0m Enter Number Of Threads : ";
    $numThreads = (int) readline();

    for ($i = 0; $i < $numThreads; $i++) {
        $proxy = !empty($proxyList) ? $proxyList[$i % count($proxyList)] : "";
        gen($proxy, $file);
    }

    fclose($file);
}

main();

?>
