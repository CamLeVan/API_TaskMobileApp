# Tài liệu API Quản lý Công việc và Chat Nhóm

## Tổng quan
API này được xây dựng trên nền tảng Laravel, cung cấp các endpoints cho ứng dụng Android Jetpack Compose với các chức năng:
- Quản lý công việc cá nhân và nhóm
- Chat nhóm realtime
- Đồng bộ dữ liệu giữa thiết bị và server
- Hỗ trợ hoạt động offline
- Thông báo đẩy
- Phân tích và báo cáo
- Tích hợp lịch
- Giao diện Kanban
- Quản lý công việc con (subtasks)
- Tùy chỉnh giao diện (theme, ngôn ngữ)
- Xác thực sinh trắc học

## Base URL
```
https://api.yourdomain.com/api/v1
```

## Xác thực
API sử dụng Laravel Sanctum để xác thực token-based.

### Đăng ký tài khoản
**Endpoint:** `POST /auth/register`

**Request Body:**
```json
{
  "name": "Nguyen Van A",
  "email": "example@email.com",
  "password": "password123",
  "password_confirmation": "password123",
  "device_id": "unique-device-identifier",
  "device_name": "Pixel 6"
}
```

**Response (201):**
```json
{
  "user": {
    "id": 1,
    "name": "Nguyen Van A",
    "email": "example@email.com",
    "created_at": "2023-05-04T16:18:13.000000Z",
    "updated_at": "2023-05-04T16:18:13.000000Z"
  },
  "token": "1|laravel_sanctum_token_hash",
  "device_id": "unique-device-identifier"
}
```

### Đăng nhập
**Endpoint:** `POST /auth/login`

**Request Body:**
```json
{
  "email": "example@email.com",
  "password": "password123",
  "device_id": "unique-device-identifier",
  "device_name": "Pixel 6"
}
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "Nguyen Van A",
    "email": "example@email.com",
    "avatar": "https://storage.yourdomain.com/avatars/user1.jpg",
    "settings": {
      "theme": "dark",
      "language": "vi",
      "notifications_enabled": true
    }
  },
  "token": "1|laravel_sanctum_token_hash",
  "device_id": "unique-device-identifier"
}
```

### Đăng nhập bằng Google
**Endpoint:** `POST /auth/google`

**Request Body:**
```json
{
  "id_token": "google_id_token",
  "device_id": "unique-device-identifier",
  "device_name": "Pixel 6"
}
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "Nguyen Van A",
    "email": "example@gmail.com",
    "avatar": "https://storage.yourdomain.com/avatars/user1.jpg",
    "google_id": "google_user_id",
    "settings": {
      "theme": "dark",
      "language": "vi",
      "notifications_enabled": true
    }
  },
  "token": "1|laravel_sanctum_token_hash",
  "device_id": "unique-device-identifier",
  "is_new_user": false
}
```

### Đăng xuất
**Endpoint:** `POST /auth/logout`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

**Request Body:**
```json
{
  "device_id": "unique-device-identifier"
}
```

**Response (200):**
```json
{
  "message": "Đăng xuất thành công"
}
```

## Quản lý Công việc Cá nhân

### Lấy danh sách công việc cá nhân
**Endpoint:** `GET /personal-tasks`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

**Query Parameters:**
- `status` (optional): Filter theo trạng thái (todo, in_progress, done)
- `priority` (optional): Filter theo độ ưu tiên (low, medium, high)
- `due_date` (optional): Filter theo ngày hết hạn (YYYY-MM-DD)
- `search` (optional): Tìm kiếm theo tiêu đề hoặc mô tả
- `page` (optional): Số trang, mặc định là 1
- `per_page` (optional): Số item mỗi trang, mặc định là 15

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Hoàn thành báo cáo",
      "description": "Hoàn thành báo cáo quý 2",
      "status": "in_progress",
      "priority": "high",
      "due_date": "2023-05-10",
      "created_at": "2023-05-04T16:18:13.000000Z",
      "updated_at": "2023-05-04T16:18:13.000000Z",
      "user_id": 1,
      "subtasks": [
        {
          "id": 1,
          "task_id": 1,
          "title": "Thu thập dữ liệu",
          "is_completed": true,
          "created_at": "2023-05-04T16:19:13.000000Z",
          "updated_at": "2023-05-04T16:20:13.000000Z"
        }
      ]
    }
  ],
  "links": {
    "first": "https://api.yourdomain.com/api/v1/personal-tasks?page=1",
    "last": "https://api.yourdomain.com/api/v1/personal-tasks?page=5",
    "prev": null,
    "next": "https://api.yourdomain.com/api/v1/personal-tasks?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "https://api.yourdomain.com/api/v1/personal-tasks",
    "per_page": 15,
    "to": 15,
    "total": 75
  }
}
```

### Tạo công việc cá nhân mới
**Endpoint:** `POST /personal-tasks`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "Đọc sách",
  "description": "Đọc sách về Android Development",
  "status": "todo",
  "priority": "medium",
  "due_date": "2023-05-15",
  "subtasks": [
    {
      "title": "Đọc chương 1"
    },
    {
      "title": "Đọc chương 2"
    }
  ]
}
```

**Response (201):**
```json
{
  "data": {
    "id": 4,
    "title": "Đọc sách",
    "description": "Đọc sách về Android Development",
    "status": "todo",
    "priority": "medium",
    "due_date": "2023-05-15",
    "created_at": "2023-05-04T19:00:13.000000Z",
    "updated_at": "2023-05-04T19:00:13.000000Z",
    "user_id": 1,
    "subtasks": [
      {
        "id": 4,
        "task_id": 4,
        "title": "Đọc chương 1",
        "is_completed": false,
        "created_at": "2023-05-04T19:00:13.000000Z",
        "updated_at": "2023-05-04T19:00:13.000000Z"
      },
      {
        "id": 5,
        "task_id": 4,
        "title": "Đọc chương 2",
        "is_completed": false,
        "created_at": "2023-05-04T19:00:13.000000Z",
        "updated_at": "2023-05-04T19:00:13.000000Z"
      }
    ]
  }
}
```

## Quản lý Nhóm

### Lấy danh sách nhóm
**Endpoint:** `GET /teams`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Dự án X",
      "description": "Dự án phát triển ứng dụng quản lý công việc",
      "created_at": "2023-05-01T09:00:00.000000Z",
      "updated_at": "2023-05-05T16:10:00.000000Z",
      "owner": {
        "id": 1,
        "name": "Nguyen Van A",
        "avatar": "https://storage.yourdomain.com/avatars/user1.jpg"
      },
      "members_count": 5,
      "tasks_count": 25
    }
  ]
}
```

### Tạo nhóm mới
**Endpoint:** `POST /teams`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Dự án Y",
  "description": "Dự án phát triển ứng dụng chat",
  "members": [2, 3, 4]
}
```

**Response (201):**
```json
{
  "data": {
    "id": 2,
    "name": "Dự án Y",
    "description": "Dự án phát triển ứng dụng chat",
    "created_at": "2023-05-06T10:00:00.000000Z",
    "updated_at": "2023-05-06T10:00:00.000000Z",
    "owner": {
      "id": 1,
      "name": "Nguyen Van A",
      "avatar": "https://storage.yourdomain.com/avatars/user1.jpg"
    },
    "members": [
      {
        "id": 1,
        "name": "Nguyen Van A",
        "avatar": "https://storage.yourdomain.com/avatars/user1.jpg",
        "role": "owner"
      },
      {
        "id": 2,
        "name": "Tran Thi B",
        "avatar": "https://storage.yourdomain.com/avatars/user2.jpg",
        "role": "member"
      },
      {
        "id": 3,
        "name": "Le Van C",
        "avatar": "https://storage.yourdomain.com/avatars/user3.jpg",
        "role": "member"
      },
      {
        "id": 4,
        "name": "Pham Thi D",
        "avatar": "https://storage.yourdomain.com/avatars/user4.jpg",
        "role": "member"
      }
    ]
  }
}
```

## Chat Nhóm

### Lấy danh sách tin nhắn
**Endpoint:** `GET /teams/{team_id}/chat`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

**Query Parameters:**
- `before` (optional): Lấy tin nhắn trước ID này
- `limit` (optional): Số lượng tin nhắn, mặc định là 50

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "content": "Chào mọi người, tôi đã hoàn thành task thiết kế UI",
      "created_at": "2023-05-06T10:15:00.000000Z",
      "updated_at": "2023-05-06T10:15:00.000000Z",
      "user": {
        "id": 2,
        "name": "Tran Thi B",
        "avatar": "https://storage.yourdomain.com/avatars/user2.jpg"
      },
      "attachments": [],
      "reactions": [
        {
          "emoji": "👍",
          "count": 2,
          "users": [1, 3]
        }
      ]
    }
  ],
  "meta": {
    "has_more": true,
    "next_cursor": "cursor_value"
  }
}
```

### Gửi tin nhắn
**Endpoint:** `POST /teams/{team_id}/chat`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
Content-Type: multipart/form-data
```

**Form Data:**
```
content: Nội dung tin nhắn
attachments[]: [binary file data] (optional)
```

**Response (201):**
```json
{
  "data": {
    "id": 2,
    "content": "Nội dung tin nhắn",
    "created_at": "2023-05-06T10:20:00.000000Z",
    "updated_at": "2023-05-06T10:20:00.000000Z",
    "user": {
      "id": 1,
      "name": "Nguyen Van A",
      "avatar": "https://storage.yourdomain.com/avatars/user1.jpg"
    },
    "attachments": [
      {
        "id": 1,
        "filename": "design.png",
        "file_size": 1024000,
        "mime_type": "image/png",
        "url": "https://storage.yourdomain.com/attachments/design.png"
      }
    ],
    "reactions": []
  }
}
```

## WebSocket Events

### Kết nối WebSocket
```
wss://api.yourdomain.com/ws
```

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

### Events

#### Tin nhắn mới
```json
{
  "event": "message.created",
  "data": {
    "id": 2,
    "content": "Nội dung tin nhắn",
    "created_at": "2023-05-06T10:20:00.000000Z",
    "user": {
      "id": 1,
      "name": "Nguyen Van A",
      "avatar": "https://storage.yourdomain.com/avatars/user1.jpg"
    }
  }
}
```

#### Tin nhắn được cập nhật
```json
{
  "event": "message.updated",
  "data": {
    "id": 2,
    "content": "Nội dung tin nhắn đã cập nhật",
    "updated_at": "2023-05-06T10:25:00.000000Z"
  }
}
```

#### Tin nhắn bị xóa
```json
{
  "event": "message.deleted",
  "data": {
    "id": 2
  }
}
```

#### Người dùng đang nhập
```json
{
  "event": "user.typing",
  "data": {
    "user_id": 1,
    "name": "Nguyen Van A"
  }
}
```

## Rate Limiting

API sử dụng rate limiting để bảo vệ server khỏi quá tải:

- Đăng nhập: 6 requests/phút
- Đăng ký: 3 requests/phút
- API chung: 60 requests/phút
- Upload file: 10 requests/phút

Khi vượt quá giới hạn, API sẽ trả về mã lỗi 429 (Too Many Requests) với thông tin về thời gian chờ:

```json
{
  "message": "Too Many Attempts.",
  "retry_after": 60
}
```

## Error Handling

API sử dụng mã HTTP tiêu chuẩn và cung cấp thông tin lỗi chi tiết:

### Cấu trúc phản hồi lỗi
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email has already been taken."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

### Mã lỗi phổ biến

| Mã HTTP | Mã lỗi | Mô tả |
|---------|--------|--------|
| 400 | bad_request | Yêu cầu không hợp lệ |
| 400 | validation_error | Dữ liệu không vượt qua kiểm tra hợp lệ |
| 401 | unauthorized | Chưa xác thực hoặc token không hợp lệ |
| 403 | forbidden | Không có quyền truy cập tài nguyên |
| 404 | not_found | Tài nguyên không tồn tại |
| 409 | conflict | Xung đột dữ liệu |
| 422 | unprocessable_entity | Không thể xử lý yêu cầu |
| 429 | too_many_requests | Quá nhiều yêu cầu, vượt quá giới hạn |
| 500 | server_error | Lỗi máy chủ nội bộ |
| 503 | service_unavailable | Dịch vụ không khả dụng |

## Validation Rules

### User
- name: required|string|max:255
- email: required|email|unique:users
- password: required|string|min:8|confirmed
- device_id: required|string|max:255
- device_name: required|string|max:255

### Task
- title: required|string|max:255
- description: nullable|string
- status: required|in:todo,in_progress,done
- priority: required|in:low,medium,high
- due_date: required|date|after:today
- assigned_to: array|exists:users,id

### Team
- name: required|string|max:255
- description: nullable|string
- members: array|exists:users,id

### Message
- content: required|string|max:1000
- attachments: array|max:5
- attachments.*: file|max:10240|mimes:jpeg,png,pdf,doc,docx

## Support

Để biết thêm thông tin hoặc hỗ trợ, vui lòng liên hệ:

- Email: support@yourdomain.com
- GitHub: https://github.com/yourusername/your-repo
- Documentation: https://docs.yourdomain.com

## Quản lý Kanban Board

### Lấy thông tin board
**Endpoint:** `GET /teams/{team_id}/kanban`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "Sprint 1",
    "columns": [
      {
        "id": 1,
        "name": "To Do",
        "order": 1,
        "tasks": [
          {
            "id": 1,
            "title": "Thiết kế UI",
            "description": "Thiết kế giao diện người dùng",
            "priority": "high",
            "due_date": "2023-05-15",
            "assigned_to": {
              "id": 2,
              "name": "Tran Thi B",
              "avatar": "https://storage.yourdomain.com/avatars/user2.jpg"
            }
          }
        ]
      },
      {
        "id": 2,
        "name": "In Progress",
        "order": 2,
        "tasks": []
      },
      {
        "id": 3,
        "name": "Done",
        "order": 3,
        "tasks": []
      }
    ]
  }
}
```

### Di chuyển task giữa các cột
**Endpoint:** `PATCH /teams/{team_id}/kanban/tasks/{task_id}/move`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
Content-Type: application/json
```

**Request Body:**
```json
{
  "column_id": 2,
  "position": 0
}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "title": "Thiết kế UI",
    "column_id": 2,
    "position": 0,
    "updated_at": "2023-05-06T11:00:00.000000Z"
  }
}
```

## Đồng bộ dữ liệu Offline

### Lấy dữ liệu cần đồng bộ
**Endpoint:** `GET /sync`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

**Query Parameters:**
- `last_sync_at`: Thời điểm đồng bộ cuối cùng (ISO 8601)
- `device_id`: ID của thiết bị

**Response (200):**
```json
{
  "data": {
    "tasks": {
      "created": [
        {
          "id": 1,
          "title": "Task mới",
          "created_at": "2023-05-06T10:00:00.000000Z"
        }
      ],
      "updated": [
        {
          "id": 2,
          "title": "Task đã cập nhật",
          "updated_at": "2023-05-06T10:30:00.000000Z"
        }
      ],
      "deleted": [3, 4]
    },
    "messages": {
      "created": [
        {
          "id": 1,
          "content": "Tin nhắn mới",
          "created_at": "2023-05-06T10:15:00.000000Z"
        }
      ],
      "updated": [],
      "deleted": []
    }
  },
  "meta": {
    "sync_timestamp": "2023-05-06T11:00:00.000000Z"
  }
}
```

### Đồng bộ dữ liệu từ thiết bị
**Endpoint:** `POST /sync`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
Content-Type: application/json
```

**Request Body:**
```json
{
  "device_id": "unique-device-identifier",
  "tasks": {
    "created": [
      {
        "local_id": "local_1",
        "title": "Task offline",
        "created_at": "2023-05-06T10:00:00.000000Z"
      }
    ],
    "updated": [],
    "deleted": []
  },
  "messages": {
    "created": [],
    "updated": [],
    "deleted": []
  }
}
```

**Response (200):**
```json
{
  "data": {
    "tasks": {
      "created": [
        {
          "local_id": "local_1",
          "server_id": 5,
          "status": "success"
        }
      ],
      "updated": [],
      "deleted": []
    },
    "messages": {
      "created": [],
      "updated": [],
      "deleted": []
    }
  },
  "meta": {
    "sync_timestamp": "2023-05-06T11:00:00.000000Z"
  }
}
```

## Push Notifications

### Đăng ký thiết bị
**Endpoint:** `POST /notifications/register`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
Content-Type: application/json
```

**Request Body:**
```json
{
  "device_id": "unique-device-identifier",
  "fcm_token": "firebase_cloud_messaging_token",
  "device_type": "android",
  "device_name": "Pixel 6"
}
```

**Response (200):**
```json
{
  "message": "Đăng ký thiết bị thành công"
}
```

### Cập nhật cài đặt thông báo
**Endpoint:** `PATCH /notifications/settings`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
Content-Type: application/json
```

**Request Body:**
```json
{
  "task_assignments": true,
  "task_updates": true,
  "task_comments": true,
  "team_messages": true,
  "team_invitations": true,
  "quiet_hours": {
    "enabled": true,
    "start": "22:00",
    "end": "07:00"
  }
}
```

**Response (200):**
```json
{
  "data": {
    "task_assignments": true,
    "task_updates": true,
    "task_comments": true,
    "team_messages": true,
    "team_invitations": true,
    "quiet_hours": {
      "enabled": true,
      "start": "22:00",
      "end": "07:00"
    }
  }
}
```

## Phân tích và Báo cáo

### Thống kê công việc
**Endpoint:** `GET /analytics/tasks`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

**Query Parameters:**
- `team_id` (optional): ID của nhóm
- `start_date`: Ngày bắt đầu (YYYY-MM-DD)
- `end_date`: Ngày kết thúc (YYYY-MM-DD)

**Response (200):**
```json
{
  "data": {
    "total_tasks": 100,
    "completed_tasks": 75,
    "overdue_tasks": 5,
    "completion_rate": 75,
    "by_status": {
      "todo": 20,
      "in_progress": 30,
      "done": 50
    },
    "by_priority": {
      "low": 30,
      "medium": 40,
      "high": 30
    },
    "by_assignee": [
      {
        "user_id": 1,
        "name": "Nguyen Van A",
        "total": 25,
        "completed": 20
      }
    ],
    "timeline": [
      {
        "date": "2023-05-01",
        "created": 5,
        "completed": 3
      }
    ]
  }
}
```

### Báo cáo hiệu suất nhóm
**Endpoint:** `GET /analytics/teams/{team_id}/performance`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

**Query Parameters:**
- `period`: Kỳ báo cáo (week, month, quarter, year)
- `start_date`: Ngày bắt đầu (YYYY-MM-DD)

**Response (200):**
```json
{
  "data": {
    "team_id": 1,
    "team_name": "Dự án X",
    "period": "month",
    "start_date": "2023-05-01",
    "metrics": {
      "task_completion_rate": 85,
      "average_completion_time": "3.5 days",
      "on_time_delivery_rate": 90,
      "team_velocity": 25
    },
    "member_performance": [
      {
        "user_id": 1,
        "name": "Nguyen Van A",
        "tasks_completed": 15,
        "on_time_rate": 95,
        "contribution_score": 90
      }
    ],
    "trends": {
      "completion_rate": [
        {
          "date": "2023-05-01",
          "value": 80
        }
      ],
      "velocity": [
        {
          "date": "2023-05-01",
          "value": 20
        }
      ]
    }
  }
}
```

## Tích hợp Lịch

### Lấy sự kiện lịch
**Endpoint:** `GET /calendar/events`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

**Query Parameters:**
- `start_date`: Ngày bắt đầu (YYYY-MM-DD)
- `end_date`: Ngày kết thúc (YYYY-MM-DD)
- `team_id` (optional): ID của nhóm

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Họp nhóm",
      "description": "Họp đánh giá sprint",
      "start_date": "2023-05-10T09:00:00.000000Z",
      "end_date": "2023-05-10T10:00:00.000000Z",
      "type": "meeting",
      "team": {
        "id": 1,
        "name": "Dự án X"
      },
      "participants": [
        {
          "id": 1,
          "name": "Nguyen Van A",
          "avatar": "https://storage.yourdomain.com/avatars/user1.jpg"
        }
      ]
    }
  ]
}
```

### Tạo sự kiện mới
**Endpoint:** `POST /calendar/events`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "Họp nhóm",
  "description": "Họp đánh giá sprint",
  "start_date": "2023-05-10T09:00:00.000000Z",
  "end_date": "2023-05-10T10:00:00.000000Z",
  "type": "meeting",
  "team_id": 1,
  "participants": [1, 2, 3]
}
```

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "title": "Họp nhóm",
    "description": "Họp đánh giá sprint",
    "start_date": "2023-05-10T09:00:00.000000Z",
    "end_date": "2023-05-10T10:00:00.000000Z",
    "type": "meeting",
    "team": {
      "id": 1,
      "name": "Dự án X"
    },
    "participants": [
      {
        "id": 1,
        "name": "Nguyen Van A",
        "avatar": "https://storage.yourdomain.com/avatars/user1.jpg"
      }
    ],
    "created_at": "2023-05-06T11:00:00.000000Z",
    "updated_at": "2023-05-06T11:00:00.000000Z"
  }
}
```

## Xác thực Sinh trắc học

### Đăng ký xác thực sinh trắc học
**Endpoint:** `POST /auth/biometric/register`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
Content-Type: application/json
```

**Request Body:**
```json
{
  "device_id": "unique-device-identifier",
  "biometric_type": "fingerprint",
  "public_key": "biometric_public_key"
}
```

**Response (201):**
```json
{
  "message": "Đăng ký xác thực sinh trắc học thành công"
}
```

### Xác thực bằng sinh trắc học
**Endpoint:** `POST /auth/biometric/verify`

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "device_id": "unique-device-identifier",
  "biometric_type": "fingerprint",
  "signature": "biometric_signature"
}
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "Nguyen Van A",
    "email": "example@email.com",
    "avatar": "https://storage.yourdomain.com/avatars/user1.jpg"
  },
  "token": "1|laravel_sanctum_token_hash",
  "device_id": "unique-device-identifier"
}
```

## Quản lý Lời mời Nhóm (Team Invitations)

### Lấy danh sách lời mời của nhóm
**Endpoint:** `GET /teams/{team_id}/invitations`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "team_id": 1,
      "email": "newuser@example.com",
      "role": "member",
      "status": "pending",
      "created_at": "2023-05-06T10:00:00.000000Z",
      "expires_at": "2023-05-13T10:00:00.000000Z"
    }
  ]
}
```

### Gửi lời mời tham gia nhóm
**Endpoint:** `POST /teams/{team_id}/invitations`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "newuser@example.com",
  "role": "member"
}
```

**Response (201):**
```json
{
  "message": "Invitation sent successfully",
  "data": {
    "id": 1,
    "team_id": 1,
    "team_name": "Dự án X",
    "email": "newuser@example.com",
    "role": "member",
    "status": "pending",
    "created_at": "2023-05-06T10:00:00.000000Z",
    "expires_at": "2023-05-13T10:00:00.000000Z"
  }
}
```

### Chấp nhận lời mời
**Endpoint:** `POST /invitations/accept`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
Content-Type: application/json
```

**Request Body:**
```json
{
  "token": "invitation_token"
}
```

**Response (200):**
```json
{
  "message": "Invitation accepted successfully",
  "data": {
    "team": {
      "id": 1,
      "name": "Dự án X",
      "description": "Dự án phát triển ứng dụng quản lý công việc"
    },
    "role": "member"
  }
}
```

### Từ chối lời mời
**Endpoint:** `POST /invitations/reject`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
Content-Type: application/json
```

**Request Body:**
```json
{
  "token": "invitation_token"
}
```

**Response (200):**
```json
{
  "message": "Invitation rejected successfully"
}
```

### Hủy lời mời (bởi người quản lý nhóm)
**Endpoint:** `DELETE /teams/{team_id}/invitations/{invitation_id}`

**Headers:**
```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

**Response (200):**
```json
{
  "message": "Invitation cancelled successfully"
}
```