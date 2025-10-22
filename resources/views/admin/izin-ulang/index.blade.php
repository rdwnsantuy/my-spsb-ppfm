<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Izin Ujian Ulang</h2>
  </x-slot>

  <div class="max-w-5xl mx-auto space-y-6">

    {{-- Flash messages --}}
    @if (session('ok'))
      <div class="p-3 rounded bg-green-50 text-green-700 border border-green-200">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
      <div class="p-3 rounded bg-red-50 text-red-700 border border-red-200">{{ session('error') }}</div>
    @endif
    @if (!empty($notice ?? null))
      <div class="p-3 rounded bg-yellow-50 text-yellow-700 border border-yellow-200">{{ $notice }}</div>
    @endif

    {{-- ===== Form grant izin ulang ===== --}}
    <div class="bg-white p-4 rounded shadow">
      <form method="POST" action="{{ route('admin.izinulang.store') }}" class="grid md:grid-cols-5 gap-3">
        @csrf

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Pendaftar</label>
          <select name="user_id" class="mt-1 w-full border rounded p-2">
            @foreach($users as $u)
              <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->username }})</option>
            @endforeach
          </select>
          @error('user_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Paket</label>
          <select name="paket_id" class="mt-1 w-full border rounded p-2">
            @foreach($paket as $p)
              <option value="{{ $p->id }}">{{ $p->nama_paket }} — maks {{ $p->maksimal_percobaan }}</option>
            @endforeach
          </select>
          @error('paket_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Kuota Tambahan</label>
          <input type="number" min="1" name="kuota_tambahan" value="1" class="mt-1 w-full border rounded p-2" required>
          @error('kuota_tambahan') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-5">
          <label class="block text-sm font-medium text-gray-700">Berlaku sampai (opsional)</label>
          <input type="datetime-local" name="berlaku_sampai" class="mt-1 w-full border rounded p-2">
          @error('berlaku_sampai') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-5">
          <label class="block text-sm font-medium text-gray-700">Alasan (opsional)</label>
          <textarea name="alasan" rows="2" class="mt-1 w-full border rounded p-2"></textarea>
          @error('alasan') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-5 flex items-center gap-3 pt-1">
          <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
            Simpan Izin
          </button>
          <span class="text-xs text-gray-500">
            * Kuota tambahan menambah batas dari paket (<em>maksimal_percobaan</em>).
          </span>
        </div>
      </form>
    </div>

    {{-- ===== Tabel daftar izin ===== --}}
    <div class="bg-white p-4 rounded shadow">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="py-2">Pendaftar</th>
            <th>Paket</th>
            <th>Kuota</th>
            <th>Berlaku sampai</th>
            <th>Status</th>
            <th class="w-24"></th>
          </tr>
        </thead>
        <tbody>
          @forelse($izinList as $row)
            <tr class="border-b">
              <td class="py-2">{{ $row->user->name ?? '-' }}</td>
              <td>{{ $row->paket->nama_paket ?? '-' }}</td>
              <td>{{ $row->kuota_tambahan }}</td>
              <td>{{ optional($row->berlaku_sampai)->format('d M Y H:i') ?? '—' }}</td>
              <td>{{ ucfirst($row->status) }}</td>
              <td>
                @if($row->status === 'aktif')
                  <form method="POST" action="{{ route('admin.izinulang.nonaktif', $row) }}">
                    @csrf
                    @method('PATCH')
                    <button class="text-red-600 hover:underline">Nonaktifkan</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-6 text-center text-gray-500">Belum ada data izin.</td>
            </tr>
          @endforelse
        </tbody>
      </table>

      @if(method_exists($izinList, 'links'))
        <div class="mt-3">{{ $izinList->links() }}</div>
      @endif
    </div>

  </div>
</x-app-layout>
