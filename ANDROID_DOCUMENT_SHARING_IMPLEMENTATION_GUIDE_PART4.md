# Hướng dẫn triển khai tính năng Chia sẻ Tài liệu trên Android (Phần 4)

## 13. Xử lý Migration

Để giải quyết lỗi migration hiện tại, bạn cần thực hiện các bước sau:

### 13.1 Cập nhật version của Room Database

Đầu tiên, cập nhật version của Room Database trong `AppDatabase.kt`:

```kotlin
@Database(
    entities = [
        UserEntity::class,
        TaskEntity::class,
        // Các entity khác
        TeamDocumentEntity::class,
        DocumentVersionEntity::class
    ],
    version = Y, // Tăng version lên (ví dụ: từ 1 lên 2)
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    // Các DAO khác
    abstract fun teamDocumentDao(): TeamDocumentDao
    abstract fun documentVersionDao(): DocumentVersionDao
}
```

### 13.2 Thêm Migration

Tạo migration để xử lý việc thêm bảng mới:

```kotlin
val MIGRATION_1_2 = object : Migration(1, 2) {
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

### 13.3 Đăng ký Migration trong Database Builder

Cập nhật cách tạo instance của Room Database để bao gồm migration:

```kotlin
@Singleton
@Provides
fun provideAppDatabase(@ApplicationContext context: Context): AppDatabase {
    return Room.databaseBuilder(
        context,
        AppDatabase::class.java,
        "app_database"
    )
    .addMigrations(MIGRATION_1_2)
    .build()
}
```

### 13.4 Giải pháp tạm thời (chỉ cho môi trường phát triển)

Nếu bạn đang trong giai đoạn phát triển và không cần giữ lại dữ liệu hiện có, bạn có thể sử dụng `fallbackToDestructiveMigration()`:

```kotlin
@Singleton
@Provides
fun provideAppDatabase(@ApplicationContext context: Context): AppDatabase {
    return Room.databaseBuilder(
        context,
        AppDatabase::class.java,
        "app_database"
    )
    .fallbackToDestructiveMigration() // Chỉ sử dụng trong môi trường phát triển!
    .build()
}
```

Hoặc xóa dữ liệu ứng dụng:
- Settings > Apps > [Tên ứng dụng] > Storage > Clear Data

## 14. Xử lý tải xuống và lưu trữ tài liệu

### 14.1 FileManager

```kotlin
class FileManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val documentsDir: File by lazy {
        File(context.filesDir, "documents").apply {
            if (!exists()) mkdirs()
        }
    }
    
    fun getDocumentFile(documentId: Long, fileName: String): File {
        val sanitizedFileName = sanitizeFileName(fileName)
        val documentDir = File(documentsDir, documentId.toString()).apply {
            if (!exists()) mkdirs()
        }
        return File(documentDir, sanitizedFileName)
    }
    
    fun getVersionFile(documentId: Long, versionNumber: Int, fileName: String): File {
        val sanitizedFileName = sanitizeFileName(fileName)
        val documentDir = File(documentsDir, "$documentId/versions/$versionNumber").apply {
            if (!exists()) mkdirs()
        }
        return File(documentDir, sanitizedFileName)
    }
    
    private fun sanitizeFileName(fileName: String): String {
        return fileName.replace("[\\\\/:*?\"<>|]".toRegex(), "_")
    }
    
    fun saveDocumentFromStream(
        inputStream: InputStream,
        outputFile: File,
        progressCallback: ((Int) -> Unit)? = null
    ): Boolean {
        return try {
            outputFile.outputStream().use { outputStream ->
                val buffer = ByteArray(DEFAULT_BUFFER_SIZE)
                var bytesRead: Int
                var totalBytesRead: Long = 0
                val contentLength = inputStream.available().toLong()
                
                while (inputStream.read(buffer).also { bytesRead = it } != -1) {
                    outputStream.write(buffer, 0, bytesRead)
                    totalBytesRead += bytesRead
                    
                    if (contentLength > 0) {
                        val progress = ((totalBytesRead * 100) / contentLength).toInt()
                        progressCallback?.invoke(progress)
                    }
                }
            }
            true
        } catch (e: Exception) {
            Log.e("FileManager", "Error saving document", e)
            outputFile.delete() // Clean up partial file
            false
        }
    }
    
    fun openDocument(file: File) {
        val uri = FileProvider.getUriForFile(
            context,
            "${context.packageName}.fileprovider",
            file
        )
        
        val mimeType = getMimeType(file.name)
        val intent = Intent(Intent.ACTION_VIEW).apply {
            setDataAndType(uri, mimeType)
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }
        
        if (intent.resolveActivity(context.packageManager) != null) {
            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            context.startActivity(intent)
        } else {
            // Không có ứng dụng nào có thể mở file này
            Toast.makeText(
                context,
                "Không tìm thấy ứng dụng để mở file này",
                Toast.LENGTH_SHORT
            ).show()
        }
    }
    
    private fun getMimeType(fileName: String): String {
        return when {
            fileName.endsWith(".pdf", ignoreCase = true) -> "application/pdf"
            fileName.endsWith(".doc", ignoreCase = true) -> "application/msword"
            fileName.endsWith(".docx", ignoreCase = true) -> "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            fileName.endsWith(".xls", ignoreCase = true) -> "application/vnd.ms-excel"
            fileName.endsWith(".xlsx", ignoreCase = true) -> "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            fileName.endsWith(".ppt", ignoreCase = true) -> "application/vnd.ms-powerpoint"
            fileName.endsWith(".pptx", ignoreCase = true) -> "application/vnd.openxmlformats-officedocument.presentationml.presentation"
            fileName.endsWith(".jpg", ignoreCase = true) -> "image/jpeg"
            fileName.endsWith(".png", ignoreCase = true) -> "image/png"
            fileName.endsWith(".txt", ignoreCase = true) -> "text/plain"
            else -> "*/*"
        }
    }
}
```

## 15. Cập nhật AndroidManifest.xml

Thêm FileProvider để chia sẻ tài liệu với các ứng dụng khác:

```xml
<manifest ...>
    <!-- Các permission -->
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
    <uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
    <uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
    
    <application ...>
        <!-- Các activity và service -->
        
        <provider
            android:name="androidx.core.content.FileProvider"
            android:authorities="${applicationId}.fileprovider"
            android:exported="false"
            android:grantUriPermissions="true">
            <meta-data
                android:name="android.support.FILE_PROVIDER_PATHS"
                android:resource="@xml/file_paths" />
        </provider>
    </application>
</manifest>
```

Tạo file `res/xml/file_paths.xml`:

```xml
<?xml version="1.0" encoding="utf-8"?>
<paths>
    <files-path name="documents" path="documents/" />
    <cache-path name="cache" path="/" />
    <external-files-path name="external_files" path="." />
    <external-cache-path name="external_cache" path="." />
</paths>
```
