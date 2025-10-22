<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bank Soal & Bobot Kategori</h2>
  </x-slot>

  <div class="max-w-7xl mx-auto space-y-6">
    @if(session('ok'))
      <div class="p-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">{{ session('ok') }}</div>
    @endif

    <div x-data="{tab: $persist('admin_soal_tab') || 'soal'}">
      <div class="flex gap-2">
        <button @click="tab='soal'"     :class="tab==='soal'?'bg-indigo-600 text-white':'bg-white text-gray-700'" class="px-4 h-10 rounded-xl border border-gray-200">Soal</button>
        <button @click="tab='kategori'" :class="tab==='kategori'?'bg-indigo-600 text-white':'bg-white text-gray-700'" class="px-4 h-10 rounded-xl border border-gray-200">Kategori</button>
        <button @click="tab='bobot'"    :class="tab==='bobot'?'bg-indigo-600 text-white':'bg-white text-gray-700'" class="px-4 h-10 rounded-xl border border-gray-200">Bobot Kategori</button>
      </div>

      {{-- TAB SOAL --}}
      <section x-show="tab==='soal'" class="mt-4 space-y-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
          <form method="GET" class="grid gap-3 md:grid-cols-12">
            <div class="md:col-span-6">
              <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari pertanyaan" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" />
            </div>
            <div class="md:col-span-4">
              <select name="kategori" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Semua kategori</option>
                @foreach($kategori as $k)
                  <option value="{{ $k->id }}" @selected(request('kategori')==$k->id)>{{ $k->nama_kategori }}</option>
                @endforeach
              </select>
            </div>
            <div class="md:col-span-2">
              <button class="w-full h-10 rounded-xl bg-indigo-600 text-white font-medium">Filter</button>
            </div>
          </form>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
          <form method="POST" action="{{ route('admin.soal.item.store') }}" class="grid gap-3 md:grid-cols-12">
            @csrf
            <div class="md:col-span-4">
              <label class="text-sm text-gray-600">Kategori</label>
              <select name="kategori_id" required class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                @foreach($kategori as $k)
                  <option value="{{ $k->id }}">{{ $k->nama_kategori }}</option>
                @endforeach
              </select>
            </div>
            <div class="md:col-span-2">
              <label class="text-sm text-gray-600">Tipe</label>
              <select name="tipe" x-data x-on:change="$dispatch('tipe-changed',$event.target.value)" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="pg">Pilihan Ganda</option>
                <option value="isian">Isian</option>
                <option value="esai">Esai</option>
              </select>
            </div>
            <div class="md:col-span-2">
              <label class="text-sm text-gray-600">Bobot</label>
              <input type="number" name="bobot" min="1" max="100" value="1" class="w-full rounded-xl border-gray-300" />
            </div>
            <div class="md:col-span-12">
              <label class="text-sm text-gray-600">Pertanyaan</label>
              <textarea name="pertanyaan" required class="w-full rounded-xl border-gray-300" rows="3" placeholder="Tulis teks pertanyaan..."></textarea>
            </div>

            {{-- opsi PG --}}
            <div class="md:col-span-12" x-data="{show:true}" @tipe-changed.window="show = ($event.detail==='pg')">
              <template x-if="show">
                <div class="grid gap-3 md:grid-cols-4">
                  <div><label class="text-sm text-gray-600">Opsi A</label><input type="text" name="opsi[]" class="w-full rounded-xl border-gray-300" /></div>
                  <div><label class="text-sm text-gray-600">Opsi B</label><input type="text" name="opsi[]" class="w-full rounded-xl border-gray-300" /></div>
                  <div><label class="text-sm text-gray-600">Opsi C</label><input type="text" name="opsi[]" class="w-full rounded-xl border-gray-300" /></div>
                  <div><label class="text-sm text-gray-600">Opsi D</label><input type="text" name="opsi[]" class="w-full rounded-xl border-gray-300" /></div>
                </div>
              </template>
            </div>

            {{-- kunci --}}
            <div class="md:col-span-3" x-data="{tipe:'pg'}" @tipe-changed.window="tipe=$event.detail">
              <label class="text-sm text-gray-600">Kunci Jawaban</label>
              <template x-if="tipe==='pg'">
                <select name="kunci" class="w-full rounded-xl border-gray-300">
                  <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
                </select>
              </template>
              <template x-if="tipe==='isian'">
                <input type="text" name="kunci" class="w-full rounded-xl border-gray-300" placeholder="Jawaban tepat" />
              </template>
              <template x-if="tipe==='esai'">
                <input type="text" class="w-full rounded-xl border-gray-300" placeholder="(Tidak wajib untuk esai)" disabled />
              </template>
            </div>

            <div class="md:col-span-3 flex items-end">
              <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="aktif" value="1" class="rounded" checked> Aktif
              </label>
            </div>
            <div class="md:col-span-6 flex items-end justify-end">
              <button class="h-10 px-4 rounded-xl bg-indigo-600 text-white">Tambah Soal</button>
            </div>
          </form>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr class="text-left text-gray-700">
                <th class="px-3 py-2">Kategori</th>
                <th class="px-3 py-2">Pertanyaan</th>
                <th class="px-3 py-2">Tipe</th>
                <th class="px-3 py-2">Bobot</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @foreach($soal as $s)
                <tr>
                  <td class="px-3 py-2 whitespace-nowrap">{{ $s->kategori->nama_kategori ?? '-' }}</td>
                  <td class="px-3 py-2 max-w-[60ch] text-gray-800">{{ Str::limit(strip_tags($s->pertanyaan), 150) }}</td>
                  <td class="px-3 py-2 uppercase">{{ $s->tipe }}</td>
                  <td class="px-3 py-2">{{ $s->bobot }}</td>
                  <td class="px-3 py-2">
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $s->aktif ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-gray-100 text-gray-700 ring-1 ring-gray-200' }}">{{ $s->aktif ? 'Aktif':'Nonaktif' }}</span>
                  </td>
                  <td class="px-3 py-2 text-right">
                    <div class="inline-flex gap-2">
                      <form method="POST" action="{{ route('admin.soal.item.update',$s) }}" class="inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="kategori_id" value="{{ $s->kategori_id }}">
                        <input type="hidden" name="pertanyaan" value="{{ $s->pertanyaan }}">
                        <input type="hidden" name="tipe" value="{{ $s->tipe }}">
                        <input type="hidden" name="bobot" value="{{ $s->bobot }}">
                        <input type="hidden" name="aktif" value="{{ $s->aktif ? 1:0 }}">
                        <button class="h-9 px-3 rounded-lg border border-gray-200">Quick Save</button>
                      </form>
                      <form method="POST" action="{{ route('admin.soal.item.destroy',$s) }}" onsubmit="return confirm('Hapus soal ini?')">
                        @csrf @method('DELETE')
                        <button class="h-9 px-3 rounded-lg border border-red-200 text-red-700">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
          <div class="mt-4">{{ $soal->links() }}</div>
        </div>
      </section>

      {{-- TAB KATEGORI --}}
      <section x-show="tab==='kategori'" class="mt-4 grid gap-4 md:grid-cols-12">
        <div class="md:col-span-5 bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
          <h3 class="font-semibold mb-3">Tambah Kategori</h3>
          <form method="POST" action="{{ route('admin.soal.kategori.store') }}" class="space-y-3">
            @csrf
            <input type="text" name="nama_kategori" class="w-full rounded-xl border-gray-300" placeholder="Nama kategori" required>
            <textarea name="deskripsi" rows="3" class="w-full rounded-xl border-gray-300" placeholder="Deskripsi (opsional)"></textarea>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="aktif" value="1" class="rounded" checked> Aktif</label>
            <button class="h-10 px-4 rounded-xl bg-indigo-600 text-white">Simpan</button>
          </form>
        </div>

        <div class="md:col-span-7 bg-white rounded-2xl border border-gray-200 p-4 shadow-sm overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50"><tr><th class="px-3 py-2">Nama</th><th class="px-3 py-2">Deskripsi</th><th class="px-3 py-2">Status</th><th class="px-3 py-2 text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
              @foreach($kategori as $k)
                <tr>
                  <td class="px-3 py-2 font-medium">{{ $k->nama_kategori }}</td>
                  <td class="px-3 py-2 text-gray-600">{{ $k->deskripsi }}</td>
                  <td class="px-3 py-2">{!! $k->aktif ? '<span class="px-2 py-0.5 rounded-full text-xs bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">Aktif</span>' : '<span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700 ring-1 ring-gray-200">Nonaktif</span>' !!}</td>
                  <td class="px-3 py-2 text-right">
                    <form method="POST" action="{{ route('admin.soal.kategori.update',$k) }}" class="inline">@csrf @method('PUT')
                      <input type="hidden" name="nama_kategori" value="{{ $k->nama_kategori }}">
                      <input type="hidden" name="deskripsi" value="{{ $k->deskripsi }}">
                      <input type="hidden" name="aktif" value="{{ $k->aktif ? 1:0 }}">
                      <button class="h-9 px-3 rounded-lg border border-gray-200">Quick Save</button>
                    </form>
                    <form method="POST" action="{{ route('admin.soal.kategori.destroy',$k) }}" class="inline" onsubmit="return confirm('Hapus kategori? Data soal ikut terhapus.')">@csrf @method('DELETE')
                      <button class="h-9 px-3 rounded-lg border border-red-200 text-red-700">Hapus</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </section>

      {{-- TAB BOBOT --}}
      <section x-show="tab==='bobot'" class="mt-4 space-y-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
          <form method="GET" class="flex gap-3 items-end">
            <div class="w-72">
              <label class="text-sm text-gray-600">Paket</label>
              <select name="paket" class="w-full rounded-xl border-gray-300">
                <option value="">-- Pilih Paket --</option>
                @foreach($paket as $p)
                  <option value="{{ $p->id }}" @selected($paketTerpilih==$p->id)>{{ $p->nama_paket }}</option>
                @endforeach
              </select>
            </div>
            <button class="h-10 px-4 rounded-xl bg-indigo-600 text-white">Lihat</button>
          </form>
        </div>

        @if($paketTerpilih)
          <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <h3 class="font-semibold">Atur Bobot per Kategori</h3>
            <form method="POST" action="{{ route('admin.soal.bobot.store') }}" class="grid md:grid-cols-12 gap-3 mt-3">
              @csrf
              <input type="hidden" name="paket_id" value="{{ $paketTerpilih }}">
              <div class="md:col-span-6">
                <select name="kategori_id" class="w-full rounded-xl border-gray-300">
                  @foreach($kategori as $k)
                    <option value="{{ $k->id }}">{{ $k->nama_kategori }}</option>
                  @endforeach
                </select>
              </div>
              <div class="md:col-span-3">
                <input type="number" name="bobot_kategori" min="0" max="100" placeholder="Bobot (%)" class="w-full rounded-xl border-gray-300" required>
              </div>
              <div class="md:col-span-3">
                <input type="number" name="ambang_kelulusan" min="0" max="100" placeholder="Ambang (%)" class="w-full rounded-xl border-gray-300">
              </div>
              <div class="md:col-span-12 text-right">
                <button class="h-10 px-4 rounded-xl bg-indigo-600 text-white">Tambah/Update</button>
              </div>
            </form>

            <div class="mt-4 overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-gray-50"><tr><th class="px-3 py-2">Kategori</th><th class="px-3 py-2">Bobot</th><th class="px-3 py-2">Ambang</th><th class="px-3 py-2 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                  @forelse($bobot as $kat)
                    <tr>
                      <td class="px-3 py-2">{{ $kat->nama_kategori }}</td>
                      <td class="px-3 py-2">{{ $kat->pivot->bobot_kategori }}%</td>
                      <td class="px-3 py-2">{{ $kat->pivot->ambang_kelulusan ?? '—' }}%</td>
                      <td class="px-3 py-2 text-right">
                        <form method="POST" action="{{ route('admin.soal.bobot.update',$kat->pivot->id) }}" class="inline">@csrf @method('PUT')
                          <input type="hidden" name="bobot_kategori" value="{{ $kat->pivot->bobot_kategori }}">
                          <input type="hidden" name="ambang_kelulusan" value="{{ $kat->pivot->ambang_kelulusan }}">
                          <button class="h-9 px-3 rounded-lg border border-gray-200">Quick Save</button>
                        </form>
                        <form method="POST" action="{{ route('admin.soal.bobot.destroy',$kat->pivot->id) }}" class="inline" onsubmit="return confirm('Hapus bobot kategori ini dari paket?')">@csrf @method('DELETE')
                          <button class="h-9 px-3 rounded-lg border border-red-200 text-red-700">Hapus</button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="4" class="px-3 py-6 text-center text-gray-600">Belum ada bobot kategori untuk paket ini.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        @endif
      </section>
    </div>
  </div>
</x-app-layout>
