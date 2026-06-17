import { Head, Link } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';

// ─── Types ───────────────────────────────────────────────────────────────────

type RiskyEmployee = {
    user_id: number;
    name: string;
    initials: string;
    role: string;
    incident_count: number;
    pause_from: string | null;
    pause_to: string | null;
};

type Props = {
    employees: RiskyEmployee[];
    meta: {
        count: number;
        threshold: number;
        window_days: number;
        last_analysis: string | null;
    };
};

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function RizicniIndex({ employees, meta }: Props) {
    return (
        <>
            <Head title="Rizični zaposleni" />

            {/* Tabs */}
            <div className="mb-6 flex items-center gap-6 border-b border-border">
                <Link
                    href="/admin/incidenti"
                    className="pb-3 text-sm text-muted-foreground hover:text-foreground"
                >
                    Lista incidenata
                </Link>
                <span className="border-b-2 border-[#185FA5] pb-3 text-sm font-semibold text-[#185FA5]">
                    Rizični zaposleni
                </span>
            </div>

            {/* Title */}
            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Rizični zaposleni
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Zaposleni koji su automatski označeni i stavljeni na pauzu
                    nakon analize incidenata
                </p>
            </div>

            {/* Alert banner */}
            {meta.count > 0 && (
                <div className="mb-5 flex items-center justify-between rounded-[10px] border border-[#e7b9aa] bg-[#ff8060] px-5 py-4">
                    <div className="flex items-center gap-3">
                        <div className="flex size-9 items-center justify-center rounded-lg bg-[#6f0e0e]">
                            <AlertTriangle className="size-4 text-white" />
                        </div>
                        <div>
                            <p className="text-sm font-bold text-[#6f0e0e]">
                                {meta.count}{' '}
                                {meta.count === 1
                                    ? 'zaposleni je'
                                    : 'zaposlena su'}{' '}
                                trenutno na pauzi
                            </p>
                            {meta.last_analysis && (
                                <p className="text-xs text-[#7a3a26]">
                                    Sistem je automatski pokrenuo pauzu nakon
                                    analize od {meta.last_analysis}
                                </p>
                            )}
                        </div>
                    </div>
                    <span className="text-xs text-[#7a3a26]">
                        Prag: {meta.threshold} incidenta / {meta.window_days}{' '}
                        dana
                    </span>
                </div>
            )}

            {/* Cards */}
            {employees.length === 0 ? (
                <div className="rounded-lg border border-dashed border-border bg-card px-6 py-16 text-center">
                    <p className="text-sm font-medium">
                        Nema rizičnih zaposlenih.
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Trenutno nijedan zaposleni nije na pauzi nakon analize
                        incidenata.
                    </p>
                </div>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2">
                    {employees.map((e) => (
                        <div
                            key={e.user_id}
                            className="overflow-hidden rounded-[10px] border border-[#e0bcae]"
                        >
                            {/* Header */}
                            <div className="flex items-center justify-between bg-[#ff8060] px-4 py-3.5">
                                <div className="flex items-center gap-3">
                                    <div className="flex size-9 items-center justify-center rounded-full border-2 border-[#6f0e0e] bg-white text-[13px] font-bold text-[#6f0e0e]">
                                        {e.initials}
                                    </div>
                                    <div>
                                        <div className="text-sm font-bold text-[#6f0e0e]">
                                            {e.name}
                                        </div>
                                        <div className="text-[11px] text-[#854f0b]">
                                            {e.role}
                                        </div>
                                    </div>
                                </div>
                                <span className="rounded-full bg-[#ff9077] px-2.5 py-0.5 text-[11px] text-[#6f0e0e]">
                                    Na pauzi
                                </span>
                            </div>

                            {/* Body */}
                            <div className="space-y-0 bg-white px-4 py-3">
                                <Row
                                    label={`Incidenti (${meta.window_days} dana)`}
                                    value={`${e.incident_count} incidenata`}
                                    valueClass="text-[#a32d2d]"
                                    border
                                />
                                <Row
                                    label="Pauza od"
                                    value={e.pause_from ?? '—'}
                                    border
                                />
                                <Row
                                    label="Pauza do"
                                    value={e.pause_to ?? '—'}
                                />
                            </div>

                            {/* Footer */}
                            <div className="flex justify-end border-t border-[#f0d9d0] bg-[#fff8f8] px-4 py-3">
                                <Link
                                    href={`/admin/incidenti/rizicni/${e.user_id}`}
                                    className="rounded-lg border border-[#d3d1c7] bg-white px-3 py-1.5 text-xs text-[#2c2c2a] transition hover:bg-muted"
                                >
                                    Pregled incidenata →
                                </Link>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </>
    );
}

function Row({
    label,
    value,
    valueClass,
    border,
}: {
    label: string;
    value: string;
    valueClass?: string;
    border?: boolean;
}) {
    return (
        <div
            className={`flex items-center justify-between py-2 text-xs ${border ? 'border-b border-[#f1efe8]' : ''}`}
        >
            <span className="text-muted-foreground">{label}</span>
            <span className={`font-bold ${valueClass ?? 'text-foreground'}`}>
                {value}
            </span>
        </div>
    );
}
