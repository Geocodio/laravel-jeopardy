<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Collection;

class VolunteerPicker extends Component
{
    public Collection $attendees;
    public array $teams;
    public array $selectedVolunteers = [];
    public int $currentTeamIndex = -1;
    public bool $isShuffling = false;
    public array $shufflingNames = [];
    public bool $selectionComplete = false;
    public ?int $rerollTeamIndex = null;
    public ?int $rerollSlotIndex = null;

    public function mount(): void
    {
        $this->loadAttendees();
        $this->teams = config('jeopardy.teams');
        $this->initializeSelectedVolunteers();
    }

    private function loadAttendees(): void
    {
        $attendeesPath = resource_path('attendees.txt');
        
        if (!file_exists($attendeesPath)) {
            $this->attendees = collect();
            return;
        }

        $attendeesList = file($attendeesPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Deduplicate and clean names
        $this->attendees = collect($attendeesList)
            ->map(fn($name) => trim($name))
            ->filter(fn($name) => !empty($name))
            ->unique()
            ->values();
    }

    private function initializeSelectedVolunteers(): void
    {
        foreach ($this->teams as $index => $team) {
            $this->selectedVolunteers[$index] = [
                'team' => $team,
                'members' => [null, null, null],
                'completed' => false,
            ];
        }
    }

    public function startSelection(): void
    {
        if ($this->currentTeamIndex === -1) {
            $this->currentTeamIndex = 0;
            $this->selectTeamMembers();
        }
    }

    public function nextTeam(): void
    {
        if ($this->currentTeamIndex < count($this->teams) - 1) {
            $this->selectedVolunteers[$this->currentTeamIndex]['completed'] = true;
            $this->currentTeamIndex++;
            $this->selectTeamMembers();
        } else {
            $this->selectedVolunteers[$this->currentTeamIndex]['completed'] = true;
            $this->selectionComplete = true;
        }
    }

    private function selectTeamMembers(): void
    {
        $this->isShuffling = true;
        $this->shufflingNames = [];
        
        // Get list of already selected volunteers
        $alreadySelected = collect($this->selectedVolunteers)
            ->pluck('members')
            ->flatten()
            ->filter()
            ->toArray();
        
        // Get available attendees
        $available = $this->attendees->diff($alreadySelected)->values();
        
        if ($available->count() < 3) {
            session()->flash('error', 'Not enough attendees available for selection!');
            $this->isShuffling = false;
            return;
        }
        
        // Generate shuffling names for animation (20 random names for each slot)
        for ($slot = 0; $slot < 3; $slot++) {
            $this->shufflingNames[$slot] = $available->random(min(20, $available->count()))->toArray();
        }
        
        // Select 3 unique random members
        $selected = $available->random(3)->toArray();
        
        // Store selected members
        $this->selectedVolunteers[$this->currentTeamIndex]['members'] = $selected;
        
        // Dispatch browser event to trigger animation
        $this->dispatch('start-shuffle', 
            teamIndex: $this->currentTeamIndex,
            shufflingNames: $this->shufflingNames,
            finalNames: $selected
        );
    }

    public function stopShuffle(): void
    {
        $this->isShuffling = false;
        $this->shufflingNames = [];
    }

    public function rerollMember(int $teamIndex, int $slotIndex): void
    {
        if ($this->isShuffling) {
            return;
        }
        
        $this->rerollTeamIndex = $teamIndex;
        $this->rerollSlotIndex = $slotIndex;
        $this->isShuffling = true;
        
        // Get current member to exclude
        $currentMember = $this->selectedVolunteers[$teamIndex]['members'][$slotIndex];
        
        // Get list of already selected volunteers (excluding current)
        $alreadySelected = collect($this->selectedVolunteers)
            ->pluck('members')
            ->flatten()
            ->filter()
            ->reject(fn($name) => $name === $currentMember)
            ->toArray();
        
        // Get available attendees
        $available = $this->attendees->diff($alreadySelected)->values();
        
        if ($available->isEmpty()) {
            session()->flash('error', 'No other attendees available!');
            $this->isShuffling = false;
            return;
        }
        
        // Generate shuffling names for animation
        $shufflingNames = $available->random(min(20, $available->count()))->toArray();
        
        // Select new random member
        $newMember = $available->random();
        
        // Update the member
        $this->selectedVolunteers[$teamIndex]['members'][$slotIndex] = $newMember;
        
        // Dispatch browser event to trigger animation for single slot
        $this->dispatch('reroll-member',
            teamIndex: $teamIndex,
            slotIndex: $slotIndex,
            shufflingNames: $shufflingNames,
            finalName: $newMember
        );
    }

    public function stopReroll(): void
    {
        $this->isShuffling = false;
        $this->rerollTeamIndex = null;
        $this->rerollSlotIndex = null;
    }

    public function resetSelection(): void
    {
        $this->currentTeamIndex = -1;
        $this->selectionComplete = false;
        $this->isShuffling = false;
        $this->shufflingNames = [];
        $this->initializeSelectedVolunteers();
    }

    public function render()
    {
        return view('livewire.volunteer-picker')
            ->layout('layouts.game');
    }
}