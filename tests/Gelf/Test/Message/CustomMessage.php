<?php
namespace Gelf\Test\Message;

use Gelf\Message;

class CustomMessage extends Message
{
    /** @var string */
    protected $additionalPrefix;

    /**
     * @return string
     */
    public function getAdditionalPrefix()
    {
        return $this->additionalPrefix;
    }

    /**
     * @param string $additionalPrefix
     *
     * @return static
     */
    public function setAdditionalPrefix($additionalPrefix)
    {
        $this->additionalPrefix = $additionalPrefix;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function setAdditional($key, $value)
    {
        return parent::setAdditional("{$this->getAdditionalPrefix()}{$key}", $value);
    }
}