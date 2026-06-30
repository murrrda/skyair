import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';
import { categoryColor } from './category-colors';
import type { CategoryRow } from './TicketsByTypeBarChart';

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
                {row.count} tiketa — {row.percentage.toFixed(1)}%
            </p>
        </div>
    );
}

export default function TypeDistributionPieChart({ data }: Props) {
    const filled = data.filter((r) => r.count > 0);

    if (filled.length === 0) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                Nema podataka za odabrani period.
            </p>
        );
    }

    return (
        <>
            <ResponsiveContainer width="100%" height={220}>
                <PieChart aria-label="Pie chart: zastupljenost tipova problema">
                    <Pie
                        data={filled}
                        dataKey="count"
                        nameKey="category_name"
                        cx="50%"
                        cy="50%"
                        innerRadius="45%"
                        outerRadius="70%"
                        paddingAngle={2}
                    >
                        {filled.map((row, i) => (
                            <Cell key={row.category_id} fill={categoryColor(i)} />
                        ))}
                    </Pie>
                    <Tooltip content={<CustomTooltip />} />
                </PieChart>
            </ResponsiveContainer>

            {/* Legend */}
            <ul className="mt-2 flex flex-wrap justify-center gap-x-4 gap-y-1.5">
                {filled.map((row, i) => (
                    <li key={row.category_id} className="flex items-center gap-1.5 text-xs">
                        <span
                            className="h-2.5 w-2.5 shrink-0 rounded-sm"
                            style={{ backgroundColor: categoryColor(i) }}
                        />
                        <span className="text-muted-foreground">
                            {row.category_name}
                        </span>
                        <span className="font-medium">{row.percentage.toFixed(1)}%</span>
                    </li>
                ))}
            </ul>

            {/* Visually hidden data table for accessibility */}
            <table className="sr-only">
                <caption>Zastupljenost tipova problema</caption>
                <thead>
                    <tr>
                        <th>Kategorija</th>
                        <th>Broj tiketa</th>
                        <th>Procenat</th>
                    </tr>
                </thead>
                <tbody>
                    {filled.map((row) => (
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
