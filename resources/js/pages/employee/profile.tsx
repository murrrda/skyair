import { Head, useForm } from '@inertiajs/react';
import { User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    user: {
        first_name: string;
        last_name: string;
        email: string;
        phone_number: string | null;
        address: string | null;
        country: string | null;
    };
};

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

export default function Profile({ user }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        first_name:   user.first_name,
        last_name:    user.last_name,
        email:        user.email,
        phone_number: user.phone_number ?? '',
        address:      user.address ?? '',
        country:      user.country ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put('/employee/profile');
    }

    return (
        <>
            <Head title="Moj profil" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">Moj profil</h1>
                <p className="mt-1 text-sm text-muted-foreground">Ažuriraj svoje lične podatke.</p>
            </div>

            <form onSubmit={handleSubmit} className="max-w-2xl">
                <div className="rounded-lg border border-border bg-card p-6">
                    <div className="mb-5 flex items-center gap-2.5">
                        <User className="size-4 text-muted-foreground" />
                        <h2 className="font-semibold">Lični podaci</h2>
                    </div>

                    <div className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
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
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <Field label="E-MAIL" required error={errors.email}>
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

                        <Field label="DRŽAVA" error={errors.country}>
                            <Input
                                value={data.country}
                                onChange={(e) => setData('country', e.target.value)}
                                placeholder="npr. Srbija"
                                className={errors.country ? 'border-destructive' : ''}
                            />
                        </Field>
                    </div>
                </div>

                <div className="mt-4 flex justify-end">
                    <Button
                        type="submit"
                        disabled={processing}
                        className="bg-emerald-700 hover:bg-emerald-800 text-white"
                    >
                        ✓ Sačuvaj promene
                    </Button>
                </div>
            </form>
        </>
    );
}
