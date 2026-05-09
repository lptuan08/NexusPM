<?php

namespace App\controllers\admin;

use App\core\Controller;
use App\core\View;
use App\core\Response;
use App\helpers\Helper;
use App\models\JobModel;


class JobController extends Controller
{
    protected JobModel $jobModal;
    public function __construct()
    {
        parent::__construct();
        $this->jobModal = $this->model('JobModel');
    }
    public function list()
    {
        $data = $this->jobModal->getJobAll();
        View::render('admin/settings/job_titles', [
            'titles' => $data,
            'pageTitle' => 'Quản lý công việc'
        ]);
    }
    public function store()
    {
        $body = $this->request->getBody();
        $data = $this->jobModal->getJobAll();
        if (!$this->validateForm($body)) {
            View::render('admin/settings/job_titles', [
                'titles' => $data,
                'pageTitle' => 'Quản lý công việc',
                'errors' => $this->validator->getErrors(),
                'old' => $body
            ]);
            return;
        }

        if (empty($body['id'])) {
            // create
            if ($this->jobModal->createJob($body)) {
                Helper::setFlash('success', "Thêm chức danh thành công");
                Response::redirect(URLROOT . '/settings/job');
            } else {
                Helper::setFlash('success', "Thêm chức danh không thành công");
                Response::redirect(URLROOT . '/settings/job');
            }
        } else {
            //update
            if ($this->jobModal->updateJob($body['id'], $body)) {
                Helper::setFlash('success', "Cập nhật chức danh thành công");
                Response::redirect(URLROOT . '/settings/job');
            } else {
                Helper::setFlash('success', "Cập nhật chức danh không thành công");
                Response::redirect(URLROOT . '/settings/job');
            }
        }
    }

    public function validateForm(array $body)
    {
        $this->validator->required('name', $body['name'], "Tên chức danh ");
        $this->validator->max('name', $body['name'], 45, "Tên chức danh ");

        return $this->validator->passes();
    }
    public function deleted($id)
    {
        if ($this->jobModal->deleteJob($id)) {
            Helper::setFlash('success', "Xóa chức danh thành công");
            Response::redirect(URLROOT . '/settings/job');
        }else{
            Helper::setFlash('success', "Xóa chức danh không thành công");
            Response::redirect(URLROOT . '/settings/job');
        }
    }
}
