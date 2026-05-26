import { Form, Head, router, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import InputError from '@/components/input-error';
import KupacHeader from '@/components/kupac-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import type { User } from '@/types';

type Props = {
    putnik: {
        tier: string;
        status_points: number;
        reward_points: number;
    } | null;
};

const tierLabels: Record<string, string> = {
    silver: 'Silver',
    gold: 'Gold',
    platinum: 'Platinum',
};

function getInitials(user: User) {
    return (
        (user.first_name?.charAt(0) ?? '') + (user.last_name?.charAt(0) ?? '')
    ).toUpperCase();
}

export default function EditProfile({ putnik }: Props) {
    const { auth } = usePage().props;
    const user = auth.user;

    return (
        <>
            <Head title="Profil — Lični podaci" />

            <div className="flex min-h-[500px]">
                <aside className="w-[220px] shrink-0 border-r border-border bg-muted/40 p-5">
                    <nav className="flex flex-col gap-1 text-[13px] font-medium">
                        <span className="rounded-lg bg-[#E6F1FB] px-3 py-2 text-[#185FA5]">
                            Lični podaci
                        </span>

                        <Separator className="my-3" />

                        <button
                            type="button"
                            onClick={() =>
                                router.post('/logout', undefined, {
                                    preserveScroll: true,
                                })
                            }
                            className="flex items-center gap-2 rounded-lg px-3 py-2 text-left text-destructive hover:bg-destructive/10"
                        >
                            <LogOut className="h-4 w-4" />
                            Odjavi se
                        </button>
                    </nav>
                </aside>

                <div className="flex-1 p-6">
                    <div className="mb-6 flex items-center gap-4 border-b border-border pb-5">
                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-[#E6F1FB] text-[22px] font-semibold text-[#185FA5]">
                            {getInitials(user)}
                        </div>
                        <div>
                            <div className="text-lg font-semibold">
                                {user.first_name} {user.last_name}
                            </div>
                            <div className="text-[13px] text-muted-foreground">
                                {user.email}
                            </div>
                            {putnik && (
                                <span className="mt-1.5 inline-block rounded-full bg-[#E6F1FB] px-2.5 py-0.5 text-[11px] font-medium text-[#185FA5]">
                                    {tierLabels[putnik.tier] ?? putnik.tier} tier
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="max-w-xl rounded-xl border border-border p-5">
                        <h3 className="mb-1 text-sm font-semibold">
                            Lični podaci
                        </h3>
                        <p className="mb-4 text-xs text-muted-foreground">
                            Ažurirajte svoje lične podatke i email adresu
                        </p>

                        <Form
                            action="/kupac/edit-profile"
                            method="patch"
                            options={{ preserveScroll: true }}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid grid-cols-2 gap-3">
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="first_name">
                                                Ime
                                            </Label>
                                            <Input
                                                id="first_name"
                                                name="first_name"
                                                defaultValue={
                                                    user.first_name ?? ''
                                                }
                                                required
                                                autoComplete="given-name"
                                                placeholder="Marko"
                                            />
                                            <InputError
                                                message={errors.first_name}
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="last_name">
                                                Prezime
                                            </Label>
                                            <Input
                                                id="last_name"
                                                name="last_name"
                                                defaultValue={
                                                    user.last_name ?? ''
                                                }
                                                required
                                                autoComplete="family-name"
                                                placeholder="Marković"
                                            />
                                            <InputError
                                                message={errors.last_name}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-3">
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="date_of_birth">
                                                Datum rođenja
                                            </Label>
                                            <Input
                                                id="date_of_birth"
                                                name="date_of_birth"
                                                type="date"
                                                defaultValue={
                                                    user.date_of_birth ?? ''
                                                }
                                                autoComplete="bday"
                                            />
                                            <InputError
                                                message={errors.date_of_birth}
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="address">
                                                Mesto stanovanja
                                            </Label>
                                            <Input
                                                id="address"
                                                name="address"
                                                defaultValue={
                                                    user.address ?? ''
                                                }
                                                autoComplete="address-level2"
                                                placeholder="Beograd"
                                            />
                                            <InputError
                                                message={errors.address}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="email">
                                            Email adresa
                                        </Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            defaultValue={user.email}
                                            required
                                            autoComplete="username"
                                            placeholder="marko@email.com"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="phone_number">
                                            Broj telefona
                                        </Label>
                                        <Input
                                            id="phone_number"
                                            name="phone_number"
                                            type="tel"
                                            defaultValue={
                                                user.phone_number ?? ''
                                            }
                                            autoComplete="tel"
                                            placeholder="+381 63 123 456"
                                        />
                                        <InputError
                                            message={errors.phone_number}
                                        />
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-[#185FA5] hover:bg-[#134d86]"
                                    >
                                        Sačuvaj izmene
                                    </Button>
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </div>
        </>
    );
}

EditProfile.layout = (page: React.ReactNode) => (
    <>
        <KupacHeader />
        {page}
    </>
);
