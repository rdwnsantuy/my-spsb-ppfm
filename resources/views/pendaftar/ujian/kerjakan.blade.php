<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kerjakan Ujian
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        {{-- DEBUG SEMENTARA — hanya muncul saat APP_DEBUG=true --}}
        @if (config('app.debug'))
            <div class="mb-3 text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded p-3">
                <div><strong>percobaan_id:</strong> {{ $attempt->id }}</div>
                <div><strong>paket_id:</strong> {{ $attempt->paket_id }}</div>
                <div><strong>urutan:</strong> {{ $urutan }} / {{ $total }}</div>
                <div><strong>opsi_snapshot_ada:</strong> {{ ($item && !empty($item->opsi_snapshot)) ? 'ya' : 'tidak' }}</div>
                <div><strong>waktu_selesai:</strong> {{ optional($attempt->selesai_pada)->format('d/m/Y H:i:s') ?? '—' }}</div>
                <div><strong>sisaDetik(server):</strong> {{ $sisaDetik }}</div>
            </div>
        @endif

        {{-- Flash messages --}}
        @if (session('error'))
            <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">
                {{ session('error') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="mb-4 p-3 rounded bg-yellow-50 text-yellow-700 border border-yellow-200">
                {{ session('warning') }}
            </div>
        @endif
        @if (session('ok'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('ok') }}
            </div>
        @endif

        {{-- Info ujian + timer + status simpan --}}
        <div class="mb-4 text-sm text-gray-700 flex items-center justify-between flex-wrap gap-2">
            <div class="space-x-2">
                <span class="font-medium">Ujian:</span> {{ $attempt->paket->nama_paket }}
                <span>•</span>
                <span>Soal {{ $urutan }} / {{ $total }}</span>
                <span>•</span>
                <span>Sisa waktu: <span id="timerText">{{ gmdate('i:s', $sisaDetik) }}</span></span>
            </div>
            <div id="saveStatus" class="text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700 border border-gray-200">
                    Status: <span class="ml-1 font-medium" data-state="idle">Siap</span>
                </span>
            </div>
        </div>

        <form id="ujian-form"
              method="POST"
              action="{{ route('pendaftar.ujian.save', $attempt->id) }}"
              data-sisa="{{ $sisaDetik }}"
              data-next-url="{{ $next ? route('pendaftar.ujian.show', [$attempt->id, $next]) : '' }}"
              data-has-next="{{ $next ? '1' : '0' }}"
        >
            @csrf
            <input type="hidden" name="jawaban_id" value="{{ $item->id }}">
            <input type="hidden" name="redirect_to" id="redirect_to" value=""> {{-- optional flag --}}

            <div class="bg-white rounded-lg shadow p-5 mb-4">
                <div class="prose max-w-none">
                    {!! $item->teks_soal_snapshot !!}
                </div>
            </div>

            @php
                // Pastikan $item->opsi_snapshot berupa array koleksi {label, teks_opsi}
                $opsi = collect($item->opsi_snapshot ?? [])->values();
            @endphp

            @if($opsi->isNotEmpty())
                <fieldset class="space-y-3 mb-6">
                    @foreach($opsi as $row)
                        @php
                            $label = $row['label'] ?? '';
                            $teks  = $row['teks_opsi'] ?? '';
                        @endphp
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                class="mt-1"
                                type="radio"
                                name="opsi_dipilih"
                                value="{{ $label }}"
                                {{ $item->opsi_dipilih === $label ? 'checked' : '' }}>
                            <span>
                                <span class="font-semibold">{{ $label }}.</span>
                                <span class="prose max-w-none">{!! $teks !!}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>
            @else
                <div class="mb-6 p-3 rounded bg-yellow-50 text-yellow-700 border border-yellow-200 text-sm">
                    Opsi jawaban tidak tersedia untuk soal ini.
                </div>
            @endif

            <div class="flex flex-wrap gap-2">
                @if($allowBack && $prev && $prev < $urutan)
                    <a href="{{ route('pendaftar.ujian.show', [$attempt->id, $prev]) }}"
                       class="inline-flex items-center px-4 py-2 rounded-md border text-sm">
                        Sebelumnya
                    </a>
                @endif

                @if($next)
                    {{-- Simpan & Lanjut: autosave lalu redirect --}}
                    <button type="button" id="btn-next"
                            class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">
                        Simpan &amp; Lanjut
                    </button>
                @else
                    {{-- Kumpulkan: autosave lalu submit --}}
                    <button type="button" id="btn-submit"
                            class="inline-flex items-center px-4 py-2 rounded-md bg-green-600 text-white text-sm hover:bg-green-700">
                        Kumpulkan Jawaban
                    </button>
                @endif

                {{-- Simpan manual --}}
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 rounded-md bg-gray-100 text-gray-700 border text-sm">
                    Simpan
                </button>
            </div>
        </form>

        {{-- Form submit tersembunyi untuk auto-submit saat waktu habis --}}
        <form id="submit-form" method="POST" action="{{ route('pendaftar.ujian.submit', $attempt->id) }}" class="hidden">
            @csrf
        </form>
    </div>

    {{-- ====== Script mini: timer, autosave, keyboard, guard ====== --}}
    <script>
        (function () {
            const form = document.getElementById('ujian-form');
            const submitForm = document.getElementById('submit-form');
            const radios = form.querySelectorAll('input[name="opsi_dipilih"]');
            const btnNext = document.getElementById('btn-next');
            const btnSubmit = document.getElementById('btn-submit');
            const timerText = document.getElementById('timerText');
            const saveStatusWrap = document.getElementById('saveStatus');
            const saveStateEl = saveStatusWrap?.querySelector('[data-state]');
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            let sisa = parseInt(form.dataset.sisa || '0', 10);
            let saving = false;
            let dirty = false; // ada perubahan belum tersimpan

            function setSaveState(stateText, tone = 'idle') {
                if (!saveStateEl) return;
                saveStateEl.textContent = stateText;
                // tone: idle | saving | saved | error
                saveStateEl.parentElement.className =
                    'inline-flex items-center px-2 py-0.5 rounded border ' + ({
                        'idle': 'bg-gray-100 text-gray-700 border-gray-200',
                        'saving': 'bg-blue-50 text-blue-700 border-blue-200',
                        'saved': 'bg-green-50 text-green-700 border-green-200',
                        'error': 'bg-red-50 text-red-700 border-red-200',
                    }[tone] || 'bg-gray-100 text-gray-700 border-gray-200');
            }

            async function autosave() {
                if (saving) return;
                saving = true;
                setSaveState('Menyimpan…', 'saving');

                try {
                    const fd = new FormData(form);
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: token ? { 'X-CSRF-TOKEN': token } : {},
                        body: fd
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    // sukses
                    dirty = false;
                    setSaveState('Tersimpan', 'saved');
                } catch (e) {
                    console.error(e);
                    setSaveState('Gagal menyimpan', 'error');
                } finally {
                    saving = false;
                }
            }

            // Debounce kecil untuk autosave
            let debounce;
            function scheduleAutosave() {
                clearTimeout(debounce);
                debounce = setTimeout(autosave, 500);
            }

            // Tandai dirty bila pilihan berubah
            radios.forEach(r => {
                r.addEventListener('change', () => {
                    dirty = true;
                    scheduleAutosave();
                });
            });

            // Tombol Next: pastikan simpan dulu
            btnNext?.addEventListener('click', async () => {
                const nextUrl = form.dataset.nextUrl;
                if (!nextUrl) return;
                if (dirty) {
                    await autosave();
                }
                window.location.assign(nextUrl);
            });

            // Tombol Submit: simpan dulu kalau perlu, lalu submit
            btnSubmit?.addEventListener('click', async () => {
                if (dirty) {
                    await autosave();
                }
                submitForm.submit();
            });

            // Timer countdown (auto-submit jika habis)
            function tick() {
                if (sisa <= 0) {
                    // Habis waktu: matikan interaksi dan submit
                    radios.forEach(r => r.disabled = true);
                    btnNext && (btnNext.disabled = true);
                    btnSubmit && (btnSubmit.disabled = true);
                    setSaveState('Waktu habis, mengirim…', 'saving');
                    submitForm.submit();
                    return;
                }
                sisa -= 1;
                const m = Math.floor(sisa / 60);
                const s = sisa % 60;
                if (timerText) timerText.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                setTimeout(tick, 1000);
            }
            setTimeout(tick, 1000);

            // Peringatan saat mau keluar halaman kalau belum tersimpan
            window.addEventListener('beforeunload', (e) => {
                if (dirty) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // Keyboard shortcuts: A/B/C/D/E untuk pilih, S simpan, Enter = next/submit
            document.addEventListener('keydown', (e) => {
                const key = e.key.toLowerCase();
                const map = { a:'A', b:'B', c:'C', d:'D', e:'E' };
                if (key in map) {
                    const target = Array.from(radios).find(r => r.value.toUpperCase() === map[key]);
                    if (target && !target.disabled) {
                        target.checked = true;
                        target.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                } else if (key === 's' && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    autosave();
                } else if (key === 'enter') {
                    e.preventDefault();
                    if (btnNext) btnNext.click();
                    else if (btnSubmit) btnSubmit.click();
                }
            });
        })();
    </script>
</x-app-layout>
