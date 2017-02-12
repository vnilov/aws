<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->get('/dynamo', function () {

    /*$client = new Aws\DynamoDb\DynamoDbClient([
        'profile' => 'default',
        'region'  => 'us-west-2',
        'version' => 'latest'
    ]);

    $result = $client->putItem(array(
        'TableName' => 'lj_tags',
        'Item' => [
            'entry_id' => ['N' => '777'],
            'tag'    => ['S' => 'бикини'],
        ]
    ));

    $s3_client = new Aws\S3\S3Client([
        'profile' => 'default',
        'region' => 'eu-central-1',
        'version' => 'latest'
    ]);

    $result2 = $s3_client->putObject(array(
        'Bucket' => 'stars365',
        'Key'    => 'data.txt',
        'Body'   => 'Hello!'
    ));

    return response()->json($result);*/
});

$app->get('lj-test', function () {
    $lj = new \App\Livejournal\Livejournal('junona', 'CTC2005trenirovka');
    $lj->setUsejournal('stars365');
    $lj->setVer();

    return $lj->getEvents('before', '50', ['before' => '2015-12-12 00:00:00']);
});

$app->get('server', function () {
    dd($_SERVER);
});

$app->get('info', function () {
    return phpinfo();
});