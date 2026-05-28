<?php use DynamicalWeb\Html\Functions; ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php Functions::printl('title'); ?></title>
        <link rel="stylesheet" href="/css/bootstrap.min.css">
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body>
        <?php Functions::insertSection('sections/navbar.phtml'); ?>

        <main class="container py-5">
            <div class="px-4 py-5 my-5 text-center">
                <h1 class="display-4 fw-bold mb-3"><?php Functions::printl('heading'); ?></h1>
                <div class="col-lg-6 mx-auto">
                    <p class="lead text-muted mb-4"><?php Functions::printl('subheading'); ?></p>
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                        <a href="#features" class="btn btn-primary btn-lg px-4 gap-3"><?php Functions::printl('get_started'); ?></a>
                    </div>
                </div>
            </div>

            <div class="row mt-5 g-4" id="features">
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-6 mb-3">&#9889;</div>
                            <h5 class="card-title"><?php Functions::printl('feature_fast_title'); ?></h5>
                            <p class="card-text text-muted"><?php Functions::printl('feature_fast_desc'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-6 mb-3">&#128295;</div>
                            <h5 class="card-title"><?php Functions::printl('feature_extensible_title'); ?></h5>
                            <p class="card-text text-muted"><?php Functions::printl('feature_extensible_desc'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-6 mb-3">&#128640;</div>
                            <h5 class="card-title"><?php Functions::printl('feature_deploy_title'); ?></h5>
                            <p class="card-text text-muted"><?php Functions::printl('feature_deploy_desc'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php Functions::insertSection('sections/footer.phtml'); ?>

        <script src="/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
