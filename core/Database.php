

<?php

// Truy vấn (query) có 5 bước:
// B1: Tiếp nhận yêu cầu tại Controller (Controller gọi model tương ứng)
// B2: Định ngĩa câu lệnh SQL (Việc này xử lý ở Model)
// B3: Chuẩn bị câu lệnh (prepare)
// B4: Thực thi và truyền tham số - Execute
class Database
{
    private $__conn;

    function __construct()
    {
        $db_config = Config::load('database');
        $this->__conn = Connection::getInstance($db_config);
    }

    /**
     * Hàm thực thi câu lệnh SQL (Dùng cho cả SELECT, INSERT, UPDATE, DELETE)
     */
    public function query($sql, $params = [])
    {
        try {

            $statement = $this->__conn->prepare($sql); // Kiểm tra câu lệnh SQL trước khi thực thi
            $statement->execute($params);
            return $statement;
        } catch (PDOException $e) {
            throw new Exception("Lỗi truy vấn: " . $e->getMessage(), 500);
        }
    }

    /**
     * Hàm Insert dữ liệu theo mảng
     * @param string $table Tên bảng
     * @param array $data Mảng dữ liệu ['cot' => 'gia_tri']
     */
    public function insert($table, $data)
    {
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = ":" . implode(',:', $keys);

        $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        return $this->query($sql, $data);
    }

    /**
     * Hàm Update dữ liệu theo ID
     * @param string $table Tên bảng
     * @param array $data Mảng dữ liệu cần cập nhật ['cot' => 'gia_tri']
     * @param string $condition Chuỗi điều kiện WHERE (ví dụ: "id = :id")
     * @param array $conditionParams Mảng tham số cho điều kiện WHERE (ví dụ: ['id' => $id])
     */
    public function update($table, $data, $condition, $conditionParams = []) // tại sao lại có conditionParams? Giải thích
    {
        $updateStr = "";
        foreach ($data as $key => $value) {
            $updateStr .= "$key=:$key,";
        }
        $updateStr = rtrim($updateStr, ',');

        // Gộp dữ liệu update và tham số điều kiện WHERE
        $params = array_merge($data, $conditionParams);

        $sql = "UPDATE $table SET $updateStr WHERE $condition";
        return $this->query($sql, $params);
    }

    /**
     * Hàm Delete dữ liệu
     * @param string $table Tên bảng
     * @param string $condition Chuỗi điều kiện WHERE (ví dụ: "id = :id")
     * @param array $conditionParams Mảng tham số cho điều kiện WHERE (ví dụ: ['id' => $id])
     */
    public function delete($table, $condition, $conditionParams = [])
    {
        $sql = "DELETE FROM $table WHERE $condition";
        return $this->query($sql, $conditionParams);
    }

    /**
     * Lấy ID vừa insert cuối cùng
     */
    public function lastInsertId()
    {
        return $this->__conn->lastInsertId();
    }

    // sử dụng transaction: begin -> commit -> rollback lỗi lỗi
    public function beginTransaction()
    {
        $this->__conn->beginTransaction();
    }

    public function commit()
    {
        $this->__conn->commit();
    }

    public function rollBack()
    {
        $this->__conn->rollBack();
    }
    
}
