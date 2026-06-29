export type TopFlightRow = {
    flight_id: number;
    flight_number: string;
    route_name: string | null;
    total: number;
    success: number;
    partial: number;
    fail: number;
};

type Props = { data: TopFlightRow[] };

const OUTCOME_COLORS = {
    success: '#10b981',
    partial: '#f59e0b',
    fail: '#ef4444',
} as const;

function OutcomeCell({
    count,
    total,
    color,
}: {
    count: number;
    total: number;
    color: string;
}) {
    const pct = total > 0 ? Math.round((count / total) * 100) : 0;

    return (
        <div className="flex flex-col gap-1">
            <div className="text-sm font-medium text-foreground">
                {count}{' '}
                <span className="text-xs text-muted-foreground">({pct}%)</span>
            </div>
            <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                <div
                    className="h-full rounded-full transition-all"
                    style={{ width: `${pct}%`, backgroundColor: color }}
                />
            </div>
        </div>
    );
}

export default function TopFlightsTable({ data }: Props) {
    if (data.length === 0) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                Nema letova sa prijavama u izabranom periodu.
            </p>
        );
    }

    const sorted = [...data].sort((a, b) => b.total - a.total).slice(0, 3);

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-b border-border text-left text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        <th className="py-2 pr-4">Let</th>
                        <th className="py-2 pr-4 text-right">Ukupno</th>
                        <th className="py-2 pr-4">Uspešno</th>
                        <th className="py-2 pr-4">Delimično</th>
                        <th className="py-2">Neuspešno</th>
                    </tr>
                </thead>
                <tbody>
                    {sorted.map((row) => (
                        <tr key={row.flight_id} className="border-b border-border/60 last:border-0">
                            <td className="py-3 pr-4">
                                <div className="font-medium text-foreground">{row.flight_number}</div>
                                {row.route_name && (
                                    <div className="text-xs text-muted-foreground">
                                        {row.route_name}
                                    </div>
                                )}
                            </td>
                            <td className="py-3 pr-4 text-right align-top">
                                <span className="inline-flex h-7 min-w-[2rem] items-center justify-center rounded-md bg-primary/10 px-2 text-sm font-semibold text-primary">
                                    {row.total}
                                </span>
                            </td>
                            <td className="py-3 pr-4 align-top">
                                <OutcomeCell
                                    count={row.success}
                                    total={row.total}
                                    color={OUTCOME_COLORS.success}
                                />
                            </td>
                            <td className="py-3 pr-4 align-top">
                                <OutcomeCell
                                    count={row.partial}
                                    total={row.total}
                                    color={OUTCOME_COLORS.partial}
                                />
                            </td>
                            <td className="py-3 align-top">
                                <OutcomeCell
                                    count={row.fail}
                                    total={row.total}
                                    color={OUTCOME_COLORS.fail}
                                />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
