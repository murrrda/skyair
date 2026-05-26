import { Head, Link, useForm } from '@inertiajs/react';
import KupacHeader from '@/components/kupac-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface FlightSummary {
    id: number;
    dep_city: string;
    arr_city: string;
    dep_time: string;
    date_formatted: string;
    plane_model: string;
}

interface PriceBreakdown {
    base_price: number;
    class_factor_label: string;
    class_factor_amount: number;
    season_amount: number;
    occupancy_amount: number;
    reward_discount: number;
    total: number;
    status_points: number;
    reward_points: number;
}

interface Props {
    flight: FlightSummary;
    ticket_class_name: string;
    ticket_class_id: number;
    price_breakdown: PriceBreakdown;
    user_reward_points: number;
}

function fmt(n: number) {
    return n.toLocaleString('sr-RS') + ' RSD';
}

export default function Placanje({ flight, ticket_class_name, ticket_class_id, price_breakdown, user_reward_points = 0 }: Props) {
    const { data, setData, post, processing } = useForm({
        flight_id: flight?.id,
        ticket_class_id,
        passenger_first_name: '',
        passenger_last_name: '',
        passport_number: '',
        date_of_birth: '',
        reward_points_used: 0,
        payment_method: 'existing_card',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/kupac/placanje');
    }

    if (!flight) {
        return (
            <>
                <Head title="Plaćanje" />
                <div className="flex min-h-screen flex-col bg-background text-foreground">
                    <KupacHeader />
                    <div className="py-20 text-center text-muted-foreground">
                        <p className="text-lg font-medium">Podaci o letu nisu dostupni</p>
                    </div>
                </div>
            </>
        );
    }

    const rewardDiscount = data.reward_points_used;
    const total = price_breakdown ? price_breakdown.total - rewardDiscount + price_breakdown.reward_discount : 0;

    return (
        <>
            <Head title="Plaćanje" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <KupacHeader />

                <div className="flex items-center gap-2 border-b border-border/60 bg-background px-6 py-3.5 text-xs text-muted-foreground">
                    <span className="font-semibold text-[#0F6E56]">&#10003; Let</span>
                    <span>—</span>
                    <span className="font-semibold text-[#0F6E56]">&#10003; Klasa</span>
                    <span>—</span>
                    <span className="font-semibold text-[#185FA5]">3. Podaci i plaćanje</span>
                    <span>—</span>
                    <span>4. Potvrda</span>
                </div>

                <form onSubmit={submit} className="grid grid-cols-[1fr_320px] gap-4 bg-muted p-5">
                    <div className="space-y-3">
                        <div className="rounded-xl border border-border bg-card p-5">
                            <h3 className="mb-3.5 text-sm font-semibold">Podaci putnika</h3>
                            <div className="mb-2 grid grid-cols-2 gap-2.5">
                                <div>
                                    <Label className="text-[11px] text-muted-foreground">Ime</Label>
                                    <Input className="mt-1" value={data.passenger_first_name} onChange={e => setData('passenger_first_name', e.target.value)} required />
                                </div>
                                <div>
                                    <Label className="text-[11px] text-muted-foreground">Prezime</Label>
                                    <Input className="mt-1" value={data.passenger_last_name} onChange={e => setData('passenger_last_name', e.target.value)} required />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-2.5">
                                <div>
                                    <Label className="text-[11px] text-muted-foreground">Broj pasoša</Label>
                                    <Input className="mt-1" value={data.passport_number} onChange={e => setData('passport_number', e.target.value)} required />
                                </div>
                                <div>
                                    <Label className="text-[11px] text-muted-foreground">Datum rođenja</Label>
                                    <Input type="date" className="mt-1" value={data.date_of_birth} onChange={e => setData('date_of_birth', e.target.value)} required />
                                </div>
                            </div>
                        </div>

                        {user_reward_points > 0 && (
                            <div className="rounded-xl border border-[#378ADD] bg-card p-5">
                                <div className="mb-2.5 flex items-center justify-between">
                                    <h3 className="text-sm font-semibold">Iskoristi reward poene</h3>
                                    <span className="rounded-md bg-[#E6F1FB] px-2.5 py-1 text-[11px] font-medium text-[#185FA5]">
                                        Imaš {user_reward_points.toLocaleString('sr-RS')} poena
                                    </span>
                                </div>
                                <p className="mb-3 text-xs text-muted-foreground">1 reward poen = 1 RSD popusta na cenu karte</p>
                                <input
                                    type="range"
                                    min={0}
                                    max={Math.min(user_reward_points, price_breakdown?.total ?? 0)}
                                    value={data.reward_points_used}
                                    step={100}
                                    className="w-full accent-[#185FA5]"
                                    onChange={e => setData('reward_points_used', Number(e.target.value))}
                                />
                                <div className="mt-2 flex items-center justify-between text-[11px] text-muted-foreground">
                                    <span>0 poena</span>
                                    <span className="text-[13px] font-semibold text-[#185FA5]">
                                        {data.reward_points_used.toLocaleString('sr-RS')} poena &middot; −{fmt(data.reward_points_used)}
                                    </span>
                                    <span>{user_reward_points.toLocaleString('sr-RS')} poena</span>
                                </div>
                            </div>
                        )}

                        <div className="rounded-xl border border-border bg-card p-5">
                            <h3 className="mb-3.5 text-sm font-semibold">Način plaćanja</h3>
                            <div className="space-y-2">
                                <label className={`flex cursor-pointer items-center gap-2.5 rounded-lg border p-3 ${data.payment_method === 'existing_card' ? 'border-[#185FA5] bg-[#E6F1FB]' : 'border-border'}`}>
                                    <input type="radio" name="payment" checked={data.payment_method === 'existing_card'} onChange={() => setData('payment_method', 'existing_card')} />
                                    <span className={`text-[13px] ${data.payment_method === 'existing_card' ? 'font-semibold text-[#185FA5]' : ''}`}>Visa **** 4242</span>
                                </label>
                                <label className={`flex cursor-pointer items-center gap-2.5 rounded-lg border p-3 ${data.payment_method === 'new_card' ? 'border-[#185FA5] bg-[#E6F1FB]' : 'border-border'}`}>
                                    <input type="radio" name="payment" checked={data.payment_method === 'new_card'} onChange={() => setData('payment_method', 'new_card')} />
                                    <span className={`text-[13px] ${data.payment_method === 'new_card' ? 'font-semibold text-[#185FA5]' : ''}`}>Dodaj novu karticu</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div className="sticky top-4 self-start rounded-xl border border-border bg-card p-5">
                        <h3 className="mb-3.5 text-sm font-semibold">Pregled rezervacije</h3>
                        <div className="text-xs text-muted-foreground">{flight.dep_city} → {flight.arr_city}</div>
                        <div className="mb-3.5 text-sm">{flight.date_formatted} &middot; {flight.dep_time} &middot; {ticket_class_name}</div>

                        {price_breakdown && (
                            <>
                                <div className="border-t border-border pt-3">
                                    <div className="mb-1 flex justify-between text-xs"><span className="text-muted-foreground">Osnovna cena</span><span>{fmt(price_breakdown.base_price)}</span></div>
                                    {price_breakdown.class_factor_amount > 0 && (
                                        <div className="mb-1 flex justify-between text-xs"><span className="text-muted-foreground">{price_breakdown.class_factor_label}</span><span>+ {fmt(price_breakdown.class_factor_amount)}</span></div>
                                    )}
                                    {price_breakdown.season_amount > 0 && (
                                        <div className="mb-1 flex justify-between text-xs"><span className="text-muted-foreground">Sezona</span><span>+ {fmt(price_breakdown.season_amount)}</span></div>
                                    )}
                                    {price_breakdown.occupancy_amount > 0 && (
                                        <div className="mb-1 flex justify-between text-xs"><span className="text-muted-foreground">Popunjenost</span><span>+ {fmt(price_breakdown.occupancy_amount)}</span></div>
                                    )}
                                    {rewardDiscount > 0 && (
                                        <div className="mb-1 flex justify-between text-xs text-[#185FA5]"><span>Reward poeni</span><span>− {fmt(rewardDiscount)}</span></div>
                                    )}
                                </div>

                                <div className="mt-3 flex items-baseline justify-between border-t border-border pt-3">
                                    <span className="text-sm font-semibold">Ukupno</span>
                                    <span className="text-xl font-semibold">{fmt(total)}</span>
                                </div>

                                <div className="mt-3 rounded-lg bg-[#E1F5EE] p-2.5">
                                    <div className="text-[11px] font-semibold text-[#0F6E56]">Osvajaš {price_breakdown.status_points.toLocaleString('sr-RS')} status poena</div>
                                    <div className="mt-0.5 text-[10px] text-[#0F6E56]">+ {price_breakdown.reward_points} reward poen za buduće karte</div>
                                </div>
                            </>
                        )}

                        <Button
                            type="submit"
                            disabled={processing}
                            className="mt-3 w-full bg-[#185FA5] py-5 text-sm font-semibold hover:bg-[#0C447C]"
                        >
                            {processing ? 'Procesiranje...' : 'Rezerviši kartu'}
                        </Button>
                        <p className="mt-2 text-center text-[10px] text-muted-foreground">Imaš 24h od kreiranja rezervacije da platiš kartu</p>
                    </div>
                </form>
            </div>
        </>
    );
}
