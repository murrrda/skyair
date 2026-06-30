import {
    Bar,
    BarChart,
    Cell,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { categoryColor } from './category-colors';

export type CategoryRow = {
    category_id: number;
    category_name: string;
    count: number;
    percentage: number;
};

type Props = { data: CategoryRow[] };

function CustomTooltip({ active, payload }: { active?: boolean; payload?: { payload: CategoryRow }[] }) {
    if (!active || !payload?.length) {
return null;
}

    const row = payload[0].payload;

    return (
        <div className="rounded-lg border border-border bg-popover px-3 py-2 text-sm shadow-md">
            <p className="font-medium">{row.category_name}</p>
            <p className="text-muted-foreground">
                {row.count} tiketa ({row.percentage.toFixed(1)}%)
            </p>
        </div>
    );
}

export default function TicketsByTypeBarChart({ data }: Props) {
    if (data.length === 0 || data.every((r) => r.count === 0)) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                Nema podataka za odabrani period.
            </p>
        );
    }

    const sorted = [...data].sort((a, b) => b.count - a.count);
    const angleThreshold = 4;
    const needsAngle = sorted.length > angleThreshold;

    return (
        <>
            <ResponsiveContainer width="100%" height={280}>
                <BarChart
                    data={sorted}
                    margin={{ top: 4, right: 8, left: -10, bottom: needsAngle ? 60 : 8 }}
                    aria-label="Bar chart: broj prijava po tipu problema"
                >
                    <XAxis
                        dataKey="category_name"
                        tick={{ fontSize: 12 }}
                        angle={needsAngle ? -35 : 0}
                        textAnchor={needsAngle ? 'end' : 'middle'}
                        interval={0}
                    />
                    <YAxis
                        allowDecimals={false}
                        tick={{ fontSize: 12 }}
                        width={36}
                    />
                    <Tooltip content={<CustomTooltip />} cursor={{ fill: 'hsl(var(--muted))' }} />
                    <Bar dataKey="count" radius={[4, 4, 0, 0]}>
                        {sorted.map((row, i) => (
                            <Cell key={row.category_id} fill={categoryColor(i)} />
                        ))}
                    </Bar>
                </BarChart>
            </ResponsiveContainer>

            {/* Visually hidden data table for accessibility */}
            <table className="sr-only">
                <caption>Broj prijava po tipu problema</caption>
                <thead>
                    <tr>
                        <th>Kategorija</th>
                        <th>Broj tiketa</th>
                        <th>Procenat</th>
                    </tr>
                </thead>
                <tbody>
                    {sorted.map((row) => (
                        <tr key={row.category_id}>
                            <td>{row.category_name}</td>
                            <td>{row.count}</td>
                            <td>{row.percentage.toFixed(1)}%</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </>
    );
}
