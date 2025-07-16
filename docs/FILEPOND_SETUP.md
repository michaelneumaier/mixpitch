# FilePond Setup Documentation

## Overview
This document outlines the FilePond configuration that has been set up for the enhanced file upload system.

## Installed Packages

### Backend (Composer)
- `sopamo/laravel-filepond` (v1.5.0) - Laravel FilePond integration package

### Frontend (NPM)
- `filepond` (v4.32.8) - Core FilePond library
- `filepond-plugin-file-validate-type` (v1.2.9) - File type validation plugin
- `filepond-plugin-file-validate-size` (v2.2.8) - File size validation plugin
- `filepond-plugin-image-preview` (v4.6.12) - Image preview plugin

## Configuration Files

### Laravel Configuration (`config/filepond.php`)
- **Middleware**: `['web', 'auth']` - Requires authentication for uploads
- **Route Prefix**: `filepond` - All FilePond routes are prefixed with `/filepond`
- **Temporary Path**: `filepond/temp` - Temporary file storage location
- **Chunks Path**: `filepond/chunks` - Chunked upload storage location
- **Disk**: `local` - Uses local storage for temporary files

### JavaScript Configuration (`resources/js/filepond-config.js`)
- **Global FilePond Setup**: Makes FilePond available globally as `window.FilePond`
- **Plugin Registration**: Automatically registers all installed plugins
- **Default Configuration**: Provides sensible defaults for chunked uploads
- **CSRF Protection**: Automatically includes CSRF tokens in requests
- **Chunk Settings**: 5MB chunk size with retry logic

### CSS Integration (`resources/css/app.css`)
- **FilePond Core Styles**: Imports `filepond/dist/filepond.css`
- **Image Preview Styles**: Imports `filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css`

## Storage Directories
The following directories have been created in `storage/app/`:
- `filepond/temp/` - For temporary file storage during uploads
- `filepond/chunks/` - For storing file chunks during chunked uploads

## API Routes
FilePond automatically registers the following routes:
- `PATCH /filepond/api` - Handle chunk uploads
- `POST /filepond/api/process` - Process complete file uploads
- `DELETE /filepond/api/process` - Delete uploaded files

## Environment Variables
The following environment variables can be configured in `.env`:
```env
FILEPOND_TEMP_PATH=filepond/temp
FILEPOND_TEMP_DISK=local
FILEPOND_CHUNKS_PATH=filepond/chunks
FILEPOND_SOFT_DELETE=false
```

## Default FilePond Configuration
The JavaScript configuration provides the following defaults:
- **Server URL**: `/filepond`
- **Timeout**: 7 seconds
- **Accepted File Types**: `audio/*`, `image/*`
- **Max File Size**: 100MB
- **Max Files**: 10
- **Chunk Size**: 5MB
- **Chunk Retries**: [500ms, 1s, 3s]
- **Multiple Files**: Enabled
- **Drag & Drop**: Enabled

## Usage
To use FilePond in a Livewire component or Blade template:

```javascript
// Create a FilePond instance
const pond = window.FilePondConfig.create(inputElement, {
    // Custom configuration options
    maxFiles: 5,
    acceptedFileTypes: ['audio/*']
});
```

## Security Features
- **Authentication Required**: All upload endpoints require user authentication
- **CSRF Protection**: All requests include CSRF tokens
- **File Validation**: Type and size validation on both client and server
- **Temporary Storage**: Files are stored temporarily and cleaned up automatically

## Next Steps
This setup provides the foundation for the enhanced file upload system. The next tasks will involve:
1. Creating database models for upload sessions and chunks
2. Building the chunk processing service
3. Creating the enhanced FileUploader Livewire component
4. Integrating with existing file management systems