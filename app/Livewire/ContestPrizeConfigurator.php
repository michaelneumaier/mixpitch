<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ContestPrize;
use App\Models\Project;
use Illuminate\Validation\Rule;

class ContestPrizeConfigurator extends Component
{
    public $project;
    public $projectId;
    
    // Prize configuration arrays
    public $prizes = [
        '1st' => [
            'type' => 'none', 
            'cash_amount' => null, 
            'currency' => 'USD', 
            'title' => '', 
            'description' => '', 
            'value_estimate' => null
        ],
        '2nd' => [
            'type' => 'none', 
            'cash_amount' => null, 
            'currency' => 'USD', 
            'title' => '', 
            'description' => '', 
            'value_estimate' => null
        ],
        '3rd' => [
            'type' => 'none', 
            'cash_amount' => null, 
            'currency' => 'USD', 
            'title' => '', 
            'description' => '', 
            'value_estimate' => null
        ],
        'runner_up' => [
            'type' => 'none', 
            'cash_amount' => null, 
            'currency' => 'USD', 
            'title' => '', 
            'description' => '', 
            'value_estimate' => null
        ]
    ];

    public $availableCurrencies = [
        'USD' => 'USD ($)',
        'EUR' => 'EUR (€)',
        'GBP' => 'GBP (£)',
        'CAD' => 'CAD (C$)',
        'AUD' => 'AUD (A$)'
    ];

    protected $rules = [
        'prizes.*.type' => 'required|in:none,cash,other',
        'prizes.*.cash_amount' => 'nullable|numeric|min:0.01|max:999999.99',
        'prizes.*.currency' => 'required|string|max:3',
        'prizes.*.title' => 'nullable|string|max:255',
        'prizes.*.description' => 'nullable|string|max:1000',
        'prizes.*.value_estimate' => 'nullable|numeric|min:0|max:999999.99'
    ];

    protected $messages = [
        'prizes.*.cash_amount.min' => 'Cash amount must be at least $0.01',
        'prizes.*.cash_amount.max' => 'Cash amount cannot exceed $999,999.99',
        'prizes.*.title.required' => 'Prize title is required for other prizes',
        'prizes.*.title.max' => 'Prize title cannot exceed 255 characters',
        'prizes.*.description.max' => 'Prize description cannot exceed 1000 characters',
        'prizes.*.value_estimate.max' => 'Estimated value cannot exceed $999,999.99'
    ];

    public function mount($project = null, $projectId = null)
    {
        if ($project) {
            $this->project = $project;
            $this->projectId = $project->id;
            $this->loadExistingPrizes();
        } elseif ($projectId) {
            $this->projectId = $projectId;
            $this->project = Project::find($projectId);
            if ($this->project) {
                $this->loadExistingPrizes();
            }
        } else {
            // Creation mode - load any stored prize data from session
            $this->loadStoredPrizes();
        }
    }

    public function loadExistingPrizes()
    {
        if (!$this->project) return;

        $existingPrizes = $this->project->contestPrizes;
        
        foreach ($existingPrizes as $prize) {
            if (isset($this->prizes[$prize->placement])) {
                $this->prizes[$prize->placement] = [
                    'type' => $prize->prize_type,
                    'cash_amount' => $prize->cash_amount,
                    'currency' => $prize->currency ?? 'USD',
                    'title' => $prize->prize_title ?? '',
                    'description' => $prize->prize_description ?? '',
                    'value_estimate' => $prize->prize_value_estimate
                ];
            }
        }
    }

    public function updatedPrizes($value, $key)
    {
        // Extract placement and field from key (e.g., "1st.type" or "2nd.cash_amount")
        $pathParts = explode('.', $key);
        $placement = $pathParts[0];
        $field = $pathParts[1];

        // Clear conflicting fields when prize type changes
        if ($field === 'type') {
            if ($value === 'cash') {
                $this->prizes[$placement]['title'] = '';
                $this->prizes[$placement]['description'] = '';
                $this->prizes[$placement]['value_estimate'] = null;
            } elseif ($value === 'other') {
                $this->prizes[$placement]['cash_amount'] = null;
            } elseif ($value === 'none') {
                $this->prizes[$placement]['cash_amount'] = null;
                $this->prizes[$placement]['title'] = '';
                $this->prizes[$placement]['description'] = '';
                $this->prizes[$placement]['value_estimate'] = null;
            }
        }

        // Emit event for parent components to listen to changes
        $this->dispatch('prizesUpdated', [
            'totalCashPrizes' => $this->getTotalCashPrizes(),
            'prizeCounts' => $this->getPrizeCounts(),
            'prizeSummary' => $this->getPrizeSummary()
        ]);
    }

    public function validatePrizes()
    {
        $rules = $this->rules;
        
        // Add conditional validation for required fields
        foreach ($this->prizes as $placement => $prize) {
            if ($prize['type'] === 'cash') {
                $rules["prizes.{$placement}.cash_amount"] = 'required|numeric|min:0.01|max:999999.99';
            } elseif ($prize['type'] === 'other') {
                $rules["prizes.{$placement}.title"] = 'required|string|max:255';
            }
        }

        $this->validate($rules);
    }

    public function savePrizes()
    {
        $this->validatePrizes();
        
        // If no project exists (creation mode), store data temporarily and emit to parent
        if (!$this->project) {
            try {
                // Store prize data in session for later use
                $prizeData = [];
                foreach ($this->prizes as $placement => $prize) {
                    if ($prize['type'] !== 'none') {
                        $prizeData[$placement] = $prize;
                    }
                }
                
                session(['contest_prize_data' => $prizeData]);
                
                // Emit updated data to parent for display in summary
                $this->dispatch('prizesUpdated', [
                    'totalCashPrizes' => $this->getTotalCashPrizes(),
                    'prizeCounts' => $this->getPrizeCounts(),
                    'prizeSummary' => $this->getPrizeSummary()
                ]);
                
                session()->flash('success', 'Contest prizes configured successfully! They will be saved when you create the project.');
                $this->dispatch('prizesSaved');
                
            } catch (\Exception $e) {
                session()->flash('error', 'Error configuring prizes: ' . $e->getMessage());
            }
            return;
        }

        // Normal save process for edit mode when project exists
        try {
            // Delete existing prizes for this project
            $this->project->contestPrizes()->delete();

            // Create new prizes
            foreach ($this->prizes as $placement => $prizeData) {
                if ($prizeData['type'] !== 'none') {
                    $data = [
                        'project_id' => $this->project->id,
                        'placement' => $placement,
                        'prize_type' => $prizeData['type']
                    ];

                    if ($prizeData['type'] === 'cash') {
                        $data['cash_amount'] = !empty($prizeData['cash_amount']) ? $prizeData['cash_amount'] : null;
                        $data['currency'] = $prizeData['currency'];
                    } elseif ($prizeData['type'] === 'other') {
                        $data['prize_title'] = $prizeData['title'];
                        $data['prize_description'] = $prizeData['description'];
                        $data['prize_value_estimate'] = !empty($prizeData['value_estimate']) ? $prizeData['value_estimate'] : null;
                    }

                    ContestPrize::create($data);
                }
            }

            // Update project budget with total cash prizes
            $this->project->update([
                'budget' => $this->getTotalCashPrizes()
            ]);

            session()->flash('success', 'Contest prizes saved successfully!');
            $this->dispatch('prizesSaved');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error saving prizes: ' . $e->getMessage());
        }
    }

    /**
     * Static method to save stored prize data to a project (called after project creation)
     */
    public static function saveStoredPrizesToProject(Project $project)
    {
        $prizeData = session('contest_prize_data', []);
        
        if (empty($prizeData)) {
            return false;
        }
        
        try {
            foreach ($prizeData as $placement => $prize) {
                $data = [
                    'project_id' => $project->id,
                    'placement' => $placement,
                    'prize_type' => $prize['type']
                ];

                if ($prize['type'] === 'cash') {
                    $data['cash_amount'] = !empty($prize['cash_amount']) ? $prize['cash_amount'] : null;
                    $data['currency'] = $prize['currency'];
                } elseif ($prize['type'] === 'other') {
                    $data['prize_title'] = $prize['title'];
                    $data['prize_description'] = $prize['description'];
                    $data['prize_value_estimate'] = !empty($prize['value_estimate']) ? $prize['value_estimate'] : null;
                }

                ContestPrize::create($data);
            }
            
            // Clear the session data
            session()->forget('contest_prize_data');
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error saving stored prizes to project: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'prize_data' => $prizeData
            ]);
            return false;
        }
    }

    /**
     * Load stored prize data from session (for creation mode)
     */
    public function loadStoredPrizes()
    {
        $storedPrizes = session('contest_prize_data', []);
        
        foreach ($storedPrizes as $placement => $prizeData) {
            if (isset($this->prizes[$placement])) {
                $this->prizes[$placement] = [
                    'type' => $prizeData['type'],
                    'cash_amount' => $prizeData['cash_amount'] ?? null,
                    'currency' => $prizeData['currency'] ?? 'USD',
                    'title' => $prizeData['title'] ?? '',
                    'description' => $prizeData['description'] ?? '',
                    'value_estimate' => $prizeData['value_estimate'] ?? null
                ];
            }
        }
        
        // Emit updated data to parent
        $this->dispatch('prizesUpdated', [
            'totalCashPrizes' => $this->getTotalCashPrizes(),
            'prizeCounts' => $this->getPrizeCounts(),
            'prizeSummary' => $this->getPrizeSummary()
        ]);
    }

    public function getTotalCashPrizes(): float
    {
        $total = 0;
        foreach ($this->prizes as $prize) {
            if ($prize['type'] === 'cash' && !empty($prize['cash_amount']) && is_numeric($prize['cash_amount'])) {
                $total += (float) $prize['cash_amount'];
            }
        }
        return $total;
    }

    public function getTotalEstimatedValue(): float
    {
        $total = $this->getTotalCashPrizes();
        foreach ($this->prizes as $prize) {
            if ($prize['type'] === 'other' && !empty($prize['value_estimate']) && is_numeric($prize['value_estimate'])) {
                $total += (float) $prize['value_estimate'];
            }
        }
        return $total;
    }

    public function getPrizeCounts(): array
    {
        $counts = ['total' => 0, 'cash' => 0, 'other' => 0];
        foreach ($this->prizes as $prize) {
            if ($prize['type'] !== 'none') {
                $counts['total']++;
                if ($prize['type'] === 'cash') {
                    $counts['cash']++;
                } elseif ($prize['type'] === 'other') {
                    $counts['other']++;
                }
            }
        }
        return $counts;
    }

    public function getPrizeSummary(): array
    {
        $summary = [];
        $order = ['1st', '2nd', '3rd', 'runner_up'];
        
        foreach ($order as $placement) {
            $prize = $this->prizes[$placement];
            if ($prize['type'] !== 'none') {
                $summary[] = [
                    'placement' => $this->getPlacementDisplayName($placement),
                    'placement_key' => $placement,
                    'type' => $prize['type'],
                    'display_value' => $this->getPrizeDisplayValue($placement, $prize),
                    'cash_value' => $prize['type'] === 'cash' && is_numeric($prize['cash_amount'] ?? 0) ? (float)($prize['cash_amount'] ?? 0) : 0,
                    'estimated_value' => $prize['type'] === 'other' && is_numeric($prize['value_estimate'] ?? 0) ? (float)($prize['value_estimate'] ?? 0) : (is_numeric($prize['cash_amount'] ?? 0) ? (float)($prize['cash_amount'] ?? 0) : 0),
                    'emoji' => $this->getPlacementEmoji($placement),
                    'title' => $prize['title'] ?? '',
                    'description' => $prize['description'] ?? ''
                ];
            }
        }
        
        return $summary;
    }

    public function getPrizeDisplayValue($placement, $prize): string
    {
        if ($prize['type'] === 'cash' && $prize['cash_amount']) {
            $currency = $prize['currency'] ?? 'USD';
            $symbol = match($currency) {
                'USD' => '$',
                'EUR' => '€',
                'GBP' => '£',
                'CAD' => 'C$',
                'AUD' => 'A$',
                default => '$'
            };
            return $symbol . number_format((float)$prize['cash_amount'], 2);
        } elseif ($prize['type'] === 'other' && $prize['title']) {
            return $prize['title'];
        }
        
        return 'Prize';
    }

    public function getPlacementDisplayName($placement): string
    {
        return match($placement) {
            '1st' => '1st Place',
            '2nd' => '2nd Place',
            '3rd' => '3rd Place',
            'runner_up' => 'Runner-ups',
            default => $placement
        };
    }

    public function getPlacementEmoji($placement): string
    {
        return match($placement) {
            '1st' => '🥇',
            '2nd' => '🥈',
            '3rd' => '🥉',
            'runner_up' => '🏅',
            default => '🎖️'
        };
    }

    /**
     * Clear stored prize data from session
     */
    public static function clearStoredPrizes()
    {
        session()->forget('contest_prize_data');
    }

    /**
     * Reset prizes to default state
     */
    public function resetPrizes()
    {
        // Reset all prizes to default
        foreach (['1st', '2nd', '3rd', 'runner_up'] as $placement) {
            $this->prizes[$placement] = [
                'type' => 'none', 
                'cash_amount' => null, 
                'currency' => 'USD', 
                'title' => '', 
                'description' => '', 
                'value_estimate' => null
            ];
        }

        // If in creation mode, clear session data
        if (!$this->project) {
            session()->forget('contest_prize_data');
        }

        // Emit updated data to parent
        $this->dispatch('prizesUpdated', [
            'totalCashPrizes' => $this->getTotalCashPrizes(),
            'prizeCounts' => $this->getPrizeCounts(),
            'prizeSummary' => $this->getPrizeSummary()
        ]);

        session()->flash('success', 'Prizes reset to default state.');
    }

    public function render()
    {
        return view('livewire.contest-prize-configurator');
    }
}
