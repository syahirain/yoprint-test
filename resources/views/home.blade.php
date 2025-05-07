<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>File Upload Yoprint</title>
  <link rel="stylesheet" href="{{ asset('css/home.css') }}">
</head>
<body>

<label for="fileElem"><div class="upload-box" id="drop-area">
  <div id="upload-text">Select file/Drag and drop</div>
  <input type="file" id="fileElem" multiple>
  <button class="upload-btn" onclick="uploadChunkFile()" style="display: none;" id="upload-btn">Upload File</button>
</div></label>

<table>
  <thead>
    <tr>
      <th>Time</th>
      <th>File Name</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody id="fileTableBody">
    @foreach ($files as $file)
        <tr>
            <td>{{ $file->created_at }}</td>
            <td>{{ $file->name }}</td>
            <td>{{ $file->status }}</td>
        </tr>
    @endforeach
  </tbody>
</table>

<script>
  const dropArea = document.getElementById('drop-area');
  const fileElem = document.getElementById('fileElem');
  const tableBody = document.getElementById('fileTableBody');
  const uploadButton = document.getElementById('upload-btn');
  const uploadText = document.getElementById('upload-text');
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  let isUploading = false;
  let fileCount = 0;

  function getTimeString() {
    const now = new Date();
    const time = now.toLocaleString();
    const ago = "Just now";
    return `${time}<br><small>${ago}</small>`;
  }

  function addFileToTable(file) {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${getTimeString()}</td>
      <td>${file.name}</td>
      <td class="status-pending">pending</td>
    `;
    tableBody.appendChild(row);
  }

  async function uploadChunkFile() {
    const fileContent = fileElem.files[0];
    const chunkSize = 5 * 1024 * 1024; // 5MB
    const uuid = generateUUIDv4(); // unique id
    let start = 0;
    let chunkIndex = 0;
    const totalChunks = Math.ceil(fileContent.size / chunkSize);
    isUploading = true;

    // Show toast
    const toast = document.getElementById('upload-toast');
    const progressText = document.getElementById('upload-progress');
    toast.style.display = 'block';

    while (start < fileContent.size) {
        const chunk = fileContent.slice(start, start + chunkSize);
        const formData = new FormData();
        formData.append('file_chunk', chunk);
        formData.append('chunk_index', chunkIndex);
        formData.append('file_id', uuid);
        formData.append('file_name', fileContent.name);
        formData.append('file_ext', fileContent.name.split('.').pop().toLowerCase());
        formData.append('total_chunks', totalChunks);

        await fetch('/upload', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        });

        chunkIndex++;
        start += chunkSize;

        // Update toast progress
        const percent = Math.floor((chunkIndex / totalChunks) * 100);
        progressText.textContent = `${percent}%`;
    }

    // Finalize
    isUploading = false;
    clearFileInput();
    progressText.textContent = `Upload Complete`;
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
  }

  function generateUUIDv4() {
    return ([1e7]+-1e3+-4e3+-8e3+-1e11)
        .replace(/[018]/g, c =>
        (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
        );
  }

  function clearFileInput(){
    fileElem.value = '';
    uploadText.textContent = "Select file/Drag and drop";
    uploadButton.classList.remove('show');
  }

  function refreshTableContent(files){
    // Clear existing rows
    tableBody.innerHTML = '';

    // Add new rows
    files.forEach(file => {
        const row = document.createElement('tr');

        const timeCell = document.createElement('td');
        timeCell.textContent = file.created_at;

        const nameCell = document.createElement('td');
        nameCell.textContent = file.name;

        const statusCell = document.createElement('td');
        statusCell.textContent = file.status;

        row.appendChild(timeCell);
        row.appendChild(nameCell);
        row.appendChild(statusCell);

        tableBody.appendChild(row);
    });
  }

  fileElem.addEventListener('change', (e) => {
    const files = e.target.files;
    if (files.length > 0) {
        const file = files[0]; // Assuming single-file upload
        uploadText.textContent = "File name: " + file.name;
        uploadButton.classList.add('show');
    }
  });

  window.addEventListener('beforeunload', function (e) {
    // Show warning only if an upload is in progress
    if (isUploading) {
        e.preventDefault(); // For some browsers
        e.returnValue = ''; // Required for Chrome
    }
  });

  const eventSource = new EventSource('/data');

  eventSource.onmessage = function(event) {
    const files = JSON.parse(event.data);

    // if(files.length > fileCount){
        refreshTableContent(files);
        fileCount = files.length;
    // }
  };

  eventSource.onerror = function(error) {
    console.error("SSE connection error:", error);
  };

//   dropArea.addEventListener('dragover', (e) => {
//     e.preventDefault();
//     dropArea.classList.add('dragover');
//   });

//   dropArea.addEventListener('dragleave', () => {
//     dropArea.classList.remove('dragover');
//   });

//   dropArea.addEventListener('drop', (e) => {
//     e.preventDefault();
//     dropArea.classList.remove('dragover');
//     const files = e.dataTransfer.files;
//     for (const file of files) {
//       addFileToTable(file);
//     }
//   });

//   function showToast(message) {
//     const toast = document.getElementById('upload-toast');
//     toast.textContent = message;
//     toast.style.display = 'block';
//     setTimeout(() => {
//         toast.style.display = 'none';
//     }, 3000);
//     }

//     // Call this when file(s) are uploaded
//     fileElem.addEventListener('change', (e) => {
//     if (e.target.files.length > 0) {
//         showToast("Uploading files...");
//     }
//     for (const file of e.target.files) {
//         addFileToTable(file);
//     }
//   });
</script>

<div id="upload-toast" style="display:none; position: fixed; bottom: 20px; right: 20px; background: #333; color: #fff; padding: 16px 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); z-index: 1000;">
  Upload Progress: <span id="upload-progress">0%</span>
</div>
</body>
</html>
