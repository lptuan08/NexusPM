<?php

namespace App\controllers\admin;

use App\core\Controller;
use App\core\View;


// use App\core\Validator;

class SettingsController extends Controller
{
    public function index()
    {

        View::render(
            'admin/settings/index',
            ['pageTitle' => 'Hệ thống']
        );
    }
}
