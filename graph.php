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
    LocationRewriter,
    Visitor\FlagDependencies,
    Visitor\RemoveDependenciesSubGraph,
};
use Innmind\Url\{
    Url,
    Path,
};

new class extends Main {
    protected function main(Environment $env, OperatingSystem $os): void
    {
        $package = bootstrap(
            $http = $os->remote()->http(),
            $resolver = new UrlResolver,
            $filesystem = $os->filesystem()->mount(
                $os->status()->tmp()
            )
        );

        $graph = new Graph;
        $visualize = new Visualize(new class($env->workingDirectory()) implements LocationRewriter {
            private $workingDirectory;

            public function __construct(Path $workingDirectory)
            {
                $this->workingDirectory = $workingDirectory;
            }

            public function __invoke(Url $location): Url
            {
                return $location->withPath(Path::of(
                    \str_replace($this->workingDirectory->toString(), '', $location->path()->toString())
                ));
            }
        });
        $flag = new FlagDependencies($http, $resolver, $filesystem);
        $remove = new RemoveDependenciesSubGraph;

        $node = $graph($package);
        $flag($node);
        $remove($node);

        $os
            ->control()
            ->processes()
            ->execute(
                Command::foreground('dot')
                    ->withShortOption('Tsvg')
                    ->withShortOption('o', 'graph.svg')
                    ->withInput(
                        $visualize($node)
                    )
            )
            ->wait();
    }
};
