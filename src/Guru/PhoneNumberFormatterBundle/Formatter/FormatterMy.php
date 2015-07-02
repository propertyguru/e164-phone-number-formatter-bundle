<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

class FormatterMy implements FormatterInterface
{
    // landlines
    private $prefixDestinationCodesShort = array();
    private $prefixDestinationCodesLong = array();

    //mobile
    //7 digit mobile
    private $shortMobileCarrierPrefix = array();
    // 8 digit mobile
    private $longMobileCarrierPrefix = array();

    public function setLandlinePrefixCodesShort($codes = array())
    {
        $this->prefixDestinationCodesShort = $codes;
    }

    public function setLandlinePrefixCodesLong($codes = array())
    {
        $this->prefixDestinationCodesLong = $codes;
    }

    public function setMobilePrefixCodesShort($codes = array())
    {
        $this->shortMobileCarrierPrefix = $codes;
    }

    public function setMobilePrefixCodesLong($codes = array())
    {
        $this->longMobileCarrierPrefix = $codes;
    }

    public function extractNationalDestinationCode($number = '', $countryCode = null)
    {
        $numberLen = strlen($number);

        //check if mobile
        // in case the prefix does not contain the leading 0
        if ($numberLen == 9) {
            $matches = array();
            preg_match_all('/^('.implode('|', array_keys($this->shortMobileCarrierPrefix)).')(.*)/', $number, $matches);
            if (!empty($matches[1]) && !empty($matches[2])){
                return array($this->shortMobileCarrierPrefix[$matches[1][0]], $matches[1][0], $matches[2][0]);
            }
        // with leading 0
        } elseif ($numberLen == 10) {
            $matches = array();
            preg_match_all('/^('.implode('|', $this->shortMobileCarrierPrefix).')(.*)/', $number, $matches);
            if (!empty($matches[1]) && !empty($matches[2])){
                return array($matches[1][0], ltrim($matches[1][0], '0'), $matches[2][0]);
            }
        // extended mobile numbers 011 prefix
        } elseif ($numberLen == 11) {
            $matches = array();
            preg_match_all('/^('.implode('|', $this->longMobileCarrierPrefix).')(.*)/', $number, $matches);
            if (!empty($matches[1]) && !empty($matches[2])){
                return array($matches[1][0], ltrim($matches[1][0], '0'), $matches[2][0]);
            }
        }

        //check if landline
        // shortest prefix without leading 0 => longest prefix with leading 0
        // base numbers are 6-8
        // area codes are 1-3
        $trySimpleOneDigitCodes = $numberLen >= 7 && $numberLen <= 9;
        $tryPrefixOneDigitCodes = $numberLen >= 8 && $numberLen <= 10;
        $trySimpleTwoDigitCodes = $numberLen >= 8 && $numberLen <= 10;
        $tryPrefixTwoDigitCodes = $numberLen >= 9 && $numberLen <= 11;

        //try extended 1 digit simple codes
        if ($trySimpleOneDigitCodes) {
            $matches = array();
            preg_match_all('/^('.implode('|', array_keys($this->prefixDestinationCodesShort)).')(.*)/', $number, $matches);
            if (!empty($matches[1]) && !empty($matches[2])){
                return array($this->prefixDestinationCodesShort[$matches[1][0]], $matches[1][0], $matches[2][0]);
            }
        }
        //try extended 1 digit codes
        if ($tryPrefixOneDigitCodes) {
            $matches = array();
            preg_match_all('/^('.implode('|', $this->prefixDestinationCodesShort).')(.*)/', $number, $matches);
            if (!empty($matches[1]) && !empty($matches[2])){
                return array($matches[1][0], ltrim($matches[1][0], '0'), $matches[2][0]);
            }
        }
        //try simple 2 digit codes
        if ($trySimpleTwoDigitCodes) {
            $matches = array();
            preg_match_all('/^('.implode('|', array_keys($this->prefixDestinationCodesLong)).')(.*)/', $number, $matches);
            if (!empty($matches[1]) && !empty($matches[2])){
                return array($this->prefixDestinationCodesLong[$matches[1][0]], $matches[1][0], $matches[2][0]);
            }
        }
        //try extended 2 digit codes
        if ($tryPrefixTwoDigitCodes) {
            $matches = array();
            preg_match_all('/^('.implode('|', $this->prefixDestinationCodesLong).')(.*)/', $number, $matches);
            if (!empty($matches[1]) && !empty($matches[2])){
                return array($matches[1][0], ltrim($matches[1][0], '0'), $matches[2][0]);
            }
        }
        return array(null, null, $number);
    }

    public function formatNumberByDigits($number = '')
    {
        $nrLen = strlen($number);
        if ($nrLen >= 7 && $nrLen <= 9){
            return substr($number, 0, $nrLen - 7).' '.substr($number, $nrLen - 7, 3).' '.substr($number, $nrLen - 4, 4);
        } elseif ($nrLen >= 10){
            return substr($number, 0, $nrLen - 8).' '.substr($number, $nrLen - 8, 4).' '.substr($number, $nrLen - 4, 4);
        }
        return $number;
    }
}