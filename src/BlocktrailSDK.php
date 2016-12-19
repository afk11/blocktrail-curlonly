<?php

namespace Afk11\Blocktrail;

use Afk11\Blocktrail\Exception\EndpointSpecificError;
use Afk11\Blocktrail\Exception\GenericHTTPError;
use Afk11\Blocktrail\Exception\GenericServerError;
use Afk11\Blocktrail\Exception\InvalidCredentials;
use Afk11\Blocktrail\Exception\MissingEndpoint;
use Afk11\Blocktrail\Exception\ObjectNotFound;
use Afk11\Blocktrail\Exception\UnknownEndpointSpecificError;
use Afk11\MiniRest\BadResponseException;
use Afk11\MiniRest\RestClient;
use Afk11\MiniRest\RestClientInterface;

class BlocktrailSDK
{
    const AGENT = 'blocktrail-php-curlonly';
    const VERSION = 'v0.0.1';

    /**
     * @var RestClientInterface
     */
    protected $client;

    /**
     * @var bool
     */
    private $verboseErrors = false;

    /**
     * BlocktrailSDK constructor.
     * @param string $apiKey
     * @param string $apiSecret
     * @param string $network
     * @param bool $testnet
     * @param string $apiVersion
     * @param null $apiEndpoint
     */
    public function __construct($apiKey, $apiSecret, $network = 'BTC', $testnet = false, $apiVersion = 'v1', $apiEndpoint = null)
    {
        if ($apiEndpoint === null) {
            if ($testnet) {
                $network = 't' . $network;
            }

            if ($network !== 'BTC' && $network !== 'tBTC') {
                throw new \RuntimeException('network unsupported');
            }

            $apiEndpoint = getenv('BLOCKTRAIL_SDK_API_ENDPOINT') ?: "https://api.blocktrail.com";
            $apiEndpoint .= "/{$apiVersion}/{$network}";
        }

        ($this->client = new RestClient($apiKey, $apiSecret, $apiEndpoint))
            ->setApiKeyQueryString(true)
            ->setUserAgent(self::AGENT)
            ->setClientVersion(self::VERSION)
        ;
    }

    // BTC/Sat conversion functions taken from upstream

    /**
     * convert a Satoshi value to a BTC value
     *
     * @param int       $satoshi
     * @return float
     */
    public static function toBTC($satoshi)
    {
        return bcdiv((int)(string)$satoshi, 100000000, 8);
    }

    /**
     * convert a Satoshi value to a BTC value and return it as a string
     *
     * @param int       $satoshi
     * @return string
     */
    public static function toBTCString($satoshi)
    {
        return sprintf("%.8f", self::toBTC($satoshi));
    }

    /**
     * convert a BTC value to a Satoshi value
     *
     * @param float     $btc
     * @return string
     */
    public static function toSatoshiString($btc)
    {
        return bcmul(sprintf("%.8f", (float)$btc), 100000000, 0);
    }

    /**
     * convert a BTC value to a Satoshi value
     *
     * @param float     $btc
     * @return string
     */
    public static function toSatoshi($btc)
    {
        return (int)self::toSatoshiString($btc);
    }

    /**
     * @param bool $setting
     * @return $this
     */
    public function setVerboseErrors($setting)
    {
        $this->verboseErrors = $setting;
        return $this;
    }

    /**
     * @param int $httpResponseCode
     * @return array
     * @throws EndpointSpecificError
     * @throws GenericHTTPError
     * @throws GenericServerError
     * @throws InvalidCredentials
     * @throws MissingEndpoint
     * @throws ObjectNotFound
     * @throws UnknownEndpointSpecificError
     * @throws \Exception
     */
    private function badResponseError($httpResponseCode, $data)
    {
        if ($httpResponseCode == 400 || $httpResponseCode == 403) {
            if (isset($data['msg'])) {
                throw new EndpointSpecificError(!is_string($data['msg']) ? json_encode($data['msg']) : $data['msg'], $data['code']);
            } else {
                throw new UnknownEndpointSpecificError(Blocktrail::EXCEPTION_UNKNOWN_ENDPOINT_SPECIFIC_ERROR);
            }
        } elseif ($httpResponseCode == 401) {
            throw new InvalidCredentials(Blocktrail::EXCEPTION_INVALID_CREDENTIALS, $httpResponseCode);
        } elseif ($httpResponseCode == 404) {
            if (isset($response['msg']) && $response['msg'] === "Endpoint Not Found") {
                throw new MissingEndpoint(Blocktrail::EXCEPTION_MISSING_ENDPOINT, $httpResponseCode);
            } else {
                throw new ObjectNotFound(Blocktrail::EXCEPTION_OBJECT_NOT_FOUND, $httpResponseCode);
            }
        } elseif ($httpResponseCode == 500) {
            throw new GenericServerError(Blocktrail::EXCEPTION_GENERIC_SERVER_ERROR . "\nServer Response: " . (isset($response['msg']) ? $response['msg'] : '*nothing*'), $httpResponseCode);
        } else {
            throw new GenericHTTPError(Blocktrail::EXCEPTION_GENERIC_HTTP_ERROR . "\nServer Response: " . (isset($response['msg']) ? $response['msg'] : '*nothing*'), $httpResponseCode);
        }
    }

    /**
     * @param string $method
     * @param string $url
     * @param null $query
     * @param array $body
     * @return array
     */
    private function request($method, $url, $query = null, array $body = [])
    {
        try {
            $response = $this->client->request($method, $url, $query, $body);
            return $response;
        } catch (BadResponseException $e) {
            return $this->badResponseError($e->getCurlInfo()['http_code'], $e->getResult());
        }
    }

    /**
     * @param string $url
     * @param null|array $query
     * @return array
     */
    private function get($url, $query = null)
    {
        return $this->request(RestClient::GET, $url, $query);
    }

    /**
     * @param string $url
     * @param null|array $query
     * @param array $body
     * @return array
     */
    private function post($url, $query, array $body = [])
    {
        return $this->request(RestClient::POST, $url, $query, $body);
    }

    /**
     * @param string $address
     * @return array
     */
    public function address($address)
    {
        return $this->get("address/{$address}");
    }

    /**
     * @param string $address
     * @param int $page
     * @param int $limit
     * @param string $sortDir
     * @return array
     */
    public function addressTransactions($address, $page = 1, $limit = 20, $sortDir = 'asc')
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
            'sort_dir' => $sortDir
        ];

        return $this->get("address/{$address}/transactions", $query);
    }

    /**
     * @param string $address
     * @param int $page
     * @param int $limit
     * @param string $sortDir
     * @return array
     */
    public function addressUnconfirmedTransactions($address, $page = 1, $limit = 20, $sortDir = 'asc')
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
            'sort_dir' => $sortDir
        ];

        return $this->get("address/{$address}/unconfirmed-transactions", $query);
    }

    /**
     * @param string $address
     * @param int $page
     * @param int $limit
     * @param string $sortDir
     * @return array
     */
    public function addressUnspentOutputs($address, $page = 1, $limit = 20, $sortDir = 'asc')
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
            'sort_dir' => $sortDir
        ];

        return $this->get("address/{$address}/unspent-outputs", $query);
    }

    /**
     * @param string[] $addresses
     * @param int $page
     * @param int $limit
     * @param string $sortDir
     * @return array
     */
    public function batchAddressUnspentOutputs($addresses, $page = 1, $limit = 20, $sortDir = 'asc')
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
            'sort_dir' => $sortDir
        ];

        return $this->post("address/unspent-outputs", $query, ['addresses' => $addresses]);
    }

    /**
     * @param string $address
     * @param string $signature
     * @return array
     */
    public function verifyAddress($address, $signature)
    {
        $response = $this->post("address/{$address}/verify", null, ['signature' => $signature]);
        return $response;
    }

    /**
     * @param int $page
     * @param int $limit
     * @param string $sortDir
     * @return array
     */
    public function allBlocks($page = 1, $limit = 20, $sortDir = 'asc')
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
            'sort_dir' => $sortDir
        ];

        return $this->get("all-blocks", $query);
    }

    /**
     * @return array
     */
    public function blockLatest()
    {
        return $this->get("block/latest");
    }

    /**
     * @param string $block
     * @return array
     */
    public function block($block)
    {
        return $this->get("block/{$block}");
    }

    /**
     * @param string $block
     * @param int $page
     * @param int $limit
     * @param string $sortDir
     * @return array
     */
    public function blockTransactions($block, $page = 1, $limit = 20, $sortDir = 'asc')
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
            'sort_dir' => $sortDir
        ];

        return $this->get("block/{$block}/transactions", $query);
    }

    /**
     * @param string $txhash
     * @return array
     */
    public function transaction($txhash)
    {
        return $this->get("transaction/{$txhash}");
    }

    /**
     * @param string $address
     * @param int $amount
     * @return array
     */
    public function faucetWithdrawl($address, $amount = 10000)
    {
        return $this->post('faucet/withdrawl', null, [
            "address" => $address,
            "amount" => $amount
        ]);
    }
}
