<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;


class ClosedSchoolsCrawling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:closed-schools';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawling closed schools';

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
        $request = Request::create('/crawl/closed', 'GET');
        $response = app()->handle($request);
    }
}
