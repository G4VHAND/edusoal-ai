<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Bank Soal Sekolah</h1>
                <p class="text-slate-500 text-sm mt-1">
                    Semua bank soal yang dibuat guru di
                    {{ auth()->user()->isSchoolAdmin() ? auth()->user()->school?->name : 'seluruh platform' }}
                </p>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <p class="text-sm text-slate-500">Total Bank Soal</p>
                <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <p class="text-sm text-slate-500">Total Guru</p>
                <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['teachers'] }}</p>
            </div>
        </div>

        {{-- Filter --}}
        <form method="GET" class="bg-white rounded-2xl border border-slate-200 p-4 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari judul, mapel, topik..."
                       class="col-span-2 border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">

                <select name="teacher_id" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
                    <option value="">Semua Guru</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" {{ $teacherId == $teacher->id ? 'selected' : '' }}>
                            {{ $teacher->name }}
                        </option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <select name="difficulty" class="flex-1 border border-slate-300 rounded-xl px-3 py-2 text-sm">
                        <option value="">Semua</option>
                        <option value="mudah" {{ $difficulty == 'mudah' ? 'selected' : '' }}>Mudah</option>
                        <option value="sedang" {{ $difficulty == 'sedang' ? 'selected' : '' }}>Sedang</option>
                        <option value="sulit" {{ $difficulty == 'sulit' ? 'selected' : '' }}>Sulit</option>
                    </select>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-medium">
                        Filter
                    </button>
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-5 py-3">Judul</th>
                        <th class="px-5 py-3">Guru</th>
                        <th class="px-5 py-3">Mapel</th>
                        <th class="px-5 py-3">Kelas</th>
                        <th class="px-5 py-3">Jenis</th>
                        <th class="px-5 py-3">Kesulitan</th>
                        <th class="px-5 py-3">Dibuat</th>
                        <th class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($questionSets as $qs)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $qs->title }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $qs->user?->name ?? '-' }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $qs->subject }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $qs->grade }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ $qs->question_type === 'multiple_choice' ? 'bg-blue-100 text-blue-700' : 'bg-violet-100 text-violet-700' }}">
                                {{ $qs->question_type === 'multiple_choice' ? 'Pilihan Ganda' : 'Essay' }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ $qs->difficulty === 'mudah' ? 'bg-green-100 text-green-700' : ($qs->difficulty === 'sedang' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                {{ ucfirst($qs->difficulty) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $qs->created_at->format('d M Y') }}</td>
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.bank-soal.show', $qs->id) }}"
                               class="text-blue-600 hover:underline text-xs font-medium">
                                Lihat
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                            Belum ada bank soal.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $questionSets->links() }}</div>
    </div>
</x-app-layout>