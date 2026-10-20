<!doctype html>
<html lang="ru">
<head><meta charset="utf-8"><style>
body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #111; }
h1 { font-size: 18px; } h2 { font-size: 12px; margin-top: 16px; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th, td { border: 1px solid #888; padding: 5px; overflow-wrap: break-word; }
th { background: #eee; } .name { width: 48%; } .number { text-align: right; }
.page { page-break-before: always; } tr { page-break-inside: avoid; }
</style></head>
<body>
@if (count($outlays) === 0)<p>Сметы за выбранный период не найдены.</p>@endif
@foreach ($outlays as $outlay)
<section @if (!$loop->first) class="page" @endif>
<h1>Смета № {{ $outlay->id }}: {{ $outlay->title }}</h1>
<p>{{ $outlay->subdivision?->title }}</p>
<p>Заказ: {{ $outlay->execution_date?->format('d.m.Y H:i') }} {{ $outlay->date_comment }}<br>
Возврат: {{ $outlay->return_date?->format('d.m.Y H:i') }} {{ $outlay->return_date_comment }}<br>
Адрес: {{ $outlay->address }}</p>
@foreach ($outlay->groups as $group)
<h2>{{ $group->groupName?->name }}</h2>
<table><thead><tr><th class="name">Наименование</th><th>Количество</th><th>Цена до скидки</th><th>Сумма со скидкой</th></tr></thead><tbody>
@foreach ($group->items as $item)
<tr><td>{{ $item->product_title_snapshot }}</td><td class="number">{{ $item->quantity }}</td><td class="number">{{ $item->price }}</td><td class="number">{{ $item->total_cost }}</td></tr>
@endforeach
</tbody></table>
@endforeach
<h2>Итого: {{ $outlay->total_cost }} руб.</h2>
<p>Скидка: {{ $outlay->discount_amount }} руб. Предоплата: {{ $outlay->prepayment ? $outlay->prepayment_amount : '0.00' }} руб.</p>
</section>
@endforeach
</body></html>
