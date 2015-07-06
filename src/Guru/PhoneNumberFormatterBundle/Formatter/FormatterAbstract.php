<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

use Guru\PhoneNumberFormatterBundle\Model\PhoneNumber;

abstract class FormatterAbstract
{
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
}