import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import KupacHeader from '@/components/kupac-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface ReservationInfo {
    id: number;
    code: string;
    total_price: number;
    status: string;
    deadline: string;
    deadline_passed: boolean;
    dep_city: string;
    arr_city: string;
    dep_code: string;
    arr_code: string;
    dep_time: string | null;
    passenger: string | null;
}

interface Props {
    reservation: ReservationInfo;
}

function formatCardNumber(raw: string) {
    const digits = raw.replace(/\D/g, '').slice(0, 19);

    return digits.replace(/(\d{4})(?=\d)/g, '$1 ');
}

function formatExpiry(raw: string) {
    const digits = raw.replace(/\D/g, '').slice(0, 4);

    if (digits.length <= 2) {
        return digits;
    }

    return digits.slice(0, 2) + '/' + digits.slice(2);
}

export default function PlacanjeKarticom({ reservation }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        card_number: '',
        card_holder: '',
        expiry: '',
        cvv: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(`/kupac/rezervacija/${reservation.id}/plati`);
    }

    const deadline = new Date(reservation.deadline);

    return (
        <>
            <Head title="Plaćanje karticom" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <KupacHeader active="moji" />

                <div className="mx-auto w-full max-w-4xl px-6 py-8">
                    <div className="mb-6">
                        <Link href="/kupac/moji-letovi" className="text-xs text-muted-foreground hover:text-foreground">
                            ← Nazad na moje letove
                        </Link>
                        <h1 className="mt-2 text-2xl font-semibold">Plaćanje karticom</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Rezervacija <span className="font-mono">{reservation.code}</span> · plati pre {deadline.toLocaleString('sr-RS')}
                        </p>
                    </div>

                    {reservation.deadline_passed && (
                        <div className="mb-6 rounded-lg border border-[#FAE3D7] bg-[#FBEFE6] p-4 text-sm text-[#854F0B]">
                            Rok za plaćanje (24h) je istekao. Rezervacija će biti automatski otkazana.
                        </div>
                    )}

                    <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
                        <form onSubmit={submit} className="rounded-xl border border-border bg-card p-6">
                            <h2 className="mb-4 text-sm font-semibold">Podaci o kartici</h2>

                            <div className="mb-4">
                                <Label className="text-[11px] uppercase tracking-wide text-muted-foreground">Broj kartice</Label>
                                <Input
                                    inputMode="numeric"
                                    autoComplete="cc-number"
                                    placeholder="4242 4242 4242 4242"
                                    className="mt-1 font-mono"
                                    value={data.card_number}
                                    onChange={(e) => setData('card_number', formatCardNumber(e.target.value))}
                                />
                                {errors.card_number && <p className="mt-1 text-xs text-destructive">{errors.card_number}</p>}
                            </div>

                            <div className="mb-4">
                                <Label className="text-[11px] uppercase tracking-wide text-muted-foreground">Ime i prezime na kartici</Label>
                                <Input
                                    autoComplete="cc-name"
                                    placeholder="STEFAN CREPULJA"
                                    className="mt-1 uppercase"
                                    value={data.card_holder}
                                    onChange={(e) => setData('card_holder', e.target.value)}
                                />
                                {errors.card_holder && <p className="mt-1 text-xs text-destructive">{errors.card_holder}</p>}
                            </div>

                            <div className="mb-6 grid grid-cols-2 gap-3">
                                <div>
                                    <Label className="text-[11px] uppercase tracking-wide text-muted-foreground">Datum isteka</Label>
                                    <Input
                                        inputMode="numeric"
                                        autoComplete="cc-exp"
                                        placeholder="MM/YY"
                                        className="mt-1 font-mono"
                                        value={data.expiry}
                                        onChange={(e) => setData('expiry', formatExpiry(e.target.value))}
                                    />
                                    {errors.expiry && <p className="mt-1 text-xs text-destructive">{errors.expiry}</p>}
                                </div>
                                <div>
                                    <Label className="text-[11px] uppercase tracking-wide text-muted-foreground">CVV</Label>
                                    <Input
                                        inputMode="numeric"
                                        autoComplete="cc-csc"
                                        placeholder="123"
                                        maxLength={3}
                                        className="mt-1 font-mono"
                                        value={data.cvv}
                                        onChange={(e) => setData('cvv', e.target.value.replace(/\D/g, '').slice(0, 3))}
                                    />
                                    {errors.cvv && <p className="mt-1 text-xs text-destructive">{errors.cvv}</p>}
                                </div>
                            </div>

                            <Button
                                type="submit"
                                className="w-full bg-[#185FA5] py-6 text-sm font-semibold hover:bg-[#0C447C]"
                                disabled={processing || reservation.deadline_passed}
                            >
                                {processing ? 'Obrada…' : `Plati ${reservation.total_price.toLocaleString('sr-RS')} RSD`}
                            </Button>

                            <p className="mt-3 text-[11px] text-muted-foreground">
                                Demo plaćanje — podaci kartice se ne čuvaju i ne procesuiraju kroz pravi procesor.
                            </p>
                        </form>

                        <aside className="rounded-xl border border-border bg-muted/40 p-5">
                            <h3 className="mb-3 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Rezervacija</h3>
                            <div className="mb-3 text-sm">
                                <div className="font-semibold">{reservation.dep_city} → {reservation.arr_city}</div>
                                <div className="text-xs text-muted-foreground">{reservation.dep_code} · {reservation.arr_code}</div>
                            </div>
                            {reservation.dep_time && (
                                <div className="mb-3 text-sm">
                                    <div className="text-[11px] text-muted-foreground">Polazak</div>
                                    <div>{reservation.dep_time}</div>
                                </div>
                            )}
                            {reservation.passenger && (
                                <div className="mb-3 text-sm">
                                    <div className="text-[11px] text-muted-foreground">Putnik</div>
                                    <div>{reservation.passenger}</div>
                                </div>
                            )}
                            <div className="mt-4 border-t border-border pt-3 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Za plaćanje</span>
                                    <span className="font-semibold">{reservation.total_price.toLocaleString('sr-RS')} RSD</span>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </>
    );
}
