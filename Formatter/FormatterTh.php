<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

use Guru\PhoneNumberFormatterBundle\Model\PhoneNumber;

class FormatterTh extends FormatterAbstract implements FormatterInterface
{
    // landlines
    private $landlinePrefixes = array();
    private $landlineNumberLength = 0;
    private $lengthLandlinePrefixes;

    //mobile
    private $mobilePrefixes = array();
    private $mobileNumberLength = 0;
    private $lengthMobilePrefixes;

    public function setLandlineNumberLength($length = 0)
    {
        $this->landlineNumberLength = $length;
    }

    public function setLandlinePrefixCodes($codes = array())
    {
        $this->landlinePrefixes = $codes;
    }

    public function setMobilePrefixCodes($codes = array())
    {
        $this->mobilePrefixes = $codes;
    }

    public function setMobileNumberLength($length = 0)
    {
        $this->mobileNumberLength = $length;
    }

    public function extractNationalDestinationCode($number = '', $countryCode = null)
    {
        $this->initPrefixes();

        $numberLen = strlen($number);

        //check if mobile
        if (isset($this->lengthMobilePrefixes[$numberLen])) {
            $phoneNumber = $this->matchNumberPrefix($this->lengthMobilePrefixes[$numberLen], $number, true);
            if ($phoneNumber) {
                $this->correctPrefixLeadingZero($phoneNumber);
                $phoneNumber->setIsMobile(true);
                return $phoneNumber;
            }
        }

        //check if landline
        if (isset($this->lengthLandlinePrefixes[$numberLen])) {
            $phoneNumber = $this->matchNumberPrefix($this->lengthLandlinePrefixes[$numberLen], $number, true);
            if ($phoneNumber) {
                $this->correctPrefixLeadingZero($phoneNumber);
                $phoneNumber->setIsMobile(false);
                return $phoneNumber;
            }
        }
    }

    private function initPrefixes()
    {
        if (is_null($this->lengthMobilePrefixes)) {
            $this->lengthMobilePrefixes = array();
            foreach ($this->mobilePrefixes as $shortPrefix => $longPrefix) {
                $this->addLengthPrefix($this->lengthMobilePrefixes, $this->mobileNumberLength - 1, $shortPrefix, $longPrefix);
                $this->addLengthPrefix($this->lengthMobilePrefixes, $this->mobileNumberLength, $longPrefix);
            }
        }
        if (is_null($this->lengthLandlinePrefixes)) {
            $this->lengthLandlinePrefixes = array();
            foreach ($this->landlinePrefixes as $shortPrefix => $longPrefix) {
                $this->addLengthPrefix($this->lengthLandlinePrefixes, $this->landlineNumberLength - 1, $shortPrefix, $longPrefix);
                $this->addLengthPrefix($this->lengthLandlinePrefixes, $this->landlineNumberLength, $longPrefix);
            }
        }
    }
}