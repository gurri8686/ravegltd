<?php
	
namespace App\Lib;

use Gecche\Multidomain\Foundation\Application;
use Gecche\Multidomain\Env\DomainEnv;

class MultidomainCustom extends Application{
	
	public function environmentFileDomain($domain = null)
    {
	    $this->checkDomainDetection();

        if (is_null($domain)) {
            $domain = $this['domain'];
        }
		
		/**
		 * Here we can make dynamic domain based .env
		 */

        $envFile = $this->searchForEnvFileDomain(explode('.',$domain));
			
        return $envFile;

    }
	
	public static function updateEnv($domain){
		$domainEnv = new DomainEnv($domain);
		$domainEnv->setValue('TOM_DRIVER', 'test');
		$domainEnv->save();
	}
	
}