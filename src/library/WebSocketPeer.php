<?php
namespace Core
{
  class WebSocketPeer
  {
    private $handshaked = false;
    private $socket = null;
    private $ip = null;
    private $port = null;
    private $timestamp = 0;
    private $requestHeaders = array();

    public function __construct($server)
    {
      if ($this->socket = socket_accept($server)) {
        if (@socket_getpeername($this->socket, $ip, $port)) {
          $this->$ip = $ip;
          $this->$port = $port;
          $this->timestamp = time();
        }
      }
    }

    public function getIP()
    {
      return $this->ip;
    }

    public function getPort()
    {
      return $this->port;
    }

    public function getSocket()
    {
      return $this->socket;
    }

    public function isHandshaked()
    {
      return $this->handshaked;
    }

    public function send($text)
    {
      return @socket_write($this->socket, $text);
    }

    public function doHandshake()
    {
      $bytes = @socket_recv($this->socket, $data, 2048, MSG_DONTWAIT);
      if ($bytes === false) {
        return true;
      }

      return $this->handleHandshake($data);
    }

    public function getHeader($parameter)
    {
      return (isset($this->requestHeaders[$parameter])) ? $this->requestHeaders[$parameter] : '';
    }

    private function handleHandshake($clientHeaders)
    {
      $this->requestHeaders = array();
      $lines = explode("\n", $clientHeaders);
      foreach ($lines as $line) {
        if (strpos($line, ":") !== false) {
          $header = explode(":", $line, 2);
          $this->requestHeaders[strtolower(trim($header[0]))] = trim($header[1]);
        } elseif (stripos($line, "get ") !== false) {
          preg_match("/GET (.*) HTTP/i", $line, $reqResource);
          $this->requestHeaders['get'] = trim($reqResource[1]);
          //$conn->path = $this->requestHeaders['get'];
        }
      }

      if (!$this->getHeader('sec-websocket-key') || !$this->getHeader('sec-websocket-version') || $this->getHeader('sec-websocket-version') != "13") {
        socket_close($this->socket);
        return false;
      }

      $acceptKey = sha1($this->getHeader('sec-websocket-key') . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11');

      $rawToken = '';
      for ($i = 0; $i < 20; $i++) {
        $rawToken .= chr(hexdec(substr($acceptKey, $i * 2, 2)));
      }
      $handshakeToken = base64_encode($rawToken) . "\r\n";

      //ws://develop.rayfung.hk:8081
      $handshakeResponse = array();
      $handshakeResponse[] = "HTTP/1.1 101 Web Socket Protocol Handshake";
      $handshakeResponse[] = "Upgrade: websocket";
      $handshakeResponse[] = "Connection: Upgrade";
      $handshakeResponse[] = "Sec-WebSocket-Version: 13";
      $handshakeResponse[] = "Server: Socket";
      $handshakeResponse[] = "Sec-WebSocket-Accept: $handshakeToken";
      $handshakeResponse = implode("\r\n", $handshakeResponse) . "\r\n";

  		if (socket_write($this->socket, $handshakeResponse, strlen($handshakeResponse)) !== FALSE) {
        $this->handshaked = true;
      }
  		return true;
    }
  }
}
?>
