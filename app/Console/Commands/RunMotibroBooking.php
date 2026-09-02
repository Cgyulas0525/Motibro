<?php

namespace App\Console\Commands;

use App\Services\MotibroBookingService;
use Illuminate\Console\Command;

class RunMotibroBooking extends Command
{
    protected $signature = 'motibro:book
                            {--manual : Kézi indítás (admin / dashboard)}
                            {--dry-run : Slotok ellenőrzése kattintás nélkül}';

    protected $description = 'Motibro foglalás futtatása (Playwright script)';

    public function handle(MotibroBookingService $service): int
    {
        try {
            $run = $service->run(
                manual: (bool) $this->option('manual'),
                dryRun: (bool) $this->option('dry-run'),
            );

            $this->info("Futás #{$run->id} — {$run->status}: {$run->summary}");

            return $run->status === 'completed' ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
