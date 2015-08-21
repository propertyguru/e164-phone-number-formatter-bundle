<?php

namespace Guru\PhoneNumberFormatterBundle\Model;

class PhoneNumber
{
    private $countryCode;
    private $nationalDestinationCode;
    private $nationalDestinationCodeInternational;
    private $subscriberNumber;
    private $isMobile = false;
    private $isValid = false;

    /* Setters */
    public function setCountryCode($countryCode = '')
    {
        $this->countryCode = $countryCode;
    }

    public function setNationalDestinationCode($nationalDestinationCode = '')
    {
        $this->nationalDestinationCode = $nationalDestinationCode;
    }

    public function setNationalDestinationCodeInternational($nationalDestinationCodeInternational = '')
    {
        $this->nationalDestinationCodeInternational = $nationalDestinationCodeInternational;
    }

    public function setSubscriberNumber($subscriberNumber = '')
    {
        $this->subscriberNumber = $subscriberNumber;
    }

    public function setIsMobile($isMobile = false)
    {
        $this->isMobile = $isMobile;
    }

    public function setIsValid($isValid = false)
    {
        $this->isValid = $isValid;
    }

    /* Getters */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    public function getNationalDestinationCode()
    {
        return $this->nationalDestinationCode;
    }

    public function getNationalDestinationCodeInternational()
    {
        return $this->nationalDestinationCodeInternational;
    }

    public function getSubscriberNumber()
    {
        return $this->subscriberNumber;
    }

    public function getIsMobile()
    {
        return $this->isMobile;
    }

    public function getIsValid()
    {
        return $this->isValid;
    }

    public function toArray()
    {
        return array(
            'countryCode' => $this->countryCode,
            'nationalDestinationCode' => $this->nationalDestinationCode,
            'nationalDestinationCodeInternational' => $this->nationalDestinationCodeInternational,
            'subscriberNumber' => $this->subscriberNumber,
            'isMobile' => $this->isMobile,
            'isValid' => $this->isValid,
        );
    }

    public static function fromArray(array $data = array())
    {
        $model = new static();
        if (isset($data['countryCode'])) {
            $model->setCountryCode($data['countryCode']);
        }
        if (isset($data['nationalDestinationCode'])) {
            $model->setNationalDestinationCode($data['nationalDestinationCode']);
        }
        if (isset($data['nationalDestinationCodeInternational'])) {
            $model->setNationalDestinationCodeInternational($data['nationalDestinationCodeInternational']);
        }
        if (isset($data['subscriberNumber'])) {
            $model->setSubscriberNumber($data['subscriberNumber']);
        }
        if (isset($data['isMobile'])) {
            $model->setIsMobile($data['isMobile']);
        }
        if (isset($data['isValid'])) {
            $model->setIsValid($data['isValid']);
        }
        return $model;
    }
}
