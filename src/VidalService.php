<?php
namespace Hagag\VidalService;

use GuzzleHttp;
use \Hagag\VidalService\Utilities\XmlHandler as XmlHandler;

class VidalService
{
    private $appId;
    private $appKey;
    private $guzzleClient;
    private $xmlHandler;
    private $baseUrl = 'http://api-sa.vidal.fr/rest/api/';
    private $fileName;

    public function __construct($appId, $appKey)
    {
        $this->appId = $appId;
        $this->appKey = $appKey;
        $this->guzzleClient = new GuzzleHttp\Client();
        $this->xmlHandler = new XmlHandler();
        $this->fileName = time();

    }

    public function index()
    {
        echo "VIDAL API Package";
    }

    /**
     * @description :  Using green rain code to get vidal medication ID
     * @param string $greenRainCode green rain code
     * @return : Medication info array
     */
    public function getMedicationByGreenRainCode($greenRainCode = null)
    {
        try {
            if ($greenRainCode == null) {
                throw new Exception('Greencode Missing');
            }
            $operation = 'search?';
            $medication = array();
            $response = $this->guzzleClient->get($this->baseUrl . $operation . 'code=' . $greenRainCode . '&app_id=' . $this->appId . '&app_key=' . $this->appKey);
            if ($response->getStatusCode() == 200) {
                $medicationResponseTags = $this->xmlHandler->toArray($response->getBody()->getContents());
                foreach ($medicationResponseTags as $medicationResponseTag) {
                    if(count(array_keys($medicationResponseTag['ENTRY']))==2){
                        $medicationResponseTag['ENTRY'] = $medicationResponseTag['ENTRY'][0];
                    }
                    $keys = array_keys($medicationResponseTag['ENTRY']);
                    foreach ($keys as $key) {
                        if (strpos($key, 'VIDAL') !== false) {
                            $newKey = strtolower(str_replace("VIDAL:", "", $key));
                            $medication[$newKey] = $medicationResponseTag['ENTRY'][$key];
                        }
                    }
                }
                return $medication;
            } else {
                return $response->getBody()->getContents();
            }
        } catch (\Exception $e) {
            return $e;
        }
    }
    /**
     * @description :  Using vidal id to get medication units
     * @param string $vidalMedicationId vidal medication id
     * @return : Units array
     */
    public function getMedicationUnits($vidalMedicationId = null)
    {
        try {
            if ($vidalMedicationId == null) {
                throw new Exception('ID Missing');
            }
            $operation = 'vmp/';
            $units = array();
            $response = $this->guzzleClient->get($this->baseUrl . $operation . $vidalMedicationId .'/units?app_id=' . $this->appId . '&app_key=' . $this->appKey);
            if ($response->getStatusCode() == 200) {
                $unitsResponseTags = $this->xmlHandler->toArray($response->getBody()->getContents());
                foreach ($unitsResponseTags as $unitsResponseTag) {
                    $unit = array();
                    $entries = array();
                    if(isset($unitsResponseTag['ENTRY'][1])){
                        $entries = $unitsResponseTag['ENTRY'];
                    }else{
                        $entries[] = $unitsResponseTag['ENTRY'];
                    }
                    foreach ($entries as $entry) {
                        $keys = array_keys($entry);
                        foreach ($keys as $key) {
                            if (strpos($key, 'VIDAL') !== false) {
                                $newKey = strtolower(str_replace("VIDAL:", "", $key));
                                $unit[$newKey] = $entry[$key];
                            }
                        }
                        $units[] = $unit;
                    }
                }
                return $units;
            } else {
                return $response->getBody()->getContents();
            }
        } catch (\Exception $e) {
            return $e;
        }
    }
    /**
     * @description :  Using name to get vidal medication ID
     * @param string name
     * @return : Medication info array
     */
    public function getMedicationByName($name = null)
    {
        try {
            if ($name == null) {
                throw new Exception('Name Missing');
            }
            $operation = 'pathologies?';
            $medication = array();
            $response = $this->guzzleClient->get($this->baseUrl . $operation . 'q=' . $name . '&app_id=' . $this->appId . '&app_key=' . $this->appKey);
            if ($response->getStatusCode() == 200) {
                $medicationResponseTags = $this->xmlHandler->toArray($response->getBody()->getContents());
                foreach ($medicationResponseTags as $medicationResponseTag) {
                    if(count(array_keys($medicationResponseTag['ENTRY']))>1){
                        $medicationResponseTag['ENTRY'] = $medicationResponseTag['ENTRY'][0];
                    }
                    $keys = array_keys($medicationResponseTag['ENTRY']);
                    foreach ($keys as $key) {
                        if (strpos($key, 'VIDAL') !== false) {
                            $newKey = strtolower(str_replace("VIDAL:", "", $key));
                            $medication[$newKey] = $medicationResponseTag['ENTRY'][$key];
                        }
                    }
                }
                return $medication;
            } else {
                return $response->getBody()->getContents();
            }
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * @description :  get vidal allergies
     * @return : Allergies Array
     */
    public function getVidalAllergies()
    {
        try {
            $page = 1;
            $operation = 'allergies?';
            $allergies = array();
            while ($page < 34) {
                $allergy = array();
                $response = $this->guzzleClient->get($this->baseUrl . $operation . 'app_id=' . $this->appId . '&app_key=' . $this->appKey . '&start-page=' . $page . '&page-size=25');
                if ($response->getStatusCode() == 200) {
                    $allergiesResponseTags = $this->xmlHandler->toArray($response->getBody()->getContents());
                    foreach ($allergiesResponseTags as $allergiesResponseTag) {
                        foreach ($allergiesResponseTag['ENTRY'] as $entry) {
                            $keys = array_keys($entry);
                            foreach ($keys as $key) {
                                if (strpos($key, 'VIDAL') !== false) {
                                    $newKey = strtolower(str_replace("VIDAL:", "", $key));
                                    $allergy[$newKey] = $entry[$key];
                                }
                            }
                            $allergies[] = $allergy;
                        }
                    }
                } else {
                    return $response->getBody()->getContents();
                }
                $page++;
            }
            return $allergies;
        } catch (\Exception $e) {
            return $e;
        }

    }
    
    /**
     * @description :  get vidal units
     * @return : Units Array
     */
    public function getVidalUnits()
    {
        try {
            $page = 1;
            $operation = 'units?';
            $units = array();
            while ($page < 16) {
                $response = $this->guzzleClient->get($this->baseUrl . $operation . 'app_id=' . $this->appId . '&app_key=' . $this->appKey . '&start-page=' . $page . '&page-size=25');
                if ($response->getStatusCode() == 200) {
                    $unitsResponseTags = $this->xmlHandler->toArray($response->getBody()->getContents());
                    foreach ($unitsResponseTags as $unitsResponseTag) {
                        foreach ($unitsResponseTag['ENTRY'] as $entry) {
                            $keys = array_keys($entry);
                            foreach ($keys as $key) {
                                if (strpos($key, 'VIDAL') !== false) {
                                    $newKey = strtolower(str_replace("VIDAL:", "", $key));
                                    $unit[$newKey] = $entry[$key];
                                }
                            }
                            $units[] = $unit;
                        }
                    }
                } else {
                    return $response->getBody()->getContents();
                }
                $page++;
            }
            return $units;
        } catch (\Exception $e) {
            return $e;
        }

    }
    /**
     * @description :  get vidal alerts by ingredients names
     * @param : array : patient['date_of_birth'] required ,
     * patient['gender'] optional - Possible values:'MALE', 'FEMALE', 'UNKNOWN',
     * patient['weight'] optional default is 0,
     * patient['height'] optional default is 0,
     * patient['breastFeeding'] optional - Possible values:'NONE', 'LESS_THAN_ONE_MONTH', 'MORE_THAN_ONE_MONTH', 'ALL',
     * patient['creatin'] optional - Normal creatinine clearance is 120 ,
     * patient['hepaticInsufficiency'] optional - Possible values:'NONE', 'MODERATE', 'SEVERE',
     * @param :array : allergyClasses : names of Allergy classes by class
     * @param :array : allergyIngredients : names of Allergy classes by Ingredients
     * @param :array : pathologies : ICD10 Codes for pathologies
     * @param :array : medications : Greed Rain Codes of Vidal
     * @return : Alerts Array
     */
    public function getPatientAlerts($patient = [], $allergyClasses = [], $allergyIngredients = [], $pathologies = [], $medications = [])
    {
        try {
            if (empty($patient) && !key_exists('date_of_birth', $patient) || empty($medications)) {
                throw new \Exception('Parameters Missing (patient profile at least date_of_birth Or medications)');
            }
            $operation = 'alerts?';
            $patient['dateOfBirth'] = new \DateTime($patient['date_of_birth']);
            $patient['dateOfBirth'] = $patient['dateOfBirth']->format('Y-m-d');
            $allergyClassesIds = [];
            $allergyIngredientsIds = [];
            $pathologiesIds = [];
            $prescriptionMedication = [];
            foreach ($allergyClasses as $allergyClass) {
                $allergyClass = $this->getAllergyByClassOrIngredients($allergyClass);
                $allergyClassesIds[] = $allergyClass['id'];
            }
            foreach ($allergyIngredients as $allergyIngredient) {
                $allergyIngredient = $this->getAllergyByClassOrIngredients($allergyIngredient);
                $allergyIngredientsIds[] = $allergyIngredient['id'];
            }
            foreach ($pathologies as $pathology) {
                $pathology = $this->getPathologyByICD10Code($pathology);
                $pathologiesIds[] = $pathology['id'];
            }
            foreach ($medications as $medication) {
                $medicationInfo = $this->getMedicationByGreenRainCode($medication);
                $prescriptionMedication[] = $medicationInfo;
            }
            $xmlPrescription = $this->xmlHandler->createPrescriptionXml($patient, $allergyClassesIds, $allergyIngredientsIds, $pathologiesIds, $prescriptionMedication);
            $response = $this->guzzleClient->post(
                $this->baseUrl . $operation . 'app_id=' . $this->appId . '&app_key=' . $this->appKey,
                [
                    'headers' => ['Content-Type' => 'text/xml'],
                    'body' => $xmlPrescription
                ]
            );
            if ($response->getStatusCode() == 200) {
                return ($this->formatAlertResponse($this->xmlHandler->toArray($response->getBody()->getContents())));
            } else {
                throw new \Exception('Unknown Error Occurred');
            }
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * @description :  get vidal alerts by ingredients ids
     * @param : array : patient['date_of_birth'] required ,
     * patient['gender'] optional - Possible values:'MALE', 'FEMALE', 'UNKNOWN',
     * patient['weight'] optional default is 0,
     * patient['height'] optional default is 0,
     * patient['breastFeeding'] optional - Possible values:'NONE', 'LESS_THAN_ONE_MONTH', 'MORE_THAN_ONE_MONTH', 'ALL',
     * patient['creatin'] optional - Normal creatinine clearance is 120 ,
     * patient['hepaticInsufficiency'] optional - Possible values:'NONE', 'MODERATE', 'SEVERE',
     * @param :array : $allergyIds : IDs of allergis ids in vidal
     * @param :array : $pathologiesIds : IDs of pathologies ids in vidal
     * @param :array : medications : medication of array info [id-dose-unitId-duration-durationtype-frequencytype]
     * durationtype : Possible values:'MINUTE', 'HOUR', 'DAY', 'WEEK', 'MONTH', 'YEAR'
     * frequencytype : Possible values: 'THIS_DAY', 'PER_DAY', 'PER_24_HOURS', 'PER_WEEK', 'PER_MONTH','PER_YEAR', 'PER_2_DAYS', 'PER_HOUR', 'PER_MINUTE'
     * @return : Alerts Array
     */

    public function getPatientAlertsByIds($patient = [], $allergyIds = [], $pathologiesIds = [], $medications = [])
    {
        try {
            if (empty($patient) && (!key_exists('date_of_birth', $patient) || empty($medications)) ) {
                throw new \Exception('Parameters Missing (patient profile at least date_of_birth Or medications)');
            }
            $operation = 'alerts?';
            $patient['dateOfBirth'] = new \DateTime($patient['date_of_birth']);
            $patient['dateOfBirth'] = $patient['dateOfBirth']->format('Y-m-d');
            $xmlPrescription = $this->xmlHandler->createPrescriptionXml($patient, $allergyIds, [], $pathologiesIds, $medications,$this->fileName);
            $response = $this->guzzleClient->post(
                $this->baseUrl . $operation . 'app_id=' . $this->appId . '&app_key=' . $this->appKey,
                [
                    'headers' => ['Content-Type' => 'text/xml'],
                    'body' => $xmlPrescription
                ]
            );
            if ($response->getStatusCode() == 200) {
                $rawResponse = $response->getBody()->getContents();
                $formatedResponse  = $this->formatAlertResponse($this->xmlHandler->toArray($rawResponse));
                $alertsFile = '/public/storage/exports/alerts/response'.$this->fileName.'.txt';
                file_put_contents(base_path().$alertsFile,$rawResponse);
                return $formatedResponse;
            } else {
                throw new \Exception('Unknown Error Occurred');
            }
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * @description :  format alert response
     * @param : array : alert array response of vidal API
     * @return : alert info array
     */
    private function formatAlertResponse($alert)
    {
        $alerts = $alert['FEED']['ENTRY'];
        $formattedAlerts = [];
        if(isset($alerts[0])){
            unset($alerts[0]);
            unset($alerts[count($alerts)]);
            foreach ($alerts as $alert) {
                $formattedAlert['alert'] = $alert['VIDAL:TYPE'];
                if(array_key_exists('VIDAL:ALERTTYPE',$alert)){
                    $formattedAlert['alertType'] = $alert['VIDAL:ALERTTYPE']['content'];
                }else{
                    $formattedAlert['alertType'] =  $alert['VIDAL:TYPE'];
                }
                if(array_key_exists('VIDAL:SEVERITY',$alert)){
                    $formattedAlert['alertSeverity'] = $alert['VIDAL:SEVERITY'];
                }else{
                    $formattedAlert['alertSeverity'] = 'INFO';
                }
                if(array_key_exists('CONTENT',$alert)) {
                    $formattedAlert['alertContent'] = strip_tags($alert['CONTENT']['content']);
                }else{
                    $formattedAlert['alertContent'] = strip_tags($alert['TITLE']);
                }
                $formattedAlert['alertTitle'] = $alert['TITLE'];
                $formattedAlert['alertCategory'] = $alert['VIDAL:CATEGORIES'];
                $formattedAlerts[] = $formattedAlert;
            }
        }       
        return $formattedAlerts;
    }

    /**
     * @description :  get vidal medication info by id
     * @param : string : id
     * @return : Medication info array
     */
    public function getMedicationById($id = null)
    {
        try {
            if ($id == null) {
                throw new Exception('id Missing');
            }
            $operation = 'package/';
            $medication = array();
            $response = $this->guzzleClient->get($this->baseUrl . $operation . $id . '?app_id=' . $this->appId . '&app_key=' . $this->appKey);
            if ($response->getStatusCode() == 200) {
                $medicationResponseTags = $this->xmlHandler->toArray($response->getBody()->getContents());
                foreach ($medicationResponseTags as $medicationResponseTag) {
                    $keys = array_keys($medicationResponseTag['ENTRY']);
                    foreach ($keys as $key) {
                        if (strpos($key, 'VIDAL') !== false) {
                            $newKey = strtolower(str_replace("VIDAL:", "", $key));
                            $medication[$newKey] = $medicationResponseTag['ENTRY'][$key];
                        }
                    }
                }
                return $medication;
            } else {
                return $response->getBody()->getContents();
            }
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * @description :  get vidal medication info by ICD10Code
     * @param : string : id
     * @return : Pathologies info array
     */
    public function getPathologyByICD10Code($icd10Code = null)
    {
        try {
            if ($icd10Code == null) {
                throw new Exception('icd10Code Missing');
            }
            $operation = 'pathologies?';
            $pathology = array();
            $response = $this->guzzleClient->get($this->baseUrl . $operation . 'app_id=' . $this->appId . '&app_key=' . $this->appKey . '&filter=CIM10&code=' . $icd10Code);
            if ($response->getStatusCode() == 200) {
                $pathologyResponseTags = $this->xmlHandler->toArray($response->getBody()->getContents());
                foreach ($pathologyResponseTags as $pathologyResponseTag) {
                    $keys = array_keys($pathologyResponseTag['ENTRY']);
                    foreach ($keys as $key) {
                        if (strpos($key, 'VIDAL') !== false) {
                            $newKey = strtolower(str_replace("VIDAL:", "", $key));
                            $pathology[$newKey] = $pathologyResponseTag['ENTRY'][$key];
                        }
                    }
                }
                return $pathology;
            } else {
                return $response->getBody()->getContents();
            }
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * @description :  get vidal medication info by name
     * @param : string : id
     * @return : Pathologies info array
     */
    public function getPathologyByName($name = null)
    {
        try {
            if ($name  == null) {
                throw new Exception('name is Missing');
            }
            $operation = 'pathologies?';
            $pathology = array();
            $response = $this->guzzleClient->get($this->baseUrl . $operation . 'app_id=' . $this->appId . '&app_key=' . $this->appKey . '&q=' . $name);
            if ($response->getStatusCode() == 200) {
                $pathologyResponseTags = $this->xmlHandler->toArray($response->getBody()->getContents());
                foreach ($pathologyResponseTags as $pathologyResponseTag) {
                    $keys = array_keys($pathologyResponseTag['ENTRY']);
                    foreach ($keys as $key) {
                        if (strpos($key, 'VIDAL') !== false) {
                            $newKey = strtolower(str_replace("VIDAL:", "", $key));
                            $pathology[$newKey] = $pathologyResponseTag['ENTRY'][$key];
                        }
                    }
                }
              return $pathology;
            } else {
                return $response->getBody()->getContents();
            }
        } catch (\Exception $e) {
            return $e;
        }
    }
    /**
     * @description :  Using allergy class or ingredients code to get vidal allergy
     * @param : string : allergy class
     * @return : Allergy info array
     */
    public function getAllergyByClassOrIngredients($allergyClassIngredients = null)
    {
        try {
            if ($allergyClassIngredients == null) {
                throw new Exception('Allergy Class Missing');
            }
            $operation = 'allergies?';
            $allergy = array();
            $response = $this->guzzleClient->get($this->baseUrl . $operation . 'q=' . $allergyClassIngredients . '&app_id=' . $this->appId . '&app_key=' . $this->appKey);
            if ($response->getStatusCode() == 200) {
                $allergyResponseTags = $this->xmlHandler->toArray($response->getBody()->getContents());
                foreach ($allergyResponseTags as $allergyResponseTag) {
                    $keys = array_keys($allergyResponseTag['ENTRY']);
                    foreach ($keys as $key) {
                        if (strpos($key, 'VIDAL') !== false) {
                            $newKey = strtolower(str_replace("VIDAL:", "", $key));
                            $allergy[$newKey] = $allergyResponseTag['ENTRY'][$key];
                        }
                    }
                }
                return $allergy;
            } else {
                return $response->getBody()->getContents();
            }
        } catch (\Exception $e) {
            return $e;
        }
    }
}
