# 📚 **TÀI LIỆU REAL-TIME HOÀN CHỈNH CHO ANDROID**

## 🎯 **TỔNG QUAN HỆ THỐNG REAL-TIME**

### **🔄 Kiến trúc:**
```
Android App ←→ Laravel API (port 8000) ←→ Laravel Reverb (port 8080)
     ↓              ↓                           ↓
WebSocket Client   REST API                WebSocket Server
     ↓              ↓                           ↓
Real-time Events   CRUD Operations         Broadcasting Events
```

### **🔄 Luồng hoạt động:**
1. **Android login** → nhận **auth token**
2. **Android connect WebSocket** với **token**
3. **Android subscribe** các **channels**
4. **Server broadcast events** → **Android nhận real-time**

---

## 🔧 **1. SERVER CONFIGURATION**

### **✅ Server URLs:**
```
API Server:      http://10.0.2.2:8000
WebSocket Server: ws://10.0.2.2:8080
```

### **✅ Reverb Configuration:**
```
App ID:  401709
App Key: 8tbaaum6noyzpvygcb1q
Host:    0.0.0.0
Port:    8080
Scheme:  http
```

### **✅ Available Endpoints:**
```
API Base:        http://10.0.2.2:8000/api/
WebSocket:       ws://10.0.2.2:8080/ws
Authentication:  POST /api/auth/login
Team API:        /api/teams/{id}
Invitations:     /api/teams/{id}/invitations
Chat:            /api/teams/{id}/chat
```

---

## 🔑 **2. AUTHENTICATION**

### **✅ Login Request:**
```kotlin
data class LoginRequest(
    val email: String,
    val password: String
)

data class LoginResponse(
    val token: String,
    val user: User
)
```

### **✅ Test Credentials:**
```kotlin
val email = "testmanager@example.com"
val password = "password123"
```

### **✅ Authentication Flow:**
```kotlin
suspend fun authenticate(): String? {
    return try {
        val request = LoginRequest(email, password)
        val response = apiService.login(request)
        response.token
    } catch (e: Exception) {
        Log.e("Auth", "Login failed: ${e.message}")
        null
    }
}
```

---

## 📡 **3. WEBSOCKET CONNECTION**

### **✅ WebSocket URL Format:**
```kotlin
val websocketUrl = "ws://10.0.2.2:8080/ws?token=$authToken"
```

### **✅ OkHttp WebSocket Setup:**
```kotlin
class RealtimeManager {
    private val client = OkHttpClient.Builder()
        .connectTimeout(10, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .writeTimeout(10, TimeUnit.SECONDS)
        .pingInterval(30, TimeUnit.SECONDS)
        .build()
    
    private var webSocket: WebSocket? = null
    
    fun connect(authToken: String) {
        val url = "ws://10.0.2.2:8080/ws?token=$authToken"
        
        val request = Request.Builder()
            .url(url)
            .build()
        
        webSocket = client.newWebSocket(request, WebSocketListener())
    }
    
    fun disconnect() {
        webSocket?.close(1000, "Normal closure")
        webSocket = null
    }
}
```

### **✅ WebSocket Listener:**
```kotlin
inner class WebSocketListener : okhttp3.WebSocketListener() {
    override fun onOpen(webSocket: WebSocket, response: Response) {
        Log.d("WebSocket", "✅ Connected to Laravel Reverb")
        
        // Subscribe to channels after connection
        subscribeToChannels()
    }
    
    override fun onMessage(webSocket: WebSocket, text: String) {
        Log.d("WebSocket", "📡 Received: $text")
        handleMessage(text)
    }
    
    override fun onFailure(webSocket: WebSocket, t: Throwable, response: Response?) {
        Log.e("WebSocket", "❌ Connection failed: ${t.message}")
        Log.e("WebSocket", "Response: ${response?.code} ${response?.message}")
        
        // Retry connection
        retryConnection()
    }
    
    override fun onClosed(webSocket: WebSocket, code: Int, reason: String) {
        Log.d("WebSocket", "Connection closed: $code - $reason")
    }
}
```

---

## 📢 **4. CHANNELS & SUBSCRIPTIONS**

### **✅ Available Channels:**
```kotlin
object Channels {
    fun teamChannel(teamId: Int) = "private-teams.$teamId"
    fun userChannel(userId: Int) = "private-users.$userId"
}
```

### **✅ Subscribe to Channels:**
```kotlin
private fun subscribeToChannels() {
    // Subscribe to team channel
    subscribeToChannel("private-teams.1")
    
    // Subscribe to user channel (if needed)
    subscribeToChannel("private-users.${currentUserId}")
}

private fun subscribeToChannel(channelName: String) {
    val subscribeMessage = JSONObject().apply {
        put("event", "pusher:subscribe")
        put("data", JSONObject().apply {
            put("channel", channelName)
        })
    }
    
    webSocket?.send(subscribeMessage.toString())
    Log.d("WebSocket", "📡 Subscribed to: $channelName")
}
```

---

## 🎯 **5. REAL-TIME EVENTS**

### **✅ Event Types:**
```kotlin
object RealtimeEvents {
    const val TEAM_INVITATION_CREATED = "team.invitation.created"
    const val TEAM_INVITATION_ACCEPTED = "team.invitation.accepted"
    const val TEAM_INVITATION_REJECTED = "team.invitation.rejected"
    const val NEW_CHAT_MESSAGE = "new-chat-message"
    const val USER_TYPING = "user-typing"
    const val MESSAGE_READ = "message-read"
    const val TEAM_UPDATED = "team.updated"
    const val TEAM_MEMBER_ADDED = "team.member.added"
    const val TEAM_MEMBER_REMOVED = "team.member.removed"
}
```

### **✅ Event Handler:**
```kotlin
private fun handleMessage(message: String) {
    try {
        val json = JSONObject(message)
        val event = json.optString("event")
        val data = json.optJSONObject("data")
        val channel = json.optString("channel")
        
        Log.d("RealTime", "Event: $event, Channel: $channel")
        
        when (event) {
            RealtimeEvents.TEAM_INVITATION_CREATED -> {
                handleTeamInvitationCreated(data)
            }
            RealtimeEvents.NEW_CHAT_MESSAGE -> {
                handleNewChatMessage(data)
            }
            RealtimeEvents.USER_TYPING -> {
                handleUserTyping(data)
            }
            RealtimeEvents.MESSAGE_READ -> {
                handleMessageRead(data)
            }
            "pusher:connection_established" -> {
                Log.d("WebSocket", "✅ Connection established")
            }
            "pusher:subscription_succeeded" -> {
                Log.d("WebSocket", "✅ Subscription successful for: $channel")
            }
            else -> {
                Log.d("RealTime", "Unknown event: $event")
            }
        }
    } catch (e: Exception) {
        Log.e("RealTime", "Error parsing message: ${e.message}")
    }
}
```
