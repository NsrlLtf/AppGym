<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use App\Models\SesiGym;
use App\Models\TransaksiMember;
use App\Models\User;
use App\Models\PaketMember;
use App\Services\WebhookService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class MemberController extends Controller
{
 public function register(Request $request)
    {
        // Validasi input dasar
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:members,email',
            'phone' => 'required|string',
            'membership_type' => 'required|in:bronze,platinum,gold',
            'payment_method' => 'required|in:cash,transfer,credit_card'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi Gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Mulai transaksi database
            return DB::transaction(function () use ($request) {
                // Ambil user yang sedang login
                $currentUser = Auth::user();

                // Jika tidak ada user yang login, kembalikan error
                if (!$currentUser) {
                    throw new \Exception('Anda harus login untuk mendaftarkan member');
                }

                // Ambil paket member sesuai tipe
                $paketMember = PaketMember::getPackageByType($request->membership_type);
                
                if (!$paketMember) {
                    throw new \Exception('Paket member tidak ditemukan');
                }

                // Generate RFID (contoh sederhana)
                $rfidCardNumber = 'RFID-' . strtoupper(uniqid());

                // Buat member baru
                $member = Member::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => Hash::make(substr($request->phone, -6)), // Contoh: password default 6 digit terakhir no telp
                    'rfid_card_number' => $rfidCardNumber,
                    'membership_type' => $request->membership_type,
                    'membership_start_date' => now(),
                    'membership_end_date' => now()->addMonths($paketMember->duration_months),
                    'status' => 'active',
                    'total_check_ins' => 0,
                    'registered_by' => $currentUser->id, // Tambahkan admin yang mendaftarkan
                    'last_updated_by' => $currentUser->id
                ]);

                // Buat transaksi member
                $transaksi = TransaksiMember::create([
                    'member_id' => $member->id,
                    'paket_member_id' => $paketMember->id,
                    'amount' => $paketMember->price,
                    'transaction_date' => now(),
                    'payment_method' => $request->payment_method,
                    'payment_status' => 'success', // Asumsi pembayaran langsung sukses
                    'processed_by' => $currentUser->id, // Tambahkan admin yang memproses
                    'notes' => 'Pendaftaran member baru'
                ]);

                return response()->json([
                    'message' => 'Pendaftaran Berhasil',
                    'member' => $member,
                    'transaksi' => $transaksi
                ], 201);
            });
        } catch (\Exception $e) {
            // Tangani error
            return response()->json([
                'message' => 'Pendaftaran Member Gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

public function memberActivityReport($memberId)
    {
        $member = Member::findOrFail($memberId);
        
        $sessions = SesiGym::where('member_id', $memberId)
            ->with('verifiedBy')
            ->whereNotNull('check_out_time')
            ->orderBy('check_in_time', 'desc')
            ->get();

        return response()->json([
            'member' => $member,
            'total_sessions' => $sessions->count(),
            'total_duration' => $sessions->sum('total_duration'),
            'average_duration' => $sessions->avg('total_duration'),
            'sessions' => $sessions
        ]);
    }

public function getAllMembers()
{
    if (!in_array(auth()->user()->role, ['admin', 'superadmin'])) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $members = Member::paginate(15);
    return response()->json(['members' => $members]);
}

public function getMember($id)
{
    $member = Member::findOrFail($id);
    return response()->json(['member' => $member]);
}

public function updateMember(Request $request, $id)
{
    if (!in_array(auth()->user()->role, ['admin', 'superadmin'])) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $member = Member::findOrFail($id);
    
    $validator = Validator::make($request->all(), [
        'name' => 'string',
        'email' => 'email|unique:members,email,'.$id,
        'phone' => 'string',
        'status' => 'in:active,inactive'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $member->update($request->all());
    return response()->json(['member' => $member]);
}

public function deleteMember($id)
{
    if (!in_array(auth()->user()->role, ['admin', 'superadmin'])) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $member = Member::findOrFail($id);
    $member->delete();
    return response()->json(['message' => 'Member berhasil dihapus']);
}

public function getProfile()
{
    $member = auth()->user();
    return response()->json(['member' => $member]);
}

public function updateProfile(Request $request)
{
    $member = auth()->user();
    
    $validator = Validator::make($request->all(), [
        'name' => 'string',
        'email' => 'email|unique:members,email,'.$member->id,
        'phone' => 'string'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $member->update($request->all());
    return response()->json(['member' => $member]);
}

public function membershipReport()
{
    $members = Member::selectRaw('
        membership_type,
        COUNT(*) as total,
        COUNT(CASE WHEN status = "active" THEN 1 END) as active,
        COUNT(CASE WHEN status = "inactive" THEN 1 END) as inactive
    ')
    ->groupBy('membership_type')
    ->get();

    return response()->json(['report' => $members]);
}

private function generateUniqueRFIDCardNumber()
{
    do {
        $rfidCardNumber = 'RFID-' . Str::random(10);
    } while (Member::where('rfid_card_number', $rfidCardNumber)->exists());

    return $rfidCardNumber;
}

private function calculateMembershipEndDate($membershipType)
{
    $durations = [
        'bronze' => 3,
        'platinum' => 6,
        'gold' => 12
    ];

    return now()->addMonths($durations[$membershipType] ?? 3);
}

private function checkAdminAccess()
{
    if (!in_array(auth()->user()->role, ['admin', 'superadmin'])) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    return true;
}
}
