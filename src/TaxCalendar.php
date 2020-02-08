<?php
/**
 * Provides a wrapper class for fetching and parsing the online tax calendar of
 * Superintendencia de Administración Tributaria of Guatemala.
 *
 * @package    Sat
 * @subpackage Sat.TaxCalendar
 * @copyright  Copyright (c) 2018-2019 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Sat;

use stdClass;

class TaxCalendar
{
    /**
     * @var string The SAT tax calendar endpoint.
     */
    private $url = 'https://farm2.sat.gob.gt/japSitio-web/consultas/paadServicios/calendario.jsf';

    /**
     * @var array An array containing the months names in spanish.
     */
    private $months = [
        'ENERO'      => '01',
        'FEBRERO'    => '02',
        'MARZO'      => '03',
        'ABRIL'      => '04',
        'MAYO'       => '05',
        'JUNIO'      => '06',
        'JULIO'      => '07',
        'AGOSTO'     => '08',
        'SEPTIEMBRE' => '09',
        'OCTUBRE'    => '10',
        'NOVIEMBRE'  => '11',
        'DICIEMBRE'  => '12'
    ];

    /**
     * @var bool|string The HTML content of the calendar.
     */
    private $html;

    /**
     * TaxCalendar constructor.
     */
    public function __construct()
    {
        // Fetch the HTML content from the SAT website
        $this->html = file_get_contents($this->url);

        // Set the Guatemalan time zone
        date_default_timezone_set('America/Guatemala');

        // Set Spanish as the localized language
        setlocale(LC_TIME, 'es_ES');
    }

    /**
     * Get the calendar options.
     *
     * @return \stdClass An object containing the calendar options.
     */
    private function getCalendarOptions() : stdClass
    {
        $first_part  = explode('ComboBox("calendario:cmbSeleccionaMes",', $this->html, 2);
        $second_part = explode(');</script>', $first_part[1], 2);

        $json = str_replace('\'', '"', $second_part[0]);

        return (object) json_decode(trim($json));
    }

    /**
     * Get the calendar tax events.
     *
     * @return array An array containing all the tax calendar events for the current month.
     */
    public function getEvents() : array
    {
        $first_part  = explode('<tbody id="calendario:data:tb">', $this->html, 2);
        $second_part = explode('</tbody>', $first_part[1], 2);

        // Get and parese HTML elements
        $html_elements = explode('</tr>', $second_part[0]);

        foreach ($html_elements as $key => $value) {
            $value = str_replace('</span>', '|', $value);
            $value = str_replace('</td>', '|', $value);
            $value = rtrim($value, '|');
            $value = strip_tags($value);

            if (!empty($value)) {
                $value = explode('|', $value);
            }

            $html_elements[$key] = $value;
        }

        // Build the events array
        $events = [];
        $day    = null;

        foreach ($html_elements as $element) {
            $event = [];

            if (!empty($element) && count($element) > 3) {
                // Get the event day
                if (count($element) === 5) {
                    $day = $element[0];
                }

                // Set event date
                $calendar_options = $this->getCalendarOptions()->listOptions->itemsText;
                $calendar_date    = explode(' ', $calendar_options[0], 2);

                $event = [
                    'date'      => date(
                        'd/m/Y',
                        strtotime($day . '-' . $this->months[strtoupper($calendar_date[0])] . '-' . $calendar_date[1])
                    ),
                    'timestamp' => strtotime(
                        $day . '-' . $this->months[strtoupper($calendar_date[0])] . '-' . $calendar_date[1]
                    ),
                    'day'       => $day,
                    'month'     => $this->months[strtoupper($calendar_date[0])],
                    'year'      => $calendar_date[1]
                ];

                // Get the event title
                if (count($element) === 5) {
                    $event['title'] = ucwords(trim(html_entity_decode($element[1])));
                } else {
                    $event['title'] = ucwords(trim(html_entity_decode($element[0])));
                }

                if (ucfirst(substr($event['title'], 0, 8)) !== 'Impuesto') {
                    $event['title'] = 'Impuesto ' . $event['title'];
                }

                // Get the event description
                if (count($element) === 5) {
                    $event['description'] = str_replace("\n", '', ucfirst(trim(html_entity_decode($element[2]))));
                } else {
                    $event['description'] = str_replace("\n", '', ucfirst(trim(html_entity_decode($element[1]))));
                }

                // Get the event DeclaraGuate form id
                if (count($element) === 5) {
                    $event['declaraguate_form'] = trim($element[3]);
                } else {
                    $event['declaraguate_form'] = trim($element[2]);
                }

                // Get the event AsisteWeb form id
                if (count($element) === 5) {
                    $event['asisteweb_form'] = trim($element[4]);
                } else {
                    $event['asisteweb_form'] = trim($element[3]);
                }

                $events[] = (object) $event;
            }
        }

        return $events;
    }
}
