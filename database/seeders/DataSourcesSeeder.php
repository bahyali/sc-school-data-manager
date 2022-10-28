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
                'name'  => 'active_schools',
                'title' => 'Ministry website: Schools in operation',
                'active' => '1',
                'configuration' => [
                    'overrides' => [
                        'status' => 'active'
                    ],
                    'url' => 'https://data.ontario.ca/dataset/7a049187-cf29-4ffe-9028-235b95c61fa3/resource/6545c5ec-a5ce-411c-8ad5-d66363da8891/download',
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
                'active' => 1,
            ],

            [
                'name'  => 'onsis_all_schools_old',
                'title' => 'Manual upload: ONSIS Spreadsheet Old',
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
                        'contact_email' => 17,
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
                'title' => 'Ministry website: Revoked Schools',
                'configuration' => [
                    'overrides' => [
                        'status' => 'revoked'
                    ],
                    'url' => 'https://www.ontario.ca/page/private-schools-cannot-grant-credits-towards-ontario-secondary-school-diploma'
                ],
                'resource' => 'html',
                'url' => NULL,
                'active' => 1,
            ],
            [
                'name'  => 'closed_schools',
                'title' => 'Ministry website: Closed Schools',
                'configuration' => [
                    'overrides' => [
                        'status' => 'closed'
                    ],
                    'url' => 'https://www.ontario.ca/page/private-schools-have-closed',
                ],
                'resource' => 'html',
                'url' => NULL,
                'active' => 1,
            ],
            [
                'name'  => 'schoolcred_engine',
                'configuration' => [],
                'title' => 'Auto Mixer',
                'resource' => 'auto_mixer',
                'url' => NULL,
            ],

            [
                'name'  => 'conflict_fixed',
                'configuration' => [],
                'title' => 'Conflict Fixer',
                'resource' => 'conflict_fixed',
                'url' => NULL,
            ],


            [
                'name'  => 'onsis_all_schools',
                'title' => 'Manual upload: ONSIS Spreadsheet',
                'configuration' => [
                    'overrides' => [],
                    'mapping' => [
                        'number' => 0,
                        'name' => 1,
                        'type' => 2,
                        'level' => 3,
                        'grade_range' => 4,
                        'semester_type' => 5,
                        'language_of_instruction' => 6,
                        'teachers_num' => 7,
                        'oct_teachers' => 8,
                        
                        'enrollment' => 9,
                        'enrollment_21_22' => 10,
                        'ossd_continuous_intake' => 11,
                        'noi_last_date_submission' => 12,
                        'noi_status_description' => 13,
                        'open_date' => 14,
                        'closed_date' => 15,
                        'status' => 16,

                        'corporation_name' => 17,
                        'corporation_establish_date' => 18,
                        'corporation_contact_name' => 19,
                        
                        'ownership_type' => 20,
                        'cra_bn' => 21,

                        'diploma_2014_2015' => 22,
                        'diploma_2015_2016' => 23,
                        'diploma_2016_2017' => 24,
                        'diploma_2017_2018' => 25,
                        'diploma_2018_2019' => 26,
                        'diploma_2019_2020' => 27,
                        'diploma_2020_2021' => 28,
                        'diploma_2021_2022' => 29,

                        'po_box' => 30,
                        'suite' => 31,
                        'street' => 32,
                        'city' => 33,
                        'postal_code' => 34,
                        'province' => 35,
                        'region' => 36,
                        'telephone' => 37,
                        'fax' => 38,
                        'website' => 39,

                        'principal_name' => 43,
                        'principal_last_name' => 44,
                        'principal_qualification' => 45,
                        'principal_qualification_other' => 46,
                        'principal_start_date' => 47,


                        'affiliation' => 48,
                        'association_membership' => 50,
                        'association_other' => 51,


                    ],
                    'date_columns' => [
                        'noi_last_date_submission',
                        'open_date',
                        'closed_date',
                        'corporation_establish_date',
                        'principal_start_date'
                    ],
                ],
                'resource' => 'excel',
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
