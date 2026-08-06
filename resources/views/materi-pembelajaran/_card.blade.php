@php
    $ext = strtoupper(pathinfo($material->original_filename, PATHINFO_EXTENSION)) ?: 'FILE';
    $sizeLabel = $material->file_size >= 1048576
        ? round($material->file_size / 1048576, 1).' MB'
        : ($material->file_size >= 1024
            ? round($material->file_size / 1024).' KB'
            : $material->file_size.' B');
    $extColors = [
        'PDF' => 'bg-rose-50 text-rose-600',
        'DOC' => 'bg-blue-50 text-blue-600',
        'DOCX' => 'bg-blue-50 text-blue-600',
        'TXT' => 'bg-slate-100 text-slate-500',
    ];
    $extColor = $extColors[$ext] ?? 'bg-slate-100 text-slate-500';
@endphp

<div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-4">
    <div class="flex items-start justify-between mb-3">
        <span class="text-[10px] font-bold px-2 py-1 rounded-md {{ $extColor }}">{{ $ext }}</span>
        <span class="text-[10px] font-semibold px-2 py-1 rounded-full {{ $badge === 'Sekolah' ? 'bg-violet-50 text-violet-600' : 'bg-slate-100 text-slate-500' }}">
            {{ $badge }}
        </span>
    </div>

    <p class="text-sm font-semibold text-slate-900 truncate mb-0.5">{{ $material->title }}</p>
    @if($material->subject)
    <p class="text-xs text-slate-500 mb-1">{{ $material->subject }}</p>
    @endif
    @if($material->description)
    <p class="text-xs text-slate-400 line-clamp-2 mb-2">{{ $material->description }}</p>
    @endif

    <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
        <span class="text-[11px] text-slate-400">
            {{ $sizeLabel }}
            @if($badge === 'Sekolah') · {{ $material->user->name ?? '-' }} @endif
        </span>

        <div class="flex items-center gap-2">
            <a href="{{ route('materi-pembelajaran.download', $material) }}"
               class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                Unduh
            </a>
            @if($material->isOwnedBy(auth()->user()))
            <form method="POST" action="{{ route('materi-pembelajaran.destroy', $material) }}"
                  onsubmit="return confirm('Hapus materi ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs font-semibold">
                    Hapus
                </button>
            </form>
            @endif
        </div>
    </div>
</div>
