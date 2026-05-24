import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type PlaneStatus = 'in_garage' | 'in_flight' | 'in_service';

export type PlaneFormData = {
    reg_number: string;
    model: string;
    capacity: string;
    luxury_level: string;
    range_km: string;
    max_speed: string;
    repair_service_interval: string;
    model_year: string;
    status: PlaneStatus;
    commissioned_at: string;
    total_mileage: string;
};

type Props = {
    data: PlaneFormData;
    setData: <K extends keyof PlaneFormData>(key: K, value: PlaneFormData[K]) => void;
    errors: Partial<Record<keyof PlaneFormData, string>>;
};

export function PlaneFormFields({ data, setData, errors }: Props) {
    return (
        <div className="space-y-8">
            <section className="rounded-xl border border-border bg-card p-6">
                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Identifikacija
                </h2>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Registracioni broj *" error={errors.reg_number}>
                        <Input
                            type="number"
                            value={data.reg_number}
                            onChange={(e) => setData('reg_number', e.target.value)}
                            placeholder="npr. 12345"
                            required
                        />
                    </Field>
                    <Field label="Model *" error={errors.model}>
                        <Input
                            value={data.model}
                            onChange={(e) => setData('model', e.target.value)}
                            placeholder="npr. Boeing 737-800"
                            required
                        />
                    </Field>
                    <Field label="Godina proizvodnje *" error={errors.model_year}>
                        <Input
                            type="number"
                            value={data.model_year}
                            onChange={(e) => setData('model_year', e.target.value)}
                            min={1950}
                            max={new Date().getFullYear() + 2}
                            required
                        />
                    </Field>
                    <Field label="Datum uvođenja u flotu" error={errors.commissioned_at}>
                        <Input
                            type="date"
                            value={data.commissioned_at}
                            onChange={(e) => setData('commissioned_at', e.target.value)}
                        />
                    </Field>
                </div>
            </section>

            <section className="rounded-xl border border-border bg-card p-6">
                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Tehničke karakteristike
                </h2>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Kapacitet (broj sedišta) *" error={errors.capacity}>
                        <Input
                            type="number"
                            value={data.capacity}
                            onChange={(e) => setData('capacity', e.target.value)}
                            min={1}
                            max={1000}
                            required
                        />
                    </Field>
                    <Field label="Nivo luksuza (1–5) *" error={errors.luxury_level}>
                        <Input
                            type="number"
                            value={data.luxury_level}
                            onChange={(e) => setData('luxury_level', e.target.value)}
                            min={1}
                            max={5}
                            required
                        />
                    </Field>
                    <Field label="Dolet (km) *" error={errors.range_km}>
                        <Input
                            type="number"
                            value={data.range_km}
                            onChange={(e) => setData('range_km', e.target.value)}
                            min={1}
                            required
                        />
                    </Field>
                    <Field label="Maksimalna brzina (km/h) *" error={errors.max_speed}>
                        <Input
                            type="number"
                            value={data.max_speed}
                            onChange={(e) => setData('max_speed', e.target.value)}
                            min={1}
                            required
                        />
                    </Field>
                    <Field label="Interval servisa (letnih sati) *" error={errors.repair_service_interval}>
                        <Input
                            type="number"
                            value={data.repair_service_interval}
                            onChange={(e) => setData('repair_service_interval', e.target.value)}
                            min={1}
                            required
                        />
                    </Field>
                    <Field label="Ukupna kilometraža" error={errors.total_mileage}>
                        <Input
                            type="number"
                            value={data.total_mileage}
                            onChange={(e) => setData('total_mileage', e.target.value)}
                            min={0}
                        />
                    </Field>
                </div>
            </section>

            <section className="rounded-xl border border-border bg-card p-6">
                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Status
                </h2>
                <Field label="Trenutni status *" error={errors.status}>
                    <select
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value as PlaneStatus)}
                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="in_garage">U hangaru</option>
                        <option value="in_flight">U letu</option>
                        <option value="in_service">Na servisu</option>
                    </select>
                </Field>
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
