<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Pin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PinController extends Controller
{
    /** Huruf yang mudah tertukar (O/0, I/1, L) dibuang supaya PIN bisa dibacakan lewat telepon. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function index(Request $request): View
    {
        $filter = $request->only(['status', 'type']);

        $pins = Pin::query()
            ->when($filter['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filter['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->with(['assignedMember', 'usedBy'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.pin.index', [
            'pins' => $pins,
            'filter' => $filter,
            'ringkasan' => [
                'issued' => Pin::where('status', 'issued')->count(),
                'used' => Pin::where('status', 'used')->count(),
                'void' => Pin::where('status', 'void')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.pin.create', [
            'peringkat' => ['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:registration,upgrade,ro'],
            'points' => ['required', 'integer', 'min:0', 'max:1000'],
            'price' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'target_rank' => ['nullable', 'required_if:type,upgrade', 'in:reseller,agent,distributor,manager'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'username' => ['nullable', 'string', 'exists:members,username'],
        ], [
            'target_rank.required_if' => 'PIN upgrade harus menyebut peringkat tujuan.',
        ]);

        if ($data['type'] === 'ro') {
            $expectedPrice = (int) $data['points'] * (int) config('sahih.ro_price_per_point');
            if ((int) $data['price'] !== $expectedPrice) {
                return back()->withInput()->withErrors([
                    'price' => "PIN RO {$data['points']} poin harus Rp ".number_format($expectedPrice, 0, ',', '.').'.',
                ]);
            }
        }

        $member = null;
        if (! empty($data['username'])) {
            $member = Member::where('username', $data['username'])->first();
            if ($member->status !== 'active') {
                return back()->withInput()->withErrors(['username' => 'Anggota ini sedang dibekukan.']);
            }
        }

        $admin = Auth::guard('admin')->user();
        $dibuat = [];

        DB::transaction(function () use ($data, $member, $admin, &$dibuat) {
            for ($i = 0; $i < (int) $data['quantity']; $i++) {
                $pin = Pin::create([
                    'code' => $this->kodeUnik(),
                    'type' => $data['type'],
                    'target_rank' => $data['type'] === 'upgrade' ? $data['target_rank'] : null,
                    'points' => (int) $data['points'],
                    'price' => (int) $data['price'],
                    'status' => 'issued',
                    'issued_by' => $admin->id,
                    'assigned_member_id' => $member?->id,
                ]);

                AuditLog::create([
                    'actor_type' => 'admin',
                    'actor_id' => $admin->id,
                    'action' => 'pin.create',
                    'subject_type' => Pin::class,
                    'subject_id' => $pin->id,
                    'after' => $pin->only(['code', 'type', 'target_rank', 'points', 'price', 'assigned_member_id']),
                    'ip' => request()->ip(),
                ]);

                $dibuat[] = $pin;
            }
        });

        return redirect()->route('admin.pin.index')
            ->with('status', count($dibuat).' PIN dibuat.');
    }

    public function void(Request $request, Pin $pin): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);

        if ($pin->status !== 'issued') {
            return back()->withErrors(['reason' => 'Hanya PIN yang belum dipakai bisa dibatalkan.']);
        }

        $admin = Auth::guard('admin')->user();
        $sebelum = $pin->only(['status']);

        DB::transaction(function () use ($pin, $admin, $data, $sebelum) {
            $pin->update(['status' => 'void']);

            AuditLog::create([
                'actor_type' => 'admin',
                'actor_id' => $admin->id,
                'action' => 'pin.void',
                'subject_type' => Pin::class,
                'subject_id' => $pin->id,
                'before' => $sebelum,
                'after' => ['status' => 'void', 'reason' => $data['reason']],
                'ip' => request()->ip(),
            ]);
        });

        return redirect()->route('admin.pin.index')->with('status', 'PIN dibatalkan.');
    }

    private function kodeUnik(): string
    {
        do {
            $kode = '';
            for ($i = 0; $i < 12; $i++) {
                $kode .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (Pin::where('code', $kode)->exists());

        return $kode;
    }
}
