import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import AuthSkyairLayout from '@/layouts/auth/auth-skyair-layout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function KupacLogin({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Prijava korisnika" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email adresa</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                placeholder="marko@email.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="password">Lozinka</Label>
                                {canResetPassword && (
                                    <a
                                        href={request().url}
                                        className="text-xs text-[#185FA5] hover:underline"
                                        tabIndex={5}
                                    >
                                        Zaboravljena lozinka?
                                    </a>
                                )}
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder="Vaša lozinka"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center gap-2">
                            <Checkbox id="remember" name="remember" tabIndex={3} />
                            <Label htmlFor="remember" className="text-sm font-normal">
                                Zapamti me
                            </Label>
                        </div>

                        <Button
                            type="submit"
                            className="mt-2 w-full bg-[#185FA5] hover:bg-[#134d86]"
                            tabIndex={4}
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            Prijavi se
                        </Button>

                        {status && (
                            <div className="text-center text-sm font-medium text-green-600">
                                {status}
                            </div>
                        )}
                    </>
                )}
            </Form>
        </>
    );
}

KupacLogin.layout = (page: React.ReactNode) => (
    <AuthSkyairLayout>{page}</AuthSkyairLayout>
);
