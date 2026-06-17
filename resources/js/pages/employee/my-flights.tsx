import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import type { Auth } from '@/types';

type Flight = {
    id: number;
    route: string;
    date: string;
    aircraft: string;
    registration: string;
    role: string;
    status: 'confirmed' | 'changed' | 'cancelled' | 'completed';
    notice?: string | null;
};

const STATUS_CONFIG = {
    confirmed: { label: 'Potvrđeno', className: 'bg-emerald-50 text-emerald-700 border border-emerald-200' },
    changed:   { label: 'Ruta izmenjena', className: 'bg-amber-50 text-amber-700 border border-amber-200' },
    cancelled: { label: 'Otkazano', className: 'bg-red-50 text-red-700 border border-red-200' },
    completed: { label: 'Završeno', className: 'bg-muted text-muted-foreground border border-border' },
};

const TABS = [
    { key: 'all',       label: 'Svi' },
    { key: 'upcoming',  label: 'Nadolazeći' },
    { key: 'completed', label: 'Završeni' },
    { key: 'cancelled', label: 'Otkazani' },
] as const;

type Tab = typeof TABS[number]['key'];

function getGreeting(): string {
    const h = new Date().getHours();

    if (h < 12) {
return 'Dobro jutro';
}

    if (h < 18) {
return 'Dobar dan';
}

    return 'Dobro veče';
}

function filterFlights(flights: Flight[], tab: Tab): Flight[] {
    if (tab === 'all') {
return flights;
}

    if (tab === 'upcoming') {
return flights.filter((f) => f.status === 'confirmed' || f.status === 'changed');
}

    if (tab === 'completed') {
return flights.filter((f) => f.status === 'completed');
}

    if (tab === 'cancelled') {
return flights.filter((f) => f.status === 'cancelled');
}

    return flights;
}

export default function MyFlights({ flights = [] }: { flights?: Flight[] }) {
    const { auth } = usePage().props as unknown as { auth: Auth };
    const firstName = auth?.user?.name?.split(' ')[0] ?? 'kolega';
    const [activeTab, setActiveTab] = useState<Tab>('all');

    const filtered = filterFlights(flights, activeTab);

    return (
        <>
            <Head title="Moji letovi" />

            <div className="mb-8">
                <h1 className="text-2xl font-bold">
                    {getGreeting()}, {firstName} 👋
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">Pregled tvojih nadolazećih letova</p>
            </div>

            {/* Tabs */}
            <div className="mb-6 flex gap-2">
                {TABS.map((tab) => (
                    <button
                        key={tab.key}
                        onClick={() => setActiveTab(tab.key)}
                        className={cn(
                            'rounded-full border px-4 py-1.5 text-sm transition-colors',
                            activeTab === tab.key
                                ? 'border-foreground bg-foreground text-background'
                                : 'border-border bg-background text-foreground hover:bg-muted',
                        )}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {/* Flight cards */}
            <div className="space-y-3">
                {filtered.map((flight) => {
                    const isCancelled = flight.status === 'cancelled';
                    const isChanged = flight.status === 'changed';
                    const statusCfg = STATUS_CONFIG[flight.status];

                    return (
                        <div
                            key={flight.id}
                            className={cn(
                                'rounded-lg border bg-card p-5',
                                isChanged ? 'border-amber-300 bg-amber-50/30' : 'border-border',
                            )}
                        >
                            <p className={cn('mb-1 font-semibold', isCancelled && 'line-through text-muted-foreground')}>
                                {flight.route}
                            </p>
                            <p className={cn('mb-3 text-sm text-muted-foreground', isCancelled && 'line-through')}>
                                {flight.date} · {flight.aircraft} · reg. {flight.registration}
                            </p>
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium">
                                    {flight.role}
                                </span>
                                <span className={cn('rounded-full px-2.5 py-0.5 text-xs font-medium', statusCfg.className)}>
                                    {statusCfg.label}
                                </span>
                                {flight.notice && (
                                    <span className="flex items-center gap-1 text-xs text-[#185FA5]">
                                        <span className="h-1.5 w-1.5 rounded-full bg-[#185FA5]" />
                                        {flight.notice}
                                    </span>
                                )}
                            </div>
                        </div>
                    );
                })}

                {filtered.length === 0 && (
                    <p className="py-12 text-center text-muted-foreground">Nema letova za prikaz.</p>
                )}
            </div>
        </>
    );
}
