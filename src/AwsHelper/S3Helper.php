<?php namespace AwsHelper;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class S3Helper
{
    protected $s3;
    protected $adapter;
    protected $bucket_name;
    protected $region;

    public function __construct(AwsHelper $adapter, $bucket_name, $region)
    {
        $this->bucket_name = $bucket_name;
        $this->adapter = $adapter;
        $this->region = $region;
        $this->connect();
    }

    public function connect()
    {
        $this->s3 = S3Client::factory($this->adapter->getDefaultOptions() + ['region' => $this->region]);
    }

    /* When accessing the S3Client you should call this function everytime
    so that you can be sure that the token isn't going to expire on you in the
    middle of a call */
    public function getClient()
    {
        $expirationTime = $this->adapter->getJSON()->Expiration;
        if ($this->adapter->hasAccessExpired($expirationTime)) {
            $this->connect();
        }
        return $this->s3;
    }

    /*
    @param $file_location   The location of the file on your local system
    @param $s3_location     The location of the file you want to upload to s3
    @param $acl             What access the file should have, options are: private,
        public-read, public-read-write, authenticated-read, bucket-owner-read,
        bucket-owner-full-control
    @param $params          Additional parameters you want to pass to S3 client.
    */
    public function saveFile($file_location, $s3_location, $acl = 'private', $params = [])
    {
        if ($this->s3 == null)
            return false;

        $this->getClient();

        try {
            $result = $this->s3->putObject([
                'Bucket'     => $this->bucket_name,
                'Key'        => $s3_location,
                'SourceFile' => $file_location,
                'ACL'        => $acl,
            ] + $params);
        } catch (\Exception $e) {
            return false;
        }
    }

    /*
    @param $file_content    The file content to upload to s3
    @param $s3_location     The location of the file you want to upload to s3
    @param $acl             What access the file should have, options are: private,
        public-read, public-read-write, authenticated-read, bucket-owner-read,
        bucket-owner-full-control
    @param $params          Additional parameters you want to pass to S3 client.
    */
    public function saveFileContent($file_content, $s3_location, $acl = 'private', $params = [])
    {
        if ($this->s3 == null)
            return false;

        $this->getClient();

        try {
            $result = $this->s3->putObject([
                'Bucket'     => $this->bucket_name,
                'Key'        => $s3_location,
                'Body'       => $file_content,
                'ACL'        => $acl,
            ] + $params);
        } catch (\Exception $e) {
            return false;
        }
    }

    /*
    @param  $s3_location the location of the file on s3
    @return A pointer to a temporary file that is deleted upon script exit
        or false if error
    */
    public function getFile($s3_location)
    {
        if ($this->s3 == null)
            return false;

        $this->getClient();

        try {
            $result = $this->s3->getObject([
                'Bucket' => $this->bucket_name,
                'Key'    => $s3_location,
            ]);
        }
        catch (\Exception $e)
        {
            return false;
        }

        $result['Body']->rewind();
        $temp_file = tmpfile();
        while ($data = $result['Body']->read(1024)) {
            fwrite($temp_file, $data);
        }

        fseek($temp_file, 0);
        return $temp_file;
    }
}
