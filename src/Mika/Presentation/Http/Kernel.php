<?php

declare(strict_types=1);

namespace Mika\Presentation\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class Kernel
{
    protected RouteCollection $routes;

    public function handle(Request $request): Response
    {
        if ($response = $this->resolveRouting($request)) {
            return $response;
        }

        return new Response('Welcome to Mika Framework!');
    }

    private function resolveRouting(Request $request): ?Response
    {
        $context = new RequestContext();
        $context->fromRequest($request);
        $matcher = new UrlMatcher($this->routes, $context);

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
}
