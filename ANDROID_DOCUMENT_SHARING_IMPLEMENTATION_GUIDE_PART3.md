# Hướng dẫn triển khai tính năng Chia sẻ Tài liệu trên Android (Phần 3)

## 11. ViewModel

```kotlin
@HiltViewModel
class DocumentViewModel @Inject constructor(
    private val documentRepository: DocumentRepository,
    private val fileManager: FileManager
) : ViewModel() {
    
    private val _currentTeamId = MutableStateFlow<Long?>(null)
    private val _currentFolderId = MutableStateFlow<Long?>(null)
    
    // Danh sách tài liệu
    val documents = combine(
        _currentTeamId,
        _currentFolderId
    ) { teamId, folderId ->
        Pair(teamId, folderId)
    }.flatMapLatest { (teamId, folderId) ->
        if (teamId == null) {
            flow { emit(emptyList<Document>()) }
        } else {
            documentRepository.getTeamDocuments(teamId, folderId)
        }
    }.stateIn(
        scope = viewModelScope,
        started = SharingStarted.WhileSubscribed(5000),
        initialValue = emptyList()
    )
    
    // Đặt nhóm và thư mục hiện tại
    fun setTeamAndFolder(teamId: Long, folderId: Long? = null) {
        _currentTeamId.value = teamId
        _currentFolderId.value = folderId
        refreshDocuments()
    }
    
    // Làm mới danh sách tài liệu
    fun refreshDocuments() {
        val teamId = _currentTeamId.value ?: return
        val folderId = _currentFolderId.value
        
        viewModelScope.launch {
            documentRepository.refreshTeamDocuments(teamId, folderId)
        }
    }
    
    // Tải lên tài liệu mới
    fun uploadDocument(
        filePath: String,
        name: String,
        description: String? = null,
        accessLevel: String = "team"
    ): Flow<Result<Document>> = flow {
        val teamId = _currentTeamId.value ?: throw IllegalStateException("Team ID not set")
        val folderId = _currentFolderId.value
        
        emit(Result.Loading())
        
        val result = documentRepository.uploadDocument(
            teamId = teamId,
            filePath = filePath,
            name = name,
            description = description,
            folderId = folderId,
            accessLevel = accessLevel
        )
        
        emit(result)
    }
    
    // Tải xuống tài liệu
    fun downloadDocument(document: Document): Flow<Result<File>> = flow {
        emit(Result.Loading())
        
        try {
            val result = documentRepository.downloadDocument(document.id)
            emit(result)
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }
    
    // Xóa tài liệu
    fun deleteDocument(documentId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading())
        
        try {
            val result = documentRepository.deleteDocument(documentId)
            emit(result)
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }
    
    // Lấy phiên bản tài liệu
    fun getDocumentVersions(documentId: Long): Flow<List<DocumentVersion>> {
        return documentRepository.getDocumentVersions(documentId)
    }
    
    // Tải lên phiên bản mới
    fun uploadNewVersion(
        documentId: Long,
        filePath: String,
        versionNote: String? = null
    ): Flow<Result<DocumentVersion>> = flow {
        emit(Result.Loading())
        
        try {
            val result = documentRepository.uploadNewVersion(
                documentId = documentId,
                filePath = filePath,
                versionNote = versionNote
            )
            emit(result)
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }
}
```

## 12. UI

### 12.1 DocumentListScreen

```kotlin
@Composable
fun DocumentListScreen(
    viewModel: DocumentViewModel = hiltViewModel(),
    teamId: Long,
    folderId: Long? = null,
    onDocumentClick: (Document) -> Unit,
    onUploadClick: () -> Unit
) {
    val documents by viewModel.documents.collectAsState()
    val context = LocalContext.current
    
    LaunchedEffect(teamId, folderId) {
        viewModel.setTeamAndFolder(teamId, folderId)
    }
    
    Column(
        modifier = Modifier.fillMaxSize()
    ) {
        // Toolbar với nút tải lên
        TopAppBar(
            title = { Text("Tài liệu") },
            actions = {
                IconButton(onClick = onUploadClick) {
                    Icon(Icons.Default.Add, contentDescription = "Tải lên")
                }
            }
        )
        
        // Danh sách tài liệu
        if (documents.isEmpty()) {
            EmptyDocumentList(onUploadClick = onUploadClick)
        } else {
            LazyColumn {
                items(documents) { document ->
                    DocumentItem(
                        document = document,
                        onClick = { onDocumentClick(document) },
                        onDownloadClick = {
                            viewModel.downloadDocument(document)
                                .onEach { result ->
                                    when (result) {
                                        is Result.Success -> {
                                            Toast.makeText(
                                                context,
                                                "Đã tải xuống: ${result.data.name}",
                                                Toast.LENGTH_SHORT
                                            ).show()
                                        }
                                        is Result.Error -> {
                                            Toast.makeText(
                                                context,
                                                "Lỗi: ${result.exception.message}",
                                                Toast.LENGTH_SHORT
                                            ).show()
                                        }
                                        else -> {}
                                    }
                                }
                                .launchIn(CoroutineScope(Dispatchers.Main))
                        }
                    )
                }
            }
        }
    }
}

@Composable
fun DocumentItem(
    document: Document,
    onClick: () -> Unit,
    onDownloadClick: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(8.dp)
            .clickable(onClick = onClick),
        elevation = 4.dp
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Thumbnail hoặc icon loại file
            Box(
                modifier = Modifier
                    .size(48.dp)
                    .background(MaterialTheme.colors.surface)
            ) {
                if (document.thumbnailUrl != null) {
                    AsyncImage(
                        model = document.thumbnailUrl,
                        contentDescription = null,
                        modifier = Modifier.fillMaxSize(),
                        contentScale = ContentScale.Crop
                    )
                } else {
                    Icon(
                        imageVector = when {
                            document.fileType.contains("pdf") -> Icons.Default.PictureAsPdf
                            document.fileType.contains("image") -> Icons.Default.Image
                            document.fileType.contains("word") -> Icons.Default.Description
                            document.fileType.contains("excel") -> Icons.Default.TableChart
                            else -> Icons.Default.InsertDriveFile
                        },
                        contentDescription = null,
                        modifier = Modifier.align(Alignment.Center)
                    )
                }
            }
            
            // Thông tin tài liệu
            Column(
                modifier = Modifier
                    .weight(1f)
                    .padding(start = 16.dp)
            ) {
                Text(
                    text = document.name,
                    style = MaterialTheme.typography.subtitle1,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                
                Text(
                    text = "Đăng bởi: ${document.uploadedBy.name}",
                    style = MaterialTheme.typography.caption
                )
                
                Text(
                    text = "Cập nhật: ${document.updatedAt.formatToDisplayDate()}",
                    style = MaterialTheme.typography.caption
                )
            }
            
            // Nút tải xuống
            IconButton(onClick = onDownloadClick) {
                Icon(
                    imageVector = Icons.Default.Download,
                    contentDescription = "Tải xuống"
                )
            }
        }
    }
}
```
