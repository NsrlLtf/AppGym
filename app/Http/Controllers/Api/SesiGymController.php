<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use App\Models\SesiGym;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;


class SesiGymController extends Controller
{
    protected $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function checkIn(Request $request)
    {
        $member = Member::where('rfid_card_number', $request->rfid_card)
                       ->where('status', 'active')
                       ->first();

        if (!$member) {
            return response()->json(['message' => 'Invalid or inactive member'], 400);
        }

        if (!$member->isActive()) {
            return response()->json(['message' => 'Membership has expired'], 400);
        }

        $activeSession = SesiGym::where('member_id', $member->id)
                               ->whereNull('check_out_time')
                               ->first();

        if ($activeSession) {
            return response()->json(['message' => 'Already checked in'], 400);
        }

        $session = SesiGym::create([
            'member_id' => $member->id,
            'check_in_time' => now(),
            'status' => 'active',
            'device_id' => $request->header('X-Device-ID'), // Tambahkan device_id
            'verified_by' => auth()->id()
        ]);

        // Update last check-in time
        $member->update([
            'last_check_in' => now(),
            'total_check_ins' => $member->total_check_ins + 1
        ]);

        // Kirim notifikasi webhook untuk check-in
        $this->webhookService->notifyCheckIn($member, $session);

        return response()->json(['session' => $session]);
    }

    public function checkOut(Request $request)
    {
        $member = Member::where('rfid_card_number', $request->rfid_card)->first();
        
        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $activeSession = SesiGym::where('member_id', $member->id)
                               ->whereNull('check_out_time')
                               ->first();

        if (!$activeSession) {
            return response()->json(['message' => 'No active session found'], 400);
        }

        $activeSession->update([
            'check_out_time' => now(),
            'total_duration' => now()->diffInMinutes($activeSession->check_in_time),
            'status' => 'completed'
        ]);

        // Kirim notifikasi webhook untuk check-out
        $this->webhookService->notifyCheckOut($member, $activeSession);

        return response()->json(['session' => $activeSession]);
    }

    public function sessionHistory(Request $request)
    {
        $sessions = SesiGym::with('member')
                          ->when($request->member_id, function($query, $memberId) {
                              return $query->where('member_id', $memberId);
                          })
                          ->when($request->date, function($query, $date) {
                              return $query->whereDate('check_in_time', $date);
                          })
                          ->orderBy('check_in_time', 'desc')
                          ->paginate(15);

        return response()->json(['sessions' => $sessions]);
    }

    public function gymUsageReport(Request $request)
    {
        $startDate = $request->start_date ?? now()->subMonth();
        $endDate = $request->end_date ?? now();

        $sessions = SesiGym::whereBetween('check_in_time', [$startDate, $endDate])
                          ->with('member')
                          ->get();

        $report = [
            'total_sessions' => $sessions->count(),
            'total_duration' => $sessions->sum('total_duration'),
            'average_duration' => $sessions->avg('total_duration'),
            'unique_members' => $sessions->unique('member_id')->count(),
            'daily_stats' => $sessions->groupBy(function($session) {
                return $session->check_in_time->format('Y-m-d');
            })->map(function($daySessions) {
                return [
                    'count' => $daySessions->count(),
                    'total_duration' => $daySessions->sum('total_duration'),
                    'unique_members' => $daySessions->unique('member_id')->count()
                ];
            })
        ];

        return response()->json(['report' => $report]);
    }

    public function getCurrentOccupancy()
    {
        $activeMembers = SesiGym::whereNull('check_out_time')
                               ->with('member')
                               ->get();

        return response()->json([
            'current_occupancy' => $activeMembers->count(),
            'active_members' => $activeMembers
        ]);
    }

    public function forceCheckOut(Request $request, $sessionId)
    {
        $session = SesiGym::findOrFail($sessionId);
        
        if ($session->check_out_time) {
            return response()->json(['message' => 'Session already checked out'], 400);
        }

        $session->update([
            'check_out_time' => now(),
            'total_duration' => now()->diffInMinutes($session->check_in_time),
            'status' => 'force_completed',
            'force_checkout_reason' => $request->reason,
            'force_checkout_by' => auth()->id()
        ]);

        // Kirim notifikasi webhook untuk force check-out
        $this->webhookService->sendNotification('member.force_checkout', [
            'member_id' => $session->member_id,
            'session_id' => $session->id,
            'reason' => $request->reason,
            'checkout_time' => $session->check_out_time,
            'duration' => $session->total_duration
        ]);

        return response()->json(['session' => $session]);
    }
}