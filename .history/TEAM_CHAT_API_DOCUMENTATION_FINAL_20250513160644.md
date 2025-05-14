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

## WebSocket

### Kết nối WebSocket

Để nhận thông báo realtime, client cần kết nối đến WebSocket server:

```text
wss://api.yourdomain.com/reverb?token={access_token}
```

### Kênh

Đăng ký kênh riêng cho mỗi nhóm:

```text
private-teams.{teamId}
```

### Events

#### 1. Tin nhắn mới

```json
{
  "event": "new-chat-message",
  "data": {
    "id": 126,
    "team_id": 1,
    "user_id": 2,
    "message": "Tin nhắn mới từ WebSocket",
    "attachments": [],
    "client_temp_id": "temp-123458",
    "created_at": "2025-05-10T09:30:00.000000Z",
    "updated_at": "2025-05-10T09:30:00.000000Z",
    "deleted_at": null,
    "user": {
      "id": 2,
      "name": "Tran Thi B",
      "email": "tranthib@example.com",
      "avatar": "https://storage.yourdomain.com/avatars/user2.jpg"
    }
  }
}
```

#### 2. Tin nhắn đã đọc

```json
{
  "event": "message-read",
  "data": {
    "team_id": 1,
    "user_id": 3,
    "message_id": 126,
    "read_at": "2025-05-10T09:31:00.000000Z"
  }
}
```

#### 3. Người dùng đang nhập

```json
{
  "event": "user-typing",
  "data": {
    "team_id": 1,
    "user_id": 3,
    "is_typing": true,
    "timestamp": "2025-05-10T09:32:00.000000Z"
  }
}
```

#### 4. Cập nhật phản ứng

```json
{
  "event": "message-reaction-updated",
  "data": {
    "team_id": 1,
    "message_id": 126,
    "user_id": 3,
    "reaction": "❤️",
    "created_at": "2025-05-10T09:33:00.000000Z"
  }
}
```

#### 5. Tin nhắn được chỉnh sửa

```json
{
  "event": "message-updated",
  "data": {
    "id": 126,
    "team_id": 1,
    "message": "Tin nhắn đã được chỉnh sửa",
    "updated_at": "2025-05-10T09:35:00.000000Z"
  }
}
```

#### 6. Tin nhắn bị xóa

```json
{
  "event": "message-deleted",
  "data": {
    "id": 125,
    "team_id": 1,
    "deleted_at": "2025-05-10T09:40:00.000000Z"
  }
}
```

## Mã lỗi

| Mã HTTP | Mô tả | Nguyên nhân |
|---------|-------|-------------|
| 400 | Bad Request | Dữ liệu gửi lên không hợp lệ hoặc thiếu thông tin |
| 403 | Forbidden | Không có quyền thực hiện hành động này |
| 404 | Not Found | Không tìm thấy tài nguyên (nhóm, tin nhắn, v.v.) |
| 429 | Too Many Requests | Vượt quá giới hạn số lượng request |
| 500 | Internal Server Error | Lỗi server |

## Xử lý offline

### Gửi tin nhắn khi offline

1. Lưu tin nhắn vào cơ sở dữ liệu local với `client_temp_id` và trạng thái `pending`
2. Hiển thị tin nhắn trong UI với chỉ báo "đang gửi"
3. Khi có kết nối, gửi tất cả tin nhắn có trạng thái `pending` lên server
4. Cập nhật `id` và trạng thái tin nhắn trong cơ sở dữ liệu local

### Đồng bộ tin nhắn

1. Lưu ID tin nhắn cuối cùng đã nhận
2. Khi kết nối lại, gọi API với tham số `after_id` để lấy tin nhắn mới
3. Cập nhật cơ sở dữ liệu local và UI
