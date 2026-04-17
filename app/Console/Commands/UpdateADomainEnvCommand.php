<?php

namespace App\Console\Commands;

use Gecche\Multidomain\Foundation\Console\AddDomainCommand;
use Illuminate\Support\Arr;

class UpdateADomainEnvCommand extends AddDomainCommand
{
    protected $signature = 'Adomain:add
                            {domain : The domain to update}
                            {--domain_values= : JSON object of specific env keys to update}';

    protected $description = "Update only specific environment keys for a domain without overwriting the rest.";

    public function handle()
    {
        $this->domain = $this->argument('domain');

        // Parse new key-values
        $newValues = json_decode($this->option("domain_values"), true);
        if (!is_array($newValues)) {
            $this->error('Invalid JSON for domain_values');
            return 1;
        }

        $envFilePath = $this->getDomainEnvFilePath();

        // Load existing env
        $envArray = $this->getVarsArray($envFilePath);

        // Merge ONLY new keys
        $mergedArray = array_merge($envArray, $newValues);

        // Save back
        $contents = $this->makeDomainEnvFileContents($mergedArray);
        $this->files->put($envFilePath, $contents);

        $this->info("Updated environment keys for domain: {$this->domain}");
    }
}
