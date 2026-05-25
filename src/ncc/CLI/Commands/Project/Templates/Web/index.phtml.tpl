<?php use DynamicalWeb\Html\Functions; ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php Functions::printl('title'); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="<?php Functions::printRoute('home'); ?>">${ASSEMBLY_NAME}</a>
            </div>
        </nav>

        <main class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h1 class="display-4 fw-bold mb-3"><?php Functions::printl('heading'); ?></h1>
                    <p class="lead text-muted mb-4"><?php Functions::printl('subheading'); ?></p>
                    <a href="#features" class="btn btn-primary btn-lg"><?php Functions::printl('get_started'); ?></a>
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

        <footer class="bg-dark text-white text-center py-3 mt-auto">
            <small><?php Functions::printl('footer'); ?></small>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
