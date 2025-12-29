<?php

    namespace WebApp;

    class Router
    {
        private array $routes = [];

        /**
         * Adds a route
         *
         * @param string $path
         * @param callable $handler
         */
        public function addRoute(string $path, callable $handler): void
        {
            $this->routes[$path] = $handler;
        }

        /**
         * Handles a request
         *
         * @param string $path
         * @return mixed
         */
        public function handle(string $path): mixed
        {
            if (isset($this->routes[$path])) {
                return call_user_func($this->routes[$path]);
            }
            
            return ['error' => 'Route not found', 'code' => 404];
        }

        /**
         * Gets all routes
         *
         * @return array
         */
        public function getRoutes(): array
        {
            return array_keys($this->routes);
        }
    }
