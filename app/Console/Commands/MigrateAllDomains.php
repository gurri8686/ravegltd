<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateAllDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:all-domains {--fresh} {--seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations for all configured multi-domains';

    /**
     * List of domains (you can also load dynamically from config or DB)
     *
     * @var array
     */
    protected $domains = [
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$this->domains = config('domain.domains');
		
        foreach ($this->domains as $domain) {
            $this->info("🔹 Running migration for domain: {$domain}");

            // Build migrate command with options
            $arguments = ['--domain' => $domain];
            if ($this->option('fresh')) $arguments['--fresh'] = true;
            if ($this->option('seed')) $arguments['--seed'] = true;

            // Run migration command
            $exitCode = $this->call('migrate', $arguments);

            if ($exitCode === 0) {
                $this->info("✅ Migration completed for {$domain}");
            } else {
                $this->error("❌ Migration failed for {$domain}");
            }
        }

        return 0;
    }
}
