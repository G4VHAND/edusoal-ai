<x-app-layout>
    <div class="p-6 max-w-xl mx-auto">
        <h1 class="text-2xl font-bold text-slate-900 mb-6">Tambah Guru</h1>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.teachers.store') }}"
              class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            @csrf

            @if(auth()->user()->isSuperAdmin())
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Sekolah</label>
                <select name="school_id" class="w-full border border-slate-300 rounded-xl p-3">
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Guru</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="w-full border border-slate-300 rounded-xl p-3" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="w-full border border-slate-300 rounded-xl p-3" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password"
                       class="w-full border border-slate-300 rounded-xl p-3" required>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold">
                    Tambah Guru
                </button>
                <a href="{{ route('admin.teachers.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-300 text-slate-700">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
