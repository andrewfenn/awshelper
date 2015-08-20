<?php namespace AwsHelper;

/* this class is designed to work on EC2 servers and in the office only.
If you need to access the security settings outside the office then please
add your own aws.json file to the storage folder with the needed security
access key and password */

class AwsHelper
{
    private $iam_role;
    private $iam_url;

    public function __construct($iam_role, $iam_url='http://169.254.169.254/latest/meta-data/iam/security-credentials/')
    {

        if (empty($iam_role))
            throw new \Exception('iam_role is empty');

        if (empty($iam_url))
            throw new \Exception('iam_url is empty');

        $this->iam_role = $iam_role;
        $this->iam_url  = $iam_url;

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

    public function getDefaultOptions($aws_key=null, $aws_secret=null)
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

            if (!empty($json->Token))
            {
                // check token expiration
                if ($this->hasAccessExpired($json->Expiration))
                {
                    $this->json = $this->getAccessData($this->iam_role, $this->iam_url);
                    return $this->getDefaultOptions();
                }
            }

            $opts = [
                'key'    => $json->AccessKeyId,
                'secret' => $json->SecretAccessKey,
                'token'  => $json->Token,
            ];
        }

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

    public function hasAccessExpired($date)
    {
        $date = new \DateTime($date);
        if ($date->format('U')-date('U') > 0)
            return false;

        return true;
    }
}
