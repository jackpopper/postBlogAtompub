<?php
require_once 'HTTP/Request.php';

class AtomPubClient {
    protected $values = array();

    public function __set($name, $value) {
        $this->values[strtolower($name)] = $value;
    }

    public function __get($name) {
        return $this->values[strtolower($name)];
    }

    public function __construct($user, $passwd) {
        $this->values['user']   = $user;
        $this->values['passwd'] = $passwd;
        $this->values['content_type'] = 'application/x.atom+xml';
    }

    public function getResorceUri() {
    }

    public function get($uri) {
        return $this->doMethod($uri, HTTP_REQUEST_METHOD_GET);
    }

    public function post($uri, $entry) {
        return $this->doMethod($uri, HTTP_REQUEST_METHOD_POST, $entry->generateAtom());
    }

    public function put($uri, $entry) {
        return $this->doMethod($uri, HTTP_REQUEST_METHOD_PUT, $entry->generateAtom());
    }

    public function delete($uri) {
        return $this->doMethod($uri, HTTP_REQUEST_METHOD_DELETE);
    }

    // WSSE認証用データの作成
    public function makeWsse() {
        $user    = $this->values['user'];
        $pass    = $this->values['passwd'];
        $created = date("Y-m-d\TH:i:s\Z");   

        $nonce = pack('H*', sha1(md5(time())));
        $pass_digest = base64_encode(pack('H*', sha1($nonce.$created.$pass)));
        $wsse = 'UsernameToken Username="'.$user.'", PasswordDigest="'.$pass_digest.'", Created="'.$created.'", Nonce="'.base64_encode($nonce).'"';

        return $wsse;
    }

    public function doMethod($url, $method, $post = null) {
        $req = new HTTP_Request();
        $req->addHeader('Accept','application/x.atom+xml, application/xml, text/xml, */*');
        $req->addHeader('Authorization', 'WSSE profile="UsernameToken"');
        $req->addHeader('X-WSSE', $this->makeWsse());
        $req->addHeader('Content-Type', $this->values['content_type']);
//        $req->addHeader('Cache-Control', 'no-cache');
        $req->setMethod($method);
        $req->setURL($url);
        if ($post) $req->addRawPostData($post);
        $req->sendRequest();
        $status_code = $req->getResponseCode();
        if ($status_code >= 400 ) {
//            echo "[ERROR] status code = $status_code\n";
            return false;
        }

        return $req->getResponseBody();
    }
}

class AtomPubEntry {
    protected $values = array();

    public function __set($name, $value) {
        $this->values[strtolower($name)] = $value;
    }

    public function __get($name) {
        return $this->values[strtolower($name)];
    }

    public function __construct($values_ary) {
        $this->values = $values_ary;
    }

    public function generateAtom() {
    }
}
