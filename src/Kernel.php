<?php

namespace Dinargab\LibraryBot;

use Dinargab\LibraryBot\Domain\Event\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;


    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass($this);
    }


    public function process(ContainerBuilder $container): void
    {
        if ( ! $container->has(EventDispatcherInterface::class)) {
            return;
        }

        $dispatcherDefinition = $container->findDefinition(EventDispatcherInterface::class);

        $taggedServices = $container->findTaggedServiceIds('library.event_observer');
        foreach ($taggedServices as $id => $tags) {
            $dispatcherDefinition->addMethodCall('attach', [new Reference($id)]);
        }
    }
}
