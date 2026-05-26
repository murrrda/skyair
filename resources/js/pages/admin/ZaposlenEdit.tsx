import { Head, Link, useForm } from '@inertiajs/react';
import { FileText, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';

type TipUgovora = {
    id: number;
    naziv: string;
};

type ZaposlenData = {
    user_id: number;
    role: string;
    datum_zaposlenja: string;
    user: {
        first_name: string;
        last_name: string;
        email: string;
        date_of_birth: string | null;
        phone_number: string | null;
        address: string | null;
    };
    tipovi_ugovora: Array<{
        id: number;
        naziv: string;
        pivot: {
            datum_potpisivanja: string;
            datum_isteka: string | null;
            napomena: string | null;
        };
    }>;
};

type Props = {
    zaposlen: ZaposlenData;
    tipoviUgovora: TipUgovora[];
};

const STEPS = [
    { number: 1, label: 'Osnovni podaci', sublabel: 'Lični podaci i ugovor' },
    { number: 2, label: 'Sertifikati', sublabel: '' },
    { number: 3, label: 'Obuke', sublabel: '' },
];

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
            <Label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {label}
                {required && <span className="ml-0.5 text-destructive">*</span>}
            </Label>
            {children}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}

export default function ZaposlenEdit({ zaposlen, tipoviUgovora }: Props) {
    const contract = zaposlen.tipovi_ugovora[0];

    const { data, setData, put, processing, errors } = useForm({
        first_name:      zaposlen.user.first_name,
        last_name:       zaposlen.user.last_name,
        email:           zaposlen.user.email,
        date_of_birth:   zaposlen.user.date_of_birth ?? '',
        phone_number:    zaposlen.user.phone_number ?? '',
        address:         zaposlen.user.address ?? '',
        role:            zaposlen.role,
        datum_zaposlenja: zaposlen.datum_zaposlenja,
        tip_ugovora_id:  contract ? String(contract.id) : '',
        datum_isteka:    contract?.pivot.datum_isteka ?? '',
        napomena:        contract?.pivot.napomena ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/admin/employee/${zaposlen.user_id}`);
    }

    return (
        <>
            <Head title="Izmeni zaposlenog" />

            {/* Step indicator */}
            <div className="mb-8 flex items-center justify-center">
                {STEPS.map((step, i) => (
                    <div key={step.number} className="flex items-center">
                        <div className="flex items-center gap-3">
                            <div
                                className={cn(
                                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                                    step.number === 1
                                        ? 'bg-[#185FA5] text-white'
                                        : 'border-2 border-border text-muted-foreground',
                                )}
                            >
                                {step.number}
                            </div>
                            <div>
                                <div
                                    className={cn(
                                        'text-sm font-medium',
                                        step.number === 1 ? 'text-foreground' : 'text-muted-foreground',
                                    )}
                                >
                                    {step.label}
                                </div>
                                {step.sublabel && (
                                    <div className="text-xs text-muted-foreground">{step.sublabel}</div>
                                )}
                            </div>
                        </div>
                        {i < STEPS.length - 1 && <div className="mx-6 h-px w-28 bg-border" />}
                    </div>
                ))}
            </div>

            {/* Page title */}
            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">Osnovni podaci zaposlenog</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Unesite lične podatke i informacije o ugovoru zaposlenog.
                </p>
            </div>

            <form id="edit-form" onSubmit={handleSubmit} className="space-y-6 pb-24">
                {/* Lični podaci */}
                <div className="rounded-lg border border-border bg-card p-6">
                    <div className="mb-5 flex items-center gap-2.5">
                        <User className="size-4 text-muted-foreground" />
                        <h2 className="font-semibold">Lični podaci</h2>
                    </div>

                    <div className="space-y-4">
                        <div className="grid grid-cols-3 gap-4">
                            <Field label="IME" required error={errors.first_name}>
                                <Input
                                    value={data.first_name}
                                    onChange={(e) => setData('first_name', e.target.value)}
                                    className={errors.first_name ? 'border-destructive' : ''}
                                />
                            </Field>
                            <Field label="PREZIME" required error={errors.last_name}>
                                <Input
                                    value={data.last_name}
                                    onChange={(e) => setData('last_name', e.target.value)}
                                    className={errors.last_name ? 'border-destructive' : ''}
                                />
                            </Field>
                            <Field label="JMBG">
                                <Input placeholder="1234567890123" maxLength={13} />
                            </Field>
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            <Field label="DATUM ROĐENJA" required error={errors.date_of_birth}>
                                <Input
                                    type="date"
                                    value={data.date_of_birth}
                                    onChange={(e) => setData('date_of_birth', e.target.value)}
                                    className={errors.date_of_birth ? 'border-destructive' : ''}
                                />
                            </Field>
                            <Field label="POL">
                                <Select>
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Izaberite..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="m">Muški</SelectItem>
                                        <SelectItem value="z">Ženski</SelectItem>
                                    </SelectContent>
                                </Select>
                            </Field>
                            <Field label="DRZAVA">
                                <Input placeholder="npr. Srbija" />
                            </Field>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <Field label="E-MAIL ADRESA" required error={errors.email}>
                                <Input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className={errors.email ? 'border-destructive' : ''}
                                />
                            </Field>
                            <Field label="BROJ TELEFONA" error={errors.phone_number}>
                                <Input
                                    value={data.phone_number}
                                    onChange={(e) => setData('phone_number', e.target.value)}
                                    className={errors.phone_number ? 'border-destructive' : ''}
                                />
                            </Field>
                        </div>

                        <Field label="ADRESA STANOVANJA" error={errors.address}>
                            <Input
                                value={data.address}
                                onChange={(e) => setData('address', e.target.value)}
                                className={errors.address ? 'border-destructive' : ''}
                            />
                        </Field>
                    </div>
                </div>

                {/* Radno mjesto i ugovor */}
                <div className="rounded-lg border border-border bg-card p-6">
                    <div className="mb-5 flex items-center gap-2.5">
                        <FileText className="size-4 text-muted-foreground" />
                        <h2 className="font-semibold">Radno mjesto i ugovor</h2>
                    </div>

                    <div className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <Field label="ULOGA" required error={errors.role}>
                                <Select value={data.role} onValueChange={(v) => setData('role', v)}>
                                    <SelectTrigger className={cn('w-full', errors.role ? 'border-destructive' : '')}>
                                        <SelectValue placeholder="Izaberite ulogu..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pilot">Pilot</SelectItem>
                                        <SelectItem value="cabin_crew">Kabinsko osoblje</SelectItem>
                                    </SelectContent>
                                </Select>
                            </Field>
                            <Field label="TIP UGOVORA" required error={errors.tip_ugovora_id}>
                                <Select
                                    value={data.tip_ugovora_id}
                                    onValueChange={(v) => setData('tip_ugovora_id', v)}
                                >
                                    <SelectTrigger className={cn('w-full', errors.tip_ugovora_id ? 'border-destructive' : '')}>
                                        <SelectValue placeholder="Izaberite tip..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {tipoviUgovora.map((tip) => (
                                            <SelectItem key={tip.id} value={String(tip.id)}>
                                                {tip.naziv}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <Field label="DATUM POČETKA RADA" required error={errors.datum_zaposlenja}>
                                <Input
                                    type="date"
                                    value={data.datum_zaposlenja}
                                    onChange={(e) => setData('datum_zaposlenja', e.target.value)}
                                    className={errors.datum_zaposlenja ? 'border-destructive' : ''}
                                />
                            </Field>
                            <Field label="DATUM ISTEKA UGOVORA" error={errors.datum_isteka}>
                                <Input
                                    type="date"
                                    value={data.datum_isteka}
                                    onChange={(e) => setData('datum_isteka', e.target.value)}
                                    className={errors.datum_isteka ? 'border-destructive' : ''}
                                />
                            </Field>
                        </div>

                        <Field label="NAPOMENA" error={errors.napomena}>
                            <textarea
                                rows={4}
                                value={data.napomena}
                                onChange={(e) => setData('napomena', e.target.value)}
                                placeholder="Dodatne napomene o zaposlenom ili ugovoru..."
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                        </Field>
                    </div>
                </div>
            </form>

            {/* Sticky footer */}
            <div className="fixed bottom-0 left-0 right-0 border-t border-border bg-background">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                    <span className="text-sm text-muted-foreground">
                        Korak 1 od 3 &bull; <span className="text-foreground">*Obavezna polja</span>
                    </span>
                    <div className="flex items-center gap-3">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/admin/employee">Otkaži</Link>
                        </Button>
                        <Button
                            type="submit"
                            form="edit-form"
                            disabled={processing}
                            className="bg-[#185FA5] hover:bg-[#0C447C]"
                        >
                            Sledeći korak →
                        </Button>
                        <Button
                            type="submit"
                            form="edit-form"
                            disabled={processing}
                            className="bg-emerald-700 hover:bg-emerald-800 text-white"
                        >
                            ✓ Sačuvaj promene
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
