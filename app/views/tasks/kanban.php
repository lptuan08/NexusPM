<style>
        /* Cài đặt màu nền chung của trang */
        body {
            background-color: #f4f6f8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Tùy chỉnh thanh cuộn ngang nếu bảng quá rộng */
        .kanban-board-container {
            overflow-x: auto;
            padding-bottom: 20px;
        }

        .kanban-board {
            display: flex;
            gap: 1.5rem;
            padding: 1.5rem;
            min-width: 1100px;
            /* Đảm bảo giao diện không bị nát khi thu nhỏ */
            align-items: flex-start;
        }

        /* Định dạng các cột Kanban */
        .kanban-col {
            flex: 1;
            min-width: 320px;
            max-width: 380px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Định dạng Header của mỗi cột (Viên thuốc) */
        .kanban-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            border-radius: 50px;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        /* Màu sắc Header theo trạng thái */
        .header-progress {
            background-color: #5d48db;
        }

        .header-reviewed {
            background-color: #f5a623;
        }

        .header-completed {
            background-color: #2ed573;
        }

        /* Vòng tròn đếm số lượng trên header */
        .header-count {
            background-color: #fff;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            margin-right: 10px;
        }

        .header-progress .header-count {
            color: #5d48db;
        }

        .header-reviewed .header-count {
            color: #f5a623;
        }

        .header-completed .header-count {
            color: #2ed573;
        }

        /* Nút thêm (+) trên header */
        .header-add-btn {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .header-add-btn:hover {
            opacity: 1;
        }

        /* Vùng chứa các thẻ để có thể thả vào (Dropzone) */
        .kanban-cards {
            min-height: 200px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Thẻ Task (Card) */
        .task-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            cursor: grab;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .task-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .task-card:active {
            cursor: grabbing;
        }

        /* Khi thẻ đang được kéo */
        .sortable-ghost {
            opacity: 0.4;
            background-color: #f8f9fa;
        }

        .sortable-drag {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
            cursor: grabbing !important;
        }

        /* Định dạng Tag/Nhãn */
        .task-tag {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 0.75rem;
        }

        .tag-important {
            color: #6a5acd;
            background-color: rgba(106, 90, 205, 0.1);
        }

        .tag-meh {
            color: #9b59b6;
            background-color: rgba(155, 89, 182, 0.1);
        }

        .tag-ok {
            color: #f39c12;
            background-color: rgba(243, 156, 18, 0.1);
        }

        .tag-high {
            color: #e74c3c;
            background-color: rgba(231, 76, 60, 0.1);
        }

        .tag-low {
            color: #2ecc71;
            background-color: rgba(46, 204, 113, 0.1);
        }

        .tag-unknown {
            color: #f1c40f;
            background-color: rgba(241, 196, 15, 0.1);
        }

        .tag-maybe {
            color: #7f8c8d;
            background-color: rgba(127, 140, 141, 0.1);
            border: 1px solid rgba(127, 140, 141, 0.2);
        }

        /* Tiêu đề & Nội dung Card */
        .task-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .task-desc {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 1.2rem;
            line-height: 1.5;
        }

        /* Chân thẻ (Avatar & Thống kê) */
        .task-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Nhóm Avatar xếp chồng lên nhau */
        .avatar-group {
            display: flex;
            align-items: center;
        }

        .avatar-group img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #fff;
            object-fit: cover;
            position: relative;
        }

        .avatar-group img:not(:first-child) {
            margin-left: -10px;
        }

        .avatar-group .more-avatars {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #fff;
            background-color: #f1f2f6;
            color: #5d48db;
            font-size: 0.7rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: -10px;
            position: relative;
            z-index: 10;
        }

        /* Thống kê (Bình luận & Hoàn thành) */
        .task-stats {
            display: flex;
            gap: 0.75rem;
            color: #95a5a6;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .task-stats span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
    </style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/tasks" class="text-decoration-none text-slate-500 hover-text-primary">Công việc</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Bảng Kanban</span>
    </div>
</div>

<div class="container-fluid p-0 kanban-board-container">
        <div class="kanban-board">

            <!-- Cột 1: Đang thực hiện -->
            <div class="kanban-col">
                <div class="kanban-header header-progress">
                    <div class="d-flex align-items-center">
                        <span class="header-count">25</span>
                        <span>Đang thực hiện</span>
                    </div>
                    <a href="<?= URLROOT ?>/tasks/create" class="header-add-btn"><i data-lucide="plus"></i></a>
                </div>

                <div class="kanban-cards" id="col-progress">
                    <!-- Thẻ Task 1 -->
                    <div class="task-card">
                        <span class="task-tag tag-important">Quan trọng</span>
                        <h5 class="task-title">Thiết kế UI/UX trong thời đại AI</h5>
                        <p class="task-desc">Lorem ipsum dolor sit amet, libre unst consectetur adipiscing elit.</p>
                        <div class="task-footer">
                            <div class="avatar-group">
                                <img src="https://i.pravatar.cc/150?img=11" alt="User 1" style="z-index: 3;">
                                <img src="https://i.pravatar.cc/150?img=12" alt="User 2" style="z-index: 2;">
                            </div>
                            <div class="task-stats">
                                <span><i data-lucide="message-square" size="16"></i> 11</span>
                                <span><i data-lucide="check-circle" size="16"></i> 187</span>
                            </div>
                        </div>
                    </div>

                    <!-- Thẻ Task 2 -->
                    <div class="task-card">
                        <span class="task-tag tag-meh">Bình thường</span>
                        <h5 class="task-title">Thiết kế Website Responsive cho 23 khách hàng khác</h5>
                        <p class="task-desc">Lorem ipsum dolor sit amet, libre unst consectetur adipiscing elit.</p>
                        <div class="task-footer">
                            <div class="avatar-group">
                                <img src="https://i.pravatar.cc/150?img=33" alt="User" style="z-index: 4;">
                                <img src="https://i.pravatar.cc/150?img=44" alt="User" style="z-index: 3;">
                                <img src="https://i.pravatar.cc/150?img=5" alt="User" style="z-index: 2;">
                                <div class="more-avatars">+3</div>
                            </div>
                            <div class="task-stats">
                                <span><i data-lucide="message-square" size="16"></i> 32</span>
                                <span><i data-lucide="check-circle" size="16"></i> 115</span>
                            </div>
                        </div>
                    </div>

                    <!-- Thẻ Task 3 -->
                    <div class="task-card">
                        <span class="task-tag tag-ok">Ổn</span>
                        <h5 class="task-title">Viết bài Copywriting (Ưu tiên thấp 😅)</h5>
                        <p class="task-desc">Lorem ipsum dolor sit amet, libre unst consectetur adipiscing elit.</p>
                        <div class="task-footer">
                            <div class="avatar-group">
                                <img src="https://i.pravatar.cc/150?img=6" alt="User" style="z-index: 2;">
                                <img src="https://i.pravatar.cc/150?img=7" alt="User" style="z-index: 1;">
                            </div>
                            <div class="task-stats">
                                <span><i data-lucide="message-square" size="16"></i> 987</span>
                                <span><i data-lucide="check-circle" size="16"></i> 21,8K</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột 2: Đã đánh giá -->
            <div class="kanban-col">
                <div class="kanban-header header-reviewed">
                    <div class="d-flex align-items-center">
                        <span class="header-count">8</span>
                        <span>Đã đánh giá</span>
                    </div>
                    <a href="<?= URLROOT ?>/tasks/create" class="header-add-btn"><i data-lucide="plus"></i></a>
                </div>

                <div class="kanban-cards" id="col-reviewed">
                    <!-- Thẻ Task 4 -->
                    <div class="task-card">
                        <span class="task-tag tag-important">Quan trọng</span>
                        <h5 class="task-title">Xác nhận luồng người dùng cho ứng dụng tài chính</h5>
                        <p class="task-desc">Lorem ipsum dolor sit amet, libre unst consectetur adipiscing elit.</p>
                        <div class="task-footer">
                            <div class="avatar-group">
                                <img src="https://i.pravatar.cc/150?img=8" alt="User">
                            </div>
                            <div class="task-stats">
                                <span><i data-lucide="message-square" size="16"></i> 8</span>
                                <span><i data-lucide="check-circle" size="16"></i> 112</span>
                            </div>
                        </div>
                    </div>

                    <!-- Thẻ Task 5 -->
                    <div class="task-card">
                        <span class="task-tag tag-important">Quan trọng</span>
                        <h5 class="task-title">Luồng wireframe ứng dụng chăm sóc sức khỏe 🤯</h5>
                        <p class="task-desc">Lorem ipsum dolor sit amet, libre unst consectetur adipiscing elit.</p>
                        <div class="task-footer">
                            <div class="avatar-group">
                                <img src="https://i.pravatar.cc/150?img=9" alt="User" style="z-index: 4;">
                                <img src="https://i.pravatar.cc/150?img=10" alt="User" style="z-index: 3;">
                                <img src="https://i.pravatar.cc/150?img=11" alt="User" style="z-index: 2;">
                                <div class="more-avatars">+3</div>
                            </div>
                            <div class="task-stats">
                                <span><i data-lucide="message-square" size="16"></i> 221</span>
                                <span><i data-lucide="check-circle" size="16"></i> 87,2k</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột 3: Đã hoàn thành -->
            <div class="kanban-col">
                <div class="kanban-header header-completed">
                    <div class="d-flex align-items-center">
                        <span class="header-count">2</span>
                        <span>Hoàn thành</span>
                    </div>
                    <a href="<?= URLROOT ?>/tasks/create" class="header-add-btn"><i data-lucide="plus"></i></a>
                </div>

                <div class="kanban-cards" id="col-completed">
                    <!-- Thẻ Task 6 -->
                    <div class="task-card">
                        <span class="task-tag tag-high">Ưu tiên cao</span>
                        <h5 class="task-title">Thiết kế UI/UX trong thời đại AI</h5>
                        <p class="task-desc">Lorem ipsum dolor sit amet, libre unst consectetur adipiscing elit.</p>
                        <div class="task-footer">
                            <div class="avatar-group">
                                <img src="https://i.pravatar.cc/150?img=12" alt="User" style="z-index: 2;">
                                <img src="https://i.pravatar.cc/150?img=13" alt="User" style="z-index: 1;">
                            </div>
                            <div class="task-stats">
                                <span><i data-lucide="message-square" size="16"></i> 108k</span>
                                <span><i data-lucide="check-circle" size="16"></i> <i data-lucide="check" size="16" class="text-success"></i></span>
                            </div>
                        </div>
                    </div>

                    <!-- Thẻ Task 7 -->
                    <div class="task-card">
                        <span class="task-tag tag-low">Ưu tiên thấp</span>
                        <h5 class="task-title">Thiết kế UI/UX trong thời đại AI</h5>
                        <p class="task-desc">Lorem ipsum dolor sit amet, libre unst consectetur adipiscing elit.</p>
                        <div class="task-footer">
                            <div class="avatar-group">
                                <img src="https://i.pravatar.cc/150?img=14" alt="User" style="z-index: 3;">
                                <img src="https://i.pravatar.cc/150?img=15" alt="User" style="z-index: 2;">
                                <img src="https://i.pravatar.cc/150?img=16" alt="User" style="z-index: 1;">
                            </div>
                            <div class="task-stats">
                                <span><i data-lucide="message-square" size="16"></i> 17</span>
                            </div>
                        </div>
                    </div>

                    <!-- Thẻ Task 8 -->
                    <div class="task-card">
                        <span class="task-tag tag-unknown">Không rõ</span>
                        <h5 class="task-title">Thiết kế UI/UX trong thời đại AI</h5>
                        <p class="task-desc">Lorem ipsum dolor sit amet, libre unst consectetur adipiscing elit.</p>
                        <div class="task-footer">
                            <div class="avatar-group">
                                <img src="https://i.pravatar.cc/150?img=17" alt="User" style="z-index: 4;">
                                <img src="https://i.pravatar.cc/150?img=18" alt="User" style="z-index: 3;">
                                <img src="https://i.pravatar.cc/150?img=19" alt="User" style="z-index: 2;">
                                <img src="https://i.pravatar.cc/150?img=20" alt="User" style="z-index: 1;">
                            </div>
                            <div class="task-stats">
                                <span><i data-lucide="message-square" size="16"></i> 888</span>
                            </div>
                        </div>
                    </div>

                    <!-- Thẻ Task rỗng để demo -->
                    <div class="task-card">
                        <span class="task-tag tag-maybe">Có thể quan trọng</span>
                        <h5 class="task-title">Nghiên cứu thị trường mới</h5>
                        <p class="task-desc">Phân tích các đối thủ cạnh tranh.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Thư viện SortableJS cho tính năng Kéo Thả (Drag & Drop) -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <script>
        // Khởi tạo SortableJS cho các cột để có thể kéo thả qua lại
        document.addEventListener("DOMContentLoaded", function() {
            const columns = document.querySelectorAll('.kanban-cards');

            columns.forEach(col => {
                new Sortable(col, {
                    group: 'shared', // Cho phép kéo từ cột này sang cột khác cùng nhóm 'shared'
                    animation: 150, // Thời gian hiệu ứng mượt mà (ms)
                    ghostClass: 'sortable-ghost', // CSS class khi một thẻ đang được giữ và di chuyển
                    dragClass: 'sortable-drag', // CSS class cho thẻ đang bám theo con trỏ chuột
                    easing: "cubic-bezier(1, 0, 0, 1)",

                    // Sự kiện khi thả thẻ thành công có thể xử lý logic lưu Database ở đây
                    onEnd: function(evt) {
                        console.log('Đã di chuyển một task sang vị trí mới!');
                        // evt.to;    // Cột HTML đích
                        // evt.from;  // Cột HTML nguồn
                        // evt.oldIndex;  // Vị trí cũ
                        // evt.newIndex;  // Vị trí mới
                    },
                });
            });
        });
    </script>