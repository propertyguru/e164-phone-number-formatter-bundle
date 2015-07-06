# E194-phone-number-formatter-bundle
Bundle to format phone numbers in E194 format

## Detection Examples
    $formatter = $this->container->get('guru_phone_number_formatter.formatter');

### If you know the country code already
    $e194 = $formatter->numberToE194('0101234567', '60');

### If the country code if embedded in the number
    $e194 = $formatter->numberToE194('+60101234567');

### If you are not sure if the country code is embedded or not
    $e194 = $formatter->numberToE194('+60101234567', '60');

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
    $e194 = $formatter->numberToE194('+65101234567', '60');

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


