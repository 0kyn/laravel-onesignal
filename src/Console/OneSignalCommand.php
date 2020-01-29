<?php

namespace Okn\OneSignal\Console;

use Illuminate\Console\Command;

class OneSignalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'onesignal:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install OneSignal wrapper';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Installing OneSignal wrapper...');

        $this->info('Publishing configuration...');

        $this->call('vendor:publish', [
            '--provider' => "Okn\OneSignal\OneSignalServiceProvider",
            '--tag' => "config"
        ]);

        $this->info('Installed OneSignal wrapper');
    }
}
