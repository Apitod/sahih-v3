<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;

class PohonController extends Controller
{
    public function index(Request $request)
    {
        $member = $request->user('web');
        $pohon = $this->buildTree($member);

        return view('member.pohon', compact('member', 'pohon'));
    }

    private function buildTree($member, int $depth = 20)
    {
        $layers = [];
        $current = collect([$member]);

        for ($i = 0; $i < $depth; $i++) {
            $next = Member::whereIn('sponsor_id', $current->pluck('id'))
                ->orderBy('username')
                ->get(['id', 'username', 'name', 'rank', 'sponsor_id', 'status']);

            if ($next->isEmpty()) break;

            $layers[] = $next;
            $current = $next;
        }

        $childMap = [];
        foreach ($layers as $layer) {
            foreach ($layer as $m) {
                $childMap[$m->sponsor_id][] = $m;
            }
        }

        $plant = function (int $id, int $remaining) use (&$plant, $childMap) {
            return collect($childMap[$id] ?? [])->map(fn($child) => [
                'model' => $child,
                'anak' => $remaining > 0 ? $plant($child->id, $remaining - 1) : collect(),
            ]);
        };

        return $plant($member->id, $depth - 1);
    }
}
