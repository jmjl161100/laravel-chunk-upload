[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-8892BF.svg)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E11.0-FF2D20.svg)](https://laravel.com)

# Laravel Chunk Upload

```markdown
Laravel package for handling chunked file uploads with multi-driver support (Local, Qiniu, etc.).
   ```

## Features

- ✅ Chunked file upload support
- ✅ Multiple storage drivers (Local, Qiniu Cloud)
- ✅ Progress tracking
- ✅ Automatic chunk merging
- ✅ Laravel 11.x compatibility

## Installation

1. Install via Composer:
   ```bash
   composer require jmjl161100/laravel-chunk-upload

## Configuration

Add a new disk to your config/filesystems.php config:

```php
<?php

return [
   'disks' => [
        //...
        'qiniu' => [
           'driver'     => 'qiniu',
           'access_key' => env('QINIU_ACCESS_KEY', 'xxxxxxxxxxxxxxxx'),
           'secret_key' => env('QINIU_SECRET_KEY', 'xxxxxxxxxxxxxxxx'),
           'bucket'     => env('QINIU_BUCKET', 'test'),
           'domain'     => env('QINIU_DOMAIN', 'xxx.clouddn.com'), // or host: https://xxxx.clouddn.com
        ],
        //...
    ]
];
```

## Usage

### Basic Upload

```php
use Jmjl161100\ChunkUpload\Facades\CheckUpload;

// initialization
$rt = CheckUpload::init($fileName);

// Upload data in chunks
$rt = CheckUpload::uploadPart($uploadId, $partNumber, $file);

// Complete file upload
$rt = CheckUpload::complete($uploadId, $partList);
```

### Frontend Integration

JavaScript example (using Axios):

```javascript
async function uploadFile(file) {
    const chunkSize = 5 * 1024 * 1024; // 5MB
    const totalChunks = Math.ceil(file.size / chunkSize);

    for (let i = 0; i < totalChunks; i++) {
        const chunk = file.slice(i * chunkSize, (i + 1) * chunkSize);
        await axios.post('/upload-chunk', {
            file_id: file.name,
            chunk: chunk,
            index: i
        });
    }

    await axios.post('/complete-upload', {file_id: file.name});
}
```

## Drivers

### Supported Drivers

1. **Local Driver**
    - **Chunk Storage**: Chunks under the disk root configuration
    - **Final Files**: Disk root configuration

2. **Qiniu**
    - Requires `qiniu/php-sdk` package
    - Direct upload to Qiniu Cloud Storage

## Security

If you discover any security issues, please email author instead of creating an issue.

## License

MIT License (see [LICENSE](LICENSE))
