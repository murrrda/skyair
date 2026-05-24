import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type AirportOption = {
    id: number;
    iata_code: string;
    label: string;
};

export type RouteFormData = {
    name: string;
    starting_airport_id: string;
    landing_airport_id: string;
    distance_km: string;
    estimated_time: string;
    active: boolean;
};

type Props = {
    data: RouteFormData;
    setData: <K extends keyof RouteFormData>(key: K, value: RouteFormData[K]) => void;
    errors: Partial<Record<keyof RouteFormData, string>>;
    airports: AirportOption[];
};

const selectClass = 'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export function RouteFormFields({ data, setData, errors, airports }: Props) {
    return (
        <div className="space-y-8">
            <section className="rounded-xl border border-border bg-card p-6">
                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Osnovni podaci
                </h2>
                <div className="space-y-4">
                    <Field label="Naziv rute *" error={errors.name}>
                        <Input
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="npr. BEG → CDG"
                            required
                        />
                    </Field>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Polazni aerodrom *" error={errors.starting_airport_id}>
                            <select
                                value={data.starting_airport_id}
                                onChange={(e) => setData('starting_airport_id', e.target.value)}
                                className={selectClass}
                                required
                            >
                                <option value="">Izaberi aerodrom…</option>
                                {airports.map((a) => (
                                    <option key={a.id} value={String(a.id)}>
                                        {a.label}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label="Krajnji aerodrom *" error={errors.landing_airport_id}>
                            <select
                                value={data.landing_airport_id}
                                onChange={(e) => setData('landing_airport_id', e.target.value)}
                                className={selectClass}
                                required
                            >
                                <option value="">Izaberi aerodrom…</option>
                                {airports.map((a) => (
                                    <option key={a.id} value={String(a.id)}>
                                        {a.label}
                                    </option>
                                ))}
                            </select>
                        </Field>
                    </div>
                </div>
            </section>

            <section className="rounded-xl border border-border bg-card p-6">
                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Detalji
                </h2>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Distanca (km) *" error={errors.distance_km}>
                        <Input
                            type="number"
                            value={data.distance_km}
                            onChange={(e) => setData('distance_km', e.target.value)}
                            min={1}
                            required
                        />
                    </Field>
                    <Field label="Procenjeno trajanje (minuta) *" error={errors.estimated_time}>
                        <Input
                            type="number"
                            value={data.estimated_time}
                            onChange={(e) => setData('estimated_time', e.target.value)}
                            min={1}
                            required
                        />
                    </Field>
                </div>
                <div className="mt-4">
                    <Field label="Aktivna" error={errors.active}>
                        <label className="flex cursor-pointer items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={data.active}
                                onChange={(e) => setData('active', e.target.checked)}
                                className="h-4 w-4 rounded border-input"
                            />
                            <span className="text-muted-foreground">
                                Ruta je dostupna za zakazivanje letova
                            </span>
                        </label>
                    </Field>
                </div>
            </section>
        </div>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label className="text-xs font-medium">{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
