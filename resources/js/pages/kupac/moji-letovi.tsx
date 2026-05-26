import { Head, Link, router } from '@inertiajs/react';
import KupacHeader from '@/components/kupac-header';
import { Button } from '@/components/ui/button';
import { useState } from 'react';

interface Reservation {
    id: number;
    route_label: string;
    info: string;
    status: string;
    status_note: string | null;
    total_price: number;
    is_past: boolean;
    can_pay: boolean;
    can_cancel: boolean;
}

interface Props {
    reservations: Reservation[];
}

const STATUS_STYLES: Record<string, { bg: string; text: string }> = {
    kreirana:    { bg: 'bg-[#FAEEDA]', text: 'text-[#854F0B]' },
    placena:     { bg: 'bg-[#E6F1FB]', text: 'text-[#185FA5]' },
    otkazana:    { bg: 'bg-[#FCEBEB]', text: 'text-[#A32D2D]' },
    iskoriscena: { bg: 'bg-muted',     text: 'text-muted-foreground' },
};

const FILTER_MAP: Record<string, string | null> = {
    'Sve': null,
    'Aktivne': 'kreirana',
    'Plaćene': 'placena',
    'Otkazane': 'otkazana',
    'Iskorišćene': 'iskoriscena',
};

function fmt(n: number) {
    return n.toLocaleString('sr-RS') + ' RSD';
}

export default function MojiLetovi({ reservations = [] }: Props) {
    const [activeFilter, setActiveFilter] = useState<string | null>(null);

    const filtered = activeFilter
        ? reservations.filter(r => r.status === activeFilter)
        : reservations;

    return (
        <>
            <Head title="Moji letovi" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <KupacHeader active="moji-letovi" />

                <div className="bg-muted p-6">
                    <div className="mx-auto max-w-5xl">
                        <h2 className="mb-4 text-lg font-semibold">Moje rezervacije</h2>

                        <div className="mb-4 flex gap-2">
                            {Object.entries(FILTER_MAP).map(([label, value]) => (
                                <button
                                    key={label}
                                    onClick={() => setActiveFilter(value)}
                                    className={`rounded-full border px-3.5 py-1.5 text-xs font-medium transition ${
                                        activeFilter === value
                                            ? 'border-[#185FA5] bg-[#185FA5] text-white'
                                            : 'border-border bg-card text-foreground hover:bg-muted'
                                    }`}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>

                        {filtered.length === 0 && (
                            <div className="py-16 text-center text-muted-foreground">
                                <p className="text-lg font-medium">Nema rezervacija</p>
                                <p className="mt-1 text-sm">Pretraži letove i napravi svoju prvu rezervaciju</p>
                                <Button className="mt-4 bg-[#185FA5] hover:bg-[#0C447C]" asChild>
                                    <Link href="/kupac/pretraga-letova">Pretraži letove</Link>
                                </Button>
                            </div>
                        )}

                        <div className="space-y-2.5">
                            {filtered.map((r) => {
                                const style = STATUS_STYLES[r.status] ?? STATUS_STYLES.kreirana;
                                const isPastOrCancelled = r.is_past || r.status === 'otkazana';
                                return (
                                    <div
                                        key={r.id}
                                        className={`grid grid-cols-[1fr_auto] items-center gap-4 rounded-xl border border-border bg-card p-4 ${isPastOrCancelled ? 'opacity-70' : ''}`}
                                    >
                                        <div>
                                            <div className="text-[15px] font-semibold">{r.route_label}</div>
                                            <div className="mt-1 text-xs text-muted-foreground">{r.info}</div>
                                            <div className="mt-2 flex items-center gap-2">
                                                <span className={`rounded-full px-2.5 py-0.5 text-[11px] font-medium ${style.bg} ${style.text}`}>
                                                    {r.status.charAt(0).toUpperCase() + r.status.slice(1)}
                                                </span>
                                                {r.status_note && (
                                                    <span className="text-xs text-muted-foreground">{r.status_note}</span>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex flex-col items-end justify-between gap-3">
                                            <div className={`text-lg font-semibold ${r.status === 'otkazana' ? 'text-muted-foreground line-through' : ''}`}>
                                                {fmt(r.total_price)}
                                            </div>
                                            <div className="flex gap-2">
                                                {r.can_cancel && (
                                                    <Button size="sm" variant="destructive" className="text-xs" asChild>
                                                        <Link href={`/kupac/otkazivanje-rezervacije/${r.id}`}>Otkaži</Link>
                                                    </Button>
                                                )}
                                                {r.can_pay && (
                                                    <Button size="sm" className="bg-[#185FA5] text-xs hover:bg-[#0C447C]" onClick={() => router.post(`/kupac/rezervacija/${r.id}/plati`)}>
                                                        Plati odmah →
                                                    </Button>
                                                )}
                                                <Button variant="outline" size="sm" className="text-xs" asChild>
                                                    <Link href={`/kupac/detalji-rezervacije/${r.id}`}>Detalji{!isPastOrCancelled ? ' →' : ''}</Link>
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
