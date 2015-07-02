<?php

namespace Guru\PhoneNumberFormatterBundle\Formatter;

class FormatterId implements FormatterInterface
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
            $matches = array();
            preg_match_all('/^('.implode('|', array_keys($this->lengthMobilePrefixes[$numberLen])).')(.*)/', $number, $matches);
            if (!empty($matches[1]) && !empty($matches[2])){
                $actualCode = strpos($matches[1][0], '0') === 0 ? $matches[1][0] : '0'.$matches[1][0]; 
                return array(
                    $actualCode,
                    ltrim($matches[1][0], '0'),
                    $matches[2][0],
                    true
                );
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
                $matches = array();
                preg_match_all('/^('.implode('|', array_keys($this->landlinePrefixesShort)).')(.*)/', $number, $matches);
                if (!empty($matches[1]) && !empty($matches[2])){
                    return array(
                        $this->landlinePrefixesShort[$matches[1][0]],
                        $matches[1][0],
                        $matches[2][0],
                        false
                    );
                }
            }
            if ($numberLen == $length + $lengthPrefixShort){
                $matches = array();
                preg_match_all('/^('.implode('|', $this->landlinePrefixesShort).')(.*)/', $number, $matches);
                if (!empty($matches[1]) && !empty($matches[2])){
                    return array(
                        $matches[1][0],
                        ltrim($matches[1][0], '0'),
                        $matches[2][0],
                        false
                    );
                }
            }

            //long
            if ($numberLen == $length + $lengthLong){
                $matches = array();
                preg_match_all('/^('.implode('|', array_keys($this->landlinePrefixesLong)).')(.*)/', $number, $matches);
                if (!empty($matches[1]) && !empty($matches[2])){
                    return array(
                        $this->landlinePrefixesLong[$matches[1][0]],
                        $matches[1][0],
                        $matches[2][0],
                        false
                    );
                }
            }
            if ($numberLen == $length + $lengthPrefixLong){
                $matches = array();
                preg_match_all('/^('.implode('|', $this->landlinePrefixesLong).')(.*)/', $number, $matches);
                if (!empty($matches[1]) && !empty($matches[2])){
                    return array(
                        $matches[1][0],
                        ltrim($matches[1][0], '0'),
                        $matches[2][0],
                        false
                    );
                }
            }
        }
        return array(null, null, $number, false);
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
