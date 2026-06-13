import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type RouteOption = {
    id: number;
    name: string;
    label: string;
    distance_km: number;
    estimated_time: number;
};

type Flight = {
    id: number;
    route_id: number;
    dep_code: string;
    dep_city: string;
    arr_code: string;
    arr_city: string;
    plane_model: string;
    takeoff_formatted: string;
    arrival_formatted: string;
    status: string;
};

type FormData = {
    new_route_id: string;
    reason: string;
};

type PageProps = {
    flight: Flight;
    routes: RouteOption[];
};

export default function PromenaRute() {
    const { flight, routes } = usePage<PageProps>().props;
    const { data, setData, post, processing, errors } = useForm<FormData>({
        new_route_id: '',
        reason: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post(`/dispatcher/letovi/${flight.id}/promena-rute`);
    }

    return (
        <>
            <Head title="Promena rute" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3 text-sm">
                            <Link href="/dispatcher" className="text-muted-foreground hover:text-foreground">
                                Upravljanje letovima
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Promena rute</span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">
                            Promena rute: {flight.dep_code} → {flight.arr_code}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Let {flight.takeoff_formatted} · {flight.plane_model}
                        </p>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="space-y-8">
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Trenutna ruta
                                </h2>
                                <div className="rounded-lg bg-muted/50 p-4 text-sm">
                                    <span className="font-semibold">{flight.dep_code} → {flight.arr_code}</span>
                                    <span className="ml-2 text-muted-foreground">
                                        ({flight.dep_city} — {flight.arr_city})
                                    </span>
                                </div>
                            </section>

                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Nova ruta
                                </h2>
                                <div className="space-y-4">
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-medium">Izaberite novu rutu *</Label>
                                        <select
                                            value={data.new_route_id}
                                            onChange={(e) => setData('new_route_id', e.target.value)}
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            required
                                        >
                                            <option value="">Izaberite rutu</option>
                                            {routes.map((r) => (
                                                <option key={r.id} value={r.id}>
                                                    {r.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.new_route_id} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-medium">Razlog promene *</Label>
                                        <textarea
                                            value={data.reason}
                                            onChange={(e) => setData('reason', e.target.value)}
                                            className="min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            placeholder="Unesite razlog za promenu rute (npr. loše vreme, zatvoreni vazdušni prostor…)"
                                            required
                                        />
                                        <InputError message={errors.reason} />
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div className="mt-6 flex items-center justify-end gap-2 border-t border-border pt-6">
                            <Button asChild variant="ghost">
                                <Link href="/dispatcher">Otkaži</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Menjanje…' : 'Potvrdi promenu rute'}
                            </Button>
                        </div>
                    </form>
                </main>
            </div>
        </>
    );
}
