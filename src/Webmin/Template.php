<?php

namespace Webmin;

use Mustache;

/**
 * Simple wrapper around Mustache template engine.
 */
class Template
{
    /** @var object Underlying Mustache engine instance */
    private $engine;

    /**
     * Constructor
     *
     * Options:
     *  - template_dir: path to templates directory
     *  - cache_dir: path to mustache cache (optional)
     *  - escape: escape callable name (default: 'htmlspecialchars')
     *
     * @param array $options
     * @throws \Exception
     */
    public function __construct(array $options = [])
    {
        $templateDir = $options['dir'];
        $cacheDir    = $options['cache_dir'] ?? null;
        $escape      = $options['escape'] ?? 'htmlspecialchars';
        try {
            $loader = new \Mustache\Loader\FilesystemLoader($templateDir, ['extension' => '.mustache']);

            $this->engine = new \Mustache\Engine([
                'loader' => $loader,
                'cache'  => ($cacheDir && is_dir($cacheDir)) ? $cacheDir : null,
                'escape' => $escape,
            ]);
            return;
            }
        catch (\Error $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Render a template file by name (without extension).
     * @param string $name
     * @param array $data
     * @return string
     */
    public function render(string $name, array $data = []): string
    {
        return $this->engine->render($name, $data);
    }

    /**
     * Return the underlying engine instance (for advanced use).
     * @return object
     */
    public function getEngine()
    {
        return $this->engine;
    }
}
