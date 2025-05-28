# Kế hoạch Phát triển Web Dashboard

## Bước 1: Chuẩn bị môi trường

### 1.1 Cài đặt Node.js và Vue.js
```bash
# Cài đặt Node.js (nếu chưa có)
# Download từ https://nodejs.org/

# Cài đặt Vue CLI
npm install -g @vue/cli

# Tạo project Vue.js mới
vue create task-management-web
cd task-management-web

# Cài đặt các dependencies cần thiết
npm install axios socket.io-client vuetify @mdi/font
npm install vue-router@4 pinia
```

### 1.2 Cấu hình CORS cho API
```php
// config/cors.php
'paths' => ['api/*', 'sanctum/csrf-cookie', 'reverb/*'],
'allowed_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

## Bước 2: Thiết kế Architecture

### 2.1 Cấu trúc thư mục
```
src/
├── components/           # Các component tái sử dụng
│   ├── common/          # Header, Sidebar, Footer
│   ├── forms/           # Form components
│   └── charts/          # Biểu đồ thống kê
├── views/               # Các trang chính
│   ├── auth/           # Đăng nhập, đăng ký
│   ├── dashboard/      # Trang chủ
│   ├── tasks/          # Quản lý công việc
│   ├── teams/          # Quản lý nhóm
│   ├── chat/           # Chat real-time
│   └── documents/      # Quản lý tài liệu
├── services/           # API services
├── stores/             # Pinia stores
├── utils/              # Utilities
└── router/             # Vue Router
```

### 2.2 Tech Stack
- **Frontend**: Vue 3 + Composition API
- **UI Framework**: Vuetify 3 (Material Design)
- **State Management**: Pinia
- **HTTP Client**: Axios
- **WebSocket**: Socket.io-client
- **Charts**: Chart.js hoặc ApexCharts
- **Authentication**: JWT tokens

## Bước 3: Các tính năng cần phát triển

### 3.1 Tính năng cốt lõi (Tuần 1-2)
- [ ] Authentication (Login/Register/Google OAuth)
- [ ] Dashboard tổng quan
- [ ] Quản lý công việc cá nhân
- [ ] Quản lý nhóm cơ bản

### 3.2 Tính năng nâng cao (Tuần 3-4)
- [ ] Chat real-time
- [ ] Kanban board (drag & drop)
- [ ] Quản lý tài liệu
- [ ] Thống kê và báo cáo

### 3.3 Tính năng bổ sung (Tuần 5-6)
- [ ] Notifications real-time
- [ ] Calendar view
- [ ] Team analytics
- [ ] Export/Import data

## Bước 4: API Integration

### 4.1 Service Layer
```javascript
// services/api.js
import axios from 'axios'

const API_BASE_URL = 'http://localhost:8000/api'

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request interceptor để thêm token
apiClient.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default apiClient
```

### 4.2 WebSocket Integration
```javascript
// services/websocket.js
import io from 'socket.io-client'

class WebSocketService {
  constructor() {
    this.socket = null
  }

  connect(token) {
    this.socket = io('ws://localhost:8080', {
      auth: { token }
    })
    
    this.socket.on('connect', () => {
      console.log('Connected to WebSocket')
    })
  }

  joinTeam(teamId) {
    this.socket.emit('join-team', teamId)
  }

  onNewMessage(callback) {
    this.socket.on('new-chat-message', callback)
  }
}

export default new WebSocketService()
```

## Bước 5: UI/UX Design

### 5.1 Layout chính
- **Sidebar**: Navigation menu
- **Header**: User info, notifications
- **Main Content**: Dynamic content area
- **Footer**: Copyright, links

### 5.2 Color Scheme
- **Primary**: #1976D2 (Blue)
- **Secondary**: #424242 (Grey)
- **Success**: #4CAF50 (Green)
- **Warning**: #FF9800 (Orange)
- **Error**: #F44336 (Red)

### 5.3 Responsive Design
- **Desktop**: Full layout với sidebar
- **Tablet**: Collapsible sidebar
- **Mobile**: Bottom navigation

## Bước 6: Development Timeline

### Tuần 1: Setup & Authentication
- [ ] Project setup
- [ ] Authentication pages
- [ ] API integration
- [ ] Basic routing

### Tuần 2: Core Features
- [ ] Dashboard
- [ ] Personal tasks CRUD
- [ ] Team management
- [ ] Basic UI components

### Tuần 3: Advanced Features
- [ ] Real-time chat
- [ ] Kanban board
- [ ] File upload/download
- [ ] Notifications

### Tuần 4: Polish & Testing
- [ ] UI/UX improvements
- [ ] Performance optimization
- [ ] Testing
- [ ] Bug fixes

## Bước 7: Deployment

### 7.1 Build cho production
```bash
npm run build
```

### 7.2 Deploy options
- **Netlify**: Miễn phí, dễ dùng
- **Vercel**: Tốt cho Vue.js
- **GitHub Pages**: Miễn phí
- **VPS**: Tự host

## Lưu ý quan trọng

1. **Tái sử dụng API**: Không cần thay đổi API hiện tại
2. **Real-time**: Sử dụng WebSocket cho chat và notifications
3. **Mobile-first**: Thiết kế responsive
4. **Performance**: Lazy loading, code splitting
5. **Security**: Validate inputs, secure token storage
