<?php

namespace Jmjl161100\ChunkUpload;

use Composer\InstalledVersions;
use Exception;
use Jmjl161100\ChunkUpload\Support\QiniuUploader;

class CheckUpload
{
    protected $disk;

    public function __construct()
    {
        $this->disk();
    }

    /**
     * 初始化驱动
     *
     * @param  string  $diskName  磁盘名称
     * @return QiniuUploader
     *
     * @throws Exception
     */
    public function disk(string $diskName = 'qiniu')
    {
        $diskConfig = config('filesystems.disks.'.$diskName);

        switch ($diskConfig['driver']) {
            case 'qiniu':
                //                if (InstalledVersions::isInstalled('qiniu/php-sdk')) {
                //                    throw new Exception('qiniu/php-sdk Not Installed.');
                //                }

                $qiniuUploader = new QiniuUploader($diskConfig);

                $this->disk = $qiniuUploader;

                break;
            default:
                throw new Exception('The current driver is not supported: '.$diskConfig['driver']);
        }

        return $this->disk;
    }

    public function __call($method, $arguments)
    {
        return $this->disk->{$method}(...$arguments);
    }

    public static function __callStatic($method, $arguments)
    {

        return (new static)->$method(...$arguments);
    }
}
