<?php

declare(strict_types=1);

namespace Ikuzo\SyliusChronopostPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UnusedVariable
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ikuzo_sylius_chronopost_plugin');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
