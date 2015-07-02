<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

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
        $output = array(
            /* Part of every type of phone number */
            'countryCode' => $countryCode,
            'subscriberNumber' => null,

            /* Number structure for geographic area */
            /* This includes the carrier prefix for mobile */
            'nationalDestinationCode' => null,

            /* When used together with the country prefix */
            'nationalDestinationCodeInternational' => null,

            /* Mobile flag */
            'isMobile' => false,

            /* Number structure for networks */
            /* Currently not used */
            //'networkIdentificationCode' => null,

            /* Number structure for groups of countries */
            /* Currently not used */
            //'groupIdentificationCode' => null,
        );

        if ($number == '') {
            return $output;
        }

        // extract the country code from the number if possible
        list($countryCode, $number) = $this->detectCountryCode($countryCode, $number);

        //cleanup the non-numeric chars 
        $number = preg_replace('/[^0-9]+/', '', $number);

        // easier to read than 65 / 60 / etc
        $regionCode = $this->getRegionCodeFromCountryCode($countryCode);

        $regionFormatter = $this->getRegionFormatter($regionCode);

        if (!$regionFormatter) {
            $output['subscriberNumber'] = $number;
            return $output;
        }

        //extract area code
        list(
            $nationalDestinationCode,
            $nationalDestinationCodeInternational,
            $number,
            $isMobile
        ) = $regionFormatter->extractNationalDestinationCode($number, $countryCode);

        $output['countryCode'] = $countryCode;
        $output['nationalDestinationCode'] = $nationalDestinationCode;
        $output['nationalDestinationCodeInternational'] = $nationalDestinationCodeInternational;
        $output['subscriberNumber'] = $number;
        $output['isMobile'] = $isMobile;

        return $output;
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
            //check only for "our" country codes
            foreach ($this->countryCodes as $regionCode => $code){
                if (strpos($number, '+'.$code) === 0){
                    $number = preg_replace('/^'.preg_quote('+'.$code, '/').'/', '', $number);
                    return array($code, $number);
                }
            }
        }

        // return specified country code
        $countryCode = $countryCode !== '' ? $countryCode : null;
        if ($countryCode !== null){
            return array($countryCode, $number);
        }

        //return request country code if none found
        if (isset($this->countryCodes[$this->regionCode])) {
            return array($this->countryCodes[$this->regionCode], $number);
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

    public function formatByDigitCount($E194)
    {
        if (!isset($E194['subscriberNumber'])){
            return null;
        }
        if (empty($E194['subscriberNumber'])) {
            return $E194['subscriberNumber'];
        }

        return (isset($E194['countryCode']) && $E194['countryCode'] != '' ? '+'.$E194['countryCode'] : '')
            . $this->formatNumberByDigits(
                $E194['nationalDestinationCodeInternational'].$E194['subscriberNumber'],
                $E194['countryCode']
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
