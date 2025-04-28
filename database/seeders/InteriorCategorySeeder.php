<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InteriorCategory;

class InteriorCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = InteriorCategory::getStaticCategories();

        foreach ($categories as $category) {
            InteriorCategory::create($category);
        }
    }
}

