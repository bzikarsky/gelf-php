<?php

class GELFMessage {

    private $graylogHostname;
    private $graylogPort;
    
    private $data;

    public function  __construct($graylogHostname, $graylogPort)
    {
        if (!is_numeric($graylogPort)) {
            throw new Exception("Port must be numeric");
        }

        $this->graylogHostname = $graylogHostname;
        $this->graylogPort = $graylogPort;
    }

    private function dataParamSet($dataType) {
        if (isset($this->data[$dataType]) && strlen($this->data[$dataType]) > 0) {
            return true;
        }

        return false;
    }

    public function send()
    {
        // Check if all required parameters are set.
        if (!$this->dataParamSet("short_message") || !$this->dataParamSet("host")) {
            print_r($this->data);
            throw new Exception('Missing required data parameter: "short_message" and "host" are required.');
        }

        // Convert data array to JSON and GZIP.
        $gzippedJsonData = gzcompress(json_encode($this->data));

        // Send.
        $sock = stream_socket_client('udp://' . gethostbyname($this->graylogHostname) .':' . $this->graylogPort);
        fwrite($sock, $gzippedJsonData);
    }

    // Setters / Getters.

    public function setShortMessage($message)
    {
        $this->data["short_message"] = $message;
    }

    public function setFullMessage($message)
    {
        $this->data["full_message"] = $message;
    }

    public function setHost($host)
    {
        $this->data["host"] = $host;
    }

    public function setLevel($level)
    {
        $this->data["level"] = $level;
    }

    public function setType($type)
    {
        $this->data["type"] = $type;
    }

    public function setFile($file)
    {
        $this->data["file"] = $file;
    }

    public function setLine($line)
    {
        $this->data["line"] = $line;
    }

    public function getShortMessage()
    {
        return isset($this->data["short_message"]) ? $this->data["short_message"] : null;
    }

    public function getFullMessage()
    {
        return isset($this->data["full_message"]) ? $this->data["full_message"] : null;
    }

    public function getHost()
    {
        return isset($this->data["host"]) ? $this->data["host"] : null;
    }

    public function getLevel()
    {
        return isset($this->data["level"]) ? $this->data["level"] : null;
    }

    public function getType()
    {
        return isset($this->data["type"]) ? $this->data["type"] : null;
    }

    public function getFile()
    {
        return isset($this->data["file"]) ? $this->data["file"] : null;
    }

    public function getLine()
    {
        return isset($this->data["line"]) ? $this->data["line"] : null;
    }
    
}