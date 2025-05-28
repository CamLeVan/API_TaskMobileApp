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

---

## 🎯 **6. EVENT HANDLERS**

### **✅ Event Data Models:**
```kotlin
data class TeamInvitationEvent(
    val id: Int,
    val teamId: Int,
    val teamName: String,
    val email: String,
    val role: String,
    val status: String,
    val createdAt: String,
    val expiresAt: String
)

data class ChatMessageEvent(
    val id: Int,
    val teamId: Int,
    val userId: Int,
    val userName: String,
    val message: String,
    val clientTempId: String?,
    val createdAt: String
)

data class TypingEvent(
    val teamId: Int,
    val userId: Int,
    val userName: String,
    val isTyping: Boolean
)
```

### **✅ Team Invitation Handler:**
```kotlin
private fun handleTeamInvitationCreated(data: JSONObject?) {
    data?.let {
        val invitation = TeamInvitationEvent(
            id = it.optInt("id"),
            teamId = it.optInt("team_id"),
            teamName = it.optString("team_name"),
            email = it.optString("email"),
            role = it.optString("role"),
            status = it.optString("status"),
            createdAt = it.optString("created_at"),
            expiresAt = it.optString("expires_at")
        )

        // Update UI
        updateInvitationsList(invitation)
        showNotification("New team invitation: ${invitation.teamName}")
    }
}
```

### **✅ Chat Message Handler:**
```kotlin
private fun handleNewChatMessage(data: JSONObject?) {
    data?.let {
        val message = ChatMessageEvent(
            id = it.optInt("id"),
            teamId = it.optInt("team_id"),
            userId = it.optInt("user_id"),
            userName = it.optString("user_name"),
            message = it.optString("message"),
            clientTempId = it.optString("client_temp_id"),
            createdAt = it.optString("created_at")
        )

        // Update chat UI
        addMessageToChat(message)

        // Mark as delivered if it's our message
        if (message.clientTempId != null) {
            markMessageAsDelivered(message.clientTempId)
        }
    }
}
```

---

## 🔄 **7. CONNECTION MANAGEMENT**

### **✅ Connection States:**
```kotlin
enum class ConnectionState {
    DISCONNECTED,
    CONNECTING,
    CONNECTED,
    RECONNECTING,
    FAILED
}
```

### **✅ Connection Manager:**
```kotlin
class ConnectionManager {
    private var connectionState = ConnectionState.DISCONNECTED
    private var retryCount = 0
    private val maxRetries = 5
    private val retryDelay = 5000L // 5 seconds

    fun connect(authToken: String) {
        if (connectionState == ConnectionState.CONNECTED) return

        connectionState = ConnectionState.CONNECTING
        realtimeManager.connect(authToken)
    }

    fun retryConnection() {
        if (retryCount < maxRetries) {
            retryCount++
            connectionState = ConnectionState.RECONNECTING

            Handler(Looper.getMainLooper()).postDelayed({
                connect(currentAuthToken)
            }, retryDelay)
        } else {
            connectionState = ConnectionState.FAILED
            Log.e("Connection", "Max retries reached")
        }
    }

    fun onConnectionSuccess() {
        connectionState = ConnectionState.CONNECTED
        retryCount = 0
    }

    fun disconnect() {
        connectionState = ConnectionState.DISCONNECTED
        realtimeManager.disconnect()
    }
}
```

---

## 🧪 **8. TESTING & DEBUGGING**

### **✅ Test Connection:**
```kotlin
fun testConnection() {
    // 1. Test API connectivity
    testApiConnectivity()

    // 2. Test authentication
    testAuthentication()

    // 3. Test WebSocket connection
    testWebSocketConnection()

    // 4. Test real-time events
    testRealtimeEvents()
}

private suspend fun testApiConnectivity() {
    try {
        val response = apiService.test()
        Log.d("Test", "✅ API: $response")
    } catch (e: Exception) {
        Log.e("Test", "❌ API failed: ${e.message}")
    }
}

private suspend fun testAuthentication() {
    try {
        val token = authenticate()
        Log.d("Test", "✅ Auth: ${token?.substring(0, 30)}...")
    } catch (e: Exception) {
        Log.e("Test", "❌ Auth failed: ${e.message}")
    }
}

private fun testWebSocketConnection() {
    val testUrl = "ws://echo.websocket.org"

    val request = Request.Builder().url(testUrl).build()
    val testSocket = client.newWebSocket(request, object : okhttp3.WebSocketListener() {
        override fun onOpen(webSocket: WebSocket, response: Response) {
            Log.d("Test", "✅ WebSocket client working")
            webSocket.send("test message")
        }

        override fun onMessage(webSocket: WebSocket, text: String) {
            Log.d("Test", "✅ WebSocket echo: $text")
            webSocket.close(1000, "Test complete")
        }
    })
}
```

---

## 🎯 **9. INTEGRATION EXAMPLE**

### **✅ Complete Implementation:**
```kotlin
class MainActivity : AppCompatActivity() {
    private lateinit var realtimeManager: RealtimeManager
    private lateinit var apiService: ApiService

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        initializeRealtime()
    }

    private fun initializeRealtime() {
        realtimeManager = RealtimeManager()

        // Set up event listeners
        realtimeManager.setEventListener(object : RealtimeEventListener {
            override fun onTeamInvitation(invitation: TeamInvitationEvent) {
                runOnUiThread {
                    showInvitationDialog(invitation)
                }
            }

            override fun onChatMessage(message: ChatMessageEvent) {
                runOnUiThread {
                    updateChatUI(message)
                }
            }

            override fun onUserTyping(typing: TypingEvent) {
                runOnUiThread {
                    updateTypingIndicator(typing)
                }
            }
        })

        // Connect to real-time
        connectRealtime()
    }

    private suspend fun connectRealtime() {
        val authToken = authenticate()
        if (authToken != null) {
            realtimeManager.connect(authToken)
        }
    }
}
```

---

## 📋 **10. CHECKLIST CHO ANDROID TEAM**

### **✅ Implementation Checklist:**
- [ ] **API Service** setup với base URL `http://10.0.2.2:8000/api/`
- [ ] **Authentication** với credentials test
- [ ] **WebSocket Client** với OkHttp
- [ ] **WebSocket URL** chính xác: `ws://10.0.2.2:8080/ws?token={token}`
- [ ] **Channel Subscription** cho team và user channels
- [ ] **Event Handlers** cho tất cả real-time events
- [ ] **Connection Management** với retry logic
- [ ] **Error Handling** và logging
- [ ] **UI Updates** cho real-time events
- [ ] **Testing** với echo server và real server

### **✅ Testing Checklist:**
- [ ] **API connectivity** test
- [ ] **Authentication** test
- [ ] **WebSocket connection** test
- [ ] **Channel subscription** test
- [ ] **Real-time events** test
- [ ] **Multi-device** test
- [ ] **Network interruption** test
- [ ] **Reconnection** test

---

## 🚀 **EXPECTED RESULTS**

### **✅ Sau khi implement:**
- **WebSocket connects** ngay lập tức
- **Real-time events** nhận được
- **Team invitations** real-time
- **Chat messages** real-time
- **Typing indicators** real-time
- **Multi-device sync** working
- **No connection errors**

**📱 Android team có thể implement real-time hoàn chỉnh với tài liệu này!** 🚀✨