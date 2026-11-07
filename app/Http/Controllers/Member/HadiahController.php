<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MilestoneReward;
use Illuminate\Http\Request;

class HadiahController extends Controller
{
    public function index(Request $request)
    {
        $member = $request->user('web');

        $bonus = $member->bonusEntries()
            ->select(['id', 'member_id', 'kind', 'percent_bp', 'level', 'status', 'available_at', 'created_at'])
            ->orderByDesc('id')
            ->paginate(15);

        $klaim = $member->milestoneClaims()
            ->with('reward')
            ->orderByDesc('cycle')
            ->paginate(15, ['*'], 'klaim');

        $milestones = MilestoneReward::where('is_active', true)
            ->orderBy('threshold')
            ->get(['id', 'threshold', 'name', 'sort']);

        return view('member.hadiah', compact('bonus', 'klaim', 'milestones'));
    }
}
