<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckIpMiddleware
{
    /**
     * Список разрешенных IP-адресов
     *
     * @var array
     */
    protected $allowedIps = [
        '92.253.212.184',
        '77.239.218.81',
    ];

    /**
     * Токен доступа к документации
     */
    protected $accessToken = 'IDASHJFGth6Y_fhjnaDF192UYr375gt129fyhBSDNQ_CV';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $clientIp = $request->ip();
        $userToken = $request->input('token');
        
        if (!in_array($clientIp, $this->allowedIps) && $userToken !== $this->accessToken) {
            abort(403, 'Доступ запрещен. Неверный IP или токен.');
        }

        return $next($request);
    }
}