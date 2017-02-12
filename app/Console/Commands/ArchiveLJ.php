<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Created by PhpStorm.
 * User: vnilov
 * Date: 12.02.17
 * Time: 18:11
 */
class ArchiveLJ extends Command
{
    protected $signature = 'lj-archive:start {count?}';

    protected $description = 'Start archiving LJ entries';

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

        $this->config = json_decode(file_get_contents("/home/ubuntu/www/aws.lookover.me/service/public/" . "config.json"));
        parent::__construct();
    }

    public function handle()
    {
        $i = 0;
        $count = ($this->argument('count') > 0) ? $this->argument('count') : 1;
        if ($this->config->lastId != -1) {
            $data = json_decode(file_get_contents("http://api.livejournal.com/os/2.0/rest/entries/27596127/@self/@posted?count=" . $count . "&fields=updated,id,likes,comments,body,mediaItems,title,digest,tags,created&startIndex=" . $this->config->lastIndex . ""));

            foreach ($data->entry as $entry) {
                if ($entry->id == $this->config->lastId) {
                    file_put_contents("/home/ubuntu/www/aws.lookover.me/service/public/" . "config.json", json_encode(["lastId" => -1, "lastIndex" => -1]));
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
                $i++;
                $lastId = $entry->id;
            }
            file_put_contents("/home/ubuntu/www/aws.lookover.me/service/public/" . "config.json", json_encode(["lastId" => $lastId, "lastIndex" => $this->config->lastIndex + $i]));
        }
    }

}