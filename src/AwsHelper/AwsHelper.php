<?php namespace AwsHelper;

/* this class is designed to work on EC2 servers and in the office only.
If you need to access the security settings outside the office then please
add your own aws.json file to the storage folder with the needed security
access key and password */

class AwsHelper
{
    public function __construct()
    {
        $json = $this->getAccessData();

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

    public function getDefaultOptions()
    {
        $opts = [
            'key'    => \Config::get('AWS.key', null),
            'secret' => \Config::get('AWS.secret', null),
        ];

        if (!\Config::has('AWS.key') || !\Config::has('AWS.secret'))
        {
            $json = $this->getJSON();

            if ($json->Code !== 'Success')
                throw new \Exception('Unsuccessful in returning iam amazon credentials');

            if (empty($json->AccessKeyId))
                throw new \Exception('AccessKeyId does not exist.');

            if (empty($json->SecretAccessKey))
                throw new \Exception('SecretAccessKey does not exist.');

            $opts = [
                'key'    => $json->AccessKeyId,
                'secret' => $json->SecretAccessKey,
                'token'  => $json->Token,
            ];
        }

        if (\Config::has('AWS.region'))
            $opts['region'] = \Config::get('AWS.region');

        return $opts;
    }

    private function getAccessData()
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

        if (empty(\Config::get('AWS.iam_role')))
            throw new \Exception('No iam_role defined in configuration');

        if (empty(\Config::get('AWS.iam_url')))
            throw new \Exception('No iam_url defined in configuration');

        // get json from the amazon iam service.
        $json = file_get_contents(\Config::get('AWS.iam_url').\Config::get('AWS.iam_role'));
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
