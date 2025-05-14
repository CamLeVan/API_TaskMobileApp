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

### 3.3. Quản lý Nhóm và Công việc Nhóm

#### 3.3.1. Bảng `teams`
Lưu trữ thông tin về các nhóm trong hệ thống.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| name | varchar | Tên nhóm |
| description | text | Mô tả nhóm (có thể null) |
| created_by | bigint | Khóa ngoại tham chiếu đến bảng users (người tạo nhóm) |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |

#### 3.3.2. Bảng `team_members`
Lưu trữ thông tin về thành viên của các nhóm.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| team_id | bigint | Khóa ngoại tham chiếu đến bảng teams |
| user_id | bigint | Khóa ngoại tham chiếu đến bảng users |
| role | enum | Vai trò trong nhóm ('manager', 'member') |
| joined_at | timestamp | Thời điểm tham gia nhóm |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |

#### 3.3.3. Bảng `team_role_history`
Lưu trữ lịch sử thay đổi vai trò của thành viên trong nhóm.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| team_id | bigint | Khóa ngoại tham chiếu đến bảng teams |
| user_id | bigint | Khóa ngoại tham chiếu đến bảng users |
| changed_by | bigint | Khóa ngoại tham chiếu đến bảng users (người thay đổi vai trò) |
| old_role | varchar | Vai trò cũ |
| new_role | varchar | Vai trò mới |
| reason | text | Lý do thay đổi (có thể null) |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |

#### 3.3.4. Bảng `team_invitations`
Lưu trữ thông tin về lời mời tham gia nhóm.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| team_id | bigint | Khóa ngoại tham chiếu đến bảng teams |
| created_by | bigint | Khóa ngoại tham chiếu đến bảng users (người gửi lời mời) |
| email | varchar | Email người được mời |
| role | enum | Vai trò được mời ('manager', 'member') |
| token | varchar(64) | Token xác thực (duy nhất) |
| status | enum | Trạng thái lời mời ('pending', 'accepted', 'rejected', 'expired') |
| expires_at | timestamp | Thời điểm hết hạn lời mời |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |

#### 3.3.5. Bảng `team_tasks`
Lưu trữ thông tin về các công việc của nhóm.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| team_id | bigint | Khóa ngoại tham chiếu đến bảng teams |
| created_by | bigint | Khóa ngoại tham chiếu đến bảng users (người tạo công việc) |
| title | varchar | Tiêu đề công việc |
| description | text | Mô tả công việc (có thể null) |
| deadline | datetime | Thời hạn hoàn thành (có thể null) |
| priority | int | Độ ưu tiên (có thể null) |
| status | enum | Trạng thái công việc ('pending', 'in_progress', 'completed', 'overdue') |
| order | int | Thứ tự sắp xếp (mặc định: 0) |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |

#### 3.3.6. Bảng `team_task_assignments`
Lưu trữ thông tin về việc phân công công việc cho thành viên nhóm.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| team_task_id | bigint | Khóa ngoại tham chiếu đến bảng team_tasks |
| assigned_to | bigint | Khóa ngoại tham chiếu đến bảng users (người được phân công) |
| status | enum | Trạng thái công việc ('pending', 'in_progress', 'completed') |
| progress | int | Tiến độ hoàn thành (0-100) |
| assigned_at | timestamp | Thời điểm phân công |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |

#### 3.3.7. Bảng `subtasks`
Lưu trữ thông tin về các công việc con (subtasks).

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| taskable_type | varchar | Loại công việc cha (polymorphic) |
| taskable_id | bigint | ID công việc cha (polymorphic) |
| title | varchar | Tiêu đề công việc con |
| completed | boolean | Trạng thái hoàn thành (mặc định: false) |
| order | int | Thứ tự sắp xếp (mặc định: 0) |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |

### 3.4. Quản lý Tài liệu

#### 3.4.1. Bảng `document_folders`
Lưu trữ thông tin về các thư mục tài liệu.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| name | varchar | Tên thư mục |
| description | text | Mô tả thư mục (có thể null) |
| parent_id | bigint | Khóa ngoại tham chiếu đến bảng document_folders (thư mục cha, có thể null) |
| team_id | bigint | Khóa ngoại tham chiếu đến bảng teams |
| created_by | bigint | Khóa ngoại tham chiếu đến bảng users (người tạo thư mục) |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |
| deleted_at | timestamp | Thời điểm xóa bản ghi (soft delete, có thể null) |

#### 3.4.2. Bảng `documents`
Lưu trữ thông tin về các tài liệu.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| name | varchar | Tên tài liệu |
| description | text | Mô tả tài liệu (có thể null) |
| file_path | varchar | Đường dẫn đến file |
| thumbnail_path | varchar | Đường dẫn đến thumbnail (có thể null) |
| file_type | varchar | Loại file |
| file_size | bigint | Kích thước file |
| folder_id | bigint | Khóa ngoại tham chiếu đến bảng document_folders (có thể null) |
| team_id | bigint | Khóa ngoại tham chiếu đến bảng teams |
| uploaded_by | bigint | Khóa ngoại tham chiếu đến bảng users (người tải lên) |
| access_level | enum | Mức độ truy cập ('public', 'team', 'private', 'specific_users') |
| current_version | int | Phiên bản hiện tại (mặc định: 1) |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |
| deleted_at | timestamp | Thời điểm xóa bản ghi (soft delete, có thể null) |

#### 3.4.3. Bảng `document_versions`
Lưu trữ thông tin về các phiên bản của tài liệu.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| document_id | bigint | Khóa ngoại tham chiếu đến bảng documents |
| version_number | int | Số phiên bản |
| file_path | varchar | Đường dẫn đến file |
| thumbnail_path | varchar | Đường dẫn đến thumbnail (có thể null) |
| file_size | bigint | Kích thước file |
| created_by | bigint | Khóa ngoại tham chiếu đến bảng users (người tạo phiên bản) |
| version_note | text | Ghi chú phiên bản (có thể null) |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |

#### 3.4.4. Bảng `document_user_permissions`
Lưu trữ thông tin về quyền truy cập tài liệu của người dùng.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| document_id | bigint | Khóa ngoại tham chiếu đến bảng documents |
| user_id | bigint | Khóa ngoại tham chiếu đến bảng users |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |

### 3.5. Trò chuyện Nhóm và Thông báo

#### 3.5.1. Bảng `group_chat_messages`
Lưu trữ thông tin về các tin nhắn trong trò chuyện nhóm.

| Cột | Kiểu dữ liệu | Mô tả |
|-----|--------------|-------|
| id | bigint | Khóa chính, tự động tăng |
| team_id | bigint | Khóa ngoại tham chiếu đến bảng teams |
| sender_id | bigint | Khóa ngoại tham chiếu đến bảng users (người gửi) |
| message | text | Nội dung tin nhắn (có thể null) |
| file_url | varchar | URL file đính kèm (có thể null) |
| status | varchar | Trạng thái tin nhắn (mặc định: 'sent') |
| client_temp_id | varchar | ID tạm thời từ client (có thể null) |
| reply_to_id | bigint | Khóa ngoại tham chiếu đến bảng group_chat_messages (tin nhắn được trả lời, có thể null) |
| created_at | timestamp | Thời điểm tạo bản ghi |
| updated_at | timestamp | Thời điểm cập nhật bản ghi |