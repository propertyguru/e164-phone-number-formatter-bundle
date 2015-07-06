<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

use Guru\PhoneNumberFormatterBundle\Model\PhoneNumber;

class FormatterId extends FormatterAbstract
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

    public function formatNumberByDigits($number = '')
    {
        $nrLen = strlen($number);
        if ($nrLen == 6){
            return ' '.substr($number, 0, $nrLen - 3).' '.substr($number, $nrLen - 3, 3);
        } elseif ($nrLen == 7){
            return ' '.substr($number, 0, $nrLen - 4).' '.substr($number, $nrLen - 4, 4);
        } elseif ($nrLen >= 8){
            $remDigits = $nrLen - 8;
            $chunkSplit = '';
            $pastChars = 0;
            $splitChar = substr($number, 0, $nrLen - 8);
            $remLen = strlen($splitChar);
            while ($remDigits > 0) {
                $size = min($remDigits, 2);
                $chunk = substr($splitChar, $remLen - $pastChars - $size, $size);
                $chunkSplit = ' '.$chunk.$chunkSplit;
                $remDigits -= $size;
                $pastChars += $size;
            }
            return $chunkSplit.' '.substr($number, $nrLen - 8, 4).' '.substr($number, $nrLen - 4, 4);
        }
        return $number;
    }

    private function addLengthPrefix($length, $prefix)
    {
        if (!isset($this->lengthMobilePrefixes[$length])) {
            $this->lengthMobilePrefixes[$length] = array();
        }

        $this->lengthMobilePrefixes[$length][$prefix] = $prefix;
    }

    private function initMobilePrefixes()
    {
        if (!is_null($this->lengthMobilePrefixes)) {
            return;
        }
        $this->lengthMobilePrefixes = array();
        foreach ($this->mobilePrefixes as $prefix => $lengths) {
            foreach ($lengths as $length){
                $this->addLengthPrefix($length, $prefix);
                $this->addLengthPrefix($length - 1, ltrim($prefix, '0'));
            }
        }
    }
}
