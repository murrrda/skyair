import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type FlightStatus = 'scheduled' | 'boarding' | 'before_takeoff' | 'in_flight' | 'landed' | 'delayed' | 'cancelled';

type RouteOption = {
    id: number;
    name: string;
    label: string;
    distance_km: number;
    estimated_time: number;
};

type SuggestedPlane = {
    id: number;
    reg_number: number;
    model: string;
    capacity: number;
    luxury_level: number;
    status: string;
    score: number;
    reason: string;
};

type Flight = {
    id: number;
    route_id: number;
    plane_id: number | null;
    route_name: string;
    dep_code: string;
    dep_city: string;
    arr_code: string;
    arr_city: string;
    plane_model: string;
    plane_reg: string;
    expected_takeoff: string;
    expected_arrival: string;
    status: FlightStatus;
};

type FlightFormData = {
    route_id: string;
    plane_id: string;
    expected_takeoff: string;
    expected_arrival: string;
    status: FlightStatus;
};

type PageProps = {
    flight: Flight;
    routes: RouteOption[];
};

const statusOptions: { value: FlightStatus; label: string }[] = [
    { value: 'scheduled', label: 'Zakazan' },
    { value: 'boarding', label: 'Ukrcavanje' },
    { value: 'before_takeoff', label: 'Pred poletanje' },
    { value: 'in_flight', label: 'U letu' },
    { value: 'landed', label: 'Sleteo' },
    { value: 'delayed', label: 'Kasni' },
    { value: 'cancelled', label: 'Otkazan' },
];

function toLocalDatetime(iso: string): string {
    if (!iso) {
return '';
}

    const d = new Date(iso);
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export default function IzmenaLeta() {
    const { flight, routes } = usePage<PageProps>().props;

    const { data, setData, patch, processing, errors } = useForm<FlightFormData>({
        route_id: String(flight.route_id),
        plane_id: flight.plane_id ? String(flight.plane_id) : '',
        expected_takeoff: toLocalDatetime(flight.expected_takeoff),
        expected_arrival: toLocalDatetime(flight.expected_arrival),
        status: flight.status,
    });

    const [suggestions, setSuggestions] = useState<SuggestedPlane[]>([]);
    const [loadingSuggestions, setLoadingSuggestions] = useState(false);

    function fetchSuggestionsFor(takeoffVal: string, arrivalVal: string) {
        if (!takeoffVal || !arrivalVal) {
return;
}

        const takeoff = new Date(takeoffVal);
        const arrival = new Date(arrivalVal);

        if (arrival <= takeoff) {
return;
}

        setLoadingSuggestions(true);
        fetch('/dispatcher/suggest-plane', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                expected_takeoff: takeoffVal,
                expected_arrival: arrivalVal,
                exclude_flight_id: flight.id,
            }),
        })
            .then((res) => (res.ok ? res.json() : null))
            .then((json) => {
                if (json) {
                    setSuggestions(json.planes ?? []);
                }
            })
            .finally(() => setLoadingSuggestions(false));
    }

    function handleTakeoffChange(val: string) {
        setData('expected_takeoff', val);
        fetchSuggestionsFor(val, data.expected_arrival);
    }

    function handleArrivalChange(val: string) {
        setData('expected_arrival', val);
        fetchSuggestionsFor(data.expected_takeoff, val);
    }

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        patch(`/dispatcher/letovi/${flight.id}`);
    }

    return (
        <>
            <Head title="Izmena leta" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3 text-sm">
                            <Link href="/dispatcher" className="text-muted-foreground hover:text-foreground">
                                Upravljanje letovima
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Izmena leta</span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">
                            Izmena leta: {flight.dep_code} → {flight.arr_code}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Izmenite detalje leta. Sve promene se primenjuju odmah.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="space-y-8">
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Ruta i vreme
                                </h2>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-1.5 sm:col-span-2">
                                        <Label className="text-xs font-medium">Ruta *</Label>
                                        <select
                                            value={data.route_id}
                                            onChange={(e) => setData('route_id', e.target.value)}
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
                                        <InputError message={errors.route_id} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-medium">Očekivano poletanje *</Label>
                                        <Input
                                            type="datetime-local"
                                            value={data.expected_takeoff}
                                            onChange={(e) => handleTakeoffChange(e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.expected_takeoff} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-medium">Očekivano sletanje *</Label>
                                        <Input
                                            type="datetime-local"
                                            value={data.expected_arrival}
                                            onChange={(e) => handleArrivalChange(e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.expected_arrival} />
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Status
                                </h2>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-medium">Status leta *</Label>
                                    <select
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value as FlightStatus)}
                                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        required
                                    >
                                        {statusOptions.map((opt) => (
                                            <option key={opt.value} value={opt.value}>
                                                {opt.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.status} />
                                </div>
                            </section>

                            <section className="rounded-xl border border-border bg-card p-6">
                                <div className="mb-5 flex items-center justify-between">
                                    <h2 className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                        Avion
                                    </h2>
                                    <button
                                        type="button"
                                        onClick={() => fetchSuggestionsFor(data.expected_takeoff, data.expected_arrival)}
                                        className="text-xs text-primary hover:underline"
                                    >
                                        Osveži preporuke
                                    </button>
                                </div>
                                {loadingSuggestions ? (
                                    <p className="text-sm text-muted-foreground">Tražim dostupne avione…</p>
                                ) : suggestions.length > 0 ? (
                                    <div className="space-y-2">
                                        {suggestions.map((plane, i) => (
                                            <label
                                                key={plane.id}
                                                className={`flex cursor-pointer items-center gap-4 rounded-lg border p-4 transition ${
                                                    data.plane_id === String(plane.id)
                                                        ? 'border-primary bg-primary/5'
                                                        : 'border-border hover:bg-accent/50'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="plane_id"
                                                    value={plane.id}
                                                    checked={data.plane_id === String(plane.id)}
                                                    onChange={() => setData('plane_id', String(plane.id))}
                                                    className="accent-primary"
                                                />
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-semibold">{plane.reg_number}</span>
                                                        <span className="text-sm text-muted-foreground">{plane.model}</span>
                                                        {i === 0 && (
                                                            <span className="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary">
                                                                Preporučen
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="mt-1 text-xs text-muted-foreground">
                                                        Kapacitet: {plane.capacity} · Luksuz: {plane.luxury_level} · Skor: {plane.score}
                                                    </div>
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-amber-600">
                                        Nema drugih dostupnih aviona za odabrani period.
                                    </p>
                                )}
                                <InputError message={errors.plane_id} />
                            </section>
                        </div>

                        <div className="mt-6 flex items-center justify-end gap-2 border-t border-border pt-6">
                            <Button asChild variant="ghost">
                                <Link href="/dispatcher">Otkaži</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Čuvanje…' : 'Sačuvaj izmene'}
                            </Button>
                        </div>
                    </form>
                </main>
            </div>
        </>
    );
}
