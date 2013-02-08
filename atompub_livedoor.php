<?php
require_once 'atompub.php';
define('SERVICE_DOCUMENT_URI', 'http://livedoor.blogcms.jp/atom/');

class LivedoorAtomPubClient extends AtomPubClient {
    public function getResorceUri() {
        $resorce = $this->doMethod(SERVICE_DOCUMENT_URI, HTTP_REQUEST_METHOD_GET);
        $obj = simplexml_load_string($resorce);
        $resorce_uri = array();
        foreach ($obj->workspace->collection as $wc) {
            preg_match('|^http:\/\/.*\/(?P<resorce>\w+)$|', $wc->attributes()->href, $matches);
            $accept = array();
            foreach ($wc->accept as $wca) {
                $accept[] = (string)$wca;
            }
            $resorce_uri[$matches['resorce']] = array('uri'    => $matches[0],
                                                      'accept' => $accept);
        }
        return $resorce_uri;
    }

    public function imgPost($path) {
        list($width, $height, $type) = getimagesize($path);
        if (empty($width) || empty($height)) {
            return false;
        }
        switch ($type) {
        case IMAGETYPE_JPEG:
            $this->values['content_type'] = 'image/jpeg';
            break;
        case IMAGETYPE_GIF:
            $this->values['content_type'] = 'image/gif';
            break;
        case IMAGETYPE_PNG:
            $this->values['content_type'] = 'image/png';
            break;
        default:
            return false;
        }
        $fp = fopen($path, 'rb');
        $image = fread($fp, filesize($path));
        fclose($fp);
        return $this->doMethod($this->values['resorce_uri']['image']['uri'], HTTP_REQUEST_METHOD_POST, $image);
    }
}

class LivedoorAtomPubEntry extends AtomPubEntry {
    public function generateAtom() {
        $title         = $this->values['title'];
//        $published     = $this->values['published'];
        $category      = $this->values['category']; // array
//        $content       = $this->values['content'];
        $body          = $this->values['body'];
        $more          = $this->values['more'];

/*
        if (preg_match('#<.+?>#', $title)) {
            $search = array('<', '>');
            $title = str_replace($search, ' ', $title);
        }
*/
        $title = str_replace('<', '&lt;', $title);
        $title = str_replace('>', '&gt;', $title);

        $pub_xml = (!empty($published)) ? '<published>'.$published.'</published>' : '';

        $cat_xml = '';
        foreach($category as $cat) {
            $cat_xml .= '<category term="'.$cat.'" />';
        }

        $ret = <<<__XML__
<?xml version="1.0" encoding="utf-8"?>
  <entry xmlns="http://www.w3.org/2005/Atom"
    xmlns:app="http://www.w3.org/2007/app"
    xmlns:blogcms="http://blogcms.jp/-/spec/atompub/1.0/">
    <title>{$title}</title>
    {$pub_xml}
    <blogcms:source>
        <blogcms:body><![CDATA[{$body}]]></blogcms:body>
        <blogcms:more><![CDATA[{$more}]]></blogcms:more>
    </blogcms:source>
    {$cat_xml}
  </entry>
__XML__;
//echo $ret;
        return $ret;
    }
}
