# Hướng dẫn Triển khai Chức năng Chia sẻ Tài liệu trên Android - Phần 2

## ViewModel (Presentation Layer)

Tạo các ViewModel để xử lý logic UI và tương tác với Repository:

```kotlin
// ui/viewmodels/DocumentViewModel.kt
class DocumentViewModel(
    private val repository: DocumentRepository,
    private val savedStateHandle: SavedStateHandle
) : ViewModel() {
    // Lưu trữ trạng thái
    private val teamId: Long = savedStateHandle.get<Long>("teamId") ?: 0
    private val _currentFolderId = MutableStateFlow<Long?>(null)
    
    // UI state
    private val _uiState = MutableStateFlow<DocumentUiState>(DocumentUiState.Loading)
    val uiState: StateFlow<DocumentUiState> = _uiState
    
    // Danh sách tài liệu
    private val _documents = MutableStateFlow<List<Document>>(emptyList())
    val documents: StateFlow<List<Document>> = _documents
    
    // Khởi tạo
    init {
        viewModelScope.launch {
            _currentFolderId.collectLatest { folderId ->
                loadDocuments(folderId)
            }
        }
    }
    
    // Thay đổi thư mục hiện tại
    fun setCurrentFolder(folderId: Long?) {
        _currentFolderId.value = folderId
    }
    
    // Tải danh sách tài liệu
    private fun loadDocuments(folderId: Long?) {
        viewModelScope.launch {
            repository.getDocuments(teamId, folderId).collect { result ->
                when (result) {
                    is Resource.Loading -> {
                        _uiState.value = DocumentUiState.Loading
                        result.data?.let { _documents.value = it }
                    }
                    is Resource.Success -> {
                        _documents.value = result.data ?: emptyList()
                        _uiState.value = DocumentUiState.Success
                    }
                    is Resource.Error -> {
                        _uiState.value = DocumentUiState.Error(result.message ?: "Unknown error")
                    }
                }
            }
        }
    }
    
    // Tải lại dữ liệu
    fun refreshDocuments() {
        loadDocuments(_currentFolderId.value)
    }
    
    // Tải lên tài liệu
    fun uploadDocument(
        file: File,
        name: String? = null,
        description: String? = null,
        accessLevel: String? = null,
        allowedUsers: List<Long>? = null
    ) {
        viewModelScope.launch {
            repository.uploadDocument(
                teamId,
                file,
                name,
                description,
                _currentFolderId.value,
                accessLevel,
                allowedUsers
            ).collect { result ->
                when (result) {
                    is Resource.Loading -> {
                        _uiState.value = DocumentUiState.Uploading
                    }
                    is Resource.Success -> {
                        refreshDocuments()
                        _uiState.value = DocumentUiState.UploadSuccess(result.data)
                    }
                    is Resource.Error -> {
                        _uiState.value = DocumentUiState.Error(result.message ?: "Upload failed")
                    }
                }
            }
        }
    }
    
    // Xóa tài liệu
    fun deleteDocument(documentId: Long) {
        viewModelScope.launch {
            repository.deleteDocument(documentId).collect { result ->
                when (result) {
                    is Resource.Loading -> {
                        _uiState.value = DocumentUiState.Loading
                    }
                    is Resource.Success -> {
                        refreshDocuments()
                        _uiState.value = DocumentUiState.DeleteSuccess
                    }
                    is Resource.Error -> {
                        _uiState.value = DocumentUiState.Error(result.message ?: "Delete failed")
                    }
                }
            }
        }
    }
    
    // Các phương thức khác cho các chức năng còn lại
}

// UI state
sealed class DocumentUiState {
    object Loading : DocumentUiState()
    object Success : DocumentUiState()
    object Uploading : DocumentUiState()
    data class UploadSuccess(val document: Document?) : DocumentUiState()
    object DeleteSuccess : DocumentUiState()
    data class Error(val message: String) : DocumentUiState()
}
```

## UI Components (UI Layer)

### 1. Document List Screen

```kotlin
// ui/screens/DocumentListScreen.kt
@Composable
fun DocumentListScreen(
    viewModel: DocumentViewModel = hiltViewModel(),
    onDocumentClick: (Document) -> Unit,
    onUploadClick: () -> Unit,
    onFolderClick: (DocumentFolder) -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val documents by viewModel.documents.collectAsState()
    val folders by viewModel.folders.collectAsState()
    
    Column(modifier = Modifier.fillMaxSize()) {
        // Breadcrumb navigation
        DocumentBreadcrumbNavigation(
            currentPath = viewModel.currentPath,
            onPathClick = { viewModel.navigateToFolder(it) }
        )
        
        // Toolbar with actions
        DocumentToolbar(
            onUploadClick = onUploadClick,
            onCreateFolderClick = { viewModel.showCreateFolderDialog() },
            onSortClick = { viewModel.showSortOptions() }
        )
        
        // Content
        when (uiState) {
            is DocumentUiState.Loading -> {
                LoadingIndicator()
            }
            is DocumentUiState.Error -> {
                ErrorView(message = (uiState as DocumentUiState.Error).message) {
                    viewModel.refreshDocuments()
                }
            }
            else -> {
                if (folders.isEmpty() && documents.isEmpty()) {
                    EmptyView(
                        message = "No documents or folders found",
                        onActionClick = onUploadClick
                    )
                } else {
                    DocumentList(
                        folders = folders,
                        documents = documents,
                        onFolderClick = onFolderClick,
                        onDocumentClick = onDocumentClick,
                        onDocumentLongClick = { viewModel.showDocumentOptions(it) }
                    )
                }
            }
        }
    }
    
    // Handle dialogs
    if (viewModel.showCreateFolderDialog) {
        CreateFolderDialog(
            onDismiss = { viewModel.dismissCreateFolderDialog() },
            onConfirm = { name, description -> 
                viewModel.createFolder(name, description)
            }
        )
    }
    
    if (viewModel.showSortOptionsDialog) {
        SortOptionsDialog(
            currentSortBy = viewModel.currentSortBy,
            currentSortDirection = viewModel.currentSortDirection,
            onDismiss = { viewModel.dismissSortOptionsDialog() },
            onConfirm = { sortBy, sortDirection ->
                viewModel.setSortOptions(sortBy, sortDirection)
            }
        )
    }
    
    if (viewModel.showDocumentOptionsDialog) {
        DocumentOptionsDialog(
            document = viewModel.selectedDocument,
            onDismiss = { viewModel.dismissDocumentOptionsDialog() },
            onDownload = { viewModel.downloadDocument(it) },
            onShare = { viewModel.shareDocument(it) },
            onDelete = { viewModel.deleteDocument(it.id) },
            onRename = { viewModel.showRenameDocumentDialog(it) },
            onChangeAccess = { viewModel.showChangeAccessDialog(it) }
        )
    }
}
```

### 2. Document Detail Screen

```kotlin
// ui/screens/DocumentDetailScreen.kt
@Composable
fun DocumentDetailScreen(
    documentId: Long,
    viewModel: DocumentDetailViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val document by viewModel.document.collectAsState()
    val versions by viewModel.versions.collectAsState()
    
    LaunchedEffect(documentId) {
        viewModel.loadDocument(documentId)
        viewModel.loadVersions(documentId)
    }
    
    Column(modifier = Modifier.fillMaxSize()) {
        // Toolbar with actions
        DocumentDetailToolbar(
            document = document,
            onBackClick = { /* Navigate back */ },
            onDownloadClick = { viewModel.downloadDocument() },
            onShareClick = { viewModel.shareDocument() },
            onMoreOptionsClick = { viewModel.showMoreOptions() }
        )
        
        // Content
        when (uiState) {
            is DocumentDetailUiState.Loading -> {
                LoadingIndicator()
            }
            is DocumentDetailUiState.Error -> {
                ErrorView(message = (uiState as DocumentDetailUiState.Error).message) {
                    viewModel.loadDocument(documentId)
                }
            }
            else -> {
                document?.let { doc ->
                    DocumentDetailContent(
                        document = doc,
                        versions = versions,
                        onVersionClick = { viewModel.showVersionDetails(it) },
                        onPreviewClick = { viewModel.previewDocument() }
                    )
                }
            }
        }
    }
    
    // Handle dialogs and actions
    if (viewModel.showMoreOptionsDialog) {
        DocumentMoreOptionsDialog(
            onDismiss = { viewModel.dismissMoreOptionsDialog() },
            onRename = { viewModel.showRenameDialog() },
            onChangeAccess = { viewModel.showChangeAccessDialog() },
            onDelete = { viewModel.showDeleteConfirmationDialog() },
            onUploadNewVersion = { viewModel.showUploadNewVersionDialog() }
        )
    }
    
    // Other dialogs...
}
```
