<?php

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->user('web');
        $status = $member === null ? null : Member::whereKey($member->id)->value('status');

        abort_unless($status === 'active', 403);

        return $next($request);
    }
}
