<?php

namespace App\Services;

use App\Models\BookingAttempt;
use App\Models\BookingRule;
use App\Models\BookingRun;
use App\Models\BookingSlotBooking;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class MotibroBookingService
{
    public function isRunning(): bool
    {
        return BookingRun::query()->where('status', 'running')->exists();
    }

    public function run(bool $manual = false, bool $dryRun = false): BookingRun
    {
        if ($this->isRunning()) {
            throw new \RuntimeException('Már fut egy foglalás. Várj, amíg befejeződik.');
        }

        $rules = BookingRule::query()
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->get();

        if ($rules->isEmpty()) {
            throw new \RuntimeException('Nincs aktív időpont-szabály.');
        }

        $run = BookingRun::create([
            'trigger' => $manual ? 'manual' : 'scheduled',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $payload = $this->buildPayload($rules);
            $result = $this->executeScript($payload, $dryRun);
            $this->persistAttempts($run, $result, $dryRun);

            $outcome = $this->resolveRunOutcome($result);
            $run->update([
                'status' => $outcome['status'],
                'finished_at' => now(),
                'summary' => $outcome['summary'],
                'exit_code' => $outcome['exit_code'],
                'raw_output' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'summary' => $e->getMessage(),
                'exit_code' => 1,
            ]);

            throw $e;
        }

        return $run->fresh();
    }

    private function buildPayload(Collection $rules): array
    {
        $skipSlots = BookingSlotBooking::query()
            ->pluck('slot_starts_at')
            ->map(fn ($dt) => Carbon::parse($dt)->timezone(config('app.timezone'))->toIso8601String())
            ->values()
            ->all();

        return [
            'rules' => $rules->map(fn (BookingRule $rule) => [
                'id' => $rule->id,
                'weekday' => $rule->weekday,
                'time' => $rule->time_formatted,
                'waitlist_ok' => $rule->waitlist_ok,
                'label' => $rule->label,
            ])->values()->all(),
            'slots' => $this->resolveUpcomingSlots($rules),
            'skip_slots' => $skipSlots,
            'weeks_ahead' => config('motibro.weeks_ahead'),
            'waitlist' => config('motibro.waitlist'),
            'base_url' => config('motibro.base_url'),
            'email' => config('motibro.email'),
            'password' => config('motibro.password'),
            'headless' => config('motibro.headless'),
            'portal_site_id' => config('motibro.portal_site_id'),
        ];
    }

    private function resolveUpcomingSlots(Collection $rules): array
    {
        $tz = config('app.timezone', 'Europe/Budapest');
        $now = now()->timezone($tz);
        $end = $now->copy()->addWeeks(config('motibro.weeks_ahead'));
        $slots = [];

        foreach ($rules as $rule) {
            $cursor = $now->copy()->startOfDay();

            while ($cursor->lte($end)) {
                if ((int) $cursor->isoWeekday() === (int) $rule->weekday) {
                    [$hour, $minute] = array_pad(explode(':', $rule->time_formatted), 2, '0');
                    $slot = $cursor->copy()->setTime((int) $hour, (int) $minute, 0);

                    if ($slot->gte($now)) {
                        $slots[] = [
                            'rule_id' => $rule->id,
                            'slot_starts_at' => $slot->toIso8601String(),
                        ];
                    }
                }
                $cursor->addDay();
            }
        }

        usort($slots, fn (array $a, array $b) => strcmp($a['slot_starts_at'], $b['slot_starts_at']));

        return $slots;
    }

    private function executeScript(array $payload, bool $dryRun): array
    {
        $command = [
            config('motibro.node_binary'),
            config('motibro.script_path'),
        ];

        if ($dryRun) {
            $command[] = '--dry-run';
        }

        $process = new Process($command, base_path());
        $process->setInput(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $process->setTimeout(config('motibro.process_timeout'));
        $process->run();

        $output = trim($process->getOutput());

        if ($output === '') {
            throw new ProcessFailedException($process);
        }

        $result = json_decode($output, true);

        if (! is_array($result)) {
            throw new \RuntimeException('A Playwright script érvénytelen JSON-t adott vissza.');
        }

        if (! $process->isSuccessful() && ! array_key_exists('ok', $result)) {
            throw new ProcessFailedException($process);
        }

        return $result;
    }

    private function persistAttempts(BookingRun $run, array $result, bool $dryRun): void
    {
        $attempts = $result['attempts'] ?? [];

        DB::transaction(function () use ($run, $attempts, $dryRun) {
            foreach ($attempts as $row) {
                $slotAt = Carbon::parse($row['slot'] ?? $row['slot_starts_at'] ?? now());
                $ruleId = $row['rule_id'] ?? null;
                $action = $row['action'] ?? 'failed';
                $message = $row['message'] ?? null;

                BookingAttempt::create([
                    'booking_run_id' => $run->id,
                    'rule_id' => $ruleId,
                    'slot_starts_at' => $slotAt,
                    'action' => $action,
                    'message' => $message,
                ]);

                if ($dryRun || ! in_array($action, ['booked', 'waitlisted'], true)) {
                    continue;
                }

                if (BookingSlotBooking::query()->where('slot_starts_at', $slotAt)->exists()) {
                    continue;
                }

                BookingSlotBooking::create([
                    'slot_starts_at' => $slotAt,
                    'rule_id' => $ruleId,
                    'booking_run_id' => $run->id,
                    'status' => $action === 'waitlisted' ? 'waitlisted' : 'booked',
                    'booked_at' => now(),
                ]);
            }
        });
    }

    private function resolveRunOutcome(array $result): array
    {
        $attempts = collect($result['attempts'] ?? []);
        $booked = $attempts->where('action', 'booked')->count();
        $waitlisted = $attempts->where('action', 'waitlisted')->count();
        $failed = $attempts->where('action', 'failed')->count();
        $unavailable = $attempts->where('action', 'unavailable')->count();

        if ($failed > 0 || ! ($result['ok'] ?? false)) {
            return [
                'status' => 'failed',
                'summary' => $this->buildSummary($result),
                'exit_code' => 1,
            ];
        }

        if ($booked === 0 && $waitlisted === 0 && $unavailable > 0) {
            return [
                'status' => 'no_slots',
                'summary' => 'Nincs foglalható időpont',
                'exit_code' => 0,
            ];
        }

        return [
            'status' => 'completed',
            'summary' => $this->buildSummary($result),
            'exit_code' => 0,
        ];
    }

    private function buildSummary(array $result): string
    {
        if (! empty($result['message'])) {
            return (string) $result['message'];
        }

        $attempts = collect($result['attempts'] ?? []);
        $booked = $attempts->where('action', 'booked')->count();
        $waitlisted = $attempts->where('action', 'waitlisted')->count();
        $skipped = $attempts->where('action', 'skipped')->count();
        $failed = $attempts->where('action', 'failed')->count();

        return sprintf(
            'Foglalva: %d, várólista: %d, kihagyva: %d, hiba: %d',
            $booked,
            $waitlisted,
            $skipped,
            $failed,
        );
    }
}
