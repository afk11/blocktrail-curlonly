<?php

namespace Afk11\Blocktrail;


use Afk11\MiniRest\RestClient;
use Afk11\MiniRest\RestClientInterface;

class SDK
{
    const AGENT = 'blocktrail-php-curlonly';
    const VERSION = 'v0.0.1';

    /**
     * @var RestClientInterface
     */
    protected $client;

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

        ($this->client = new RestClient($apiKey, $apiSecret, $apiVersion, $apiEndpoint))
            ->setApiKeyQueryString(true)
            ->setUserAgent(self::AGENT)
            ->setClientVersion(self::VERSION)
        ;
    }

    /**
     * @param string $address
     * @return array
     */
    public function address($address) {
        return $this->client->get("address/{$address}");
    }

    /**
     * @param string $address
     * @param int $page
     * @param int $limit
     * @param string $sortDir
     * @return array
     */
    public function addressTransactions($address, $page = 1, $limit = 20, $sortDir = 'asc') {
        $query = [
            'page' => $page,
            'limit' => $limit,
            'sort_dir' => $sortDir
        ];

        return $this->client->get("address/{$address}/transactions", $query);
    }

    /**
     * @param string $address
     * @param int $page
     * @param int $limit
     * @param string $sortDir
     * @return array
     */
    public function addressUnconfirmedTransactions($address, $page = 1, $limit = 20, $sortDir = 'asc') {
        $query = [
            'page' => $page,
            'limit' => $limit,
            'sort_dir' => $sortDir
        ];

        return $this->client->get("address/{$address}/unconfirmed-transactions", $query);
    }

    /**
     * @param string $address
     * @param int $page
     * @param int $limit
     * @param string $sortDir
     * @return array
     */
    public function addressUnspentOutputs($address, $page = 1, $limit = 20, $sortDir = 'asc') {
        $query = [
            'page' => $page,
            'limit' => $limit,
            'sort_dir' => $sortDir
        ];

        return $this->client->get("address/{$address}/unspent-outputs", $query);
    }

    /**
     * @param string[] $addresses
     * @param int $page
     * @param int $limit
     * @param string $sortDir
     * @return array
     */
    public function batchAddressUnspentOutputs($addresses, $page = 1, $limit = 20, $sortDir = 'asc') {
        $query = [
            'page' => $page,
            'limit' => $limit,
            'sort_dir' => $sortDir
        ];

        return $this->client->post("address/unspent-outputs", $query, ['addresses' => $addresses]);
    }

    /**
     * @param string $address
     * @param string $signature
     * @return array
     */
    public function verifyAddress($address, $signature) {
        return $this->client->post("address/{$address}/verify", null, ['signature' => $signature]);
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

        return $this->client->post("all-blocks", $query);
    }

    /**
     * @return array
     */
    public function blockLatest() {
        return $this->client->get("block/latest");
    }

    /**
     * @param string $block
     * @return array
     */
    public function block($block) {
        return $this->client->get("block/{$block}");
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

        return $this->client->post("block/{$block}/transactions", $query);
    }

    /**
     * @param string $txhash
     * @return array
     */
    public function transaction($txhash) {
        return $this->client->get("transaction/{$txhash}");
    }

    /**
     * @param string $address
     * @param int $amount
     * @return array
     */
    public function faucetWithdrawl($address, $amount = 10000) {
        return $this->client->post('faucet/withdrawl', null, [
            "address" => $address,
            "amount" => $amount
        ]);
    }
}