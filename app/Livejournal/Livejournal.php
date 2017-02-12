<?php


namespace App\Livejournal;


use \PhpXmlRpc\Value;
use \PhpXmlRpc\Request;
use \PhpXmlRpc\Client;

class Livejournal
{

    protected $username;
    protected $password;
    protected $usejournal;
    protected $ver;

    /**
     * Livejournal constructor.
     */
    public function __construct($username, $password)
    {
        if (empty($username)) {
            throw new LivejournalException('no username');
        }

        if (empty($password)) {
            throw new LivejournalException('no password');
        }
        
        $this->username = new \PhpXmlRpc\Value($username, "string");
        $this->password = new \PhpXmlRpc\Value($password, "string");
    }

    public function getUserTags()
    {
        if (!empty($this->usejournal) > 0)
            $data['usejournal'] = $this->usejournal;
        return $this->sendRequest('getusertags', $data);
    }
    
    
    public function getEvents($selecttype = 'lastn', $howmany = '1', $additional_data = []) {
        if (!empty($this->usejournal) > 0)
            $data['usejournal'] = $this->usejournal;

        if (!empty($this->ver) > 0)
            $data['ver'] = $this->ver;

        foreach ($additional_data as $k => $v) {
            $data[$k] = new \PhpXmlRpc\Value($v, "string");
        }

        $data['selecttype'] = new \PhpXmlRpc\Value($selecttype, "string");
        $data['howmany']    = new \PhpXmlRpc\Value($howmany, "string");
        
        return $this->sendRequest('getevents', $data);
    }

    public function syncItems() {

        if (!empty($this->ver) > 0)
            $data['ver'] = $this->ver;

        $data['lastsync'] = new \PhpXmlRpc\Value("2016-12-12 00:00:00", "string");

        return $this->sendRequest('syncitems', $data);
    }

    /**
     * @param mixed $usejournal
     */
    public function setUsejournal($usejournal)
    {
        $this->usejournal = new \PhpXmlRpc\Value($usejournal, "string");
    }

    public function setVer($ver = 1) 
    {
        $this->ver = new \PhpXmlRpc\Value($ver, "string");
    }
    
    private function sendRequest($action, $data = array())
    {

        $data['username'] = $this->username;
        $data['password'] = $this->password;

        $structure = array(
            new \PhpXmlRpc\Value($data, "struct")
        );

        $m = new \PhpXmlRpc\Request('LJ.XMLRPC.'.$action, $structure);

        $client = new \PhpXmlRpc\Client("/interface/xmlrpc", "www.livejournal.com", 80);
        $client->request_charset_encoding = "UTF-8";
        $client->setAcceptedCompression('gzip');
        $res = $client->send($m);
        //var_dump($m);
        if (!$res->faultCode()) {
            $enc = new \PhpXmlRpc\Encoder;
            $result = $enc->decode($res->value());
        } else {
            throw new LivejournalException($res->faultString());
        }

        return $result;
    }
}