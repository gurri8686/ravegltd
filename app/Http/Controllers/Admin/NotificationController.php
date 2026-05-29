<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Platform announcements composed by the superadmin team and stored in the
 * control-plane `platform_notifications` table (organizations DB).
 *
 * Everything here is real: each announcement records the live audience size
 * (its actual reach) at the moment it's posted. There is no vendor-facing
 * delivery channel yet, so we DON'T fabricate "read/delivered" stats — the
 * recipient count is the honest reach figure. Surfacing these to vendors in
 * the main app is a deliberate follow-up.
 */
class NotificationController extends Controller
{
    /** vendor tier (matches VendorController). */
    private const ROLES = ['admin', 'vendor'];

    /** Audience options → human label. */
    public const AUDIENCES = [
        'all'      => 'All vendors',
        'active'   => 'Active vendors',
        'inactive' => 'Inactive vendors',
    ];

    /** Severity levels → label (drives the badge colour in the view). */
    public const LEVELS = [
        'info'     => 'Info',
        'success'  => 'Success',
        'warning'  => 'Warning',
        'critical' => 'Critical',
    ];

    public function index()
    {
        $notifications = $this->table()->orderByDesc('created_at')->limit(50)->get();

        // live audience sizes so the compose form shows real reach per option
        $audienceCounts = [
            'all'      => $this->audienceSize('all'),
            'active'   => $this->audienceSize('active'),
            'inactive' => $this->audienceSize('inactive'),
        ];

        $totalSent  = (int) $this->table()->count();
        $totalReach = (int) $this->table()->sum('recipients');
        $lastSent   = optional($notifications->first())->created_at;

        return view('admin.notifications.index', [
            'notifications'  => $notifications,
            'audiences'      => self::AUDIENCES,
            'levels'         => self::LEVELS,
            'audienceCounts' => $audienceCounts,
            'totalSent'      => $totalSent,
            'totalReach'     => $totalReach,
            'lastSent'       => $lastSent,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'    => 'required|string|max:160',
            'message'  => 'required|string|max:2000',
            'audience' => ['required', Rule::in(array_keys(self::AUDIENCES))],
            'level'    => ['required', Rule::in(array_keys(self::LEVELS))],
        ]);

        $this->table()->insert([
            'title'        => $data['title'],
            'message'      => $data['message'],
            'audience'     => $data['audience'],
            'level'        => $data['level'],
            'recipients'   => $this->audienceSize($data['audience']),
            'is_published' => 1,
            'created_by'   => Auth::id(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return redirect()->route('admin.notifications.index')->with('status', 'Announcement posted.');
    }

    public function destroy($id)
    {
        $this->table()->where('id', $id)->delete();

        return redirect()->route('admin.notifications.index')->with('status', 'Announcement deleted.');
    }

    // ── helpers ──────────────────────────────────────────────────────────

    /** Real number of vendors an audience targets. */
    private function audienceSize(string $audience): int
    {
        $q = User::whereIn('role', self::ROLES);
        if ($audience === 'active') {
            $q->where('is_active', 1);
        } elseif ($audience === 'inactive') {
            $q->where('is_active', 0);
        }

        return (int) $q->count();
    }

    private function table()
    {
        return DB::connection('organizations')->table('platform_notifications');
    }
}
