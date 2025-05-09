# Tóm tắt Triển khai API Chia sẻ Tài liệu

## Tổng quan

Chức năng Chia sẻ tài liệu đã được triển khai đầy đủ trong API Laravel với các thành phần sau:

1. **Cơ sở dữ liệu**:
   - Bảng `document_folders`: Lưu trữ thông tin về các thư mục tài liệu
   - Bảng `documents`: Lưu trữ thông tin về các tài liệu
   - Bảng `document_versions`: Lưu trữ các phiên bản của tài liệu
   - Bảng `document_user_permissions`: Lưu trữ quyền truy cập của người dùng đối với tài liệu

2. **Models**:
   - `DocumentFolder`: Quản lý thư mục tài liệu
   - `Document`: Quản lý tài liệu
   - `DocumentVersion`: Quản lý phiên bản tài liệu
   - `DocumentUserPermission`: Quản lý quyền truy cập tài liệu

3. **Controllers**:
   - `DocumentController`: Xử lý các request liên quan đến tài liệu
   - `DocumentFolderController`: Xử lý các request liên quan đến thư mục
   - `DocumentVersionController`: Xử lý các request liên quan đến phiên bản tài liệu
   - `DocumentSyncController`: Xử lý đồng bộ hóa tài liệu

4. **Routes**:
   - Đã thêm các routes cần thiết cho việc quản lý tài liệu, thư mục, phiên bản và đồng bộ hóa

## Các API Endpoints

### Quản lý Tài liệu

| Phương thức | Endpoint | Mô tả |
|-------------|----------|-------|
| GET | `/api/teams/{team_id}/documents` | Lấy danh sách tài liệu trong nhóm |
| POST | `/api/teams/{team_id}/documents` | Tải lên tài liệu mới |
| GET | `/api/documents/{document_id}` | Lấy chi tiết tài liệu |
| PUT | `/api/documents/{document_id}` | Cập nhật thông tin tài liệu |
| DELETE | `/api/documents/{document_id}` | Xóa tài liệu |
| GET | `/api/documents/{document_id}/download` | Tải xuống tài liệu |
| PUT | `/api/documents/{document_id}/access` | Cập nhật quyền truy cập tài liệu |

### Quản lý Thư mục

| Phương thức | Endpoint | Mô tả |
|-------------|----------|-------|
| GET | `/api/teams/{team_id}/folders` | Lấy danh sách thư mục |
| POST | `/api/teams/{team_id}/folders` | Tạo thư mục mới |
| GET | `/api/folders/{folder_id}` | Lấy chi tiết thư mục |
| PUT | `/api/folders/{folder_id}` | Cập nhật thư mục |
| DELETE | `/api/folders/{folder_id}` | Xóa thư mục |

### Quản lý Phiên bản Tài liệu

| Phương thức | Endpoint | Mô tả |
|-------------|----------|-------|
| GET | `/api/documents/{document_id}/versions` | Lấy danh sách phiên bản |
| POST | `/api/documents/{document_id}/versions` | Tải lên phiên bản mới |
| GET | `/api/documents/{document_id}/versions/{version_number}` | Lấy chi tiết phiên bản |
| GET | `/api/documents/{document_id}/versions/{version_number}/download` | Tải xuống phiên bản cụ thể |
| POST | `/api/documents/{document_id}/versions/{version_number}/restore` | Khôi phục phiên bản cũ |

### Đồng bộ hóa Tài liệu

| Phương thức | Endpoint | Mô tả |
|-------------|----------|-------|
| GET | `/api/sync/documents` | Lấy tài liệu đã thay đổi |
| POST | `/api/sync/documents/resolve-conflicts` | Giải quyết xung đột |

## Cấu trúc Dữ liệu

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

## Quyền Truy cập

API hỗ trợ các loại quyền truy cập sau:

1. **public**: Tài liệu có thể được truy cập bởi bất kỳ ai
2. **team**: Tài liệu chỉ có thể được truy cập bởi thành viên trong nhóm
3. **private**: Tài liệu chỉ có thể được truy cập bởi người tải lên
4. **specific_users**: Tài liệu chỉ có thể được truy cập bởi người tải lên và những người dùng được chỉ định

## Giới hạn và Định dạng File

- **Giới hạn kích thước**: 100MB
- **Định dạng hỗ trợ**: Tất cả các định dạng file
- **Thumbnail**: Tự động tạo cho các file hình ảnh

## Đồng bộ hóa

API hỗ trợ đồng bộ hóa dữ liệu giữa thiết bị và server:

1. **Lấy thay đổi**: Lấy danh sách tài liệu đã thay đổi kể từ lần đồng bộ cuối
2. **Giải quyết xung đột**: Giải quyết xung đột giữa phiên bản trên thiết bị và server

## Kết luận

Chức năng Chia sẻ tài liệu đã được triển khai đầy đủ trong API Laravel. Các endpoint và cấu trúc dữ liệu đã được thiết kế để hỗ trợ tất cả các chức năng cần thiết cho ứng dụng Android.
