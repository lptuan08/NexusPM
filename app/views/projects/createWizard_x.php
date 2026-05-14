<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Fetch API Example</title>
    <style>
        #display-area {
            padding: 20px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>

    <h2>Dữ liệu từ Wizard Data:</h2>
    <div id="display-area">Đang tải dữ liệu...</div>

    <script>
        async function getWizardData() {
            const displayArea = document.getElementById('display-area');
            const apiUrl = '<?= URLROOT ?>/api/projects/wizard-data';
            
            console.log(apiUrl);

            try {
                // 1. Gửi yêu cầu lấy dữ liệu
                const response = await fetch(apiUrl);

                // Kiểm tra nếu phản hồi không thành công (vd: 404, 500)
                if (!response.ok) {
                    throw new Error(`Lỗi HTTP! Trạng thái: ${response.status}`);
                }

                // 2. Chuyển đổi dữ liệu nhận được sang định dạng JSON
                const data = await response.json();

                // 3. Hiển thị ra màn hình
                // Sử dụng JSON.stringify để xem toàn bộ cấu trúc dữ liệu dưới dạng chữ
                displayArea.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;

            } catch (error) {
                // Xử lý lỗi nếu API không hoạt động hoặc sai URL
                console.error("Có lỗi xảy ra:", error);
                displayArea.innerHTML = `<p class="error">Không thể lấy dữ liệu: ${error.message}</p>`;
            }
        }

        // Gọi hàm thực thi
        getWizardData();
    </script>
</body>

</html>