import { Head, Link, useForm } from '@inertiajs/react';
import { Award, Check, Plus, X } from 'lucide-react';
import { useRef } from 'react';
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

type CertificateType = {
    id: number;
    name: string;
    default_validity_months: number | null;
};

type Certificate = {
    id: number;
    certificate_type_id: number;
    issued_at: string;
    expires_at: string;
    note: string | null;
};

type Props = {
    zaposlen: {
        user_id: number;
        first_name: string;
        last_name: string;
    };
    certificates: Certificate[];
    certificateTypes: CertificateType[];
    mode: 'create' | 'edit';
};

type CertificateRow = {
    id: number | null;
    certificate_type_id: string;
    issued_at: string;
    expires_at: string;
    note: string;
};

const STEPS = [
    { number: 1, label: 'Osnovni podaci', sublabel: 'Lični podaci i ugovor' },
    { number: 2, label: 'Sertifikati', sublabel: 'Licence i uvjerenja' },
    { number: 3, label: 'Obuke', sublabel: '' },
];

function emptyRow(): CertificateRow {
    return {
        id: null,
        certificate_type_id: '',
        issued_at: '',
        expires_at: '',
        note: '',
    };
}

function toRows(certificates: Certificate[]): CertificateRow[] {
    if (certificates.length === 0) {
        return [emptyRow()];
    }

    return certificates.map((c) => ({
        id: c.id,
        certificate_type_id: String(c.certificate_type_id),
        issued_at: c.issued_at?.slice(0, 10) ?? '',
        expires_at: c.expires_at?.slice(0, 10) ?? '',
        note: c.note ?? '',
    }));
}

/** issued_at + months → yyyy-mm-dd, for convenient expiry pre-fill. */
function addMonths(date: string, months: number): string {
    const d = new Date(date);

    if (Number.isNaN(d.getTime())) {
        return '';
    }

    d.setMonth(d.getMonth() + months);

    return d.toISOString().slice(0, 10);
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

export default function ZaposlenSertifikati({
    zaposlen,
    certificates,
    certificateTypes,
    mode,
}: Props) {
    const actionRef = useRef<'continue' | 'save'>('continue');

    const { data, setData, transform, put, processing, errors } = useForm<{
        certificates: CertificateRow[];
    }>({
        certificates: toRows(certificates),
    });

    transform((d) => ({ ...d, action: actionRef.current }));

    const fieldErrors = errors as unknown as Record<string, string>;

    function updateRow(index: number, patch: Partial<CertificateRow>) {
        setData(
            'certificates',
            data.certificates.map((row, i) =>
                i === index ? { ...row, ...patch } : row,
            ),
        );
    }

    function handleTypeChange(index: number, value: string) {
        const type = certificateTypes.find((t) => String(t.id) === value);
        const row = data.certificates[index];
        const patch: Partial<CertificateRow> = { certificate_type_id: value };

        // Pre-fill expiry from the type's default validity when an issue date exists.
        if (type?.default_validity_months && row.issued_at && !row.expires_at) {
            patch.expires_at = addMonths(
                row.issued_at,
                type.default_validity_months,
            );
        }

        updateRow(index, patch);
    }

    function handleIssuedChange(index: number, value: string) {
        const row = data.certificates[index];
        const type = certificateTypes.find(
            (t) => String(t.id) === row.certificate_type_id,
        );
        const patch: Partial<CertificateRow> = { issued_at: value };

        if (type?.default_validity_months && value && !row.expires_at) {
            patch.expires_at = addMonths(value, type.default_validity_months);
        }

        updateRow(index, patch);
    }

    function addRow() {
        setData('certificates', [...data.certificates, emptyRow()]);
    }

    function removeRow(index: number) {
        const next = data.certificates.filter((_, i) => i !== index);
        setData('certificates', next.length > 0 ? next : [emptyRow()]);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/admin/employee/${zaposlen.user_id}/sertifikati`);
    }

    const filledCount = data.certificates.filter(
        (r) => r.certificate_type_id !== '',
    ).length;

    return (
        <>
            <Head title="Sertifikati zaposlenog" />

            {/* Step indicator */}
            <div className="mb-8 flex items-center justify-center">
                {STEPS.map((step, i) => {
                    const completed = step.number < 2;
                    const active = step.number === 2;

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
                    Sertifikati zaposlenog
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Dodajte licence i uvjerenja za{' '}
                    <span className="font-medium text-foreground">
                        {zaposlen.first_name} {zaposlen.last_name}
                    </span>
                    . Svaka obnova se unosi kao novi unos.
                </p>
            </div>

            <form
                id="certificates-form"
                onSubmit={handleSubmit}
                className="space-y-4 pb-24"
            >
                {data.certificates.map((row, index) => (
                    <div
                        key={index}
                        className="rounded-lg border border-border bg-card p-6"
                    >
                        <div className="mb-5 flex items-center justify-between">
                            <div className="flex items-center gap-2.5">
                                <Award className="size-4 text-muted-foreground" />
                                <h2 className="font-semibold">
                                    Sertifikat #{index + 1}
                                </h2>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="text-muted-foreground hover:text-destructive"
                                onClick={() => removeRow(index)}
                                aria-label="Ukloni sertifikat"
                            >
                                <X className="size-4" />
                            </Button>
                        </div>

                        <div className="space-y-4">
                            <Field
                                label="Tip sertifikata"
                                required
                                error={
                                    fieldErrors[
                                        `certificates.${index}.certificate_type_id`
                                    ]
                                }
                            >
                                <Select
                                    value={row.certificate_type_id}
                                    onValueChange={(v) =>
                                        handleTypeChange(index, v)
                                    }
                                >
                                    <SelectTrigger
                                        className={cn(
                                            'w-full',
                                            fieldErrors[
                                                `certificates.${index}.certificate_type_id`
                                            ] && 'border-destructive',
                                        )}
                                    >
                                        <SelectValue placeholder="Izaberite tip..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {certificateTypes.map((type) => (
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

                            <div className="grid grid-cols-2 gap-4">
                                <Field
                                    label="Datum izdavanja"
                                    required
                                    error={
                                        fieldErrors[
                                            `certificates.${index}.issued_at`
                                        ]
                                    }
                                >
                                    <Input
                                        type="date"
                                        value={row.issued_at}
                                        onChange={(e) =>
                                            handleIssuedChange(
                                                index,
                                                e.target.value,
                                            )
                                        }
                                        className={
                                            fieldErrors[
                                                `certificates.${index}.issued_at`
                                            ]
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Datum isteka"
                                    required
                                    error={
                                        fieldErrors[
                                            `certificates.${index}.expires_at`
                                        ]
                                    }
                                >
                                    <Input
                                        type="date"
                                        value={row.expires_at}
                                        onChange={(e) =>
                                            updateRow(index, {
                                                expires_at: e.target.value,
                                            })
                                        }
                                        className={
                                            fieldErrors[
                                                `certificates.${index}.expires_at`
                                            ]
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    />
                                </Field>
                            </div>

                            <Field
                                label="Opis / napomena"
                                error={
                                    fieldErrors[`certificates.${index}.note`]
                                }
                            >
                                <textarea
                                    rows={3}
                                    value={row.note}
                                    onChange={(e) =>
                                        updateRow(index, {
                                            note: e.target.value,
                                        })
                                    }
                                    placeholder="Opciona napomena..."
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
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
                    Dodaj još jedan sertifikat
                </button>
            </form>

            {/* Sticky footer */}
            <div className="fixed right-0 bottom-0 left-0 border-t border-border bg-background">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                    <span className="text-sm text-muted-foreground">
                        Korak 2 od 3 &bull;{' '}
                        <span className="text-foreground">
                            Dodato {filledCount} sertifikata
                        </span>
                    </span>
                    <div className="flex items-center gap-3">
                        <Button type="button" variant="outline" asChild>
                            <Link
                                href={`/admin/employee/${zaposlen.user_id}/edit`}
                            >
                                ← Nazad
                            </Link>
                        </Button>
                        <Button
                            type="submit"
                            form="certificates-form"
                            disabled={processing}
                            onClick={() => (actionRef.current = 'continue')}
                            className="bg-[#185FA5] hover:bg-[#0C447C]"
                        >
                            Sledeći korak →
                        </Button>
                        {mode === 'edit' && (
                            <Button
                                type="submit"
                                form="certificates-form"
                                disabled={processing}
                                onClick={() => (actionRef.current = 'save')}
                                className="bg-[#0F6E56] text-white hover:bg-[#0B5743]"
                            >
                                ✓ Sačuvaj promene
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
