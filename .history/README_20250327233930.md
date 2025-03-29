# Task Management API

API backend cho ứng dụng quản lý công việc (Task Management) được xây dựng bằng Laravel.

## Yêu cầu hệ thống

- PHP >= 8.1
- MySQL >= 5.7
- Composer
- Laravel >= 10.0

## Cài đặt

1. Clone repository:
```bash
git clone <repository-url>
cd myapi
```

2. Cài đặt dependencies:
```bash
composer install
```

3. Tạo file .env:
```bash
cp .env.example .env
```

4. Tạo application key:
```bash
php artisan key:generate
```

5. Cấu hình database trong file .env:
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapi
DB_USERNAME=root
DB_PASSWORD=
```

6. Chạy migrations:
```bash
php artisan migrate
```

7. Chạy server:
```bash
php artisan serve
```

## API Endpoints

### Authentication

#### Đăng ký
- **POST** `/api/auth/register`
- Body:
```json
{
    "name": "User Name",
    "email": "user@example.com",
    "password": "password",
    "password_confirmation": "password"
}
```

#### Đăng nhập
- **POST** `/api/auth/login`
- Body:
```json
{
    "email": "user@example.com",
    "password": "password"
}
```

#### Đăng xuất
- **POST** `/api/auth/logout`
- Header: `Authorization: Bearer {token}`

#### Lấy thông tin user
- **GET** `/api/user`
- Header: `Authorization: Bearer {token}`

### Personal Tasks

#### Lấy danh sách công việc
- **GET** `/api/personal-tasks`
- Header: `Authorization: Bearer {token}`

#### Tạo công việc mới
- **POST** `/api/personal-tasks`
- Header: `Authorization: Bearer {token}`
- Body:
```json
{
    "title": "Task Title",
    "description": "Task Description",
    "deadline": "2024-03-27 15:00:00",
    "priority": 1,
    "status": "pending"
}
```

#### Xem chi tiết công việc
- **GET** `/api/personal-tasks/{taskId}`
- Header: `Authorization: Bearer {token}`

#### Cập nhật công việc
- **PUT** `/api/personal-tasks/{taskId}`
- Header: `Authorization: Bearer {token}`
- Body: (tương tự như tạo mới)

#### Xóa công việc
- **DELETE** `/api/personal-tasks/{taskId}`
- Header: `Authorization: Bearer {token}`

### Teams

#### Lấy danh sách nhóm
- **GET** `/api/teams`
- Header: `Authorization: Bearer {token}`

#### Tạo nhóm mới
- **POST** `/api/teams`
- Header: `Authorization: Bearer {token}`
- Body:
```json
{
    "name": "Team Name",
    "description": "Team Description"
}
```

#### Xem chi tiết nhóm
- **GET** `/api/teams/{teamId}`
- Header: `Authorization: Bearer {token}`

#### Cập nhật nhóm
- **PUT** `/api/teams/{teamId}`
- Header: `Authorization: Bearer {token}`
- Body: (tương tự như tạo mới)

#### Xóa nhóm
- **DELETE** `/api/teams/{teamId}`
- Header: `Authorization: Bearer {token}`

### Team Members

#### Lấy danh sách thành viên
- **GET** `/api/teams/{teamId}/members`
- Header: `Authorization: Bearer {token}`

#### Thêm thành viên
- **POST** `/api/teams/{teamId}/members`
- Header: `Authorization: Bearer {token}`
- Body:
```json
{
    "user_id": 1,
    "role": "member"
}
```

#### Xóa thành viên
- **DELETE** `/api/teams/{teamId}/members/{userId}`
- Header: `Authorization: Bearer {token}`

### Team Tasks

#### Lấy danh sách công việc nhóm
- **GET** `/api/teams/{teamId}/tasks`
- Header: `Authorization: Bearer {token}`

#### Tạo công việc nhóm
- **POST** `/api/teams/{teamId}/tasks`
- Header: `Authorization: Bearer {token}`
- Body:
```json
{
    "title": "Task Title",
    "description": "Task Description",
    "deadline": "2024-03-27 15:00:00",
    "priority": 1,
    "status": "pending"
}
```

#### Xem chi tiết công việc nhóm
- **GET** `/api/teams/{teamId}/tasks/{taskId}`
- Header: `Authorization: Bearer {token}`

#### Cập nhật công việc nhóm
- **PUT** `/api/teams/{teamId}/tasks/{taskId}`
- Header: `Authorization: Bearer {token}`
- Body: (tương tự như tạo mới)

#### Xóa công việc nhóm
- **DELETE** `/api/teams/{teamId}/tasks/{taskId}`
- Header: `Authorization: Bearer {token}`

### Team Task Assignments

#### Lấy danh sách phân công
- **GET** `/api/teams/{teamId}/tasks/{taskId}/assignments`
- Header: `Authorization: Bearer {token}`

#### Giao công việc
- **POST** `/api/teams/{teamId}/tasks/{taskId}/assignments`
- Header: `Authorization: Bearer {token}`
- Body:
```json
{
    "user_id": 1,
    "status": "pending"
}
```

#### Cập nhật tiến độ
- **PUT** `/api/teams/{teamId}/tasks/{taskId}/assignments/{assignmentId}`
- Header: `Authorization: Bearer {token}`
- Body:
```json
{
    "status": "in_progress",
    "progress": 50
}
```

#### Xóa phân công
- **DELETE** `/api/teams/{teamId}/tasks/{taskId}/assignments/{assignmentId}`
- Header: `Authorization: Bearer {token}`

### Group Chat

#### Lấy danh sách tin nhắn
- **GET** `/api/teams/{teamId}/chat`
- Header: `Authorization: Bearer {token}`

#### Gửi tin nhắn
- **POST** `/api/teams/{teamId}/chat`
- Header: `Authorization: Bearer {token}`
- Body:
```json
{
    "message": "Message content"
}
```
hoặc
```json
{
    "file_url": "https://example.com/file.pdf"
}
```

#### Đánh dấu đã đọc
- **PUT** `/api/teams/{teamId}/chat/{messageId}/read`
- Header: `Authorization: Bearer {token}`

#### Lấy số tin nhắn chưa đọc
- **GET** `/api/teams/{teamId}/chat/unread`
- Header: `Authorization: Bearer {token}`

## Response Format

Tất cả các response đều có format JSON với cấu trúc:

```json
{
    "data": {}, // hoặc []
    "message": "Success message", // tùy trường hợp
    "status": 200 // HTTP status code
}
```

## Error Handling

Khi có lỗi, API sẽ trả về response với format:

```json
{
    "message": "Error message",
    "errors": {
        "field": ["Error detail"]
    },
    "status": 400 // HTTP status code
}
```

## Authentication

API sử dụng Laravel Sanctum cho authentication. Sau khi đăng nhập thành công, server sẽ trả về token. Client cần gửi token này trong header của mọi request:

```
Authorization: Bearer {token}
```

## Rate Limiting

API có giới hạn 60 requests/phút cho mỗi IP address.
