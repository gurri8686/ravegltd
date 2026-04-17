<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        $request->attributes->add(['organization' => $org]);
        return $next($request);
    }
}
