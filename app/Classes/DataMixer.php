<?php

namespace App\Classes;

use App\Models\School;
use App\Models\DataSource;

use Exception;

class DataMixer
{
    private $data_source;
    private static $instance = null;

    private function __construct()
    {
        $this->data_source = DataSource::where('name', 'schoolcred_engine')->first();
    }

    // Use Singleton pattern
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new DataMixer();
        }

        return self::$instance;
    }

    public function run(SchoolRecord $school_record)
    {
        $school = $school_record->getSchool();

        if (!$school)
            throw new Exception("We need a school to create a remix!");

        // Get latest revision from each data source sorted
        $latest_revisions = $this->getLatestRevisions($school);

        $remix = $latest_revisions->reduce(function ($carry, $item) {
            if (!$carry)
                return $item;

            return $carry->merge($item);
        })->toArray();

        $school_record->addRevision($remix, $this->data_source);

        return $remix;
    }

    private function clean($item, $key)
    {
        return !($item == null || in_array($key, ['created_at', 'updated_at', 'id']));
    }
    private function getLatestRevisions($school)
    {
        // Order of merging
        $SORT = [
            'active',
            'revoked',
            'closed'
        ];

        $data_sources = $school->dataSources->pluck('id');

        // Latest revision from each data source
        $latest_revisions = $data_sources->map(function ($ds_id) use ($school) {
            // Convert to Support\Collection
            return collect($school->revisions()
                ->byDataSourceId($ds_id)
                ->latest()
                ->first()
                ->toArray())
                ->filter(function ($item, $key) {
                    return $this->clean($item, $key);
                });
            // Sort by order of operations (closed > revoked > active)
        })->sortBy(function ($revision) use ($SORT) {
            return array_search($revision->get('status'), $SORT);
        });

        return $latest_revisions;
    }
}
