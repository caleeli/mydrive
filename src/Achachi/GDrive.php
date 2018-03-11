<?php

namespace Achachi;

use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use InvalidArgumentException;

/**
 * Description of GDrive
 *
 * @author davidcallizaya
 */
class GDrive {

    const MIME_FOLDER = 'application/vnd.google-apps.folder';

    /**
     *
     * @var Google_Client $client
     */
    private $client;
    /**
     *
     * @var Google_Service_Drive $service
     */
    public $service;

    public function __construct($redirect_uri, $token)
    {
        $config = getenv('config.json');
        $this->client = new Google_Client();
        $this->client->setAuthConfig($config);
        $this->client->setAccessType("offline");
        $this->client->setIncludeGrantedScopes(true);
        $this->client->setApprovalPrompt('force');
        $this->client->addScope(Google_Service_Drive::DRIVE);
        $this->client->setRedirectUri($redirect_uri);
        if ($token) {
            try {
                $this->client->setAccessToken($token);
            } catch (InvalidArgumentException $exc) {
                throw $exc;
            }
            if ($this->client->isAccessTokenExpired()) {
                $token = $this->client->fetchAccessTokenWithRefreshToken();
                file_put_contents(getenv('token.json'), json_encode($token));
            }
            $this->service = new Google_Service_Drive($this->client);
        }
    }
    public function getAuthUrl() {
        return $this->client->createAuthUrl();
    }

    public function fetchAccessToken($code) {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        $this->service = new Google_Service_Drive($this->client);
        return $token;
    }
    public function listIn($id=null, $recursive=true) {
        $id = $this->escape($id);
        $op = $recursive ? 'in' : '=';
        $params = [];
        if ($id) {
            $params['q'] = "$id $op parents";
        }
        return $this->service->files->listFiles($params)->files;
    }
    /**
     * $path no debe empezar con /
     * El separador es /
     *
     * @param type $path
     * @return Google_Service_Drive_DriveFile
     */
    public function findPath($path) {
        $pwd = 'root';
        foreach (explode('/', $path) as $p) {
            $file = $this->findIn($p, $pwd);
            $pwd = $file->id;
        }
        return $file;
    }
    /**
     *
     * @param type $name
     * @param type $id
     * @param type $recursive
     * @return Google_Service_Drive_DriveFile
     */
    protected function findIn($name, $id=null, $recursive=true) {
        $id = $this->escape($id);
        $name = $this->escape($name);
        $op = $recursive ? 'in' : '=';
        $q = ["name = $name"];
        if ($id) {
            $q[] = "$id $op parents";
        }
        $files = $this->service->files->listFiles([
            'q' => implode(' and ', $q),
        ])->files;
        return $files[0];
    }
    public function downloadLink(Google_Service_Drive_DriveFile $file) {
        $id = $file->id;
        $token = $this->client->getAccessToken()['access_token'];
        return "https://www.googleapis.com/drive/v3/files/$id?alt=media&access_token=$token";
    }
    public function getContent(Google_Service_Drive_DriveFile $file)
    {
        return $this->getContentById($file->id);
    }
    public function getContentById($fileId)
    {
        $response = $this->service->files->get($fileId, array('alt' => 'media'));
        return ($response->getBody());
    }

    public function export(Google_Service_Drive_DriveFile $file, $mimeType = 'text/html')
    {
        /* @var $response \GuzzleHttp\Psr7\Response */
        $response = $this->service->files->export($file->id, $mimeType);
        return ($response->getBody()->__toString());
    }

    /**
     *
     * @param type $value
     * @param string $type
     */
    private function escape($value, $type = 'auto')
    {
        if ($type === 'auto') {
            $type = gettype($value);
        }
        switch ($type) {
            case 'string':
                return "'" . str_replace("'", "\\'", $value) . "'";
            case 'integer':
            case 'double':
                return $value;
            case 'boolean':
                return $value ? 'true' : 'false';
        }
    }

    /**
     *
     * @param type $path
     * @return Google_Service_Drive_DriveFile[]
     */
    public function listInPath($path)
    {
        if ($path === 'root') {
            $id = $path;
        } else {
            $gpath = $this->findPath($path);
            $id = $gpath->id;
        }
        $list = [];
        return $this->listIn($id);
    }

    /**
     *
     * @return GDrive
     */
    public static function getInstance()
    {

        $path = getenv('token.json');
        if (isset($_REQUEST['code'])) {
            $drive = new GDrive(getenv('host.url') . "/index.php", false);
            $token = $drive->fetchAccessToken($_REQUEST['code']);
            file_put_contents($path, json_encode($token));
            header('Location: ' . getenv('host.url') . "/index.php");
            return;
        } else {
            $token = self::loadJson($path);
            $drive = new GDrive(getenv('host.url') . "/index.php", $token);
        }
        return [$drive, $token];
    }

    private static function loadJson($path)
    {
        if (!file_exists($path)) {
            return false;
        }
        return json_decode(file_get_contents($path), true);
    }
}
