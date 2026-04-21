<?php use DynamicalWeb\Html\Functions; ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>500 — Internal Server Error</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YcnS/1p8FjY2dZMp+DXhPNAiVUto0Wd2sFI" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
        <div class="text-center">
            <h1 class="display-1 fw-bold text-danger">500</h1>
            <p class="lead mb-4">Something went wrong on our end.</p>
            <a href="<?php Functions::printRoute('home'); ?>" class="btn btn-primary">Go Home</a>
        </div>
    </body>
</html>
