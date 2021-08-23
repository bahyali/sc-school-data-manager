<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DataSource;


class DataSourcesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $defined_types = [
            [
                'name'  => 'active',
                'configuration' => 
                	[
                		'name' => 0,
                		'number' => 1,
                		'principal_name' => 2,
                		'special_conditions_code' => 3,
                		'address_line_1' => 4,
                		'address_line_2' => 5,
                		'address_line_3' => 6,
                		'country' => 7,
                		'telephone' => 8,
                		'fax' => 9,
                		'website' => 10,

                	],
                'resource' => 'excel',
            ],
        ];

        foreach($defined_types as $defined_type) {
            $data_source = new DataSource();
            $data_source->name = $defined_type['name'];
            $data_source->configuration = $defined_type['configuration'];
            $data_source->resource = $defined_type['resource'];

            $data_source->save();
        }
    }
}
