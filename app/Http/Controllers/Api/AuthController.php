<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Member;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register Admin
     */
    public function registerAdminUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role
        ]);

        return response()->json([
            'message' => 'Admin berhasil didaftarkan',
            'user' => $user
        ], 201);
    }

    /**
     * Get All Admins (Untuk Superadmin)
     */
    public function getAllAdmins()
    {
        $admins = User::where('role', 'admin')->get();
        return response()->json(['admins' => $admins]);
    }

    /**
     * Update Admin (Untuk Superadmin)
     */
    public function updateAdmin(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $admin = User::findOrFail($id);
        $admin->update($request->all());

        return response()->json([
            'message' => 'Data admin berhasil diperbarui',
            'admin' => $admin
        ]);
    }

    /**
     * Delete Admin (Untuk Superadmin)
     */
    public function deleteAdmin($id)
    {
        $admin = User::findOrFail($id);
        $admin->delete();

        return response()->json([
            'message' => 'Admin berhasil dihapus'
        ]);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|in:member,admin,superadmin'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Login berdasarkan role
        if ($request->role === 'member') {
            return $this->memberLogin($request);
        } else {
            return $this->adminLogin($request);
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Berhasil logout'
        ]);
    }

    /**
     * Helper Methods
     */
    private function memberLogin(Request $request)
    {
        $member = Member::where('email', $request->email)->first();

        if (!$member || !Hash::check($request->password, $member->password)) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        if ($member->status !== 'active' || now()->gt($member->membership_end_date)) {
            return response()->json([
                'message' => 'Membership tidak aktif'
            ], 403);
        }

        $token = $member->createToken('member_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'member' => $member,
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    private function adminLogin(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        if ($user->role !== $request->role) {
            return response()->json([
                'message' => 'Role tidak sesuai'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
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
}