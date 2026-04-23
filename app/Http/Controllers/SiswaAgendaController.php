<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiswaAgendaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $siswa = DB::table('siswa')->where('id_user', $user->id_user)->first();

        if (!$siswa) {
            return redirect()->route('dashboard')->with('error', 'Data siswa tidak ditemukan.');
        }

        $today = Carbon::today()->toDateString();
        
        // Fetch today's agenda if exists
        $todayAgenda = DB::table('agenda')
            ->where('id_siswa', $siswa->nis)
            ->where('tanggal', $today)
            ->first();

        // Fetch history
        $history = DB::table('agenda')
            ->where('id_siswa', $siswa->nis)
            ->orderBy('tanggal', 'desc')
            ->paginate(10);

        return view('siswa.agenda', [
            'siswa' => $siswa,
            'todayAgenda' => $todayAgenda,
            'history' => $history,
            'today' => Carbon::today()->locale('id')->translatedFormat('l, d F Y'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $siswa = DB::table('siswa')->where('id_user', $user->id_user)->first();

        if (!$siswa) {
            return back()->with('error', 'Data siswa tidak ditemukan.');
        }

        $validated = $request->validate([
            'rencana_pekerjaan' => 'required|string',
            'realisasi_pekerjaan' => 'nullable|string',
            'penugasan_khusus_dari_atasan' => 'nullable|string',
            'penemuan_masalah' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        $today = Carbon::today()->toDateString();
        
        $existing = DB::table('agenda')
            ->where('id_siswa', $siswa->nis)
            ->where('tanggal', $today)
            ->first();

        $data = array_merge($validated, [
            'id_siswa' => $siswa->nis,
            'tanggal' => $today,
            // Any student edit resets approval until it is reviewed again.
            'id_instruktur' => null,
            'id_pembimbing' => null,
            'updated_at' => now(),
        ]);

        if ($existing) {
            DB::table('agenda')
                ->where('id_agenda', $existing->id_agenda)
                ->update($data);
            $message = 'Agenda hari ini berhasil diperbarui.';
        } else {
            $data['created_at'] = now();
            DB::table('agenda')->insert($data);
            $message = 'Agenda hari ini berhasil disimpan.';
        }

        return back()->with('success', $message);
    }
}
