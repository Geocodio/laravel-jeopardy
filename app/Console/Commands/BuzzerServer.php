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

    private array $buttonPins;

    private array $ledPins;

    private $buttonPinIds = [
        17, // White
        18, // Red
        24, // Yellow
        25, // Green
        5,  // Blue
    ];

    private $ledPinIds = [
	    21, // White 
	    14, // Red
	    23, // Yellow
	    15, // Green
	    20, // Blue
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->init();

        while (true) {
            $this->checkButtons();
            usleep(10000); // Sleep for 10ms
        }
    }

    private function init(): void
    {
        $this->buttonPins = [];
        foreach ($this->buttonPinIds as $pin) {
            $buttonPin = PinService::pin($pin);
            $buttonPin->makeInput();
            $this->buttonPins[] = $buttonPin;
        }

        $this->ledPins = [];
        foreach ($this->ledPinIds as $pin) {
            $ledPin = PinService::pin($pin);
            $ledPin->makeOutput();
            $ledPin->turnOn();
            $this->ledPins[] = $ledPin;
        }
    }

    private function loopLEDs(): void
    {
        while (true) {
            foreach ($this->ledPins as $id => $pin) {
                echo 'Turning on ' . $id . "\n";
                $pin->turnOff();
                sleep(1);
                $pin->turnOn();
                sleep(1);
            }
        }
    }

    private function checkButtons(): void
    {
        foreach ($this->buttonPins as $index => $pin) {
            if ($pin->isOff()) {
                $this->info("Button on pin #{$index} is pressed");

                for ($i = 0; $i < 5; $i++) {
                    $this->ledPins[$index]->turnOff();
                    usleep(100000); // Sleep for 100ms
                    $this->ledPins[$index]->turnOn();
                    usleep(100000); // Sleep for 100ms
                }
            }
        }
    }
}
