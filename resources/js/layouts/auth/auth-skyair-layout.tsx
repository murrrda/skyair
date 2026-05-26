import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import type { AuthLayoutProps } from '@/types';

export default function AuthSkyairLayout({ children, hideTabs = false }: AuthLayoutProps & { hideTabs?: boolean }) {
    const { url } = usePage();
    const isLogin = url.startsWith('/kupac/login') || url.startsWith('/login') || url.startsWith('/admin/login');

    return (
        <div className="grid min-h-svh lg:grid-cols-2">
            {/* Left — blue marketing panel */}
            <div className="hidden flex-col justify-center bg-[#185FA5] p-10 text-white lg:flex">
                <Link href="/" className="mb-8 flex items-center gap-2 font-semibold">
                    <div className="flex h-8 w-8 items-center justify-center overflow-hidden rounded-md bg-white">
                        <AppLogoIcon className="h-full w-full object-contain p-0.5" />
                    </div>
                    SkyAir
                </Link>

                <h2 className="mb-3 text-3xl font-semibold leading-tight">
                    Dobrodošli u SkyAir
                </h2>
                <p className="max-w-md text-sm leading-relaxed opacity-80">
                    Prijavite se ili registrujte da biste rezervisali letove,
                    pratili status karata i koristili loyalty program sa
                    statusnim i reward poenima.
                </p>

                <ul className="mt-8 space-y-2.5 text-sm opacity-90">
                    <li>✓ Rezervišite letove za minut</li>
                    <li>✓ Pratite status rezervacija</li>
                    <li>✓ Zarađujte bonus poene</li>
                    <li>✓ Koristite reward poene za popuste</li>
                </ul>
            </div>

            {/* Right — form */}
            <div className="flex flex-col justify-center p-6 sm:p-10">
                <div className="mx-auto w-full max-w-md">
                    <Link
                        href="/"
                        className="mb-6 flex items-center justify-center gap-2 font-semibold lg:hidden"
                    >
                        <div className="flex h-8 w-8 items-center justify-center overflow-hidden rounded-md bg-white">
                            <AppLogoIcon className="h-full w-full object-contain p-0.5" />
                        </div>
                        SkyAir
                    </Link>

                    {!hideTabs && (
                        <div className="mb-6 flex overflow-hidden rounded-lg border border-border">
                            <Link
                                href="/kupac/login"
                                className={
                                    'flex-1 py-2.5 text-center text-sm font-medium transition ' +
                                    (isLogin
                                        ? 'bg-[#185FA5] text-white'
                                        : 'bg-background text-muted-foreground hover:bg-muted')
                                }
                            >
                                Prijava
                            </Link>
                            <Link
                                href="/kupac/register"
                                className={
                                    'flex-1 py-2.5 text-center text-sm font-medium transition ' +
                                    (!isLogin
                                        ? 'bg-[#185FA5] text-white'
                                        : 'bg-background text-muted-foreground hover:bg-muted')
                                }
                            >
                                Registracija
                            </Link>
                        </div>
                    )}

                    {children}

                    <div className="mt-6 text-center">
                        <Link
                            href="/kupac/pretraga-letova"
                            className="text-sm text-muted-foreground transition hover:text-foreground"
                        >
                            Nastavi kao gost →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
