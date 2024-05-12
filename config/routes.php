<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->import('../src/Controller/Frontend/', type: 'annotation',);
    $routes->import('../src/Controller/Page/', type: 'annotation');
    $routes->import('../src/Components/', type: 'annotation');

    $routes->add('live_component_cms', '/_contao/_components/{_live_component}/{_live_action}')
        ->defaults(['_live_action' => 'get', '_scope' => 'frontend']);
};
