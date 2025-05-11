# Tài liệu API Quản lý Lời mời Nhóm

## Giới thiệu

Tài liệu này mô tả chi tiết các API endpoints liên quan đến quản lý lời mời nhóm (Team Invitations) trong ứng dụng. Các API này cho phép người dùng gửi lời mời, chấp nhận hoặc từ chối lời mời tham gia nhóm.

## Base URL

Tất cả các API endpoints đều có tiền tố:

```text
https://api.yourdomain.com/api
```

Ví dụ, endpoint đầy đủ cho việc lấy danh sách lời mời sẽ là:

```text
https://api.yourdomain.com/api/teams/1/invitations
```

## Xác thực

Tất cả các API endpoints đều yêu cầu xác thực bằng token. Token cần được gửi trong header của mỗi request:

```text
Authorization: Bearer 1|laravel_sanctum_token_hash
```

## Endpoints

### 1. Lấy danh sách lời mời của nhóm

**Endpoint:** `GET /api/teams/{team_id}/invitations`

**Mô tả:** Lấy danh sách tất cả lời mời đã gửi trong một nhóm. Chỉ người quản lý nhóm mới có quyền xem danh sách này.

**Tham số URL:**
- `team_id`: ID của nhóm

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
      "created_at": "2025-05-06T10:00:00.000000Z",
      "expires_at": "2025-05-13T10:00:00.000000Z"
    },
    {
      "id": 2,
      "team_id": 1,
      "email": "anotheruser@example.com",
      "role": "manager",
      "status": "pending",
      "created_at": "2025-05-07T10:00:00.000000Z",
      "expires_at": "2025-05-14T10:00:00.000000Z"
    }
  ]
}
```

### 2. Gửi lời mời tham gia nhóm

**Endpoint:** `POST /api/teams/{team_id}/invitations`

**Mô tả:** Gửi lời mời tham gia nhóm cho một người dùng thông qua email. Chỉ người quản lý nhóm mới có quyền gửi lời mời.

**Tham số URL:**
- `team_id`: ID của nhóm

**Request Body:**
```json
{
  "email": "newuser@example.com",
  "role": "member"
}
```

**Tham số:**
- `email` (required): Email của người được mời
- `role` (required): Vai trò trong nhóm (member, manager)

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
    "created_at": "2025-05-06T10:00:00.000000Z",
    "expires_at": "2025-05-13T10:00:00.000000Z"
  }
}
```

### 3. Chấp nhận lời mời

**Endpoint:** `POST /api/invitations/accept`

**Mô tả:** Chấp nhận lời mời tham gia nhóm. Người dùng phải đăng nhập và email của họ phải khớp với email trong lời mời.

**Request Body:**
```json
{
  "token": "invitation_token"
}
```

**Tham số:**
- `token` (required): Token của lời mời

**Response (200):**
```json
{
  "message": "Invitation accepted successfully",
  "data": {
    "team": {
      "id": 1,
      "name": "Dự án X",
      "description": "Mô tả về dự án X",
      "created_at": "2025-05-01T10:00:00.000000Z"
    },
    "role": "member"
  }
}
```

### 4. Từ chối lời mời

**Endpoint:** `POST /api/invitations/reject`

**Mô tả:** Từ chối lời mời tham gia nhóm. Người dùng phải đăng nhập và email của họ phải khớp với email trong lời mời.

**Request Body:**
```json
{
  "token": "invitation_token"
}
```

**Tham số:**
- `token` (required): Token của lời mời

**Response (200):**
```json
{
  "message": "Invitation rejected successfully"
}
```

### 5. Xóa lời mời

**Endpoint:** `DELETE /api/teams/{team_id}/invitations/{invitation_id}`

**Mô tả:** Xóa một lời mời đã gửi. Chỉ người quản lý nhóm mới có quyền xóa lời mời.

**Tham số URL:**
- `team_id`: ID của nhóm
- `invitation_id`: ID của lời mời

**Response (200):**
```json
{
  "message": "Invitation deleted successfully"
}
```

## Mã lỗi

Dưới đây là các mã lỗi có thể gặp phải khi sử dụng API:

| Mã HTTP | Mô tả | Nguyên nhân |
|---------|-------|-------------|
| 400 | Bad Request | Dữ liệu gửi lên không hợp lệ hoặc thiếu thông tin |
| 403 | Forbidden | Không có quyền thực hiện hành động này |
| 404 | Not Found | Không tìm thấy tài nguyên (nhóm, lời mời, v.v.) |
| 500 | Internal Server Error | Lỗi server |

## Thông báo WebSocket

Khi có lời mời mới hoặc khi trạng thái lời mời thay đổi, ứng dụng sẽ gửi thông báo qua WebSocket với cấu trúc sau:

### Lời mời nhóm mới

```json
{
  "event": "team.invitation.created",
  "data": {
    "id": 1,
    "team_id": 1,
    "team_name": "Dự án X",
    "role": "member",
    "created_at": "2025-05-06T10:00:00.000000Z",
    "expires_at": "2025-05-13T10:00:00.000000Z",
    "token": "invitation_token"
  }
}
```

### Lời mời nhóm được chấp nhận

```json
{
  "event": "team.invitation.accepted",
  "data": {
    "invitation_id": 1,
    "team_id": 1,
    "team_name": "Dự án X",
    "user": {
      "id": 5,
      "name": "Nguyen Van E",
      "avatar": "https://storage.yourdomain.com/avatars/user5.jpg"
    },
    "role": "member"
  }
}
```

## Thông báo Push (FCM)

Khi có lời mời mới, ứng dụng sẽ gửi thông báo push qua Firebase Cloud Messaging (FCM) với cấu trúc sau:

```json
{
  "notification": {
    "title": "Lời mời tham gia nhóm",
    "body": "Bạn được mời tham gia nhóm Dự án X",
    "android_channel_id": "team_invitations",
    "sound": "default"
  },
  "data": {
    "notification_id": "notification-uuid-1",
    "type": "team_invitation",
    "team_id": "1",
    "team_name": "Dự án X",
    "invitation_id": "5",
    "invitation_token": "invitation_token",
    "click_action": "OPEN_INVITATION_DETAIL"
  }
}
```
