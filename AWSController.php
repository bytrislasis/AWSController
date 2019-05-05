<?php

namespace App\Http\Controllers;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Aws\CommandPool;
use Aws\CommandInterface;
use Aws\ResultInterface;
use GuzzleHttp\Promise\PromiseInterface;

class AWSController extends Controller
{

    private $version="latest";
    private $region="us-west-2";
    private $accesskeyid="ACESSKEY";
    private $secretaccesskey="SECRET KEY";

    private function AWSAccess (){
        return $credentials = new \Aws\Credentials\Credentials($this->accesskeyid ,$this->secretaccesskey);
    }

    public function AwsConnect(){
        $s3Client  = new \Aws\S3\S3Client([
            'version' => $this->version,
            'region'  => $this->region,
            'credentials' => $this->AWSAccess(),
        ]);

    }

    public function AwsCreateBucket ($BucketName, $Privacy){
        $s3Client  = new \Aws\S3\S3Client([
            'version' => $this->version,
            'region'  => $this->region,
            'credentials' => $this->AWSAccess(),
        ]);
        try{
            $s3Client->createBucket([
                'Bucket' => $BucketName,
                'ACL'    => $Privacy]);
        }catch (Aws\S3\Exception\S3Exception $e){
            return  $e->getMessage();
        }
    }

    public function AwsFtpDirectoryUpload($FtpPath, $ToBucketName){
        $s3Client  = new \Aws\S3\S3Client([
            'version' => $this->version,
            'region'  => $this->region,
            'credentials' => $this->AWSAccess(),
        ]);
        $files      = new \DirectoryIterator($FtpPath);
        $commandGenerator = function (\Iterator $files, $bucket) use ($s3Client) {
            foreach ($files as $file) {
                if ($file->isDot()) {
                    continue;
                }
                $filename = $file->getPath() . '/' . $file->getFilename();
                yield $s3Client->getCommand('PutObject', [
                    'Bucket' => $bucket,
                    'Key'    => $file->getBaseName(),
                    'Body'   => fopen($filename, 'r')
                ]);
            }
        };
        $commands = $commandGenerator($files, $ToBucketName);
        $pool = new CommandPool($s3Client, $commands, [
            'concurrency' => 5,
            'before' => function (CommandInterface $cmd, $iterKey) {
                echo "About to send {$iterKey}: "
                    . print_r($cmd->toArray(), true) . "\n";
            },
            'fulfilled' => function (
                ResultInterface $result,
                $iterKey,
                PromiseInterface $aggregatePromise
            ) {
                echo "Completed {$iterKey}: {$result}\n";
            },
            'rejected' => function (
                AwsException $reason,
                $iterKey,
                PromiseInterface $aggregatePromise
            ) {
                echo "Failed {$iterKey}: {$reason}\n";
            },
        ]);
                $promise = $pool->promise();
                $promise->wait();
                $promise->then(function() { echo "Done\n"; });
    }

    public function AwsGetDownloadLink (){
    //yazılacak
    }

}
