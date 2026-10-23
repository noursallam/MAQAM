{{--
    Dot-matrix sparkline: one column of dots per value, lit up to the value's share of the largest.
    @include('admin.components.dots', ['values' => [3, 5, 2], 'color' => '#C5A059', 'rows' => 10])
--}}
@php
    $rows = $rows ?? 10;
    $values = array_values($values);
    $max = max(1, ...array_map('floatval', $values ?: [0]));
    $size = $size ?? 4;
@endphp
<div class="ui-dots" style="--dot: {{ $color ?? '#C5A059' }}" aria-hidden="true">
    @foreach($values as $value)
        @php $lit = $value > 0 ? max(1, (int) round($value / $max * $rows)) : 0; @endphp
        <div class="ui-dots-col">
            @for($r = 0; $r < $rows; $r++)
                <i @class(['on' => $r < $lit]) style="width: {{ $size }}px; height: {{ $size }}px"></i>
            @endfor
        </div>
    @endforeach
</div>
