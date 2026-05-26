import { Head, Link } from '@inertiajs/react';
import KupacHeader from '@/components/kupac-header';

interface TierInfo {
    name: string;
    threshold: number;
    state: 'done' | 'active' | 'inactive';
}

interface Summary {
    status_points: number;
    reward_points: number;
    reward_spent: number;
    expired: number;
    tier: string;
    next_tier: string | null;
    points_to_next: number;
    progress_pct: number;
}

interface Props {
    summary: Summary;
    tiers: TierInfo[];
}

const TIER_COLORS: Record<string, { ring: string; bg: string; text: string; icon: string }> = {
    Silver:   { ring: 'ring-[#A0A0A0]', bg: 'bg-[#F0F0F0]', text: 'text-[#555]',    icon: '🥈' },
    Gold:     { ring: 'ring-[#D4A017]', bg: 'bg-[#FFF8E1]', text: 'text-[#8B6914]', icon: '🥇' },
    Platinum: { ring: 'ring-[#5B6770]', bg: 'bg-[#EAEEF1]', text: 'text-[#3A444C]', icon: '💎' },
};

function fmt(n: number) {
    return n.toLocaleString('sr-RS');
}

export default function Loyalty({ summary, tiers }: Props) {
    const currentTierLabel = summary.tier.charAt(0).toUpperCase() + summary.tier.slice(1);
    const tc = TIER_COLORS[currentTierLabel] ?? TIER_COLORS.Silver;

    return (
        <>
            <Head title="Loyalty program" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <KupacHeader active="loyalty" />

                <div className="bg-muted p-6">
                    <div className="mx-auto max-w-4xl space-y-4">

                        {/* Hero card */}
                        <div className={`rounded-xl border bg-card p-6 ${tc.ring} ring-2`}>
                            <div className="flex items-center justify-between">
                                <div>
                                    <div className="text-xs font-medium uppercase tracking-wider text-muted-foreground">Tvoj nivo</div>
                                    <div className="mt-1 text-2xl font-bold">
                                        <span className="mr-2">{tc.icon}</span>
                                        {currentTierLabel}
                                    </div>
                                </div>
                                <div className="text-right">
                                    <div className="text-xs text-muted-foreground">Status poeni</div>
                                    <div className="text-2xl font-bold">{fmt(summary.status_points)}</div>
                                </div>
                            </div>

                            {summary.next_tier && (
                                <div className="mt-5">
                                    <div className="mb-1.5 flex justify-between text-xs text-muted-foreground">
                                        <span>Napredak do {summary.next_tier.charAt(0).toUpperCase() + summary.next_tier.slice(1)}</span>
                                        <span>{summary.progress_pct}%</span>
                                    </div>
                                    <div className="h-2.5 w-full rounded-full bg-muted">
                                        <div
                                            className="h-2.5 rounded-full bg-[#185FA5] transition-all"
                                            style={{ width: `${summary.progress_pct}%` }}
                                        />
                                    </div>
                                    <div className="mt-1 text-xs text-muted-foreground">
                                        Još <strong>{fmt(summary.points_to_next)}</strong> poena do sledećeg nivoa
                                    </div>
                                </div>
                            )}

                            {!summary.next_tier && (
                                <div className="mt-4 text-sm text-muted-foreground">
                                    Dostigli ste najviši nivo! Uživajte u svim pogodnostima.
                                </div>
                            )}
                        </div>

                        {/* Points overview */}
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div className="rounded-xl border border-border bg-card p-4 text-center">
                                <div className="text-xs text-muted-foreground">Status poeni</div>
                                <div className="mt-1 text-xl font-bold">{fmt(summary.status_points)}</div>
                            </div>
                            <div className="rounded-xl border border-border bg-card p-4 text-center">
                                <div className="text-xs text-muted-foreground">Reward poeni</div>
                                <div className="mt-1 text-xl font-bold text-[#185FA5]">{fmt(summary.reward_points)}</div>
                            </div>
                            <div className="rounded-xl border border-border bg-card p-4 text-center">
                                <div className="text-xs text-muted-foreground">Iskorišćeno</div>
                                <div className="mt-1 text-xl font-bold">{fmt(summary.reward_spent)}</div>
                            </div>
                            <div className="rounded-xl border border-border bg-card p-4 text-center">
                                <div className="text-xs text-muted-foreground">Isteklo</div>
                                <div className="mt-1 text-xl font-bold text-muted-foreground">{fmt(summary.expired)}</div>
                            </div>
                        </div>

                        {/* Tiers */}
                        <div className="rounded-xl border border-border bg-card p-5">
                            <div className="mb-4 flex items-center justify-between">
                                <h3 className="text-sm font-semibold">Nivoi programa</h3>
                                <Link href="/kupac/loyalty/istorija" className="text-xs font-medium text-[#185FA5] hover:underline">
                                    Istorija poena →
                                </Link>
                            </div>
                            <div className="grid grid-cols-3 gap-3">
                                {tiers.map((tier) => {
                                    const colors = TIER_COLORS[tier.name] ?? TIER_COLORS.Silver;
                                    const isActive = tier.state === 'active';
                                    const isDone = tier.state === 'done';

                                    return (
                                        <div
                                            key={tier.name}
                                            className={`rounded-lg border p-3.5 text-center transition ${
                                                isActive
                                                    ? `${colors.bg} border-current ${colors.text} ring-1 ${colors.ring}`
                                                    : isDone
                                                      ? 'border-border bg-muted/50'
                                                      : 'border-border bg-card opacity-50'
                                            }`}
                                        >
                                            <div className="text-lg">{colors.icon}</div>
                                            <div className="mt-1 text-sm font-semibold">{tier.name}</div>
                                            <div className="mt-0.5 text-[11px] text-muted-foreground">
                                                {tier.threshold === 0 ? 'Početni' : `${fmt(tier.threshold)} poena`}
                                            </div>
                                            {isActive && (
                                                <div className={`mt-2 text-[10px] font-medium ${colors.text}`}>Trenutni</div>
                                            )}
                                            {isDone && (
                                                <div className="mt-2 text-[10px] font-medium text-[#0F6E56]">✓ Dostignuto</div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Info */}
                        <div className="rounded-xl border border-border bg-card p-5">
                            <h3 className="mb-3 text-sm font-semibold">Kako funkcioniše?</h3>
                            <div className="grid grid-cols-1 gap-4 text-xs text-muted-foreground sm:grid-cols-3">
                                <div>
                                    <div className="mb-1 font-semibold text-foreground">Status poeni</div>
                                    <p>Zarađuješ ih svakom kupovinom karte. Formula: cena × faktor klase × 0.25. Status poeni određuju tvoj nivo u programu.</p>
                                </div>
                                <div>
                                    <div className="mb-1 font-semibold text-foreground">Reward poeni</div>
                                    <p>Zarađuješ ih svakom kupovinom. Formula: cena × faktor klase. Koristi ih za popuste na buduće karte!</p>
                                </div>
                                <div>
                                    <div className="mb-1 font-semibold text-foreground">Nivoi</div>
                                    <p>Silver (0), Gold (10.000), Platinum (20.000). Viši nivo = više pogodnosti.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </>
    );
}
