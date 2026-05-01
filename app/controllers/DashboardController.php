<?php
namespace App\controllers;

use App\core\Controller;
use App\core\View;

/**
 * Controller Dashboard - Hiển thị trang tổng quan
 */
class DashboardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function index()
    {
        // In a real application, you would fetch data here
        // e.g., total users, total projects, recent activities, etc.
        View::render('dashboard/dashboard', [
            'title' => 'Tổng quan',
            'extra_css' => 'dashboard'
        ]);
    }
}