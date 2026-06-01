<?php

namespace App\controllers;

use App\core\Controller;
use App\core\Response;
use App\core\View;
use App\helpers\AuthHelper;
use App\helpers\Helper;
use App\models\UserModel;

class AccountController extends Controller
{
    private UserModel $modelUser;

    /**
     * =============================================================
     * NHOM KHOI TAO
     * =============================================================
     */
    public function __construct()
    {
        parent::__construct();
        $this->modelUser = $this->model('UserModel');
    }

    /**
     * =============================================================
     * NHOM DOI MAT KHAU CA NHAN
     * =============================================================
     */
    public function password()
    {
        View::render('account/change_password', [
            'pageTitle' => 'Đổi mật khẩu',
        ]);
    }

    public function updatePassword()
    {
        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . '/account/password');
        }

        $userId = AuthHelper::id();
        $user = $this->modelUser->getUserById($userId);

        if (!$user) {
            Helper::setFlash('danger', 'Không tìm thấy tài khoản đang đăng nhập.');
            Response::redirect(URLROOT . '/login');
        }

        $body = $this->request->getBody();
        $currentPassword = $body['current_password'] ?? '';
        $newPassword = $body['new_password'] ?? '';
        $confirmPassword = $body['confirm_password'] ?? '';

        if ($this->validator->required('current_password', $currentPassword, 'Mật khẩu hiện tại')) {
            if (!password_verify($currentPassword, $user['password'] ?? '')) {
                $this->validator->addError('current_password', 'Mật khẩu hiện tại chưa chính xác');
            }
        }

        if ($this->validator->required('new_password', $newPassword, 'Mật khẩu mới')) {
            $this->validator->min('new_password', $newPassword, 6, 'Mật khẩu mới');
        }

        if ($this->validator->required('confirm_password', $confirmPassword, 'Xác nhận mật khẩu')) {
            $this->validator->matches('confirm_password', $confirmPassword, $newPassword, 'mật khẩu mới', 'Xác nhận mật khẩu');
        }

        if ($currentPassword !== '' && $newPassword !== '' && $currentPassword === $newPassword) {
            $this->validator->addError('new_password', 'Mật khẩu mới không được trùng với mật khẩu hiện tại');
        }

        if (!$this->validator->passes()) {
            return View::render('account/change_password', [
                'pageTitle' => 'Đổi mật khẩu',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $this->modelUser->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));

        Helper::setFlash('success', 'Đổi mật khẩu thành công.');
        Response::redirect(URLROOT . '/account/password');
    }
}
