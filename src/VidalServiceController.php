<?php
namespace Hagag\VidalService;

use App\Http\Controllers\Controller;
use GuzzleHttp;
use XmlParser;

class VidalServiceController extends Controller
{
    private $appId;
    private $appKey;
    private $guzzleClient;
    private $baseUrl = 'http://api-sa.vidal.fr/rest/api/';
    public function register()
    {
        include __DIR__.'/routes.php';
        $this->app->make('Hagag\VidalService\VidalServiceController');
    }

    public function __construct($appId,$appKey)
    {
      $this->appId = $appId;
      $this->appKey= $appKey;
      $this->guzzleClient = new GuzzleHttp\Client();
    }

    public function index()
    {
        echo "VIDAL API Package";
    }

    /**
     * @description :  Using green rain code to get vidal medication ID
     * @param : string : green rain code
     * @return : Medication info array
     */
    public function getMedicationByGreenRainCode($greenRainCode=null){
        try{
            if($greenRainCode==null){
                throw new Exception('Greencode Missing');
            }
        $operation = 'search?';
        $medication =  array();
        $response = $this->guzzleClient->get($this->baseUrl.$operation.'code='.$greenRainCode.'&app_id='.$this->appId.'&app_key='.$this->appKey);
        if($response->getStatusCode() == 200){
            $medicationResponseTags = $this->XMLtoArray($response->getBody()->getContents());
            foreach ($medicationResponseTags as $medicationResponseTag){
              $keys = array_keys($medicationResponseTag['ENTRY']);
              foreach ($keys as $key){
                  if(strpos($key,'VIDAL')!==false){
                      $newKey = strtolower(str_replace("VIDAL:","",$key));
                      $medication[$newKey]=$medicationResponseTag['ENTRY'][$key];
                  }
              }
            }
            return $medication;
        }else{
            return $response->getBody();
        }
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    private function XMLtoArray($XML)
    {
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $XML, $vals);
        xml_parser_free($xml_parser);
        // wyznaczamy tablice z powtarzajacymi sie tagami na tym samym poziomie
        $_tmp='';
        foreach ($vals as $xml_elem) {
            $x_tag=$xml_elem['tag'];
            $x_level=$xml_elem['level'];
            $x_type=$xml_elem['type'];
            if ($x_level!=1 && $x_type == 'close') {
                if (isset($multi_key[$x_tag][$x_level]))
                    $multi_key[$x_tag][$x_level]=1;
                else
                    $multi_key[$x_tag][$x_level]=0;
            }
            if ($x_level!=1 && $x_type == 'complete') {
                if ($_tmp==$x_tag)
                    $multi_key[$x_tag][$x_level]=1;
                $_tmp=$x_tag;
            }
        }
        // jedziemy po tablicy
        foreach ($vals as $xml_elem) {
            $x_tag=$xml_elem['tag'];
            $x_level=$xml_elem['level'];
            $x_type=$xml_elem['type'];
            if ($x_type == 'open')
                $level[$x_level] = $x_tag;
            $start_level = 1;
            $php_stmt = '$xml_array';
            if ($x_type=='close' && $x_level!=1)
                $multi_key[$x_tag][$x_level]++;
            while ($start_level < $x_level) {
                $php_stmt .= '[$level['.$start_level.']]';
                if (isset($multi_key[$level[$start_level]][$start_level]) && $multi_key[$level[$start_level]][$start_level])
                    $php_stmt .= '['.($multi_key[$level[$start_level]][$start_level]-1).']';
                $start_level++;
            }
            $add='';
            if (isset($multi_key[$x_tag][$x_level]) && $multi_key[$x_tag][$x_level] && ($x_type=='open' || $x_type=='complete')) {
                if (!isset($multi_key2[$x_tag][$x_level]))
                    $multi_key2[$x_tag][$x_level]=0;
                else
                    $multi_key2[$x_tag][$x_level]++;
                $add='['.$multi_key2[$x_tag][$x_level].']';
            }
            if (isset($xml_elem['value']) && trim($xml_elem['value'])!='' && !array_key_exists('attributes', $xml_elem)) {
                if ($x_type == 'open')
                    $php_stmt_main=$php_stmt.'[$x_type]'.$add.'[\'content\'] = $xml_elem[\'value\'];';
                else
                    $php_stmt_main=$php_stmt.'[$x_tag]'.$add.' = $xml_elem[\'value\'];';
                eval($php_stmt_main);
            }
            if (array_key_exists('attributes', $xml_elem)) {
                if (isset($xml_elem['value'])) {
                    $php_stmt_main=$php_stmt.'[$x_tag]'.$add.'[\'content\'] = $xml_elem[\'value\'];';
                    eval($php_stmt_main);
                }
                foreach ($xml_elem['attributes'] as $key=>$value) {
                    $php_stmt_att=$php_stmt.'[$x_tag]'.$add.'[$key] = $value;';
                    eval($php_stmt_att);
                }
            }
        }
        return $xml_array;
    }
}