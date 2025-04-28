<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InteriorFeature;

class InteriorFeatureSeeder extends Seeder
{
    public function run()
    {
        $features = InteriorFeature::getStaticFeatures();

        foreach ($features as $feature) {
            InteriorFeature::create($feature);
        }
    }
}
