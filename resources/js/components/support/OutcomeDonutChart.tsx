import { Cell, Label, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';

export type OutcomeSummary = {
    success_count: number;
    partial_count: number;
    fail_count: number;
    success_pct: number;
    partial_pct: number;
    fail_pct: number;
};

type Props = { data: OutcomeSummary };

type Segment = {
    key: 'success' | 'partial' | 'fail';
    name: string;
    count: number;
    percentage: number;
    color: string;
};

const COLORS = {
    success: '#10b981',
    partial: '#f59e0b',
    fail: '#ef4444',
} as const;

function CustomTooltip({
    active,
    payload,
}: {
    active?: boolean;
    payload?: { payload: Segment }[];
}) {
    if (!active || !payload?.length) {
        return null;
    }

    const seg = payload[0].payload;

    return (
        <div className="rounded-lg border border-border bg-popover px-3 py-2 text-sm shadow-md">
            <p className="font-medium">{seg.name}</p>
            <p className="text-muted-foreground">
                {seg.count} {seg.count === 1 ? 'tiket' : 'tiketa'} — {seg.percentage.toFixed(1)}%
            </p>
        </div>
    );
}

function PercentageLabel(props: {
    cx?: number;
    cy?: number;
    midAngle?: number;
    innerRadius?: number;
    outerRadius?: number;
    payload?: Segment;
}) {
    const { cx, cy, midAngle, innerRadius, outerRadius, payload } = props;

    if (
        cx === undefined ||
        cy === undefined ||
        midAngle === undefined ||
        innerRadius === undefined ||
        outerRadius === undefined ||
        payload === undefined ||
        payload.percentage < 5
    ) {
        return null;
    }

    const RADIAN = Math.PI / 180;
    const radius = innerRadius + (outerRadius - innerRadius) * 0.5;
    const x = cx + radius * Math.cos(-midAngle * RADIAN);
    const y = cy + radius * Math.sin(-midAngle * RADIAN);

    return (
        <text
            x={x}
            y={y}
            fill="#fff"
            textAnchor="middle"
            dominantBaseline="central"
            fontSize={12}
            fontWeight={600}
        >
            {payload.percentage.toFixed(0)}%
        </text>
    );
}

export default function OutcomeDonutChart({ data }: Props) {
    const total = data.success_count + data.partial_count + data.fail_count;

    if (total === 0) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                Nema zatvorenih tiketa za izabrani period.
            </p>
        );
    }

    const segments: Segment[] = (
        [
            {
                key: 'success' as const,
                name: 'Uspešno',
                count: data.success_count,
                percentage: data.success_pct,
                color: COLORS.success,
            },
            {
                key: 'partial' as const,
                name: 'Delimično uspešno',
                count: data.partial_count,
                percentage: data.partial_pct,
                color: COLORS.partial,
            },
            {
                key: 'fail' as const,
                name: 'Neuspešno',
                count: data.fail_count,
                percentage: data.fail_pct,
                color: COLORS.fail,
            },
        ] satisfies Segment[]
    ).filter((s) => s.count > 0);

    return (
        <>
            <ResponsiveContainer width="100%" height={240}>
                <PieChart aria-label="Donut chart: ishod tiketa (stopa uspešnosti)">
                    <Pie
                        data={segments}
                        dataKey="count"
                        nameKey="name"
                        cx="50%"
                        cy="50%"
                        innerRadius="55%"
                        outerRadius="80%"
                        paddingAngle={2}
                        label={PercentageLabel}
                        labelLine={false}
                    >
                        {segments.map((seg) => (
                            <Cell key={seg.key} fill={seg.color} />
                        ))}
                        <Label
                            position="center"
                            content={({ viewBox }) => {
                                if (!viewBox || !('cx' in viewBox) || !('cy' in viewBox)) {
                                    return null;
                                }

                                const { cx, cy } = viewBox as { cx: number; cy: number };

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
                                            {total}
                                        </text>
                                        <text
                                            x={cx}
                                            y={cy + 14}
                                            textAnchor="middle"
                                            className="fill-muted-foreground"
                                            fontSize={11}
                                        >
                                            zatvorenih
                                        </text>
                                    </g>
                                );
                            }}
                        />
                    </Pie>
                    <Tooltip content={<CustomTooltip />} />
                </PieChart>
            </ResponsiveContainer>

            <ul className="mt-3 flex flex-wrap justify-center gap-x-4 gap-y-1.5">
                {segments.map((seg) => (
                    <li key={seg.key} className="flex items-center gap-1.5 text-xs">
                        <span
                            className="h-2.5 w-2.5 shrink-0 rounded-sm"
                            style={{ backgroundColor: seg.color }}
                        />
                        <span className="text-muted-foreground">{seg.name}</span>
                        <span className="font-medium">
                            {seg.count} ({seg.percentage.toFixed(1)}%)
                        </span>
                    </li>
                ))}
            </ul>

            <table className="sr-only">
                <caption>Ishod tiketa</caption>
                <thead>
                    <tr>
                        <th>Ishod</th>
                        <th>Broj</th>
                        <th>Procenat</th>
                    </tr>
                </thead>
                <tbody>
                    {segments.map((seg) => (
                        <tr key={seg.key}>
                            <td>{seg.name}</td>
                            <td>{seg.count}</td>
                            <td>{seg.percentage.toFixed(1)}%</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </>
    );
}
