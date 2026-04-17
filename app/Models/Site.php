<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;

    protected $connection = 'organizations';

    /**
     * 
     */
    public static function getValidDomainDatabase($domain){
        $obj = new self();
        $site = $obj->where('domain', $domain)->orWhere('subdomain', $domain)->first();

        if(empty($site)){
            return "";
        }
        return $site;     
    }
}
