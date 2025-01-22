<?php

namespace App\Classes;

use App\Models\School;
use App\Models\DataSource;
use Exception;
use Illuminate\Support\Collection;
use Carbon\Carbon;


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

        $latest_revisions = $latest_revisions->unique(function ($item) {
            return $item['id'];
        });

        // dd($latest_revisions);
        
        $remix = $this->mix($latest_revisions);

        

        // dd($remix);

        if ($remix){
            
            $remix->forget(['id', 'updated_at']);

            // if($remix['status'] == 'active'){
            //     $remix['revoked_date'] = NULL; 
            //     $remix['closed_date'] = NULL; 
            // }


            //to modify OSSD if school type is private inspected
            if(isset($remix['type']) && strtolower($remix['type']) == 'private inspected'){
                $remix['ossd_credits_offered'] = 'yes'; 
            }


            //to ensure that open_date field in not empty in case status is active
            if(isset($remix['status']) && strtolower($remix['status']) == 'active' && !isset($remix['open_date']) ){
                $remix['open_date'] = Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
            }
            
            $school_record->addRevision($remix->toArray(), $this->data_source, false, true, false, true);
        }

        return $remix;
    }

    private function clean($item, $key)
    {
        return !($item == null || in_array($key, ['created_at', 'hash']));
    }

    private function mix(Collection $entries)
    {
        return $entries->reduce(function ($carry, $item) {
            if (!$carry)
                return $item;

            return $carry->merge($item);
        });
    }

    private function getLatestRevisions($school)
    {
        // Order of merging
        // $SORT = [
        //     'active',
        //     'revoked',
        //     'closed',
        //     'revoked, closed'
        // ];

        $SORT = DataSource::orderBy('priority','DESC')->pluck('id')->toArray();

        $data_sources = $school->dataSources->pluck('id');
        $last_revision_id = $school->revision_id;

        $last_revision_updated_at = ($last_revision_id) ? $school->lastRevision->updated_at->subMinute()->toDateTimeString() : NULL;

        // var_dump($last_revision_updated_at->subMinute()->toDateTimeString());
        // return;


        // mixed revision from each data source
        $latest_revisions = $data_sources->map(function ($ds_id) use ($school, $last_revision_id, $last_revision_updated_at) {
            $revisions_by_ds = $school->revisions()
                ->byDataSourceId($ds_id)
                ->oldest();

            // Get latest revision and new ones only
             if ($last_revision_id)
                // $revisions_by_ds = $revisions_by_ds->where('id', '>=', $last_revision_id);
                $revisions_by_ds = $revisions_by_ds->where('updated_at', '>=', $last_revision_updated_at);

            $revisions_by_ds = $revisions_by_ds->get()

                // clean up each row
                ->map(function ($rev) {
                    return collect($rev->toArray())
                        ->filter(function ($item, $key) {
                            return $this->clean($item, $key);
                        });
                });

            // Convert to Support\Collection
            if ($revisions_by_ds)
                return $this->mix($revisions_by_ds);
            else
                return null;

            // Sort by order of operations (closed > revoked > active)
        })->filter(function ($revision) {
            return $revision;
        })
        // ->sortBy(function ($revision) use ($SORT) {
        //     // return array_search($revision->get('status'), $SORT);
        //     return array_search($revision->get('data_source_id'), $SORT);
        // });
        ->sortBy('updated_at');

        if($latest_revisions){
            $latest_revisions = $this->managingDatasourcesPriorities($latest_revisions);
        }
        return $latest_revisions;
    }


    // we put the onsis_items to the top of the collection because during the mixing the priority will be to the bottom items
    public function managingDatasourcesPriorities($revisions)
    {
        $onsis_ds = DataSource::where('name','onsis_all_schools')->first();
        $old_onsis_ds = DataSource::where('name','onsis_all_schools_old')->first();
        $onsis_items = new Collection();
        foreach ($revisions as $key => $value) {
            if ($value["data_source_id"] == $onsis_ds->id || $value["data_source_id"] == $old_onsis_ds->id) {
                $onsis_items->push($value);
                $revisions->forget($key);      
            }
        }

        // if(!empty($onsis_items)) $revisions->push(...$onsis_items); //adding onsis items to the end of collection
        if(!empty($onsis_items)) $revisions = $onsis_items->merge($revisions); //adding onsis items to the top of collection
        return $revisions;

    }

    
}
