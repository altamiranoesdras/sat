<?php
/**
 * Provides a wrapper class for communication, administration and creation of requests
 * to the portal and virtual agency of the Superintendencia de Administración Tributaria
 * of Guatemala.
 *
 * @package    Sat
 * @subpackage Sat.VirtualAgency
 * @copyright  Copyright (c) 2018-2019 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Sat;

use stdClass;

class VirtualAgency
{
    /**
     * @var string The SAT virtual agency username.
     */
    protected $username;

    /**
     * @var string The SAT virtual agency password.
     */
    protected $password;

    /**
     * @var string The session directory.
     */
    protected $session_dir;

    /**
     * @var array An array of all available endpoints.
     */
    protected $endpoint = [
        'authentication' => 'https://farm3.sat.gob.gt/menu/init.do',
        'application'    => 'https://farm3.sat.gob.gt/menu/menuAplicacion.jsp',
        'security'       => 'https://farm3.sat.gob.gt/menu/Seguridad.do',
        'portal'         => 'https://farm3.sat.gob.gt/agenciaVirtual-web/pages/agenciaPortada.jsf',
        'new-dte'        => 'https://felav.c.sat.gob.gt/fel-web/privado/vistas/fel.jsf',
        'void-dte'       => 'https://felav.c.sat.gob.gt/fel-web/privado/vistas/anulacionDte.jsf',
        'verify-dte'     => 'https://felcons.c.sat.gob.gt/dte-agencia-virtual/dte-consulta',
        'fel-rest'       => 'https://felav02.c.sat.gob.gt/fel-rest/rest',
        'fel-recipient'  => 'https://felav02.c.sat.gob.gt/fel-rest/rest/publico/receptor'
    ];

    /**
     * VirtualAgency constructor.
     *
     * @param string      $username    The SAT virtual agency username.
     * @param string      $password    The SAT virtual agency password.
     * @param string|null $session_dir The session directory.
     *
     * @throws \Sat\Error\Authentication
     * @throws \Sat\Error\InvalidEndpoint
     */
    public function __construct(string $username, string $password, string $session_dir = null)
    {
        $this->username = $username;
        $this->password = $password;

        // Set the session directory
        if (is_null($session_dir)) {
            $this->session_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        } else {
            $this->session_dir = rtrim($session_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        // Authenticate in to the Virtual Agency system
        $authentication = $this->authenticate();

        if (!$authentication) {
            throw new Error\Authentication('The given combination of username and password is incorrect');
        }
    }

    /**
     * Send a request to the API.
     *
     * @param string      $endpoint     The endpoint to send the request.
     * @param mixed|null  $params       The parameters to be send with the request, can be an array, an object or an
     *                                  XML string.
     * @param string      $method       The method for the HTTP call.
     * @param string|null $referer      The HTTP referer.
     * @param string|null $endpoint_uri Additional URI for the called endpoint.
     * @param array       $headers      The HTTP headers.
     *
     * @return mixed The raw response from the API.
     * @throws \Sat\Error\InvalidEndpoint
     */
    protected function sendRequest(string $endpoint, $params = null, string $method = 'GET', string $referer = null, string $endpoint_uri = null, array $headers = [])
    {
        $curl = curl_init();

        // Set request URL
        if (isset($this->endpoint[$endpoint])) {
            $url = $this->endpoint[$endpoint] . (isset($endpoint_uri) ? $endpoint_uri : '');
        } else {
            throw new Error\InvalidEndpoint('The specified endpoint does not exist in the system');
        }

        // Set request headers
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            if (in_array('Content-Type: application/json;charset=UTF-8', $headers) && (is_array($params) || is_object(
                        $params
                    ))) {
                $params = json_encode($params, JSON_UNESCAPED_UNICODE);
            }
        }

        // Build GET request
        if ($method == 'GET') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

            if (!empty($params)) {
                $get = '?' . http_build_query($params);
            }
        }

        // Build POST request
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POST, true);

            if (!empty($params)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            }
        }

        // Build URL
        $url = $url . (isset($get) ? $get : '');

        // Make request
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Fetch headers
        if ($endpoint == 'authentication') {
            curl_setopt($curl, CURLOPT_HEADER, true);
        }

        // Set referer
        if (!is_null($referer)) {
            curl_setopt($curl, CURLOPT_REFERER, trim($referer));
        }

        // Create and save the request cookie
        $cookie = $this->session_dir . md5($this->username) . '.txt';

        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);

        // Get result
        $result = curl_exec($curl);

        // Close request
        curl_close($curl);

        return $result;
    }

    /**
     * Authenticate user.
     *
     * @return bool True if the user succesfully authenticated on to the virtual agency.
     * @throws \Sat\Error\InvalidEndpoint
     */
    private function authenticate() : bool
    {
        $params = [
            'operacion' => 'ACEPTAR',
            'login'     => $this->username,
            'password'  => $this->password
        ];
        $result = $this->sendRequest('authentication', $params, 'POST');

        return strpos($result, 'Virtual') !== false;
    }

    /**
     * Get the security token for initialization of the main portal.
     *
     * @return array An array containing the issuer NIT and the security token.
     * @throws \Sat\Error\Authentication
     * @throws \Sat\Error\InvalidEndpoint
     * @throws \Sat\Error\UnknownError
     */
    protected function getSecurityToken() : array
    {
        $params = [
            'nombreApp' => 'AgenciaVirtual',
            'pllamada'  => '1'
        ];
        $token  = [];
        $result = $this->sendRequest('application', $params, 'GET');

        if (strpos($result, 'Seguridad.do') !== false) {
            $html = explode('Seguridad.do?', $result, 2);

            if (isset($html[1])) {
                $html = explode('\', null, null],', $html[1], 2);
                parse_str($html[0], $token);
            } else {
                throw new Error\Authentication('An error occurred trying to get the authorization token');
            }
        } else {
            throw new Error\UnknownError('An error occurred trying to initialize the application');
        }

        return $token;
    }

    /**
     * Get the main portal endpoint url.
     *
     * @return string The url of the main portal endpoint.
     * @throws \Sat\Error\Authentication
     * @throws \Sat\Error\InvalidEndpoint
     * @throws \Sat\Error\UnknownError
     */
    protected function getPortalEndpoint() : string
    {
        // Fetch the security token
        $token = $this->getSecurityToken();

        // Verify security token
        $security = $this->sendRequest('security', $token, 'GET');

        // Initialize and set the portal endpoint
        $url = '';

        if (strpos($security, 'id="desplegar"') !== false) {
            $html = explode('id="desplegar"  src="', $security, 2);

            if (isset($html[1])) {
                $html = explode('" name="desplegar"', $html[1], 2);
                $url  = urldecode($html[0]);
            }
        }

        // Add the url to the "endpoint" property
        $this->endpoint['portal'] = $url;

        return $url;
    }

    /**
     * Get the security token for DTE issuing.
     *
     * @return array An array containing the issuer NIT and the security token.
     * @throws \Sat\Error\Authentication
     * @throws \Sat\Error\InvalidEndpoint
     * @throws \Sat\Error\UnknownError
     */
    protected function getNewDteToken() : array
    {
        // Initialize the portal
        $this->getPortalEndpoint();

        // Get the portal application
        $result = $this->sendRequest('portal');

        // Initialize and set the new dte endpoint
        $token = [];

        if (strpos($result, 'fel.jsf?') !== false) {
            $html = explode('/fel-web/privado/vistas/fel.jsf?', $result, 2);

            if (isset($html[1])) {
                $html = explode('">', $html[1], 2);
                $url  = str_replace('&amp;', '&', urldecode($html[0]));
                parse_str($url, $token);
            }
        }

        return $token;
    }

    /**
     * Get the security token for DTE voiding.
     *
     * @return array An array containing the issuer NIT and the security token.
     * @throws \Sat\Error\Authentication
     * @throws \Sat\Error\InvalidEndpoint
     * @throws \Sat\Error\UnknownError
     */
    protected function getVoidDteToken() : array
    {
        // Initialize the portal
        $this->getPortalEndpoint();

        // Get the portal application
        $result = $this->sendRequest('portal');

        // Initialize and set the new dte endpoint
        $token = [];

        if (strpos($result, 'anulacionDte.jsf?') !== false) {
            $html = explode('/fel-web/privado/vistas/anulacionDte.jsf?', $result, 2);

            if (isset($html[1])) {
                $html = explode('">', $html[1], 2);
                $url  = str_replace('&amp;', '&', urldecode($html[0]));
                parse_str($url, $token);
            }
        }

        return $token;
    }

    /**
     * Get the security token for DTE verification.
     *
     * @return array An array containing the issuer NIT and the security token.
     * @throws \Sat\Error\Authentication
     * @throws \Sat\Error\InvalidEndpoint
     * @throws \Sat\Error\UnknownError
     */
    protected function getVerifyDteToken() : array
    {
        // Initialize the portal
        $this->getPortalEndpoint();

        // Get the portal application
        $result = $this->sendRequest('portal');

        // Initialize and set the new dte endpoint
        $token = [];

        if (strpos($result, 'dte-consulta?') !== false) {
            $html = explode('/dte-agencia-virtual/dte-consulta?', $result, 2);

            if (isset($html[1])) {
                $html = explode('">', $html[1], 2);
                $url  = str_replace('&amp;', '&', urldecode($html[0]));
                parse_str($url, $token);
            }
        }

        return $token;
    }

    /**
     * Get the taxpayer information and settings.
     *
     * @return \stdClass An object with all the taxpayer settings.
     * @throws \Sat\Error\Authentication
     * @throws \Sat\Error\InvalidEndpoint
     * @throws \Sat\Error\UnknownError
     */
    protected function getTaxpayerSettings() : stdClass
    {
        // Fetch the DTE token
        $token = $this->getNewDteToken();

        return (object) json_decode(
            $this->sendRequest(
                'fel-rest',
                [],
                'GET',
                $this->endpoint['portal'],
                '/publico/configuracion/' . $token['Nit'] . '/' . $token['Clave']
            )
        );
    }

    /**
     * Get recipient NIT information.
     *
     * @param string $nit The NIT to query.
     *
     * @return \stdClass A class containing the information of the provided NIT.
     * @throws \Sat\Error\Authentication
     * @throws \Sat\Error\InvalidEndpoint
     * @throws \Sat\Error\UnknownError
     */
    public function getRecipientInformation(string $nit) : stdClass
    {
        // Fetch the DTE token
        $token = $this->getNewDteToken();

        $headers  = [
            'Accept: application/json;charset=utf-8',
            'Content-Type: application/json;charset=UTF-8'
        ];
        $response = json_decode(
            $this->sendRequest(
                'fel-recipient',
                null,
                'GET',
                $this->endpoint['new-dte'] . '?' . http_build_query($token),
                '/' . trim($nit),
                $headers
            )
        );

        return isset($response->respuesta) ? $response->respuesta : (object) [];
    }
}
