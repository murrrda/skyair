import { Head, Link, router } from '@inertiajs/react';
import KupacHeader from '@/components/kupac-header';
import { Button } from '@/components/ui/button';

interface Props {
    reservation: {
        id: number;
        code: string;
        is_paid: boolean;
        route_label: string;
        date_formatted: string;
        dep_time: string;
        class_name: string;
        seat_number: string | null;
        total_price: number;
        status_points: number;
        reward_points: number;
        total_status_points: number;
        gold_progress_pct: number;
        user_email: string;
    };
}

function fmt(n: number) { return n.toLocaleString('sr-RS') + ' RSD'; }

export default function PotvrdaRezervacije({ reservation }: Props) {
    if (!reservation) {
        return (
            <>
                <Head title="Potvrda" />
                <div className="flex min-h-screen flex-col bg-background text-foreground">
                    <KupacHeader />
                    <div className="py-20 text-center text-muted-foreground">
                        <p className="text-lg font-medium">Nema podataka o rezervaciji</p>
                    </div>
                </div>
            </>
        );
    }

    const r = reservation;

    return (
        <>
            <Head title="Potvrda rezervacije" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <KupacHeader />

                <div className="flex items-center gap-2 border-b border-border/60 bg-background px-6 py-3.5 text-xs text-muted-foreground">
                    <span className="font-semibold text-[#0F6E56]">&#10003; Let</span>
                    <span>—</span>
                    <span className="font-semibold text-[#0F6E56]">&#10003; Klasa</span>
                    <span>—</span>
                    <span className="font-semibold text-[#0F6E56]">&#10003; Plaćanje</span>
                    <span>—</span>
                    <span className="font-semibold text-[#185FA5]">4. Potvrda</span>
                </div>

                <div className="bg-background px-6 py-12 text-center">
                    {r.is_paid ? (
                        <>
                            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#E1F5EE] text-3xl">
                                &#10003;
                            </div>
                            <h1 className="mb-2 text-2xl font-semibold">Rezervacija plaćena!</h1>
                            <p className="mb-6 text-sm text-muted-foreground">
                                Karta je uspešno plaćena. Detalji su poslati na {r.user_email}
                            </p>
                            <div className="flex justify-center gap-2.5">
                                <Button className="bg-[#185FA5] hover:bg-[#0C447C]" asChild>
                                    <Link href={`/kupac/detalji-rezervacije/${r.id}`}>Pogledaj detalje</Link>
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/kupac/moji-letovi">Idi na Moji letovi</Link>
                                </Button>
                            </div>
                        </>
                    ) : (
                        <>
                            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#FAEEDA] text-3xl">
                                &#128203;
                            </div>
                            <h1 className="mb-2 text-2xl font-semibold">Rezervacija kreirana!</h1>
                            <p className="mb-6 text-sm text-muted-foreground">
                                Rezervacija je uspešno kreirana. Imaš 24h da platiš kartu.<br />
                                Detalji su poslati na {r.user_email}
                            </p>
                            <div className="flex justify-center gap-2.5">
                                <Button className="bg-[#185FA5] hover:bg-[#0C447C]" onClick={() => router.post(`/kupac/rezervacija/${r.id}/plati`)}>
                                    Plati odmah
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/kupac/moji-letovi">Plati kasnije</Link>
                                </Button>
                            </div>
                        </>
                    )}

                    <div className="mx-auto mt-6 max-w-lg rounded-xl bg-muted p-4 text-left">
                        {[
                            ['Rezervacija', r.code, true],
                            ['Let', r.route_label, false],
                            ['Datum', `${r.date_formatted} · ${r.dep_time}`, false],
                            ['Klasa', `${r.class_name}${r.seat_number ? ` · Sedište ${r.seat_number}` : ''}`, false],
                            ['Plaćeno', fmt(r.total_price), true],
                        ].map(([label, value, bold]) => (
                            <div key={label as string} className="flex justify-between border-b border-border py-1.5 text-[13px] last:border-b-0">
                                <span className="text-muted-foreground">{label as string}</span>
                                <span className={(bold as boolean) ? 'font-semibold' : ''}>{value as string}</span>
                            </div>
                        ))}
                    </div>

                    <div className="mx-auto mt-4 max-w-lg rounded-xl bg-[#E1F5EE] p-3.5 text-center">
                        <div className="text-[13px] font-semibold text-[#0F6E56]">
                            Zarađeno {r.status_points.toLocaleString('sr-RS')} status poena i {r.reward_points} reward poen!
                        </div>
                        <div className="mt-1 text-xs text-[#0F6E56]">
                            Ukupno status poena: {r.total_status_points.toLocaleString('sr-RS')} &middot; Napredak ka Gold tier-u: {r.gold_progress_pct}%
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
