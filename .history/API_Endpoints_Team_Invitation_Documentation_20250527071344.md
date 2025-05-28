# 📚 **CHUẨN HÓA API ENDPOINTS VÀ TEAM INVITATION SYSTEM**

## 🎯 **TỔNG QUAN**

### **🔄 Hệ thống Team Invitation:**
```
User A (Manager) → Send Invitation → User B (Member)
     ↓                    ↓                ↓
Create Invitation    Real-time Event    Receive Notification
     ↓                    ↓                ↓
Store in DB         Broadcast Event    Accept/Reject
     ↓                    ↓                ↓
Update Status       Real-time Update   Join Team
```

### **🔄 API Flow:**
1. **Manager** gửi invitation qua API
2. **Server** tạo invitation record
3. **Server** broadcast real-time event
4. **Recipient** nhận notification
5. **Recipient** accept/reject qua API
6. **Server** update status và broadcast

---

## 🔧 **1. CHUẨN HÓA API ENDPOINTS**

### **✅ Base Configuration:**
```
API Base URL: http://10.0.2.2:8000/api/
Content-Type: application/json
Authorization: Bearer {token}
Accept: application/json
```

### **✅ Response Format Chuẩn:**
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Actual response data
  },
  "meta": {
    "timestamp": "2024-01-15T10:30:00Z",
    "version": "1.0"
  }
}
```

### **✅ Error Response Format:**
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  },
  "error_code": "VALIDATION_ERROR",
  "meta": {
    "timestamp": "2024-01-15T10:30:00Z",
    "version": "1.0"
  }
}
```

---

## 🔑 **2. AUTHENTICATION ENDPOINTS**

### **✅ Login:**
```
POST /api/auth/login
```

**Request:**
```json
{
  "email": "testmanager@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "123|abcdef...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "name": "Test Manager",
      "email": "testmanager@example.com",
      "role": "manager"
    }
  }
}
```

### **✅ Logout:**
```
POST /api/auth/logout
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

### **✅ Refresh Token:**
```
POST /api/auth/refresh
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "124|newtoken...",
    "expires_in": 3600
  }
}
```

---

## 👥 **3. TEAM MANAGEMENT ENDPOINTS**

### **✅ Get Teams List:**
```
GET /api/teams
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Development Team",
      "description": "Main development team",
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-15T10:30:00Z",
      "members_count": 5,
      "user_role": "admin"
    }
  ]
}
```

### **✅ Get Team Details:**
```
GET /api/teams/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Development Team",
    "description": "Main development team",
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-15T10:30:00Z",
    "members": [
      {
        "id": 1,
        "name": "Test Manager",
        "email": "testmanager@example.com",
        "role": "admin",
        "joined_at": "2024-01-01T00:00:00Z"
      },
      {
        "id": 2,
        "name": "Team Member",
        "email": "member@example.com",
        "role": "member",
        "joined_at": "2024-01-02T00:00:00Z"
      }
    ],
    "pending_invitations": [
      {
        "id": 1,
        "email": "pending@example.com",
        "role": "member",
        "status": "pending",
        "created_at": "2024-01-15T10:00:00Z",
        "expires_at": "2024-01-22T10:00:00Z"
      }
    ]
  }
}
```

### **✅ Create Team:**
```
POST /api/teams
Authorization: Bearer {token}
```

**Request:**
```json
{
  "name": "New Team",
  "description": "Team description"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Team created successfully",
  "data": {
    "id": 2,
    "name": "New Team",
    "description": "Team description",
    "created_at": "2024-01-15T10:30:00Z",
    "user_role": "admin"
  }
}
```

### **✅ Update Team:**
```
PUT /api/teams/{id}
Authorization: Bearer {token}
```

**Request:**
```json
{
  "name": "Updated Team Name",
  "description": "Updated description"
}
```

### **✅ Delete Team:**
```
DELETE /api/teams/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Team deleted successfully"
}
```

---

## 📧 **4. TEAM INVITATION ENDPOINTS**

### **✅ Get Team Invitations:**
```
GET /api/teams/{team_id}/invitations
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional): `pending`, `accepted`, `rejected`, `expired`
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "team_id": 1,
      "team_name": "Development Team",
      "email": "newmember@example.com",
      "role": "member",
      "status": "pending",
      "invited_by": {
        "id": 1,
        "name": "Test Manager",
        "email": "testmanager@example.com"
      },
      "created_at": "2024-01-15T10:00:00Z",
      "updated_at": "2024-01-15T10:00:00Z",
      "expires_at": "2024-01-22T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

### **✅ Send Team Invitation:**
```
POST /api/teams/{team_id}/invitations
Authorization: Bearer {token}
```

**Request:**
```json
{
  "email": "newmember@example.com",
  "role": "member"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Invitation sent successfully",
  "data": {
    "id": 1,
    "team_id": 1,
    "team_name": "Development Team",
    "email": "newmember@example.com",
    "role": "member",
    "status": "pending",
    "invited_by": {
      "id": 1,
      "name": "Test Manager",
      "email": "testmanager@example.com"
    },
    "created_at": "2024-01-15T10:30:00Z",
    "expires_at": "2024-01-22T10:30:00Z",
    "invitation_url": "https://app.example.com/invitations/accept?token=abc123"
  }
}
```

**Real-time Event Triggered:**
```json
{
  "event": "team.invitation.created",
  "channel": "private-teams.1",
  "data": {
    "id": 1,
    "team_id": 1,
    "team_name": "Development Team",
    "email": "newmember@example.com",
    "role": "member",
    "status": "pending",
    "invited_by": "Test Manager",
    "created_at": "2024-01-15T10:30:00Z",
    "expires_at": "2024-01-22T10:30:00Z"
  }
}
```

### **✅ Cancel Invitation:**
```
DELETE /api/teams/{team_id}/invitations/{invitation_id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Invitation cancelled successfully"
}
```

**Real-time Event Triggered:**
```json
{
  "event": "team.invitation.cancelled",
  "channel": "private-teams.1",
  "data": {
    "id": 1,
    "team_id": 1,
    "email": "newmember@example.com",
    "cancelled_by": "Test Manager"
  }
}
```
