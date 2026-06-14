import { Head, Link } from '@inertiajs/react';
import KupacHeader from '@/components/kupac-header';
import { Button } from '@/components/ui/button';

interface Props {
    cancellation: {
        reservation_code: string;
        route_label: string;
        date_formatted: string;
        dep_time: string;
        class_name: string;
        seat_number: string | null;
        reason_label: string;
        cancelled_at: string;
        total_paid: number;
        refund_amount: number;
        payment_method_label: string;
        reward_points_returned: number;
        user_email: string;
    };
}

function fmt(n: number) {
 return n.toLocaleString('sr-RS') + ' RSD'; 
}

export default function PotvrdaOtkazivanja({ cancellation }: Props) {
    if (!cancellation) {
        return (
            <>
                <Head title="Potvrda otkazivanja" />
                <div className="flex min-h-screen flex-col bg-background text-foreground">
                    <KupacHeader active="moji-letovi" />
                    <div className="py-20 text-center text-muted-foreground">
                        <p className="text-lg font-medium">Nema podataka o otkazivanju</p>
                    </div>
                </div>
            </>
        );
    }

    const c = cancellation;

    return (
        <>
            <Head title="Potvrda otkazivanja" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <KupacHeader active="moji-letovi" />

                <div className="bg-background px-6 py-14 text-center">
                    <div className="mx-auto mb-4 flex h-[68px] w-[68px] items-center justify-center rounded-full bg-[#FCEBEB] text-3xl text-[#A32D2D]">
                        &#10005;
                    </div>
                    <h1 className="mb-2 text-2xl font-semibold">Rezervacija je otkazana</h1>
                    <p className="mb-7 text-sm leading-relaxed text-muted-foreground">
                        Rezervacija {c.reservation_code} je uspešno otkazana.<br />
                        Potvrda je poslata na {c.user_email}
                    </p>

                    <div className="flex justify-center gap-2.5">
                        <Button className="bg-[#185FA5] hover:bg-[#0C447C]" asChild>
                            <Link href="/kupac/moji-letovi">Idi na Moji letovi</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/kupac/pretraga-letova">Pretraži nove letove</Link>
                        </Button>
                    </div>

                    <div className="mx-auto mt-6 max-w-lg rounded-xl bg-muted p-4 text-left">
                        {[
                            ['Rezervacija', c.reservation_code],
                            ['Let', c.route_label],
                            ['Datum leta', `${c.date_formatted} · ${c.dep_time}`],
                            ['Klasa', `${c.class_name}${c.seat_number ? ` · Sedište ${c.seat_number}` : ''}`],
                            ['Razlog otkazivanja', c.reason_label],
                            ['Datum otkazivanja', c.cancelled_at],
                        ].map(([label, value]) => (
                            <div key={label} className="flex justify-between border-b border-border py-1.5 text-[13px] last:border-b-0">
                                <span className="text-muted-foreground">{label}</span>
                                <span className={label === 'Rezervacija' ? 'font-semibold' : ''}>{value}</span>
                            </div>
                        ))}
                    </div>

                    <div className="mx-auto mt-4 max-w-lg rounded-xl bg-[#E1F5EE] p-4 text-left">
                        <div className="mb-2.5 text-[13px] font-semibold text-[#0F6E56]">Povraćaj sredstava</div>
                        <div className="mb-1 flex justify-between text-[13px]"><span className="text-muted-foreground">Na karticu ({c.payment_method_label})</span><span>{fmt(c.refund_amount)}</span></div>
                        <div className="mb-1 flex justify-between text-[13px]"><span className="text-muted-foreground">Očekivani rok</span><span>3–5 radnih dana</span></div>
                        <div className="mt-2 flex justify-between border-t border-[#B2E4D2] pt-2.5 text-[15px] font-semibold text-[#0F6E56]">
                            <span>Ukupno vraćeno</span><span>{fmt(c.refund_amount)}</span>
                        </div>
                    </div>

                    {c.reward_points_returned > 0 && (
                        <div className="mx-auto mt-3.5 max-w-lg rounded-xl bg-[#E6F1FB] p-3.5 text-center">
                            <div className="text-[13px] font-semibold text-[#185FA5]">
                                Vraćeno {c.reward_points_returned.toLocaleString('sr-RS')} reward poena na tvoj nalog
                            </div>
                            <div className="mt-1 text-xs text-[#185FA5]">Poeni su dostupni odmah i možeš ih koristiti pri sledećoj kupovini</div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
