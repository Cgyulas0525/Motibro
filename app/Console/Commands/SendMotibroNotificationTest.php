<?php

namespace App\Console\Commands;

use App\Mail\BookingRunFinished;
use App\Models\BookingRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMotibroNotificationTest extends Command
{
    protected $signature = 'motibro:notify-test
                            {--email= : Címzett felülírása (alapértelmezés: MOTIBRO_NOTIFY_EMAIL)}';

    protected $description = 'Értesítő e-mail küldése próbaként, foglalás futtatása nélkül';

    public function handle(): int
    {
        $recipient = $this->option('email') ?: config('motibro.notify_email');

        if (empty($recipient)) {
            $this->error('Nincs címzett — állítsd be a MOTIBRO_NOTIFY_EMAIL kulcsot, vagy add meg az --email opciót.');

            return self::FAILURE;
        }

        $run = BookingRun::query()->latest('id')->first();

        if ($run === null) {
            $this->error('Nincs egyetlen futás sem az adatbázisban.');

            return self::FAILURE;
        }

        $this->line('Mailer: '.config('mail.default'));
        $this->line('Feladó: '.config('mail.from.address'));
        $this->line('Link: '.rtrim((string) config('app.url'), '/'));

        try {
            Mail::to($recipient)->send(new BookingRunFinished($run));
        } catch (\Throwable $e) {
            $this->error("Küldés sikertelen: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Elküldve: {$recipient} (futás #{$run->id})");

        return self::SUCCESS;
    }
}
