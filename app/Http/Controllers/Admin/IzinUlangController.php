<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IzinUlangUjian;
use App\Models\PaketUjian;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IzinUlangController extends Controller
{
    public function index()
    {
        return view('admin.izin-ulang.index', [
            'izinList' => IzinUlangUjian::with(['user','paket'])->latest()->paginate(20),
            'users'    => User::where('role','pendaftar')->orderBy('name')->get(['id','name','username']),
            'paket'    => PaketUjian::orderBy('nama_paket')->get(['id','nama_paket','maksimal_percobaan']),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'user_id'        => ['required','exists:users,id'],
            'paket_id'       => ['required','exists:paket_ujian,id'],
            'kuota_tambahan' => ['required','integer','min:1','max:10'],
            'berlaku_sampai' => ['nullable','date'],
            'alasan'         => ['nullable','string','max:1000'],
        ]);

        $data['granted_by'] = $r->user()->id;

        DB::transaction(function () use ($data) {
            // 1) HAPUS semua baris NONAKTIF untuk pasangan ini
            IzinUlangUjian::where('user_id', $data['user_id'])
                ->where('paket_id', $data['paket_id'])
                ->where('status', 'nonaktif')
                ->delete();

            // 2) NONAKTIFKAN izin AKTIF (jika ada)
            IzinUlangUjian::where('user_id', $data['user_id'])
                ->where('paket_id', $data['paket_id'])
                ->where('status', 'aktif')
                ->update(['status' => 'nonaktif']);

            // 3) BUAT izin AKTIF baru
            IzinUlangUjian::create($data);
        });

        return back()->with('ok','Izin ujian ulang diberikan.');
    }

    public function nonaktif(IzinUlangUjian $izin)
    {
        $izin->update(['status'=>'nonaktif']);
        return back()->with('ok','Izin dinonaktifkan.');
    }
}
