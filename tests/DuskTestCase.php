<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = false;

    /**
     * The seeder class to run before each test (when $seed = true).
     *
     * @var string
     */
    protected $seeder = \Database\Seeders\DuskTestSeeder::class;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Initialize RLS context for tests
        $this->initializeRLSContext();
    }

    /**
     * Initialize Row-Level Security context for multi-tenancy tests.
     */
    protected function initializeRLSContext(): void
    {
        // Clear any existing RLS context
        DB::statement("SELECT set_config('app.current_org_id', NULL, false)");
    }

    /**
     * Set RLS context for a specific organization.
     *
     * @param string $orgId
     * @return void
     */
    protected function setOrgContext(string $orgId): void
    {
        DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);
    }

    /**
     * Clear the RLS context.
     *
     * @return void
     */
    protected function clearOrgContext(): void
    {
        DB::statement("SELECT set_config('app.current_org_id', NULL, false)");
    }
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
