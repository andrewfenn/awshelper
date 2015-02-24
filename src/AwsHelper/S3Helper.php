<?php namespace AwsHelper;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class S3Helper
{
    public function __construct(AwsHelper $adapter, $bucket_name)
    {
        $this->bucket_name = $bucket_name;
        $this->s3 = S3Client::factory($adapter->getDefaultOptions());
    }

    /*
    @param $file_location   The location of the file on your local system
    @param $s3_location     The location of the file you want to upload to s3
    @param $acl             What access the file should have, options are: private,
        public-read, public-read-write, authenticated-read, bucket-owner-read,
        bucket-owner-full-control
    @param $metadata        Metadata you want to store with the file.
    */
    public function saveFile($file_location, $s3_location, $acl = 'private', $metadata=[])
    {
        if ($this->s3 == null)
            return false;
        try {
            $result = $this->s3->putObject([
                'Bucket'     => $this->bucket_name,
                'Key'        => $s3_location,
                'SourceFile' => $file_location,
                'ACL'        => $acl,
                'Metadata'   => $metadata,
            ]);
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
    @param $metadata        Metadata you want to store with the file.
    */
    public function saveFileContent($file_content, $s3_location, $acl = 'private', $metadata=[])
    {
        if ($this->s3 == null)
            return false;

        try {
            $result = $this->s3->putObject([
                'Bucket'     => $this->bucket_name,
                'Key'        => $s3_location,
                'Body'       => $file_content,
                'ACL'        => $acl,
                'Metadata'   => $metadata,
            ]);
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
