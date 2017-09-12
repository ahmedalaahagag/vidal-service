<?php
namespace Hagag\VidalService\Utilities;

class XmlHandler implements XmlHandlerInterface {

	/**
	 * convert valid xml format to array
	 * @param  string $xml
	 * @return array  $result
	 */
	function toArray($xml) {
		$xml_parser = xml_parser_create();
		xml_parse_into_struct($xml_parser, $xml, $vals);
		xml_parser_free($xml_parser);
		// wyznaczamy tablice z powtarzajacymi sie tagami na tym samym poziomie
		$_tmp = '';
		foreach ($vals as $xml_elem) {
			$x_tag = $xml_elem['tag'];
			$x_level = $xml_elem['level'];
			$x_type = $xml_elem['type'];
			if ($x_level != 1 && $x_type == 'close') {
				if (isset($multi_key[$x_tag][$x_level])) {
					$multi_key[$x_tag][$x_level] = 1;
				} else {
					$multi_key[$x_tag][$x_level] = 0;
				}

			}
			if ($x_level != 1 && $x_type == 'complete') {
				if ($_tmp == $x_tag) {
					$multi_key[$x_tag][$x_level] = 1;
				}

				$_tmp = $x_tag;
			}
		}

		foreach ($vals as $xml_elem) {
			$x_tag = $xml_elem['tag'];
			$x_level = $xml_elem['level'];
			$x_type = $xml_elem['type'];
			if ($x_type == 'open') {
				$level[$x_level] = $x_tag;
			}

			$start_level = 1;
			$php_stmt = '$xml_array';
			if ($x_type == 'close' && $x_level != 1) {
				$multi_key[$x_tag][$x_level]++;
			}

			while ($start_level < $x_level) {
				$php_stmt .= '[$level[' . $start_level . ']]';
				if (isset($multi_key[$level[$start_level]][$start_level]) && $multi_key[$level[$start_level]][$start_level]) {
					$php_stmt .= '[' . ($multi_key[$level[$start_level]][$start_level] - 1) . ']';
				}

				$start_level++;
			}
			$add = '';
			if (isset($multi_key[$x_tag][$x_level]) && $multi_key[$x_tag][$x_level] && ($x_type == 'open' || $x_type == 'complete')) {
				if (!isset($multi_key2[$x_tag][$x_level])) {
					$multi_key2[$x_tag][$x_level] = 0;
				} else {
					$multi_key2[$x_tag][$x_level]++;
				}

				$add = '[' . $multi_key2[$x_tag][$x_level] . ']';
			}
			if (isset($xml_elem['value']) && trim($xml_elem['value']) != '' && !array_key_exists('attributes', $xml_elem)) {
				if ($x_type == 'open') {
					$php_stmt_main = $php_stmt . '[$x_type]' . $add . '[\'content\'] = $xml_elem[\'value\'];';
				} else {
					$php_stmt_main = $php_stmt . '[$x_tag]' . $add . ' = $xml_elem[\'value\'];';
				}

				eval($php_stmt_main);
			}
			if (array_key_exists('attributes', $xml_elem)) {
				if (isset($xml_elem['value'])) {
					$php_stmt_main = $php_stmt . '[$x_tag]' . $add . '[\'content\'] = $xml_elem[\'value\'];';
					eval($php_stmt_main);
				}
				foreach ($xml_elem['attributes'] as $key => $value) {
					$php_stmt_att = $php_stmt . '[$x_tag]' . $add . '[$key] = $value;';
					eval($php_stmt_att);
				}
			}
		}
		return $xml_array;
	}

	function createPrescriptionXml($patient = [],$allergyClassesIds = [],$allergyIngredientsIds = [], $pathologiesIds = [],$medications = [],$logFile = null){
        $xmlRequest = new \SimpleXMLElement('<prescription></prescription>');
        $patientXml = $xmlRequest->addChild('patient');
        $patientXml->addChild('dateOfBirth', $patient['dateOfBirth']);
        if(key_exists('gender',$patient)){
            $patientXml->addChild('gender', $patient['gender']==null || $patient['gender']=="" ? null: strtoupper($patient['gender']));
        }
        if(key_exists('weight',$patient)) {
            $patientXml->addChild('weight', key_exists('weight', $patient) && ($patient['weight']==null || $patient['weight']=="") ? 0 : $patient['weight']);
        }
        if(key_exists('height',$patient)) {
            $patientXml->addChild('height', key_exists('height', $patient) && ($patient['height']==null || $patient['height']=="") ? 0 : $patient['height']);
        }
        if(key_exists('breastFeeding',$patient)) {
            $patientXml->addChild('breastFeeding', key_exists('breastFeeding', $patient) && ($patient['breastFeeding']==null || $patient['breastFeeding']=="")  ? 'NONE' : $patient['breastFeeding']);
        }
        if(key_exists('breastFeeding',$patient)) {
            $patientXml->addChild('breastFeeding', key_exists('creatin', $patient) && ($patient['creatin']==null || $patient['creatin']=="") ? '120' : $patient['creatin']);
        }
        if(key_exists('hepaticInsufficiency',$patient)) {
            $patientXml->addChild('hepaticInsufficiency', key_exists('hepaticInsufficiency', $patient) && ($patient['hepaticInsufficiency']==null || $patient['hepaticInsufficiency']=="") ? 'NONE' : $patient['hepaticInsufficiency']);
        }
        $allergiesXml = $patientXml->addChild('allergies');
        if($allergyClassesIds != null && !empty($allergyClassesIds)){
            foreach ($allergyClassesIds as $allergyClassesId) {
                $allergiesXml->addChild('allergy', 'vidal://allergy/'.$allergyClassesId);
            }
        }
        $moleculesXml = $patientXml->addChild('molecules');
        if($allergyIngredientsIds!=null && !empty($allergyIngredientsIds)){
            foreach ($allergyIngredientsIds as $allergyIngredientsId) {
                $moleculesXml->addChild('molecule', 'vidal://molecule/'.$allergyIngredientsId);
            }
        }
        $pathologiesXml = $patientXml->addChild('pathologies');
        if($pathologiesIds!=null && !empty($pathologiesIds)) {
            foreach ($pathologiesIds as $pathologiesId) {
                $pathologiesXml->addChild('pathology', 'vidal://cim10/' . $pathologiesId);
            }
        }
        $prescriptionLinesXml = $xmlRequest->addChild('prescription-lines');
        foreach ($medications as $medication) {
            $prescriptionLineXml = $prescriptionLinesXml->addChild('prescription-line');
            if(key_exists('id',$medication)) {
                $prescriptionLineXml->addChild('drugId',$medication['id']);
            }else{
                $prescriptionLineXml->addChild('drugId', $medication['vmp']['VIDALID']);
            }
            $prescriptionLineXml->addChild('drugType', 'COMMON_NAME_GROUP');
            if(key_exists('dose',$medication)) {
                $prescriptionLineXml->addChild('dose', $medication['dose']);
            }
            if(key_exists('unitId',$medication)) {
                $prescriptionLineXml->addChild('unitId', $medication['unitId']);
            }
            if(key_exists('duration',$medication)) {
                $prescriptionLineXml->addChild('duration', $medication['duration']);
            }
            if(key_exists('durationtype',$medication)) {
                $prescriptionLineXml->addChild('durationType', $medication['durationtype']);
            }
            if(key_exists('frequencytype',$medication)) {
                $prescriptionLineXml->addChild('frequencyType', $medication['frequencytype']);
            }
        }
            if($logFile!=null){
                $alertsFile = '/public/storage/exports/alerts/request'.$logFile.'.txt';
                file_put_contents(base_path().$alertsFile,$xmlRequest->asXML());
            }
         return $xmlRequest->asXML();
    }
}

?>
