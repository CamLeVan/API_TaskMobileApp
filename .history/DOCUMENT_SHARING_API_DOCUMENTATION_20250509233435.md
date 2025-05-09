# API Tài liệu - Chức năng Chia sẻ Tài liệu

## Tổng quan

API Chia sẻ Tài liệu cho phép người dùng tải lên, quản lý và chia sẻ tài liệu trong nhóm. Chức năng này hỗ trợ:

- Tải lên và tải xuống tài liệu
- Quản lý thư mục
- Phiên bản tài liệu
- Quyền truy cập tài liệu
- Đồng bộ hóa tài liệu giữa các thiết bị

## Cấu trúc dữ liệu

### Document Object

```json
{
  "id": 1,
  "name": "Project Proposal.pdf",
  "description": "Proposal for new feature",
  "file_url": "https://api.yourdomain.com/storage/teams/1/documents/uuid-filename.pdf",
  "thumbnail_url": "https://api.yourdomain.com/storage/teams/1/documents/thumbnails/uuid-filename.jpg",
  "file_type": "application/pdf",
  "file_size": 1024000,
  "folder_id": null,
  "team_id": 1,
  "uploaded_by": {
    "id": 1,
    "name": "Nguyen Van A",
    "avatar": "https://api.yourdomain.com/storage/avatars/user1.jpg"
  },
  "access_level": "team",
  "allowed_users": [],
  "current_version": 1,
  "created_at": "2023-05-10T10:30:00.000000Z",
  "updated_at": "2023-05-10T10:30:00.000000Z"
}
```

### Folder Object

```json
{
  "id": 1,
  "name": "Project Documentation",
  "description": "Contains all project documentation",
  "parent_id": null,
  "team_id": 1,
  "created_by": {
    "id": 1,
    "name": "Nguyen Van A",
    "avatar": "https://api.yourdomain.com/storage/avatars/user1.jpg"
  },
  "document_count": 5,
  "subfolder_count": 2,
  "created_at": "2023-05-10T10:30:00.000000Z",
  "updated_at": "2023-05-10T10:30:00.000000Z"
}
```

### Version Object

```json
{
  "id": 1,
  "document_id": 1,
  "version_number": 1,
  "file_url": "https://api.yourdomain.com/storage/teams/1/documents/uuid-filename.pdf",
  "thumbnail_url": "https://api.yourdomain.com/storage/teams/1/documents/thumbnails/uuid-filename.jpg",
  "file_size": 1024000,
  "created_by": {
    "id": 1,
    "name": "Nguyen Van A",
    "avatar": "https://api.yourdomain.com/storage/avatars/user1.jpg"
  },
  "version_note": "Initial version",
  "created_at": "2023-05-10T10:30:00.000000Z",
  "updated_at": "2023-05-10T10:30:00.000000Z"
}
```

## Endpoints

### Quản lý Tài liệu

#### Lấy danh sách tài liệu trong nhóm

```
GET /api/teams/{team_id}/documents
```

**Query Parameters:**
- `folder_id` (optional): ID của thư mục
- `search` (optional): Từ khóa tìm kiếm
- `file_type` (optional): Lọc theo loại file
- `sort_by` (optional): Sắp xếp theo trường (`name`, `created_at`, `updated_at`, `file_size`)
- `sort_direction` (optional): Hướng sắp xếp (`asc`, `desc`)
- `page` (optional): Số trang
- `per_page` (optional): Số item mỗi trang

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Project Proposal.pdf",
      "description": "Proposal for new feature",
      "file_url": "https://api.yourdomain.com/storage/teams/1/documents/uuid-filename.pdf",
      "thumbnail_url": "https://api.yourdomain.com/storage/teams/1/documents/thumbnails/uuid-filename.jpg",
      "file_type": "application/pdf",
      "file_size": 1024000,
      "folder_id": null,
      "team_id": 1,
      "uploaded_by": {
        "id": 1,
        "name": "Nguyen Van A",
        "avatar": "https://api.yourdomain.com/storage/avatars/user1.jpg"
      },
      "access_level": "team",
      "current_version": 1,
      "created_at": "2023-05-10T10:30:00.000000Z",
      "updated_at": "2023-05-10T10:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "https://api.yourdomain.com/api/teams/1/documents",
    "per_page": 20,
    "to": 20,
    "total": 100
  }
}
```

#### Tải lên tài liệu mới

```
POST /api/teams/{team_id}/documents
```

**Content-Type:** `multipart/form-data`

**Parameters:**
- `file` (required): File cần upload
- `name` (optional): Tên tài liệu (mặc định lấy từ tên file)
- `description` (optional): Mô tả tài liệu
- `folder_id` (optional): ID của thư mục chứa tài liệu
- `access_level` (optional): Mức độ truy cập (`public`, `team`, `private`, `specific_users`)
- `allowed_users` (optional): Mảng ID người dùng được phép truy cập (chỉ cần thiết khi `access_level` là `specific_users`)

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "name": "Project Proposal.pdf",
    "description": "Proposal for new feature",
    "file_url": "https://api.yourdomain.com/storage/teams/1/documents/uuid-filename.pdf",
    "thumbnail_url": "https://api.yourdomain.com/storage/teams/1/documents/thumbnails/uuid-filename.jpg",
    "file_type": "application/pdf",
    "file_size": 1024000,
    "folder_id": null,
    "team_id": 1,
    "uploaded_by": {
      "id": 1,
      "name": "Nguyen Van A",
      "avatar": "https://api.yourdomain.com/storage/avatars/user1.jpg"
    },
    "access_level": "team",
    "allowed_users": [],
    "current_version": 1,
    "created_at": "2023-05-10T10:30:00.000000Z",
    "updated_at": "2023-05-10T10:30:00.000000Z"
  }
}
```

#### Lấy chi tiết tài liệu

```
GET /api/documents/{document_id}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "Project Proposal.pdf",
    "description": "Proposal for new feature",
    "file_url": "https://api.yourdomain.com/storage/teams/1/documents/uuid-filename.pdf",
    "thumbnail_url": "https://api.yourdomain.com/storage/teams/1/documents/thumbnails/uuid-filename.jpg",
    "file_type": "application/pdf",
    "file_size": 1024000,
    "folder_id": null,
    "team_id": 1,
    "uploaded_by": {
      "id": 1,
      "name": "Nguyen Van A",
      "avatar": "https://api.yourdomain.com/storage/avatars/user1.jpg"
    },
    "access_level": "team",
    "allowed_users": [],
    "versions": [
      {
        "id": 1,
        "version_number": 1,
        "created_at": "2023-05-10T10:30:00.000000Z",
        "created_by": {
          "id": 1,
          "name": "Nguyen Van A",
          "avatar": "https://api.yourdomain.com/storage/avatars/user1.jpg"
        },
        "version_note": "Initial version"
      }
    ],
    "current_version": 1,
    "created_at": "2023-05-10T10:30:00.000000Z",
    "updated_at": "2023-05-10T10:30:00.000000Z"
  }
}
```

#### Cập nhật thông tin tài liệu

```
PUT /api/documents/{document_id}
```

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "name": "Updated Project Proposal.pdf",
  "description": "Updated proposal for new feature",
  "folder_id": 2
}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "Updated Project Proposal.pdf",
    "description": "Updated proposal for new feature",
    "file_url": "https://api.yourdomain.com/storage/teams/1/documents/uuid-filename.pdf",
    "thumbnail_url": "https://api.yourdomain.com/storage/teams/1/documents/thumbnails/uuid-filename.jpg",
    "file_type": "application/pdf",
    "file_size": 1024000,
    "folder_id": 2,
    "team_id": 1,
    "uploaded_by": {
      "id": 1,
      "name": "Nguyen Van A",
      "avatar": "https://api.yourdomain.com/storage/avatars/user1.jpg"
    },
    "access_level": "team",
    "current_version": 1,
    "created_at": "2023-05-10T10:30:00.000000Z",
    "updated_at": "2023-05-10T11:15:00.000000Z"
  }
}
```

#### Xóa tài liệu

```
DELETE /api/documents/{document_id}
```

**Response (200):**
```json
{
  "message": "Document deleted successfully"
}
```

#### Tải xuống tài liệu

```
GET /api/documents/{document_id}/download
```

**Response:** File stream với header phù hợp

#### Cập nhật quyền truy cập tài liệu

```
PUT /api/documents/{document_id}/access
```

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "access_level": "specific_users",
  "allowed_users": [2, 3, 4]
}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "access_level": "specific_users",
    "allowed_users": [
      {
        "id": 2,
        "name": "Tran Thi B",
        "avatar": "https://api.yourdomain.com/storage/avatars/user2.jpg"
      },
      {
        "id": 3,
        "name": "Le Van C",
        "avatar": "https://api.yourdomain.com/storage/avatars/user3.jpg"
      },
      {
        "id": 4,
        "name": "Pham Thi D",
        "avatar": "https://api.yourdomain.com/storage/avatars/user4.jpg"
      }
    ]
  }
}
```
