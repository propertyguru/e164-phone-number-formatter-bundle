# E164-phone-number-formatter-bundle
Bundle to format phone numbers in E164 format

## Detection Examples
    $formatter = $this->container->get('guru_phone_number_formatter.formatter');
    // optional: set current country - if known
    $formatter->setDefaultRegionCode('my');

### If you know the country code already
    $e164 = $formatter->numberToE164('0101234567', '60');

### If the country code if embedded in the number
    $e164 = $formatter->numberToE164('+60101234567');

### If you are not sure if the country code is embedded or not
    $e164 = $formatter->numberToE164('+60101234567', '60');

#### All of the above will output
    /*
        Output:
        array(
           'countryCode' => '60',
           'nationalDestinationCode' => '010',
           'nationalDestinationCodeInternational' => '10',
           'subscriberNumber' => '1234567',
           'isMobile' => true,
        )
    */

### Embedded country code has precedence over specified country code
    $e164 = $formatter->numberToE164('+65101234567', '60');

    /*
    Output:
        array(
           'countryCode' => '65',
           'nationalDestinationCode' => NULL,
           'nationalDestinationCodeInternational' => NULL,
           'subscriberNumber' => '101234567',
           'isMobile' => false,
        )
    */

