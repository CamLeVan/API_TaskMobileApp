# API Documentation - Chức năng Chia sẻ Tài liệu (Document Sharing Feature)

## Tổng quan (Overview)

API Chia sẻ Tài liệu cho phép người dùng tải lên, quản lý và chia sẻ tài liệu trong nhóm. Chức năng này hỗ trợ:

- Tải lên và tải xuống tài liệu
- Quản lý thư mục
- Phiên bản tài liệu
- Quyền truy cập tài liệu
- Đồng bộ hóa tài liệu giữa các thiết bị

## Cấu trúc dữ liệu (Data Structures)

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

## Endpoints (API Endpoints)

### Quản lý Tài liệu (Document Management)

#### Lấy danh sách tài liệu trong nhóm

```http
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

```http
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

### Quản lý Thư mục (Folder Management)

#### Lấy danh sách thư mục

```
GET /api/teams/{team_id}/folders
```

**Query Parameters:**
- `parent_id` (optional): ID của thư mục cha (null để lấy thư mục gốc)

**Response (200):**
```json
{
  "data": [
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
  ]
}
```

#### Tạo thư mục mới

```
POST /api/teams/{team_id}/folders
```

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "name": "Project Documentation",
  "description": "Contains all project documentation",
  "parent_id": null
}
```

**Response (201):**
```json
{
  "data": {
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
    "document_count": 0,
    "subfolder_count": 0,
    "created_at": "2023-05-10T10:30:00.000000Z",
    "updated_at": "2023-05-10T10:30:00.000000Z"
  }
}
```

#### Lấy chi tiết thư mục

```
GET /api/folders/{folder_id}
```

**Response (200):**
```json
{
  "data": {
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
}
```

#### Cập nhật thư mục

```
PUT /api/folders/{folder_id}
```

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "name": "Updated Project Documentation",
  "description": "Updated description"
}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "Updated Project Documentation",
    "description": "Updated description",
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
    "updated_at": "2023-05-10T11:45:00.000000Z"
  }
}
```

#### Xóa thư mục

```
DELETE /api/folders/{folder_id}
```

**Query Parameters:**
- `delete_contents` (optional): Boolean, xác định có xóa tất cả nội dung trong thư mục không (mặc định là false)

**Response (200):**
```json
{
  "message": "Folder deleted successfully"
}
```

### Quản lý Phiên bản Tài liệu (Document Version Management)

#### Lấy danh sách phiên bản

```
GET /api/documents/{document_id}/versions
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 2,
      "document_id": 1,
      "version_number": 2,
      "file_url": "https://api.yourdomain.com/storage/teams/1/documents/uuid-filename-v2.pdf",
      "thumbnail_url": "https://api.yourdomain.com/storage/teams/1/documents/thumbnails/uuid-filename-v2.jpg",
      "file_size": 1048576,
      "created_by": {
        "id": 1,
        "name": "Nguyen Van A",
        "avatar": "https://api.yourdomain.com/storage/avatars/user1.jpg"
      },
      "version_note": "Updated with feedback",
      "created_at": "2023-05-11T09:45:00.000000Z",
      "updated_at": "2023-05-11T09:45:00.000000Z"
    },
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
  ]
}
```

#### Tải lên phiên bản mới

```
POST /api/documents/{document_id}/versions
```

**Content-Type:** `multipart/form-data`

**Parameters:**
- `file` (required): File mới
- `version_note` (optional): Ghi chú cho phiên bản mới

**Response (200):**
```json
{
  "data": {
    "id": 2,
    "document_id": 1,
    "version_number": 2,
    "file_url": "https://api.yourdomain.com/storage/teams/1/documents/uuid-filename-v2.pdf",
    "thumbnail_url": "https://api.yourdomain.com/storage/teams/1/documents/thumbnails/uuid-filename-v2.jpg",
    "file_size": 1048576,
    "created_by": {
      "id": 1,
      "name": "Nguyen Van A",
      "avatar": "https://api.yourdomain.com/storage/avatars/user1.jpg"
    },
    "version_note": "Updated with feedback",
    "created_at": "2023-05-11T09:45:00.000000Z",
    "updated_at": "2023-05-11T09:45:00.000000Z"
  }
}
```

#### Lấy chi tiết phiên bản

```
GET /api/documents/{document_id}/versions/{version_number}
```

**Response (200):**
```json
{
  "data": {
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
}
```

#### Tải xuống phiên bản cụ thể

```
GET /api/documents/{document_id}/versions/{version_number}/download
```

**Response:** File stream với header phù hợp

#### Khôi phục phiên bản cũ

```
POST /api/documents/{document_id}/versions/{version_number}/restore
```

**Response (200):**
```json
{
  "message": "Version restored successfully",
  "data": {
    "id": 1,
    "name": "Project Proposal.pdf",
    "current_version": 1,
    "file_url": "https://api.yourdomain.com/storage/teams/1/documents/uuid-filename.pdf",
    "thumbnail_url": "https://api.yourdomain.com/storage/teams/1/documents/thumbnails/uuid-filename.jpg",
    "file_size": 1024000,
    "updated_at": "2023-05-12T14:30:00.000000Z"
  }
}
```

### Đồng bộ hóa Tài liệu (Document Synchronization)

#### Lấy tài liệu đã thay đổi

```
GET /api/sync/documents
```

**Query Parameters:**
- `last_sync_at` (required): Thời điểm đồng bộ cuối cùng (ISO 8601)
- `team_id` (optional): ID của team

**Response (200):**
```json
{
  "data": {
    "updated": [
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
          "name": "Nguyen Van A"
        },
        "access_level": "team",
        "current_version": 1,
        "created_at": "2023-05-10T10:30:00.000000Z",
        "updated_at": "2023-05-10T10:30:00.000000Z"
      }
    ],
    "deleted": [2, 3]
  }
}
```

#### Giải quyết xung đột

```
POST /api/sync/documents/resolve-conflicts
```

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "conflicts": [
    {
      "document_id": 1,
      "resolution": "server",
      "client_data": null
    },
    {
      "document_id": 2,
      "resolution": "client",
      "client_data": {
        "name": "Client version of document",
        "description": "Updated on client",
        "folder_id": 3
      }
    }
  ]
}
```

**Response (200):**
```json
{
  "message": "Conflicts resolved successfully",
  "data": {
    "resolved": [1, 2]
  }
}
```
