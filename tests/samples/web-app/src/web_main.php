<?php

    use WebApp\Router;
    use WebApp\Response;

    $router = new Router();

    $router->addRoute('/', function() {
        return Response::json([
            'message' => 'Web Application',
            'info' => Response::getPackageInfo()
        ]);
    });

    $router->addRoute('/health', function() {
        return Response::json(['status' => 'healthy']);
    });

    $path = $_SERVER['REQUEST_URI'] ?? '/';
    $result = $router->handle($path);
    
    if (is_array($result)) {
        Response::json($result);
    }
