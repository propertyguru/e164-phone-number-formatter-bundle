<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

use Guru\PhoneNumberFormatterBundle\Model\PhoneNumber;

abstract class FormatterAbstract
{
    /**
     * Helper function
     * Matches a number that begins with one of the specified prefixes
    **/
    protected function matchNumberPrefix($prefixList = array(), $number = '', $inputShortPrefixes = false)
    {
        $matchPrefixList = $inputShortPrefixes ? array_keys($prefixList) : $prefixList;
        preg_match_all('/^('.implode('|', $matchPrefixList).')(.*)/', $number, $matches);
        if (!empty($matches[1]) && !empty($matches[2])){
            $response = new PhoneNumber();
            $response->setNationalDestinationCode(!$inputShortPrefixes ? $matches[1][0] : $prefixList[$matches[1][0]]);
            $response->setNationalDestinationCodeInternational(!$inputShortPrefixes ? ltrim($matches[1][0], '0') : $matches[1][0]);
            $response->setSubscriberNumber($matches[2][0]);
            return $response;
        }
    }

    /**
     * Helper function
     * Ensure that local prefixes start with 0 and that international prefixes don't start with 0
    **/
    protected function correctPrefixLeadingZero(PhoneNumber $phoneNumber) {
        $prefix = $phoneNumber->getNationalDestinationCode();

        //make sure that the prefix codes contain the correct number of 0's
        $phoneNumber->setNationalDestinationCode(
            strpos($prefix, '0') !== 0 ? '0'.$prefix : $prefix
        );
        $phoneNumber->setNationalDestinationCodeInternational(ltrim($prefix, '0'));
    }

    /**
     * Helper function
     * Generate prefix lists grouped by number length
    **/
    protected function addLengthPrefix(&$lengthPrefixArray, $length, $prefixKey, $prefixValue = null)
    {
        if (!isset($lengthPrefixArray[$length])) {
            $lengthPrefixArray[$length] = array();
        }

        $lengthPrefixArray[$length][$prefixKey] = $prefixValue !== null ? $prefixValue : $prefixKey;
    }

    /**
     * Default method
     * Should be overwritten if there is a country-specific logic
     * Returns a string split into groups of x,4,4
    **/
    public static function formatNumberByDigits($number = '')
    {
        $nrLen = strlen($number);
        if ($nrLen < 6){
            return $number;
        } elseif ($nrLen == 6){
            return substr($number, 0, $nrLen - 3).' '.substr($number, $nrLen - 3, 3);
        } else {
            $rev = strrev($number);
            $split = str_split($rev, 4);
            $rev = implode(" ", $split);
            return strrev($rev);
        }
    }
}