<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Created by PhpStorm.
 * User: vnilov
 * Date: 12.02.17
 * Time: 18:11
 */
class NewLJ extends Command
{
    protected $signature = 'lj-new:start {count?}';

    protected $description = 'Get LJ entries';

    private $config, $dynamoClient, $s3Client;

    public function __construct()
    {
        $this->dynamoClient = new \Aws\DynamoDb\DynamoDbClient([
            'profile' => 'default',
            'region'  => 'us-west-2',
            'version' => 'latest'
        ]);

        $this->s3Client = new \Aws\S3\S3Client([
            'profile' => 'default',
            'region' => 'eu-central-1',
            'version' => 'latest'
        ]);

        $this->config = json_decode(file_get_contents("/home/ubuntu/www/aws.lookover.me/service/public/" . "config_new_entries.json"));
        parent::__construct();
    }

    public function handle()
    {
        $count = ($this->argument('count') > 0) ? $this->argument('count') : 1;
        $data = json_decode(file_get_contents("http://api.livejournal.com/os/2.0/rest/entries/27596127/@self/@posted?count=" . $count . "&fields=updated,id,likes,comments,body,mediaItems,title,digest,tags,created&startIndex=0"));
        $lastId = $this->config->lastId;
        echo "ID: " . $lastId . "\n";
        foreach ($data->entry as $entry) {
            if ($entry->id <= $lastId) {
                echo "This is an old one:" . $entry->id;
                return;
            }

            foreach ($entry->tags as $tag) {
                $this->dynamoClient->putItem([
                    'TableName' => 'lj_tags',
                    'Item' => [
                        'entry_id' => ['N' => $entry->id],
                        'tag' => ['S' => $tag]
                    ]
                ]);
                sleep(1);
            }
            $this->s3Client->putObject([
                'Bucket' => 'stars365',
                'Key' => $entry->id . '.json',
                'Body' => json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ]);
            if ($lastId < $entry->id)
                $lastId = $entry->id;
        }
        file_put_contents("/home/ubuntu/www/aws.lookover.me/service/public/" . "config_new_entries.json", json_encode(["lastId" => $lastId]));
    }
}