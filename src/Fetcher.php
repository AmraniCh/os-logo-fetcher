<?php

namespace AmraniCh\OsLogoFetcher;

use Curl\Curl;

class Fetcher
{
    /**
     * @var Curl
     */
    private $curl; 

    /**
     * @var string
     */
    private $imagesBaseUrl = "https://github.com/EgoistDeveloper/operating-system-logos/blob/master/src/24x24";

    /**
     * @var string
     */
    private $aliasesJsonFileUrl = "https://raw.githubusercontent.com/EgoistDeveloper/operating-system-logos/master/src/alpha3-list.json";

    /**
     * @var array
     */
    private static $aliases;

    /**
     * Cache URLs with response HTTP status code.
     * @var array<string, int>
     */
    private static $cacheBag = [];

    public function __construct()
    {
        $c = $this->curl = new Curl;
        $c->setFollowLocation(true);

        // if os aliases static variable is null, do a fetch
        if (!self::$aliases) {
            $c->get($this->aliasesJsonFileUrl);
            $json = $c->response;
            $jsonDecoded = json_decode($json, true);
            if (!$jsonDecoded) {
                throw new \RuntimeException("Cannot decode json data fetched by the following URL: " . $this->aliasesJsonFileUrl);
            }
            // lowercase all os names
            $jsonDecoded = array_map('strtolower', $jsonDecoded);
            self::$aliases = $jsonDecoded;
        }
    }

    /**
     * Closes cURL connection handle on object destruction.
     */
    public function __destruct()
    {
        $this->curl->close();
    }

    /**
     * Returns the logo image URL on success, and an empty string 
     * if cannot find the corresponding logo image in the repository.
     */
    public function fetch(string $osName): ?string
    {
        $osName = strtolower(trim($osName));

        if (!in_array($osName, self::$aliases)) {
            return '';
        }

        $alias = array_search($osName, self::$aliases);
        $logoUrl = sprintf("%s/%s.png?raw=true", $this->imagesBaseUrl, $alias);

        if (!$this->testUrlResponseIsOk($logoUrl)) {
            return '';
        }

        return $logoUrl;
    }

    private function testUrlResponseIsOk(string $url): bool
    {
        if (array_key_exists($url, self::$cacheBag)) {
            return self::$cacheBag[$url] === 200;
        }

        $c = $this->curl;
        $c->get($url);

        $statusCode = $c->httpStatusCode;

        self::$cacheBag[$url] = $statusCode;

        return $statusCode === 200;
    }
}
