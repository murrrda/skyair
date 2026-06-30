import { Plane, TrendingDown, TrendingUp } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ReferenceLine,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

export type FlightOccupancyRow = {
    flight_id: number;
    flight_number: string;
    route_name: string | null;
    date: string;
    capacity: number;
    sold: number;
    occupancy_pct: number;
};

type Props = {
    highest: FlightOccupancyRow[];
    lowest: FlightOccupancyRow[];
    highThreshold: number;
    lowThreshold: number;
};

const COLORS = {
    high: '#10b981',
    low: '#ef4444',
} as const;

type ChartRow = FlightOccupancyRow & { group: 'high' | 'low' };

function formatDate(s: string): string {
    const [y, m, d] = s.split('-');

    return `${d}.${m}.${y}.`;
}

function CustomTooltip({
    active,
    payload,
}: {
    active?: boolean;
    payload?: { payload: ChartRow }[];
}) {
    if (!active || !payload?.length) {
        return null;
    }

    const row = payload[0].payload;

    return (
        <div className="rounded-lg border border-border bg-popover px-3 py-2 text-sm shadow-md">
            <p className="font-medium">
                {row.flight_number}
                {row.route_name ? ` · ${row.route_name}` : ''}
            </p>
            <p className="text-muted-foreground">
                Popunjenost:{' '}
                <span className="font-medium">
                    {row.occupancy_pct.toFixed(1)}%
                </span>
            </p>
            <p className="text-muted-foreground">
                {row.sold} / {row.capacity} sedišta · {formatDate(row.date)}
            </p>
        </div>
    );
}

function RankingTable({
    rows,
    subtitle,
    tone,
}: {
    rows: FlightOccupancyRow[];
    subtitle: string;
    tone: 'high' | 'low';
}) {
    const valueCls = tone === 'high' ? 'text-emerald-600' : 'text-red-600';

    return (
        <div>
            <p className="mb-2 text-xs text-muted-foreground">{subtitle}</p>
            {rows.length === 0 ? (
                <p className="rounded-lg border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground">
                    Nema letova u ovoj kategoriji za izabrani period.
                </p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-border text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <th className="py-2 pr-4 font-medium">Let</th>
                                <th className="py-2 pr-4 font-medium">Ruta</th>
                                <th className="py-2 pr-4 font-medium">Datum</th>
                                <th className="py-2 pr-4 text-right font-medium">
                                    Popunjenost
                                </th>
                                <th className="py-2 text-right font-medium">
                                    Prodato / kapacitet
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row) => (
                                <tr
                                    key={row.flight_id}
                                    className="border-b border-border/60 last:border-0"
                                >
                                    <td className="py-2 pr-4 font-medium">
                                        {row.flight_number}
                                    </td>
                                    <td className="py-2 pr-4 text-muted-foreground">
                                        {row.route_name ?? '—'}
                                    </td>
                                    <td className="py-2 pr-4 text-muted-foreground tabular-nums">
                                        {formatDate(row.date)}
                                    </td>
                                    <td
                                        className={`py-2 pr-4 text-right font-semibold tabular-nums ${valueCls}`}
                                    >
                                        {row.occupancy_pct.toFixed(1)}%
                                    </td>
                                    <td className="py-2 text-right text-muted-foreground tabular-nums">
                                        {row.sold} / {row.capacity}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

export default function OccupancyExtremesSection({
    highest,
    lowest,
    highThreshold,
    lowThreshold,
}: Props) {
    const chartData: ChartRow[] = [
        ...highest.map((f) => ({ ...f, group: 'high' as const })),
        ...lowest.map((f) => ({ ...f, group: 'low' as const })),
    ];

    const needsAngle = chartData.length > 6;

    return (
        <section className="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div className="mb-5 flex items-start gap-3">
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                    <Plane className="h-5 w-5" />
                </span>
                <div>
                    <h2 className="text-base font-semibold tracking-tight">
                        Letovi visoke i niske popunjenosti
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Najpopunjeniji i najmanje popunjeni letovi u periodu
                    </p>
                </div>
            </div>

            {/* Ranking bar chart */}
            {chartData.length === 0 ? (
                <p className="rounded-lg border border-dashed border-border px-4 py-10 text-center text-sm text-muted-foreground">
                    Nema letova sa visokom ili niskom popunjenošću za izabrani
                    period.
                </p>
            ) : (
                <ResponsiveContainer width="100%" height={300}>
                    <BarChart
                        data={chartData}
                        margin={{
                            top: 4,
                            right: 8,
                            left: -10,
                            bottom: needsAngle ? 60 : 8,
                        }}
                        aria-label="Stubičasti grafikon: rangiranje letova po popunjenosti"
                    >
                        <CartesianGrid
                            strokeDasharray="3 3"
                            vertical={false}
                            stroke="hsl(var(--border))"
                        />
                        <XAxis
                            dataKey="flight_number"
                            tick={{ fontSize: 12 }}
                            angle={needsAngle ? -35 : 0}
                            textAnchor={needsAngle ? 'end' : 'middle'}
                            interval={0}
                        />
                        <YAxis
                            tick={{ fontSize: 12 }}
                            width={40}
                            domain={[0, 100]}
                            tickFormatter={(v: number) => `${v}%`}
                        />
                        <Tooltip
                            content={<CustomTooltip />}
                            cursor={{ fill: 'hsl(var(--muted))' }}
                        />
                        <ReferenceLine
                            y={highThreshold}
                            stroke={COLORS.high}
                            strokeDasharray="4 4"
                            label={{
                                value: `Visoka ${highThreshold}%`,
                                position: 'insideTopRight',
                                fontSize: 11,
                                fill: COLORS.high,
                            }}
                        />
                        <ReferenceLine
                            y={lowThreshold}
                            stroke={COLORS.low}
                            strokeDasharray="4 4"
                            label={{
                                value: `Niska ${lowThreshold}%`,
                                position: 'insideBottomRight',
                                fontSize: 11,
                                fill: COLORS.low,
                            }}
                        />
                        <Bar
                            dataKey="occupancy_pct"
                            name="Popunjenost"
                            radius={[3, 3, 0, 0]}
                        >
                            {chartData.map((row) => (
                                <Cell
                                    key={row.flight_id}
                                    fill={
                                        row.group === 'high'
                                            ? COLORS.high
                                            : COLORS.low
                                    }
                                />
                            ))}
                        </Bar>
                    </BarChart>
                </ResponsiveContainer>
            )}

            {/* Ranked tables */}
            <div className="mt-6 grid gap-6 md:grid-cols-2">
                <div>
                    <h3 className="mb-1 flex items-center gap-2 text-sm font-semibold tracking-tight">
                        <TrendingUp className="h-4 w-4 text-emerald-600" />
                        Najpopunjeniji
                    </h3>
                    <RankingTable
                        rows={highest}
                        subtitle={`Popunjenost ≥ ${highThreshold}%`}
                        tone="high"
                    />
                </div>
                <div>
                    <h3 className="mb-1 flex items-center gap-2 text-sm font-semibold tracking-tight">
                        <TrendingDown className="h-4 w-4 text-red-600" />
                        Najmanje popunjeni
                    </h3>
                    <RankingTable
                        rows={lowest}
                        subtitle={`Popunjenost ≤ ${lowThreshold}%`}
                        tone="low"
                    />
                </div>
            </div>
        </section>
    );
}
