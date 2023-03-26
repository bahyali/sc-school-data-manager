<?php

namespace App\Http\Controllers\API;

use App\Models\DataSource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DataSourceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return DataSource::whereNotIn('resource', ['auto_mixer', 'conflict_fixed', 'onsis_all_schools_old'])->paginate();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DataSource  $dataSource
     * @return \Illuminate\Http\Response
     */
    public function show($idOrName)
    {
        $ds = DataSource::find($idOrName);
        if (!$ds)
            $ds = DataSource::where('name', $idOrName)->first();
        return $ds;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DataSource  $dataSource
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DataSource $dataSource)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DataSource  $dataSource
     * @return \Illuminate\Http\Response
     */
    public function destroy(DataSource $dataSource)
    {
        //
    }



    public function findByName($name)
    {
        return DataSource::where('name', $name)->first();
    }
}
