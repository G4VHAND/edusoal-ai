<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Audit Log</h1>
                <p class="text-slate-500 text-sm mt-1">
                    Riwayat aktivitas
                    {{ auth()->user()->isSchoolAdmin() ? 'di ' . (auth()->user()->school?->name ?? 'sekolah Anda') : 'di seluruh platform' }}
                </p>
            </div>
        </div>

        {{-- Filter --}}
        <form method="GET" class="bg-white rounded-2xl border border-slate-200 p-4 mb-6 space-y-3">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari deskripsi aktivitas..."
                       class="col-span-2 border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">

                <select name="user_id" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
                    <option value="">Semua User</option>
                    @foreach($userOptions as $option)
                        <option value="{{ $option->id }}" {{ (string) $userId === (string) $option->id ? 'selected' : '' }}>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>

                @if(auth()->user()->isSuperAdmin())
                <select name="school_id" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
                    <option value="">Semua Sekolah</option>
                    @foreach($schoolOptions as $school)
                        <option value="{{ $school->id }}" {{ (string) $schoolId === (string) $school->id ? 'selected' : '' }}>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
                @endif
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <select name="module" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
                    <option value="">Semua Modul</option>
                    @foreach($moduleOptions as $option)
                        <option value="{{ $option }}" {{ $module === $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>

                <select name="event" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
                    <option value="">Semua Aksi</option>
                    @foreach($eventOptions as $option)
                        <option value="{{ $option }}" {{ $event === $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>

                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="border border-slate-300 rounded-xl px-3 py-2 text-sm" title="Dari tanggal">

                <div class="flex gap-2">
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                           class="flex-1 border border-slate-300 rounded-xl px-3 py-2 text-sm" title="Sampai tanggal">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-medium">
                        Filter
                    </button>
                    @if($search || $module || $event || $userId || $schoolId || $dateFrom || $dateTo)
                    <a href="{{ route('admin.audit-log.index') }}"
                       class="border border-slate-300 text-slate-600 px-4 py-2 rounded-xl text-sm font-medium">
                        Reset
                    </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3">User</th>
                        @if(auth()->user()->isSuperAdmin())
                        <th class="px-5 py-3">Sekolah</th>
                        @endif
                        <th class="px-5 py-3">Modul</th>
                        <th class="px-5 py-3">Aksi</th>
                        <th class="px-5 py-3">Deskripsi</th>
                        <th class="px-5 py-3">IP / Perangkat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($auditLogs as $log)
                    <tr class="hover:bg-slate-50 align-top" x-data="{ open: false }">
                        <td class="px-5 py-3 text-slate-500 whitespace-nowrap">
                            {{ $log->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-5 py-3 text-slate-600">
                            {{ $log->user?->name ?? 'Sistem' }}
                        </td>
                        @if(auth()->user()->isSuperAdmin())
                        <td class="px-5 py-3 text-slate-600">
                            {{ $log->school?->name ?? '-' }}
                        </td>
                        @endif
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-700">
                                {{ $log->module }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            @php
                                $eventColor = match(true) {
                                    str_contains($log->event, 'delete') || str_contains($log->event, 'failed') => 'bg-red-100 text-red-700',
                                    str_contains($log->event, 'create') || str_contains($log->event, 'finish') || $log->event === 'login' => 'bg-green-100 text-green-700',
                                    str_contains($log->event, 'update') => 'bg-amber-100 text-amber-700',
                                    $log->event === 'logout' => 'bg-slate-200 text-slate-700',
                                    default => 'bg-blue-100 text-blue-700',
                                };
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs {{ $eventColor }}">
                                {{ $log->event }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-700 max-w-xs">
                            <p>{{ $log->description }}</p>

                            @if(! empty($log->properties))
                            <button type="button" @click="open = !open"
                                    class="text-xs text-blue-600 hover:underline mt-1">
                                <span x-show="!open">Lihat detail</span>
                                <span x-show="open" style="display:none">Sembunyikan</span>
                            </button>

                            <div x-show="open" style="display:none" x-cloak class="mt-2 bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs space-y-2">
                                @if(!empty($log->properties['changes']))
                                    {{-- Diff before/after --}}
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-slate-400 text-left">
                                                <th class="pr-2 pb-1 font-medium">Field</th>
                                                <th class="pr-2 pb-1 font-medium">Sebelum</th>
                                                <th class="pb-1 font-medium">Sesudah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($log->properties['changes'] as $field => $change)
                                            <tr class="border-t border-slate-200">
                                                <td class="pr-2 py-1 font-medium text-slate-600 whitespace-nowrap">{{ $field }}</td>
                                                <td class="pr-2 py-1 text-red-600">{{ $change['before'] === null || $change['before'] === '' ? '-' : $change['before'] }}</td>
                                                <td class="py-1 text-green-600">{{ $change['after'] === null || $change['after'] === '' ? '-' : $change['after'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif

                                {{-- Properties lain di luar 'changes' --}}
                                @php $otherProps = collect($log->properties)->except('changes'); @endphp
                                @if($otherProps->isNotEmpty())
                                <dl class="grid grid-cols-2 gap-x-3 gap-y-1 {{ !empty($log->properties['changes']) ? 'pt-2 border-t border-slate-200' : '' }}">
                                    @foreach($otherProps as $key => $value)
                                    <div class="contents">
                                        <dt class="text-slate-400">{{ $key }}</dt>
                                        <dd class="text-slate-700 break-all">{{ is_array($value) ? json_encode($value) : ($value === null || $value === '' ? '-' : $value) }}</dd>
                                    </div>
                                    @endforeach
                                </dl>
                                @endif
                            </div>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap">
                            {{ $log->ip_address ?? '-' }}<br>
                            {{ trim(($log->browser ?? '-') . ' / ' . ($log->device ?? '-'), ' /') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 7 : 6 }}" class="px-5 py-12 text-center text-slate-400">
                            Belum ada aktivitas yang tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $auditLogs->links() }}</div>
    </div>
</x-app-layout>
