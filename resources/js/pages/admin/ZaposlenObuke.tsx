import { Head, Link, useForm } from '@inertiajs/react';
import { Check, GraduationCap, Plus, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

type TrainingType = {
    id: number;
    name: string;
    duration_days: number | null;
};

type Training = {
    id: number;
    training_type_id: number;
    started_at: string;
    finished_at: string;
    note: string | null;
};

type Props = {
    zaposlen: {
        user_id: number;
        first_name: string;
        last_name: string;
    };
    trainings: Training[];
    trainingTypes: TrainingType[];
};

type TrainingRow = {
    id: number | null;
    training_type_id: string;
    started_at: string;
    finished_at: string;
    note: string;
};

const STEPS = [
    { number: 1, label: 'Osnovni podaci', sublabel: 'Lični podaci i ugovor' },
    { number: 2, label: 'Sertifikati', sublabel: 'Licence i uvjerenja' },
    { number: 3, label: 'Obuke', sublabel: 'Završene obuke' },
];

function emptyRow(): TrainingRow {
    return {
        id: null,
        training_type_id: '',
        started_at: '',
        finished_at: '',
        note: '',
    };
}

function toRows(trainings: Training[]): TrainingRow[] {
    if (trainings.length === 0) {
        return [emptyRow()];
    }

    return trainings.map((t) => ({
        id: t.id,
        training_type_id: String(t.training_type_id),
        started_at: t.started_at?.slice(0, 10) ?? '',
        finished_at: t.finished_at?.slice(0, 10) ?? '',
        note: t.note ?? '',
    }));
}

function Field({
    label,
    required,
    error,
    children,
}: {
    label: string;
    required?: boolean;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                {label}
                {required && <span className="ml-0.5 text-destructive">*</span>}
            </Label>
            {children}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}

export default function ZaposlenObuke({
    zaposlen,
    trainings,
    trainingTypes,
}: Props) {
    const { data, setData, put, processing, errors } = useForm<{
        trainings: TrainingRow[];
    }>({
        trainings: toRows(trainings),
    });

    const fieldErrors = errors as unknown as Record<string, string>;

    function updateRow(index: number, patch: Partial<TrainingRow>) {
        setData(
            'trainings',
            data.trainings.map((row, i) =>
                i === index ? { ...row, ...patch } : row,
            ),
        );
    }

    function addRow() {
        setData('trainings', [...data.trainings, emptyRow()]);
    }

    function removeRow(index: number) {
        const next = data.trainings.filter((_, i) => i !== index);
        setData('trainings', next.length > 0 ? next : [emptyRow()]);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/admin/employee/${zaposlen.user_id}/obuke`);
    }

    const filledCount = data.trainings.filter(
        (r) => r.training_type_id !== '',
    ).length;
    const typeName = (id: string) =>
        trainingTypes.find((t) => String(t.id) === id)?.name;

    return (
        <>
            <Head title="Obuke zaposlenog" />

            {/* Step indicator */}
            <div className="mb-8 flex items-center justify-center">
                {STEPS.map((step, i) => {
                    const completed = step.number < 3;
                    const active = step.number === 3;

                    return (
                        <div key={step.number} className="flex items-center">
                            <div className="flex items-center gap-3">
                                <div
                                    className={cn(
                                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                                        active && 'bg-[#185FA5] text-white',
                                        completed && 'bg-[#0F6E56] text-white',
                                        !active &&
                                            !completed &&
                                            'border-2 border-border text-muted-foreground',
                                    )}
                                >
                                    {completed ? (
                                        <Check className="size-4" />
                                    ) : (
                                        step.number
                                    )}
                                </div>
                                <div>
                                    <div
                                        className={cn(
                                            'text-sm font-medium',
                                            active || completed
                                                ? 'text-foreground'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {step.label}
                                    </div>
                                    {step.sublabel && (
                                        <div className="text-xs text-muted-foreground">
                                            {step.sublabel}
                                        </div>
                                    )}
                                </div>
                            </div>
                            {i < STEPS.length - 1 && (
                                <div className="mx-6 h-px w-28 bg-border" />
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Page title */}
            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Obuke zaposlenog
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Unesite obuke koje je{' '}
                    <span className="font-medium text-foreground">
                        {zaposlen.first_name} {zaposlen.last_name}
                    </span>{' '}
                    završio. Možete dodati više obuka.
                </p>
            </div>

            <form
                id="trainings-form"
                onSubmit={handleSubmit}
                className="space-y-4 pb-24"
            >
                {data.trainings.map((row, index) => (
                    <div
                        key={index}
                        className="rounded-lg border border-border bg-card p-6"
                    >
                        <div className="mb-5 flex items-center justify-between">
                            <div className="flex items-center gap-2.5">
                                <GraduationCap className="size-4 text-muted-foreground" />
                                <span className="rounded-full bg-[#E6F1FB] px-2.5 py-0.5 text-xs font-semibold text-[#185FA5]">
                                    Obuka #{index + 1}
                                </span>
                                <span className="text-sm font-semibold">
                                    {typeName(row.training_type_id) ??
                                        'Nova obuka'}
                                </span>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="text-muted-foreground hover:text-destructive"
                                onClick={() => removeRow(index)}
                                aria-label="Ukloni obuku"
                            >
                                <X className="size-4" />
                            </Button>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-[2fr_3fr_1.2fr_1.2fr]">
                            <Field
                                label="Tip obuke"
                                required
                                error={
                                    fieldErrors[
                                        `trainings.${index}.training_type_id`
                                    ]
                                }
                            >
                                <Select
                                    value={row.training_type_id}
                                    onValueChange={(v) =>
                                        updateRow(index, {
                                            training_type_id: v,
                                        })
                                    }
                                >
                                    <SelectTrigger
                                        className={cn(
                                            'w-full',
                                            fieldErrors[
                                                `trainings.${index}.training_type_id`
                                            ] && 'border-destructive',
                                        )}
                                    >
                                        <SelectValue placeholder="Izaberite tip..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {trainingTypes.map((type) => (
                                            <SelectItem
                                                key={type.id}
                                                value={String(type.id)}
                                            >
                                                {type.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>

                            <Field
                                label="Opis / napomena"
                                error={fieldErrors[`trainings.${index}.note`]}
                            >
                                <Input
                                    value={row.note}
                                    onChange={(e) =>
                                        updateRow(index, {
                                            note: e.target.value,
                                        })
                                    }
                                    placeholder="Opciona napomena (npr. instruktor)..."
                                />
                            </Field>

                            <Field
                                label="Datum početka"
                                required
                                error={
                                    fieldErrors[`trainings.${index}.started_at`]
                                }
                            >
                                <Input
                                    type="date"
                                    value={row.started_at}
                                    onChange={(e) =>
                                        updateRow(index, {
                                            started_at: e.target.value,
                                        })
                                    }
                                    className={
                                        fieldErrors[
                                            `trainings.${index}.started_at`
                                        ]
                                            ? 'border-destructive'
                                            : ''
                                    }
                                />
                            </Field>

                            <Field
                                label="Datum završetka"
                                required
                                error={
                                    fieldErrors[
                                        `trainings.${index}.finished_at`
                                    ]
                                }
                            >
                                <Input
                                    type="date"
                                    value={row.finished_at}
                                    onChange={(e) =>
                                        updateRow(index, {
                                            finished_at: e.target.value,
                                        })
                                    }
                                    className={
                                        fieldErrors[
                                            `trainings.${index}.finished_at`
                                        ]
                                            ? 'border-destructive'
                                            : ''
                                    }
                                />
                            </Field>
                        </div>
                    </div>
                ))}

                <button
                    type="button"
                    onClick={addRow}
                    className="flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-border bg-card/50 py-4 text-sm font-medium text-muted-foreground transition-colors hover:border-[#185FA5] hover:text-[#185FA5]"
                >
                    <Plus className="size-4" />
                    Dodaj još jednu obuku
                </button>
            </form>

            {/* Sticky footer */}
            <div className="fixed right-0 bottom-0 left-0 border-t border-border bg-background">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                    <span className="text-sm text-muted-foreground">
                        Korak 3 od 3 &bull;{' '}
                        <span className="text-foreground">
                            Dodato {filledCount} obuka
                        </span>
                    </span>
                    <div className="flex items-center gap-3">
                        <Button type="button" variant="outline" asChild>
                            <Link
                                href={`/admin/employee/${zaposlen.user_id}/sertifikati`}
                            >
                                ← Nazad
                            </Link>
                        </Button>
                        <Button
                            type="submit"
                            form="trainings-form"
                            disabled={processing}
                            className="bg-[#0F6E56] hover:bg-[#0B5743]"
                        >
                            ✓ Sačuvaj zaposlenog
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
