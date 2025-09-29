<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class JawabanUjian extends Model
{
    use HasFactory;

    protected $table = 'jawaban_ujian';
    protected $guarded = [];

    protected $casts = [
        // JSON berisi array opsi: [{label, teks_opsi, benar}, ...]
        'opsi_snapshot' => 'array',
        'benar'         => 'boolean',
    ];

    /* =========================
     |  Relationships
     |=========================*/
    public function percobaan(): BelongsTo
    {
        return $this->belongsTo(PercobaanUjian::class, 'percobaan_id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriSoal::class, 'kategori_id');
    }

    /* =========================
     |  Accessors & Mutators
     |=========================*/

    /**
     * Pastikan opsi_snapshot selalu array (tidak null).
     * @return array<int, array{label:string,teks_opsi:string,benar:bool}>
     */
    public function getOpsiSnapshotAttribute($value): array
    {
        if (is_array($value)) return $value;
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * Normalisasi opsi_dipilih ke huruf A–E (uppercase) atau null.
     */
    public function setOpsiDipilihAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['opsi_dipilih'] = null;
            return;
        }

        $val = strtoupper(trim((string) $value));
        $this->attributes['opsi_dipilih'] = in_array($val, ['A','B','C','D','E'], true) ? $val : null;
    }

    /* =========================
     |  Helpers
     |=========================*/

    /**
     * Kembalikan opsi sebagai Collection untuk memudahkan manipulasi.
     * @return \Illuminate\Support\Collection<int, array{label:string,teks_opsi:string,benar:bool}>
     */
    public function opsi(): Collection
    {
        return collect($this->opsi_snapshot);
    }

    /**
     * Ambil label kunci dari snapshot (mis. "C"). Null bila tidak ada.
     */
    public function kunciLabel(): ?string
    {
        $kunci = $this->opsi()->firstWhere('benar', true);
        return is_array($kunci) ? ($kunci['label'] ?? null) : null;
    }

    /**
     * Apakah jawaban yang dipilih benar (berdasarkan snapshot)?
     */
    public function isBenar(): bool
    {
        $kunci = $this->kunciLabel();
        return $kunci !== null && $this->opsi_dipilih === $kunci;
    }

    /* =========================
     |  Query Scopes
     |=========================*/

    public function scopeOfPercobaan($query, int $percobaanId)
    {
        return $query->where('percobaan_id', $percobaanId);
    }

    public function scopeUrutan($query, int $urutan)
    {
        return $query->where('urutan_soal', $urutan);
    }

    public function scopeBelumDijawab($query)
    {
        return $query->whereNull('opsi_dipilih');
    }
}
