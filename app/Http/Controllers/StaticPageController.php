<?php

namespace Mesa\Http\Controllers;

use Illuminate\Routing\Controller;

class StaticPageController extends Controller
{
    /**
     * Render the haulage page.
     *
     * @return mixed
     */
    public function haulage()
    {
        return view('haulage');
    }
}
