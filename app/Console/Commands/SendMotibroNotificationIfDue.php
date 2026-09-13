<?php

namespace App\Console\Commands;

use App\Mail\BookingRunFinished;
use App\Models\BookingRun;
use App\Services\SchedulerWindowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMotibroNotificationIfDue extends Command
{
    protected $signature = 'motibro:notify-if-due';

    protected $description = 'Értesítő e-mail az ütemezési ablak lezárása után, naponta egyszer';

    public function handle(SchedulerWindowService $scheduler): int
    {
        if (! $scheduler->shouldNotifyNow()) {
            return self::SUCCESS;
        }

        // Ablakonként egy kísérlet: sikertelen küldés se indítson percenkénti újrapróbálást.
        $scheduler->markNotified();

        $recipient = config('motibro.notify_email');
        $run = BookingRun::query()->latest('id')->first();

        if (empty($recipient) || $run === null) {
            return self::SUCCESS;
        }

        try {
            Mail::to($recipient)->send(new BookingRunFinished($run));

            $this->info("Értesítés elküldve: {$recipient} (futás #{$run->id})");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Motibro értesítés küldése sikertelen.', [
                'booking_run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);

            $this->warn("Értesítés nem küldhető el: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
