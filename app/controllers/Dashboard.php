<?php

/**
 * Controller Dashboard - Hiển thị trang tổng quan
 */
class Dashboard extends Controller
{
    public function index()
    {
        // In a real application, you would fetch data here
        // e.g., total users, total projects, recent activities, etc.
        View::render('dashboard/index', [
            'title' => 'Tổng quan',
            'extra_css' => 'dashboard'
        ]);
    }
}