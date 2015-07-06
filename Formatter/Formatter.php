<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

use Guru\PhoneNumberFormatterBundle\Model\PhoneNumber;

class Formatter
{
    private $countryCodes = array();
    private $countryCodesFlipped = array();
    private $regionFormatters = array();
    private $regionCode;

    public function addRegionFormatter($regionCode, FormatterInterface $formatter)
    {
        $this->regionFormatters[$regionCode] = $formatter;
    }

    public function setCountryCodes($countryCodes = array())
    {
        $this->countryCodes = $countryCodes;
        $this->countryCodesFlipped = null;
    }

    public function setDefaultRegionCode($regionCode = '')
    {
        $this->regionCode = $regionCode;
    }

    public function numberToE164($number = '', $countryCode = null)
    {
        if ($number == '') {
            $phoneNumber = new PhoneNumber();
            $phoneNumber->setCountryCode($countryCode);
            return $phoneNumber;
        }

        // extract the country code from the number if possible
        list($countryCode, $number) = $this->detectCountryCode($countryCode, $number);

        //cleanup the non-numeric chars 
        $number = preg_replace('/[^0-9]+/', '', $number);

        // easier to read than 65 / 60 / etc
        $regionCode = $this->getRegionCodeFromCountryCode($countryCode);

        $regionFormatter = $this->getRegionFormatter($regionCode);

        if (!$regionFormatter) {
            $phoneNumber = new PhoneNumber();
            $phoneNumber->setCountryCode($countryCode);
            $phoneNumber->setSubscriberNumber($number);
            return $phoneNumber;
        }

        //extract area code
        $phoneNumber = $regionFormatter->extractNationalDestinationCode($number, $countryCode);
        if (!$phoneNumber) {
            $phoneNumber = new PhoneNumber();
            $phoneNumber->setSubscriberNumber($number);
        }
        $phoneNumber->setCountryCode($countryCode);

        return $phoneNumber;
    }

    private function getRegionFormatter($regionCode)
    {
        if (!isset($this->regionFormatters[$regionCode])) {
            return  null;
        }
        return $this->regionFormatters[$regionCode];
    }

    private function detectCountryCode($countryCode, $number)
    {
        //cleanup the non-numeric chars 
        $number = preg_replace('/[^0-9\+]/', '', $number);

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
                    return array((string)$code, $number);
                }
            }

            //if we didn't find the country code
            //remove the + / 00
            $number = $checkNumber;
        }

        // return specified country code
        $countryCode = $countryCode !== '' ? $countryCode : null;
        if ($countryCode !== null){
            return array((string)$countryCode, $number);
        }

        //return request country code if none found
        if (isset($this->countryCodes[$this->regionCode])) {
            return array((string)$this->countryCodes[$this->regionCode], $number);
        }

        return array(null, $number);
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
            return $E164->getSubscriberNumber();
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
