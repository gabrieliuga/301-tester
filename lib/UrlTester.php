<?php
/**
 * Class UrlTester
 */
class UrlTester {

    public $results;

    private $delimiter;
    private $root_url;
    private $old_new_urls;
    private $url_dump;

    /**
     * @param $root_url
     * @param $url_dump
     * @param string $delimiter
     */
    function __construct($root_url, $url_dump, $delimiter = ' ') {
        $this->url_dump = $url_dump;

        $this->SetDelimiter($delimiter);
        $this->SetRootUrl($root_url);
        $this->ProcessUrlDump();
        $this->TestAllUrls();
    }

	/**
     * @param $delimiter
     */
    private function SetDelimiter($delimiter) {
        $this->delimiter = $delimiter;
    }

	/**
     * @param $url
     */
    private function SetRootUrl($url) {
        if(substr($url,strlen($url)-1)!='/'){
            $url.='/';
        }
        $this->root_url = 'http://'.$url;
    }

    /**
     *
     */
    private function ProcessUrlDump() {
        $urls_to_test = explode("\n", $this->url_dump);

        $this->old_new_urls = array();

        foreach ($urls_to_test as $url) {
            $tmp = explode($this->delimiter, $url);

            $tmp[0] = $this->ProcessUrlPart(trim($tmp[0]));
            $tmp[1] = $this->ProcessUrlPart(trim($tmp[1]));
            if(substr($tmp[0], 0, 1)=='/'){
                $tmp[0] = substr($tmp[0], 1);
            }
            if(substr($tmp[1], 0, 1)=='/'){
                $tmp[1] = substr($tmp[1], 1);
            }
            if($tmp[1]=='/'){
                $tmp[1]='';
            }
            $this->old_new_urls[] = array('old_url' => $tmp[0], 'new_url' => $tmp[1]);
        }
    }

    /**
     * @param $url_part
     * @return string
     */
    private function ProcessUrlPart($url_part) {
        if ($url_part[0] != '/') {
            return '/'.$url_part;
        }

        return $url_part;
    }

    /**
     *
     */
    private function TestAllUrls() {
        $this->results = array();
        $this->results['succeeded'] = array();
        $this->results['failed'] = array();

        $total = count($this->old_new_urls);
        $i = 1;
        foreach ($this->old_new_urls as $url) {

            $test = $this->TestUrl($url['old_url'], $url['new_url']);

            $percent_complete = $this->GetPercentage($i, $total);

            $this->FlushOutput('<script>
                                UpdateLoadingbar('.$percent_complete.');
                                UpdateUrl("Checking '.$this->root_url.$url['old_url'].'...");
                                </script>');

            if ($test['success']) {
                $this->results['succeeded'][] = array($url['old_url'].' '.$url['new_url']);
            } else {
                $this->results['failed'][] = array($test['error'], $url['old_url'].' '.$url['new_url'], $test['response']);
            }

            $i++;
        }

        $this->FlushOutput('<script>UpdateUrl("Finished!");</script>');
    }

    /**
     * @param $old_url
     * @param $new_url
     * @return array
     */
    private function TestUrl($old_url, $new_url) {

        $ch = curl_init($this->root_url.$old_url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLINFO_EFFECTIVE_URL, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_exec($ch);
        $response = curl_getinfo($ch);
        curl_close($ch);
        if ($response['http_code'] == 200) {

            $response['url'] = $this->removeTrailingSlashIfExists($response['url']);
            $destUrl = $this->removeTrailingSlashIfExists($this->root_url.$new_url);

            if ($response['url'] == $destUrl) {
                return array('success' => true);
            } else {
                return array('success' => false, 'response' => $response, 'error' => 'This URL does not go to its intended destination ' . $destUrl . ' but arrived at ' . $response['url']);
            }
        } else {
            return array('success' => false, 'response' => $response, 'error' => 'This URL 404s');
        }
    }

    private function removeTrailingSlashIfExists($url){
        if(substr($url, strlen($url)-1)=='/'){
            return trim(substr($url, 0, strlen($url)-1));
        }
        return trim($url);
    }

    /**
     * @param $content_to_flush
     */
    private function FlushOutput($content_to_flush) {
        echo str_repeat(' ',1024*64);

        ob_start();
        echo $content_to_flush;
        ob_end_flush();
    }

    /**
     * @param $value
     * @param $total
     * @return float
     */
    private function GetPercentage($value, $total) {
        return round(($value / $total) * 100);
    }

} 
