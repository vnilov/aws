<?php


namespace App\Livejournal;


interface LivejournalDB
{
    public function saveTag($tag_name);

    public function getTag($tag_name);
}