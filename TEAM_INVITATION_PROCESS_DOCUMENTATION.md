# Tài liệu Quy trình Mời Người dùng vào Nhóm

## Giới thiệu

Tài liệu này mô tả chi tiết quy trình mời người dùng vào nhóm, bao gồm các API endpoints liên quan đến tìm kiếm người dùng và quản lý lời mời. Quy trình này cho phép người quản lý nhóm tìm kiếm người dùng, gửi lời mời, và người dùng có thể chấp nhận hoặc từ chối lời mời.

## Quy trình tổng quan

1. **Tìm kiếm người dùng**: Người quản lý nhóm tìm kiếm người dùng theo tên hoặc email
2. **Gửi lời mời**: Người quản lý nhóm gửi lời mời cho người dùng đã chọn
3. **Thông báo**: Người dùng nhận được thông báo về lời mời (qua WebSocket và Push Notification)
4. **Phản hồi lời mời**: Người dùng chấp nhận hoặc từ chối lời mời
5. **Cập nhật trạng thái**: Hệ thống cập nhật trạng thái lời mời và thông báo cho người quản lý nhóm

## Base URL

Tất cả các API endpoints đều có tiền tố:

```text
https://api.yourdomain.com/api
```

## Xác thực

Tất cả các API endpoints đều yêu cầu xác thực bằng token. Token cần được gửi trong header của mỗi request:

```text
Authorization: Bearer 1|laravel_sanctum_token_hash
```

## API Endpoints

### 1. Tìm kiếm người dùng

**Endpoint:** `GET /api/users/search`

**Mô tả:** Tìm kiếm người dùng theo tên hoặc email. Thường được sử dụng khi mời người dùng vào nhóm.

**Query Parameters:**

- `query` (optional): Từ khóa tìm kiếm chung (tìm theo cả tên và email), tối thiểu 2 ký tự
- `name` (optional): Tìm kiếm theo tên, tối thiểu 2 ký tự
- `email` (optional): Tìm kiếm theo email, tối thiểu 2 ký tự
- `exclude_team` (optional): ID của nhóm mà bạn muốn loại trừ những người đã là thành viên
- `per_page` (optional): Số lượng kết quả trên mỗi trang, mặc định là 15

**Lưu ý:** Ít nhất một trong các tham số `query`, `name` hoặc `email` phải được cung cấp. Nếu cung cấp `query`, hệ thống sẽ tìm kiếm theo cả tên và email.

**Response (200):**

```json
{
  "data": [
    {
      "id": 2,
      "name": "Tran Thi B",
      "email": "tranthib@example.com",
      "avatar": "https://storage.yourdomain.com/avatars/user2.jpg",
      "created_at": "2025-05-01T10:00:00.000000Z",
      "updated_at": "2025-05-01T10:00:00.000000Z"
    },
    {
      "id": 3,
      "name": "Le Van C",
      "email": "levanc@example.com",
      "avatar": null,
      "created_at": "2025-05-02T10:00:00.000000Z",
      "updated_at": "2025-05-02T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 2
  }
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

### 3. Lấy danh sách lời mời của nhóm

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

### 4. Chấp nhận lời mời

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

### 5. Từ chối lời mời

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

### 6. Xóa lời mời

**Endpoint:** `DELETE /api/teams/{team_id}/invitations/{invitation_id}`

**Mô tả:** Xóa một lời mời đã gửi. Chỉ người quản lý nhóm mới có quyền xóa lời mời.

**Tham số URL:**

- `team_id`: ID của nhóm
- `invitation_id`: ID của lời mời

**Response (200):**

```json
{
  "message": "Invitation cancelled successfully"
}
```
