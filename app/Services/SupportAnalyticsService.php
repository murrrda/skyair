<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SupportAnalyticsService
{
    public function analytics(Carbon $from, Carbon $to): array
    {
        $outcomeSummary = $this->outcomeSummary($from, $to);
        $previousOutcomeSummary = $this->outcomeSummary(
            $from->copy()->subDays($to->diffInDays($from) + 1),
            $from->copy()->subDay()->endOfDay(),
        );

        return [
            'total_tickets' => $this->totalTickets($from, $to),
            'open_tickets' => $this->openTickets($from, $to),
            'avg_resolution_minutes' => $this->avgResolutionMinutes($from, $to),
            'tickets_by_category' => $this->ticketsByCategory($from, $to),
            'resolution_time_by_category' => $this->resolutionTimeByCategory($from, $to),
            'outcome_summary' => $outcomeSummary,
            'previous_period_outcome_summary' => $previousOutcomeSummary,
            'top_flights_by_issues' => $this->topFlightsByIssues($from, $to),
            'daily_counts' => $this->dailyCounts($from, $to),
        ];
    }

    private function avgResolutionMinutes(Carbon $from, Carbon $to): ?float
    {
        $result = DB::selectOne(
            <<<'SQL'
            SELECT ROUND(CAST(
                AVG(EXTRACT(EPOCH FROM (closed_at - created_at)) / 60)
            AS NUMERIC), 2) AS avg_minutes
            FROM support_ticket
            WHERE created_at BETWEEN ? AND ?
              AND status = 'closed'
              AND closed_at IS NOT NULL
            SQL,
            [$from, $to],
        );

        return $result?->avg_minutes !== null ? (float) $result->avg_minutes : null;
    }

    private function totalTickets(Carbon $from, Carbon $to): int
    {
        return (int) DB::table('support_ticket')
            ->whereBetween('created_at', [$from, $to])
            ->where('status', '!=', 'draft')
            ->count();
    }

    private function openTickets(Carbon $from, Carbon $to): int
    {
        return (int) DB::table('support_ticket')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['open', 'in_progress', 'requires_info', 'transferred'])
            ->count();
    }

    private function ticketsByCategory(Carbon $from, Carbon $to): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT
                c.id   AS category_id,
                c.name AS category_name,
                COUNT(st.id) AS count
            FROM category c
            LEFT JOIN support_ticket st
                ON  st.category_id = c.id
                AND st.created_at BETWEEN ? AND ?
                AND st.status <> 'draft'
            GROUP BY c.id, c.name
            ORDER BY count DESC
            SQL,
            [$from, $to],
        );

        $total = array_sum(array_column($rows, 'count'));

        return array_map(fn ($row) => [
            'category_id' => $row->category_id,
            'category_name' => $row->category_name,
            'count' => (int) $row->count,
            'percentage' => $total > 0 ? round($row->count / $total * 100, 2) : 0.00,
        ], $rows);
    }

    private function resolutionTimeByCategory(Carbon $from, Carbon $to): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT
                c.id   AS category_id,
                c.name AS category_name,
                ROUND(CAST(
                    AVG(EXTRACT(EPOCH FROM (st.closed_at - st.created_at)) / 60)
                AS NUMERIC), 2) AS avg_minutes,
                ROUND(CAST(
                    MIN(EXTRACT(EPOCH FROM (st.closed_at - st.created_at)) / 60)
                AS NUMERIC), 2) AS min_minutes,
                ROUND(CAST(
                    MAX(EXTRACT(EPOCH FROM (st.closed_at - st.created_at)) / 60)
                AS NUMERIC), 2) AS max_minutes
            FROM support_ticket st
            JOIN category c ON c.id = st.category_id
            WHERE st.created_at BETWEEN ? AND ?
              AND st.status = 'closed'
              AND st.closed_at IS NOT NULL
            GROUP BY c.id, c.name
            ORDER BY avg_minutes DESC
            SQL,
            [$from, $to],
        );

        return array_map(fn ($row) => [
            'category_id' => $row->category_id,
            'category_name' => $row->category_name,
            'avg_minutes' => (float) $row->avg_minutes,
            'min_minutes' => (float) $row->min_minutes,
            'max_minutes' => (float) $row->max_minutes,
        ], $rows);
    }

    private function outcomeSummary(Carbon $from, Carbon $to): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT outcome, COUNT(*) AS count
            FROM support_ticket
            WHERE created_at BETWEEN ? AND ?
              AND status = 'closed'
            GROUP BY outcome
            SQL,
            [$from, $to],
        );

        $map = [];
        foreach ($rows as $row) {
            $map[$row->outcome] = (int) $row->count;
        }

        $successCount = $map['success'] ?? 0;
        $partialCount = $map['partial'] ?? 0;
        $failCount = $map['fail'] ?? 0;
        $total = $successCount + $partialCount + $failCount;

        return [
            'success_count' => $successCount,
            'partial_count' => $partialCount,
            'fail_count' => $failCount,
            'success_pct' => $total > 0 ? round($successCount / $total * 100, 2) : 0.00,
            'partial_pct' => $total > 0 ? round($partialCount / $total * 100, 2) : 0.00,
            'fail_pct' => $total > 0 ? round($failCount / $total * 100, 2) : 0.00,
        ];
    }

    private function topFlightsByIssues(Carbon $from, Carbon $to): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT
                f.id     AS flight_id,
                f.number AS flight_number,
                r.name   AS route_name,
                COUNT(DISTINCT st.id) AS total,
                COUNT(CASE WHEN st.outcome = 'success' THEN 1 END) AS success,
                COUNT(CASE WHEN st.outcome = 'partial'  THEN 1 END) AS partial,
                COUNT(CASE WHEN st.outcome = 'fail'     THEN 1 END) AS fail
            FROM flights f
            LEFT JOIN routes r ON r.id = f.route_id
            JOIN support_ticket_field_value stfv
                ON  stfv.value ~ '^[0-9]+$'
                AND stfv.value::bigint = f.id
            JOIN category_field cf
                ON  cf.id = stfv.category_field_id
                AND cf.reference_table = 'flights'
            JOIN support_ticket st
                ON  st.id = stfv.support_ticket_id
                AND st.created_at BETWEEN ? AND ?
                AND st.status <> 'draft'
            GROUP BY f.id, f.number, r.name
            ORDER BY total DESC
            LIMIT 3
            SQL,
            [$from, $to],
        );

        return array_map(fn ($row) => [
            'flight_id' => $row->flight_id,
            'flight_number' => $row->flight_number,
            'route_name' => $row->route_name,
            'total' => (int) $row->total,
            'success' => (int) $row->success,
            'partial' => (int) $row->partial,
            'fail' => (int) $row->fail,
        ], $rows);
    }

    private function dailyCounts(Carbon $from, Carbon $to): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT
                gs.d::date AS date,
                COALESCE(c.count, 0) AS count
            FROM generate_series(?::date, ?::date, '1 day'::interval) AS gs(d)
            LEFT JOIN (
                SELECT created_at::date AS day, COUNT(*) AS count
                FROM support_ticket
                WHERE created_at BETWEEN ? AND ?
                  AND status <> 'draft'
                GROUP BY created_at::date
            ) c ON c.day = gs.d::date
            ORDER BY gs.d
            SQL,
            [$from->toDateString(), $to->toDateString(), $from, $to],
        );

        return array_map(fn ($row) => [
            'date' => $row->date,
            'count' => (int) $row->count,
        ], $rows);
    }
}
