import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthSkyairLayout from '@/layouts/auth/auth-skyair-layout';

type Props = {
    status?: string;
};

export default function EmployeeLogin({ status }: Props) {
    return (
        <>
            <Head title="Prijava zaposlenih" />

            <Form
                action="/employee/login"
                method="post"
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
                                placeholder="pilot@skyair.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Lozinka</Label>
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
                            <Checkbox
                                id="remember"
                                name="remember"
                                tabIndex={3}
                            />
                            <Label
                                htmlFor="remember"
                                className="text-sm font-normal"
                            >
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
                            Prijavi se kao zaposleni
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

EmployeeLogin.layout = (page: React.ReactNode) => (
    <AuthSkyairLayout hideTabs>{page}</AuthSkyairLayout>
);
