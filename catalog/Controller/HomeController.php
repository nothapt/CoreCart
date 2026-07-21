<?php
declare(strict_types=1);

namespace CoreCart\Catalog\Controller;

/**
 * Frontend Home Controller
 *
 * Handles the storefront landing page.
 */
class HomeController
{
    /**
     * Show the homepage.
     */
    public function index(): void
    {
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>CoreCart</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { background: #f8f9fa; }
                .hero { padding: 80px 0; text-align: center; }
                .hero h1 { font-size: 3rem; font-weight: 700; color: #272d3b; }
                .hero p { font-size: 1.2rem; color: #666; }
            </style>
        </head>
        <body>
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
                <div class="container">
                    <a class="navbar-brand fw-bold" href="/">CoreCart</a>
                </div>
            </nav>
            <div class="hero">
                <h1>Welcome to CoreCart</h1>
                <p>A next-generation e-commerce platform built on clean PHP.</p>
                <a href="/admin/" class="btn btn-primary btn-lg">Go to Admin Panel</a>
            </div>
        </body>
        </html>
        HTML;
    }
}
