<?php

namespace Jmjl161100\ChunkUpload\Support;

use Exception;
use Illuminate\Http\UploadedFile;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Http\Client;
use Qiniu\Http\RequestOptions;

use function Qiniu\base64_urlSafeEncode;

class QiniuUploader
{
    private array $diskConfig; // 磁盘配置

    private Auth $auth;

    private Config $config;

    private RequestOptions $requestOptions;

    /**
     * @param  array  $diskConfig  磁盘配置
     */
    public function __construct(array $diskConfig)
    {
        $this->diskConfig = $diskConfig;
        $auth = new Auth(
            $this->diskConfig['access_key'],
            $this->diskConfig['secret_key']
        );
        $this->auth = $auth;
        $this->config = new Config;
        $this->requestOptions = new RequestOptions;
    }

    /**
     * 初始化分片上传
     *
     * @param  string  $fileName  上传的文件名
     * @return array 包含 uploadId 的数组
     *
     * @throws Exception
     */
    public function init(string $fileName): array
    {
        $url = $this->getUploadUrl($fileName).'?uploads';
        $token = $this->auth->uploadToken($this->diskConfig['bucket'], $fileName);
        $headers = [
            'Authorization' => 'UpToken '.$token,
        ];

        $client = new Client;
        $response = $client->post($url, null, $headers, $this->requestOptions);

        if (! $response->ok()) {
            throw new Exception('Initiate multipart upload failed: '.$response->error);
        }

        $rt = $response->json();
        cache()->put($rt['uploadId'], ['fileName' => $fileName, 'token' => $token], 600);

        return $rt;
    }

    /**
     * 上传分片
     *
     * @param  string  $uploadId  分片上传 ID
     * @param  int  $partNumber  分片序号（从1开始）
     * @param  UploadedFile  $file  分片文件
     * @return array 包含 etag 的数组
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function uploadPart(string $uploadId, int $partNumber, UploadedFile $file): array
    {
        $initCache = cache()->get($uploadId);
        $fileName = $initCache['fileName'];
        $token = $initCache['token'];
        $url = $this->getUploadUrl($fileName).'/'.$uploadId.'/'.$partNumber;
        $headers = [
            'Authorization' => 'UpToken '.$token,
            'Content-Type' => 'application/octet-stream',
        ];

        $client = new Client;
        $response = $client->put($url, $file->getContent(), $headers, $this->requestOptions);

        if (! $response->ok()) {
            throw new Exception("Upload part {$partNumber} failed: ".$response->error);
        }

        return $response->json();
    }

    /**
     * 完成分片上传
     *
     * @param  string  $uploadId  分片上传ID
     * @param  array  $parts  所有分片信息数组 [['partNumber' => 1, 'etag' => '...'], ...]
     * @return array 包含文件信息的数组
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function complete(string $uploadId, array $parts): array
    {
        $initCache = cache()->get($uploadId);
        $fileName = $initCache['fileName'];
        $token = $initCache['token'];
        $url = $this->getUploadUrl($fileName).'/'.$uploadId;
        $headers = [
            'Authorization' => 'UpToken '.$token,
            'Content-Type' => 'application/json',
        ];

        // 按照partNumber排序
        usort($parts, function ($a, $b) {
            return $a['partNumber'] - $b['partNumber'];
        });

        $body = json_encode(['parts' => $parts]);

        $client = new Client;
        $response = $client->post($url, $body, $headers, $this->requestOptions);

        if (! $response->ok()) {
            throw new Exception('Complete multipart upload failed: '.$response->error);
        }

        cache()->forget($uploadId);

        return $response->json();
    }

    /**
     * 获取上传URL
     *
     * @param  string  $fileName  上传的文件名
     * @return string 上传URL
     */
    private function getUploadUrl(string $fileName): string
    {
        $host = $this->config->getUpHost($this->auth->getAccessKey(), $this->diskConfig['bucket']);

        return $host.'/buckets/'.$this->diskConfig['bucket'].'/objects/'.base64_urlSafeEncode($fileName).'/uploads';
    }
}
