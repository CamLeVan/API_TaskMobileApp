# 📱 **ANDROID TEAM INVITATION IMPLEMENTATION**

## 🎯 **IMPLEMENTATION OVERVIEW**

### **🔄 Team Invitation Flow:**
```
Manager → Send Invitation → Real-time Event → Recipient Notification
    ↓            ↓               ↓                    ↓
API Call    Store in DB    Broadcast Event    Show in App
    ↓            ↓               ↓                    ↓
Response    Update UI      Update UI         Accept/Reject
```

---

## 🔧 **1. API SERVICE SETUP**

### **✅ Retrofit Interface:**
```kotlin
interface TeamInvitationApiService {
    
    // Get team invitations
    @GET("teams/{teamId}/invitations")
    suspend fun getTeamInvitations(
        @Path("teamId") teamId: Int,
        @Query("status") status: String? = null,
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<TeamInvitation>>>
    
    // Send team invitation
    @POST("teams/{teamId}/invitations")
    suspend fun sendTeamInvitation(
        @Path("teamId") teamId: Int,
        @Body request: SendInvitationRequest
    ): Response<ApiResponse<TeamInvitation>>
    
    // Cancel invitation
    @DELETE("teams/{teamId}/invitations/{invitationId}")
    suspend fun cancelInvitation(
        @Path("teamId") teamId: Int,
        @Path("invitationId") invitationId: Int
    ): Response<ApiResponse<Unit>>
    
    // Get my invitations
    @GET("invitations")
    suspend fun getMyInvitations(
        @Query("status") status: String? = null
    ): Response<ApiResponse<List<MyInvitation>>>
    
    // Accept invitation
    @POST("invitations/{invitationId}/accept")
    suspend fun acceptInvitation(
        @Path("invitationId") invitationId: Int
    ): Response<ApiResponse<AcceptInvitationResponse>>
    
    // Reject invitation
    @POST("invitations/{invitationId}/reject")
    suspend fun rejectInvitation(
        @Path("invitationId") invitationId: Int,
        @Body request: RejectInvitationRequest? = null
    ): Response<ApiResponse<Unit>>
    
    // Search users
    @GET("users/search")
    suspend fun searchUsers(
        @Query("query") query: String? = null,
        @Query("exclude_team") excludeTeam: Int? = null
    ): Response<ApiResponse<List<User>>>
}
```

### **✅ Data Models:**
```kotlin
data class TeamInvitation(
    val id: Int,
    val teamId: Int,
    val teamName: String,
    val email: String,
    val role: String,
    val status: String,
    val invitedBy: User,
    val createdAt: String,
    val updatedAt: String,
    val expiresAt: String
)

data class MyInvitation(
    val id: Int,
    val teamId: Int,
    val teamName: String,
    val teamDescription: String,
    val role: String,
    val status: String,
    val invitedBy: User,
    val createdAt: String,
    val expiresAt: String
)

data class SendInvitationRequest(
    val email: String,
    val role: String
)

data class RejectInvitationRequest(
    val reason: String? = null
)

data class AcceptInvitationResponse(
    val team: Team,
    val syncData: SyncData
)

data class SyncData(
    val chatMessages: List<ChatMessage>,
    val teamTasks: List<Task>,
    val documents: List<Document>
)

data class User(
    val id: Int,
    val name: String,
    val email: String,
    val avatar: String? = null
)
```

---

## 🎯 **2. REPOSITORY IMPLEMENTATION**

### **✅ Team Invitation Repository:**
```kotlin
class TeamInvitationRepository(
    private val apiService: TeamInvitationApiService,
    private val localDatabase: AppDatabase
) {
    
    suspend fun getTeamInvitations(teamId: Int, status: String? = null): Result<List<TeamInvitation>> {
        return try {
            val response = apiService.getTeamInvitations(teamId, status)
            if (response.isSuccessful && response.body()?.success == true) {
                val invitations = response.body()!!.data
                
                // Cache in local database
                localDatabase.teamInvitationDao().insertAll(invitations)
                
                Result.success(invitations)
            } else {
                Result.failure(Exception(response.body()?.message ?: "Failed to get invitations"))
            }
        } catch (e: Exception) {
            // Return cached data if available
            val cachedInvitations = localDatabase.teamInvitationDao().getByTeamId(teamId)
            if (cachedInvitations.isNotEmpty()) {
                Result.success(cachedInvitations)
            } else {
                Result.failure(e)
            }
        }
    }
    
    suspend fun sendInvitation(teamId: Int, email: String, role: String): Result<TeamInvitation> {
        return try {
            val request = SendInvitationRequest(email, role)
            val response = apiService.sendTeamInvitation(teamId, request)
            
            if (response.isSuccessful && response.body()?.success == true) {
                val invitation = response.body()!!.data
                
                // Cache in local database
                localDatabase.teamInvitationDao().insert(invitation)
                
                Result.success(invitation)
            } else {
                Result.failure(Exception(response.body()?.message ?: "Failed to send invitation"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun cancelInvitation(teamId: Int, invitationId: Int): Result<Unit> {
        return try {
            val response = apiService.cancelInvitation(teamId, invitationId)
            
            if (response.isSuccessful && response.body()?.success == true) {
                // Remove from local database
                localDatabase.teamInvitationDao().deleteById(invitationId)
                
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.body()?.message ?: "Failed to cancel invitation"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun getMyInvitations(status: String? = null): Result<List<MyInvitation>> {
        return try {
            val response = apiService.getMyInvitations(status)
            
            if (response.isSuccessful && response.body()?.success == true) {
                val invitations = response.body()!!.data
                
                // Cache in local database
                localDatabase.myInvitationDao().insertAll(invitations)
                
                Result.success(invitations)
            } else {
                Result.failure(Exception(response.body()?.message ?: "Failed to get my invitations"))
            }
        } catch (e: Exception) {
            // Return cached data
            val cachedInvitations = localDatabase.myInvitationDao().getAll()
            if (cachedInvitations.isNotEmpty()) {
                Result.success(cachedInvitations)
            } else {
                Result.failure(e)
            }
        }
    }
    
    suspend fun acceptInvitation(invitationId: Int): Result<AcceptInvitationResponse> {
        return try {
            val response = apiService.acceptInvitation(invitationId)
            
            if (response.isSuccessful && response.body()?.success == true) {
                val acceptResponse = response.body()!!.data
                
                // Update local database with new team data
                localDatabase.teamDao().insert(acceptResponse.team)
                localDatabase.chatMessageDao().insertAll(acceptResponse.syncData.chatMessages)
                localDatabase.taskDao().insertAll(acceptResponse.syncData.teamTasks)
                localDatabase.documentDao().insertAll(acceptResponse.syncData.documents)
                
                // Remove invitation from local database
                localDatabase.myInvitationDao().deleteById(invitationId)
                
                Result.success(acceptResponse)
            } else {
                Result.failure(Exception(response.body()?.message ?: "Failed to accept invitation"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun rejectInvitation(invitationId: Int, reason: String? = null): Result<Unit> {
        return try {
            val request = if (reason != null) RejectInvitationRequest(reason) else null
            val response = apiService.rejectInvitation(invitationId, request)
            
            if (response.isSuccessful && response.body()?.success == true) {
                // Remove invitation from local database
                localDatabase.myInvitationDao().deleteById(invitationId)
                
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.body()?.message ?: "Failed to reject invitation"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun searchUsers(query: String? = null, excludeTeam: Int? = null): Result<List<User>> {
        return try {
            val response = apiService.searchUsers(query, excludeTeam)
            
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!.data)
            } else {
                Result.failure(Exception(response.body()?.message ?: "Failed to search users"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
```
