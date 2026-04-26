<?php

class ProjectController extends Controller
{
    private $modelProject;

    public function __construct()
    {
        parent::__construct();
        $this->modelProject = $this->model('ProjectModel');
    }

    public function index()
    {
        $perPage = 5;
        $currentPage = (int)$this->request->input('page', 1);
        if ($currentPage < 1) $currentPage = 1;

        $projects = $this->modelProject->getProjectsByPage($currentPage, $perPage);
        $totalProjects = $this->modelProject->count();
        $totalPages = ceil($totalProjects / $perPage);
        $pages = $totalPages > 0 ? range(1, $totalPages) : [];

        View::render('projects/list', [
            'data' => $projects,
            'pageTitle' => 'Quản lý dự án',
            'totalProjects' => $totalProjects,
            'currentPage' => $currentPage,
            'totalPage' => $totalPages,
            'pages' => $pages,
            'perPage' => $perPage
        ]);
    }

    public function show($id)
    {
        $project = $this->modelProject->find($id);
        if (!$project) {
            Response::redirect(URLROOT . '/projects');
        }

        $members = $this->modelProject->getProjectMembers($id);
        $tasks = $this->modelProject->getProjectTasks($id);

        View::render('projects/detail', [
            'project' => $project,
            'members' => $members,
            'tasks' => $tasks,
            'pageTitle' => 'Chi tiết dự án: ' . $project['name']
        ]);
    }

    public function create()
    {
        View::render('projects/create', [
            'pageTitle' => 'Tạo dự án mới',
            'action_url' => URLROOT . '/projects/create'
        ]);
    }

    public function store()
    {
        if ($this->request->isPost()) {
            $data = $this->getFormData();
            
            $this->validator->required('name', $data['name'], 'Tên dự án');
            $this->validator->required('status', $data['status'], 'Trạng thái');

            if (!$this->validator->passes()) {
                return View::render('projects/create', [
                    'errors' => $this->validator->getErrors(),
                    'old' => $this->request->getBody(),
                    'pageTitle' => 'Tạo dự án mới',
                    'action_url' => URLROOT . '/projects/create'
                ]);
            }

            $this->modelProject->create($data);
            Helper::setFlash('success', 'Tạo dự án mới thành công!');
            Response::redirect(URLROOT . '/projects');
        }
    }

    public function edit($id)
    {
        $project = $this->modelProject->find($id);
        if (!$project) Response::redirect(URLROOT . '/projects');

        View::render('projects/create', [
            'project' => $project,
            'pageTitle' => 'Chỉnh sửa dự án',
            'action_url' => URLROOT . "/projects/{$id}/edit"
        ]);
    }

    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->getFormData();
            $this->validator->required('name', $data['name'], 'Tên dự án');

            if (!$this->validator->passes()) {
                return View::render('projects/create', [
                    'project' => $this->modelProject->find($id),
                    'errors' => $this->validator->getErrors(),
                    'old' => $this->request->getBody(),
                    'pageTitle' => 'Chỉnh sửa dự án',
                    'action_url' => URLROOT . "/projects/{$id}/edit"
                ]);
            }

            $this->modelProject->update($id, $data);
            Helper::setFlash('success', 'Cập nhật dự án thành công');
            Response::redirect(URLROOT . '/projects');
        }
    }

    public function delete($id)
    {
        $this->modelProject->delete($id);
        Helper::setFlash('success', 'Đã xóa dự án vào thùng rác');
        Response::redirect(URLROOT . '/projects');
    }

    private function getFormData()
    {
        $body = $this->request->getBody();
        return [
            'name'        => $body['name'] ?? '',
            'description' => $body['description'] ?? '',
            'status'      => $body['status'] ?? 'planning',
            'start_date'  => !empty($body['start_date']) ? $body['start_date'] : null,
            'due_date'    => !empty($body['due_date']) ? $body['due_date'] : null,
        ];
    }
}