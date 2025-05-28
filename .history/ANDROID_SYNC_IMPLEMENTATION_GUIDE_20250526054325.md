# 🔄 ANDROID SYNC IMPLEMENTATION GUIDE

## 📋 TỔNG QUAN

Tài liệu này mô tả chi tiết **tính năng đồng bộ hóa** đã được triển khai hoàn chỉnh trong Laravel API (MySQL Server) và hướng dẫn team Android implement tương ứng để đảm bảo ứng dụng hoạt động mượt mà cả online và offline.

## ✅ **TRẠNG THÁI TRIỂN KHAI**

### **🚀 ĐÃ HOÀN THÀNH (Ready for Android)**
- ✅ **5 API endpoints đồng bộ** đã implement và test
- ✅ **MySQL database** với 30+ tables đã setup
- ✅ **SyncController** với đầy đủ logic xử lý
- ✅ **Conflict resolution** đã implement
- ✅ **WebSocket real-time** đã hoạt động
- ✅ **Admin panel** để monitor sync

### **📱 CẦN ANDROID IMPLEMENT**
- 🔄 **Local SQLite database** theo schema đã thiết kế
- 🔄 **SyncManager class** theo API đã có
- 🔄 **Background sync worker** với WorkManager
- 🔄 **UI components** cho sync status
- 🔄 **Conflict resolution dialogs**

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

## �️ **CHIẾN LƯỢC ĐỒNG BỘ CÓ CHỌN LỌC**

### ✅ **BẢNG ĐƯỢC ĐỒNG BỘ (MySQL Server ↔ Android SQLite)**

#### **📱 A. Đồng bộ hai chiều (Bidirectional Sync)**
```sql
-- User có thể tạo/sửa từ Android và cần sync lên MySQL server
✅ personal_tasks          -- Công việc cá nhân
✅ subtasks               -- Công việc con
✅ team_task_assignments  -- Phân công công việc
✅ message_read_status    -- Trạng thái đọc tin nhắn
✅ message_reactions      -- Phản ứng emoji
✅ drafts                 -- Bản nháp tự động lưu
```

#### **📤 B. Đồng bộ một chiều: Android → MySQL (Push Only)**
```sql
-- Android tạo và đẩy lên, server broadcast qua WebSocket
✅ group_chat_messages    -- Tin nhắn chat (push lên, WebSocket xuống)
```

#### **📥 C. Đồng bộ một chiều: MySQL → Android (Pull Only)**
```sql
-- MySQL server quản lý, Android chỉ nhận về
✅ users                  -- Thông tin user (metadata only)
✅ teams                  -- Thông tin teams user tham gia
✅ team_members          -- Danh sách thành viên teams
✅ team_tasks            -- Công việc nhóm (chỉ tasks được assign)
✅ team_invitations      -- Lời mời tham gia team
✅ documents             -- Metadata tài liệu (không sync file content)
✅ document_folders      -- Cấu trúc thư mục
```

### ❌ **BẢNG KHÔNG ĐỒNG BỘ (Chỉ trên MySQL Server)**

#### **🔒 A. Bảng bảo mật (Security Tables)**
```sql
❌ password_resets        -- Reset password tokens
❌ personal_access_tokens -- API tokens
❌ sessions              -- Web sessions
❌ biometric_auth        -- Sinh trắc học (chỉ local device)
❌ device_tokens         -- FCM tokens
```

#### **⚙️ B. Bảng hệ thống (System Tables)**
```sql
❌ migrations            -- Database migrations
❌ failed_jobs           -- Failed queue jobs
❌ jobs                  -- Queue jobs
❌ cache                 -- Cache data
❌ notification_queue    -- Push notification queue
```

#### **📊 C. Bảng admin/analytics (Admin Only)**
```sql
❌ admin_users           -- Admin accounts
❌ audit_logs           -- System audit logs
❌ analytics_data       -- Usage analytics
❌ system_settings      -- System configuration
```

#### **🗂️ D. Bảng metadata không cần thiết**
```sql
❌ document_versions     -- File versions (quá nặng)
❌ document_user_permissions -- Permissions (server quản lý)
❌ kanban_columns       -- Kanban config (server-side)
❌ labels               -- Labels metadata
❌ calendar_events      -- Calendar integration
```

### 📊 **SO SÁNH KÍCH THƯỚC DỮ LIỆU**

#### **📱 Android SQLite Database (Nhẹ - ~8-10 tables)**
```sql
-- Chỉ cần những tables chính cho offline functionality
users (metadata only)
personal_tasks + subtasks
teams (basic info) + team_tasks (assigned only)
group_chat_messages (recent 50 per team)
message_read_status + message_reactions
documents (metadata only)
sync_status + pending_actions + drafts
```

#### **☁️ MySQL Server Database (Đầy đủ - 30+ tables)**
```sql
-- Toàn bộ hệ thống với admin, analytics, security
users + password_resets + sessions + tokens + biometric_auth
personal_tasks + subtasks + team_tasks + assignments
teams + members + invitations + role_history
messages + reactions + read_status
documents + versions + permissions + folders
sync_status + drafts + devices + notifications
admin_users + audit_logs + analytics + cache
kanban_columns + labels + calendar_events
```

### 🎯 **SELECTIVE SYNC OPTIONS**

#### **User có thể chọn sync:**
```kotlin
val syncOptions = listOf(
    "personal_tasks",    // Chỉ công việc cá nhân
    "team_tasks",        // Chỉ công việc nhóm (assigned only)
    "messages",          // Chỉ tin nhắn (recent only)
    "teams",            // Chỉ thông tin teams
    "documents"         // Chỉ metadata tài liệu
)

// Hoặc chọn teams cụ thể
val selectedTeams = listOf(1, 3, 5) // Chỉ sync team 1, 3, 5
```

### 💡 **TẠI SAO KHÔNG SYNC TẤT CẢ?**

#### **🚀 Performance Benefits:**
- **Faster sync**: Chỉ sync data cần thiết cho mobile UX
- **Less bandwidth**: Tiết kiệm data mobile của user
- **Smaller local DB**: SQLite nhẹ hơn, app khởi động nhanh

#### **🔋 Battery & Resource:**
- **Less processing**: Ít CPU usage cho sync operations
- **Less network**: Ít radio usage, tiết kiệm pin
- **User control**: User tự chọn sync gì

#### **🔒 Security & Privacy:**
- **Sensitive data**: Passwords, admin data không xuống client
- **Privacy protection**: Không lưu data không cần thiết
- **Audit separation**: System logs chỉ ở server

## �🔄 5 LOẠI ĐỒNG BỘ ĐÃ TRIỂN KHAI

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

## 🌐 API ENDPOINTS SUMMARY

### **Base URL:** `http://10.0.2.2:8000/api/` (MySQL Server)
### **Authentication:** `Authorization: Bearer {token}`

| Endpoint | Method | Purpose | Frequency |
|----------|--------|---------|-----------|
| `/sync/initial` | POST | Lần đầu sync | Once per install |
| `/sync/quick` | POST | Sync thường xuyên | Every 15 min |
| `/sync/push` | POST | Upload changes | When online |
| `/sync/selective` | POST | Sync có chọn lọc | User triggered |
| `/sync/resolve-conflicts` | POST | Giải quyết xung đột | When conflicts |

## 🔧 CONFIGURATION CONSTANTS

```kotlin
object SyncConfig {
    const val QUICK_SYNC_INTERVAL_MINUTES = 15L
    const val MAX_RETRY_ATTEMPTS = 3
    const val INITIAL_RETRY_DELAY_MS = 5000L
    const val MAX_PENDING_CHANGES = 1000
    const val MESSAGES_PER_TEAM_LIMIT = 50
    const val SYNC_TIMEOUT_SECONDS = 30L

    // Sync types
    const val SYNC_TYPE_MESSAGES = "messages"
    const val SYNC_TYPE_TASKS = "tasks"
    const val SYNC_TYPE_TEAMS = "teams"

    // Action types
    const val ACTION_CREATE = "create"
    const val ACTION_UPDATE = "update"
    const val ACTION_DELETE = "delete"

    // Entity types
    const val ENTITY_PERSONAL_TASK = "personal_task"
    const val ENTITY_TEAM_TASK = "team_task"
    const val ENTITY_MESSAGE = "message"
    const val ENTITY_TEAM = "team"
}
```

## 🎨 UI/UX RECOMMENDATIONS

### **1. Sync Status Indicator:**
```kotlin
@Composable
fun SyncStatusIndicator(syncStatus: SyncStatus) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier.padding(8.dp)
    ) {
        when {
            syncStatus.isSyncing -> {
                CircularProgressIndicator(
                    modifier = Modifier.size(16.dp),
                    strokeWidth = 2.dp
                )
                Text("Đang đồng bộ...", modifier = Modifier.padding(start = 8.dp))
            }
            !syncStatus.isOnline -> {
                Icon(
                    Icons.Default.CloudOff,
                    contentDescription = "Offline",
                    tint = Color.Gray
                )
                Text("Offline", modifier = Modifier.padding(start = 8.dp))
            }
            syncStatus.pendingChanges > 0 -> {
                Icon(
                    Icons.Default.CloudQueue,
                    contentDescription = "Pending",
                    tint = Color.Orange
                )
                Text(
                    "${syncStatus.pendingChanges} chờ đồng bộ",
                    modifier = Modifier.padding(start = 8.dp)
                )
            }
            else -> {
                Icon(
                    Icons.Default.CloudDone,
                    contentDescription = "Synced",
                    tint = Color.Green
                )
                Text("Đã đồng bộ", modifier = Modifier.padding(start = 8.dp))
            }
        }
    }
}
```

### **2. Conflict Resolution Dialog:**
```kotlin
@Composable
fun ConflictResolutionDialog(
    conflict: Conflict,
    onResolve: (ResolutionStrategy) -> Unit
) {
    AlertDialog(
        onDismissRequest = { },
        title = { Text("Xung đột dữ liệu") },
        text = {
            Column {
                Text("Dữ liệu này đã được thay đổi ở thiết bị khác:")
                Spacer(modifier = Modifier.height(8.dp))
                Text("Phiên bản local: ${conflict.clientData}")
                Text("Phiên bản server: ${conflict.serverData}")
            }
        },
        confirmButton = {
            Row {
                TextButton(onClick = { onResolve(ResolutionStrategy.SERVER_WINS) }) {
                    Text("Dùng server")
                }
                TextButton(onClick = { onResolve(ResolutionStrategy.CLIENT_WINS) }) {
                    Text("Dùng local")
                }
            }
        }
    )
}
```

### **3. Sync Settings Screen:**
```kotlin
@Composable
fun SyncSettingsScreen() {
    Column(modifier = Modifier.padding(16.dp)) {
        SwitchPreference(
            title = "Tự động đồng bộ",
            summary = "Đồng bộ dữ liệu định kỳ",
            checked = autoSyncEnabled,
            onCheckedChange = { /* Update setting */ }
        )

        ListPreference(
            title = "Tần suất đồng bộ",
            summary = "Mỗi 15 phút",
            options = listOf("5 phút", "15 phút", "30 phút", "1 giờ")
        )

        MultiSelectPreference(
            title = "Loại dữ liệu đồng bộ",
            summary = "Chọn dữ liệu cần đồng bộ",
            options = listOf("Tin nhắn", "Công việc", "Nhóm")
        )

        SwitchPreference(
            title = "Chỉ đồng bộ qua WiFi",
            summary = "Tiết kiệm dữ liệu di động",
            checked = wifiOnlySync,
            onCheckedChange = { /* Update setting */ }
        )
    }
}
```

## 🚨 COMMON ISSUES & SOLUTIONS

### **1. Large Dataset Performance:**
```kotlin
// Problem: Initial sync quá chậm với user có nhiều data
// Solution: Implement progressive loading
suspend fun performProgressiveInitialSync(
    onProgress: (Int, Int) -> Unit
) {
    val steps = listOf("personal_tasks", "teams", "team_tasks", "messages")

    steps.forEachIndexed { index, step ->
        when (step) {
            "personal_tasks" -> syncPersonalTasks()
            "teams" -> syncTeams()
            "team_tasks" -> syncTeamTasks()
            "messages" -> syncRecentMessages()
        }
        onProgress(index + 1, steps.size)
    }
}
```

### **2. Memory Management:**
```kotlin
// Problem: OutOfMemoryError khi sync large datasets
// Solution: Process data in chunks
suspend fun processLargeDataset(items: List<Any>) {
    items.chunked(100).forEach { chunk ->
        processChunk(chunk)
        // Give GC a chance to clean up
        delay(100)
    }
}
```

### **3. Battery Optimization:**
```kotlin
// Problem: Frequent sync drains battery
// Solution: Adaptive sync frequency
class AdaptiveSyncManager {
    fun calculateSyncInterval(): Long {
        return when {
            isUserActive() -> 5.minutes.inWholeMilliseconds
            isCharging() -> 10.minutes.inWholeMilliseconds
            isBatteryLow() -> 60.minutes.inWholeMilliseconds
            else -> 15.minutes.inWholeMilliseconds
        }
    }
}
```

## 📊 METRICS & ANALYTICS

### **Track Important Events:**
```kotlin
class SyncAnalytics {
    fun trackSyncStart(type: String) {
        analytics.logEvent("sync_start") {
            param("sync_type", type)
            param("timestamp", System.currentTimeMillis())
        }
    }

    fun trackSyncComplete(type: String, duration: Long, itemCount: Int) {
        analytics.logEvent("sync_complete") {
            param("sync_type", type)
            param("duration_ms", duration)
            param("item_count", itemCount)
        }
    }

    fun trackSyncError(type: String, error: String) {
        analytics.logEvent("sync_error") {
            param("sync_type", type)
            param("error_message", error)
        }
    }

    fun trackConflictResolution(strategy: String) {
        analytics.logEvent("conflict_resolved") {
            param("resolution_strategy", strategy)
        }
    }
}
```

## 🔄 MIGRATION STRATEGY

### **Từ version cũ lên version có sync:**
```kotlin
class SyncMigration {
    suspend fun migrateToSyncVersion() {
        // 1. Backup existing data
        backupExistingData()

        // 2. Add sync columns to existing tables
        addSyncColumns()

        // 3. Mark existing data as synced
        markExistingDataAsSynced()

        // 4. Perform initial sync to get latest data
        syncManager.performInitialSync()
    }

    private suspend fun addSyncColumns() {
        database.execSQL("ALTER TABLE personal_tasks ADD COLUMN is_synced INTEGER DEFAULT 1")
        database.execSQL("ALTER TABLE personal_tasks ADD COLUMN server_id INTEGER")
        // ... other tables
    }
}
```

## 📞 SUPPORT & TROUBLESHOOTING

### **Debug Tools:**
```kotlin
class SyncDebugger {
    fun generateSyncReport(): String {
        return buildString {
            appendLine("=== SYNC DEBUG REPORT ===")
            appendLine("Last sync: ${getLastSyncTime()}")
            appendLine("Pending changes: ${getPendingChangesCount()}")
            appendLine("Network status: ${getNetworkStatus()}")
            appendLine("Sync settings: ${getSyncSettings()}")
            appendLine("Recent errors: ${getRecentErrors()}")
        }
    }

    fun exportSyncLogs(): File {
        // Export detailed logs for debugging
        return File(context.cacheDir, "sync_logs.txt").apply {
            writeText(getSyncLogs())
        }
    }
}
```

### **Contact Information:**
- **Backend Team**: backend@company.com
- **API Documentation**: [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- **Project Documentation**: [PROJECT_DOCUMENTATION.md](PROJECT_DOCUMENTATION.md)
- **Issue Tracker**: GitHub Issues

---

**📱 Tài liệu này cung cấp đầy đủ thông tin để team Android implement tính năng đồng bộ hoàn chỉnh. Mọi thắc mắc xin liên hệ team Backend để được hỗ trợ!** 🚀

**🔄 Version: 1.0 | Last Updated: January 2025**
