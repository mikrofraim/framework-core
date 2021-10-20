<?php

declare(strict_types=1);

namespace Mikrofraim\ServiceProvider;

use Mikrofraim\ServiceProvider;

class TwigFilesystemLoader extends ServiceProvider
{
    public function createService(): \Twig\Loader\FilesystemLoader
    {
        $path = $this->config->get('service.twig.templatesPath');

        if (!$path) {
            throw new \Exception('twig template path not set in config');
        }

        if (!\file_exists($path)) {
            throw new \Exception('path does not exist: ' . $path);
        }

        return new \Twig\Loader\FilesystemLoader($path);
    }
}
