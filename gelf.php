<?php

class GELFMessage {

    const VERSION = "1.0";

    private $graylogHostname;
    private $graylogPort;
    private $maxChunkSize;
    
    private $data;

    public function  __construct($graylogHostname, $graylogPort, $maxChunkSize = 'WAN')
    {
        if (!is_numeric($graylogPort)) {
            throw new Exception("Port must be numeric");
        }

        $this->graylogHostname = $graylogHostname;
        $this->graylogPort = $graylogPort;
        switch ($maxChunkSize) {
            case 'WAN':
                $this->maxChunkSize = 1420;
                break;
            case 'LAN':
                $this->maxChunkSize = 8154;
                break;
            default:
                $this->maxChunkSize = $maxChunkSize;
        }
        
        $this->setVersion(self::VERSION);
    }

    private function dataParamSet($dataType) {
        if (isset($this->data[$dataType]) && strlen($this->data[$dataType]) > 0) {
            return true;
        }

        return false;
    }

    private function setVersion($version) {
        $this->data["version"] = $version;
    }

    public function send()
    {
        // Check if all required parameters are set.
        if (!$this->dataParamSet("version") || !$this->dataParamSet("short_message") || !$this->dataParamSet("host")) {
            throw new Exception('Missing required data parameter: "version", "short_message" and "host" are required.');
        }

        // Convert data array to JSON and GZIP.
        $gzippedJsonData = gzcompress(json_encode($this->data));
	
        $sock = stream_socket_client('udp://' . gethostbyname($this->graylogHostname) .':' . $this->graylogPort);

        // Maximum size is 8192 byte. Split to chunks. (GELFv2 supports chunking)
        if (strlen($gzippedJsonData) > $this->maxChunkSize) {
            // Too big for one datagram. Send in chunks.
            $msgId = microtime(true) . rand(0,10000);

            $parts = str_split($gzippedJsonData, $this->maxChunkSize);
            $i = 0;
            foreach($parts as $part) {
                fwrite($sock, $this->prependChunkData($part, $msgId, $i, count($parts)));
                $i++;
            }

        } else {
            // Send in one datagram.
            fwrite($sock, $gzippedJsonData);
        }
    }

    private function prependChunkData($data, $msgId, $seqNum, $seqCnt)
    {
        if (!is_string($data) || $data === '') {
            throw new Exception('Data must be a string and not be empty');
        }

        if (!is_integer($seqNum) || !is_integer($seqCnt) || $seqCnt <= 0) {
            throw new Exception('Sequence number and count must be integer. Sequence count must be bigger than 0.');
        }

        if ($seqNum > $seqCnt) {
            throw new Exception('Sequence number must be bigger than sequence count');
        }

        return pack('CC', 30, 15) . hash('sha256', $msgId, true) . pack('nn', $seqNum, $seqCnt) . $data;
    }

    // Setters / Getters.- Nothing to see here.

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

    public function setFacility($facility)
    {
        $this->data["facility"] = $facility;
    }

    public function setTimestamp($timestamp)
    {
      $this->data["timestamp"] = $timestamp;
    }

    public function setAdditional($key, $value)
    {
      $key = str_replace (" ", "", $key);
      $this->data["_" . $key] = $value;
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

    public function getAdditional()
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
    
}
