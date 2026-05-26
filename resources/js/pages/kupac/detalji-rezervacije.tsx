import { Head, Link } from '@inertiajs/react';
import KupacHeader from '@/components/kupac-header';
import { Button } from '@/components/ui/button';

interface TimelineStep {
    title: string;
    date: string;
    state: 'done' | 'active' | 'pending';
}

interface FlightInfo {
    dep_time: string;
    arr_time: string;
    dep_code: string;
    dep_city: string;
    arr_code: string;
    arr_city: string;
    duration: string;
    type: string;
    plane_model: string;
}

interface Reservation {
    id: number;
    code: string;
    status: string;
    date_formatted: string;
    class_name: string;
    seat_number: string | null;
    passenger_name: string;
    passport_number: string | null;
    paid_at: string | null;
    can_cancel: boolean;
    cancel_deadline: string | null;
    flight: FlightInfo;
    price_breakdown: {
        base_price: number;
        class_factor_label: string;
        class_factor_amount: number;
        season_amount: number;
        occupancy_amount: number;
        reward_discount: number;
        total: number;
        status_points: number;
        reward_points: number;
    };
    timeline: TimelineStep[];
}

interface Props {
    reservation: Reservation;
}

const STATUS_BADGE: Record<string, string> = {
    kreirana: 'bg-[#FAEEDA] text-[#854F0B]',
    placena: 'bg-[#E6F1FB] text-[#185FA5]',
    otkazana: 'bg-[#FCEBEB] text-[#A32D2D]',
    iskoriscena: 'bg-muted text-muted-foreground',
};

const DOT_CLASS: Record<string, string> = {
    done: 'bg-[#0F6E56]',
    active: 'bg-[#185FA5] ring-[3px] ring-[#E6F1FB]',
    pending: 'bg-[#D3D1C7]',
};

function fmt(n: number) { return n.toLocaleString('sr-RS') + ' RSD'; }

export default function DetaljiRezervacije({ reservation }: Props) {
    if (!reservation) {
        return (
            <>
                <Head title="Rezervacija nije pronađena" />
                <div className="flex min-h-screen flex-col bg-background text-foreground">
                    <KupacHeader active="moji-letovi" />
                    <div className="py-20 text-center text-muted-foreground">
                        <p className="text-lg font-medium">Rezervacija nije pronađena</p>
                        <Button variant="outline" className="mt-4" asChild>
                            <Link href="/kupac/moji-letovi">Nazad na Moji letovi</Link>
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    const r = reservation;
    const f = r.flight;
    const p = r.price_breakdown;
    const badge = STATUS_BADGE[r.status] ?? STATUS_BADGE.kreirana;
    const statusLabel = r.status.charAt(0).toUpperCase() + r.status.slice(1);

    return (
        <>
            <Head title={`Rezervacija ${r.code}`} />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <KupacHeader active="moji-letovi" />

                <div className="border-b border-border/60 bg-background px-6 py-3 text-xs text-muted-foreground">
                    <Link href="/kupac/moji-letovi" className="hover:text-foreground">← Nazad na Moji letovi</Link>
                </div>

                <div className="grid grid-cols-[1fr_300px] gap-4 bg-muted p-5">
                    <div className="space-y-3">
                        {/* Flight details */}
                        <div className="rounded-xl border border-border bg-card p-5">
                            <div className="mb-3.5 flex items-center justify-between">
                                <h3 className="text-sm font-semibold">Rezervacija {r.code}</h3>
                                <span className={`rounded-full px-2.5 py-0.5 text-[11px] font-medium ${badge}`}>{statusLabel}</span>
                            </div>

                            <div className="flex items-center gap-5 rounded-lg bg-muted/80 p-3.5">
                                <div>
                                    <div className="text-[22px] font-semibold">{f.dep_time}</div>
                                    <div className="text-xs text-muted-foreground">{f.dep_code} &middot; {f.dep_city}</div>
                                </div>
                                <div className="flex-1 text-center">
                                    <div className="text-[11px] text-muted-foreground">{f.duration} &middot; {f.type}</div>
                                    <div className="relative my-1 h-px bg-border">
                                        <div className="absolute -right-px -top-[3px] border-b-[3px] border-l-[5px] border-t-[3px] border-b-transparent border-l-muted-foreground border-t-transparent" />
                                    </div>
                                    <div className="text-[11px] text-muted-foreground">{f.plane_model}</div>
                                </div>
                                <div className="text-right">
                                    <div className="text-[22px] font-semibold">{f.arr_time}</div>
                                    <div className="text-xs text-muted-foreground">{f.arr_code} &middot; {f.arr_city}</div>
                                </div>
                            </div>

                            <hr className="my-4 border-border" />

                            <div className="grid grid-cols-3 gap-3 text-[13px]">
                                <div><div className="mb-0.5 text-[11px] text-muted-foreground">Datum leta</div><div className="font-medium">{r.date_formatted}</div></div>
                                <div><div className="mb-0.5 text-[11px] text-muted-foreground">Klasa</div><div className="font-medium">{r.class_name}</div></div>
                                <div><div className="mb-0.5 text-[11px] text-muted-foreground">Sedište</div><div className="font-medium">{r.seat_number ?? '—'}</div></div>
                                <div><div className="mb-0.5 text-[11px] text-muted-foreground">Putnik</div><div className="font-medium">{r.passenger_name}</div></div>
                                {r.passport_number && <div><div className="mb-0.5 text-[11px] text-muted-foreground">Pasoš</div><div className="font-medium">{r.passport_number}</div></div>}
                                {r.paid_at && <div><div className="mb-0.5 text-[11px] text-muted-foreground">Plaćeno</div><div className="font-medium">{r.paid_at}</div></div>}
                            </div>
                        </div>

                        {/* Timeline */}
                        {r.timeline.length > 0 && (
                            <div className="rounded-xl border border-border bg-card p-5">
                                <h3 className="mb-3.5 text-sm font-semibold">Status rezervacije</h3>
                                <div className="flex flex-col">
                                    {r.timeline.map((step, i) => (
                                        <div key={step.title} className="relative flex gap-3.5 pb-4 last:pb-0">
                                            {i < r.timeline.length - 1 && (
                                                <div className="absolute bottom-0 left-[5px] top-3.5 w-px bg-border" />
                                            )}
                                            <div className={`relative z-10 mt-0.5 h-3 w-3 flex-shrink-0 rounded-full ${DOT_CLASS[step.state]}`} />
                                            <div>
                                                <div className={`text-[13px] font-semibold ${step.state === 'pending' ? 'text-muted-foreground' : ''}`}>
                                                    {step.title}
                                                </div>
                                                <div className="text-[11px] text-muted-foreground">{step.date}</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Cancellation */}
                        {r.can_cancel && (
                            <div className="rounded-xl border border-[#F0C5C5] bg-card p-5">
                                <h3 className="mb-3 text-sm font-semibold">Otkazivanje rezervacije</h3>
                                {r.cancel_deadline && (
                                    <p className="mb-3 text-[13px] leading-relaxed text-muted-foreground">
                                        Možeš otkazati rezervaciju <strong>do {r.cancel_deadline}</strong> (3 dana pre leta). Nakon tog roka otkazivanje nije moguće.
                                    </p>
                                )}
                                <Button variant="destructive" size="sm" className="text-[13px]" asChild>
                                    <Link href={`/kupac/otkazivanje-rezervacije/${r.id}`}>Otkaži rezervaciju</Link>
                                </Button>
                            </div>
                        )}
                    </div>

                    {/* Right sidebar */}
                    <div className="space-y-3">
                        {p && (
                            <div className="rounded-xl border border-border bg-card p-5">
                                <h3 className="mb-3.5 text-sm font-semibold">Pregled plaćanja</h3>
                                <div className="text-xs">
                                    <div className="flex justify-between border-b border-muted py-1.5"><span className="text-muted-foreground">Osnovna cena</span><span>{fmt(p.base_price)}</span></div>
                                    {p.class_factor_amount > 0 && <div className="flex justify-between border-b border-muted py-1.5"><span className="text-muted-foreground">{p.class_factor_label}</span><span>+ {fmt(p.class_factor_amount)}</span></div>}
                                    {p.season_amount > 0 && <div className="flex justify-between border-b border-muted py-1.5"><span className="text-muted-foreground">Sezona</span><span>+ {fmt(p.season_amount)}</span></div>}
                                    {p.occupancy_amount > 0 && <div className="flex justify-between border-b border-muted py-1.5"><span className="text-muted-foreground">Popunjenost</span><span>+ {fmt(p.occupancy_amount)}</span></div>}
                                    {p.reward_discount > 0 && <div className="flex justify-between py-1.5 text-[#185FA5]"><span>Reward poeni</span><span>− {fmt(p.reward_discount)}</span></div>}
                                    <div className="mt-1.5 flex justify-between border-t border-border pt-3 text-sm font-semibold"><span>Ukupno</span><span>{fmt(p.total)}</span></div>
                                </div>
                                <hr className="my-3.5 border-border" />
                                <div className="rounded-lg bg-[#E1F5EE] p-2.5 text-xs">
                                    <div className="font-semibold text-[#0F6E56]">Zarađeno: {p.status_points.toLocaleString('sr-RS')} status poena</div>
                                    <div className="text-[#0F6E56]">+ {p.reward_points} reward poen</div>
                                </div>
                            </div>
                        )}

                        <div className="rounded-xl border border-border bg-card p-5">
                            <h3 className="mb-3.5 text-sm font-semibold">Akcije</h3>
                            <div className="flex flex-col gap-2">
                                <Button className="w-full bg-[#185FA5] hover:bg-[#0C447C]">Preuzmi kartu (PDF)</Button>
                                <Button variant="outline" className="w-full">Dodaj u Google Wallet</Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
