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
        $buttonPinIds = [
            17,
            18,
            24,
            25,
            5
        ];

        $ledPinIds = [
            14,
            15,
            23,
            20,
            21
        ];

        $buttonPins = [];
        foreach ($buttonPinIds as $pin) {
            $buttonPins[$pin] = PinService::pin($pin);
            $buttonPins[$pin]->makeInput();
        }

        $ledPins = [];
        foreach ($ledPinIds as $pin) {
            $ledPins[$pin] = PinService::pin($pin);
            $ledPins[$pin]->makeOutput();
            $ledPins[$pin]->turnOn();
        }

        while (true) {
            foreach ($ledPins as $id => $pin) {
	        echo "Turning on " . $id . "\n";
                $pin->turnOff();
                sleep(1);
                $pin->turnOn();
                sleep(1);
            }
            /*
            $this->checkButtons($buttonPins);
            usleep(10000); // Sleep for 10ms
            */
        }
    }

    private function checkButtons(array $buttonPins): void
    {
        foreach ($buttonPins as $id => $pin) {
            if ($pin->isOff()) {
                $this->info("Button on pin {$id} is pressed.");
            }
        }
    }
}
