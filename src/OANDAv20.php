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
     * Helper method to automatically send a POST request and return the HTTP response
     *
     * @param string $endpoint
     * @param array $data Data to send (encoded) with request
     * @param array $headers Additional headers to send with request
     * @return mixed
     */
    protected function makePostRequest($endpoint, $data = [], $headers = [])
    {
        $request = $this->prepareRequest($endpoint, 'POST', $data, $headers);

        return $this->sendRequest($request);
    }

    /**
     * Helper method to automatically send a PATCH request and return the HTTP response
     *
     * @param string $endpoint
     * @param array $data Data to send (encoded) with request
     * @param array $headers Additional headers to send with request
     * @return mixed
     */
    protected function makePatchRequest($endpoint, $data = [], $headers = [])
    {
        $request = $this->prepareRequest($endpoint, 'PATCH', $data, $headers);

        return $this->sendRequest($request);
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
     * Get full account details
     *
     * @param string $accountId
     * @return array
     */
    public function getAccount($accountId)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId);
    }

    /**
     * Get an account summary
     *
     * @param string $accountId
     * @return array
     */
    public function getAccountSummary($accountId)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/summary');
    }

    /**
     * Get a list of tradeable instruments for an account
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
     * @param array $data
     * @return GuzzleHttp\Psr7\Response
     */
    public function updateAccount($accountId, array $data)
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/configuration', $data);
    }

    /**
     * Get an account's changes to a particular account since a particular transaction id
     *
     * @param string $accountId
     * @param array $data
     * @return array
     */
    public function getAccountChanges($accountId, array $data)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/changes', $data);
    }

    /**
     * Get candlestick data for an instrument
     *
     * @param string $instrumentName
     * @param array $data
     * @return array
     */
    public function getInstrumentCandles($instrumentName, array $data = [])
    {
        return $this->makeGetRequest('/v3/instruments/' . $instrumentName . '/candles', $data);
    }

    /**
     * Create an order for an account
     *
     * @param string $accountId
     * @param array $data
     * @return GuzzleHttp\Psr7\Response
     */
    public function createOrder($accountId, array $data)
    {
        return $this->makePostRequest('/v3/accounts/' . $accountId . '/orders', $data);
    }

    /**
     * Get a list of orders for an account
     *
     * @param string $accountId
     * @param array $data
     * @return array
     */
    public function getOrders($accountId, $data = [])
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/orders', $data);
    }

    /**
     * Get a list of pending orders for an account
     *
     * @param string $accountId
     * @return array
     */
    public function getPendingOrders($accountId)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/pendingOrders');
    }

    /**
     * Get details of an order
     *
     * @param string $accountId
     * @param string $orderSpecifier
     * @return array
     */
    public function getOrder($accountId, $orderSpecifier)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/orders/' . $orderSpecifier);
    }

    /**
     * Update an order by cancelling and replacing with a new one
     *
     * @param string $accountId
     * @param string $orderSpecifier
     * @param array $data
     * @return GuzzleHttp\Psr7\Response
     */
    public function updateOrder($accountId, $orderSpecifier, array $data)
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/orders/' . $orderSpecifier, $data);
    }

    /**
     * Cancel a pending order
     *
     * @param string $accountId
     * @param string $orderSpecifier
     * @return GuzzleHttp\Psr7\Response
     */
    public function cancelPendingOrder($accountId, $orderSpecifier)
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/orders/' . $orderSpecifier . '/cancel', $data);
    }

    /**
     * Update Client Extensions for an order
     *
     * @param string $accountId
     * @param string $orderSpecifier
     * @param array $data
     * @return GuzzleHttp\Psr7\Response
     */
    public function updateOrderClientExtensions($accountId, $orderSpecifier, array $data)
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/orders/' . $orderSpecifier . '/clientExtensions', $data);
    }

    /**
     * Get a list of trades for an account
     *
     * @param string $accountId
     * @param array $data
     * @return array
     */
    public function getTrades($accountId, $data = [])
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/trades', $data);
    }

    /**
     * Get a list of open trades for an account
     *
     * @param string $accountId
     * @param array $data
     * @return array
     */
    public function getOpenTrades($accountId)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/openTrades');
    }

    /**
     * Get details of a trade
     *
     * @param string $accountId
     * @param string $tradeSpecifier
     * @return array
     */
    public function getTrade($accountId, $tradeSpecifier)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/trades/' . $tradeSpecifier);
    }

    /**
     * Close (partially or fully) an open trade
     *
     * @param string $accountId
     * @param string $tradeSpecifier
     * @param array $data
     * @return GuzzleHttp\Psr7\Response
     */
    public function closeTrade($accountId, $tradeSpecifier, array $data)
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/trades/' . $tradeSpecifier . '/close', $data);
    }

    /**
     * Update the Client Extensions for an open trade
     *
     * @param string $accountId
     * @param string $tradeSpecifier
     * @param array $data
     * @return GuzzleHttp\Psr7\Response
     */
    public function updateTradeClientExtensions($accountId, $tradeSpecifier, array $data)
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/trades/' . $tradeSpecifier . '/clientExtensions', $data);
    }

    /**
     * Create, replace and cancel the dependent orders for an open trade
     *
     * @param string $accountId
     * @param string $tradeSpecifier
     * @param array $data
     * @return GuzzleHttp\Psr7\Response
     */
    public function updateTradeOrders($accountId, $tradeSpecifier, array $data)
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/trades/' . $tradeSpecifier . '/orders', $data);
    }

    /**
     * Get a list of all positions for an account
     *
     * @param string $accountId
     * @return array
     */
    public function getPositions($accountId)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/positions');
    }

    /**
     * Get a list of all open positions for an account
     *
     * @param string $accountId
     * @return array
     */
    public function getOpenPositions($accountId)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/openPositions');
    }

    /**
     * Get details of a single instrument's position in an account
     *
     * @param string $accountId
     * @param string $instrumentName
     * @return array
     */
    public function getInstrumentPosition($accountId, $instrumentName)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/positions/' . $instrumentName);
    }

    /**
     * Close a position on an account
     *
     * @param string $accountId
     * @param string $tradeSpecifier
     * @param array $data
     * @return GuzzleHttp\Psr7\Response
     */
    public function closePosition($accountId, $instrumentName, array $data)
    {
        return $this->makePatchRequest('/v3/accounts/' . $accountId . '/positions/' . $instrumentName . '/close', $data);
    }

    /**
     * Get a paginated list of all transactions on an account
     *
     * @param string $accountId
     * @return array
     */
    public function getTransactions($accountId, array $data = [])
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/transactions', $data);
    }

    /**
     * Get a paginated list of all transactions on an account
     *
     * @param string $accountId
     * @param string $transactionId
     * @return array
     */
    public function getTransaction($accountId, $transactionId)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/transactions/' . $transactionId);
    }

    /**
     * Get a range of transactions on an account
     *
     * @param string $accountId
     * @param array $data
     * @return array
     */
    public function getTransactionRange($accountId, array $data)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/transactions/idrange', $data);
    }

    /**
     * Get a range of transactions since (but not including) a particular id
     *
     * @param string $accountId
     * @param array $data
     * @return array
     */
    public function getTransactionsSince($accountId, array $data)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/transactions/sinceid', $data);
    }

    /**
     * Get pricing information for a list of instruments on an account
     *
     * @param string $accountId
     * @param array $data
     * @return array
     */
    public function getPricing($accountId, array $data)
    {
        return $this->makeGetRequest('/v3/accounts/' . $accountId . '/pricing', $data);
    }
}
