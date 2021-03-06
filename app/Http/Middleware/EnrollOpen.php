<?php

namespace App\Http\Middleware;

use App\Helpers;
use App\Setting;
use Closure;

class EnrollOpen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $setting = Helpers::getInstance()->setting();

        if ($setting !== null)
        {
            if ($setting->hasEnroll(true))
            {
                return $next($request);
            }
        }

        return redirect()->route('home.index')->with('error', "Les inscriptions sont closes !");
    }
}
