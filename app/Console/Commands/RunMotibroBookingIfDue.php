<?php

namespace App\Console\Commands;

use App\Services\MotibroBookingService;
use App\Services\SchedulerWindowService;
use Illuminate\Console\Command;

class RunMotibroBookingIfDue extends Command
{
    protected $signature = 'motibro:book-if-due
                            {--force : Ütemezési ablak figyelmen kívül hagyása (dev)}';

    protected $description = 'Automatikus Motibro foglalás, ha az ütemezési ablakban és esedékes';

    public function handle(
        SchedulerWindowService $scheduler,
        MotibroBookingService $bookingService,
    ): int {
        if (! $this->option('force') && ! $scheduler->shouldRunNow()) {
            return self::SUCCESS;
        }

        try {
            $run = $bookingService->run(manual: false, dryRun: false);
            $scheduler->markScheduledRun();

            $this->info("Automatikus futás #{$run->id} — {$run->status}: {$run->summary}");

            return $run->status === 'completed' ? self::SUCCESS : self::FAILURE;
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Már fut egy foglalás')) {
                $this->comment('Kihagyva: már fut egy foglalás.');

                return self::SUCCESS;
            }

            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
