<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Site;

class DomainsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $host = request()->getProtocolVersion();
        if($host == 'HTTP/1.1'){
            $protocol = 'http://';
        }
        $domain = request()->getHost();
        $headers = $request->headers->all();
        $org = Site::getValidDomainDatabase($domain);

        if(empty($org)){
            abort('404');
        }

        /**
         * Check and set database as per domain.
         */
        config()->set('app.url', $domain);
        config()->set('database.connections.mysql.database', $org->database);

        // The default 'mysql' connection may already have been opened on the
        // env-default database earlier in this request/boot. config() alone does
        // NOT move an open connection, so force it to re-open on the per-domain
        // (tenant) database — otherwise every query leaks the main DB's data.
        DB::purge('mysql');
        DB::reconnect('mysql');

        $request->attributes->add(['organization' => $org]);
        return $next($request);
    }
}
