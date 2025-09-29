@props(['type' => 'info'])
@php
    $map = [
        'success' => 'bg-green-50 text-green-700 border-green-200',
        'warning' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
        'error'   => 'bg-red-50 text-red-700 border-red-200',
        'info'    => 'bg-blue-50 text-blue-700 border-blue-200',
    ];
@endphp
<div {{ $attributes->merge(['class' => "mb-4 p-3 rounded border ".$map[$type] ?? $map['info']]) }}>
    {{ $slot }}
</div>
