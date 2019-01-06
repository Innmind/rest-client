<?php
declare(strict_types = 1);

require __DIR__.'/vendor/autoload.php';

use Innmind\CLI\{
    Main,
    Environment,
};
use Innmind\OperatingSystem\OperatingSystem;
use function Innmind\Rest\Client\bootstrap;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Server\Control\Server\Command;
use Innmind\ObjectGraph\{
    Graph,
    Visualize,
};

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): void
    {
        $package = bootstrap(
            $os->remote()->http(),
            new UrlResolver,
            $os->filesystem()->mount(
                $os->status()->tmp()
            )
        );

        $graph = new Graph;
        $visualize = new Visualize;

        $os
            ->control()
            ->processes()
            ->execute(
                Command::foreground('dot')
                    ->withShortOption('Tsvg')
                    ->withShortOption('o', 'graph.svg')
                    ->withInput(
                        $visualize($graph($package))
                    )
            )
            ->wait();
    }
};
