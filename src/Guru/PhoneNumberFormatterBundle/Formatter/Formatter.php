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

    public function numberToE194($number = '', $countryCode = null)
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

        if (strpos($number, '+') === 0) {
            //check only for the defined country codes
            foreach ($this->countryCodes as $regionCode => $code){
                if (strpos($number, '+'.$code) === 0){
                    $number = preg_replace('/^'.preg_quote('+'.$code, '/').'/', '', $number);
                    return array((string)$code, $number);
                }
            }

            //if we didn't find the country code
            //remove the plus
            $number = preg_replace('/^\+/', '', $number);
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

    public function formatByDigitCount(PhoneNumber $E194)
    {
        if ($E194->getSubscriberNumber() === '' || $E194->getSubscriberNumber() === null){
            return $E194->getSubscriberNumber();
        }

        return ($E194->getCountryCode() !== '' && $E194->getCountryCode() !== null ? '+'.$E194->getCountryCode() : '')
            . $this->formatNumberByDigits(
                $E194->getNationalDestinationCodeInternational().$E194->getSubscriberNumber(),
                $E194->getCountryCode()
            );
    }

    private function formatNumberByDigits($number, $countryCode)
    {
        $regionCode = $this->getRegionCodeFromCountryCode($countryCode);
        $regionFormatter = $this->getRegionFormatter($regionCode);
        if (!$regionFormatter) {
            return $number;
        }
        return $regionFormatter->formatNumberByDigits($number);
    }
}
