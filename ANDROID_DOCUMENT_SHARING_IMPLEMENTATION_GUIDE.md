# Hướng dẫn triển khai tính năng Chia sẻ Tài liệu trên Android

## 1. Tổng quan

Tài liệu này cung cấp hướng dẫn chi tiết để triển khai tính năng Chia sẻ Tài liệu trong ứng dụng Android, phù hợp với API đã được triển khai trên server.

## 2. Cấu trúc dữ liệu

### 2.1 Các bảng cần đồng bộ

| Bảng | Mô tả | Đồng bộ |
|------|-------|---------|
| `documents` | Tài liệu | Đồng bộ hai chiều |
| `document_versions` | Phiên bản tài liệu | Đồng bộ hai chiều |

### 2.2 Các bảng chỉ lưu trên server

| Bảng | Mô tả |
|------|-------|
| `document_folders` | Thư mục tài liệu |
| `document_user_permissions` | Quyền truy cập tài liệu |

## 3. Cấu trúc Entity

### 3.1 TeamDocumentEntity

```kotlin
@Entity(tableName = "team_documents")
data class TeamDocumentEntity(
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
    val isDeleted: Int = 0,
    val isLocallyModified: Int = 0,
    val localFilePath: String? = null
)
```

### 3.2 DocumentVersionEntity

```kotlin
@Entity(
    tableName = "document_versions",
    foreignKeys = [
        ForeignKey(
            entity = TeamDocumentEntity::class,
            parentColumns = ["id"],
            childColumns = ["documentId"],
            onDelete = ForeignKey.CASCADE
        )
    ],
    indices = [Index("documentId")]
)
data class DocumentVersionEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val documentId: Long,
    val versionNumber: Int,
    val fileUrl: String,
    val thumbnailUrl: String?,
    val fileSize: Long,
    val createdById: Long,
    val createdByName: String,
    val createdByAvatar: String?,
    val versionNote: String?,
    val createdAt: String,
    val isLocallyModified: Int = 0,
    val localFilePath: String? = null
)
```

## 4. DAO (Data Access Object)

### 4.1 TeamDocumentDao

```kotlin
@Dao
interface TeamDocumentDao {
    @Query("SELECT * FROM team_documents WHERE teamId = :teamId AND isDeleted = 0")
    fun getTeamDocuments(teamId: Long): Flow<List<TeamDocumentEntity>>
    
    @Query("SELECT * FROM team_documents WHERE id = :documentId")
    fun getDocument(documentId: Long): Flow<TeamDocumentEntity?>
    
    @Query("SELECT * FROM team_documents WHERE folderId = :folderId AND isDeleted = 0")
    fun getDocumentsByFolder(folderId: Long): Flow<List<TeamDocumentEntity>>
    
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAll(documents: List<TeamDocumentEntity>)
    
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(document: TeamDocumentEntity): Long
    
    @Update
    suspend fun update(document: TeamDocumentEntity)
    
    @Query("UPDATE team_documents SET isDeleted = 1 WHERE id = :documentId")
    suspend fun softDelete(documentId: Long)
}
```

### 4.2 DocumentVersionDao

```kotlin
@Dao
interface DocumentVersionDao {
    @Query("SELECT * FROM document_versions WHERE documentId = :documentId ORDER BY versionNumber DESC")
    fun getVersions(documentId: Long): Flow<List<DocumentVersionEntity>>
    
    @Query("SELECT * FROM document_versions WHERE documentId = :documentId AND versionNumber = :versionNumber")
    fun getVersion(documentId: Long, versionNumber: Int): Flow<DocumentVersionEntity?>
    
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAll(versions: List<DocumentVersionEntity>)
    
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(version: DocumentVersionEntity): Long
}
```

## 5. Migration

Thêm migration để tạo các bảng mới:

```kotlin
val MIGRATION_x_y = object : Migration(x, y) { // Thay x, y bằng phiên bản cũ và mới của DB
    override fun migrate(database: SupportSQLiteDatabase) {
        // Tạo bảng team_documents
        database.execSQL("""
            CREATE TABLE IF NOT EXISTS `team_documents` (
                `id` INTEGER PRIMARY KEY NOT NULL,
                `name` TEXT NOT NULL,
                `description` TEXT,
                `fileUrl` TEXT NOT NULL,
                `thumbnailUrl` TEXT,
                `fileType` TEXT NOT NULL,
                `fileSize` INTEGER NOT NULL,
                `folderId` INTEGER,
                `teamId` INTEGER NOT NULL,
                `uploadedById` INTEGER NOT NULL,
                `uploadedByName` TEXT NOT NULL,
                `uploadedByAvatar` TEXT,
                `accessLevel` TEXT NOT NULL,
                `currentVersion` INTEGER NOT NULL,
                `createdAt` TEXT NOT NULL,
                `updatedAt` TEXT NOT NULL,
                `isDeleted` INTEGER NOT NULL DEFAULT 0,
                `isLocallyModified` INTEGER NOT NULL DEFAULT 0,
                `localFilePath` TEXT
            )
        """)
        
        // Tạo bảng document_versions
        database.execSQL("""
            CREATE TABLE IF NOT EXISTS `document_versions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                `documentId` INTEGER NOT NULL,
                `versionNumber` INTEGER NOT NULL,
                `fileUrl` TEXT NOT NULL,
                `thumbnailUrl` TEXT,
                `fileSize` INTEGER NOT NULL,
                `createdById` INTEGER NOT NULL,
                `createdByName` TEXT NOT NULL,
                `createdByAvatar` TEXT,
                `versionNote` TEXT,
                `createdAt` TEXT NOT NULL,
                `isLocallyModified` INTEGER NOT NULL DEFAULT 0,
                `localFilePath` TEXT,
                FOREIGN KEY(`documentId`) REFERENCES `team_documents`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE
            )
        """)
        
        database.execSQL("CREATE INDEX IF NOT EXISTS `index_document_versions_documentId` ON `document_versions` (`documentId`)")
    }
}
```

## 6. Cập nhật AppDatabase

```kotlin
@Database(
    entities = [
        UserEntity::class,
        TaskEntity::class,
        // Các entity khác
        TeamDocumentEntity::class,
        DocumentVersionEntity::class
    ],
    version = Y, // Tăng version lên
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    // Các DAO khác
    abstract fun teamDocumentDao(): TeamDocumentDao
    abstract fun documentVersionDao(): DocumentVersionDao
}
```
