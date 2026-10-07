<?php

namespace Webmin;

use Psr\Log\LoggerInterface;

final class App
{
    public readonly Database $db;
    public readonly Template $template;

    public function __construct(
        public readonly array $config,
        public readonly LoggerInterface $logger,
    ) {
        $this->db = new Database(
            $config['database']['dsn'],
            $logger
        );

        $this->template = new Template(
            $config['template'],
            $logger,
            [
                'app' => $config['app'],
            ]
        );
    }
}
