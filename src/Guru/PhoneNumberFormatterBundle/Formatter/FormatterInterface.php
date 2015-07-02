<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

interface FormatterInterface
{
    public function extractNationalDestinationCode($number = '', $countryCode = null);
    public function formatNumberByDigits($number = '');
}