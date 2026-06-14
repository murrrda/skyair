import { Head, Link, useForm } from '@inertiajs/react';
import KupacHeader from '@/components/kupac-header';
import { Button } from '@/components/ui/button';

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

interface Props {
    reservation: {
        id: number;
        code: string;
        status: string;
        date_formatted: string;
        class_name: string;
        seat_number: string | null;
        total_price: number;
        reward_points_used: number;
        cancel_deadline: string;
        cancellation_fee: number;
        refund_amount: number;
        payment_method_label: string;
        flight: FlightInfo;
    };
}

function fmt(n: number) {
 return n.toLocaleString('sr-RS') + ' RSD'; 
}

export default function OtkazivanjeRezervacije({ reservation }: Props) {
    const { data, setData, post, processing } = useForm({
        reason: '',
        note: '',
    });

    if (!reservation) {
        return (
            <>
                <Head title="Otkazivanje" />
                <div className="flex min-h-screen flex-col bg-background text-foreground">
                    <KupacHeader active="moji-letovi" />
                    <div className="py-20 text-center text-muted-foreground">
                        <p className="text-lg font-medium">Rezervacija nije pronađena</p>
                    </div>
                </div>
            </>
        );
    }

    const r = reservation;
    const f = r.flight;

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(`/kupac/otkazivanje-rezervacije/${r.id}`);
    }

    return (
        <>
            <Head title={`Otkazivanje ${r.code}`} />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <KupacHeader active="moji-letovi" />

                <div className="border-b border-border/60 bg-background px-6 py-3 text-xs text-muted-foreground">
                    <Link href={`/kupac/detalji-rezervacije/${r.id}`} className="hover:text-foreground">← Nazad na detalje rezervacije</Link>
                    <span> &middot; Otkazivanje {r.code}</span>
                </div>

                <form onSubmit={submit} className="grid grid-cols-[1fr_320px] gap-4 bg-muted p-5">
                    <div className="space-y-3">
                        <div className="rounded-xl border border-border bg-card p-5">
                            <h3 className="mb-3.5 text-sm font-semibold">Rezervacija koju otkazuješ</h3>
                            <div className="flex items-center gap-5 rounded-lg bg-muted/80 p-3.5">
                                <div>
                                    <div className="text-xl font-semibold">{f.dep_time}</div>
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
                                    <div className="text-xl font-semibold">{f.arr_time}</div>
                                    <div className="text-xs text-muted-foreground">{f.arr_code} &middot; {f.arr_city}</div>
                                </div>
                            </div>
                            <div className="mt-3 grid grid-cols-3 gap-3 text-xs">
                                <div><div className="mb-0.5 text-[11px] text-muted-foreground">Datum leta</div><div className="font-medium">{r.date_formatted}</div></div>
                                <div><div className="mb-0.5 text-[11px] text-muted-foreground">Klasa</div><div className="font-medium">{r.class_name}{r.seat_number ? ` · Sedište ${r.seat_number}` : ''}</div></div>
                                <div>
                                    <div className="mb-0.5 text-[11px] text-muted-foreground">Status</div>
                                    <span className="rounded-full bg-[#E6F1FB] px-2.5 py-0.5 text-[11px] font-medium text-[#185FA5]">
                                        {r.status.charAt(0).toUpperCase() + r.status.slice(1)}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-lg border border-[#F0C5C5] bg-[#FCEBEB] p-3.5">
                            <div className="text-[13px] font-semibold text-[#A32D2D]">Otkazivanje je moguće do {r.cancel_deadline}</div>
                            <div className="mt-1 text-xs leading-relaxed text-[#A32D2D]">
                                Sistem omogućava otkazivanje karte najviše 3 dana pre početka leta. Nakon tog roka otkazivanje nije moguće.
                            </div>
                        </div>

                        {r.reward_points_used > 0 && (
                            <div className="rounded-lg border border-[#F0D9B5] bg-[#FAEEDA] p-3.5">
                                <div className="text-[13px] font-semibold text-[#854F0B]">Šta se dešava sa reward poenima?</div>
                                <div className="mt-1 text-xs leading-relaxed text-[#854F0B]">
                                    Reward poeni iskorišćeni pri plaćanju ove karte biće vraćeni na tvoj nalog u roku od 24h nakon potvrde otkazivanja.
                                </div>
                            </div>
                        )}

                        <div className="rounded-xl border border-border bg-card p-5">
                            <h3 className="mb-3.5 text-sm font-semibold">Razlog otkazivanja</h3>
                            <div className="mb-3">
                                <label className="mb-1 block text-[11px] font-medium text-muted-foreground">Odaberi razlog</label>
                                <select
                                    className="w-full rounded-md border border-border bg-background px-3 py-2 text-[13px]"
                                    value={data.reason}
                                    onChange={e => setData('reason', e.target.value)}
                                    required
                                >
                                    <option value="">Odaberi razlog...</option>
                                    <option value="promena_planova">Promena planova</option>
                                    <option value="zdravstveni_razlozi">Zdravstveni razlozi</option>
                                    <option value="vanredna_situacija">Vanredna situacija</option>
                                    <option value="jeftinija_karta">Pronašao jeftiniju kartu</option>
                                    <option value="ostalo">Ostalo</option>
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-[11px] font-medium text-muted-foreground">Dodatni komentar (opciono)</label>
                                <textarea
                                    rows={3}
                                    placeholder="Unesite dodatne detalje..."
                                    className="w-full resize-none rounded-md border border-border bg-background px-3 py-2 text-[13px] leading-relaxed"
                                    value={data.note}
                                    onChange={e => setData('note', e.target.value)}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="sticky top-4 self-start rounded-xl border border-border bg-card p-5">
                        <h3 className="mb-3.5 text-sm font-semibold">Pregled povraćaja</h3>
                        <div className="mb-1 flex justify-between text-xs"><span className="text-muted-foreground">Plaćeno</span><span>{fmt(r.total_price)}</span></div>
                        <div className="mb-1 flex justify-between text-xs"><span className="text-muted-foreground">Naknada za otkazivanje</span><span className="text-[#A32D2D]">− {fmt(r.cancellation_fee)}</span></div>

                        <hr className="my-3 border-border" />

                        <div className="rounded-lg bg-[#E1F5EE] p-3">
                            <div className="mb-2 text-xs font-semibold text-[#0F6E56]">Iznos povraćaja</div>
                            <div className="mb-1 flex justify-between text-xs"><span className="text-muted-foreground">Na karticu ({r.payment_method_label})</span><span>{fmt(r.refund_amount)}</span></div>
                            <div className="mb-1 flex justify-between text-xs"><span className="text-muted-foreground">Rok povraćaja</span><span>3–5 radnih dana</span></div>
                            {r.reward_points_used > 0 && (
                                <div className="flex justify-between text-xs"><span className="text-muted-foreground">Reward poeni</span><span>+ {r.reward_points_used.toLocaleString('sr-RS')} poena</span></div>
                            )}
                        </div>

                        <hr className="my-3 border-border" />

                        <div className="flex gap-2.5">
                            <Button type="button" variant="outline" className="flex-1" asChild>
                                <Link href={`/kupac/detalji-rezervacije/${r.id}`}>Odustani</Link>
                            </Button>
                            <Button type="submit" variant="destructive" className="flex-1" disabled={processing}>
                                {processing ? 'Procesiranje...' : 'Potvrdi otkazivanje'}
                            </Button>
                        </div>
                        <p className="mt-2 text-center text-[10px] text-muted-foreground">Ova akcija je nepovratna</p>
                    </div>
                </form>
            </div>
        </>
    );
}
