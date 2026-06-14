import { Link, usePage } from '@inertiajs/react';
import { useState, useRef, useEffect } from 'react';
import { toast } from 'sonner';
import AppLogoIcon from '@/components/app-logo-icon';
import { NotificationBell } from '@/components/notification-bell';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { Auth } from '@/types';

const NAV_LINKS = [
    { label: 'Moji letovi', href: '/employee/my-flights' },
    { label: 'Moji sertifikati', href: '/employee/certificates' },
];

export default function EmployeeLayout({ children }: { children: React.ReactNode }) {
    const page = usePage();
    const { auth, flash } = page.props as unknown as { auth: Auth; flash?: { success?: string } };
    const currentUrl = page.url;
    const getInitials = useInitials();
    const [dropdownOpen, setDropdownOpen] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (flash?.success) {
toast.success(flash.success, { duration: 2000 });
}
    }, [flash?.success]);

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
                setDropdownOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);

        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const fullName = auth?.user?.name ?? '';
    const initials = auth?.user ? getInitials(fullName) : '?';

    return (
        <div className="min-h-screen bg-background text-foreground">
            <header className="border-b border-border/60">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-3">
                    {/* Logo */}
                    <Link href="/employee/my-flights" className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center overflow-hidden rounded-md bg-white">
                            <AppLogoIcon className="h-full w-full object-contain p-0.5" />
                        </div>
                        <span className="text-[15px] font-semibold">SkyAir</span>
                    </Link>

                    {/* Right side: nav + bell + avatar */}
                    <div className="flex items-center gap-7">
                        {NAV_LINKS.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className={cn(
                                    'text-sm transition-colors',
                                    currentUrl.startsWith(link.href)
                                        ? 'font-semibold text-foreground'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {link.label}
                            </Link>
                        ))}

                        <NotificationBell />

                        <div className="relative" ref={dropdownRef}>
                            <button
                                onClick={() => setDropdownOpen((o) => !o)}
                                className="flex h-8 w-8 items-center justify-center rounded-full bg-[#E6F1FB] text-xs font-semibold text-[#185FA5] transition-colors hover:bg-[#cfe3f5]"
                            >
                                {initials}
                            </button>

                            {dropdownOpen && (
                                <div className="absolute right-0 top-10 z-50 w-52 rounded-lg border border-border bg-background shadow-md">
                                    <div className="border-b border-border px-4 py-3">
                                        <p className="text-sm font-semibold">{fullName}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {(auth?.user as { email?: string })?.email ?? ''}
                                        </p>
                                    </div>
                                    <div className="p-1">
                                        <Link
                                            href="/employee/profile"
                                            onClick={() => setDropdownOpen(false)}
                                            className="flex w-full items-center rounded-md px-3 py-2 text-sm hover:bg-muted"
                                        >
                                            Izmeni profil
                                        </Link>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </header>

            <div className="mx-auto max-w-5xl px-6 py-8">{children}</div>
        </div>
    );
}
