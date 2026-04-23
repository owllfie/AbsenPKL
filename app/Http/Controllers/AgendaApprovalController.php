<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgendaApprovalController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $context = $this->resolveApproverContext($request->user());

        if (! $context) {
            return redirect()->route('dashboard')->with('error', 'Akun Anda belum terhubung ke data pembimbing atau instruktur.');
        }

        $tab = $request->query('tab', 'pending');
        $tab = in_array($tab, ['approved', 'pending'], true) ? $tab : 'pending';

        $query = DB::table('agenda')
            ->join('siswa', 'agenda.id_siswa', '=', 'siswa.nis')
            ->leftJoin('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->leftJoin('tempat_pkl', 'siswa.id_tempat', '=', 'tempat_pkl.id_tempat')
            ->select([
                'agenda.id_agenda',
                'agenda.tanggal',
                'agenda.rencana_pekerjaan',
                'agenda.realisasi_pekerjaan',
                'agenda.penugasan_khusus_dari_atasan',
                'agenda.penemuan_masalah',
                'agenda.catatan',
                'agenda.id_instruktur',
                'agenda.id_pembimbing',
                'siswa.nis',
                'siswa.nama_siswa',
                'kelas.kelas',
                'tempat_pkl.nama_perusahaan',
            ])
            ->where($context['student_assignment_column'], $context['approver_id']);

        if ($tab === 'approved') {
            $query->whereNotNull($context['agenda_approval_column']);
        } else {
            $query->whereNull($context['agenda_approval_column']);
        }

        $agendas = $query
            ->orderByDesc('agenda.tanggal')
            ->paginate(10)
            ->withQueryString();

        return view('agenda.review', [
            'tab' => $tab,
            'agendas' => $agendas,
            'roleLabel' => $context['role_label'],
            'approveRoute' => $context['approve_route'],
            'disapproveRoute' => $context['disapprove_route'],
            'approvalColumn' => $context['agenda_approval_column'],
        ]);
    }

    public function approve(Request $request, int $agenda): RedirectResponse
    {
        $context = $this->resolveApproverContext($request->user());
        abort_unless($context, 403);

        $affected = DB::table('agenda')
            ->join('siswa', 'agenda.id_siswa', '=', 'siswa.nis')
            ->where('agenda.id_agenda', $agenda)
            ->where($context['student_assignment_column'], $context['approver_id'])
            ->update([
                $context['agenda_approval_column'] => $context['approver_id'],
            ]);

        return back()->with(
            $affected ? 'success' : 'error',
            $affected ? 'Agenda berhasil di-approve.' : 'Agenda tidak ditemukan atau tidak bisa di-approve oleh akun ini.'
        );
    }

    public function disapprove(Request $request, int $agenda): RedirectResponse
    {
        $context = $this->resolveApproverContext($request->user());
        abort_unless($context, 403);

        $affected = DB::table('agenda')
            ->join('siswa', 'agenda.id_siswa', '=', 'siswa.nis')
            ->where('agenda.id_agenda', $agenda)
            ->where($context['student_assignment_column'], $context['approver_id'])
            ->update([
                $context['agenda_approval_column'] => null,
            ]);

        return back()->with(
            $affected ? 'success' : 'error',
            $affected ? 'Approve agenda berhasil dibatalkan.' : 'Agenda tidak ditemukan atau tidak bisa diubah oleh akun ini.'
        );
    }

    private function resolveApproverContext(object $user): ?array
    {
        if ((int) $user->role === 4) {
            $pembimbingId = DB::table('pembimbing')
                ->where('id_user', $user->id_user)
                ->value('id_pembimbing');

            if (! $pembimbingId) {
                return null;
            }

            return [
                'approver_id' => $pembimbingId,
                'student_assignment_column' => 'siswa.id_pembimbing',
                'agenda_approval_column' => 'id_pembimbing',
                'role_label' => 'Pembimbing',
                'approve_route' => 'agenda.review.approve',
                'disapprove_route' => 'agenda.review.disapprove',
            ];
        }

        if ((int) $user->role === 3) {
            $instrukturQuery = DB::table('instruktur');

            if (Schema::hasColumn('instruktur', 'id_user')) {
                $instrukturQuery->where('id_user', $user->id_user);
            } else {
                $instrukturQuery->where('nama_instruktur', $user->name);
            }

            $instrukturId = $instrukturQuery->value('id_instruktur');

            if (! $instrukturId) {
                return null;
            }

            return [
                'approver_id' => $instrukturId,
                'student_assignment_column' => 'siswa.id_instruktur',
                'agenda_approval_column' => 'id_instruktur',
                'role_label' => 'Instruktur',
                'approve_route' => 'agenda.review.approve',
                'disapprove_route' => 'agenda.review.disapprove',
            ];
        }

        return null;
    }
}
