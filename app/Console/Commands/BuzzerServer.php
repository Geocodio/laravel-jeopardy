<?php

namespace App\Console\Commands;

use DanJohnson95\Pinout\Facades\PinService;
use Illuminate\Console\Command;

class BuzzerServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:buzzer-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the buzzer server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $buttonPins = [
            17,
            18,
        ];

        $pins = [];

        foreach ($buttonPins as $pin) {
            $pins[$pin] = PinService::pin($pin);
            $pins[$pin]->makeInput();
        }

        while (true) {
            $this->checkButtons($pins);
            usleep(10000); // Sleep for 10ms
        }
    }

    private function checkButtons(array $pins): void
    {
        foreach ($pins as $id => $pin) {
            if ($pin->isOff()) {
                $this->info("Button on pin {$id} is pressed.");
            }
        }
    }
}
