<?php

namespace TheCodeMill\OANDA;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class OANDAv20
{
    /**
     * Defines the LIVE API url
     *
     * @const URL_LIVE
     */
    const URL_LIVE = 'https://api-fxtrade.oanda.com';

    /**
     * Defines the PRACTICE API url
     *
     * @const URL_PRACTICE
     */
    const URL_PRACTICE = 'https://api-fxpractice.oanda.com';

    /**
     * Defines the LIVE API environment
     *
     * @const ENV_LIVE
     */
    const ENV_LIVE = 1;

    /**
     * Defines the PRACTICE API environment
     *
     * @const ENV_PRACTICE
     */
    const ENV_PRACTICE = 2;

    /**
     * API environment for current connection
     *
     * @var integer
     */
    protected $apiEnvironment;

    /**
     * API key for current connection
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Build an OANDA v20 API instance
     *
     * @param integer $apiEnvironment Optional environment mode to set on instantiation
     * @param string $apiKey Optional API key to set at instantiation
     * @return void
     */
    public function __construct($apiEnvironment = null, $apiKey = null)
    {
        if ($apiEnvironment !== null) {
            $this->setApiEnvironment($apiEnvironment);
        }

        if ($apiKey !== null) {
            $this->setApiKey($apiKey);
        }
    }

    /**
     * Return the current API environment
     *
     * @return integer
     */
    public function getApiEnvironment()
    {
        return $this->apiEnvironment;
    }

    /**
     * Set the API environment
     *
     * @param integer $apiEnvironment
     * @return TheCodeMill\OANDA\OANDAv20 $this
     */
    public function setApiEnvironment($apiEnvironment)
    {
        $this->apiEnvironment = $apiEnvironment;

        return $this;
    }

    /**
     * Return the current API key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set the API key
     *
     * @param string $apiKey
     * @return TheCodeMill\OANDA\OANDAv20 $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Prepare an HTTP request using a Guzzle client
     *
     * @param string $endpoint API endpoint
     * @param string $method Optional HTTP method
     * @param mixed $data Data to send (encoded) with request
     * @param array $headers Additional headers to send with request
     * @return GuzzleHttp\Psr7\Request
     */
    protected function prepareRequest($endpoint, $method = 'GET', $data = null, $headers = [])
    {
        $headers += [
            'Authorization' => $this->bearerToken(),
            'Content-Type' => 'application/json'
        ];

        // Handle data
        if ($method == 'GET') {
            $endpoint = $this->absoluteEndpoint($endpoint, $data);
            $body = null;
        } else {
            $endpoint = $this->absoluteEndpoint($endpoint);
            $body = ($data !== null) ? $this->jsonEncode($data) : null;
        }

        return new Request($method, $endpoint, $headers, $body);
    }

    /**
     * Send an HTTP request
     *
     * @param GuzzleHttp\Psr7\Request $request
     * @return GuzzleHttp\Psr7\Response
     */
    protected function sendRequest(Request $request)
    {
        $client = new Client;

        return $client->send($request);
    }

    /**
     * Helper method to automatically send a GET request and return the decoded response
     *
     * @param string $endpoint
     * @param array $data Data to send (encoded) with request
     * @param array $headers Additional headers to send with request
     * @return mixed
     */
    protected function makeGetRequest($endpoint, $data = [], $headers = [])
    {
        $request = $this->prepareRequest($endpoint, 'GET', $data, $headers);
        $response = $this->sendRequest($request);

        return $this->jsonDecode($response->getBody());
    }

    /**
     * Return the appropriate API base uri based on connection mode
     *
     * @return string
     */
    protected function baseUri()
    {
        return $this->getApiEnvironment() == static::ENV_LIVE ? static::URL_LIVE : static::URL_PRACTICE;
    }

    /**
     * Parse a complete API url given an endpoint
     *
     * @param string $endpoint
     * @param array $data Optional query string parameters
     * @return string
     */
    protected function absoluteEndpoint($endpoint, $data = [])
    {
        $url = parse_url($endpoint);

        if (isset($url['query'])) {
            parse_str($url['query'], $data);
        }

        return $this->baseUri()
            . '/'
            . trim($url['path'], '/')
            . (!empty($data) ? '?' . http_build_query($data) : '');
    }

    /**
     * Return the bearer token from the current API key
     *
     * @return string
     */
    protected function bearerToken()
    {
        return 'Bearer ' . $this->getApiKey();
    }

    /**
     * Encode data as JSON
     *
     * @param mixed $data
     * @return string
     */
    protected function jsonEncode($data)
    {
        return json_encode($data);
    }

    /**
     * Decode JSON using arrays (not objects)
     *
     * @param string $data
     * @return mixed
     */
    protected function jsonDecode($data)
    {
        return json_decode($data, true);
    }

    /**
     * Get all accounts for current token
     *
     * @return array
     */
    public function getAccounts()
    {
        return $this->makeGetRequest('/v3/accounts');
    }

    /**
     * Get full account details for a particular account id
     *
     * @param string $accountId
     * @return array
     */
    public function getAccount($accountId)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId);
    }

    /**
     * Get an account summary for a particular account id
     *
     * @param string $accountId
     * @return array
     */
    public function getAccountSummary($accountId)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/summary');
    }

    /**
     * Get a list of tradeable instruments for a particular account id
     *
     * @param string $accountId
     * @return array
     */
    public function getAccountInstruments($accountId)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/instruments');
    }

    /**
     * Update the configurable properties of an account
     *
     * @param string $accountId
     * @param array $body
     * @return GuzzleHttp\Psr7\Response
     */
    public function updateAccount($accountId, array $body)
    {
        $request = $this->prepareRequest('/v3/accounts/' . $accountId . '/configuration', 'PATCH', $body);

        return $this->sendRequest($request);
    }

    /**
     * Get an account's changes to a particular account since a particular transaction id
     *
     * @param string $accountId
     * @param string $transactionId
     * @return array
     */
    public function getAccountChanges($accountId, $transactionId)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/changes', [
            'sinceTransactionID' => $transactionId
        ]);
    }
}
