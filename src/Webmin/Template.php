<?php

namespace Webmin;

use Psr\Log\LoggerInterface;

class Template
{
    private \Mustache\Engine $engine;
    private ?LoggerInterface $logger;

    public function __construct(
        array $options = [],
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger;

        $templateDir = $options['dir'];
        $cacheDir = $options['cache_dir'] ?? null;
        $escape = $options['escape'] ?? 'htmlspecialchars';

        try {
            $loader = new \Mustache\Loader\FilesystemLoader(
                $templateDir,
                ['extension' => '.mustache']
            );

            $this->engine = new \Mustache\Engine([
                'loader' => $loader,
                'partials_loader' => $loader,
                'cache' => ($cacheDir && is_dir($cacheDir)) ? $cacheDir : null,
                'escape' => $escape,
            ]);
        } catch (\Error $e) {
            $this->logger?->error(
                'Failed to initialize Mustache engine: ' . $e->getMessage()
            );

            throw new \Exception($e->getMessage());
        }
    }

    public function render(string $name, array $data = []): string
    {
        return $this->engine->render($name, $data);
    }
}
