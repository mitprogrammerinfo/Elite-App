<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExteriorFeature;

class ExteriorFeatureSeeder extends Seeder
{
    public function run()
    {
        $features = ExteriorFeature::getStaticExtFeatures();

        foreach ($features as $feature) {
            ExteriorFeature::create($feature);
        }
    }
}
