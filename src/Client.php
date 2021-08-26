<?php

namespace Onetoweb\Parcelpro;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Cookie\CookieJar;
use Onetoweb\Parcelpro\Exception\{RequestException, InputException, FileException};

/**
 * Parcel Pro Api Client
 * 
 * @author Jonathan van 't Ende <jvantende@onetoweb.nl>
 * @copyright Onetoweb B.V.
 * 
 * @link https://login.parcelpro.nl/api/docs/#introduction
 */
class Client
{
    const BASE_URL = 'https://login.parcelpro.nl/';
    
    /**
     * @var int
     */
    private $userId;
    
    /**
     * @var string
     */
    private $apiKey;
    
    /**
     * @param int $userId
     * @param string $apiKey
     */
    public function __construct(int $userId, string $apiKey)
    {
        $this->userId = $userId;
        $this->apiKey = $apiKey;
        
        $this->client = new GuzzleClient([
            'base_uri' => self::BASE_URL,
        ]);
    }
    
    /**
     * Send request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data = null
     *
     * @return array
     */
    private function request(string $method, string $endpoint, array $data = null)
    {
        $options = [
            RequestOptions::HEADERS => [
                'Cache-Control' => 'no-cache',
                'Connection' => 'close',
                'Content-Type' => 'application/json',
            ],
        ];
        
        if($data !== null) {
            $options[RequestOptions::JSON] = $data;
        }
        
        $result = $this->client->request($method, $endpoint, $options);
        
        $contents = $result->getBody()->getContents();
        
        return json_decode($contents, true);
    }
    
    /**
     * Send a GET request
     *
     * @param string $endpoint
     * @param array $data = null
     *
     * @return array
     */
    private function get(string $endpoint, array $data = null)
    {
        return $this->request('GET', $endpoint, $data);
    }
    
    /**
     * Send a POST request
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return array
     */
    private function post(string $endpoint, array $data)
    {
        return $this->request('POST', $endpoint, $data);
    }
    
    /**
     * Send a PUT request
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return array
     */
    private function put(string $endpoint, array $data)
    {
        return $this->request('PUT', $endpoint, $data);
    }
    
    /**
     * Send a DELETE request
     *
     * @param string $endpoint
     *
     * @return array
     */
    private function delete(string $endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }
    
    /**
     * Create HmacSha256
     * 
     * @param array $data
     * 
     * @return string
     */
    private function createHmacSha256(array $data)
    {
        return hash_hmac('sha256', implode('', $data), $this->apiKey);
    }
    
    /**
     * Get time
     * 
     * @return string
     */
    private function getDatetime()
    {
        $datetime = new \Datetime();
        
        return $datetime->format('Y-m-d H:i:s');
    }
    
    /**
     * Check required
     *
     * @param array $fields
     * @param array $data
     *
     * @throws InputException if field is not set
     */
    private function checkRequired(array $fields, array $data)
    {
        foreach ($fields as $field) {
            
            if(!isset($data[$field])) {
                throw new InputException("input data must contain the field: '$field'");
            }
        }
    }
    
    /**
     * Validate Api Key.
     * 
     * @link https://login.parcelpro.nl/api/docs/#authentication
     * 
     * @return array
     */
    public function validateApikey()
    {
        $date = $this->getDatetime();
        
        return $this->post('/api/validate_apikey.php', [
            'GebruikerId' => $this->userId,
            'Datum' => $date,
            'HmacSha256' => $this->createHmacSha256([$this->userId, $date]),
        ]);
    }
    
    /**
     * Create Account
     * 
     * @link https://login.parcelpro.nl/api/docs/#operation--account-aanmaken
     * 
     * @param array $data
     * 
     * @return array
     */
    public function createAccount(array $data)
    {
        $date = $this->getDatetime();
        
        $this->checkRequired(['Email'], $data);
        
        $data['GebruikerId'] = $this->userId;
        $data['Datum'] = $date;
        $data['HmacSha256'] = $this->createHmacSha256([$this->userId, $date, $data['Email']]);
        
        return $this->post('/api/create_account.php', $data);
        
    }
    
    /**
     * Check if account exists
     * 
     * @link https://login.parcelpro.nl/api/docs/#operation--account-bestaat
     * 
     * @param string $email
     * 
     * @return array
     */
    public function accountExists(string $email)
    {
        return $this->get('/api/account_exists.php', [
            'GebruikerId' => $this->userId,
            'Email' => $email,
            'HmacSha256' => $this->createHmacSha256([$this->userId, $email]),
        ]);
    }
    
    /**
     * Get shipment types
     * 
     * @link https://login.parcelpro.nl/api/docs/#operation--type-zendingen-opvragen
     * 
     * @return array
     */
    public function getShipmentTypes()
    {
        $date = $this->getDatetime();
        
        return $this->get('/api/type.php', [
            'GebruikerId' => $this->userId,
            'Datum' => $date,
            'HmacSha256' => $this->createHmacSha256([$this->userId, $date]),
        ]);
    }
    
    /**
     * Get shipment types
     * 
     * @link https://login.parcelpro.nl/api/docs/#operation--uitreiklocaties
     * 
     * @param array $data
     * 
     * @return array
     */
    public function getPickupPoints(array $data)
    {
        $date = $this->getDatetime();
        
        $this->checkRequired(['Postcode', 'Nummer', 'Straat'], $data);
        
        $data['GebruikerId'] = $this->userId;
        $data['Datum'] = $date;
        $data['HmacSha256'] = $this->createHmacSha256([$this->userId, $date, $data['Postcode'], $data['Nummer'], $data['Straat']]);
        
        return $this->get('/api/uitreiklocatie.php', $data);
    }
    
    /**
     * Create shipment
     * 
     * @link https://login.parcelpro.nl/api/docs/#operation--nieuwe-zending
     * 
     * @param array $data
     * 
     * @return array
     */
    public function createShipment(array $data)
    {
        $date = $this->getDatetime();
        
        $this->checkRequired(['Postcode'], $data);
        
        $data['GebruikerId'] = $this->userId;
        $data['Datum'] = $date;
        
        if (isset($data['PostcodeAfzender'])) {
            $data['HmacSha256'] = $this->createHmacSha256([$this->userId, $date, $data['PostcodeAfzender'],  $data['Postcode']]);
        } else {
            $data['HmacSha256'] = $this->createHmacSha256([$this->userId, $date, $data['Postcode']]);
        }
        
        return $this->post('/api/zending.php', $data);
    }
    
    /**
     * Get label url
     * 
     * @param string $shipmentId
     * 
     * @return string
     */
    public function getLabelUrl(string $shipmentId)
    {
        return self::BASE_URL . "api/label.php?GebruikerId={$this->userId}&ZendingId=$shipmentId&HmacSha256=" . $this->createHmacSha256([$this->userId, $shipmentId]);
    }
    
    /**
     * Get label contents
     *
     * @param string $shipmentId
     *
     * @return string base64 encoded
     */
    public function getLabelContents(string $shipmentId)
    {
        $url = $this->getLabelUrl($shipmentId);
        
        $client = new GuzzleClient();
        $response = $client->request('GET', $url, [
            RequestOptions::COOKIES => new CookieJar()
        ]);
        
        if ($response->getStatusCode() != 200) {
            
            throw new RequestException("failed to download label from: $url", $resonse->getStatusCode());
            
        }
        
        return base64_encode($response->getBody()->getContents());
    }
    
    /**
     * Save label
     * 
     * @param string $shipmentId
     * @param string $filename
     */
    public function saveLabel(string $shipmentId, string $filename)
    {
        $contents = base64_decode($this->getLabelContents($shipmentId));
        
        if (!is_writable(dirname($filename))) {
            throw new FileException("file: $filename is not writable");
        }
        
        if (file_put_contents($filename, $contents) === false) {
            throw new FileException("file: $filename could not be saved");
        }
    }
    
    /**
     * Get shipments
     * 
     * @link https://login.parcelpro.nl/api/docs/#operation--zendingen
     * 
     * @param array $data = []
     * 
     * @return array
     */
    public function getShipments(array $data = [])
    {
        $date = $this->getDatetime();
        
        $data['GebruikerId'] = $this->userId;
        $data['Datum'] = $date;
        $data['HmacSha256'] = $this->createHmacSha256([$this->userId, $date]);
        
        return $this->get('/api/zendingen.php', $data);
    }
    
    /**
     * Print label
     * 
     * @link https://login.parcelpro.nl/api/docs/#operation--label-afdrukken
     * 
     * @param string $shipmentId
     * @param bool $pdf = true
     * 
     * @return null
     */
    public function printLabel(string $shipmentId, bool $pdf = true)
    {
        $date = $this->getDatetime();
        
        $data = [
            'GebruikerId' => $this->userId,
            'Datum' => $date,
            'ZendingId' => $shipmentId,
            'PrintPdf' => (int) $pdf,
            'HmacSha256' => $this->createHmacSha256([$this->userId, $shipmentId]),
        ];
        
        return $this->get('/api/label.php', $data);
    }
    
    /**
     * Create trigger
     * 
     * @link https://login.parcelpro.nl/api/docs/#operation--status-terugmelding
     * 
     * @param string $url
     * @param string $status = 'afgedrukt'
     * @param string $data = ''
     * 
     * @return array
     */
    public function createTrigger(string $url, string $status = 'afgedrukt', string $data = '')
    {
        $date = $this->getDatetime();
        
        $data = [
            'GebruikerId' => $this->userId,
            'Datum' => $date,
            'Status' => $status,
            'Url' => $url,
            'Data' => $data,
            'HmacSha256' => $this->createHmacSha256([$this->userId, $date]),
        ];
        
        return $this->post('/api/triggers.php', $data);
    }
}