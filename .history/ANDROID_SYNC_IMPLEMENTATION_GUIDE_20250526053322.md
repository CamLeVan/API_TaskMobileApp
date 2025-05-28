# 🔄 ANDROID SYNC IMPLEMENTATION GUIDE

## 📋 TỔNG QUAN

Tài liệu này mô tả chi tiết **tính năng đồng bộ hóa** đã được triển khai trong API và hướng dẫn team Android implement tương ứng để đảm bảo ứng dụng hoạt động mượt mà cả online và offline.

## 🎯 MỤC TIÊU ĐỒNG BỘ

### ✅ **Offline-First Architecture**
- App hoạt động bình thường khi không có mạng
- Dữ liệu được lưu local SQLite trước
- Sync khi có kết nối mạng

### ✅ **Multi-Device Support**
- Đồng bộ giữa nhiều thiết bị của cùng user
- Mỗi device có sync status riêng
- Conflict resolution khi có xung đột

### ✅ **Performance Optimization**
- Chỉ sync dữ liệu thay đổi (delta sync)
- Selective sync theo nhu cầu user
- Background sync không ảnh hưởng UI

## 🔄 5 LOẠI ĐỒNG BỘ ĐÃ TRIỂN KHAI

### 🚀 **1. INITIAL SYNC - Đồng bộ lần đầu**

#### **API Endpoint:**
```
POST /api/sync/initial
Content-Type: application/json
Authorization: Bearer {token}

{
    "device_id": "android_device_123"
}
```

#### **Response:**
```json
{
    "personal_tasks": [...],
    "teams": [...],
    "team_tasks": [...],
    "messages": {
        "team_1": [...],
        "team_2": [...]
    },
    "sync_time": "2024-01-20T10:30:00Z"
}
```

#### **Android Implementation:**
```kotlin
class SyncManager {
    suspend fun performInitialSync(): SyncResult {
        try {
            // 1. Call API
            val response = apiService.initialSync(
                InitialSyncRequest(deviceId = getDeviceId())
            )
            
            // 2. Clear local database
            localDb.clearAllData()
            
            // 3. Insert all data to local SQLite
            localDb.insertPersonalTasks(response.personalTasks)
            localDb.insertTeams(response.teams)
            localDb.insertTeamTasks(response.teamTasks)
            localDb.insertMessages(response.messages)
            
            // 4. Update sync status
            updateSyncStatus(response.syncTime)
            
            return SyncResult.Success
        } catch (e: Exception) {
            return SyncResult.Error(e.message)
        }
    }
}
```

#### **Khi nào sử dụng:**
- Lần đầu cài app và đăng nhập
- Khi user chọn "Reset & Re-sync"
- Khi có lỗi sync nghiêm trọng

---

### ⚡ **2. QUICK SYNC - Đồng bộ nhanh**

#### **API Endpoint:**
```
POST /api/sync/quick
Content-Type: application/json
Authorization: Bearer {token}

{
    "device_id": "android_device_123",
    "last_synced_at": "2024-01-20T10:00:00Z",
    "include": ["messages", "tasks", "teams"]
}
```

#### **Response:**
```json
{
    "messages": [...],
    "personal_tasks": {
        "created": [...],
        "updated": [...],
        "deleted": [1, 2, 3]
    },
    "team_tasks": {
        "created": [...],
        "updated": [...],
        "deleted": [4, 5, 6]
    },
    "sync_time": "2024-01-20T10:30:00Z"
}
```

#### **Android Implementation:**
```kotlin
suspend fun performQuickSync(): SyncResult {
    try {
        val lastSyncTime = getLastSyncTime()
        
        val response = apiService.quickSync(
            QuickSyncRequest(
                deviceId = getDeviceId(),
                lastSyncedAt = lastSyncTime,
                include = listOf("messages", "tasks", "teams")
            )
        )
        
        // Process incremental changes
        processIncrementalChanges(response)
        updateSyncStatus(response.syncTime)
        
        return SyncResult.Success
    } catch (e: Exception) {
        return SyncResult.Error(e.message)
    }
}

private fun processIncrementalChanges(response: QuickSyncResponse) {
    // Insert new items
    localDb.insertPersonalTasks(response.personalTasks.created)
    localDb.insertTeamTasks(response.teamTasks.created)
    
    // Update existing items
    localDb.updatePersonalTasks(response.personalTasks.updated)
    localDb.updateTeamTasks(response.teamTasks.updated)
    
    // Delete items
    localDb.deletePersonalTasks(response.personalTasks.deleted)
    localDb.deleteTeamTasks(response.teamTasks.deleted)
    
    // Insert new messages
    localDb.insertMessages(response.messages)
}
```

#### **Khi nào sử dụng:**
- Mỗi 15 phút (background sync)
- Khi mở app
- Khi có mạng trở lại sau offline
- User pull-to-refresh

---

### 📤 **3. PUSH SYNC - Đẩy thay đổi lên**

#### **API Endpoint:**
```
POST /api/sync/push
Content-Type: application/json
Authorization: Bearer {token}

{
    "device_id": "android_device_123",
    "changes": [
        {
            "type": "task",
            "action": "create",
            "local_id": "temp_123",
            "data": {
                "title": "New task created offline",
                "description": "Task description",
                "priority": 2
            }
        },
        {
            "type": "message",
            "action": "create",
            "local_id": "temp_msg_456",
            "data": {
                "team_id": 5,
                "message": "Message sent offline"
            }
        }
    ]
}
```

#### **Response:**
```json
{
    "results": [
        {
            "local_id": "temp_123",
            "server_id": 789,
            "status": "success"
        },
        {
            "local_id": "temp_msg_456",
            "server_id": 101112,
            "status": "success"
        }
    ],
    "sync_time": "2024-01-20T10:30:00Z"
}
```

#### **Android Implementation:**
```kotlin
suspend fun performPushSync(): SyncResult {
    try {
        // 1. Get pending changes from local queue
        val pendingChanges = localDb.getPendingChanges()
        
        if (pendingChanges.isEmpty()) {
            return SyncResult.Success
        }
        
        // 2. Send to server
        val response = apiService.pushSync(
            PushSyncRequest(
                deviceId = getDeviceId(),
                changes = pendingChanges
            )
        )
        
        // 3. Process results
        response.results.forEach { result ->
            when (result.status) {
                "success" -> {
                    // Update local ID with server ID
                    localDb.updateLocalId(result.localId, result.serverId)
                    localDb.markAsSynced(result.localId)
                }
                "error" -> {
                    // Handle error, maybe retry later
                    localDb.markAsError(result.localId, result.error)
                }
            }
        }
        
        return SyncResult.Success
    } catch (e: Exception) {
        return SyncResult.Error(e.message)
    }
}
```

#### **Khi nào sử dụng:**
- Khi có mạng trở lại sau offline
- Định kỳ để đẩy pending changes
- Trước khi thực hiện quick sync

---

### 🎯 **4. SELECTIVE SYNC - Đồng bộ có chọn lọc**

#### **API Endpoint:**
```
POST /api/sync/selective
Content-Type: application/json
Authorization: Bearer {token}

{
    "device_id": "android_device_123",
    "types": ["personal_tasks", "messages"],
    "team_ids": [1, 3, 5],
    "last_sync_at": "2024-01-20T10:00:00Z"
}
```

#### **Android Implementation:**
```kotlin
suspend fun performSelectiveSync(
    types: List<String>,
    teamIds: List<Int>? = null
): SyncResult {
    try {
        val response = apiService.selectiveSync(
            SelectiveSyncRequest(
                deviceId = getDeviceId(),
                types = types,
                teamIds = teamIds,
                lastSyncAt = getLastSyncTime()
            )
        )
        
        // Only process selected types
        if ("personal_tasks" in types) {
            processPersonalTasks(response.personalTasks)
        }
        
        if ("messages" in types) {
            processMessages(response.messages, teamIds)
        }
        
        return SyncResult.Success
    } catch (e: Exception) {
        return SyncResult.Error(e.message)
    }
}
```

#### **Use Cases:**
- User chỉ muốn sync personal tasks
- Chỉ sync một số teams cụ thể
- Tiết kiệm bandwidth và battery
- Sync nhanh cho dữ liệu quan trọng

---

### ⚔️ **5. CONFLICT RESOLUTION - Giải quyết xung đột**

#### **API Endpoint:**
```
POST /api/sync/resolve-conflicts
Content-Type: application/json
Authorization: Bearer {token}

{
    "device_id": "android_device_123",
    "conflicts": [
        {
            "type": "personal_task",
            "id": 123,
            "resolution": "server_wins",
            "client_data": {...},
            "server_data": {...}
        }
    ]
}
```

#### **Android Implementation:**
```kotlin
suspend fun resolveConflicts(conflicts: List<Conflict>): SyncResult {
    try {
        val response = apiService.resolveConflicts(
            ConflictResolutionRequest(
                deviceId = getDeviceId(),
                conflicts = conflicts
            )
        )
        
        // Apply resolved data
        response.resolvedItems.forEach { item ->
            when (item.type) {
                "personal_task" -> localDb.updatePersonalTask(item.data)
                "team_task" -> localDb.updateTeamTask(item.data)
                "message" -> localDb.updateMessage(item.data)
            }
        }
        
        return SyncResult.Success
    } catch (e: Exception) {
        return SyncResult.Error(e.message)
    }
}
```

#### **Conflict Resolution Strategies:**
1. **Server Wins** (Default): Server data được ưu tiên
2. **Client Wins**: Local data được giữ lại
3. **Last Write Wins**: Dựa trên timestamp
4. **Manual Resolution**: User quyết định

## 📱 ANDROID LOCAL DATABASE DESIGN

### **SQLite Tables cần thiết:**

```sql
-- Sync tracking
CREATE TABLE sync_status (
    id INTEGER PRIMARY KEY,
    table_name TEXT NOT NULL,
    last_sync_at TEXT,
    device_id TEXT
);

-- Pending changes queue
CREATE TABLE pending_actions (
    id INTEGER PRIMARY KEY,
    action_type TEXT NOT NULL, -- create, update, delete
    entity_type TEXT NOT NULL, -- task, message, team
    local_id TEXT,
    server_id INTEGER,
    data TEXT, -- JSON data
    created_at TEXT,
    status TEXT DEFAULT 'pending' -- pending, synced, error
);

-- Main data tables
CREATE TABLE personal_tasks (
    id INTEGER PRIMARY KEY,
    server_id INTEGER,
    title TEXT NOT NULL,
    description TEXT,
    status TEXT,
    priority INTEGER,
    deadline TEXT,
    created_at TEXT,
    updated_at TEXT,
    is_synced INTEGER DEFAULT 0
);

CREATE TABLE teams (
    id INTEGER PRIMARY KEY,
    server_id INTEGER,
    name TEXT NOT NULL,
    description TEXT,
    role TEXT,
    is_synced INTEGER DEFAULT 0
);

CREATE TABLE team_tasks (
    id INTEGER PRIMARY KEY,
    server_id INTEGER,
    team_id INTEGER,
    title TEXT NOT NULL,
    status TEXT,
    assigned_to_me INTEGER DEFAULT 0,
    created_at TEXT,
    updated_at TEXT,
    is_synced INTEGER DEFAULT 0
);

CREATE TABLE chat_messages (
    id INTEGER PRIMARY KEY,
    server_id INTEGER,
    team_id INTEGER,
    sender_id INTEGER,
    message TEXT,
    created_at TEXT,
    is_synced INTEGER DEFAULT 0
);
```

## 🔄 SYNC WORKFLOW IMPLEMENTATION

### **1. App Startup Flow:**
```kotlin
class SyncManager {
    suspend fun onAppStart() {
        if (isFirstTime()) {
            performInitialSync()
        } else {
            // Push pending changes first
            performPushSync()
            // Then get new data
            performQuickSync()
        }
    }
}
```

### **2. Offline Operations:**
```kotlin
suspend fun createTaskOffline(task: PersonalTask): Long {
    // 1. Generate local ID
    val localId = "temp_${System.currentTimeMillis()}"
    
    // 2. Save to local database
    val localDbId = localDb.insertPersonalTask(
        task.copy(localId = localId, isSynced = false)
    )
    
    // 3. Add to pending queue
    localDb.addPendingAction(
        PendingAction(
            actionType = "create",
            entityType = "personal_task",
            localId = localId,
            data = task.toJson()
        )
    )
    
    // 4. Show in UI immediately
    return localDbId
}
```

### **3. Background Sync:**
```kotlin
class SyncWorker : CoroutineWorker(context, params) {
    override suspend fun doWork(): Result {
        return try {
            if (NetworkUtils.isConnected()) {
                syncManager.performPushSync()
                syncManager.performQuickSync()
            }
            Result.success()
        } catch (e: Exception) {
            Result.retry()
        }
    }
}

// Schedule periodic sync
WorkManager.getInstance(context)
    .enqueueUniquePeriodicWork(
        "sync_work",
        ExistingPeriodicWorkPolicy.KEEP,
        PeriodicWorkRequestBuilder<SyncWorker>(15, TimeUnit.MINUTES)
            .setConstraints(
                Constraints.Builder()
                    .setRequiredNetworkType(NetworkType.CONNECTED)
                    .build()
            )
            .build()
    )
```

## 🛡️ ERROR HANDLING & RETRY LOGIC

### **Retry Strategy:**
```kotlin
class SyncRetryPolicy {
    private val maxRetries = 3
    private val baseDelay = 5000L // 5 seconds
    
    suspend fun executeWithRetry(operation: suspend () -> SyncResult): SyncResult {
        repeat(maxRetries) { attempt ->
            try {
                return operation()
            } catch (e: Exception) {
                if (attempt == maxRetries - 1) {
                    throw e
                }
                delay(baseDelay * (attempt + 1)) // Exponential backoff
            }
        }
        return SyncResult.Error("Max retries exceeded")
    }
}
```

### **Network Error Handling:**
```kotlin
sealed class SyncResult {
    object Success : SyncResult()
    data class Error(val message: String) : SyncResult()
    object NetworkError : SyncResult()
    object AuthError : SyncResult()
    data class ConflictDetected(val conflicts: List<Conflict>) : SyncResult()
}
```

## 📊 SYNC STATUS MONITORING

### **Sync Status UI:**
```kotlin
data class SyncStatus(
    val isOnline: Boolean,
    val lastSyncTime: String?,
    val pendingChanges: Int,
    val isSyncing: Boolean,
    val syncProgress: Float = 0f
)

class SyncStatusViewModel : ViewModel() {
    private val _syncStatus = MutableLiveData<SyncStatus>()
    val syncStatus: LiveData<SyncStatus> = _syncStatus
    
    fun updateSyncStatus() {
        _syncStatus.value = SyncStatus(
            isOnline = NetworkUtils.isConnected(),
            lastSyncTime = syncManager.getLastSyncTime(),
            pendingChanges = localDb.getPendingChangesCount(),
            isSyncing = syncManager.isSyncing()
        )
    }
}
```

## 🎯 PERFORMANCE OPTIMIZATION

### **1. Batch Operations:**
```kotlin
// Batch insert for better performance
suspend fun insertMultipleItems(items: List<PersonalTask>) {
    localDb.withTransaction {
        items.forEach { item ->
            localDb.insertPersonalTask(item)
        }
    }
}
```

### **2. Pagination:**
```kotlin
// Load data in chunks
suspend fun loadMessagesInChunks(teamId: Int) {
    var offset = 0
    val limit = 50
    
    do {
        val messages = apiService.getMessages(teamId, offset, limit)
        localDb.insertMessages(messages)
        offset += limit
    } while (messages.size == limit)
}
```

### **3. Compression:**
```kotlin
// Compress large sync payloads
fun compressSyncData(data: String): ByteArray {
    return GZIPOutputStream(ByteArrayOutputStream()).use { gzip ->
        gzip.write(data.toByteArray())
        gzip.finish()
        (gzip as ByteArrayOutputStream).toByteArray()
    }
}
```

## 🔒 SECURITY CONSIDERATIONS

### **1. Data Encryption:**
```kotlin
// Encrypt sensitive data before storing locally
class EncryptedPreferences {
    private val masterKey = MasterKey.Builder(context)
        .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
        .build()
        
    private val encryptedPrefs = EncryptedSharedPreferences.create(
        context,
        "sync_prefs",
        masterKey,
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
    )
}
```

### **2. Token Management:**
```kotlin
// Auto-refresh tokens during sync
suspend fun syncWithTokenRefresh() {
    try {
        performSync()
    } catch (e: AuthException) {
        authManager.refreshToken()
        performSync() // Retry with new token
    }
}
```

## 📋 TESTING STRATEGY

### **1. Unit Tests:**
```kotlin
@Test
fun `test offline task creation`() = runTest {
    // Given
    val task = PersonalTask(title = "Test Task")
    
    // When
    val result = syncManager.createTaskOffline(task)
    
    // Then
    assertThat(result).isGreaterThan(0)
    assertThat(localDb.getPendingChangesCount()).isEqualTo(1)
}
```

### **2. Integration Tests:**
```kotlin
@Test
fun `test full sync cycle`() = runTest {
    // Test: offline -> online -> sync -> verify
    syncManager.createTaskOffline(testTask)
    networkSimulator.goOnline()
    syncManager.performPushSync()
    
    val syncedTask = localDb.getTaskByLocalId(testTask.localId)
    assertThat(syncedTask.serverId).isNotNull()
}
```

## 🚀 DEPLOYMENT CHECKLIST

### **✅ Pre-deployment:**
- [ ] Test all sync scenarios
- [ ] Verify offline functionality
- [ ] Test conflict resolution
- [ ] Performance testing with large datasets
- [ ] Battery usage optimization
- [ ] Network error handling

### **✅ Monitoring:**
- [ ] Sync success/failure rates
- [ ] Average sync duration
- [ ] Pending changes queue size
- [ ] Conflict frequency
- [ ] User engagement metrics

---

**📱 Tài liệu này cung cấp đầy đủ thông tin để team Android implement tính năng đồng bộ hoàn chỉnh. Mọi thắc mắc xin liên hệ team Backend để được hỗ trợ!** 🚀
