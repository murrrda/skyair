import { Head } from '@inertiajs/react';

type Training = {
    id: number;
    type: string;
    started_at: string;
    finished_at: string;
    note: string | null;
};

type Props = {
    trainings: Training[];
};

/** 'YYYY-MM-DD' → 'DD.MM.YYYY.' */
function formatDate(date: string): string {
    const [year, month, day] = date.split('-');

    return `${day}.${month}.${year}.`;
}

export default function EmployeeTrainings({ trainings }: Props) {
    return (
        <>
            <Head title="Moje obuke" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Moje obuke
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Pregled obuka koje ste završili
                </p>
            </div>

            <div className="space-y-3">
                {trainings.map((training) => (
                    <div
                        key={training.id}
                        className="rounded-xl border border-border bg-card p-5"
                    >
                        <div className="flex items-start justify-between gap-4">
                            <div className="min-w-0">
                                <h2 className="font-semibold text-foreground">
                                    {training.type}
                                </h2>
                                {training.note && (
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {training.note}
                                    </p>
                                )}
                            </div>

                            <div className="flex shrink-0 flex-col items-end gap-1.5">
                                <span className="text-sm font-semibold text-foreground">
                                    {formatDate(training.started_at)} –{' '}
                                    {formatDate(training.finished_at)}
                                </span>
                                <span className="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                    ✓ Završeno
                                </span>
                            </div>
                        </div>
                    </div>
                ))}

                {trainings.length === 0 && (
                    <p className="py-12 text-center text-muted-foreground">
                        Trenutno nemate evidentiranih obuka.
                    </p>
                )}
            </div>
        </>
    );
}
