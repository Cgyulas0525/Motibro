<?php

namespace App\Console\Commands;

use App\Mail\BookingRunFinished;
use App\Models\BookingRun;
use App\Services\MotibroBookingService;
use App\Services\SchedulerWindowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            $lastOfWindow = $scheduler->isLastRunOfWindow();

            $run = $bookingService->run(manual: false, dryRun: false);
            $scheduler->markScheduledRun();

            $this->info("Automatikus futás #{$run->id} — {$run->status}: {$run->summary}");

            if ($lastOfWindow) {
                $this->notify($run);
            }

            return in_array($run->status, ['completed', 'no_slots'], true) ? self::SUCCESS : self::FAILURE;
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

    /**
     * Az értesítés hibája ne buktassa el a futást.
     */
    private function notify(BookingRun $run): void
    {
        $recipient = config('motibro.notify_email');

        if (empty($recipient)) {
            return;
        }

        try {
            Mail::to($recipient)->send(new BookingRunFinished($run));
            $this->info("Értesítés elküldve: {$recipient}");
        } catch (\Throwable $e) {
            Log::error('Motibro értesítés küldése sikertelen.', [
                'booking_run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
            $this->warn("Értesítés nem küldhető el: {$e->getMessage()}");
        }
    }
}
