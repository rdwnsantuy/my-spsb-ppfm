<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PercobaanUjian;
use App\Models\PaketUjian;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapUjianController extends Controller
{
    public function index(Request $request)
    {
        $pakets = PaketUjian::orderBy('nama_paket')->get();

        $rows = PercobaanUjian::with(['paket','user','nilaiKategori.kategori'])
            ->when($request->filled('paket_id'), fn($q) => $q->where('paket_id', $request->paket_id))
            ->latest()
            ->paginate(25);

        return view('admin.ujian.rekap.index', compact('rows','pakets'));
    }

    /** Export CSV sederhana */
    public function exportCsv(Request $request): StreamedResponse
    {
        $fileName = 'rekap_ujian.csv';

        $rows = PercobaanUjian::with(['paket','user','nilaiKategori.kategori'])
            ->when($request->filled('paket_id'), fn($q) => $q->where('paket_id', $request->paket_id))
            ->latest()
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
        ];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['User', 'Paket', 'Status', 'Skor Total', 'Lulus', 'Dibuat', 'Selesai']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->user->username ?? $r->user->name,
                    $r->paket->nama_paket ?? '-',
                    $r->status,
                    $r->skor_total,
                    $r->lulus ? 'YA' : 'TIDAK',
                    optional($r->created_at)->toDateTimeString(),
                    optional($r->selesai_pada)->toDateTimeString(),
                ]);
            }

            fclose($out);
        }, 200, $headers);
    }
}
