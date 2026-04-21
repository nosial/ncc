<?php use DynamicalWeb\Html\Functions; ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>500 — Internal Server Error</title>
        <link rel="stylesheet" href="/css/bootstrap.min.css">
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body>
        <?php Functions::insertSection('navbar'); ?>

        <main class="d-flex align-items-center justify-content-center flex-grow-1">
            <div class="text-center">
                <h1 class="display-1 fw-bold text-danger">500</h1>
                <p class="lead mb-4">Something went wrong on our end.</p>
                <a href="<?php Functions::printRoute('home'); ?>" class="btn btn-primary">Go Home</a>
            </div>
        </main>

        <?php Functions::insertSection('footer'); ?>

        <script src="/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
