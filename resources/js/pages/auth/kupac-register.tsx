import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import AuthSkyairLayout from '@/layouts/auth/auth-skyair-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
};

export default function KupacRegister({ passwordRules }: Props) {
    return (
        <>
            <Head title="Registracija korisnika" />

            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="first_name">Ime</Label>
                                <Input
                                    id="first_name"
                                    name="first_name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="given-name"
                                    placeholder="Marko"
                                />
                                <InputError message={errors.first_name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="last_name">Prezime</Label>
                                <Input
                                    id="last_name"
                                    name="last_name"
                                    type="text"
                                    required
                                    tabIndex={2}
                                    autoComplete="family-name"
                                    placeholder="Marković"
                                />
                                <InputError message={errors.last_name} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email adresa</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                required
                                tabIndex={3}
                                autoComplete="email"
                                placeholder="marko@email.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Lozinka</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                tabIndex={4}
                                autoComplete="new-password"
                                placeholder="Minimum 8 karaktera"
                                passwordrules={passwordRules}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Potvrda lozinke</Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                tabIndex={5}
                                autoComplete="new-password"
                                placeholder="Ponovite lozinku"
                                passwordrules={passwordRules}
                            />
                            <InputError message={errors.password_confirmation} />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="date_of_birth">Datum rođenja</Label>
                                <Input
                                    id="date_of_birth"
                                    name="date_of_birth"
                                    type="text"
                                    tabIndex={6}
                                    placeholder="DD.MM.GGGG."
                                />
                                <InputError message={errors.date_of_birth} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="place_of_residence">Mesto stanovanja</Label>
                                <Input
                                    id="place_of_residence"
                                    name="place_of_residence"
                                    type="text"
                                    tabIndex={7}
                                    placeholder="Beograd"
                                />
                                <InputError message={errors.place_of_residence} />
                            </div>
                        </div>

                        <Button
                            type="submit"
                            className="mt-2 w-full bg-[#185FA5] hover:bg-[#134d86]"
                            tabIndex={8}
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            Registruj se
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

KupacRegister.layout = (page: React.ReactNode) => (
    <AuthSkyairLayout>{page}</AuthSkyairLayout>
);
