<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Integrations;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class XeroController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        dd('xero integration'); // nosemgrep: semgrep.rules.php-debug-helper-outside-tests -- upstream 2.4.7 debt, README-FORK
    }
}
