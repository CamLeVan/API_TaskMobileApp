# Tài liệu API Chat Nhóm

## Giới thiệu

Tài liệu này mô tả chi tiết các API endpoints và WebSocket events liên quan đến tính năng chat nhóm trong ứng dụng. Hệ thống chat nhóm hỗ trợ các chức năng như gửi tin nhắn văn bản, đính kèm tệp, đánh dấu đã đọc, hiển thị trạng thái đang nhập, phản ứng emoji, và đồng bộ hóa tin nhắn khi offline.

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

## API Endpoints

### 1. Lấy lịch sử chat

**Endpoint:** `GET /api/teams/{team_id}/chat`

**Mô tả:** Lấy lịch sử tin nhắn của một nhóm, hỗ trợ phân trang và tải tin nhắn cũ hơn/mới hơn.

**Tham số URL:**

- `team_id`: ID của nhóm

**Query Parameters:**

- `limit` (optional): Số lượng tin nhắn tối đa trả về, mặc định là 50
- `before_id` (optional): Lấy tin nhắn có ID nhỏ hơn giá trị này (tin nhắn cũ hơn)
- `after_id` (optional): Lấy tin nhắn có ID lớn hơn giá trị này (tin nhắn mới hơn)

**Response (200):**

```json
{
  "data": [
    {
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
      "read_status": [
        {
          "user_id": 2,
          "read_at": "2025-05-10T09:01:00.000000Z"
        }
      ],
      "reactions": [
        {
          "user_id": 2,
          "reaction": "👍",
          "created_at": "2025-05-10T09:02:00.000000Z"
        }
      ]
    },
    {
      "id": 123,
      "team_id": 1,
      "user_id": 2,
      "message": "Chào bạn, mình đang làm việc trên tài liệu mới",
      "attachments": [],
      "client_temp_id": "temp-123455",
      "created_at": "2025-05-10T08:55:00.000000Z",
      "updated_at": "2025-05-10T08:55:00.000000Z",
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
          "read_at": "2025-05-10T08:56:00.000000Z"
        }
      ],
      "reactions": []
    }
  ],
  "meta": {
    "oldest_id": 123,
    "newest_id": 124,
    "has_more_older": true,
    "has_more_newer": false
  }
}
```

### 2. Gửi tin nhắn mới

**Endpoint:** `POST /api/teams/{team_id}/chat`

**Mô tả:** Gửi tin nhắn mới trong nhóm.

**Tham số URL:**

- `team_id`: ID của nhóm

**Request Body:**

```json
{
  "message": "Xin chào mọi người!",
  "attachments": [1, 2],
  "client_temp_id": "temp-123456"
}
```

**Tham số:**

- `message` (required nếu không có attachments): Nội dung tin nhắn
- `attachments` (required nếu không có message): Mảng ID của các tệp đính kèm đã tải lên trước đó
- `client_temp_id` (required): ID tạm thời do client tạo ra để theo dõi tin nhắn

**Response (201):**

```json
{
  "data": {
    "id": 125,
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
    "created_at": "2025-05-10T09:05:00.000000Z",
    "updated_at": "2025-05-10T09:05:00.000000Z",
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

**Endpoint:** `POST /api/teams/{team_id}/chat/read`

**Mô tả:** Đánh dấu tin nhắn đã đọc đến một ID cụ thể.

**Tham số URL:**

- `team_id`: ID của nhóm

**Request Body:**

```json
{
  "message_id": 125
}
```

**Tham số:**

- `message_id` (required): ID của tin nhắn cuối cùng đã đọc

**Response (200):**

```json
{
  "message": "Messages marked as read",
  "data": {
    "team_id": 1,
    "user_id": 2,
    "message_id": 125,
    "read_at": "2025-05-10T09:06:00.000000Z"
  }
}
```

### 4. Đếm tin nhắn chưa đọc

**Endpoint:** `GET /api/teams/{team_id}/chat/unread-count`

**Mô tả:** Lấy số lượng tin nhắn chưa đọc trong nhóm.

**Tham số URL:**

- `team_id`: ID của nhóm

**Response (200):**

```json
{
  "data": {
    "unread_count": 5,
    "last_read_id": 120
  }
}
```

### 5. Cập nhật trạng thái đang nhập

**Endpoint:** `POST /api/teams/{team_id}/chat/typing`

**Mô tả:** Cập nhật trạng thái đang nhập của người dùng.

**Tham số URL:**

- `team_id`: ID của nhóm

**Request Body:**

```json
{
  "is_typing": true
}
```

**Tham số:**

- `is_typing` (required): Trạng thái đang nhập (true/false)

**Response (200):**

```json
{
  "message": "Typing status updated"
}
```

### 6. Gửi lại tin nhắn

**Endpoint:** `POST /api/teams/{team_id}/chat/retry/{client_temp_id}`

**Mô tả:** Gửi lại tin nhắn khi gặp lỗi.

**Tham số URL:**

- `team_id`: ID của nhóm
- `client_temp_id`: ID tạm thời của tin nhắn cần gửi lại

**Response (201):**

```json
{
  "data": {
    "id": 125,
    "team_id": 1,
    "user_id": 1,
    "message": "Tin nhắn gửi lại",
    "attachments": [],
    "client_temp_id": "temp-123457",
    "created_at": "2025-05-10T09:05:00.000000Z",
    "updated_at": "2025-05-10T09:05:00.000000Z",
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

### 7. Chỉnh sửa tin nhắn

**Endpoint:** `PUT /api/teams/{team_id}/chat/{message_id}`

**Mô tả:** Chỉnh sửa nội dung tin nhắn đã gửi. Chỉ người gửi tin nhắn mới có quyền chỉnh sửa.

**Tham số URL:**

- `team_id`: ID của nhóm
- `message_id`: ID của tin nhắn

**Request Body:**

```json
{
  "message": "Nội dung đã chỉnh sửa"
}
```

**Tham số:**

- `message` (required): Nội dung tin nhắn mới

**Response (200):**

```json
{
  "data": {
    "id": 124,
    "team_id": 1,
    "user_id": 1,
    "message": "Nội dung đã chỉnh sửa",
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
    "updated_at": "2025-05-10T09:10:00.000000Z",
    "deleted_at": null,
    "user": {
      "id": 1,
      "name": "Nguyen Van A",
      "email": "nguyenvana@example.com",
      "avatar": "https://storage.yourdomain.com/avatars/user1.jpg"
    },
    "read_status": [
      {
        "user_id": 2,
        "read_at": "2025-05-10T09:01:00.000000Z"
      }
    ],
    "reactions": []
  }
}
```

### 8. Xóa tin nhắn

**Endpoint:** `DELETE /api/teams/{team_id}/chat/{message_id}`

**Mô tả:** Xóa tin nhắn đã gửi. Chỉ người gửi tin nhắn mới có quyền xóa.

**Tham số URL:**

- `team_id`: ID của nhóm
- `message_id`: ID của tin nhắn

**Response (200):**

```json
{
  "message": "Message deleted successfully",
  "data": {
    "id": 124,
    "deleted_at": "2025-05-10T09:15:00.000000Z"
  }
}
```

### 9. Thêm/xóa phản ứng

**Endpoint:** `POST /api/teams/{team_id}/chat/{message_id}/react`

**Mô tả:** Thêm hoặc xóa phản ứng emoji cho tin nhắn.

**Tham số URL:**

- `team_id`: ID của nhóm
- `message_id`: ID của tin nhắn

**Request Body:**

```json
{
  "reaction": "👍"
}
```

**Tham số:**

- `reaction` (required): Emoji phản ứng. Gửi chuỗi rỗng để xóa phản ứng.

**Response (200):**

```json
{
  "message": "Reaction updated",
  "data": {
    "user_id": 1,
    "message_id": 124,
    "reaction": "👍",
    "created_at": "2025-05-10T09:20:00.000000Z"
  }
}
```

### 10. Tìm kiếm tin nhắn

**Endpoint:** `GET /api/teams/{team_id}/chat/search`

**Mô tả:** Tìm kiếm tin nhắn trong nhóm theo từ khóa.

**Tham số URL:**

- `team_id`: ID của nhóm

**Query Parameters:**

- `q` (required): Từ khóa tìm kiếm
- `limit` (optional): Số lượng tin nhắn tối đa trả về, mặc định là 20
- `from_date` (optional): Tìm kiếm từ ngày (định dạng YYYY-MM-DD)
- `to_date` (optional): Tìm kiếm đến ngày (định dạng YYYY-MM-DD)
- `user_id` (optional): Lọc theo người gửi

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
      "highlight": {
        "message": "Xin <em>chào</em> mọi người!"
      }
    }
  ],
  "meta": {
    "total": 5,
    "page": 1,
    "per_page": 20,
    "has_more": false
  }
}
```
