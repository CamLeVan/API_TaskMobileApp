# Hướng dẫn Triển khai Chức năng Chia sẻ Tài liệu trên Android - Phần 3

## Đồng bộ hóa Offline (Offline Synchronization)

Để hỗ trợ chức năng offline, chúng ta cần triển khai cơ chế đồng bộ hóa dữ liệu giữa thiết bị và server.

### 1. WorkManager để đồng bộ hóa

```kotlin
// sync/DocumentSyncWorker.kt
class DocumentSyncWorker(
    context: Context,
    params: WorkerParameters
) : CoroutineWorker(context, params) {
    
    @Inject
    lateinit var documentRepository: DocumentRepository
    
    @Inject
    lateinit var preferencesManager: PreferencesManager
    
    override suspend fun doWork(): Result {
        try {
            val lastSyncTime = preferencesManager.getLastDocumentSyncTime()
            val teamId = inputData.getLong("team_id", -1L)
            
            if (teamId == -1L) {
                return Result.failure()
            }
            
            // Đồng bộ hóa dữ liệu từ server
            val syncResult = documentRepository.syncDocumentsFromServer(lastSyncTime, teamId)
            
            if (syncResult is Resource.Success) {
                // Đồng bộ hóa dữ liệu từ thiết bị lên server
                val localChangesResult = documentRepository.syncLocalChangesToServer()
                
                if (localChangesResult is Resource.Success) {
                    // Cập nhật thời gian đồng bộ hóa
                    preferencesManager.setLastDocumentSyncTime(DateTimeUtils.getCurrentIsoDateTime())
                    return Result.success()
                }
            }
            
            return Result.retry()
        } catch (e: Exception) {
            return Result.retry()
        }
    }
}
```

### 2. Thiết lập WorkManager

```kotlin
// sync/SyncManager.kt
class SyncManager @Inject constructor(
    private val workManager: WorkManager,
    private val preferencesManager: PreferencesManager
) {
    // Thiết lập đồng bộ hóa định kỳ
    fun setupPeriodicSync(teamId: Long) {
        val constraints = Constraints.Builder()
            .setRequiredNetworkType(NetworkType.CONNECTED)
            .build()
            
        val syncRequest = PeriodicWorkRequestBuilder<DocumentSyncWorker>(
            15, TimeUnit.MINUTES
        ).setConstraints(constraints)
            .setInputData(workDataOf("team_id" to teamId))
            .setBackoffCriteria(BackoffPolicy.LINEAR, 10, TimeUnit.MINUTES)
            .build()
            
        workManager.enqueueUniquePeriodicWork(
            "document_sync_$teamId",
            ExistingPeriodicWorkPolicy.REPLACE,
            syncRequest
        )
    }
    
    // Đồng bộ hóa ngay lập tức
    fun syncNow(teamId: Long) {
        val constraints = Constraints.Builder()
            .setRequiredNetworkType(NetworkType.CONNECTED)
            .build()
            
        val syncRequest = OneTimeWorkRequestBuilder<DocumentSyncWorker>()
            .setConstraints(constraints)
            .setInputData(workDataOf("team_id" to teamId))
            .build()
            
        workManager.enqueueUniqueWork(
            "document_sync_now_$teamId",
            ExistingWorkPolicy.REPLACE,
            syncRequest
        )
    }
    
    // Hủy đồng bộ hóa
    fun cancelSync(teamId: Long) {
        workManager.cancelUniqueWork("document_sync_$teamId")
    }
}
```

## Xử lý File (File Handling)

### 1. Tải xuống và lưu trữ file

```kotlin
// utils/FileManager.kt
class FileManager @Inject constructor(
    private val context: Context
) {
    // Lưu file vào bộ nhớ trong
    suspend fun saveFileToInternalStorage(
        fileName: String,
        fileContent: InputStream
    ): File {
        return withContext(Dispatchers.IO) {
            val file = File(context.filesDir, fileName)
            file.outputStream().use { output ->
                fileContent.copyTo(output)
            }
            file
        }
    }
    
    // Lưu file vào bộ nhớ ngoài
    suspend fun saveFileToExternalStorage(
        fileName: String,
        fileContent: InputStream,
        mimeType: String
    ): Uri? {
        return withContext(Dispatchers.IO) {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                val contentValues = ContentValues().apply {
                    put(MediaStore.MediaColumns.DISPLAY_NAME, fileName)
                    put(MediaStore.MediaColumns.MIME_TYPE, mimeType)
                    put(MediaStore.MediaColumns.RELATIVE_PATH, Environment.DIRECTORY_DOWNLOADS)
                }
                
                val resolver = context.contentResolver
                val uri = resolver.insert(MediaStore.Downloads.EXTERNAL_CONTENT_URI, contentValues)
                
                uri?.let {
                    resolver.openOutputStream(it)?.use { output ->
                        fileContent.copyTo(output)
                    }
                }
                
                uri
            } else {
                val downloadsDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS)
                val file = File(downloadsDir, fileName)
                
                file.outputStream().use { output ->
                    fileContent.copyTo(output)
                }
                
                Uri.fromFile(file)
            }
        }
    }
    
    // Tạo file tạm thời
    suspend fun createTempFile(
        fileName: String,
        fileContent: InputStream
    ): File {
        return withContext(Dispatchers.IO) {
            val file = File.createTempFile("temp_", fileName, context.cacheDir)
            file.outputStream().use { output ->
                fileContent.copyTo(output)
            }
            file
        }
    }
    
    // Xóa file
    suspend fun deleteFile(file: File): Boolean {
        return withContext(Dispatchers.IO) {
            file.delete()
        }
    }
}
```

### 2. Xử lý tải xuống tài liệu

```kotlin
// repository/DocumentDownloadManager.kt
class DocumentDownloadManager @Inject constructor(
    private val apiService: DocumentApiService,
    private val fileManager: FileManager,
    private val notificationManager: NotificationManagerCompat,
    private val context: Context
) {
    // Tải xuống tài liệu
    suspend fun downloadDocument(
        document: Document,
        saveToExternalStorage: Boolean = true
    ): Flow<DownloadStatus> = flow {
        emit(DownloadStatus.Downloading(0))
        
        try {
            val response = apiService.downloadDocument(document.id)
            
            if (!response.isSuccessful) {
                emit(DownloadStatus.Error("Download failed: ${response.code()}"))
                return@flow
            }
            
            val body = response.body() ?: run {
                emit(DownloadStatus.Error("Empty response"))
                return@flow
            }
            
            val contentLength = body.contentLength()
            val inputStream = body.byteStream()
            
            val fileName = document.name
            val mimeType = document.fileType
            
            val result = if (saveToExternalStorage) {
                fileManager.saveFileToExternalStorage(fileName, inputStream, mimeType)?.let { uri ->
                    DownloadStatus.Success(uri)
                } ?: DownloadStatus.Error("Failed to save file")
            } else {
                val file = fileManager.saveFileToInternalStorage(fileName, inputStream)
                DownloadStatus.Success(Uri.fromFile(file))
            }
            
            emit(result)
        } catch (e: Exception) {
            emit(DownloadStatus.Error("Download failed: ${e.message}"))
        }
    }
}

// Download status
sealed class DownloadStatus {
    data class Downloading(val progress: Int) : DownloadStatus()
    data class Success(val uri: Uri) : DownloadStatus()
    data class Error(val message: String) : DownloadStatus()
}
```

## Tích hợp với Dependency Injection (Hilt)

```kotlin
// di/DocumentModule.kt
@Module
@InstallIn(SingletonComponent::class)
object DocumentModule {
    @Provides
    @Singleton
    fun provideDocumentApiService(retrofit: Retrofit): DocumentApiService {
        return retrofit.create(DocumentApiService::class.java)
    }
    
    @Provides
    @Singleton
    fun provideDocumentDao(database: AppDatabase): DocumentDao {
        return database.documentDao()
    }
    
    @Provides
    @Singleton
    fun provideDocumentFolderDao(database: AppDatabase): DocumentFolderDao {
        return database.documentFolderDao()
    }
    
    @Provides
    @Singleton
    fun provideDocumentRepository(
        apiService: DocumentApiService,
        documentDao: DocumentDao,
        folderDao: DocumentFolderDao
    ): DocumentRepository {
        return DocumentRepository(apiService, documentDao, folderDao)
    }
    
    @Provides
    @Singleton
    fun provideDocumentDownloadManager(
        apiService: DocumentApiService,
        fileManager: FileManager,
        notificationManager: NotificationManagerCompat,
        @ApplicationContext context: Context
    ): DocumentDownloadManager {
        return DocumentDownloadManager(apiService, fileManager, notificationManager, context)
    }
    
    @Provides
    @Singleton
    fun provideSyncManager(
        workManager: WorkManager,
        preferencesManager: PreferencesManager
    ): SyncManager {
        return SyncManager(workManager, preferencesManager)
    }
}
```
