<?php

declare(strict_types=1);

namespace Mikrofraim\ServiceProvider;

use Mikrofraim\ServiceProvider;

class TwigEnvironment extends ServiceProvider
{
    public string $alias = 'twig';

    public function createService(\Twig\Loader\FilesystemLoader $twigLoader): \Twig\Environment
    {
        $debug = $this->config->get('service.twig.debug', false);
        $cache = $this->config->get('service.twig.cache', false);
        $cachePath = $this->config->get('service.twig.cachePath');

        $twig = new \Twig\Environment($twigLoader, [
            'cache' => ($cache && $cachePath) ? $cachePath : false,
            'debug' => $debug,
        ]);

        /* add useful globals */
        $twig->addGlobal('global', [
            'server' => $_SERVER ?? null,
            'session' => $_SESSION ?? null,
            'request' => $_REQUEST ?? null,
        ]);

        /* add dump function using vardumper */
        $twig->addFunction(new \Twig\TwigFunction('dump', static function ($variable) {
            $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
            $dumper = new \Symfony\Component\VarDumper\Dumper\HtmlDumper();
            $output = '';
            $dumper->dump($cloner->cloneVar($variable), $output, [
                'maxDepth' => 10,
                'maxStringLength' => 250,
            ]);

            return $output;
        }));

        /* add getenv function for convenient access to the environment */
        // $twig->addFunction(new \Twig\TwigFunction('getenv', static function ($variableName) {
        //     return $_ENV[$variableName];
        // }));

        return $twig;
    }
}
