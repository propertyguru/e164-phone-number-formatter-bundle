<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

use Guru\PhoneNumberFormatterBundle\Model\PhoneNumber;

class FormatterMy extends FormatterAbstract
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
            $phoneNumber = $this->matchNumberPrefix($this->shortMobileCarrierPrefix, $number, true);
            if ($phoneNumber) {
                $phoneNumber->setIsMobile(true);
                return $phoneNumber;
            }
        // with leading 0
        } elseif ($numberLen == 10) {
            $phoneNumber = $this->matchNumberPrefix($this->shortMobileCarrierPrefix, $number);
            if ($phoneNumber) {
                $phoneNumber->setIsMobile(true);
                return $phoneNumber;
            }
        // extended mobile numbers 011 prefix
        } elseif ($numberLen == 11) {
            $phoneNumber = $this->matchNumberPrefix($this->longMobileCarrierPrefix, $number);
            if ($phoneNumber) {
                $phoneNumber->setIsMobile(true);
                return $phoneNumber;
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
            $phoneNumber = $this->matchNumberPrefix($this->prefixDestinationCodesShort, $number, true);
            if ($phoneNumber) {
                $phoneNumber->setIsMobile(false);
                return $phoneNumber;
            }
        }
        //try extended 1 digit codes
        if ($tryPrefixOneDigitCodes) {
            $phoneNumber = $this->matchNumberPrefix($this->prefixDestinationCodesShort, $number);
            if ($phoneNumber) {
                $phoneNumber->setIsMobile(false);
                return $phoneNumber;
            }
        }
        //try simple 2 digit codes
        if ($trySimpleTwoDigitCodes) {
            $phoneNumber = $this->matchNumberPrefix($this->prefixDestinationCodesLong, $number, true);
            if ($phoneNumber) {
                $phoneNumber->setIsMobile(false);
                return $phoneNumber;
            }
        }
        //try extended 2 digit codes
        if ($tryPrefixTwoDigitCodes) {
            $phoneNumber = $this->matchNumberPrefix($this->prefixDestinationCodesLong, $number);
            if ($phoneNumber) {
                $phoneNumber->setIsMobile(false);
                return $phoneNumber;
            }
        }

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