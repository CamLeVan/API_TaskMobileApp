# Hướng dẫn Triển khai Chức năng Chia sẻ Tài liệu trên Android (Android Implementation Guide)

## Tổng quan (Overview)

Tài liệu này cung cấp hướng dẫn chi tiết về cách triển khai chức năng Chia sẻ tài liệu trong ứng dụng Android, sử dụng API đã được phát triển. Chức năng này cho phép người dùng tải lên, quản lý và chia sẻ tài liệu trong nhóm.

## Cấu trúc dữ liệu (Data Structures)

### 1. Model Classes

Đầu tiên, tạo các model class để đại diện cho dữ liệu từ API:

```kotlin
// models/Document.kt
data class Document(
    val id: Long,
    val name: String,
    val description: String?,
    val fileUrl: String,
    val thumbnailUrl: String?,
    val fileType: String,
    val fileSize: Long,
    val folderId: Long?,
    val teamId: Long,
    val uploadedBy: User,
    val accessLevel: String,
    val allowedUsers: List<User>,
    val currentVersion: Int,
    val createdAt: String,
    val updatedAt: String
)

// models/DocumentFolder.kt
data class DocumentFolder(
    val id: Long,
    val name: String,
    val description: String?,
    val parentId: Long?,
    val teamId: Long,
    val createdBy: User,
    val documentCount: Int,
    val subfolderCount: Int,
    val createdAt: String,
    val updatedAt: String
)

// models/DocumentVersion.kt
data class DocumentVersion(
    val id: Long,
    val documentId: Long,
    val versionNumber: Int,
    val fileUrl: String,
    val thumbnailUrl: String?,
    val fileSize: Long,
    val createdBy: User,
    val versionNote: String?,
    val createdAt: String,
    val updatedAt: String
)
```

### 2. Room Entities

Để hỗ trợ chức năng offline, tạo các entity cho Room Database:

```kotlin
// database/entities/DocumentEntity.kt
@Entity(tableName = "documents")
data class DocumentEntity(
    @PrimaryKey val id: Long,
    val name: String,
    val description: String?,
    val fileUrl: String,
    val thumbnailUrl: String?,
    val fileType: String,
    val fileSize: Long,
    val folderId: Long?,
    val teamId: Long,
    val uploadedById: Long,
    val uploadedByName: String,
    val uploadedByAvatar: String?,
    val accessLevel: String,
    val currentVersion: Int,
    val createdAt: String,
    val updatedAt: String,
    val isLocallyModified: Boolean = false,
    val localFilePath: String? = null
)

// database/entities/DocumentFolderEntity.kt
@Entity(tableName = "document_folders")
data class DocumentFolderEntity(
    @PrimaryKey val id: Long,
    val name: String,
    val description: String?,
    val parentId: Long?,
    val teamId: Long,
    val createdById: Long,
    val createdByName: String,
    val createdByAvatar: String?,
    val createdAt: String,
    val updatedAt: String,
    val isLocallyModified: Boolean = false
)
```

## API Service (Network Layer)

Tạo interface để gọi các API endpoint:

```kotlin
// DocumentApiService.kt
interface DocumentApiService {
    // Document endpoints
    @GET("teams/{teamId}/documents")
    suspend fun getDocuments(
        @Path("teamId") teamId: Long,
        @Query("folder_id") folderId: Long?,
        @Query("search") search: String?,
        @Query("file_type") fileType: String?,
        @Query("sort_by") sortBy: String?,
        @Query("sort_direction") sortDirection: String?,
        @Query("page") page: Int?,
        @Query("per_page") perPage: Int?
    ): Response<PaginatedResponse<Document>>

    @Multipart
    @POST("teams/{teamId}/documents")
    suspend fun uploadDocument(
        @Path("teamId") teamId: Long,
        @Part file: MultipartBody.Part,
        @Part("name") name: RequestBody?,
        @Part("description") description: RequestBody?,
        @Part("folder_id") folderId: RequestBody?,
        @Part("access_level") accessLevel: RequestBody?,
        @Part("allowed_users") allowedUsers: RequestBody?
    ): Response<DocumentResponse>

    // Thêm các endpoint khác theo tài liệu API
}
```

## Repository (Data Layer)

Tạo repository để xử lý logic nghiệp vụ:

```kotlin
// DocumentRepository.kt
class DocumentRepository(
    private val apiService: DocumentApiService,
    private val documentDao: DocumentDao,
    private val folderDao: DocumentFolderDao
) {
    // Lấy danh sách tài liệu từ API và lưu vào database
    suspend fun getDocuments(
        teamId: Long,
        folderId: Long? = null,
        forceRefresh: Boolean = false
    ): Flow<Resource<List<Document>>> = flow {
        emit(Resource.Loading())

        // Trả về dữ liệu từ database trước
        val localDocuments = documentDao.getDocumentsByTeamAndFolder(teamId, folderId)
        emit(Resource.Loading(data = localDocuments.map { it.toDocument() }))

        // Nếu không cần refresh và có dữ liệu local, trả về dữ liệu local
        if (!forceRefresh && localDocuments.isNotEmpty()) {
            emit(Resource.Success(localDocuments.map { it.toDocument() }))
            return@flow
        }

        // Lấy dữ liệu từ API
        try {
            val response = apiService.getDocuments(teamId, folderId, null, null, null, null, null, null)
            if (response.isSuccessful) {
                response.body()?.data?.let { documents ->
                    // Lưu vào database
                    documentDao.insertDocuments(documents.map { it.toEntity() })
                    emit(Resource.Success(documents))
                } ?: emit(Resource.Error("Empty response"))
            } else {
                emit(Resource.Error("Failed to fetch documents: ${response.code()}"))
            }
        } catch (e: Exception) {
            emit(Resource.Error("Network error: ${e.message}"))
        }
    }

    // Tải lên tài liệu
    suspend fun uploadDocument(
        teamId: Long,
        file: File,
        name: String? = null,
        description: String? = null,
        folderId: Long? = null,
        accessLevel: String? = null,
        allowedUsers: List<Long>? = null
    ): Flow<Resource<Document>> = flow {
        emit(Resource.Loading())

        try {
            // Chuẩn bị file để upload
            val fileRequestBody = RequestBody.create(
                MediaType.parse(getMimeType(file.path) ?: "application/octet-stream"),
                file
            )
            val filePart = MultipartBody.Part.createFormData("file", file.name, fileRequestBody)

            // Chuẩn bị các tham số khác
            val nameBody = name?.let { RequestBody.create(MediaType.parse("text/plain"), it) }
            val descriptionBody = description?.let { RequestBody.create(MediaType.parse("text/plain"), it) }
            val folderIdBody = folderId?.let { RequestBody.create(MediaType.parse("text/plain"), it.toString()) }
            val accessLevelBody = accessLevel?.let { RequestBody.create(MediaType.parse("text/plain"), it) }
            val allowedUsersBody = allowedUsers?.let {
                RequestBody.create(MediaType.parse("application/json"), Gson().toJson(it))
            }

            // Gọi API
            val response = apiService.uploadDocument(
                teamId, filePart, nameBody, descriptionBody, folderIdBody, accessLevelBody, allowedUsersBody
            )

            if (response.isSuccessful) {
                response.body()?.data?.let { document ->
                    // Lưu vào database
                    documentDao.insertDocument(document.toEntity())
                    emit(Resource.Success(document))
                } ?: emit(Resource.Error("Empty response"))
            } else {
                emit(Resource.Error("Failed to upload document: ${response.code()}"))
            }
        } catch (e: Exception) {
            emit(Resource.Error("Network error: ${e.message}"))
        }
    }

    // Thêm các phương thức khác cho các chức năng còn lại
}
```
