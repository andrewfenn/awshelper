<?php namespace AwsHelper;

/* this class is designed to work on EC2 servers and in the office only.
If you need to access the security settings outside the office then please
add your own aws.json file to the storage folder with the needed security
access key and password */

class AwsHelper
{
    public function __construct($iam_role, $iam_url='http://169.254.169.254/latest/meta-data/iam/security-credentials/')
    {

        if (empty($iam_role))
            throw new \Exception('iam_role is empty');

        if (empty($iam_url))
            throw new \Exception('iam_url is empty');

        $json = $this->getAccessData($iam_role, $iam_url);

        if ($json->Code !== 'Success')
            throw new \Exception('Unsuccessful in returning iam amazon credentials');

        if (empty($json->AccessKeyId))
            throw new \Exception('AccessKeyId does not exist.');

        if (empty($json->SecretAccessKey))
            throw new \Exception('SecretAccessKey does not exist.');

        $this->json = $json;
    }

    public function getJSON()
    {
        return $this->json;
    }

    public function getDefaultOptions($aws_region=null, $aws_key=null, $aws_secret=null)
    {
        $opts = [
            'key'    => $aws_key,
            'secret' => $aws_secret,
        ];

        if (empty($aws_key) || empty($aws_secret))
        {
            $json = $this->getJSON();

            if ($json->Code !== 'Success')
                throw new \Exception('Unsuccessful in returning iam amazon credentials');

            if (empty($json->AccessKeyId))
                throw new \Exception('AccessKeyId does not exist. This is possibly a problem with you aws setup.');

            if (empty($json->SecretAccessKey))
                throw new \Exception('SecretAccessKey does not exist. This is possibly a problem with you aws setup.');

            $opts = [
                'key'    => $json->AccessKeyId,
                'secret' => $json->SecretAccessKey,
                'token'  => $json->Token,
            ];
        }

        if (!empty($aws_region))
            $opts['region'] = $aws_region;

        return $opts;
    }

    private function getAccessData($iam_role, $iam_url)
    {
        /* grab the access and secret key from the iam role "aws-opsworks-ec2-role" */
        if (file_exists(storage_path().'/aws.json'))
        {
            $json = json_decode(file_get_contents(storage_path().'/aws.json'));
            if (property_exists($json, 'Expiration') and !$this->hasAccessExpired($json->Expiration))
            {
                return $json;
            }
        }

        // get json from the amazon iam service.
        $json = file_get_contents($iam_url.$iam_role);
        file_put_contents(storage_path().'/aws.json', $json);

        return json_decode($json);
    }

    private function hasAccessExpired($date)
    {
        $date = new \DateTime($date);
        if ($date->format('U')-date('U') > 0)
            return false;

        return true;
    }
}
