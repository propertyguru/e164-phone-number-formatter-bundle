<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

use Guru\PhoneNumberFormatterBundle\Model\PhoneNumber;

class Formatter
{
    private $countryCodes = array();
    private $countryCodesFlipped = array();
    private $regionFormatters = array();
    private $regionCode;
    private $countryWeights = array();

    public function addRegionFormatter($regionCode, FormatterInterface $formatter)
    {
        $this->regionFormatters[$regionCode] = $formatter;
    }

    public function setCountryCodes(array $countryCodes = array())
    {
        $this->countryCodes = $countryCodes;
        $this->countryCodesFlipped = null;
    }

    public function setDefaultRegionCode($regionCode = '')
    {
        $this->regionCode = $regionCode;
    }

    /**
     * Set match weight based on the phone number country code
     * Defined the order in which the matching occures
     * If we match the same number with 2 countries, the "heaviest" one will be returned
     * The same thing applies not non-matched possible options
    **/
    public function setCountryCodeWeights(array $weights = array())
    {
        $this->countryWeights = $weights;
    }

    /**
     * Set match weight based on the region code
     * Same as setCountryCodeWeights, but uses region codes instead of phone country codes for keys
    **/
    public function setRegionWeights(array $weights = array())
    {
        $this->countryWeights = array();
        foreach ($weights as $regionCode => $weight) {
            if (!isset($this->countryCodes[$regionCode])) {
                continue;
            }
            $this->countryWeights[$this->countryCodes[$regionCode]] = $weight;
        }
    }

    public function numberToE164($number = '', $countryCode = null)
    {
        if ($number == '') {
            $phoneNumber = new PhoneNumber();
            $phoneNumber->setCountryCode($countryCode);
            return $phoneNumber;
        }

        // extract the country code from the number if possible
        // get all possibilities
        $possibleCountryCodes = $this->getAllPossibleCountryCodes($countryCode, $number);

        //the original match order matters also
        //if the weights are the same
        foreach ($possibleCountryCodes as $k => $numberCode) {
            $possibleCountryCodes[$k]['order'] = $k;
        }

        $foundValidNumbers = array();
        $foundInvalidNumbers = array();

        //re-order the country codes based on the suplied weights
        uasort($possibleCountryCodes, array($this, 'comparePossibleNumbers'));

        $possibleCountryCodes = array_values($possibleCountryCodes);

        if (empty($possibleCountryCodes)) {
            //return default number
            $phoneNumber = new PhoneNumber();
            $phoneNumber->setCountryCode($countryCode);
            $phoneNumber->setSubscriberNumber($number);
            return $phoneNumber;
        }
        foreach ($possibleCountryCodes as $k => $numberCode) {
            $countryCode = $numberCode['countryCode'];
            $subscriberNumber = $numberCode['subscriberNumber'];

            // cleanup the non-numeric chars 
            $subscriberNumber = preg_replace('/[^0-9]+/', '', $subscriberNumber);

            // easier to read than 65 / 60 / etc
            $regionCode = $this->getRegionCodeFromCountryCode($countryCode);

            $regionFormatter = $this->getRegionFormatter($regionCode);

            if (!$regionFormatter) {
                $phoneNumber = new PhoneNumber();
                $phoneNumber->setCountryCode($countryCode);
                $phoneNumber->setSubscriberNumber($subscriberNumber);
                $this->addListNumber($foundInvalidNumbers, $phoneNumber);
                continue;
            }

            //extract area code
            $phoneNumber = $regionFormatter->extractNationalDestinationCode($subscriberNumber, $countryCode);
            if (!$phoneNumber) {
                $phoneNumber = new PhoneNumber();
                $phoneNumber->setCountryCode($countryCode);
                $phoneNumber->setSubscriberNumber($subscriberNumber);
                $this->addListNumber($foundInvalidNumbers, $phoneNumber);
            } else {
                $phoneNumber->setCountryCode($countryCode);
                $phoneNumber->setIsValid(true);
                $this->addListNumber($foundValidNumbers, $phoneNumber);
            }
        }

        foreach ($foundValidNumbers as $countryCode => $numbers){
            foreach ($numbers as $phoneNumber) {
                return $phoneNumber;
            }
        }
        foreach ($foundInvalidNumbers as $countryCode => $numbers){
            foreach ($numbers as $phoneNumber) {
                return $phoneNumber;
            }
        }
    }

    private function comparePossibleNumbers($a, $b)
    {
        $weightA = isset($this->countryWeights[$a['countryCode']]) ? $this->countryWeights[$a['countryCode']] + 0 : 0;
        $weightB = isset($this->countryWeights[$b['countryCode']]) ? $this->countryWeights[$b['countryCode']] + 0 : 0;

        $embededPrefixCompare = $a['embededPrefix'] == $b['embededPrefix'] ? 0 : ($a['embededPrefix'] ? -1 : 1);
        $orderCompare = $a['order'] == $b['order'] ? 0 : ($a['order'] < $b['order'] ? -1 : 1);
        $weightCompare = $weightA == $weightB ? 0 : ($weightA < $weightB ? 1 : -1);

        // if the numbers contain + or 00
        if ($embededPrefixCompare) {
            return $embededPrefixCompare;
        }
        // config country weight
        if ($weightCompare){
            return $weightCompare;
        }
        // original match order
        if ($orderCompare) {
            return $orderCompare;
        }
    }

    private function addListNumber(&$list, $phoneNumber)
    {
        $countryCode = $phoneNumber->getCountryCode() ? $phoneNumber->getCountryCode() : '';
        if (!isset($list [$countryCode])) {
            $list [$countryCode] = array();
        }
        $list [$countryCode][]= $phoneNumber;
    }

    private function getRegionFormatter($regionCode)
    {
        if (!isset($this->regionFormatters[$regionCode])) {
            return  null;
        }
        return $this->regionFormatters[$regionCode];
    }

    public function getAllPossibleCountryCodes($countryCode, $number)
    {
        $countryCode = $countryCode !== '' ? $countryCode : null;

        $possibleCodes = array();

        //cleanup the non-numeric chars 
        $number = preg_replace('/[^0-9\+]/', '', $number);
        $numberWithoutCountryCode = null;

        if (strpos($number, '+') === 0 || strpos($number, '00') === 0) {
            if (strpos($number, '+') === 0){
                $checkNumber = preg_replace('/^\+/', '', $number);
            } else {
                $checkNumber = preg_replace('/^00/', '', $number);
            }
            //check only for the defined country codes
            foreach ($this->countryCodes as $regionCode => $code){
                if (strpos($checkNumber, $code) === 0){
                    $number = preg_replace('/^'.preg_quote($code, '/').'/', '', $checkNumber);
                    $possibleCodes []= array(
                        'countryCode' => (string)$code,
                        'subscriberNumber' => $number,
                        'embededPrefix' => 1,
                    );
                }
            }

            //if we didn't find the country code
            //remove the + / 00
            $numberWithoutCountryCode = $checkNumber;
            if ($countryCode !== null){
                $possibleCodes []= array(
                    'countryCode' => (string)$countryCode,
                    'subscriberNumber' => $numberWithoutCountryCode,
                    'embededPrefix' => 0,
                );
            }
        }

        // return specified country code
        if ($countryCode !== null){
            $possibleCodes []= array(
                'countryCode' => (string)$countryCode,
                'subscriberNumber' => $number,
                'embededPrefix' => 0,
            );
        }

        //return request country code if none found
        if (isset($this->countryCodes[$this->regionCode])) {
            $possibleCodes []= array(
                'countryCode' => (string)$this->countryCodes[$this->regionCode],
                'subscriberNumber' => $number,
                'embededPrefix' => 0,
            );
            if ($numberWithoutCountryCode !== null) {
                $possibleCodes []= array(
                    'countryCode' => (string)$this->countryCodes[$this->regionCode],
                    'subscriberNumber' => $numberWithoutCountryCode,
                    'embededPrefix' => 0,
                );
            }
        }

        if (empty($possibleCodes)) {
            $possibleCodes []=array(
                'countryCode' => null,
                'subscriberNumber' => $number,
                'embededPrefix' => 0
            );
        }

        //remove duplicate - if any
        $possibleCodes = array_values(array_unique($possibleCodes, SORT_REGULAR));

        return $possibleCodes;
    }

    private function getRegionCodeFromCountryCode($countryCode)
    {
        if (!$this->countryCodesFlipped) {
            $this->countryCodesFlipped = array_flip($this->countryCodes);
        }

        return isset($this->countryCodesFlipped[$countryCode]) ? $this->countryCodesFlipped[$countryCode] : null;
    }

    public function formatByDigitCount(PhoneNumber $E164)
    {
        if ($E164->getSubscriberNumber() === '' || $E164->getSubscriberNumber() === null){
            return null;
        }

        $numberString = '';
        //add country code
        if ($E164->getCountryCode() !== '' && $E164->getCountryCode() !== null) {
            $numberString .= '+'.$E164->getCountryCode();
        }

        //add region / mobile code
        if ($E164->getNationalDestinationCode() != '') {
            //see if we need local / international code
            $code = '';
            if ($E164->getCountryCode() !== '' && $E164->getCountryCode() !== null) {
                $code = $E164->getNationalDestinationCodeInternational();
            } else {
                $code = $E164->getNationalDestinationCode();
            }

            $numberString .= ($numberString != '' ? ' ' :'').$code;
        }

        $numberString .= ($numberString != '' ? ' ' :'').$this->formatNumberByDigits(
            $E164->getSubscriberNumber(),
            $E164->getCountryCode()
        );

        return $numberString;
    }

    private function formatNumberByDigits($number, $countryCode)
    {
        $regionCode = $this->getRegionCodeFromCountryCode($countryCode);
        $regionFormatter = $this->getRegionFormatter($regionCode);
        if (!$regionFormatter) {
            //use the generic formatter
            return FormatterAbstract::formatNumberByDigits($number);
        }
        return $regionFormatter->formatNumberByDigits($number);
    }
}
