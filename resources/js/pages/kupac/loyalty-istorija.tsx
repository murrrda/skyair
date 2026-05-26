import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import KupacHeader from '@/components/kupac-header';

interface PointEntry {
    id: number;
    route_label: string;
    description: string;
    type: 'status' | 'reward';
    action: 'earned' | 'spent' | 'expired';
    amount: number;
    date: string;
    expires_at: string | null;
    reservation_code: string | null;
}

interface Props {
    points: PointEntry[];
    filters: {
        type?: string;
        action?: string;
    };
}

const TYPE_FILTERS: Record<string, string> = {
    all: 'Svi',
    status: 'Status',
    reward: 'Reward',
};

const ACTION_FILTERS: Record<string, string> = {
    all: 'Sve',
    earned: 'Zarađeno',
    spent: 'Iskorišćeno',
    expired: 'Isteklo',
};

const ACTION_BADGE: Record<string, { bg: string; text: string; sign: string }> = {
    earned:  { bg: 'bg-[#E1F5EE]', text: 'text-[#0F6E56]', sign: '+' },
    spent:   { bg: 'bg-[#E6F1FB]', text: 'text-[#185FA5]', sign: '−' },
    expired: { bg: 'bg-[#FCEBEB]', text: 'text-[#A32D2D]', sign: '−' },
};

function fmt(n: number) {
    return n.toLocaleString('sr-RS');
}

export default function LoyaltyIstorija({ points, filters }: Props) {
    const [type, setType] = useState(filters.type ?? 'all');
    const [action, setAction] = useState(filters.action ?? 'all');

    function applyFilters(newType: string, newAction: string) {
        setType(newType);
        setAction(newAction);
        router.get('/kupac/loyalty/istorija', {
            ...(newType !== 'all' ? { type: newType } : {}),
            ...(newAction !== 'all' ? { action: newAction } : {}),
        }, { preserveState: true, replace: true });
    }

    return (
        <>
            <Head title="Istorija poena" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <KupacHeader active="loyalty" />

                <div className="border-b border-border/60 bg-background px-6 py-3 text-xs text-muted-foreground">
                    <Link href="/kupac/loyalty" className="hover:text-foreground">← Nazad na Loyalty</Link>
                </div>

                <div className="bg-muted p-6">
                    <div className="mx-auto max-w-4xl">
                        <h2 className="mb-4 text-lg font-semibold">Istorija poena</h2>

                        {/* Filters */}
                        <div className="mb-4 flex flex-wrap items-center gap-4">
                            <div className="flex items-center gap-1.5">
                                <span className="text-xs text-muted-foreground">Tip:</span>
                                {Object.entries(TYPE_FILTERS).map(([value, label]) => (
                                    <button
                                        key={value}
                                        onClick={() => applyFilters(value, action)}
                                        className={`rounded-full border px-3 py-1 text-xs font-medium transition ${
                                            type === value
                                                ? 'border-[#185FA5] bg-[#185FA5] text-white'
                                                : 'border-border bg-card text-foreground hover:bg-muted'
                                        }`}
                                    >
                                        {label}
                                    </button>
                                ))}
                            </div>
                            <div className="flex items-center gap-1.5">
                                <span className="text-xs text-muted-foreground">Akcija:</span>
                                {Object.entries(ACTION_FILTERS).map(([value, label]) => (
                                    <button
                                        key={value}
                                        onClick={() => applyFilters(type, value)}
                                        className={`rounded-full border px-3 py-1 text-xs font-medium transition ${
                                            action === value
                                                ? 'border-[#185FA5] bg-[#185FA5] text-white'
                                                : 'border-border bg-card text-foreground hover:bg-muted'
                                        }`}
                                    >
                                        {label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* List */}
                        {points.length === 0 ? (
                            <div className="py-16 text-center text-muted-foreground">
                                <p className="text-lg font-medium">Nema poena za prikaz</p>
                                <p className="mt-1 text-sm">Rezerviši let da počneš zarađivati poene</p>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {points.map((p) => {
                                    const badge = ACTION_BADGE[p.action] ?? ACTION_BADGE.earned;

                                    return (
                                        <div
                                            key={p.id}
                                            className="grid grid-cols-[1fr_auto] items-center gap-4 rounded-xl border border-border bg-card p-4"
                                        >
                                            <div>
                                                <div className="text-[15px] font-semibold">{p.route_label}</div>
                                                <div className="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                    <span>{p.date}</span>
                                                    {p.reservation_code && (
                                                        <>
                                                            <span>·</span>
                                                            <span>{p.reservation_code}</span>
                                                        </>
                                                    )}
                                                    {p.expires_at && (
                                                        <>
                                                            <span>·</span>
                                                            <span>Ističe: {p.expires_at}</span>
                                                        </>
                                                    )}
                                                </div>
                                                <div className="mt-2 flex items-center gap-2">
                                                    <span className={`rounded-full px-2.5 py-0.5 text-[11px] font-medium ${
                                                        p.type === 'status' ? 'bg-[#FAEEDA] text-[#854F0B]' : 'bg-[#E6F1FB] text-[#185FA5]'
                                                    }`}>
                                                        {p.type === 'status' ? 'Status' : 'Reward'}
                                                    </span>
                                                    <span className={`rounded-full px-2.5 py-0.5 text-[11px] font-medium ${badge.bg} ${badge.text}`}>
                                                        {p.action === 'earned' ? 'Zarađeno' : p.action === 'spent' ? 'Iskorišćeno' : 'Isteklo'}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className={`text-lg font-semibold ${badge.text}`}>
                                                {badge.sign}{fmt(p.amount)}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
