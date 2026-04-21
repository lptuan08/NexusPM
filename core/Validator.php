<?php

/**
 * Lớp Validator - Xử lý kiểm tra tính hợp lệ của dữ liệu đầu vào
 * 
 * Lớp này cung cấp các phương thức để kiểm tra các quy tắc phổ biến như:
 * - Bắt buộc nhập (Required)
 * - Độ dài tối thiểu (Min length)
 * - Định dạng Email
 * - So khớp dữ liệu (Matches)
 */
class Validator
{
    protected $errors = []; // Mảng lưu trữ các lỗi phát sinh trong quá trình validate

    /**
     * Kiểm tra trường bắt buộc không được để trống
     * @param string $field Tên định danh của trường (key trong mảng lỗi)
     * @param mixed $value Giá trị cần kiểm tra
     * @param string $label Tên hiển thị thân thiện (ví dụ: 'Họ tên')
     * @return bool Trả về true nếu hợp lệ, false nếu rỗng
     */

    public function required($field, $value, $label = '')
    {
        if (empty(trim($value))) {
            $this->errors[$field] = ($label ?: $field) . " không được để trống";
            return false;
        }
        return true;
    }

    /**
     * Kiểm tra độ dài tối thiểu của chuỗi
     * @param string $field Tên định danh của trường
     * @param string $value Chuỗi cần kiểm tra
     * @param int $min Số ký tự tối thiểu yêu cầu
     */
    public function min($field, $value, $min, $label = '')
    {
        if (strlen(trim($value)) < $min) {
            $this->errors[$field] = ($label ?: $field) . " phải có ít nhất {$min} ký tự";
            return false;
        }
        return true;
    }

    /**
     * Kiểm tra xem chuỗi có phải là định dạng email hợp lệ hay không
     */
    public function email($field, $value, $label = '')
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = ($label ?: $field) . " không đúng định dạng email";
            return false;
        }
        return true;
    }

    /**
     * Kiểm tra hai giá trị có khớp nhau không (Dùng cho xác nhận mật khẩu)
     * @param string $matchValue Giá trị thứ nhất
     * @param string $matchLabel Tên của trường dùng để so sánh (Ví dụ: 'Mật khẩu')
     */
    public function matches($field, $value, $matchValue, $matchLabel, $label = '')
    {
        if ($value !== $matchValue) {
            $this->errors[$field] = ($label ?: $field) . " không khớp với {$matchLabel}";
            return false;
        }
        return true;
    }

    /**
     * Trả về danh sách tất cả các lỗi đã phát hiện
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Kiểm tra tính hợp lệ của file ảnh (Rút gọn và đồng bộ)
     * @param string $field Tên của input file (ví dụ: 'avatar')
     * @param string $label Tên hiển thị lỗi
     * @param int $maxMb Dung lượng tối đa (mặc định 2MB)
     */
    public function image($field, $label = 'Ảnh', $maxMb = 2)
    {
        // Nếu không có file tải lên thì bỏ qua (hợp lệ)
        if (!isset($_FILES[$field]) || empty($_FILES[$field]['name'])) {
            return true;
        }
        // có file tải lên
        $file = $_FILES[$field];
        //   'name' => 
        //   'full_path' => string '597892222_1263747742253384_7360865321904570280_n.jpg' (length=52)
        //   'type' => string 'image/jpeg' (length=10)
        //   'tmp_name' => string 'C:\wamp64\tmp\php53F2.tmp' (length=25)
        //   'error' => 0 thành công | UPLOAD_ERR_OK lỗi
        //   'size' => int 355367 -> kích thước ảnh
    
        // 1. Kiểm tra lỗi upload cơ bản từ PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[$field] = "$label tải lên thất bại (Mã lỗi: {$file['error']})";
            return false;
        }

        // 2. Kiểm tra dung lượng
        if ($file['size'] > ($maxMb * 1024 * 1024)) {
            $this->errors[$field] = "$label không được vượt quá {$maxMb}MB";
            return false;
        }

        // 3. Kiểm tra định dạng qua MIME type và nội dung thực tế
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        
        /**
         * getimagesize(): Đọc nội dung file để lấy kích thước và loại ảnh.
         * Dấu @ để chặn Warning nếu file tải lên không phải là ảnh thật.
         * Trả về false nếu không phải ảnh, ngược lại trả về mảng thông tin.
         */
        $imageInfo = @getimagesize($file['tmp_name']);  
        //@ kiểm soát lỗi | getimagesize đọc file ảnh trả về thông tin
                //   0 => int 1920
                //   1 => int 1080
                //   2 => int 2
                //   3 => string 'width="1920" height="1080"' (length=26)
                //   'bits' => int 8
                //   'channels' => int 3
                //   'mime' => string 'image/jpeg' (length=10)

        if (!$imageInfo || !in_array($imageInfo['mime'], $allowedMimes)) {
            $this->errors[$field] = "$label phải là định dạng JPG, PNG, GIF hoặc WEBP";
            return false;
        }

        // 4. Kiểm tra kích thước vật lý (Tránh file quá lớn gây treo server)
        if ($imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
            $this->errors[$field] = "$label có độ phân giải quá lớn (tối đa 5000px)";
            return false;
        }

        return true;
    }






    /**
     * Kiểm tra xem dữ liệu có vượt qua tất cả các bài kiểm tra không
     */
    public function passes()
    {
        return empty($this->errors);
    }
}
