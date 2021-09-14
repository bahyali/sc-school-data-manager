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
                'name'  => 'private_schools_ontario',
                'title' => 'Private Schools Contact Information',
                'configuration' => [
                    'overrides' => [
                        'status' => 'active'
                    ],
                    'mapping' => [
                        'name' => 0,
                        'number' => 1,
                        'ossd_credits_offered' => 2,
                        'principal_name' => 3,
                        'address_line_1' => 6,
                        'address_line_2' => 7,
                        'address_line_3' => 9,
                        'telephone' => 10,
                        'fax' => 11,
                        'region' => 12,
                        'website' => 13,
                        'level' => 14,
                        'special_conditions_code' => 15,
                        'program_type' => 16,
                        'association_membership' => 17,
                    ],
                    'date_columns' => [],
                ],
                'resource' => 'excel',
                'url' => NULL,
            ],

            [
                'name'  => 'onsis_all_schools',
                'title' => 'ONSIS Schools Sheet',
                'configuration' => [
                    'overrides' => [],
                    'mapping' => [
                        'status' => 0,
                        'name' => 1,
                        'number' => 2,
                        'open_date' => 3,
                        'revoked_date' => 4,
                        'closed_date' => 6,
                        'special_conditions_code' => 7,
                        'principal_name' => 8,
                        'teachers_num' => 10,
                        'oct_teachers' => 11,
                        'owner_business' => 12,
                        'corporation_name' => 13,
                        'corporation_establish_date' => 14,
                        'corporation_contact_name' => 15,
                        'enrollment' => 16,
                    ],
                    'date_columns' => [
                        'open_date',
                        'revoked_date',
                        'closed_date',
                        'corporation_establish_date'
                    ],
                ],
                'resource' => 'excel',
                'url' => NULL,
            ],
            [
                'name'  => 'revoked_schools',
                'title' => 'Revoked Schools',
                'configuration' => [
                    'overrides' => [
                        'status' => 'revoked'
                    ],
                    'url' => 'http://www.edu.gov.on.ca/eng/general/elemsec/privsch/revoked.html#1920'
                ],
                'resource' => 'html',
                'url' => NULL,
            ],
            [
                'name'  => 'closed_schools',
                'title' => 'Closed Schools',
                'configuration' => [
                    'overrides' => [
                        'status' => 'closed'
                    ],
                    'url' => 'http://www.edu.gov.on.ca/eng/general/elemsec/privsch/closed.html#2021',
                ],
                'resource' => 'html',
                'url' => NULL,
            ],
            [
                'name'  => 'schoolcred_engine',
                'configuration' => [],
                'title' => 'Auto Mixer',
                'resource' => 'auto_mixer',
                'url' => NULL,
            ]
        ];

        foreach ($defined_types as $defined_type) {
            $data_source = new DataSource();
            $data_source->name = $defined_type['name'];
            $data_source->title = $defined_type['title'];
            $data_source->configuration = $defined_type['configuration'];
            $data_source->resource = $defined_type['resource'];
            $data_source->url = $defined_type['url'];

            $data_source->save();
        }
    }
}
