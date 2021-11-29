<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;


class RevokedSchoolsCrawling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:revoked-schools';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawling revoked schools';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $request = Request::create('/crawl/revoked', 'GET');
        $response = app()->handle($request);
    }
}
