# Tài liệu Cơ sở dữ liệu - Ứng dụng Quản lý Công việc Di động

## 1. Giới thiệu

Tài liệu này mô tả chi tiết cấu trúc cơ sở dữ liệu của ứng dụng Quản lý Công việc Di động. Cơ sở dữ liệu được thiết kế để hỗ trợ các tính năng chính của ứng dụng bao gồm quản lý người dùng, quản lý công việc cá nhân và nhóm, quản lý tài liệu, và trò chuyện nhóm.

## 2. Tổng quan về Cơ sở dữ liệu

Cơ sở dữ liệu được tổ chức thành 5 nhóm chính:
1. Quản lý người dùng và xác thực
2. Quản lý công việc cá nhân
3. Quản lý nhóm và công việc nhóm
4. Quản lý tài liệu
5. Trò chuyện nhóm và thông báo

## 3. Chi tiết các Bảng

### 3.1. Quản lý Người dùng và Xác thực

#### 3.1.1. Bảng `users`
Lưu trữ thông tin người dùng trong hệ thống.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| name | varchar | Tên người dùng |
| email | varchar | Email người dùng (duy nhất) |
| password | varchar | Mật khẩu đã được mã hóa (có thể null nếu đăng nhập bằng Google) |
| phone | varchar | Số điện thoại (có thể null) |
| email_verified_at | timestamp | Thời điểm xác thực email (có thể null) |
| google_id | varchar | ID Google của người dùng (có thể null, duy nhất) |
| avatar | varchar | URL avatar (có thể null) |
| remember_token | varchar | Token ghi nhớ đăng nhập (có thể null) |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |

#### 3.1.2. Bảng `biometric_auth`
Lưu trữ thông tin xác thực sinh trắc học cho người dùng.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| user_id | bigint | Khóa ngoại tham chiếu đến bảng users |
| device_id | varchar | ID thiết bị |
| biometric_token | varchar | Token sinh trắc học |
| last_used_at | timestamp | Thời điểm sử dụng cuối cùng |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |

### 3.2. Quản lý Công việc Cá nhân

#### 3.2.1. Bảng `personal_tasks`
Lưu trữ thông tin về các công việc cá nhân của người dùng.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| user_id | bigint | Khóa ngoại tham chiếu đến bảng users |
| title | varchar | Tiêu đề công việc |
| description | text | Mô tả công việc (có thể null) |
| deadline | datetime | Thời hạn hoàn thành (có thể null) |
| priority | int | Độ ưu tiên (có thể null) |
| status | enum | Trạng thái công việc ('pending', 'in_progress', 'completed', 'overdue') |
| order | int | Thứ tự sắp xếp (mặc định: 0) |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |
