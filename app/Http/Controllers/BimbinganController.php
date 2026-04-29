<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BimbinganController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $role = (int) $user->role;

        $query = DB::table('catatan_bimbingan')
            ->join('siswa', 'catatan_bimbingan.id_siswa', '=', 'siswa.nis')
            ->leftJoin('pembimbing', 'catatan_bimbingan.approved_by', '=', 'pembimbing.id_pembimbing')
            ->whereNull('catatan_bimbingan.deleted_at');

        if ($role === 1) { // Siswa
            $siswa = DB::table('siswa')->where('id_user', $user->id_user)->first();
            $query->where('catatan_bimbingan.id_siswa', $siswa->nis);
        } elseif ($role === 4) { // Pembimbing
            $pembimbing = DB::table('pembimbing')->where('id_user', $user->id_user)->first();
            $query->where('siswa.id_pembimbing', $pembimbing->id_pembimbing);
        }

        $notes = $query->orderByDesc('catatan_bimbingan.created_at')
            ->select('catatan_bimbingan.*', 'siswa.nama_siswa', 'pembimbing.nama_pembimbing')
            ->get();

        return view('admin.catatan-bimbingan', [
            'notes' => $notes,
            'role' => $role
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'poin_perbaikan' => 'required|string',
            'tindakan_lanjut' => 'nullable|string',
        ]);

        $user = $request->user();
        $siswa = DB::table('siswa')->where('id_user', $user->id_user)->first();

        if (!$siswa) {
            return back()->with('error', 'Hanya siswa yang dapat membuat catatan bimbingan.');
        }

        DB::table('catatan_bimbingan')->insert([
            'id_siswa' => $siswa->nis,
            'poin_perbaikan' => $request->poin_perbaikan,
            'tindakan_lanjut' => $request->tindakan_lanjut,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Catatan bimbingan berhasil disimpan.');
    }

    public function approve(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $pembimbing = DB::table('pembimbing')->where('id_user', $user->id_user)->first();

        if (!$pembimbing && !in_array((int)$user->role, [7, 8])) {
            return back()->with('error', 'Anda tidak memiliki otoritas untuk melakukan validasi.');
        }

        DB::table('catatan_bimbingan')
            ->where('id_catatan', $id)
            ->update([
                'is_approved' => true,
                'approved_by' => $pembimbing ? $pembimbing->id_pembimbing : null,
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Catatan bimbingan berhasil divalidasi.');
    }

    public function destroy($id): RedirectResponse
    {
        DB::table('catatan_bimbingan')
            ->where('id_catatan', $id)
            ->update(['deleted_at' => now()]);

        return back()->with('success', 'Catatan berhasil dihapus.');
    }
}
