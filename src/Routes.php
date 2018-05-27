<?php

namespace PhpHelper\Routes;

use League\Route\RouteCollection;
use PhpHelper\Routes\Enums\HttpMethodEnum;
use PhpHelper\Routes\Enums\RouteEnum;
use PhpHelper\Routes\Exceptions\RouteException;

class Routes
{
    const ROUTE_FILE_EXT = 'php';

    private $routes;
    private $routesPath;

    public function __construct(RouteCollection $routes, string $routesPath)
    {
        $this->routes = $routes;
        $this->routesPath = $this->prepareRoutesPath($routesPath);
    }

    public function load(): void
    {
        foreach ($this->getRoutesFileList() as $routeFile) {
            $this->loadRoutesFromFile($routeFile);
        }
    }

    private function prepareRoutesPath(string $configPath): string
    {
        return rtrim($configPath, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * @return string[]
     */
    private function getRoutesFileList(): array
    {
        return glob(sprintf('%s*%s', $this->routesPath, self::ROUTE_FILE_EXT));
    }

    private function loadRoutesFromFile(string $routesFileName): void
    {
        $routes = require_once $routesFileName;
        foreach ($routes as $pattern => $route) {
            if (empty($route[RouteEnum::CONTROLLER])) {
                throw new RouteException(sprintf('Empty route controller for pattern: %s.', $pattern));
            }
            if (empty($route[RouteEnum::ACTION])) {
                throw new RouteException(sprintf('Empty route action for pattern: %s.', $pattern));
            }

            $handler = sprintf('%s::%s', $route[RouteEnum::CONTROLLER], $route[RouteEnum::ACTION]);
            $method = $this->getMethodFromRoute($route);
            $mappedRoute = $this->routes->map($method, $pattern, $handler);
            if (!empty($route[RouteEnum::NAME])) {
                $mappedRoute->setName($route[RouteEnum::NAME]);
            }
        }
    }

    /**
     * @param mixed $route
     * @return string[]
     * @throws RouteException
     */
    private function getMethodFromRoute($route): array
    {
        $method = HttpMethodEnum::GET;
        if (!empty($route[RouteEnum::METHOD])) {
            $method = (array)$route[RouteEnum::METHOD];
            $this->checkMethod($method);
        }

        return $method;
    }

    /**
     * @param string[] $methods
     * @return bool
     * @throws RouteException
     */
    private function checkMethod(array $methods): bool
    {
        foreach ($methods as $method) {
            if (!HttpMethodEnum::isValid($method)) {
                throw new RouteException(sprintf('Invalid route method: %s.', $method));
            }
        }

        return true;
    }

    /**
     * @param string $uriName
     * @param mixed[] $params
     * @return string
     */
    public function path(string $uriName, array $params = []): string
    {
        $path = $this->routes->getNamedRoute($uriName)->getPath();
        $uriSegments = explode('/', ltrim($path, '/'));
        foreach ($uriSegments as $segment) {
            preg_match('#(\{(?P<param>.+)\})#', $segment, $matches);
            if (isset($matches['param']) && isset($params[$matches['param']])) {
                $path = str_replace(
                    sprintf('{%s}', $matches['param']),
                    $params[$matches['param']],
                    $path
                );
            }
        }

        return $path;
    }
}
