import {
    Bar,
    BarChart,
    CartesianGrid,
    Legend,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

export type ResolutionRow = {
    category_id: number;
    category_name: string;
    avg_minutes: number;
    min_minutes: number;
    max_minutes: number;
};

type Props = { data: ResolutionRow[] };

const COLORS = {
    min: '#10b981',
    avg: '#3b82f6',
    max: '#ef4444',
} as const;

function formatHours(minutes: number): string {
    if (minutes <= 0) {
        return '0h';
    }

    const h = Math.floor(minutes / 60);
    const m = Math.round(minutes % 60);

    if (h === 0) {
        return `${m}m`;
    }

    if (m === 0) {
        return `${h}h`;
    }

    return `${h}h ${m}m`;
}

type ChartRow = ResolutionRow & {
    min_hours: number;
    avg_hours: number;
    max_hours: number;
};

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
            <p className="mb-1.5 font-medium">{row.category_name}</p>
            <div className="space-y-1">
                <div className="flex items-center justify-between gap-4">
                    <span className="flex items-center gap-1.5 text-muted-foreground">
                        <span
                            className="h-2 w-2 rounded-sm"
                            style={{ backgroundColor: COLORS.min }}
                        />
                        Min:
                    </span>
                    <span className="font-medium">
                        {formatHours(row.min_minutes)}{' '}
                        <span className="text-xs text-muted-foreground">
                            ({Math.round(row.min_minutes)}m)
                        </span>
                    </span>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <span className="flex items-center gap-1.5 text-muted-foreground">
                        <span
                            className="h-2 w-2 rounded-sm"
                            style={{ backgroundColor: COLORS.avg }}
                        />
                        Prosek:
                    </span>
                    <span className="font-medium">
                        {formatHours(row.avg_minutes)}{' '}
                        <span className="text-xs text-muted-foreground">
                            ({Math.round(row.avg_minutes)}m)
                        </span>
                    </span>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <span className="flex items-center gap-1.5 text-muted-foreground">
                        <span
                            className="h-2 w-2 rounded-sm"
                            style={{ backgroundColor: COLORS.max }}
                        />
                        Max:
                    </span>
                    <span className="font-medium">
                        {formatHours(row.max_minutes)}{' '}
                        <span className="text-xs text-muted-foreground">
                            ({Math.round(row.max_minutes)}m)
                        </span>
                    </span>
                </div>
            </div>
        </div>
    );
}

export default function ResolutionTimeGroupedBarChart({ data }: Props) {
    if (data.length === 0) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                Nema rešenih tiketa za odabrani period.
            </p>
        );
    }

    const chartData: ChartRow[] = data.map((row) => ({
        ...row,
        min_hours: +(row.min_minutes / 60).toFixed(2),
        avg_hours: +(row.avg_minutes / 60).toFixed(2),
        max_hours: +(row.max_minutes / 60).toFixed(2),
    }));

    const angleThreshold = 4;
    const needsAngle = chartData.length > angleThreshold;

    return (
        <>
            <ResponsiveContainer width="100%" height={300}>
                <BarChart
                    data={chartData}
                    margin={{
                        top: 4,
                        right: 8,
                        left: -10,
                        bottom: needsAngle ? 60 : 8,
                    }}
                    aria-label="Grouped bar chart: vreme rešavanja po tipu problema"
                >
                    <CartesianGrid
                        strokeDasharray="3 3"
                        vertical={false}
                        stroke="hsl(var(--border))"
                    />
                    <XAxis
                        dataKey="category_name"
                        tick={{ fontSize: 12 }}
                        angle={needsAngle ? -35 : 0}
                        textAnchor={needsAngle ? 'end' : 'middle'}
                        interval={0}
                    />
                    <YAxis
                        tick={{ fontSize: 12 }}
                        width={40}
                        tickFormatter={(v: number) => `${v}h`}
                    />
                    <Tooltip
                        content={<CustomTooltip />}
                        cursor={{ fill: 'hsl(var(--muted))' }}
                    />
                    <Legend
                        wrapperStyle={{ fontSize: 12, paddingTop: 8 }}
                        iconType="square"
                    />
                    <Bar
                        dataKey="min_hours"
                        name="Min"
                        fill={COLORS.min}
                        radius={[3, 3, 0, 0]}
                    />
                    <Bar
                        dataKey="avg_hours"
                        name="Prosek"
                        fill={COLORS.avg}
                        radius={[3, 3, 0, 0]}
                    />
                    <Bar
                        dataKey="max_hours"
                        name="Max"
                        fill={COLORS.max}
                        radius={[3, 3, 0, 0]}
                    />
                </BarChart>
            </ResponsiveContainer>

            <table className="sr-only">
                <caption>Vreme rešavanja po tipu problema</caption>
                <thead>
                    <tr>
                        <th>Kategorija</th>
                        <th>Min</th>
                        <th>Prosek</th>
                        <th>Max</th>
                    </tr>
                </thead>
                <tbody>
                    {data.map((row) => (
                        <tr key={row.category_id}>
                            <td>{row.category_name}</td>
                            <td>{formatHours(row.min_minutes)}</td>
                            <td>{formatHours(row.avg_minutes)}</td>
                            <td>{formatHours(row.max_minutes)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </>
    );
}
