<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;

final class AdminHomepageController extends Controller
{
    /**
     * Display default admin home page
     */
    public function index(ViewFactory $viewFactory): View
    {
        $quote = 'Well begun is half done.';
        $quoteAuthor = 'Aristotle';

        return $viewFactory->make(
            'brackets/admin-auth::admin.homepage.index',
            [
                'quote' => $quote,
                'quoteAuthor' => $quoteAuthor,
            ],
        );
    }
}
