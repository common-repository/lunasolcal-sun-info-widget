<?php
    class LSC_Rise_Set_Info
    {
        public $rise;
        public $set;
        public $allDayAboveHorizon;
        public $allDayBelowHorizon;
    }

    class LSC_Calculator
    {
        const RADEG = 180.0 / M_PI;
        const DEGRAD = M_PI / 180.0;

        //
        // Helpers  
        //
        function rev($x)
        {
            return $x - floor($x / 360.0) * 360.0;
        }

        function rev180($x)
        {
            return ($x - 360.0 * floor($x / 360.0 + 0.5));
        }

        function sind($x)
        {
            return sin($x * self::DEGRAD);
        }

        function cosd($x)
        {
            return cos($x * self::DEGRAD);
        }

        function tand($x)
        {
            return tan($x * self::DEGRAD);
        }

        function asind($x)
        {
            return (self::RADEG * asin($x));
        }

        function acosd($x)
        {
            return (self::RADEG * acos($x));
        }

        function atand($x)
        {
            return (self::RADEG * atan($x));
        }

        function atan2d($y, $x)
        {
            return (self::RADEG * atan2($y, $x));
        }

        function fixangle($a)
        {
            return ($a - 360.0 * (floor($a / 360.0)));
        }

        function GMST0($d)
        {
            /* Sidtime at 0h UT = L (Sun's mean longitude) + 180.0 degr  */
            /* L = M + w, as defined in sunpos().  Math.since I'm too lazy to */
            /* add these numbers, I'll let the C compiler do it for me.  */
            /* Any decent C compiler will add the constants at compile   */
            /* time, imposing no runtime or code overhead.               */
            $sidtim0 = $this->rev((180.0 + 356.0470 + 282.9404) +
                (0.9856002585 + 4.70935E-5) * $d);
            return $sidtim0;
        }

        function convertDateToDayNumber($date, $ignoreTime)
        {
            $timeOffset = $ignoreTime ? 0 :
                $date->format('H') + $date->format('i') / 60.0 + $date->format('s') / 3600.0;

            $year = $date->format('Y');
            $month = $date->format('m');
            $day = $date->format('d');

            return 367 * $year -
                floor((7 * ($year + floor(($month + 9) / 12))) / 4) +
                floor((275 * $month) / 9) + $day - 730530 +
                $timeOffset / 24.0;
        }

        function calc_sunrise_sunset($date, $altit, $latitude, $longitude)
        {
            $upperLimb = 1;

            $rc = 0; /* Return cde from function - usually 0 */

            /* Compute d of 12h local mean solar time */
            $d = $this->convertDateToDayNumber($date, true) + 0.5 - $longitude / 360.0;

            /* Compute local sidereal time of this moment */
            $sidtime = $this->rev($this->GMST0($d) + 180.0 + $longitude);

            /* Compute Sun's RA + Decl at this moment */
            //inlined: sun_RA_dec( d, &sRA, &sdec, &sr );

            /* Compute Sun's ecliptical coordinates */
            // inlined: sun$longitude= sunpos( d, r );
            // Compute mean elements
            // Mean anomaly of the Sun
            $M = $this->rev(356.0470 + 0.9856002585 * $d);
            // Mean longitude of perihelion
            $w = 282.9404 + 4.70935E-5 * $d;
            // Eccentricity of Earth's orbit
            $e = 0.016709 - 1.151E-9 * $d;

            // Compute true longitude and radius vector
            // Eccentric anomaly
            $E = $M + $e * self::RADEG * $this->sind($M) * (1.0 + $e * $this->cosd($M));

            // x, y coordinates in orbit
            $orb_x = $this->cosd($E) - $e;
            $orb_y = sqrt(1.0 - $e * $e) * $this->sind($E);

            //Solar distance
            $sr = sqrt($orb_x * $orb_x + $orb_y * $orb_y);

            // True anomaly
            $v = $this->atan2d($orb_y, $orb_x);

            // True solar longitude
            $sunLon = $v + $w;
            if ($sunLon >= 360.0) {
                $sunLon -= 360.0; /* Make it 0..360 degrees */
            }

            /* Compute ecliptic rectangular coordinates (z=0) */
            $x = $sr * $this->cosd($sunLon);
            $y = $sr * $this->sind($sunLon);

            /* Compute obliquity of ecliptic (inclination of Earth's axis) */
            $obl_ecl = 23.4393 - 3.563E-7 * $d;

            /* Convert to equatorial rectangular coordinates - x is unchanged */
            $z = $y * $this->sind($obl_ecl);
            $y = $y * $this->cosd($obl_ecl);

            /* Convert to spherical coordinates */
            $sRA = $this->atan2d($y, $x);
            $sdec = $this->atan2d($z, sqrt($x * $x + $y * $y));

            /* Compute time when Sun is at south - in hours UT */
            $tsouth = 12.0 - $this->rev180($sidtime - $sRA) / 15.0;

            /* Compute the Sun's apparent radius, degrees */
            $sradius = 0.2666 / $sr;

            /* Do correction to upper limb, if necessary */
            if ($upperLimb > 0) {
                $altit -= $sradius;
            }

            /* Compute the diurnal arc that the Sun traverses to reach */
            /* the specified altitude altit: */ {
                $cost = ($this->sind($altit) - $this->sind($latitude) * $this->sind($sdec)) /
                    ($this->cosd($latitude) * $this->cosd($sdec));
                if ($cost >= 1.0) {
                    $rc = -1;
                    $t = 0.0; /* Sun always below altit */
                } else {
                    if ($cost <= -1.0) {
                        $rc = +1;
                        $t = 12.0; /* Sun always above altit */
                    } else {
                        $t = $this->acosd($cost) / 15.0; /* The diurnal arc, hours */
                    }
                }
            }

            $rise_set_info = new LSC_Rise_Set_Info();

            switch ($rc) {
                case 0:
                    $rise_set_info->rise = $tsouth - $t;
                    $rise_set_info->set = $tsouth + $t;
                    $rise_set_info->allDayBelowHorizon = false;
                    $rise_set_info->allDayAboveHorizon = false;
                    break;
                case -1:
                    $rise_set_info->allDayBelowHorizon = true;
                    $rise_set_info->allDayAboveHorizon = false;
                    break;
                case 1:
                    $rise_set_info->allDayAboveHorizon = true;
                    $rise_set_info->allDayBelowHorizon = false;
            }

            return $rise_set_info;
        }

        function format_datetime($date, $time)
        {
            $hours = floor($time);
            $minutes = fmod($time, 1) * 60;
            $seconds = fmod($minutes, 1) * 60;

            $d = clone $date;
            $d->setTime($hours, $minutes, $seconds);

            return $d;
        }
    }
?>