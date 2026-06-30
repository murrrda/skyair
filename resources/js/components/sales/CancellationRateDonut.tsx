import {
    Cell,
    Label,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
} from 'recharts';

export type CancellationSummary = {
    total_reservations: number;
    cancelled_reservations: number;
    rate_pct: number;
};

type Props = { data: CancellationSummary };

type Segment = {
    key: 'cancelled' | 'active';
    name: string;
    count: number;
    color: string;
};

const COLORS = {
    cancelled: '#ef4444',
    active: '#10b981',
} as const;

function CustomTooltip({
    active,
    payload,
    total,
}: {
    active?: boolean;
    payload?: { payload: Segment }[];
    total: number;
}) {
    if (!active || !payload?.length) {
        return null;
    }

    const seg = payload[0].payload;
    const pct = total > 0 ? (seg.count / total) * 100 : 0;

    return (
        <div className="rounded-lg border border-border bg-popover px-3 py-2 text-sm shadow-md">
            <p className="font-medium">{seg.name}</p>
            <p className="text-muted-foreground">
                {seg.count.toLocaleString('sr-RS')}{' '}
                {seg.count === 1 ? 'rezervacija' : 'rezervacija'} —{' '}
                {pct.toFixed(1)}%
            </p>
        </div>
    );
}

export default function CancellationRateDonut({ data }: Props) {
    const total = data.total_reservations;

    if (total === 0) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                Nema rezervacija za izabrani period.
            </p>
        );
    }

    const cancelled = data.cancelled_reservations;
    const active = Math.max(0, total - cancelled);

    const segments: Segment[] = (
        [
            {
                key: 'cancelled' as const,
                name: 'Otkazane',
                count: cancelled,
                color: COLORS.cancelled,
            },
            {
                key: 'active' as const,
                name: 'Aktivne',
                count: active,
                color: COLORS.active,
            },
        ] satisfies Segment[]
    ).filter((s) => s.count > 0);

    return (
        <>
            <ResponsiveContainer width="100%" height={240}>
                <PieChart aria-label="Donut grafikon: stopa otkazivanja rezervacija">
                    <Pie
                        data={segments}
                        dataKey="count"
                        nameKey="name"
                        cx="50%"
                        cy="50%"
                        innerRadius="55%"
                        outerRadius="80%"
                        paddingAngle={2}
                    >
                        {segments.map((seg) => (
                            <Cell key={seg.key} fill={seg.color} />
                        ))}
                        <Label
                            position="center"
                            content={({ viewBox }) => {
                                if (
                                    !viewBox ||
                                    !('cx' in viewBox) ||
                                    !('cy' in viewBox)
                                ) {
                                    return null;
                                }

                                const { cx, cy } = viewBox as {
                                    cx: number;
                                    cy: number;
                                };

                                return (
                                    <g>
                                        <text
                                            x={cx}
                                            y={cy - 6}
                                            textAnchor="middle"
                                            className="fill-foreground"
                                            fontSize={22}
                                            fontWeight={700}
                                        >
                                            {data.rate_pct.toFixed(1)}%
                                        </text>
                                        <text
                                            x={cx}
                                            y={cy + 14}
                                            textAnchor="middle"
                                            className="fill-muted-foreground"
                                            fontSize={11}
                                        >
                                            otkazano
                                        </text>
                                    </g>
                                );
                            }}
                        />
                    </Pie>
                    <Tooltip content={<CustomTooltip total={total} />} />
                </PieChart>
            </ResponsiveContainer>

            <ul className="mt-3 flex flex-wrap justify-center gap-x-4 gap-y-1.5">
                {segments.map((seg) => (
                    <li
                        key={seg.key}
                        className="flex items-center gap-1.5 text-xs"
                    >
                        <span
                            className="h-2.5 w-2.5 shrink-0 rounded-sm"
                            style={{ backgroundColor: seg.color }}
                        />
                        <span className="text-muted-foreground">
                            {seg.name}
                        </span>
                        <span className="font-medium">
                            {seg.count.toLocaleString('sr-RS')}
                        </span>
                    </li>
                ))}
            </ul>

            <table className="sr-only">
                <caption>Stopa otkazivanja</caption>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Broj rezervacija</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Otkazane</td>
                        <td>{cancelled}</td>
                    </tr>
                    <tr>
                        <td>Aktivne</td>
                        <td>{active}</td>
                    </tr>
                </tbody>
            </table>
        </>
    );
}
