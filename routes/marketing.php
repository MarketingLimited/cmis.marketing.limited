<?php

use App\Http\Controllers\Marketing\BlogController;
use App\Http\Controllers\Marketing\CaseStudyController;
use App\Http\Controllers\Marketing\ContactController;
use App\Http\Controllers\Marketing\DemoController;
use App\Http\Controllers\Marketing\MarketingController;
use App\Http\Controllers\Marketing\NewsletterController;
use App\Http\Controllers\Marketing\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketing Website Routes
|--------------------------------------------------------------------------
|
| Public-facing marketing website routes. These routes are accessible
| without authentication and serve the marketing content, blog, pricing,
| contact forms, and other public pages.
|
| All routes use the 'web' middleware group for session and CSRF protection.
|
*/

Route::middleware(['web'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Homepage
    |--------------------------------------------------------------------------
    |
    | The main landing page. For authenticated users, we can optionally
    | redirect to their dashboard, or show the marketing page with a
    | "Go to Dashboard" button.
    |
    */
    Route::get('/', [MarketingController::class, 'home'])->name('marketing.home');

    /*
    |--------------------------------------------------------------------------
    | Core Marketing Pages
    |--------------------------------------------------------------------------
    */
    Route::get('/features', [MarketingController::class, 'features'])->name('marketing.features');
    Route::get('/pricing', [MarketingController::class, 'pricing'])->name('marketing.pricing');
    Route::get('/about', [MarketingController::class, 'about'])->name('marketing.about');
    Route::get('/faq', [MarketingController::class, 'faq'])->name('marketing.faq');

    /*
    |--------------------------------------------------------------------------
    | Blog
    |--------------------------------------------------------------------------
    */
    Route::prefix('blog')->name('marketing.blog.')->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/category/{category:slug}', [BlogController::class, 'category'])->name('category');
        Route::get('/{post:slug}', [BlogController::class, 'show'])->name('show');
    });

    /*
    |--------------------------------------------------------------------------
    | Case Studies
    |--------------------------------------------------------------------------
    */
    Route::prefix('case-studies')->name('marketing.case-studies.')->group(function () {
        Route::get('/', [CaseStudyController::class, 'index'])->name('index');
        Route::get('/{caseStudy:slug}', [CaseStudyController::class, 'show'])->name('show');
    });

    /*
    |--------------------------------------------------------------------------
    | Contact & Demo Forms
    |--------------------------------------------------------------------------
    |
    | Contact form submissions are stored in cmis.leads table.
    | Demo requests are also stored in cmis.leads with type='demo'.
    |
    */
    Route::get('/contact', [ContactController::class, 'show'])->name('marketing.contact');
    Route::post('/contact', [ContactController::class, 'submit'])->name('marketing.contact.submit');

    Route::get('/demo', [DemoController::class, 'show'])->name('marketing.demo');
    Route::post('/demo', [DemoController::class, 'submit'])->name('marketing.demo.submit');

    /*
    |--------------------------------------------------------------------------
    | Newsletter Subscription
    |--------------------------------------------------------------------------
    |
    | Newsletter subscriptions are stored in cmis.contacts table.
    |
    */
    Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->name('marketing.newsletter.subscribe');

    /*
    |--------------------------------------------------------------------------
    | Language Switcher
    |--------------------------------------------------------------------------
    */
    Route::get('/locale/{locale}', function (string $locale) {
        if (in_array($locale, ['en', 'ar'])) {
            session(['app_locale' => $locale]);
            cookie()->queue(cookie()->forever('app_locale', $locale));
        }
        return redirect()->back();
    })->name('locale.switch');

    /*
    |--------------------------------------------------------------------------
    | Legal Pages
    |--------------------------------------------------------------------------
    |
    | These are special CMS pages with fixed slugs.
    |
    */
    Route::get('/terms', [PageController::class, 'terms'])->name('marketing.terms');
    Route::get('/privacy', [PageController::class, 'privacy'])->name('marketing.privacy');
    Route::get('/cookies', [PageController::class, 'cookies'])->name('marketing.cookies');

    /*
    |--------------------------------------------------------------------------
    | Dynamic CMS Pages (Catch-All)
    |--------------------------------------------------------------------------
    |
    | This must be LAST to catch any slug not matched by other routes.
    | It will look up the page in cmis_website.pages table by slug.
    |
    */
    Route::get('/{slug}', [PageController::class, 'show'])
        ->where('slug', '^(?!login|register|logout|password|email|orgs|super-admin|api|sanctum|livewire|broadcasting|storage|_debugbar|horizon|telescope).*$')
        ->name('marketing.page');

});
