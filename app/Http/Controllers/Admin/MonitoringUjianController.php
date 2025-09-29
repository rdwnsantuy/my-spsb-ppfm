<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PercobaanUjian;
use Illuminate\Http\Request;

class MonitoringUjianController extends Controller
{
    public function index(Request $request)
    {
        $rows = PercobaanUjian::with(['paket','user'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return view('admin.ujian.monitoring.index', compact('rows'));
    }

    /** (Opsional) paksa kumpulkan/expire 1 attempt */
    public function forceSubmit(int $id)
    {
        $attempt = PercobaanUjian::with('paket')->findOrFail($id);
        if (in_array($attempt->status, ['selesai','kadaluarsa'])) {
            return back()->with('warning', 'Percobaan sudah berakhir.');
        }

        // Mark as kadaluarsa namun tetap hitung nilai atas jawaban yang ada
        app(\App\Http\Controllers\Ujian\PengerjaanUjianController::class)->forceExpire($attempt);

        return back()->with('ok', 'Percobaan ditutup & dinilai.');
    }
}
