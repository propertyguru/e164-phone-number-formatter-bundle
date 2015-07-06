<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

use Guru\PhoneNumberFormatterBundle\Model\PhoneNumber;

class FormatterId extends FormatterAbstract implements FormatterInterface
{
    private $lengthMobilePrefixes;

    private $landlineLengths = array();
    private $landlinePrefixesShort = array();
    private $landlinePrefixesLong = array();
    private $mobilePrefixes = array();

    public function setLandlineLengths($lengths = array())
    {
        $this->landlineLengths = $lengths;
    }

    public function setLandlinePrefixCodesShort($codes = array())
    {
        $this->landlinePrefixesShort = $codes;
    }

    public function setLandlinePrefixCodesLong($codes = array())
    {
        $this->landlinePrefixesLong = $codes;
    }

    public function setMobileCodes($codes = array())
    {
        $this->mobilePrefixes = $codes;
    }

    public function extractNationalDestinationCode($number = '', $countryCode = null)
    {
        $this->initMobilePrefixes();

        $numberLen = strlen($number);

        //check if mobile
        if (isset($this->lengthMobilePrefixes[$numberLen])){
            $phoneNumber = $this->matchNumberPrefix($this->lengthMobilePrefixes[$numberLen], $number, true);
            if ($phoneNumber) {
                $phoneNumber->setIsMobile(true);
                return $phoneNumber;
            }
        }

        //check if landline
        //first short
        $lengthShort = 0;
        $lengthPrefixShort = 0;
        foreach ($this->landlinePrefixesShort as $short => $prefixShort){
            $lengthShort = strlen($short);
            $lengthPrefixShort = strlen($prefixShort);
            break;
        }

        //first long
        $lengthLong = 0;
        $lengthPrefixLong = 0;
        foreach ($this->landlinePrefixesLong as $long => $prefixLong){
            $lengthLong = strlen($long);
            $lengthPrefixLong = strlen($prefixLong);
            break;
        }

        foreach ($this->landlineLengths as $length) {
            //short
            if ($numberLen == $length + $lengthShort){
                $phoneNumber = $this->matchNumberPrefix($this->landlinePrefixesShort, $number, true);
                if ($phoneNumber) {
                    $phoneNumber->setIsMobile(false);
                    return $phoneNumber;
                }
            }
            if ($numberLen == $length + $lengthPrefixShort){
                $phoneNumber = $this->matchNumberPrefix($this->landlinePrefixesShort, $number);
                if ($phoneNumber) {
                    $phoneNumber->setIsMobile(false);
                    return $phoneNumber;
                }
            }

            //long
            if ($numberLen == $length + $lengthLong){
                $phoneNumber = $this->matchNumberPrefix($this->landlinePrefixesLong, $number, true);
                if ($phoneNumber) {
                    $phoneNumber->setIsMobile(false);
                    return $phoneNumber;
                }
            }
            if ($numberLen == $length + $lengthPrefixLong){
                $phoneNumber = $this->matchNumberPrefix($this->landlinePrefixesLong, $number);
                if ($phoneNumber) {
                    $phoneNumber->setIsMobile(false);
                    return $phoneNumber;
                }
            }
        }
    }

    private function initMobilePrefixes()
    {
        if (!is_null($this->lengthMobilePrefixes)) {
            return;
        }
        $this->lengthMobilePrefixes = array();
        foreach ($this->mobilePrefixes as $prefix => $lengths) {
            foreach ($lengths as $length){
                $this->addLengthPrefix($this->lengthMobilePrefixes, $length, $prefix);
                $this->addLengthPrefix($this->lengthMobilePrefixes, $length - 1, ltrim($prefix, '0'), $prefix);
            }
        }
    }
}
