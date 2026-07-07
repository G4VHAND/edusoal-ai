@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-slate-700 mb-2']) }}>
    {{ $value ?? $slot }}
</label>
