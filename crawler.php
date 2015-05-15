<?php

class Crawler
{
    protected $_last_fetch = null;

    public function http($url)
    {
        while (!is_null($this->_last_fetch) and (microtime(true) - $this->_last_fetch) < 0.5) {
            usleep(1000);
        }
        $this->_last_fetch = microtime(true);

        error_log("Fetch $url");
        for ($i = 0; $i < 3; $i ++) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 20);
            $ret = curl_exec($curl);
            $info = curl_getinfo($curl);
            if ($info['http_code'] == 200) {
                return $ret;
            }
        }
        file_put_contents(__DIR__ . '/error', $url . "\n", FILE_APPEND);
        return null;
    }

    public function getCountries()
    {
        return array(
            'II0' => '屏東縣',
            'IJ0' => '花蓮縣',
            'IK0' => '台東縣',
            'IL0' => '澎湖縣',
            'IB0' => '南投縣',
            'G00' => '台北市',
            'H00' => '高雄市',
            'J10' => '連江縣',
            'J20' => '金門縣',
            'I10' => '基隆市',
            'I20' => '宜蘭縣',
            'I30' => '新北市',
            'I40' => '桃園縣',
            'I50' => '新竹市',
            'I60' => '新竹縣',
            'I70' => '苗栗縣',
            'I80' => '台中市',
            'IA0' => '彰化縣',
            'IC0' => '雲林縣',
            'ID0' => '嘉義市',
            'IE0' => '嘉義縣',
            'IF0' => '台南市',
        );
    }

    public function main()
    {
        foreach ($this->getCountries() as $id => $name) {
            @mkdir(__DIR__ . '/outputs/' . $id);
            if ($id != 'H00') { continue; }
            for ($year = date('Y') - 1911; $year >= 20; $year --) {
                $failed_count = 0;
                $files = glob(__DIR__ . '/outputs/' . $id . '/' . $year . '/' . $year . '*.json');
                rsort($files);
                if (count($files) and preg_match('#(\d+)*\.json$#', $files[0], $matches)) {
                    $no = floor($matches[1] / 10000);
                } else {
                    $no = 1;
                }
                for (; ; $no ++) {
                    $l_no = sprintf("%04d", $no);
                    $target = __DIR__ . '/outputs/' . $id . '/' . $year . '/' . $year . $l_no . '.json';
                    if (file_exists($target)) {
                        $failed_count = 0;
                        continue;
                    }
                    $url = "http://cpabm.cpami.gov.tw/MobileAPPWebServices/GetLicenseImageList.do?countyId={$id}&licenseType=3&licenseYear={$year}&licenseNo={$l_no}";
                    $ret = $this->http($url);


                    if (is_null($ret) or strpos($ret, '"count":0') !== false) {
                        error_log($url . ' failed');
                        $failed_count ++;
                        if ($failed_count > 100) break;
                        continue;
                    }
                    $failed_count = 0;
                    if (!file_exists(dirname($target))) {
                        mkdir(dirname($target));
                    }
                    file_put_contents($target, $ret);
                }
            }
        }
    }
}

$c = new Crawler;
$c->main();
