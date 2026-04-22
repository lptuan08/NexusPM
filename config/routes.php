<?php
// nhiệm vụ: đường dẫn ảo sẽ trỏ đến đường dẫn thật
$routes['defaut_controller'] = 'Dashboard';
$routes['trang-chu'] = 'Dashboard';

// login
$routes['dang-nhap'] = 'Auth/login';

// router cho user
$routes['nguoi-dung'] = 'Users/getlist';
$routes['nguoi-dung/danh-sach'] = 'Users/getlist';
$routes['nguoi-dung/them-moi'] = 'Users/create';
$routes['nguoi-dung/luu'] = 'Users/store'; // Route xử lý lưu dữ liệu
$routes['nguoi-dung/chinh-sua/(\d+)'] = 'Users/edit/$1';
$routes['nguoi-dung/cap-nhat/(\d+)'] = 'Users/update/$1';
$routes['nguoi-dung/chi-tiet/(\d+)'] = 'Users/show/$1';
$routes['nguoi-dung/xoa/(\d+)'] = 'Users/delete/$1';
$routes['nguoi-dung/check'] = 'Users/check';



// router cho project
$routes['du-an'] = 'projects/getall';
$routes['du-an/danh-sach'] = 'projects/getall';
$routes['du-an/tao-moi'] = 'projects/create';
$routes['du-an/chinh-sua/(\d+)'] = 'projects/edit/$1';
$routes['du-an/chi-tiet/(\d+)'] = 'projects/show/$1';


// 