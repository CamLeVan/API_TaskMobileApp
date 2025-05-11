# Tài liệu API Quản lý Công việc Nhóm

## Giới thiệu

Tài liệu này mô tả chi tiết các API endpoints liên quan đến quản lý công việc nhóm (Team Tasks) trong ứng dụng. Các API này cho phép người dùng tạo, xem, cập nhật và xóa công việc nhóm, cũng như quản lý việc phân công và theo dõi tiến độ.

## Xác thực

Tất cả các API endpoints đều yêu cầu xác thực bằng token. Token cần được gửi trong header của mỗi request:

```
Authorization: Bearer 1|laravel_sanctum_token_hash
```

## Endpoints

### 1. Lấy danh sách công việc nhóm

**Endpoint:** `GET /api/teams/{team_id}/tasks`

**Mô tả:** Lấy danh sách tất cả công việc trong một nhóm.

**Tham số URL:**
- `team_id`: ID của nhóm

**Response (200):**
```json
[
  {
    "id": 1,
    "team_id": 1,
    "created_by": 1,
    "title": "Thiết kế giao diện người dùng",
    "description": "Thiết kế giao diện người dùng cho ứng dụng Android",
    "deadline": "2025-05-15T00:00:00.000000Z",
    "priority": 2,
    "status": "in_progress",
    "order": 1,
    "created_at": "2025-04-30T01:49:40.000000Z",
    "updated_at": "2025-04-30T01:49:40.000000Z",
    "creator": {
      "id": 1,
      "name": "Nguyen Van A",
      "email": "nguyenvana@example.com",
      "avatar": null
    },
    "assignments": [
      {
        "id": 1,
        "team_task_id": 1,
        "assigned_to": 2,
        "status": "in_progress",
        "progress": 50,
        "assigned_at": "2025-04-30T01:49:40.000000Z",
        "created_at": "2025-04-30T01:49:40.000000Z",
        "updated_at": "2025-04-30T01:49:40.000000Z",
        "assigned_to": {
          "id": 2,
          "name": "Tran Thi B",
          "email": "tranthib@example.com",
          "avatar": null
        }
      }
    ]
  }
]
```

### 2. Xem chi tiết công việc nhóm

**Endpoint:** `GET /api/teams/{team_id}/tasks/{task_id}`

**Mô tả:** Lấy thông tin chi tiết của một công việc nhóm.

**Tham số URL:**
- `team_id`: ID của nhóm
- `task_id`: ID của công việc

**Response (200):**
```json
{
  "id": 1,
  "team_id": 1,
  "created_by": 1,
  "title": "Thiết kế giao diện người dùng",
  "description": "Thiết kế giao diện người dùng cho ứng dụng Android",
  "deadline": "2025-05-15T00:00:00.000000Z",
  "priority": 2,
  "status": "in_progress",
  "order": 1,
  "created_at": "2025-04-30T01:49:40.000000Z",
  "updated_at": "2025-04-30T01:49:40.000000Z",
  "creator": {
    "id": 1,
    "name": "Nguyen Van A",
    "email": "nguyenvana@example.com",
    "avatar": null
  },
  "assignments": [
    {
      "id": 1,
      "team_task_id": 1,
      "assigned_to": 2,
      "status": "in_progress",
      "progress": 50,
      "assigned_at": "2025-04-30T01:49:40.000000Z",
      "created_at": "2025-04-30T01:49:40.000000Z",
      "updated_at": "2025-04-30T01:49:40.000000Z",
      "assigned_to": {
        "id": 2,
        "name": "Tran Thi B",
        "email": "tranthib@example.com",
        "avatar": null
      }
    }
  ],
  "subtasks": [
    {
      "id": 1,
      "taskable_id": 1,
      "taskable_type": "App\\Models\\TeamTask",
      "title": "Thiết kế màn hình đăng nhập",
      "is_completed": false,
      "order": 1,
      "created_at": "2025-04-30T01:49:40.000000Z",
      "updated_at": "2025-04-30T01:49:40.000000Z"
    }
  ]
}
```

### 3. Tạo công việc nhóm mới

**Endpoint:** `POST /api/teams/{team_id}/tasks`

**Mô tả:** Tạo một công việc mới trong nhóm.

**Tham số URL:**
- `team_id`: ID của nhóm

**Request Body:**
```json
{
  "title": "Thiết kế giao diện người dùng",
  "description": "Thiết kế giao diện người dùng cho ứng dụng Android",
  "deadline": "2025-05-15",
  "priority": 2,
  "status": "todo",
  "assigned_to": [1, 2]
}
```

**Tham số:**
- `title` (required): Tiêu đề công việc
- `description` (optional): Mô tả công việc
- `deadline` (optional): Hạn chót (định dạng YYYY-MM-DD)
- `priority` (optional): Mức độ ưu tiên (1: Thấp, 2: Trung bình, 3: Cao)
- `status` (optional): Trạng thái (todo, in_progress, done)
- `assigned_to` (optional): Mảng ID của các thành viên được phân công

**Response (201):**
```json
{
  "id": 1,
  "team_id": 1,
  "created_by": 1,
  "title": "Thiết kế giao diện người dùng",
  "description": "Thiết kế giao diện người dùng cho ứng dụng Android",
  "deadline": "2025-05-15T00:00:00.000000Z",
  "priority": 2,
  "status": "todo",
  "order": 1,
  "created_at": "2025-04-30T01:49:40.000000Z",
  "updated_at": "2025-04-30T01:49:40.000000Z",
  "assignments": [
    {
      "id": 1,
      "team_task_id": 1,
      "assigned_to": 1,
      "status": "todo",
      "progress": 0,
      "assigned_at": "2025-04-30T01:49:40.000000Z",
      "created_at": "2025-04-30T01:49:40.000000Z",
      "updated_at": "2025-04-30T01:49:40.000000Z"
    },
    {
      "id": 2,
      "team_task_id": 1,
      "assigned_to": 2,
      "status": "todo",
      "progress": 0,
      "assigned_at": "2025-04-30T01:49:40.000000Z",
      "created_at": "2025-04-30T01:49:40.000000Z",
      "updated_at": "2025-04-30T01:49:40.000000Z"
    }
  ]
}
```

### 4. Cập nhật công việc nhóm

**Endpoint:** `PUT /api/teams/{team_id}/tasks/{task_id}`

**Mô tả:** Cập nhật thông tin của một công việc nhóm.

**Tham số URL:**
- `team_id`: ID của nhóm
- `task_id`: ID của công việc

**Request Body:**
```json
{
  "title": "Thiết kế giao diện người dùng (cập nhật)",
  "description": "Thiết kế giao diện người dùng cho ứng dụng Android và iOS",
  "deadline": "2025-05-20",
  "priority": 3,
  "status": "in_progress",
  "assigned_to": [2, 3]
}
```

**Tham số:**
- `title` (optional): Tiêu đề công việc
- `description` (optional): Mô tả công việc
- `deadline` (optional): Hạn chót (định dạng YYYY-MM-DD)
- `priority` (optional): Mức độ ưu tiên (1: Thấp, 2: Trung bình, 3: Cao)
- `status` (optional): Trạng thái (todo, in_progress, done)
- `assigned_to` (optional): Mảng ID của các thành viên được phân công

**Response (200):**
```json
{
  "id": 1,
  "team_id": 1,
  "created_by": 1,
  "title": "Thiết kế giao diện người dùng (cập nhật)",
  "description": "Thiết kế giao diện người dùng cho ứng dụng Android và iOS",
  "deadline": "2025-05-20T00:00:00.000000Z",
  "priority": 3,
  "status": "in_progress",
  "order": 1,
  "created_at": "2025-04-30T01:49:40.000000Z",
  "updated_at": "2025-04-30T02:15:30.000000Z",
  "assignments": [
    {
      "id": 2,
      "team_task_id": 1,
      "assigned_to": 2,
      "status": "in_progress",
      "progress": 0,
      "assigned_at": "2025-04-30T01:49:40.000000Z",
      "created_at": "2025-04-30T01:49:40.000000Z",
      "updated_at": "2025-04-30T02:15:30.000000Z"
    },
    {
      "id": 3,
      "team_task_id": 1,
      "assigned_to": 3,
      "status": "todo",
      "progress": 0,
      "assigned_at": "2025-04-30T02:15:30.000000Z",
      "created_at": "2025-04-30T02:15:30.000000Z",
      "updated_at": "2025-04-30T02:15:30.000000Z"
    }
  ]
}
```
