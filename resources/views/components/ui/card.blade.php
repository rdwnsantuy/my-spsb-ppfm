@props(['as' => 'div'])
<{{ $as }} {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow p-6']) }}>
    {{ $slot }}
</{{ $as }}>
