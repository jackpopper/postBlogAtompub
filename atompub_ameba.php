<?php
require_once 'atompub.php';
define('SERVICE_DOCUMENT_URI',    'http://atomblog.ameba.jp/servlet/_atom/blog');

class AmebaAtomPubClient extends AtomPubClient {
    public function getResorceUri() {
        $resorce = $this->doMethod(SERVICE_DOCUMENT_URI, HTTP_REQUEST_METHOD_GET);
        $obj = simplexml_load_string($resorce);
        $resorce_uri = array();
        foreach ($obj->link as $wc) {
            preg_match('|^service\.(?P<resorce>\w+)$|', $wc->attributes()->rel, $matches);
            $resorce_uri[$matches['resorce']] = (string)$wc->attributes()->href;
        }
        return $resorce_uri;
    }

    public function makeWsse() {
        $user    = $this->values['user'];
        $pass    = $this->values['passwd'];
        $created = date("Y-m-d\TH:i:s\Z");   

        // WSSE認証用データの作成
        $nonce = sha1(md5(time()));
        $pass = strtolower(md5($pass));
        $pass_digest = base64_encode(pack('H*', sha1($nonce.$created.$pass)));
        $wsse = 'UsernameToken Username="'.$user.'", PasswordDigest="'.$pass_digest.'", Nonce="'.base64_encode($nonce).'", Created="'.$created.'"';

        return $wsse;
    }
}

class AmebaAtomPubEntry extends AtomPubEntry {
    public function generateAtom() {
        $title   = $this->values['title'];
        $content = $this->values['content'];

        $ret = <<<__XML__
<?xml version="1.0" encoding="utf-8"?>
  <feed xmlns="http://purl.org/atom/ns#" xmlns:taxo="http://purl.org/rss/1.0/modules/taxonomy/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" xmlns:dc="http://purl.org/dc/elements/1.1/" version="0.3">
    <title>{$title}</title>
    <content type="text/html">
        <![CDATA[{$content}]]>
    </content>
  </feed>
__XML__;
//echo $ret;
        return $ret;
    }
}
