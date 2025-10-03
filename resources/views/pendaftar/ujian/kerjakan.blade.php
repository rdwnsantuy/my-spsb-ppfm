{{-- resources/views/pendaftar/ujian/kerjakan.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kerjakan Ujian
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        @if (config('app.debug'))
            <div class="mb-3 text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded p-3">
                <div><strong>percobaan_id:</strong> {{ $attempt->id }}</div>
                <div><strong>paket_id:</strong> {{ $attempt->paket_id }}</div>
                <div><strong>urutan:</strong> {{ $urutan }} / {{ $total }}</div>
                <div><strong>opsi_snapshot_ada:</strong> {{ ($item && !empty($item->opsi_snapshot)) ? 'ya' : 'tidak' }}</div>
                <div><strong>waktu_selesai:</strong> {{ optional($attempt->selesai_pada)->format('d/m/Y H:i:s') ?? '—' }}</div>
                <div><strong>sisaDetik(server):</strong> {{ $sisaDetik }}</div>
                <div><strong>jawaban_id:</strong> {{ $item->id }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">{{ session('error') }}</div>
        @endif
        @if (session('warning'))
            <div class="mb-4 p-3 rounded bg-yellow-50 text-yellow-700 border border-yellow-200 text-sm">{{ session('warning') }}</div>
        @endif
        @if (session('ok'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">{{ session('ok') }}</div>
        @endif

        <div class="mb-4 text-sm text-gray-700 flex items-center justify-between flex-wrap gap-2">
            <div class="space-x-2">
                <span class="font-medium">Ujian:</span> {{ $attempt->paket->nama_paket }}
                <span>•</span>
                <span>Soal {{ $urutan }} / {{ $total }}</span>
                <span>•</span>
                <span>Sisa waktu: <span id="timerText">{{ gmdate('i:s', $sisaDetik) }}</span></span>
            </div>
            <div id="saveStatus" class="text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded border bg-gray-100 text-gray-700 border-gray-200">
                    Status: <span class="ml-1 font-medium" data-state>Siap</span>
                </span>
            </div>
        </div>

        {{-- FORM HALAMAN (hanya pegang state pilihan di halaman ini) --}}
        @php
            $nextUrl = $urutan < $total ? route('pendaftar.ujian.show', [$attempt->id, $urutan+1]) : null;
            $prevUrl = ($attempt->paket->boleh_kembali ?? true) && $urutan > 1
                ? route('pendaftar.ujian.show', [$attempt->id, $urutan-1]) : null;
        @endphp
        <form id="ujian-form"
              method="POST"
              action="#"
              data-sisa="{{ $sisaDetik }}"
              data-next-url="{{ $nextUrl }}"
              data-prev-url="{{ $prevUrl }}"
              data-attempt-id="{{ $attempt->id }}"
              data-jawaban-id="{{ $item->id }}">
            @csrf
            <input type="hidden" name="jawaban_id" value="{{ $item->id }}">
            <input type="hidden" name="urutan" value="{{ $urutan }}">

            <div class="bg-white rounded-lg shadow p-5 mb-4">
                <div class="prose max-w-none">{!! $item->teks_soal_snapshot !!}</div>
            </div>

            @php $opsi = collect($item->opsi_snapshot ?? [])->values(); @endphp
            @if($opsi->isNotEmpty())
                <fieldset class="space-y-3 mb-6">
                    @foreach($opsi as $row)
                        @php
                            $label = $row['label'] ?? '';
                            $teks  = $row['teks_opsi'] ?? ($row['teks'] ?? '');
                        @endphp
                        <label class="flex items-start gap-3 p-3 rounded border hover:bg-gray-50 cursor-pointer">
                            <input class="mt-1" type="radio" name="opsi_dipilih"
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

            <div class="flex flex-wrap items-center gap-2">
                @if($prevUrl)
                    <a href="{{ $prevUrl }}" class="inline-flex items-center px-4 py-2 rounded-md border text-sm">
                        Sebelumnya
                    </a>
                @endif

                {{-- NEXT: tombol SELALU tampak, dengan fallback inline-style anti-override --}}
                @if($nextUrl)
                    <button type="button" id="btn-next"
                        class="inline-flex items-center px-4 py-2 rounded-md text-sm border"
                        style="border:1px solid #2563eb;background-color:#2563eb;color:#fff;">
                        Lanjut soal berikutnya
                    </button>
                    {{-- backup link kalau tombol masih tak terlihat karena CSS lain --}}
                    <a id="link-next" href="{{ $nextUrl }}"
                       class="text-xs underline text-blue-700"
                       style="display:block">
                        (Jika tombol tidak terlihat, klik di sini)
                    </a>
                @else
                    <button type="button" id="btn-submit"
                        class="inline-flex items-center px-4 py-2 rounded-md text-sm border"
                        style="border:1px solid #16a34a;background-color:#16a34a;color:#fff;">
                        Kumpulkan Jawaban
                    </button>
                @endif
            </div>
        </form>

        {{-- form tersembunyi untuk submit seluruh percobaan --}}
        <form id="submit-form" method="POST" action="{{ route('pendaftar.ujian.submit', $attempt->id) }}" class="hidden">
            @csrf
            <input type="hidden" name="answers_json" id="answers_json">
        </form>
    </div>

    <script>
        (function () {
            const form        = document.getElementById('ujian-form');
            const btnNext     = document.getElementById('btn-next');
            const linkNext    = document.getElementById('link-next');
            const btnSubmit   = document.getElementById('btn-submit');
            const submitForm  = document.getElementById('submit-form');
            const timerText   = document.getElementById('timerText');
            const saveStateEl = document.querySelector('#saveStatus [data-state]');

            let sisa = parseInt(form.dataset.sisa || '0', 10);

            function tag(tone) {
                const base = 'inline-flex items-center px-2 py-0.5 rounded border ';
                if (tone === 'saving') return base + 'bg-blue-50 text-blue-700 border-blue-200';
                if (tone === 'saved')  return base + 'bg-green-50 text-green-700 border-green-200';
                if (tone === 'warn')   return base + 'bg-red-50 text-red-700 border-red-200';
                return base + 'bg-gray-100 text-gray-700 border-gray-200';
            }
            function setSaveState(text, tone = 'idle') {
                if (!saveStateEl) return;
                saveStateEl.textContent = text;
                saveStateEl.parentElement.className = tag(tone);
            }

            const attemptId  = form.dataset.attemptId || '{{ $attempt->id }}';
            const jawabanId  = form.dataset.jawabanId || '{{ $item->id }}';
            const storageKey = (id) => `ujian:${id}:answers`;

            function loadAll() {
                try { return JSON.parse(localStorage.getItem(storageKey(attemptId)) || '{}'); }
                catch { return {}; }
            }
            function persist(obj) {
                localStorage.setItem(storageKey(attemptId), JSON.stringify(obj));
            }
            function saveCurrentSelection() {
                const checked = form.querySelector('input[name="opsi_dipilih"]:checked');
                const all = loadAll();
                all[jawabanId] = checked ? checked.value : null;
                persist(all);
                setSaveState('Tersimpan di perangkat', 'saved');
            }
            function restoreSelection() {
                const all = loadAll();
                const val = all[jawabanId] ?? null;
                if (!val) return;
                const target = form.querySelector(`input[name="opsi_dipilih"][value="${val}"]`);
                if (target) target.checked = true;
            }
            function buildAnswersJson() {
                saveCurrentSelection();
                return JSON.stringify(loadAll());
            }

            // Init
            restoreSelection();
            setSaveState('Siap', 'idle');

            // NEXT (button)
            btnNext?.addEventListener('click', () => {
                const nextUrl = form.dataset.nextUrl;
                saveCurrentSelection();
                if (nextUrl) window.location.assign(nextUrl);
            });

            // NEXT (backup link) – tetap simpan dulu
            linkNext?.addEventListener('click', (e) => {
                saveCurrentSelection();
            });

            // SUBMIT (soal terakhir)
            btnSubmit?.addEventListener('click', () => {
                const payload = buildAnswersJson();
                document.getElementById('answers_json').value = payload;
                submitForm.submit();
            });

            // Timer (auto submit saat habis)
            function tick() {
                if (sisa <= 0) {
                    setSaveState('Waktu habis, mengirim…', 'saving');
                    const payload = buildAnswersJson();
                    document.getElementById('answers_json').value = payload;
                    submitForm.submit();
                    return;
                }
                sisa -= 1;
                const m = Math.floor(sisa / 60), s = sisa % 60;
                timerText.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                setTimeout(tick, 1000);
            }
            setTimeout(tick, 1000);

            form.addEventListener('submit', (e) => e.preventDefault());
        })();
    </script>
</x-app-layout>
