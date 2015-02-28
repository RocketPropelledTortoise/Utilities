<?php namespace Rocket\Utilities;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $migratedDatabases = false;

    public function setUp()
    {
        if (! $this->app) {
            $this->refreshApplication();
        }

        $artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');
        $artisan->call('vendor:publish');

        //refresh configuration values
        $this->refreshApplication();
    }

    public function tearDown()
    {
        // if we're using mysql or postgresql,
        // we need to remove the used data
        if ($this->migratedDatabases) {
            $artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');
            $artisan->call('migrate:reset');
        }

        parent::tearDown();
    }

    public function packagesToTest(array $items)
    {
        $artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');

        $migrations = [
            'translations' => "Translation/migrations",
            'entities' => "Entities/migrations", //depends on translations
            'taxonomy' => "Taxonomy/migrations", //depends on translations
        ];

        foreach ($migrations as $key => $path) {
            if (in_array($key, $items)) {
                $artisan->call('migrate', ['--path' => $path]);
            }
        }

        $this->migratedDatabases = true;
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // reset base path to point to our package's src directory
        $app['path.base'] = realpath(__DIR__ . '/..');

        $app['config']->set(
            'database.connections',
            [
                'default' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
                // create database travis_test;
                // grant usage on *.* to travis@localhost;
                // grant all privileges on travis_test.* to travis@localhost;
                'travis-mysql' => [
                    'driver'    => 'mysql',
                    'host'      => 'localhost',
                    'database'  => 'travis_test',
                    'username'  => 'travis',
                    'password'  => '',
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => '',
                ],
                'travis-pgsql' => [
                    'driver'   => 'pgsql',
                    'host'     => 'localhost',
                    'database' => 'travis_test',
                    'username' => 'postgres',
                    'password' => '',
                    'charset'  => 'utf8',
                    'prefix'   => '',
                    'schema'   => 'public',
                ]
            ]
        );

        $app['config']->set('database.default', 'default');
        if ($db = getenv('DB')) {
            if ($db == 'pgsql') {
                $app['config']->set('database.default', 'travis-pgsql');
            }

            if ($db == 'mysql') {
                $app['config']->set('database.default', 'travis-mysql');
            }
        }
    }

    /**
     * Get package providers. At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return array(
            //'Cartalyst\Sentry\SentryServiceProvider',
            //'YourProject\YourPackage\YourPackageServiceProvider',
        );
    }

    /**
     * Get package aliases. In a normal app environment these would be added to
     * the 'aliases' array in the config/app.php file. If your package exposes an
     * aliased facade, you should add the alias here, along with aliases for
     * facades upon which your package depends, e.g. Cartalyst/Sentry
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return array(
            //'Sentry' => 'Cartalyst\Sentry\Facades\Laravel\Sentry',
            //'YourPackage' => 'YourProject\YourPackage\Facades\YourPackage',
        );
    }
}
