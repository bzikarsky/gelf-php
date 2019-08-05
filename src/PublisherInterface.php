<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gelf;

/**
 * A publisher is responsible for publishing a given GELF-message to one or multiple backend
 */
interface PublisherInterface
{
    /**
     * Publish a message
     *
     * @param MessageInterface $message
     * @throws Exception
     * @return void
     */
    public function publish(MessageInterface $message): void;

    /**
     * Return the default context options
     *
     * @return array
     */
    public function getDefaultContext(): array;

    /**
     * Set an array with context options
     *
     * The context options get added to every message if they don't contain
     * the field yet
     *
     * @param array $context
     * @return PublisherInterface
     */
    public function setDefaultContext(array $context): self;
}
