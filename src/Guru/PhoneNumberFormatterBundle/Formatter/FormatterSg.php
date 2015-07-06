<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

use Guru\PhoneNumberFormatterBundle\Model\PhoneNumber;

class FormatterSg extends FormatterAbstract implements FormatterInterface
{
    //generic length
    private $numberLength;

    // landlines + voip
    private $landlinePrefix;

    // voip
    private $voipPrefix;

    //mobile
    private $mobileRules;

    public function setNumberLength($length = 0)
    {
        $this->numberLength = $length;
    }

    public function setLandlinePrefix($prefix = '')
    {
        $this->landlinePrefix = $prefix;
    }

    public function setVoipPrefix($prefix = '')
    {
        $this->voipPrefix = $prefix;
    }

    public function setMobileRules($rules = array())
    {
        $this->mobileRules = $rules;
    }

    public function extractNationalDestinationCode($number = '', $countryCode = null)
    {
        // all numbers have the same length
        if (strlen($number) != $this->numberLength) {
            return;
        }

        //landlines + voip
        if (strpos($number, $this->landlinePrefix) === 0 || strpos($number, $this->voipPrefix) === 0) {
            $phoneNumber = new PhoneNumber();
            $phoneNumber->setSubscriberNumber($number);
            $phoneNumber->setIsMobile(false);
            return $phoneNumber;
        }

        //mobile
        foreach ($this->mobileRules as $rule){
            if (preg_match('/^'.$rule.'.*$/', $number)) {
                $phoneNumber = new PhoneNumber();
                $phoneNumber->setSubscriberNumber($number);
                $phoneNumber->setIsMobile(true);
                return $phoneNumber;
            }
        }
    }
}