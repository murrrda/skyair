import { Head, Link } from '@inertiajs/react';
import KupacHeader from '@/components/kupac-header';
import { Button } from '@/components/ui/button';

interface TicketClass {
    id: number;
    name: string;
    price: number;
    status_points: number;
    features: string[];
    featured: boolean;
}

interface FlightDetail {
    id: number;
    dep_time: string;
    arr_time: string;
    dep_code: string;
    dep_city: string;
    arr_code: string;
    arr_city: string;
    duration: string;
    type: string;
    plane_model: string;
    date_formatted: string;
}

interface PricingInfo {
    base_price: number;
    season_factor: string;
    occupancy_pct: number;
    occupancy_factor: string;
    tier_discount: string;
}

interface Props {
    flight: FlightDetail;
    ticket_classes: TicketClass[];
    pricing_info: PricingInfo | null;
}

function formatPrice(price: number) {
    return price.toLocaleString('sr-RS') + ' RSD';
}

export default function DetaljiLeta({ flight, ticket_classes = [], pricing_info }: Props) {
    if (!flight) {
        return (
            <>
                <Head title="Let nije pronađen" />
                <div className="flex min-h-screen flex-col bg-background text-foreground">
                    <KupacHeader active="letovi" />
                    <div className="py-20 text-center text-muted-foreground">
                        <p className="text-lg font-medium">Let nije pronađen</p>
                        <Button variant="outline" className="mt-4" asChild>
                            <Link href="/kupac/rezultati-pretrage">Nazad na rezultate</Link>
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={`Let ${flight.dep_code} → ${flight.arr_code}`} />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <KupacHeader active="letovi" />

                <div className="border-b border-border/60 bg-background px-6 py-3.5 text-xs text-muted-foreground">
                    <Link href="/kupac/rezultati-pretrage" className="hover:text-foreground">← Nazad na rezultate</Link>
                    <span> &middot; {flight.dep_city} → {flight.arr_city} &middot; {flight.date_formatted}</span>
                </div>

                <div className="border-b border-border/60 bg-background px-6 py-5">
                    <div className="mx-auto flex max-w-4xl items-center gap-5">
                        <div>
                            <div className="text-2xl font-semibold">{flight.dep_time}</div>
                            <div className="text-xs text-muted-foreground">{flight.dep_code} &middot; {flight.dep_city}</div>
                        </div>
                        <div className="flex-1 text-center">
                            <div className="text-[11px] text-muted-foreground">{flight.duration} &middot; {flight.type}</div>
                            <div className="relative my-1.5 h-px bg-border">
                                <div className="absolute -right-px -top-[3px] border-b-[3px] border-l-[7px] border-t-[3px] border-b-transparent border-l-muted-foreground border-t-transparent" />
                            </div>
                            <div className="text-[11px] text-muted-foreground">{flight.plane_model}</div>
                        </div>
                        <div className="text-right">
                            <div className="text-2xl font-semibold">{flight.arr_time}</div>
                            <div className="text-xs text-muted-foreground">{flight.arr_code} &middot; {flight.arr_city}</div>
                        </div>
                    </div>
                </div>

                <div className="bg-muted p-6">
                    <div className="mx-auto max-w-4xl">
                        <h2 className="mb-4 text-base font-semibold">Izaberi klasu</h2>

                        {ticket_classes.length === 0 ? (
                            <p className="py-10 text-center text-muted-foreground">Nema dostupnih klasa za ovaj let</p>
                        ) : (
                            <div className={`mb-5 grid gap-3 ${ticket_classes.length >= 3 ? 'grid-cols-3' : ticket_classes.length === 2 ? 'grid-cols-2' : 'grid-cols-1'}`}>
                                {ticket_classes.map((c) => (
                                    <div
                                        key={c.id}
                                        className={`relative rounded-xl border bg-card p-5 ${c.featured ? 'border-2 border-[#185FA5]' : 'border-border'}`}
                                    >
                                        {c.featured && (
                                            <span className="absolute -top-2.5 left-5 rounded-md bg-[#185FA5] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">
                                                Najpopularnije
                                            </span>
                                        )}
                                        <div className="mb-1.5 text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{c.name}</div>
                                        <div className="mb-1 text-2xl font-semibold">{formatPrice(c.price)}</div>
                                        <div className="mb-3.5 text-[11px] text-muted-foreground">+ {c.status_points.toLocaleString('sr-RS')} status poena</div>
                                        <ul className="mb-3.5 space-y-1 text-xs leading-relaxed text-muted-foreground">
                                            {c.features.map((f) => (
                                                <li key={f}>&middot; {f}</li>
                                            ))}
                                        </ul>
                                        <Button
                                            className={`w-full ${c.featured ? 'bg-[#185FA5] hover:bg-[#0C447C]' : ''}`}
                                            variant={c.featured ? 'default' : 'outline'}
                                            asChild
                                        >
                                            <Link href={`/kupac/placanje?flight_id=${flight.id}&ticket_class_id=${c.id}`}>Izaberi</Link>
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}

                        {pricing_info && (
                            <div className="rounded-lg bg-[#FAEEDA] p-3.5">
                                <div className="text-xs font-semibold text-[#854F0B]">Cena prilagođena u realnom vremenu</div>
                                <div className="mt-1 text-[11px] leading-relaxed text-[#854F0B]">
                                    Osnovna cena {formatPrice(pricing_info.base_price)} &middot; {pricing_info.season_factor} &middot; Popunjenost {pricing_info.occupancy_pct}% {pricing_info.occupancy_factor} &middot; {pricing_info.tier_discount}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
