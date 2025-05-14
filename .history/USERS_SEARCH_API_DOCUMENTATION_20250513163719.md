# Tài liệu API Tìm kiếm Người dùng

## Giới thiệu

Tài liệu này mô tả chi tiết API endpoint để tìm kiếm người dùng, thường được sử dụng khi mời người dùng vào nhóm. API này cho phép tìm kiếm người dùng theo tên hoặc email và loại trừ những người đã là thành viên của một nhóm cụ thể.

## Base URL

Tất cả các API endpoints đều có tiền tố:

```text
https://api.yourdomain.com/api
```

Ví dụ, endpoint đầy đủ cho việc tìm kiếm người dùng sẽ là:

```text
https://api.yourdomain.com/api/users/search
```

## Xác thực

API endpoint này yêu cầu xác thực bằng token. Token cần được gửi trong header của mỗi request:

```text
Authorization: Bearer 1|laravel_sanctum_token_hash
```

## Endpoint

### Tìm kiếm người dùng

**Endpoint:** `GET /api/users/search`

**Mô tả:** Tìm kiếm người dùng theo tên hoặc email. Thường được sử dụng khi mời người dùng vào nhóm.

**Query Parameters:**

- `name` (optional): Tìm kiếm theo tên, tối thiểu 2 ký tự
- `email` (optional): Tìm kiếm theo email, tối thiểu 2 ký tự
- `exclude_team` (optional): ID của nhóm mà bạn muốn loại trừ những người đã là thành viên
- `per_page` (optional): Số lượng kết quả trên mỗi trang, mặc định là 15

**Lưu ý:** Ít nhất một trong hai tham số `name` hoặc `email` phải được cung cấp.

**Response (200):**

```json
{
  "data": [
    {
      "id": 2,
      "name": "Tran Thi B",
      "email": "tranthib@example.com",
      "avatar": "https://storage.yourdomain.com/avatars/user2.jpg",
      "created_at": "2025-05-01T10:00:00.000000Z",
      "updated_at": "2025-05-01T10:00:00.000000Z"
    },
    {
      "id": 3,
      "name": "Le Van C",
      "email": "levanc@example.com",
      "avatar": null,
      "created_at": "2025-05-02T10:00:00.000000Z",
      "updated_at": "2025-05-02T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 2
  }
}
```

## Mã lỗi

| Mã HTTP | Mô tả | Nguyên nhân |
|---------|-------|-------------|
| 400 | Bad Request | Dữ liệu gửi lên không hợp lệ hoặc thiếu thông tin |
| 401 | Unauthorized | Token không hợp lệ hoặc đã hết hạn |
| 422 | Unprocessable Entity | Từ khóa tìm kiếm quá ngắn (< 2 ký tự) |
| 500 | Internal Server Error | Lỗi server |

## Ví dụ sử dụng

### Tìm kiếm người dùng theo tên

```text
GET /api/users/search?name=nguyen
```

### Tìm kiếm người dùng theo email

```text
GET /api/users/search?email=example.com
```

### Tìm kiếm người dùng theo cả tên và email

```text
GET /api/users/search?name=nguyen&email=example.com
```

### Tìm kiếm người dùng không phải là thành viên của nhóm có ID = 5

```text
GET /api/users/search?name=nguyen&exclude_team=5
```

### Tìm kiếm với số lượng kết quả tùy chỉnh

```text
GET /api/users/search?email=example.com&per_page=10
```

## Triển khai trong Android

```kotlin
// Kotlin với Retrofit
interface ApiService {
    @GET("users/search")
    fun searchUsers(
        @Query("q") query: String,
        @Query("exclude_team") excludeTeam: Int? = null,
        @Query("per_page") perPage: Int? = null
    ): Call<UserSearchResponse>
}

// Model classes
data class UserSearchResponse(
    val data: List<User>,
    val meta: Meta
)

data class User(
    val id: Int,
    val name: String,
    val email: String,
    val avatar: String?
)

data class Meta(
    val current_page: Int,
    val last_page: Int,
    val per_page: Int,
    val total: Int
)

// Sử dụng
fun searchUsers(query: String, teamId: Int?) {
    apiService.searchUsers(query, teamId).enqueue(object : Callback<UserSearchResponse> {
        override fun onResponse(call: Call<UserSearchResponse>, response: Response<UserSearchResponse>) {
            if (response.isSuccessful) {
                val users = response.body()?.data ?: emptyList()
                // Hiển thị danh sách người dùng
            } else {
                // Xử lý lỗi
            }
        }

        override fun onFailure(call: Call<UserSearchResponse>, t: Throwable) {
            // Xử lý lỗi kết nối
        }
    })
}
```
