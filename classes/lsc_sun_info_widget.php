<?php

class LSC_SunInfoWidget extends WP_Widget
{
    /**
     * Initializes the plugin by setting its properties and calling the parent class with the description.
     */
    public function __construct()
    {
        parent::__construct(
            LSC__PLUGIN_SLUG,
            __('LunaSolCal - Sun Info Widget', LSC__PLUGIN_SLUG),
            array(
                'customize_selective_refresh' => true,
            )
        );
    }

    /**
     * The Widget uses the browser language, not the language of the Wordpress site!!!
     */
    public $ui_strings = array(
        "de" => array(
            'sunrise' => 'Sonnenaufgang: ',
            'sunset' => 'Sonnenuntergang: ',
            'invalidLocation' => 'Die Ortsangabe ist inkorrekt: <br/>Bitte überprüfen Sie die Werte für Breitengrad und Längengrad!
            <br/>Gültige Werte:<br/>Breitengrad: zwischen -90 und 90 Grad<br/>Längengrad: zwischen -180 und 180 Grad<br/>',
        ),
        "en" => array(
            'sunrise' => 'Sunrise: ',
            'sunset' => 'Sunset: ',
            'invalidLocation' => 'The location is incorrect: <br/>Please check the values for latitude and longitude!
            <br/>Latitude: between -90 and 90<br/>Longitude: between -180 and 180 degrees<br/>',
        ),
        "fr" => array(
            'sunrise' => 'Lever du soleil: ',
            'sunset' => 'Coucher du soleil: ',
            'invalidLocation' => 'Le lieu indiqué est incorrect :<br/>veuillez vérifier les valeurs de latitude et de longitude !
            <br/>Latitude : entre -90 et 90.<br/>Longitude : entre -180 et 180<br/>',
        ),
        "es" => array(
            'sunrise' => 'Salida del sol: ',
            'sunset' => 'Puesta del sol: ',
            'invalidLocation' => 'La ubicación es incorrecta:<br/>¡compruebe los valores de latitud y longitud!
            <br/>Latitud: entre -90 y 90<br/>Longitud: entre -180 y 180<br/>',
        ),
    );

    /**
     * Displays the administrative view of the form and includes the options
     * for the instance of the widget as arguments passed into the function.
     *
     * @param array $instance the options for the instance of this widget
     */
    public function form($instance)
    {
        // Set widget defaults
        $defaults = array(
            'location' => '',
            'latitude' => '0.0',
            'longitude' => '0.0',
            'timezone' => '',
            'bgcolor'    => '#ebebeb', // default light grey color
            'fgcolor'    => '#000000', // default black color
            'validLocation' => true
        );

        $zone_array = array();
        $timestamp = time();
        $now = new DateTime();

        foreach (timezone_identifiers_list() as $key => $zone) {
            $tz = new DateTimeZone($zone);
            date_timezone_set($now, $tz);
            $zone_array[$key]['zone'] = $zone;
            $zone_array[$key]['offset'] = date_offset_get($now);
        }

        $myCmpFunc = function ($a, $b) {
            if ($a['offset'] == $b['offset']) {
                return strcmp($a['zone'], $b['zone']);
            }
            return ($a['offset'] < $b['offset']) ? -1 : 1;
        };
    
        usort($zone_array, $myCmpFunc);

        $locale = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

        if (empty($locale)) {
            $locale = setlocale(LC_ALL, 0);
        }

        $this->init_location_from_locale($defaults, $locale);

        // Parse current settings with defaults
        extract(wp_parse_args((array) $instance, $defaults));

        $current_timezone = $defaults['timezone'];

        if (empty($current_timezone)) {
            $current_timezone = date_default_timezone_get();
        }

?>
        <!-- // Location -->
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('location')); ?>"><?php echo __('Location', LSC__PLUGIN_SLUG); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('location')); ?>" name="<?php echo esc_attr($this->get_field_name('location')); ?>" type="text" value="<?php echo esc_attr($location); ?>" />
        </p>

        <!-- // Latitude -->
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('latitude')); ?>"><?php echo __('Latitude', LSC__PLUGIN_SLUG); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('latitude')); ?>" name="<?php echo esc_attr($this->get_field_name('latitude')); ?>" type="text" value="<?php echo esc_attr($latitude); ?>" required />
        </p>

        <!-- // Longitude -->
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('longitude')); ?>"><?php echo __('Longitude', LSC__PLUGIN_SLUG); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('longitude')); ?>" name="<?php echo esc_attr($this->get_field_name('longitude')); ?>" type="text" value="<?php echo esc_attr($longitude); ?>" required />
        </p>

        <!-- // Timezone -->
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('timezone')); ?>"><?php echo __('Time zone', LSC__PLUGIN_SLUG); ?></label>
            <select class='widefat' id="<?php echo esc_attr($this->get_field_id('timezone')); ?>" name="<?php echo esc_attr($this->get_field_name('timezone')); ?>" type="text">
                <?php
                foreach ($zone_array as &$the_zone) {

                    $the_offset = $the_zone['offset'];
                    $hours = floor($the_offset / 3600);
                    $minutes = floor(($the_offset / 60) % 60);
                    $sign = $hours > 0 ? "+" : "";

                    $formatted_offset = " (UTC" . $sign . $hours . ($minutes > 0 ?  ":" . $minutes : "") . ")";

                    if ($the_zone['zone'] == $current_timezone) {
                        echo "<option value=\"" . esc_attr($the_zone['zone']) . "\" selected>" . esc_attr($the_zone['zone'] . $formatted_offset) . "</option>";
                    } else {
                        echo "<option value=\"" . esc_attr($the_zone['zone']) . "\">" . esc_attr($the_zone['zone'] . $formatted_offset) . "</option>";
                    }
                }
                ?>
            </select>
        </p>

        <?php // Widget background color 
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('bgcolor')); ?>"><?php echo __('Background color:', LSC__PLUGIN_SLUG); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('bgcolor')); ?>" name="<?php echo esc_attr($this->get_field_name('bgcolor')); ?>" type="color" value="<?php echo esc_attr($bgcolor); ?>" />
        </p>

        <?php // Widget text color 
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('fgcolor')); ?>"><?php echo __('Text color:', LSC__PLUGIN_SLUG); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('fgcolor')); ?>" name="<?php echo esc_attr($this->get_field_name('fgcolor')); ?>" type="color" value="<?php echo esc_attr($fgcolor); ?>" />
        </p>

<?php }

    /**
     * Updates the values of the widget. Uses the serialization class to sanitize the
     * information before saving it.
     *
     * @param array $newInstance the values to be sanitized and saved
     * @param array $oldInstance the values that were originally saved
     */
    public function update($new_instance, $old_instance)
    {
        parent::update($new_instance, $old_instance);

        $instance = $old_instance;
        $instance['location'] = isset($new_instance['location']) ? wp_strip_all_tags($new_instance['location']) : '';
        $instance['latitude'] = isset($new_instance['latitude']) ? wp_strip_all_tags($new_instance['latitude']) : '';
        $instance['longitude'] = isset($new_instance['longitude']) ? wp_strip_all_tags($new_instance['longitude']) : '';
        $instance['timezone'] = isset($new_instance['timezone']) ? wp_strip_all_tags($new_instance['timezone']) : '';
        $instance['bgcolor'] = isset($new_instance['bgcolor']) ? wp_strip_all_tags($new_instance['bgcolor']) : '';
        $instance['fgcolor'] = isset($new_instance['fgcolor']) ? wp_strip_all_tags($new_instance['fgcolor']) : '';

        $validLatitude = $this->is_valid($instance['latitude'], -90, 90);
        $validLongitude = $this->is_valid($instance['longitude'], -180, 180);

        if (!$validLatitude) {
            error_log(print_r("Incorrect latitude: " . $instance['latitude'], true));
            $instance['latitude'] = 0.0;
        }

        if (!$validLongitude) {
            error_log(print_r("Incorrect longitude: " . $instance['longitude'], true));
            $instance['longitude'] = 0.0;
        }

        $instance['validLocation'] = $validLatitude && $validLongitude;

        return $instance;
    }

    public function is_valid(&$value, $min, $max)
    {
        $isValid = is_numeric($value);

        if (!$isValid) {
            // try to parse DMS
            $value = $this->convertDMSToDecimal($value);
            $isValid = ( $value != false );
        }

        if ($isValid && (($value < $min) || ($value > $max))) {
            $isValid = false;
        }

        return $isValid;
    }

    function convertDMSToDecimal($latlng)
    {
        $isValid = false;
        $decimal_degrees = 0;
        $degrees = 0;
        $minutes = 0;
        $seconds = 0;
        $direction = 1;

        $locale_info = localeconv();
        $decimal_separator = $locale_info['decimal_point'];

        // Determine if there are extra decimal separators in the input string
        $num_periods = substr_count($latlng, $decimal_separator);
        if ($num_periods > 1) {
            $temp = preg_replace('/' . $decimal_separator . '/', ' ', $latlng, $num_periods - 1); // replace all but last decimal separator with delimiter
            $temp = trim(preg_replace('/[a-zA-Z]/', '', $temp)); // when counting chunks we only want numbers
            $chunk_count = count(explode(" ", $temp));
            if ($chunk_count > 2) {
                $latlng = preg_replace('/' . $decimal_separator . '/', ' ', $latlng, $num_periods - 1); // remove last decimal separator
            } else {
                $latlng = str_replace($decimal_separator, " ", $latlng); // remove all decimal separators, not enough chunks left by keeping last one
            }
        }

        // Remove units
        $latlng = trim($latlng);
        $latlng = str_replace(["º", "°","'","′", "`", "\"", "″" ], " ", $latlng);
        $latlng = str_replace("  ", " ", $latlng);

        $latlng = substr($latlng, 0, 1) . str_replace('-', ' ', substr($latlng, 1)); // remove all but first dash

        if ($latlng != "") {
            // direction at the start
            if (preg_match("/^([nsewoNSEWO]?)\s*(\d{1,3})\s+(\d{1,3})\s*(\d*\.?\d*)$/", $latlng, $matches)) {
                $isValid = true;
                $degrees = intval($matches[2]);
                $minutes = intval($matches[3]);
                $seconds = floatval($matches[4]);
                if (strtoupper($matches[1]) == "S" || strtoupper($matches[1]) == "W")
                    $direction = -1;
            }
            // direction at the end
            elseif (preg_match("/^(-?\d{1,3})\s+(\d{1,3})\s*(\d*(?:\.\d*)?)\s*([nsewoNSEWO]?)$/", $latlng, $matches)) {
                $isValid = true;
                $degrees = intval($matches[1]);
                $minutes = intval($matches[2]);
                $seconds = floatval($matches[3]);
                if (strtoupper($matches[4]) == "S" || strtoupper($matches[4]) == "W" || $degrees < 0) {
                    $direction = -1;
                    $degrees = abs($degrees);
                }
            }
            if ($isValid) {
                // A match was found, do the calculation
                $decimal_degrees = ($degrees + ($minutes / 60) + ($seconds / 3600)) * $direction;
            } else {
                // Decimal degrees with a direction at the start of the string
                if (preg_match("/^([nsewNSEW]?)\s*(\d+(?:\.\d+)?)$/", $latlng, $matches)) {
                    $isValid = true;
                    if (strtoupper($matches[1]) == "S" || strtoupper($matches[1]) == "W")
                        $direction = -1;
                    $decimal_degrees = $matches[2] * $direction;
                }
                // Decimal degrees with a direction at the end of the string
                elseif (preg_match("/^(-?\d+(?:\.\d+)?)\s*([nsewNSEW]?)$/", $latlng, $matches)) {
                    $isValid = true;
                    if (strtoupper($matches[2]) == "S" || strtoupper($matches[2]) == "W" || $degrees < 0) {
                        $direction = -1;
                        $degrees = abs($degrees);
                    }
                    $decimal_degrees = $matches[1] * $direction;
                }
            }
        }
        if ($isValid) {
            return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $decimal_degrees);
        } else {
            return false;
        }
    }

    /** 
     * Get localilzed string
     */
    public function getString($string_id, $locale)
    {
        $language = strtolower(substr($locale, 0, 2));

        if (!array_key_exists($language, $this->ui_strings)) {
            $language = "en";
        }
        return $this->ui_strings[$language][$string_id];
    }

    /** 
     * get default location for locale language
     */
    public function init_location_from_locale(&$defaults, $locale)
    {
        $lang = substr($locale, 0, 2);
        switch ($lang) {
            case "de":
                $defaults['location'] = "Berlin";
                $defaults['latitude'] = 52.518611;
                $defaults['longitude'] = 13.408333;
                $defaults['timezone'] = "Europe/Berlin";
                break;
            default:
            case "en":
                $defaults['location'] = "New York";
                $defaults['latitude'] = 40.712778;
                $defaults['longitude'] = -74.005833;
                $defaults['timezone'] = "America/New_York";
                break;
            case "es":
                $instance['location'] = "Madrid";
                $defaults['latitude'] = 40.4125;
                $defaults['longitude'] = -3.703889;
                $defaults['timezone'] = "Europe/Madrid";
                break;
            case "fr":
                $defaults['location'] = "Paris";
                $defaults['latitude'] = 48.856667;
                $defaults['longitude'] = 2.351667;
                $defaults['timezone'] = "Europe/Paris";
                break;
        }
    }


    /**
     * Displays the widget based on the contents of the included template.
     *
     * @param array $args     argument provided by WordPress that may be useful in rendering the widget
     * @param array $instance the values of the widget
     */
    public function widget($args, $instance)
    {
        extract($args);

        // Check the widget options
        $location = isset($instance['location']) ? $instance['location'] : '';
        $latitude = isset($instance['latitude']) ? $instance['latitude'] : '';
        $longitude = isset($instance['longitude']) ? $instance['longitude'] : '';
        $timezone = isset($instance['timezone']) ? $instance['timezone'] : '';
        $bgcolor = isset($instance['bgcolor']) ? $instance['bgcolor'] : '';
        $fgcolor = isset($instance['fgcolor']) ? $instance['fgcolor'] : '';
        $validLocation = isset($instance['validLocation']) ? $instance['validLocation'] : '';

        if (empty($timezone)) {
            $timezone = date_default_timezone_get();
        }

        // WordPress core before_widget hook (always include )
        echo $before_widget;

        // Display the widget
        echo '<div class="widget-text wp_widget_plugin_box">';

        echo "<div id='lunasolcal-sun-info-widget' style='color:" . esc_attr($fgcolor) . "; background-color:" . esc_attr($bgcolor) . "; border-radius: 10px; padding: 10px; '>";

        $today = new DateTime();
        $lsc_cfg_latitude = $latitude;
        $lsc_cfg_longitude = $longitude;
        $lsc_cfg_locationName = $location;
        $lsc_cfg_timezone = new DateTimeZone($timezone);
        $lsc_cfg_locale = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

        if (empty($lsc_cfg_locale)) {
            $lsc_cfg_locale = setlocale(LC_ALL, 0);
        }

        if ($validLocation) {
            $lsc_calculator = new LSC_Calculator();

            $lsc_rise_set_info = $lsc_calculator->calc_sunrise_sunset($today, -35.0 / 60.0, $lsc_cfg_latitude, $lsc_cfg_longitude);

            $lsc_sunrise = $lsc_calculator->format_datetime($today, $lsc_rise_set_info->rise);
            $lsc_sunset = $lsc_calculator->format_datetime($today, $lsc_rise_set_info->set);
        }

        if (empty($lsc_cfg_locationName)) {
            $lsc_cfg_locationName = $lsc_cfg_latitude . '°, ' . $lsc_cfg_longitude . '°';
        }

        $lsc_date_formatter = new IntlDateFormatter($lsc_cfg_locale, IntlDateFormatter::LONG, IntlDateFormatter::NONE, $lsc_cfg_timezone, IntlDateFormatter::GREGORIAN);
        $lsc_time_formatter = new IntlDateFormatter($lsc_cfg_locale, IntlDateFormatter::NONE, IntlDateFormatter::SHORT, $lsc_cfg_timezone, IntlDateFormatter::GREGORIAN);
        $lsc_weekday_formatter = new IntlDateFormatter($lsc_cfg_locale, IntlDateFormatter::LONG, IntlDateFormatter::NONE, $lsc_cfg_timezone, IntlDateFormatter::GREGORIAN, "EEEE");

        $weekDayNumber = date_format($today, 'N');
        $dt = date_create("4.1.1970 +" . $weekDayNumber . " Days");
        $weekDayName = datefmt_format($lsc_weekday_formatter, $dt);

        $lsc_sun_img_path = plugin_dir_url(__FILE__) . '../images/sun_240.png';
        echo '<center>';
        echo "<div style='font-size: 18px;'><b>";
        echo esc_attr($weekDayName) . '<br/>';
        echo esc_attr($lsc_date_formatter->format($today)) . "</b></div>";
        echo esc_attr($lsc_cfg_locationName) . '<br/>';
        echo '<div style="padding: 10px;"> <img src=\'' . esc_attr($lsc_sun_img_path) . '\' width="80"> </div>';
        if ($validLocation) {
            echo "<div style='font-size: 18px;'>";
            echo '<b>' . esc_attr($this->getString("sunrise", $lsc_cfg_locale) . $lsc_time_formatter->format($lsc_sunrise)) . '<br/>';
            echo esc_attr($this->getString("sunset", $lsc_cfg_locale) . $lsc_time_formatter->format($lsc_sunset)) . '</b></div></center>';
        } else {
            echo '</center><p>' . esc_attr($this->getString("invalidLocation", $lsc_cfg_locale)) . '</p>';
        }

        echo "</div>";
        echo "</div>";

        // WordPress core after_widget hook (always include )
        echo $after_widget;
    }
}
