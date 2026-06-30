import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

export type DailyCount = { date: string; count: number };

type Props = { data: DailyCount[] };

type Granularity = 'daily' | 'weekly' | 'monthly';

type Bucket = {
    key: string;
    label: string;
    tooltipLabel: string;
    count: number;
};

function pickGranularity(days: number): Granularity {
    if (days <= 14) {
        return 'daily';
    }

    if (days <= 90) {
        return 'weekly';
    }

    return 'monthly';
}

function parseDate(s: string): Date {
    const [y, m, d] = s.split('-').map(Number);

    return new Date(y, m - 1, d);
}

function pad(n: number): string {
    return n.toString().padStart(2, '0');
}

function formatDateLabel(d: Date): string {
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.`;
}

function formatDateLong(d: Date): string {
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}.`;
}

function isoWeek(d: Date): { year: number; week: number } {
    const target = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const dayNr = (target.getDay() + 6) % 7;
    target.setDate(target.getDate() - dayNr + 3);
    const firstThursday = new Date(target.getFullYear(), 0, 4);
    const diff = target.getTime() - firstThursday.getTime();
    const week = 1 + Math.round(diff / (7 * 24 * 60 * 60 * 1000));

    return { year: target.getFullYear(), week };
}

function startOfIsoWeek(d: Date): Date {
    const target = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const dayNr = (target.getDay() + 6) % 7;
    target.setDate(target.getDate() - dayNr);

    return target;
}

const MONTHS = [
    'jan', 'feb', 'mar', 'apr', 'maj', 'jun',
    'jul', 'avg', 'sep', 'okt', 'nov', 'dec',
];

function bucketize(data: DailyCount[], granularity: Granularity): Bucket[] {
    if (granularity === 'daily') {
        return data.map((row) => {
            const d = parseDate(row.date);

            return {
                key: row.date,
                label: formatDateLabel(d),
                tooltipLabel: formatDateLong(d),
                count: row.count,
            };
        });
    }

    if (granularity === 'weekly') {
        const map = new Map<string, Bucket>();

        for (const row of data) {
            const d = parseDate(row.date);
            const { year, week } = isoWeek(d);
            const key = `${year}-W${pad(week)}`;
            const weekStart = startOfIsoWeek(d);

            if (!map.has(key)) {
                map.set(key, {
                    key,
                    label: `KW ${week}`,
                    tooltipLabel: `Sedmica ${week} (od ${formatDateLong(weekStart)})`,
                    count: 0,
                });
            }

            map.get(key)!.count += row.count;
        }

        return Array.from(map.values());
    }

    const map = new Map<string, Bucket>();

    for (const row of data) {
        const d = parseDate(row.date);
        const key = `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;

        if (!map.has(key)) {
            map.set(key, {
                key,
                label: `${MONTHS[d.getMonth()]} ${d.getFullYear()}`,
                tooltipLabel: `${MONTHS[d.getMonth()]} ${d.getFullYear()}`,
                count: 0,
            });
        }

        map.get(key)!.count += row.count;
    }

    return Array.from(map.values());
}

function CustomTooltip({
    active,
    payload,
}: {
    active?: boolean;
    payload?: { payload: Bucket }[];
}) {
    if (!active || !payload?.length) {
        return null;
    }

    const b = payload[0].payload;

    return (
        <div className="rounded-lg border border-border bg-popover px-3 py-2 text-sm shadow-md">
            <p className="font-medium">{b.tooltipLabel}</p>
            <p className="text-muted-foreground">
                {b.count} {b.count === 1 ? 'tiket' : 'tiketa'}
            </p>
        </div>
    );
}

export default function TicketTrendAreaChart({ data }: Props) {
    if (data.length === 0 || data.every((r) => r.count === 0)) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                Nema tiketa za izabrani period.
            </p>
        );
    }

    const granularity = pickGranularity(data.length);
    const buckets = bucketize(data, granularity);

    const granularityLabel: Record<Granularity, string> = {
        daily: 'dnevno',
        weekly: 'sedmično',
        monthly: 'mesečno',
    };

    const tickInterval = Math.max(0, Math.floor(buckets.length / 12));

    return (
        <>
            <div className="mb-3 text-xs text-muted-foreground">
                Granularnost: <span className="font-medium">{granularityLabel[granularity]}</span>
            </div>

            <ResponsiveContainer width="100%" height={280}>
                <AreaChart
                    data={buckets}
                    margin={{ top: 4, right: 12, left: -10, bottom: 8 }}
                    aria-label="Area chart: trend prijava tiketa"
                >
                    <defs>
                        <linearGradient id="ticketTrendFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stopColor="#3b82f6" stopOpacity={0.35} />
                            <stop offset="100%" stopColor="#3b82f6" stopOpacity={0.02} />
                        </linearGradient>
                    </defs>
                    <CartesianGrid
                        strokeDasharray="3 3"
                        vertical={false}
                        stroke="hsl(var(--border))"
                    />
                    <XAxis
                        dataKey="label"
                        tick={{ fontSize: 11 }}
                        interval={tickInterval}
                        minTickGap={8}
                    />
                    <YAxis
                        allowDecimals={false}
                        tick={{ fontSize: 12 }}
                        width={36}
                    />
                    <Tooltip
                        content={<CustomTooltip />}
                        cursor={{ stroke: 'hsl(var(--muted-foreground))', strokeDasharray: '3 3' }}
                    />
                    <Area
                        type="monotone"
                        dataKey="count"
                        stroke="#3b82f6"
                        strokeWidth={2}
                        fill="url(#ticketTrendFill)"
                        dot={granularity === 'daily' && buckets.length <= 14}
                    />
                </AreaChart>
            </ResponsiveContainer>

            <table className="sr-only">
                <caption>Trend prijava tiketa</caption>
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Broj tiketa</th>
                    </tr>
                </thead>
                <tbody>
                    {buckets.map((b) => (
                        <tr key={b.key}>
                            <td>{b.tooltipLabel}</td>
                            <td>{b.count}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </>
    );
}
