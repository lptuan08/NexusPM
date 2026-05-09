<?php

namespace App\controllers;

use App\core\Controller;
use App\core\Response;
use App\helpers\Helper;

class TaskController extends Controller
{
    public function index()
    {
        Helper::setFlash('warning', 'Chức năng quản lý công việc đang được phát triển.');
        Response::redirect(URLROOT . '/projects');
    }

    public function create()
    {
        $query = $this->request->getQuery();
        Helper::setFlash('warning', 'Chức năng tạo công việc đang được phát triển.');

        if (!empty($query['project_id'])) {
            Response::redirect(URLROOT . '/projects/' . (int)$query['project_id']);
        }

        Response::redirect(URLROOT . '/projects');
    }
}
