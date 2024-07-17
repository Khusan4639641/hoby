<?php

namespace App\Console\Commands;

use App\Models\KatmRegion;
use App\Models\V3\District;
use App\Models\V3\Region;
use Illuminate\Console\Command;

class MigrateKatmRegionsToRegionsAndDistricts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'katm_regions:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate KATM regions to regions&districts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $katm_regions = KatmRegion::distinct()->get(['region_name', 'region']);
        $katm_districts = KatmRegion::distinct()->get(['local_region_name', 'local_region', 'myid_region', 'region']);

        foreach ($katm_regions as $i => $katm_region) {
            $region = Region::updateOrCreate(
                ['cbu_id' => $katm_region->region],
                ['name' => $katm_region->region_name]
            );
            $this->info(($i + 1) . '.' . $katm_region->region_name . ' (' . $katm_region->region . ')');

            foreach ($katm_districts as $ii => $katm_district) {
                if($katm_region->region !== $katm_district->region) continue;

                $region->districts()->updateOrCreate(
                    ['cbu_id' => $katm_district->local_region],
                    [
                        'name' => $katm_district->local_region_name,
                        'iiv_id' => $katm_district->myid_region,
                    ]
                );
                $this->info('- ' . $katm_district->local_region_name . ' (' . $katm_district->local_region . ')');
            }
        }
        $this->info('Команда завершена успешно');
    }
}
