<?php

declare(strict_types=1);

namespace Mika\Presentation\Http;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Kernel
{
    protected ?ContainerInterface $container;

    public function handle(Request $request): Response
    {
        if ($response = $this->resolveRouting($request)) {
            return $response;
        }

        return new Response('Welcome to Mika Framework!');
    }

    protected function resolveRouting(Request $request): ?Response
    {
        $routes = $this->loadRoutes();

        $context = new RequestContext();
        $context->fromRequest($request);
        $matcher = new UrlMatcher($routes, $context);

        $request->attributes->add($matcher->match($request->getPathInfo()));

        $controllerResolver = new ControllerResolver();
        $argumentResolver = new ArgumentResolver();

        $controller = $controllerResolver->getController($request);

        if (!$controller) {
            return null;
        }

        $arguments = $argumentResolver->getArguments($request, $controller);

        return call_user_func_array($controller, $arguments);
    }

    protected function container(): ContainerInterface
    {
        if (!$this->container) {
            $this->container = new ContainerBuilder();
        }

        return $this->container;
    }

    protected function loadRoutes(): RouteCollection
    {
        $routes = new RouteCollection();

        $file = (new \ReflectionObject($this))->getFileName();
        $routesDir = dirname($file) . '/../config/routes';
        $files = glob($routesDir . '/*.php');

        foreach ($files as $file) {
            $config = require $file;

            foreach ($config as $key => $value) {
                $routes->add($key, new Route($value[0], ['_controller' => $value[1] . '::' . $value[2]]));
            }
        }

        return $routes;
    }
}
