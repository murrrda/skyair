import { Head, Link, router } from '@inertiajs/react';
import KupacHeader from '@/components/kupac-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useState } from 'react';

interface Flight {
    id: number;
    dep_time: string;
    arr_time: string;
    dep_code: string;
    dep_city: string;
    arr_code: string;
    arr_city: string;
    duration: string;
    type: string;
    economy_price: number | null;
    business_price: number | null;
    first_price: number | null;
    occupancy_pct: number | null;
}

interface Props {
    flights: Flight[];
    query: {
        from?: string;
        to?: string;
        date?: string;
        passengers?: number;
        class?: string;
    };
    filters: {
        price_min?: number;
        price_max?: number;
        time_of_day?: string[];
        classes?: string[];
        stops?: string;
    };
    sort?: string;
}

function formatPrice(price: number | null) {
    if (price === null) return '—';
    return price.toLocaleString('sr-RS') + ' RSD';
}

function occupancyTag(pct: number | null) {
    if (pct === null) return null;
    if (pct >= 80) return { label: 'Visoka popunjenost', color: 'bg-[#E1F5EE] text-[#0F6E56]' };
    if (pct <= 35) return { label: 'Niska popunjenost', color: 'bg-[#FAEEDA] text-[#854F0B]' };
    return null;
}

export default function RezultatiPretrage({ flights = [], query = {}, filters = {}, sort = 'price_asc' }: Props) {
    const [selectedSort, setSelectedSort] = useState(sort);
    const [priceMin, setPriceMin] = useState(filters.price_min?.toString() ?? '');
    const [priceMax, setPriceMax] = useState(filters.price_max?.toString() ?? '');

    function applyFilters() {
        router.get('/kupac/rezultati-pretrage', {
            ...query,
            price_min: priceMin || undefined,
            price_max: priceMax || undefined,
            sort: selectedSort,
        }, { preserveState: true });
    }

    function changeSort(value: string) {
        setSelectedSort(value);
        router.get('/kupac/rezultati-pretrage', { ...query, sort: value }, { preserveState: true });
    }

    const summary = [query.from, query.to].filter(Boolean).join(' → ')
        + (query.date ? ` · ${query.date}` : '')
        + (query.passengers ? ` · ${query.passengers} odrasli` : '')
        + (query.class ? ` · ${query.class}` : '');

    return (
        <>
            <Head title="Rezultati pretrage" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <KupacHeader active="letovi" />

                <div className="flex items-center gap-4 border-b border-border/60 bg-background px-6 py-3 text-xs text-muted-foreground">
                    <span>{summary || 'Pretraga letova'}</span>
                    <Button variant="outline" size="sm" className="text-xs" asChild>
                        <Link href="/kupac/pretraga-letova">Izmeni pretragu</Link>
                    </Button>
                </div>

                <div className="grid min-h-[600px] grid-cols-[240px_1fr]">
                    <aside className="border-r border-border/60 bg-muted/50 p-5">
                        <h3 className="mb-4 text-[13px] font-semibold">Filteri</h3>

                        <div className="mb-5">
                            <span className="mb-2 block text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Cena (RSD)</span>
                            <div className="flex gap-1.5">
                                <Input placeholder="Od" className="h-8 text-xs" value={priceMin} onChange={e => setPriceMin(e.target.value)} />
                                <Input placeholder="Do" className="h-8 text-xs" value={priceMax} onChange={e => setPriceMax(e.target.value)} />
                            </div>
                        </div>

                        <div className="mb-5">
                            <span className="mb-2 block text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Vreme polaska</span>
                            <label className="mb-1.5 flex items-center gap-2 text-[13px]"><input type="checkbox" defaultChecked className="rounded" /> Jutro (06–12h)</label>
                            <label className="mb-1.5 flex items-center gap-2 text-[13px]"><input type="checkbox" className="rounded" /> Popodne (12–18h)</label>
                            <label className="mb-1.5 flex items-center gap-2 text-[13px]"><input type="checkbox" className="rounded" /> Veče (18–24h)</label>
                        </div>

                        <div className="mb-5">
                            <span className="mb-2 block text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Klasa</span>
                            <label className="mb-1.5 flex items-center gap-2 text-[13px]"><input type="checkbox" defaultChecked className="rounded" /> Ekonomska</label>
                            <label className="mb-1.5 flex items-center gap-2 text-[13px]"><input type="checkbox" className="rounded" /> Biznis</label>
                            <label className="mb-1.5 flex items-center gap-2 text-[13px]"><input type="checkbox" className="rounded" /> Prva</label>
                        </div>

                        <div className="mb-5">
                            <span className="mb-2 block text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Presedanja</span>
                            <label className="mb-1.5 flex items-center gap-2 text-[13px]"><input type="radio" name="stops" defaultChecked /> Direktan let</label>
                            <label className="mb-1.5 flex items-center gap-2 text-[13px]"><input type="radio" name="stops" /> 1 presedanje</label>
                            <label className="mb-1.5 flex items-center gap-2 text-[13px]"><input type="radio" name="stops" /> Svejedno</label>
                        </div>

                        <Button variant="outline" className="w-full text-xs" onClick={applyFilters}>Primeni filtere</Button>
                    </aside>

                    <div className="p-5">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-sm font-semibold">{flights.length} letova pronađeno</h3>
                            <select
                                className="rounded-md border border-border bg-background px-3 py-1.5 text-xs"
                                value={selectedSort}
                                onChange={e => changeSort(e.target.value)}
                            >
                                <option value="price_asc">Sortiraj: Cena (rastuće)</option>
                                <option value="time_asc">Sortiraj: Vreme polaska</option>
                                <option value="duration_asc">Sortiraj: Trajanje</option>
                            </select>
                        </div>

                        {flights.length === 0 && (
                            <div className="py-20 text-center text-muted-foreground">
                                <p className="text-lg font-medium">Nema pronađenih letova</p>
                                <p className="mt-1 text-sm">Pokušaj sa drugačijim filterima ili datumima</p>
                            </div>
                        )}

                        <div className="space-y-3">
                            {flights.map((f) => {
                                const tag = occupancyTag(f.occupancy_pct);
                                return (
                                    <div key={f.id} className="grid grid-cols-[1fr_auto] rounded-xl border border-border bg-card p-4">
                                        <div className="flex items-center gap-5">
                                            <div>
                                                <div className="text-lg font-semibold">{f.dep_time}</div>
                                                <div className="text-[11px] text-muted-foreground">{f.dep_code} &middot; {f.dep_city}</div>
                                            </div>
                                            <div className="w-20 text-center">
                                                <div className="text-[11px] text-muted-foreground">{f.duration}</div>
                                                <div className="relative my-1 h-px bg-border">
                                                    <div className="absolute -right-px -top-[3px] border-b-[3px] border-l-[5px] border-t-[3px] border-b-transparent border-l-muted-foreground border-t-transparent" />
                                                </div>
                                                <div className="text-[10px] text-muted-foreground">{f.type}</div>
                                            </div>
                                            <div>
                                                <div className="text-lg font-semibold">{f.arr_time}</div>
                                                <div className="text-[11px] text-muted-foreground">{f.arr_code} &middot; {f.arr_city}</div>
                                            </div>
                                            {tag && (
                                                <span className={`ml-3 rounded-full px-2.5 py-0.5 text-[11px] font-medium ${tag.color}`}>
                                                    {tag.label}
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex flex-col items-end justify-between">
                                            <div className="flex gap-3">
                                                <div className="text-center">
                                                    <div className="text-[10px] text-muted-foreground">Ekonomska</div>
                                                    <div className="text-sm font-semibold text-[#185FA5]">{formatPrice(f.economy_price)}</div>
                                                </div>
                                                <div className="text-center">
                                                    <div className="text-[10px] text-muted-foreground">Biznis</div>
                                                    <div className="text-sm font-semibold">{formatPrice(f.business_price)}</div>
                                                </div>
                                                <div className="text-center">
                                                    <div className="text-[10px] text-muted-foreground">Prva</div>
                                                    <div className="text-sm font-semibold">{formatPrice(f.first_price)}</div>
                                                </div>
                                            </div>
                                            <Button size="sm" className="bg-[#185FA5] text-[13px] hover:bg-[#0C447C]" asChild>
                                                <Link href={`/kupac/detalji-leta/${f.id}`}>Izaberi →</Link>
                                            </Button>
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
