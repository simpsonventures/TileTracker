<?php

class TileTracker
{
    public $COOKIE_STORE = './cookiestore.txt';
    public $UUID_STORE = './uuidstore.txt';
    public $USER_UUID;
    public $CLIENT_UUID;
    public $USER_UUID_EXPIRE;

    private $USER;
    private $PASS;
    private $API_URL_SCAFFOLD = 'https://production.tile-api.com/api/v1';
    private $DEFAULT_APP_ID = 'ios-tile-production';
    private $DEFAULT_APP_VERSION = '2.69.0.4123';
    private $DEFAULT_LOCALE = 'en-US';

    private $CURL;

    function __construct($user, $pass)
    {
        $this->USER = $user;
        $this->PASS = $pass;
        $this->CURL = curl_init();

        $UUID = unserialize(file_get_contents($this->UUID_STORE));
        if (isset($UUID) && is_array($UUID)) {
            if (round(($UUID['expiry'] - (time() * 1000)) / 1000 / 60) < 1) {
                unset($UUID);
                $this->newClientSession();
            } else {
                $this->USER_UUID = $UUID['user_uuid'];
                $this->CLIENT_UUID = $UUID['client_uuid'];
                $this->USER_UUID_EXPIRE = $UUID['expiry'];
            }
        } else {
            $this->newClientSession();
        }
    }

    private function createClientUUID()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }

    public function TileRequest($uri, $reuse = true, $data = 0, $type = null)
    {

        curl_setopt($this->CURL, CURLOPT_URL, $this->API_URL_SCAFFOLD . $uri);

        $data = (is_array($data)) ? http_build_query($data) : $data;

        if ($type === 'put') {
            curl_setopt($this->CURL, CURLOPT_CUSTOMREQUEST, "PUT");
        } else if ($type === 'post') {
            curl_setopt($this->CURL, CURLOPT_POST, true);
        } else {
            curl_setopt($this->CURL, CURLOPT_HTTPGET, true);
        }

        $headers = array(
            "Tile_app_id: " . $this->DEFAULT_APP_ID,
            "Tile_app_version: " . $this->DEFAULT_APP_VERSION,
            "Tile_client_uuid: " . $this->CLIENT_UUID
        );

        if (!empty($data)) {
            curl_setopt($this->CURL, CURLOPT_POSTFIELDS, $data);
            array_push($headers, "Content-Length: " . strlen($data));
        }

        curl_setopt($this->CURL, CURLOPT_ENCODING, '');
        curl_setopt($this->CURL, CURLOPT_TCP_FASTOPEN, true);
        curl_setopt($this->CURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->CURL, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->CURL, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->CURL, CURLOPT_COOKIESESSION, true);
        curl_setopt($this->CURL, CURLOPT_COOKIEJAR, $this->COOKIE_STORE);
        curl_setopt($this->CURL, CURLOPT_COOKIEFILE, $this->COOKIE_STORE);
        $exec = curl_exec($this->CURL);

        // todo capture 401 errors as invalid credentials

        if ($reuse === false) {
            curl_close($this->CURL);
            $this->CURL = curl_init();
        }

        return $exec;
    }

    private function newClientSession()
    {
        $this->CLIENT_UUID = $this->createClientUUID();
        $data['app_id'] = $this->DEFAULT_APP_ID;
        $data['app_version'] = $this->DEFAULT_APP_VERSION;
        $data['locale'] = $this->DEFAULT_LOCALE;
        $this->TileRequest('/clients/' . $this->CLIENT_UUID, false, $data, 'put');

        $session_data['email'] = $this->USER;
        $session_data['password'] = $this->PASS;
        $client = $this->TileRequest('/clients/' . $this->CLIENT_UUID . '/sessions', false, $session_data, 'post');
        $client_decoded = json_decode($client, true);

        if ($client_decoded) {
            $UUID['expiry'] = $client_decoded['result']['session_expiration_timestamp'];
            $UUID['user_uuid'] = $client_decoded['result']['user']['user_uuid'];
            $UUID['client_uuid'] = $this->CLIENT_UUID;
            $this->USER_UUID = $UUID['user_uuid'];
            $this->USER_UUID_EXPIRE = $UUID['expiry'];
            file_put_contents($this->UUID_STORE, serialize($UUID));
        }

    }

}