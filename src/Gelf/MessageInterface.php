<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf;

/**
 * This interface defines the minimum amount of method any Message implementation
 * must provide to be used by the publisher or transports.
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
interface MessageInterface
{
    /**
     * Returns the GELF version of the message
     * 
     * @return string
     */
    public function getVersion();

    /**
     * Returns the host of the message
     *
     * @return string
     */
    public function getHost();

    /**
     * Returns the short text of the message
     *
     * @return string
     */
    public function getShortMessage();

    /**
     * Returns the full text of the message
     *
     * @return string
     */
    public function getFullMessage();

    /**
     * Returns the timestamp of the message
     *
     * @return float
     */
    public function getTimestamp();

    /**
     * Returns the log level of the message
     * By setting $psrStyle to true the message will comply
     * to the Psr\Log\LogLevel constants
     *
     * @param boolean $psrStyle
     * @return integer|string
     */
    public function getLevel($psrStyle = false);

    /**
     * Returns the facility of the message
     *
     * @return string
     */
    public function getFacility();

    /**
     * Returns the file of the message
     *
     * @return string
     */
    public function getFile();

    /**
     * Returns the the line of the message
     *
     * @return string
     */
    public function getLine();

    /**
     * Returns the value of the additional field of the message
     *
     * @param string $key
     * @return mixed
     */
    public function getAdditional($key);

    /**
     * Returns all additional fields as an array
     *
     * @return array
     */
    public function getAllAdditionals();

    /**
     * Converts the message to an array
     *
     * @return array
     */
    public function toArray();
}
