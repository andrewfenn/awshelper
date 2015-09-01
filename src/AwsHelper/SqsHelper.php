<?php namespace AwsHelper;

use Aws\Sqs\SqsClient;
use Aws\S3\Exception\S3Exception;

class SqsHelper
{

    protected $sqs;
    protected $queue_url;
    protected $region;

    public function __construct(AwsHelper $adapter, $queue_url, $region)
    {
        $this->setQueueUrl($queue_url);
        $this->adapter = $adapter;
        $this->region = $region;
        $this->connect();
    }

    public function connect()
    {
        $this->sqs = SqsClient::factory($this->adapter->getDefaultOptions() + ['region' => $this->region]);
    }

    public function setQueueUrl($queue_url)
    {
        $this->queue_url = $queue_url;
    }

    /* For when you want to stop the listener from running */
    public function stop()
    {
        $this->stop = true;
    }

    /* Begin listening for messages from the SQS queue.
    @param $num_msg Dictates how many messages you wish to process at a time
    */
    public function listen($num_msg = 1)
    {
        $expirationTime = $this->adapter->getJSON()->Expiration;
        $this->stop = false;
        while (!$this->stop) {

            if ($this->adapter->hasAccessExpired($expirationTime))
            {
                $this->connect();
                $expirationTime = $this->adapter->getJSON()->Expiration;
            }

            $messages = $this->sqs->receiveMessage([
                'QueueUrl'        => $this->queue_url,

                // Enables HTTP long polling
                'WaitTimeSeconds' => 20,
                'MaxNumberOfMessages' => $num_msg,
            ]);

            if (!$messages->hasKey('Messages')) {
                continue;
            }

            foreach ($messages->get('Messages') as $msg_data) {
                yield new SqsMessage($msg_data);
            }
        }
    }

    /* Throws a message off the queue. I.E. It's been processed successfully
    @params $message Feed it the message object you recieved from the listen method
    */
    public function remove(SqsMessage $message)
    {
        $messages = $this->sqs->deleteMessage([
            'QueueUrl'       => $this->queue_url,
            'ReceiptHandle'  => $message->get('ReceiptHandle'),

        ]);
    }

    /* Pushes something to the SQS service
    @params $data The data of your message.
    @params $attributes Any optional meta data attributes you wish to feed through
    */
    public function push($data, $attributes = [])
    {
        return $this->sqs->sendMessage([
            'QueueUrl' => $this->queue_url,
            'MessageBody' => $data,
            'MessageAttributes' => $attributes,
        ]);
    }

    /**
     * Get a list of queue attributes
     * @param  array $attributes An array of queue attribute names
     * @return array             An array of specified queue attributes with keys as attribute names
     */
    public function getQueueAttributes($attributes)
    {
        $queueAttributes = $this->sqs->getQueueAttributes([
            'QueueUrl' => $this->queue_url,
            'AttributeNames' => $attributes,
        ]);
        if (!$queueAttributes->hasKey('Attributes')) {
            return [];
        }
        return $queueAttributes->get('Attributes');
    }

    /**
     * Shorthand function to get a single queue attribute
     * @param  string $attribute A Queue attribute
     * @return mixed             String data of the specified queue attribute or null
     */
    public function getQueueAttribute($attribute)
    {
        $queueAttributes = $this->getQueueAttributes([$attribute]);
        return array_key_exists($attribute, $queueAttributes) ? $queueAttributes[$attribute] : null;
    }
}


class SqsMessage
{
    public function __construct($msg_data)
    {
        $this->data = $msg_data;
    }

    public function getAll()
    {
        return $this->data;
    }

    public function get($key)
    {
        return $this->data[$key];
    }

    public function has($key)
    {
        return empty($this->data[$key]);
    }
}
