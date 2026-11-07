<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MilestoneReward;
use Illuminate\Http\Request;

class HadiahAdminController extends Controller
{
    public function index()
    {
        $hadiah = MilestoneReward::orderBy('threshold')->get();

        return view('admin.hadiah.index', compact('hadiah'));
    }

    public function edit(MilestoneReward $reward)
    {
        return view('admin.hadiah.edit', compact('reward'));
    }

    /** threshold sengaja tidak di-validate maupun di-update: mengubahnya akan menggeser hadiah yang sudah diklaim. */
    public function update(Request $request, MilestoneReward $reward)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'value_internal' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $reward->update([
            'name' => $data['name'],
            'value_internal' => $data['value_internal'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.hadiah.index')->with('status', 'Hadiah diperbarui.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'threshold' => 'required|integer|min:1|unique:milestone_rewards,threshold',
            'name' => 'required|string|max:100',
            'value_internal' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        MilestoneReward::create([
            'threshold' => $data['threshold'],
            'name' => $data['name'],
            'value_internal' => $data['value_internal'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.hadiah.index')->with('status', 'Hadiah ditambahkan.');
    }
}
