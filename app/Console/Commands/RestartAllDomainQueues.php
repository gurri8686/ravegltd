<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RestartAllDomainQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:restart-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart queue workers for all multi-domains';

    /**
     * List of all domains
     *
     * @var array
     */
    protected $domains = [
        
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {	
		$this->domains = config('domain.domains');
	
        foreach ($this->domains as $domain) {
            $this->info("Restarting queue for domain: {$domain}");

            // Run queue:restart for each domain
            $exitCode = $this->call('queue:restart', [
                '--domain' => $domain
            ]);

            if ($exitCode === 0) {
                $this->info("Queue restarted successfully for {$domain}");
            } else {
                $this->error("Failed to restart queue for {$domain}");
            }
        }

        return 0;
    }
}
