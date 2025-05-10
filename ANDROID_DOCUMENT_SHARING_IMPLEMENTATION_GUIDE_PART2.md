# Hướng dẫn triển khai tính năng Chia sẻ Tài liệu trên Android (Phần 2)

## 7. DTO (Data Transfer Object)

### 7.1 DocumentDto

```kotlin
data class DocumentDto(
    val id: Long,
    val name: String,
    val description: String?,
    val file_url: String,
    val thumbnail_url: String?,
    val file_type: String,
    val file_size: Long,
    val folder_id: Long?,
    val team_id: Long,
    val uploaded_by: UserDto,
    val access_level: String,
    val current_version: Int,
    val created_at: String,
    val updated_at: String
)
```

### 7.2 DocumentVersionDto

```kotlin
data class DocumentVersionDto(
    val id: Long,
    val document_id: Long,
    val version_number: Int,
    val file_url: String,
    val thumbnail_url: String?,
    val file_size: Long,
    val created_by: UserDto,
    val version_note: String?,
    val created_at: String
)
```

### 7.3 DocumentFolderDto

```kotlin
data class DocumentFolderDto(
    val id: Long,
    val name: String,
    val description: String?,
    val parent_id: Long?,
    val team_id: Long,
    val created_by: UserDto,
    val document_count: Int,
    val subfolder_count: Int,
    val created_at: String,
    val updated_at: String
)
```

## 8. Mapper

### 8.1 DocumentMapper

```kotlin
fun DocumentDto.toEntity() = TeamDocumentEntity(
    id = id,
    name = name,
    description = description,
    fileUrl = file_url,
    thumbnailUrl = thumbnail_url,
    fileType = file_type,
    fileSize = file_size,
    folderId = folder_id,
    teamId = team_id,
    uploadedById = uploaded_by.id,
    uploadedByName = uploaded_by.name,
    uploadedByAvatar = uploaded_by.avatar,
    accessLevel = access_level,
    currentVersion = current_version,
    createdAt = created_at,
    updatedAt = updated_at
)

fun TeamDocumentEntity.toModel() = Document(
    id = id,
    name = name,
    description = description,
    fileUrl = fileUrl,
    thumbnailUrl = thumbnailUrl,
    fileType = fileType,
    fileSize = fileSize,
    folderId = folderId,
    teamId = teamId,
    uploadedBy = User(
        id = uploadedById,
        name = uploadedByName,
        avatar = uploadedByAvatar
    ),
    accessLevel = accessLevel,
    currentVersion = currentVersion,
    createdAt = createdAt,
    updatedAt = updatedAt,
    localFilePath = localFilePath
)
```

### 8.2 DocumentVersionMapper

```kotlin
fun DocumentVersionDto.toEntity(documentId: Long) = DocumentVersionEntity(
    documentId = documentId,
    versionNumber = version_number,
    fileUrl = file_url,
    thumbnailUrl = thumbnail_url,
    fileSize = file_size,
    createdById = created_by.id,
    createdByName = created_by.name,
    createdByAvatar = created_by.avatar,
    versionNote = version_note,
    createdAt = created_at
)

fun DocumentVersionEntity.toModel() = DocumentVersion(
    id = id,
    documentId = documentId,
    versionNumber = versionNumber,
    fileUrl = fileUrl,
    thumbnailUrl = thumbnailUrl,
    fileSize = fileSize,
    createdBy = User(
        id = createdById,
        name = createdByName,
        avatar = createdByAvatar
    ),
    versionNote = versionNote,
    createdAt = createdAt,
    localFilePath = localFilePath
)
```

## 9. API Service

```kotlin
interface ApiService {
    // Các endpoint khác
    
    @GET("teams/{teamId}/documents")
    suspend fun getTeamDocuments(
        @Path("teamId") teamId: Long,
        @Query("folder_id") folderId: Long? = null,
        @Query("search") search: String? = null,
        @Query("file_type") fileType: String? = null,
        @Query("sort_by") sortBy: String? = null,
        @Query("sort_direction") sortDirection: String? = null,
        @Query("page") page: Int? = null,
        @Query("per_page") perPage: Int? = null
    ): ApiResponse<List<DocumentDto>>
    
    @GET("documents/{documentId}")
    suspend fun getDocument(
        @Path("documentId") documentId: Long
    ): ApiResponse<DocumentDto>
    
    @Multipart
    @POST("teams/{teamId}/documents")
    suspend fun uploadDocument(
        @Path("teamId") teamId: Long,
        @Part file: MultipartBody.Part,
        @Part("name") name: RequestBody,
        @Part("description") description: RequestBody?,
        @Part("folder_id") folderId: RequestBody?,
        @Part("access_level") accessLevel: RequestBody?
    ): ApiResponse<DocumentDto>
    
    @PUT("documents/{documentId}")
    suspend fun updateDocument(
        @Path("documentId") documentId: Long,
        @Body request: UpdateDocumentRequest
    ): ApiResponse<DocumentDto>
    
    @DELETE("documents/{documentId}")
    suspend fun deleteDocument(
        @Path("documentId") documentId: Long
    ): ApiResponse<Unit>
    
    @GET("documents/{documentId}/download")
    @Streaming
    suspend fun downloadDocument(
        @Path("documentId") documentId: Long
    ): ResponseBody
    
    @GET("documents/{documentId}/versions")
    suspend fun getDocumentVersions(
        @Path("documentId") documentId: Long
    ): ApiResponse<List<DocumentVersionDto>>
    
    @Multipart
    @POST("documents/{documentId}/versions")
    suspend fun uploadNewVersion(
        @Path("documentId") documentId: Long,
        @Part file: MultipartBody.Part,
        @Part("version_note") versionNote: RequestBody?
    ): ApiResponse<DocumentVersionDto>
    
    @GET("teams/{teamId}/folders")
    suspend fun getTeamFolders(
        @Path("teamId") teamId: Long,
        @Query("parent_id") parentId: Long? = null
    ): ApiResponse<List<DocumentFolderDto>>
}
```

## 10. Repository

```kotlin
class DocumentRepository @Inject constructor(
    private val apiService: ApiService,
    private val teamDocumentDao: TeamDocumentDao,
    private val documentVersionDao: DocumentVersionDao,
    private val fileManager: FileManager
) {
    // Lấy danh sách tài liệu trong nhóm
    fun getTeamDocuments(teamId: Long, folderId: Long? = null) = 
        teamDocumentDao.getTeamDocuments(teamId).map { documents ->
            documents.filter { doc -> folderId == null || doc.folderId == folderId }
                .map { it.toModel() }
        }
    
    // Đồng bộ tài liệu từ server
    suspend fun refreshTeamDocuments(teamId: Long, folderId: Long? = null) {
        try {
            val response = apiService.getTeamDocuments(teamId, folderId)
            if (response.isSuccessful && response.data != null) {
                teamDocumentDao.insertAll(response.data.map { it.toEntity() })
            }
        } catch (e: Exception) {
            Log.e("DocumentRepo", "Error refreshing documents", e)
        }
    }
    
    // Lấy chi tiết tài liệu
    fun getDocument(documentId: Long) = 
        teamDocumentDao.getDocument(documentId).map { it?.toModel() }
    
    // Lấy phiên bản tài liệu
    fun getDocumentVersions(documentId: Long) =
        documentVersionDao.getVersions(documentId).map { versions ->
            versions.map { it.toModel() }
        }
    
    // Tải lên tài liệu mới
    suspend fun uploadDocument(
        teamId: Long,
        filePath: String,
        name: String,
        description: String? = null,
        folderId: Long? = null,
        accessLevel: String = "team"
    ): Result<Document> {
        return try {
            val file = File(filePath)
            val requestFile = file.asRequestBody("application/octet-stream".toMediaTypeOrNull())
            val filePart = MultipartBody.Part.createFormData("file", file.name, requestFile)
            
            val namePart = name.toRequestBody("text/plain".toMediaTypeOrNull())
            val descriptionPart = description?.toRequestBody("text/plain".toMediaTypeOrNull())
            val folderIdPart = folderId?.toString()?.toRequestBody("text/plain".toMediaTypeOrNull())
            val accessLevelPart = accessLevel.toRequestBody("text/plain".toMediaTypeOrNull())
            
            val response = apiService.uploadDocument(
                teamId, filePart, namePart, descriptionPart, folderIdPart, accessLevelPart
            )
            
            if (response.isSuccessful && response.data != null) {
                val entity = response.data.toEntity()
                teamDocumentDao.insert(entity)
                Result.success(entity.toModel())
            } else {
                Result.failure(Exception(response.message ?: "Unknown error"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
```
