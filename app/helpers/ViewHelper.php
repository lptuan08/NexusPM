<?php
namespace App\helpers;

class ViewHelper {
    /**
     * Xây dựng URL ảnh đại diện đồng nhất
     */
    public static function buildAvatar(?string $avatar, string $name = 'User', int $size = 80): string {
        if (!empty($avatar) && file_exists(APPROOT . '/public/uploads/avatars/' . $avatar)) {
            return URLROOT . '/uploads/avatars/' . rawurlencode($avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=E2E8F0&color=0F172A&rounded=true&size=' . $size;
    }

    public static function formatPriority(string $priority): array {
        return match($priority) {
            'urgent', 'high' => ['text' => 'Cao', 'class' => 'priority-high'],
            'medium' => ['text' => 'Trung bình', 'class' => 'priority-medium'],
            default => ['text' => 'Thấp', 'class' => 'priority-low'],
        };
    }
}