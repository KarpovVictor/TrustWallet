<?php

namespace App\Console\Commands;

use App\Services\StakingService;
use Illuminate\Console\Command;

class CalculateStakingProfit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'staking:calculate-profit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate daily profit for active stakes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param StakingService $stakingService
     * @return int
     */
    public function handle(StakingService $stakingService)
    {
        $this->info('Calculating staking profits...');
        
        $stakingService->calculateDailyProfit();
        
        $this->info('Profits calculated successfully.');
        
        return 0;
    }
}