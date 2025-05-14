# Tài liệu API Chat Nhóm Realtime

## Giới thiệu

Tài liệu này mô tả chi tiết các API endpoints liên quan đến tính năng chat nhóm realtime trong ứng dụng. Các API này cho phép người dùng gửi, nhận, chỉnh sửa và xóa tin nhắn, cũng như theo dõi trạng thái đọc và phản ứng với tin nhắn.

## Base URL

Tất cả các API endpoints đều có tiền tố:

```text
https://api.yourdomain.com/api
```

Ví dụ, endpoint đầy đủ cho việc lấy lịch sử chat sẽ là:

```text
https://api.yourdomain.com/api/teams/1/chat
```

## Xác thực

Tất cả các API endpoints đều yêu cầu xác thực bằng token. Token cần được gửi trong header của mỗi request:

```text
Authorization: Bearer 1|laravel_sanctum_token_hash
```

## Endpoints

### 1. Lấy lịch sử chat

**Endpoint:** `GET /api/teams/{team_id}/chat`

**Mô tả:** Lấy lịch sử tin nhắn của một nhóm, hỗ trợ phân trang và tải tin nhắn cũ hơn.

**Tham số URL:**

- `team_id`: ID của nhóm

**Query Parameters:**

- `limit` (optional): Số lượng tin nhắn tối đa trả về, mặc định là 50
- `before_id` (optional): Lấy tin nhắn có ID nhỏ hơn giá trị này (để tải tin nhắn cũ hơn)
- `after_id` (optional): Lấy tin nhắn có ID lớn hơn giá trị này (để tải tin nhắn mới hơn)

**Response (200):**

```json
{
  "data": [
    {
      "id": 123,
      "team_id": 1,
      "user_id": 2,
      "message": "Xin chào mọi người!",
      "attachments": [],
      "client_temp_id": "temp-123456",
      "created_at": "2025-05-10T08:30:00.000000Z",
      "updated_at": "2025-05-10T08:30:00.000000Z",
      "deleted_at": null,
      "user": {
        "id": 2,
        "name": "Tran Thi B",
        "email": "tranthib@example.com",
        "avatar": "https://storage.yourdomain.com/avatars/user2.jpg"
      },
      "read_status": [
        {
          "user_id": 1,
          "read_at": "2025-05-10T08:31:00.000000Z"
        },
        {
          "user_id": 3,
          "read_at": null
        }
      ],
      "reactions": [
        {
          "user_id": 1,
          "reaction": "👍",
          "created_at": "2025-05-10T08:32:00.000000Z"
        }
      ]
    }
  ],
  "meta": {
    "has_more": true,
    "oldest_id": 122,
    "newest_id": 123
  }
}
```

### 2. Gửi tin nhắn

**Endpoint:** `POST /api/teams/{team_id}/chat`

**Mô tả:** Gửi tin nhắn mới trong nhóm.

**Tham số URL:**

- `team_id`: ID của nhóm

**Request Body:**

```json
{
  "message": "Xin chào mọi người!",
  "client_temp_id": "temp-123456",
  "attachments": [
    {
      "file_id": 1
    }
  ]
}
```

**Tham số:**

- `message` (required nếu không có attachments): Nội dung tin nhắn
- `client_temp_id` (required): ID tạm thời do client tạo ra để theo dõi tin nhắn
- `attachments` (optional): Mảng các tệp đính kèm
  - `file_id`: ID của tệp đã được tải lên trước đó

**Response (201):**

```json
{
  "data": {
    "id": 124,
    "team_id": 1,
    "user_id": 1,
    "message": "Xin chào mọi người!",
    "attachments": [
      {
        "id": 1,
        "file_name": "document.pdf",
        "file_size": 1024000,
        "file_type": "application/pdf",
        "url": "https://storage.yourdomain.com/attachments/document.pdf"
      }
    ],
    "client_temp_id": "temp-123456",
    "created_at": "2025-05-10T09:00:00.000000Z",
    "updated_at": "2025-05-10T09:00:00.000000Z",
    "deleted_at": null,
    "user": {
      "id": 1,
      "name": "Nguyen Van A",
      "email": "nguyenvana@example.com",
      "avatar": "https://storage.yourdomain.com/avatars/user1.jpg"
    },
    "read_status": [],
    "reactions": []
  }
}
```

### 3. Đánh dấu tin nhắn đã đọc

**Endpoint:** `PUT /api/teams/{team_id}/chat/{message_id}/read`

**Mô tả:** Đánh dấu một tin nhắn cụ thể là đã đọc. Hệ thống sẽ tự động đánh dấu tất cả tin nhắn cũ hơn là đã đọc.

**Tham số URL:**

- `team_id`: ID của nhóm
- `message_id`: ID của tin nhắn

**Response (200):**

```json
{
  "message": "Marked as read successfully",
  "data": {
    "user_id": 1,
    "message_id": 124,
    "read_at": "2025-05-10T09:01:00.000000Z"
  }
}
```

### 4. Đếm tin nhắn chưa đọc

**Endpoint:** `GET /api/teams/{team_id}/chat/unread`

**Mô tả:** Lấy số lượng tin nhắn chưa đọc trong nhóm.

**Tham số URL:**

- `team_id`: ID của nhóm

**Response (200):**

```json
{
  "data": {
    "count": 5,
    "last_read_id": 119
  }
}
```

### 5. Cập nhật trạng thái nhập

**Endpoint:** `POST /api/teams/{team_id}/chat/typing`

**Mô tả:** Thông báo cho các thành viên khác rằng người dùng đang nhập tin nhắn.

**Tham số URL:**

- `team_id`: ID của nhóm

**Request Body:**

```json
{
  "is_typing": true
}
```

**Tham số:**

- `is_typing` (required): `true` khi bắt đầu nhập, `false` khi dừng nhập

**Response (200):**

```json
{
  "message": "Typing status updated"
}
```
