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

**Response (200):**
```json
{
    "message": "Invitation accepted successfully",
    "team": {
        "id": 5,
        "name": "Dự án Mobile App",
        "description": "Team phát triển ứng dụng di động",
        "created_at": "2025-01-15T08:00:00.000000Z"
    },
    "role": "member"
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
        "invitation": {
            "id": 1,
            "team_id": 5,
            "email": "newuser@example.com",
            "role": "member"
        },
        "user": {
            "id": 10,
            "name": "New User",
            "email": "newuser@example.com"
        },
        "team": {
            "id": 5,
            "name": "Dự án Mobile App"
        }
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
