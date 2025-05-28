# 📨 ANDROID TEAM INVITATION IMPLEMENTATION GUIDE

## 📋 TỔNG QUAN

Tài liệu này mô tả chi tiết **tính năng mời thành viên vào team** đã được triển khai hoàn chỉnh trong Laravel API và hướng dẫn team Android implement tương ứng.

## ✅ **TRẠNG THÁI BACKEND**

### **🚀 ĐÃ HOÀN THÀNH 90%**
- ✅ **Database schema** với bảng `team_invitations`
- ✅ **7 API endpoints** đầy đủ chức năng
- ✅ **4 WebSocket events** real-time
- ✅ **Business logic** với validation và security
- ✅ **Permission system** (chỉ manager mới mời được)
- ✅ **Auto-expiration** (7 ngày tự động hết hạn)
- ❌ **Email notifications** (10% - sẽ bổ sung sau)

### **📱 CẦN ANDROID IMPLEMENT**
- 🔄 **API integration** với 7 endpoints
- 🔄 **Local SQLite** cho offline support
- 🔄 **UI components** cho invitation flow
- 🔄 **WebSocket handling** cho real-time updates
- 🔄 **Push notifications** cho invitation alerts

## 🗄️ **DATABASE SCHEMA**

### **MySQL Server (Đã có):**
```sql
CREATE TABLE team_invitations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    team_id BIGINT NOT NULL,
    created_by BIGINT NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('manager', 'member') DEFAULT 'member',
    token VARCHAR(64) UNIQUE NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'expired') DEFAULT 'pending',
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_pending_invitation (team_id, email, status)
);
```

### **Android SQLite (Cần tạo):**
```sql
CREATE TABLE team_invitations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_id INTEGER,
    team_id INTEGER,
    team_name TEXT,
    team_uuid TEXT,
    email TEXT,
    role TEXT, -- 'manager' or 'member'
    status TEXT, -- 'pending', 'accepted', 'rejected', 'expired'
    token TEXT,
    created_at TEXT,
    expires_at TEXT,
    is_synced INTEGER DEFAULT 0,

    UNIQUE(server_id)
);
```

## 🌐 **API ENDPOINTS**

### **Base URL:** `http://10.0.2.2:8000/api/`
### **Authentication:** `Authorization: Bearer {token}`

### **1. 📋 Xem lời mời của team**
```
GET /teams/{team_id}/invitations
```

**Mô tả:** Lấy danh sách lời mời đang pending của team (chỉ manager)

**Response (200):**
```json
[
    {
        "id": 1,
        "team_id": 5,
        "email": "newuser@example.com",
        "role": "member",
        "status": "pending",
        "created_at": "2025-01-20T10:00:00.000000Z",
        "expires_at": "2025-01-27T10:00:00.000000Z",
        "token": "abc123..."
    }
]
```

### **2. 📤 Gửi lời mời mới**
```
POST /teams/{team_id}/invitations
Content-Type: application/json

{
    "email": "newuser@example.com",
    "role": "member"
}
```

**Response (201):**
```json
{
    "message": "Invitation sent successfully",
    "id": 1,
    "team_id": 5,
    "team_name": "Dự án Mobile App",
    "email": "newuser@example.com",
    "role": "member",
    "status": "pending",
    "created_at": "2025-01-20T10:00:00.000000Z",
    "expires_at": "2025-01-27T10:00:00.000000Z"
}
```

### **3. ✅ Chấp nhận lời mời**
```
POST /invitations/accept
Content-Type: application/json

{
    "token": "abc123def456..."
}
```

**Response (200) - TOÀN BỘ TEAM DATA:**
```json
{
    "message": "Invitation accepted successfully",
    "team": {
        "id": 5,
        "name": "Dự án Mobile App",
        "description": "Team phát triển ứng dụng di động",
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "created_at": "2025-01-15T08:00:00.000000Z"
    },
    "role": "member",
    "team_data": {
        "team": {
            "id": 5,
            "name": "Dự án Mobile App",
            "description": "Team phát triển ứng dụng di động",
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "created_at": "2025-01-15T08:00:00.000000Z"
        },
        "members": [
            {
                "id": 1,
                "name": "Team Manager",
                "email": "manager@test.com",
                "role": "manager",
                "joined_at": "2025-01-15T08:00:00.000000Z"
            },
            {
                "id": 2,
                "name": "New Member",
                "email": "newuser@example.com",
                "role": "member",
                "joined_at": "2025-01-20T10:00:00.000000Z"
            }
        ],
        "messages": [
            {
                "id": 1,
                "team_id": 5,
                "sender": {
                    "id": 1,
                    "name": "Team Manager",
                    "email": "manager@test.com"
                },
                "message": "Chào mừng bạn đến với team!",
                "file_url": null,
                "created_at": "2025-01-20T09:30:00.000000Z",
                "read_statuses": []
            }
        ],
        "team_tasks": [
            {
                "id": 1,
                "team_id": 5,
                "title": "Setup project structure",
                "description": "Thiết lập cấu trúc dự án ban đầu",
                "status": "in_progress",
                "priority": 2,
                "deadline": "2025-01-25T00:00:00.000000Z",
                "created_at": "2025-01-16T08:00:00.000000Z",
                "assignments": [
                    {
                        "user_id": 1,
                        "user_name": "Team Manager",
                        "assigned_at": "2025-01-16T08:00:00.000000Z"
                    }
                ]
            }
        ],
        "documents": [
            {
                "id": 1,
                "team_id": 5,
                "name": "Project Requirements.pdf",
                "file_path": "documents/team_5/requirements.pdf",
                "file_size": 1024000,
                "mime_type": "application/pdf",
                "folder_id": 1,
                "folder_name": "Requirements",
                "uploaded_by": 1,
                "created_at": "2025-01-16T10:00:00.000000Z"
            }
        ],
        "folders": [
            {
                "id": 1,
                "team_id": 5,
                "name": "Requirements",
                "parent_id": null,
                "created_at": "2025-01-16T09:00:00.000000Z"
            }
        ],
        "sync_time": "2025-01-20T10:00:00.000000Z"
    }
}
```

### **4. ❌ Từ chối lời mời**
```
POST /invitations/reject
Content-Type: application/json

{
    "token": "abc123def456..."
}
```

**Response (200):**
```json
{
    "message": "Invitation rejected successfully"
}
```

### **5. 🗑️ Hủy lời mời**
```
DELETE /teams/{team_id}/invitations/{invitation_id}
```

**Response (200):**
```json
{
    "message": "Invitation cancelled successfully"
}
```

### **6. 📥 Lời mời của user hiện tại**
```
GET /invitations
```

**Response (200):**
```json
[
    {
        "id": 1,
        "team_id": 5,
        "email": "user@example.com",
        "role": "member",
        "status": "pending",
        "token": "abc123...",
        "created_at": "2025-01-20T10:00:00.000000Z",
        "expires_at": "2025-01-27T10:00:00.000000Z",
        "team": {
            "id": 5,
            "name": "Dự án Mobile App",
            "description": "Team phát triển ứng dụng di động"
        }
    }
]
```

### **7. 🔗 Mời qua team UUID**
```
POST /teams/{team_uuid}/invite
Content-Type: application/json

{
    "email": "newuser@example.com",
    "role": "member"
}
```

**Response (201):** Giống như endpoint #2

## 🔄 **WEBSOCKET EVENTS**

### **WebSocket URL:** `ws://10.0.2.2:8080/ws?token={auth_token}`

### **1. 📨 Lời mời mới được tạo**
```json
{
    "event": "team.invitation.created",
    "data": {
        "id": 1,
        "team_id": 5,
        "team_name": "Dự án Mobile App",
        "email": "newuser@example.com",
        "role": "member",
        "created_at": "2025-01-20T10:00:00.000000Z",
        "expires_at": "2025-01-27T10:00:00.000000Z"
    }
}
```

### **2. ✅ Lời mời được chấp nhận**
```json
{
    "event": "team.invitation.accepted",
    "data": {
        "invitation_id": 1,
        "team_id": 5,
        "team_name": "Dự án Mobile App",
        "new_member": {
            "id": 10,
            "name": "New User",
            "email": "newuser@example.com",
            "avatar": null,
            "role": "member",
            "joined_at": "2025-01-20T10:00:00.000000Z"
        },
        "message": "New User đã tham gia team Dự án Mobile App",
        "timestamp": "2025-01-20T10:00:00.000000Z"
    }
}
```

### **3. ❌ Lời mời bị từ chối**
```json
{
    "event": "team.invitation.rejected",
    "data": {
        "id": 1,
        "team_id": 5,
        "email": "newuser@example.com",
        "status": "rejected"
    }
}
```

### **4. 🗑️ Lời mời bị hủy**
```json
{
    "event": "team.invitation.cancelled",
    "data": {
        "invitation_id": 1,
        "team_id": 5
    }
}
```

## 📱 **ANDROID IMPLEMENTATION**

### **1. 📦 Data Models**

```kotlin
data class TeamInvitation(
    val id: Int,
    val serverId: Int? = null,
    val teamId: Int,
    val teamName: String? = null,
    val teamUuid: String? = null,
    val email: String,
    val role: InvitationRole,
    val status: InvitationStatus,
    val token: String,
    val createdAt: String,
    val expiresAt: String,
    val isSynced: Boolean = false
)

enum class InvitationRole {
    MANAGER, MEMBER;

    override fun toString(): String = name.lowercase()
}

enum class InvitationStatus {
    PENDING, ACCEPTED, REJECTED, EXPIRED;

    override fun toString(): String = name.lowercase()
}

data class SendInvitationRequest(
    val email: String,
    val role: String
)

data class AcceptInvitationRequest(
    val token: String
)

data class RejectInvitationRequest(
    val token: String
)
```

### **2. 🌐 API Service**

```kotlin
interface TeamInvitationApiService {
    @GET("teams/{teamId}/invitations")
    suspend fun getTeamInvitations(
        @Path("teamId") teamId: Int
    ): Response<List<TeamInvitation>>

    @POST("teams/{teamId}/invitations")
    suspend fun sendInvitation(
        @Path("teamId") teamId: Int,
        @Body request: SendInvitationRequest
    ): Response<TeamInvitation>

    @POST("invitations/accept")
    suspend fun acceptInvitation(
        @Body request: AcceptInvitationRequest
    ): Response<AcceptInvitationResponse>

    @POST("invitations/reject")
    suspend fun rejectInvitation(
        @Body request: RejectInvitationRequest
    ): Response<RejectInvitationResponse>

    @DELETE("teams/{teamId}/invitations/{invitationId}")
    suspend fun cancelInvitation(
        @Path("teamId") teamId: Int,
        @Path("invitationId") invitationId: Int
    ): Response<CancelInvitationResponse>

    @GET("invitations")
    suspend fun getUserInvitations(): Response<List<TeamInvitation>>

    @POST("teams/{teamUuid}/invite")
    suspend fun inviteByUuid(
        @Path("teamUuid") teamUuid: String,
        @Body request: SendInvitationRequest
    ): Response<TeamInvitation>
}
```

### **3. 💾 Local Database (Room)**

```kotlin
@Entity(tableName = "team_invitations")
data class TeamInvitationEntity(
    @PrimaryKey(autoGenerate = true)
    val id: Int = 0,
    val serverId: Int? = null,
    val teamId: Int,
    val teamName: String? = null,
    val teamUuid: String? = null,
    val email: String,
    val role: String,
    val status: String,
    val token: String,
    val createdAt: String,
    val expiresAt: String,
    val isSynced: Boolean = false
)

@Dao
interface TeamInvitationDao {
    @Query("SELECT * FROM team_invitations WHERE status = 'pending'")
    suspend fun getPendingInvitations(): List<TeamInvitationEntity>

    @Query("SELECT * FROM team_invitations WHERE team_id = :teamId")
    suspend fun getTeamInvitations(teamId: Int): List<TeamInvitationEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertInvitation(invitation: TeamInvitationEntity): Long

    @Update
    suspend fun updateInvitation(invitation: TeamInvitationEntity)

    @Delete
    suspend fun deleteInvitation(invitation: TeamInvitationEntity)

    @Query("UPDATE team_invitations SET status = :status WHERE server_id = :serverId")
    suspend fun updateInvitationStatus(serverId: Int, status: String)
}
```

### **4. 🔄 Repository Pattern**

```kotlin
class TeamInvitationRepository(
    private val apiService: TeamInvitationApiService,
    private val localDao: TeamInvitationDao,
    private val networkUtils: NetworkUtils
) {
    suspend fun getTeamInvitations(teamId: Int): Result<List<TeamInvitation>> {
        return try {
            if (networkUtils.isConnected()) {
                // Fetch from API
                val response = apiService.getTeamInvitations(teamId)
                if (response.isSuccessful) {
                    val invitations = response.body() ?: emptyList()

                    // Save to local database
                    invitations.forEach { invitation ->
                        localDao.insertInvitation(invitation.toEntity())
                    }

                    Result.success(invitations)
                } else {
                    // Fallback to local data
                    val localInvitations = localDao.getTeamInvitations(teamId)
                    Result.success(localInvitations.map { it.toDomain() })
                }
            } else {
                // Offline - use local data
                val localInvitations = localDao.getTeamInvitations(teamId)
                Result.success(localInvitations.map { it.toDomain() })
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun sendInvitation(
        teamId: Int,
        email: String,
        role: InvitationRole
    ): Result<TeamInvitation> {
        return try {
            val request = SendInvitationRequest(email, role.toString())
            val response = apiService.sendInvitation(teamId, request)

            if (response.isSuccessful) {
                val invitation = response.body()!!
                localDao.insertInvitation(invitation.toEntity())
                Result.success(invitation)
            } else {
                Result.failure(Exception("Failed to send invitation"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun acceptInvitation(token: String): Result<AcceptInvitationResponse> {
        return try {
            val request = AcceptInvitationRequest(token)
            val response = apiService.acceptInvitation(request)

            if (response.isSuccessful) {
                // Update local status
                localDao.updateInvitationStatus(
                    serverId = getInvitationIdByToken(token),
                    status = "accepted"
                )
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Failed to accept invitation"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun rejectInvitation(token: String): Result<RejectInvitationResponse> {
        return try {
            val request = RejectInvitationRequest(token)
            val response = apiService.rejectInvitation(request)

            if (response.isSuccessful) {
                // Update local status
                localDao.updateInvitationStatus(
                    serverId = getInvitationIdByToken(token),
                    status = "rejected"
                )
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Failed to reject invitation"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getUserInvitations(): Result<List<TeamInvitation>> {
        return try {
            if (networkUtils.isConnected()) {
                val response = apiService.getUserInvitations()
                if (response.isSuccessful) {
                    val invitations = response.body() ?: emptyList()

                    // Save to local database
                    invitations.forEach { invitation ->
                        localDao.insertInvitation(invitation.toEntity())
                    }

                    Result.success(invitations)
                } else {
                    // Fallback to local data
                    val localInvitations = localDao.getPendingInvitations()
                    Result.success(localInvitations.map { it.toDomain() })
                }
            } else {
                // Offline - use local data
                val localInvitations = localDao.getPendingInvitations()
                Result.success(localInvitations.map { it.toDomain() })
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
```

### **5. 🎨 UI Components**

#### **Invitation List Screen:**
```kotlin
@Composable
fun InvitationListScreen(
    viewModel: InvitationViewModel = hiltViewModel()
) {
    val invitations by viewModel.userInvitations.collectAsState()
    val uiState by viewModel.uiState.collectAsState()

    LaunchedEffect(Unit) {
        viewModel.loadUserInvitations()
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp)
    ) {
        Text(
            text = "Lời mời tham gia team",
            style = MaterialTheme.typography.headlineMedium,
            modifier = Modifier.padding(bottom = 16.dp)
        )

        when (uiState) {
            is InvitationUiState.Loading -> {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator()
                }
            }

            is InvitationUiState.Success -> {
                if (invitations.isEmpty()) {
                    EmptyInvitationState()
                } else {
                    LazyColumn {
                        items(invitations) { invitation ->
                            InvitationCard(
                                invitation = invitation,
                                onAccept = { viewModel.acceptInvitation(it.token) },
                                onReject = { viewModel.rejectInvitation(it.token) }
                            )
                        }
                    }
                }
            }

            is InvitationUiState.Error -> {
                ErrorMessage(
                    message = uiState.message,
                    onRetry = { viewModel.loadUserInvitations() }
                )
            }
        }
    }
}

@Composable
fun InvitationCard(
    invitation: TeamInvitation,
    onAccept: (TeamInvitation) -> Unit,
    onReject: (TeamInvitation) -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = invitation.teamName ?: "Unknown Team",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )

                    Text(
                        text = "Vai trò: ${invitation.role.name}",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )

                    Text(
                        text = "Hết hạn: ${formatDate(invitation.expiresAt)}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }

                InvitationStatusBadge(status = invitation.status)
            }

            if (invitation.status == InvitationStatus.PENDING) {
                Spacer(modifier = Modifier.height(12.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    OutlinedButton(
                        onClick = { onReject(invitation) },
                        modifier = Modifier.weight(1f)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Close,
                            contentDescription = null,
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Từ chối")
                    }

                    Button(
                        onClick = { onAccept(invitation) },
                        modifier = Modifier.weight(1f)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Check,
                            contentDescription = null,
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Chấp nhận")
                    }
                }
            }
        }
    }
}
```

#### **Send Invitation Dialog:**
```kotlin
@Composable
fun SendInvitationDialog(
    teamId: Int,
    onDismiss: () -> Unit,
    onSend: (String, InvitationRole) -> Unit
) {
    var email by remember { mutableStateOf("") }
    var selectedRole by remember { mutableStateOf(InvitationRole.MEMBER) }
    var isEmailValid by remember { mutableStateOf(true) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Text("Mời thành viên mới")
        },
        text = {
            Column {
                OutlinedTextField(
                    value = email,
                    onValueChange = {
                        email = it
                        isEmailValid = android.util.Patterns.EMAIL_ADDRESS.matcher(it).matches()
                    },
                    label = { Text("Email") },
                    isError = !isEmailValid && email.isNotEmpty(),
                    supportingText = {
                        if (!isEmailValid && email.isNotEmpty()) {
                            Text("Email không hợp lệ")
                        }
                    },
                    modifier = Modifier.fillMaxWidth()
                )

                Spacer(modifier = Modifier.height(16.dp))

                Text(
                    text = "Vai trò:",
                    style = MaterialTheme.typography.labelMedium
                )

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    FilterChip(
                        onClick = { selectedRole = InvitationRole.MEMBER },
                        label = { Text("Thành viên") },
                        selected = selectedRole == InvitationRole.MEMBER
                    )

                    FilterChip(
                        onClick = { selectedRole = InvitationRole.MANAGER },
                        label = { Text("Quản lý") },
                        selected = selectedRole == InvitationRole.MANAGER
                    )
                }
            }
        },
        confirmButton = {
            TextButton(
                onClick = {
                    if (isEmailValid && email.isNotEmpty()) {
                        onSend(email, selectedRole)
                        onDismiss()
                    }
                },
                enabled = isEmailValid && email.isNotEmpty()
            ) {
                Text("Gửi lời mời")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Hủy")
            }
        }
    )
}
```

### **6. 🔔 WebSocket Integration**

```kotlin
class InvitationWebSocketListener(
    private val invitationRepository: TeamInvitationRepository,
    private val notificationManager: InvitationNotificationManager
) : WebSocketEventListener {

    override fun onEvent(event: WebSocketEvent) {
        when (event.type) {
            "team.invitation.created" -> {
                val invitation = event.data.toInvitation()
                handleInvitationCreated(invitation)
            }

            "team.invitation.accepted" -> {
                val data = event.data
                handleInvitationAccepted(data)
            }

            "team.invitation.rejected" -> {
                val data = event.data
                handleInvitationRejected(data)
            }

            "team.invitation.cancelled" -> {
                val data = event.data
                handleInvitationCancelled(data)
            }
        }
    }

    private fun handleInvitationCreated(invitation: TeamInvitation) {
        // Save to local database
        CoroutineScope(Dispatchers.IO).launch {
            invitationRepository.saveInvitationLocally(invitation)
        }

        // Show notification
        notificationManager.showInvitationReceived(invitation)

        // Update UI
        EventBus.post(InvitationReceivedEvent(invitation))
    }

    private fun handleInvitationAccepted(data: JsonObject) {
        // Update team member list
        EventBus.post(TeamMemberJoinedEvent(data))

        // Show success notification
        notificationManager.showInvitationAccepted(data)
    }

    private fun handleInvitationRejected(data: JsonObject) {
        // Update invitation status
        CoroutineScope(Dispatchers.IO).launch {
            invitationRepository.updateInvitationStatus(
                data.get("id").asInt,
                "rejected"
            )
        }

        // Update UI
        EventBus.post(InvitationRejectedEvent(data))
    }

    private fun handleInvitationCancelled(data: JsonObject) {
        // Remove invitation from local database
        CoroutineScope(Dispatchers.IO).launch {
            invitationRepository.removeInvitation(data.get("invitation_id").asInt)
        }

        // Update UI
        EventBus.post(InvitationCancelledEvent(data))
    }
}
```

### **7. 🔔 Push Notifications**

```kotlin
class InvitationNotificationManager(
    private val context: Context,
    private val notificationManager: NotificationManagerCompat
) {
    companion object {
        const val CHANNEL_ID = "team_invitations"
        const val NOTIFICATION_ID_BASE = 1000
    }

    fun showInvitationReceived(invitation: TeamInvitation) {
        val acceptIntent = createAcceptIntent(invitation)
        val rejectIntent = createRejectIntent(invitation)

        val notification = NotificationCompat.Builder(context, CHANNEL_ID)
            .setContentTitle("Lời mời tham gia team")
            .setContentText("Bạn được mời tham gia team ${invitation.teamName}")
            .setSmallIcon(R.drawable.ic_team_invitation)
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setAutoCancel(true)
            .addAction(
                R.drawable.ic_check,
                "Chấp nhận",
                acceptIntent
            )
            .addAction(
                R.drawable.ic_close,
                "Từ chối",
                rejectIntent
            )
            .setContentIntent(createOpenAppIntent(invitation))
            .build()

        notificationManager.notify(
            NOTIFICATION_ID_BASE + invitation.id,
            notification
        )
    }

    private fun createAcceptIntent(invitation: TeamInvitation): PendingIntent {
        val intent = Intent(context, InvitationActionReceiver::class.java).apply {
            action = "ACCEPT_INVITATION"
            putExtra("invitation_token", invitation.token)
        }

        return PendingIntent.getBroadcast(
            context,
            invitation.id,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )
    }

    private fun createRejectIntent(invitation: TeamInvitation): PendingIntent {
        val intent = Intent(context, InvitationActionReceiver::class.java).apply {
            action = "REJECT_INVITATION"
            putExtra("invitation_token", invitation.token)
        }

        return PendingIntent.getBroadcast(
            context,
            invitation.id + 10000,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )
    }
}

class InvitationActionReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        val token = intent.getStringExtra("invitation_token") ?: return

        when (intent.action) {
            "ACCEPT_INVITATION" -> {
                // Handle accept action
                CoroutineScope(Dispatchers.IO).launch {
                    val repository = getInvitationRepository(context)
                    repository.acceptInvitation(token)
                }
            }

            "REJECT_INVITATION" -> {
                // Handle reject action
                CoroutineScope(Dispatchers.IO).launch {
                    val repository = getInvitationRepository(context)
                    repository.rejectInvitation(token)
                }
            }
        }
    }
}
```

## 🧪 **TESTING GUIDE**

### **📋 Test Cases cần kiểm tra:**

#### **1. API Integration Tests:**
- [ ] **GET /teams/{id}/invitations** - Lấy danh sách lời mời
- [ ] **POST /teams/{id}/invitations** - Gửi lời mời mới
- [ ] **POST /invitations/accept** - Chấp nhận lời mời
- [ ] **POST /invitations/reject** - Từ chối lời mời
- [ ] **DELETE /teams/{id}/invitations/{id}** - Hủy lời mời
- [ ] **GET /invitations** - Lời mời của user
- [ ] **POST /teams/{uuid}/invite** - Mời qua UUID

#### **2. WebSocket Tests:**
- [ ] **team.invitation.created** event
- [ ] **team.invitation.accepted** event
- [ ] **team.invitation.rejected** event
- [ ] **team.invitation.cancelled** event

#### **3. UI Tests:**
- [ ] **Invitation list** hiển thị đúng
- [ ] **Send invitation dialog** hoạt động
- [ ] **Accept/Reject buttons** functional
- [ ] **Status badges** hiển thị đúng
- [ ] **Empty state** khi không có lời mời

#### **4. Offline Tests:**
- [ ] **Local database** lưu trữ invitations
- [ ] **Offline viewing** invitations
- [ ] **Sync when online** trở lại
- [ ] **Conflict resolution** khi có xung đột

#### **5. Notification Tests:**
- [ ] **Push notification** khi có lời mời mới
- [ ] **Action buttons** trong notification
- [ ] **Deep link** từ notification
- [ ] **Badge count** update

### **🔧 Test Data:**

#### **Test Accounts:**
```
Manager Account:
- Email: manager@test.com
- Password: password123

Member Account:
- Email: member@test.com
- Password: password123

New User (chưa có account):
- Email: newuser@test.com
```

#### **Test Teams:**
```
Team 1:
- ID: 1
- Name: "Test Team Alpha"
- UUID: "550e8400-e29b-41d4-a716-446655440000"

Team 2:
- ID: 2
- Name: "Test Team Beta"
- UUID: "550e8400-e29b-41d4-a716-446655440001"
```

### **📱 Manual Testing Steps:**

#### **Scenario 1: Gửi lời mời mới**
1. Login với manager account
2. Vào team management screen
3. Click "Mời thành viên"
4. Nhập email: newuser@test.com
5. Chọn role: member
6. Click "Gửi lời mời"
7. ✅ Verify: Lời mời xuất hiện trong danh sách
8. ✅ Verify: WebSocket event được gửi
9. ✅ Verify: Notification hiển thị (nếu newuser đang online)

#### **Scenario 2: Chấp nhận lời mời**
1. Login với newuser account
2. Vào invitation list screen
3. Thấy lời mời từ "Test Team Alpha"
4. Click "Chấp nhận"
5. ✅ Verify: User được thêm vào team
6. ✅ Verify: Invitation status = "accepted"
7. ✅ Verify: WebSocket event được gửi
8. ✅ Verify: Team member list được update

#### **Scenario 3: Từ chối lời mời**
1. Login với newuser account
2. Vào invitation list screen
3. Thấy lời mời từ "Test Team Beta"
4. Click "Từ chối"
5. ✅ Verify: Invitation status = "rejected"
6. ✅ Verify: WebSocket event được gửi
7. ✅ Verify: Lời mời biến mất khỏi pending list

#### **Scenario 4: Hủy lời mời**
1. Login với manager account
2. Vào team invitation management
3. Thấy lời mời pending
4. Click "Hủy lời mời"
5. ✅ Verify: Lời mời bị xóa
6. ✅ Verify: WebSocket event được gửi
7. ✅ Verify: Recipient không thấy lời mời nữa

## 🚨 **ERROR HANDLING**

### **Common Error Cases:**

#### **1. Network Errors:**
```kotlin
sealed class InvitationError : Exception() {
    object NetworkError : InvitationError()
    object ServerError : InvitationError()
    object UnauthorizedError : InvitationError()
    data class ValidationError(val field: String, val message: String) : InvitationError()
    data class BusinessLogicError(val message: String) : InvitationError()
}
```

#### **2. Business Logic Errors:**
- **Already member**: User đã là thành viên của team
- **Duplicate invitation**: Email đã được mời và đang pending
- **Expired invitation**: Lời mời đã hết hạn
- **Invalid token**: Token không tồn tại hoặc không hợp lệ
- **Permission denied**: User không có quyền gửi lời mời
- **Email mismatch**: Email trong lời mời không khớp với user hiện tại

#### **3. Error Messages:**
```kotlin
object InvitationErrorMessages {
    const val NETWORK_ERROR = "Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng."
    const val ALREADY_MEMBER = "Người dùng này đã là thành viên của team."
    const val DUPLICATE_INVITATION = "Lời mời đã được gửi cho email này."
    const val EXPIRED_INVITATION = "Lời mời đã hết hạn."
    const val INVALID_TOKEN = "Lời mời không hợp lệ hoặc đã hết hạn."
    const val PERMISSION_DENIED = "Bạn không có quyền thực hiện hành động này."
    const val EMAIL_MISMATCH = "Lời mời này không được gửi cho email của bạn."
    const val INVALID_EMAIL = "Email không hợp lệ."
    const val SERVER_ERROR = "Có lỗi xảy ra trên server. Vui lòng thử lại sau."
}
```

## 📊 **IMPLEMENTATION CHECKLIST**

### **📋 Phase 1: Basic Setup (Week 1)**
- [ ] **Setup API service** với Retrofit
- [ ] **Create data models** và DTOs
- [ ] **Setup local database** với Room
- [ ] **Implement repository** pattern
- [ ] **Basic error handling**

### **📋 Phase 2: Core Features (Week 2)**
- [ ] **Invitation list screen** UI
- [ ] **Send invitation dialog** UI
- [ ] **Accept/Reject functionality**
- [ ] **Team member management** integration
- [ ] **Status indicators** và badges

### **📋 Phase 3: Real-time Features (Week 3)**
- [ ] **WebSocket integration** cho invitation events
- [ ] **Push notifications** setup
- [ ] **Notification actions** (accept/reject từ notification)
- [ ] **Auto-refresh** UI khi có events
- [ ] **Deep link handling**

### **📋 Phase 4: Polish & Testing (Week 4)**
- [ ] **Offline support** hoàn chỉnh
- [ ] **Error handling** comprehensive
- [ ] **Loading states** và animations
- [ ] **Unit tests** cho repository
- [ ] **UI tests** cho screens
- [ ] **Integration tests** với API

## 🎯 **SUCCESS CRITERIA**

### **✅ Functional Requirements:**
- [ ] Manager có thể gửi lời mời cho email bất kỳ
- [ ] User nhận được notification khi có lời mời
- [ ] User có thể accept/reject lời mời
- [ ] Manager có thể hủy lời mời đã gửi
- [ ] Lời mời tự động hết hạn sau 7 ngày
- [ ] Real-time updates qua WebSocket
- [ ] Offline support cho viewing invitations

### **✅ Non-functional Requirements:**
- [ ] **Performance**: API calls < 2 seconds
- [ ] **Reliability**: 99% success rate cho API calls
- [ ] **Usability**: Intuitive UI/UX
- [ ] **Security**: Token-based authentication
- [ ] **Scalability**: Support 1000+ concurrent invitations

---

**📱 Tài liệu này cung cấp đầy đủ thông tin để team Android implement tính năng Team Invitation hoàn chỉnh. Backend đã sẵn sàng 90%, chỉ cần Android team follow theo guide này!** 🚀

**🔄 Version: 1.0 | Last Updated: January 2025 | Backend Ready**
```
