<?php

namespace Webmin;

use Psr\Log\LoggerInterface;

final class App
{
    public function __construct(
        public readonly array $config,
        public readonly LoggerInterface $logger,
    ) {
    }
}
