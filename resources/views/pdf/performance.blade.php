<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 110px 36px 60px 36px; }

        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.4; }

        header {
            position: fixed; top: -90px; left: 0; right: 0; height: 76px;
            border-bottom: 2px solid #3b82f6; padding-bottom: 6px;
        }
        header .brand { font-size: 14px; font-weight: bold; color: #185FA5; }
        header .title { font-size: 13px; font-weight: bold; margin-top: 4px; }
        header .period { position: absolute; top: 0; right: 0; font-size: 10px; color: #6b7280; text-align: right; }

        footer {
            position: fixed; bottom: -40px; left: 0; right: 0; height: 30px;
            font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 6px;
        }

        .kpis { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .kpis td {
            width: 20%; border: 1px solid #e5e7eb; border-radius: 6px;
            padding: 10px; vertical-align: top;
        }
        .kpis .label { font-size: 8px; text-transform: uppercase; color: #6b7280; letter-spacing: .3px; }
        .kpis .value { font-size: 18px; font-weight: bold; margin-top: 3px; }
        .kpis .sub { font-size: 8px; color: #9ca3af; margin-top: 2px; }

        h2 { font-size: 12px; margin: 16px 0 6px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th {
            background: #f3f4f6; text-align: left; font-size: 9px; text-transform: uppercase;
            color: #6b7280; padding: 6px 8px; border-bottom: 1px solid #e5e7eb;
        }
        table.data td { padding: 6px 8px; border-bottom: 1px solid #f0f0f0; }
        .badge { font-size: 9px; font-weight: bold; padding: 2px 6px; border-radius: 4px; }
        .over { color: #b91c1c; }
        .near { color: #b45309; }
        .normal { color: #1f2937; }
    </style>
</head>
<body>
    @php
        $kpis = $report['kpis'];
        $statusLabels = ['over' => 'Prekoračenje', 'near' => 'Blizu limita', 'normal' => 'Normalno'];
    @endphp

    <header>
        <div class="brand">SkyAir</div>
        <div class="title">{{ $title }}</div>
        <div class="period">
            Period: {{ $period['from'] }} – {{ $period['to'] }}<br>
            Generisano: {{ $generated_at }}
        </div>
    </header>

    <footer>SkyAir — Izveštaj o performansama posade</footer>

    <table class="kpis">
        <tr>
            <td><div class="label">Ukupno letova</div><div class="value">{{ number_format($kpis['total_flights'], 0, ',', '.') }}</div><div class="sub">u periodu</div></td>
            <td><div class="label">Ukupno sati leta</div><div class="value">{{ number_format($kpis['total_hours'], 0, ',', '.') }}</div><div class="sub">sati</div></td>
            <td><div class="label">Prosek sati / zap.</div><div class="value">{{ number_format($kpis['avg_hours_per_employee'], 1, ',', '.') }}</div><div class="sub">od {{ $kpis['active_employees'] }} zaposlenih</div></td>
            <td><div class="label">Prekoračenje limita</div><div class="value">{{ $kpis['over_limit'] }}</div><div class="sub">iznad {{ $report['cap'] }}h/ned</div></td>
            <td><div class="label">Prosek uzast. dana</div><div class="value">{{ number_format($kpis['avg_consecutive_days'], 1, ',', '.') }}</div><div class="sub">radnih dana</div></td>
        </tr>
    </table>

    <h2>Detalji po zaposlenom</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Zaposleni</th>
                <th>Pozicija</th>
                <th>Letova</th>
                <th>Sati leta</th>
                <th>Prosek h/letu</th>
                <th>Uzast. dana</th>
                <th>Opterećenje</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['employees'] as $e)
                @php
                    $h = (int) floor($e['avg_hours_per_flight']);
                    $m = (int) round(($e['avg_hours_per_flight'] - $h) * 60);
                @endphp
                <tr>
                    <td>{{ $e['name'] }}</td>
                    <td>{{ $e['position'] }}</td>
                    <td>{{ $e['flights'] }}</td>
                    <td class="{{ $e['status'] }}"><strong>{{ $e['hours'] }}h</strong></td>
                    <td>{{ $h }}h {{ $m }}m</td>
                    <td>{{ $e['max_consecutive_days'] }} dana</td>
                    <td>{{ $e['peak_week_hours'] }} / {{ $report['cap'] }}h</td>
                    <td class="badge {{ $e['status'] }}">{{ $statusLabels[$e['status']] ?? $e['status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:16px;">Nema podataka za izabrani period.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
