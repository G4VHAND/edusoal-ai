<x-app-layout>
    <div class="p-6 max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Provider AI</h1>
        <p class="text-slate-500 mb-6">
            Pilih provider AI yang dipakai untuk generate soal di sekolah Anda. Semua guru di
            sekolah ini akan otomatis memakai provider yang sama — guru tidak perlu (dan tidak
            bisa) memilih provider sendiri.
        </p>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.ai-provider.update') }}"
              class="bg-white rounded-2xl border border-slate-200 p-6">
            @csrf

            <div class="space-y-3">
                @foreach($providers as $key => $info)
                    @php
                        $selected = old('ai_provider', $school->ai_provider ?? config('ai.default')) === $key;
                    @endphp
                    <label class="flex items-start gap-3 border rounded-xl p-4 cursor-pointer transition
                        {{ $selected ? 'border-blue-500 bg-blue-50' : 'border-slate-200 hover:bg-slate-50' }}">
                        <input type="radio" name="ai_provider" value="{{ $key }}"
                               class="mt-1 w-4 h-4 text-blue-600"
                               {{ $selected ? 'checked' : '' }}>
                        <div>
                            <p class="font-semibold text-slate-900">{{ $info['label'] }}</p>
                            @if($info['description'])
                                <p class="text-sm text-slate-500 mt-0.5">{{ $info['description'] }}</p>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>

            @if(is_null($school->ai_provider))
                <p class="text-xs text-slate-400 mt-3">
                    Sekolah Anda belum pernah mengatur provider — saat ini otomatis memakai
                    <strong>{{ $providers[config('ai.default')]['label'] ?? config('ai.default') }}</strong>
                    (default sistem).
                </p>
            @endif

            <div class="pt-5">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700">
                    Simpan Provider
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
