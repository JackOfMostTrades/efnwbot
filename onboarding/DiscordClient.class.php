<?php
  require_once('config.php');

  class DiscordClient {

    public static function authRedirect() {
      $auth_url = AUTHORIZATION_ENDPOINT
        . "?client_id=" . CLIENT_ID
        . "&redirect_uri=" . urlencode(REDIRECT_URI)
        . "&response_type=code"
        . "&scope=" . urlencode("identify guilds.join");
      header('Location: ' . $auth_url);
      exit;
    }

    public static function botClient() {
      return new DiscordClient('Bot ' . BOT_TOKEN);
    }

    public static function userClient($code) {
      $client = new DiscordClient(NULL);
      $response = $client->doRequest('POST',
        'application/x-www-form-urlencoded',
        TOKEN_ENDPOINT,
        http_build_query(array(
          'client_id' => CLIENT_ID,
          'client_secret' => CLIENT_SECRET,
          'grant_type' => 'authorization_code',
          'code' => $_GET['code'],
          'redirect_uri' => REDIRECT_URI)));
      if ($response == NULL) {
        return $NULL;
      }
      $client->auth_header = 'Bearer ' . $response->access_token;
      return $client;
    }

    private $curl;
    private $auth_header;

    private function __construct($auth_header) {
      $this->curl = curl_init();
      $this->auth_header = $auth_header;
    }

    function __destruct() {
      curl_close($this->curl);
    }

    private function logMsg($msg) {
      $f = fopen('log/discord.log', 'a');
      fwrite($f, date(DATE_ATOM) . " $msg\n");
      fclose($f);
    }

    private function doRequest($method, $contentType, $url, $body) {
      curl_setopt($this->curl, CURLOPT_URL, $url);

      if ($method == 'POST') {
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
      } else {
        curl_setopt($this->curl, CURLOPT_POST, 0);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
      }

      $headers = array();
      if ($this->auth_header !== NULL) {
        array_push($headers, "Authorization: $this->auth_header");
      }
      if ($contentType !== NULL) {
        array_push($headers, "Content-Type: $contentType");
      }
      curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
      if ($body !== NULL) {
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
      }
      curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($this->curl);
      $response_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
      if ($response_code != 200) {
        $this->logMsg("Error executing request to $url: $response\n");
        return NULL;
      }
      return json_decode($response);
    }

    public function doGet($url) {
      return $this->doRequest('GET', NULL, $url, NULL);
    }
    public function doPost($url, $body) {
      return $this->doRequest('POST', 'application/json', $url,
        json_encode($body));
    }
    public function doPatch($url, $body) {
      return $this->doRequest('PATCH', 'application/json', $url,
        json_encode($body));
    }
  }

