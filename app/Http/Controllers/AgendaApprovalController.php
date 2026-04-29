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
            ->leftJoin('penilaian', 'agenda.id_agenda', '=', 'penilaian.id_agenda')
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
                'siswa.nama_siswa as student_name',
                'siswa.nis',
                'penilaian.senyum',
                'penilaian.keramahan',
                'penilaian.penampilan',
                'penilaian.komunikasi',
                'penilaian.realisasi_kerja',
            ]);

        if ($context['restrict_by_assignment']) {
            $query->where($context['student_assignment_column'], $context['approver_id']);
        }

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
            'supportsAssessment' => $context['supports_assessment'],
            'approveRoute' => $context['approve_route'],
            'disapproveRoute' => $context['disapprove_route'],
            'approvalColumn' => $context['agenda_approval_field'],
        ]);
    }

    public function approve(Request $request, int $agenda): RedirectResponse
    {
        $context = $this->resolveApproverContext($request->user());
        abort_unless($context, 403);

        $query = DB::table('agenda')
            ->join('siswa', 'agenda.id_siswa', '=', 'siswa.nis')
            ->where('agenda.id_agenda', $agenda)
            ->when(
                $context['restrict_by_assignment'],
                fn ($query) => $query->where($context['student_assignment_column'], $context['approver_id'])
            );

        if ($context['supports_assessment']) {
            $query->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('penilaian')
                    ->whereColumn('penilaian.id_agenda', 'agenda.id_agenda');
            });
        }

        $affected = $query->update([
            $context['agenda_approval_column'] => $context['approver_id'],
        ]);

        return back()->with(
            $affected ? 'success' : 'error',
            $affected
                ? 'Agenda berhasil di-approve.'
                : ($context['supports_assessment']
                    ? 'Agenda tidak ditemukan, tidak bisa di-approve oleh akun ini, atau belum memiliki penilaian.'
                    : 'Agenda tidak ditemukan atau tidak bisa di-approve oleh akun ini.')
        );
    }

    public function saveAssessment(Request $request, int $agenda): RedirectResponse
    {
        $context = $this->resolveApproverContext($request->user());
        abort_unless($context, 403);
        abort_unless($context['supports_assessment'], 403);

        $validated = $request->validate([
            'senyum' => 'required|in:0,1',
            'keramahan' => 'required|in:0,1',
            'penampilan' => 'required|in:0,1',
            'komunikasi' => 'required|in:0,1',
            'realisasi_kerja' => 'required|in:0,1',
        ]);

        $agendaRow = DB::table('agenda')
            ->join('siswa', 'agenda.id_siswa', '=', 'siswa.nis')
            ->select(['agenda.id_agenda', 'agenda.id_siswa'])
            ->where('agenda.id_agenda', $agenda)
            ->when(
                $context['restrict_by_assignment'],
                fn ($query) => $query->where($context['student_assignment_column'], $context['approver_id'])
            )
            ->first();

        if (! $agendaRow) {
            return back()->with('error', 'Agenda tidak ditemukan atau tidak bisa dinilai oleh akun ini.');
        }

        $payload = $validated + [
            'id_siswa' => $agendaRow->id_siswa,
            'id_agenda' => $agendaRow->id_agenda,
            'updated_at' => now(),
            'updated_by' => $request->user()->id_user,
        ];

        $existing = DB::table('penilaian')
            ->where('id_agenda', $agendaRow->id_agenda)
            ->value('id_penilaian');

        if ($existing) {
            DB::table('penilaian')
                ->where('id_penilaian', $existing)
                ->update($payload);
        } else {
            $payload['created_at'] = now();
            $payload['created_by'] = $request->user()->id_user;
            DB::table('penilaian')->insert($payload);
        }

        return back()->with('success', 'Penilaian agenda berhasil disimpan.');
    }

    public function disapprove(Request $request, int $agenda): RedirectResponse
    {
        $context = $this->resolveApproverContext($request->user());
        abort_unless($context, 403);

        $canAccess = DB::table('agenda')
            ->join('siswa', 'agenda.id_siswa', '=', 'siswa.nis')
            ->where('agenda.id_agenda', $agenda)
            ->when(
                $context['restrict_by_assignment'],
                fn ($query) => $query->where($context['student_assignment_column'], $context['approver_id'])
            )
            ->exists();

        if (! $canAccess) {
            return back()->with('error', 'Agenda tidak ditemukan atau tidak bisa diubah oleh akun ini.');
        }

        DB::table('agenda')
            ->where('agenda.id_agenda', $agenda)
            ->update([
                $context['agenda_approval_column'] => null,
            ]);

        return back()->with('success', 'Agenda ditandai sebagai not approved.');
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

            $hasAssignedStudents = DB::table('siswa')
                ->where('id_pembimbing', $pembimbingId)
                ->exists();

            return [
                'approver_id' => $pembimbingId,
                'student_assignment_column' => 'siswa.id_pembimbing',
                'agenda_approval_column' => 'agenda.id_pembimbing',
                'agenda_approval_field' => 'id_pembimbing',
                'restrict_by_assignment' => $hasAssignedStudents,
                'supports_assessment' => false,
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

            $hasAssignedStudents = DB::table('siswa')
                ->where('id_instruktur', $instrukturId)
                ->exists();

            return [
                'approver_id' => $instrukturId,
                'student_assignment_column' => 'siswa.id_instruktur',
                'agenda_approval_column' => 'agenda.id_instruktur',
                'agenda_approval_field' => 'id_instruktur',
                'restrict_by_assignment' => $hasAssignedStudents,
                'supports_assessment' => true,
                'role_label' => 'Instruktur',
                'approve_route' => 'agenda.review.approve',
                'disapprove_route' => 'agenda.review.disapprove',
            ];
        }

        return null;
    }
}
