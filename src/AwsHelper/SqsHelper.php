<?php namespace AwsHelper;

use Aws\Sqs\SqsClient;
use Aws\S3\Exception\S3Exception;

class SqsHelper
{
    public function __construct(AwsHelper $adapter, $queue_url)
    {
        $this->queue_url = $queue_url;
        $this->sqs = SqsClient::factory($adapter->getDefaultOptions());
    }

    /* For when you want to stop the listener from running */
    public function stop()
    {
        $this->stop = true;
    }

    /* Begin listening for messages from the SQS queue.
    @param $num_msg Dictates how many messages you wish to process at a time
    */
    public function listen($num_msg=1)
    {
        $this->stop = false;
        while(!$this->stop)
        {
            $messages = $this->sqs->receiveMessage([
                'QueueUrl'        => $this->queue_url,

                // Enables HTTP long polling
                'WaitTimeSeconds' => 20,
                'MaxNumberOfMessages' => $num_msg,
            ]);

            if (!$messages->hasKey('Messages'))
                continue;

            foreach($messages->get('Messages') as $msg_data)
            {
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
    public function push($data, $attributes=[])
    {
        return $this->sqs->sendMessage([
            'QueueUrl' => $this->queue_url,
            'MessageBody' => $data,
            'MessageAttributes' => $attributes,
        ]);
    }
}


Class SqsMessage
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
