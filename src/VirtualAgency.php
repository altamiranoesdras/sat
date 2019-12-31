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

class VirtualAgency
{
    protected $username;

    protected $password;

    protected $session_dir;

    protected $endpoint = [
        'authentication' => 'https://farm3.sat.gob.gt/menu/init.do',
        'application'    => 'https://farm3.sat.gob.gt/menu/menuAplicacion.jsp',
        'security'       => 'https://farm3.sat.gob.gt/menu/Seguridad.do',
        'portal'         => 'https://farm3.sat.gob.gt/agenciaVirtual-web/pages/agenciaPortada.jsf',
        'new-dte'        => 'https://felav.c.sat.gob.gt/fel-web/privado/vistas/fel.jsf',
        'void-dte'       => 'https://felav.c.sat.gob.gt/fel-web/privado/vistas/anulacionDte.jsf',
        'verify-dte'     => 'https://felcons.c.sat.gob.gt/dte-agencia-virtual/dte-consulta',
        'fel-rest'       => 'https://felav02.c.sat.gob.gt/fel-rest/rest'
    ];

    public function __construct($username, $password, $session_dir = null)
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

    protected function sendRequest($endpoint, $params = null, $method = 'GET', $referer = null, $endpoint_uri = null, array $headers = [])
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

            if (in_array('Content-Type: application/json;charset=UTF-8', $headers) && (is_array($params) || is_object($params))) {
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

    private function authenticate()
    {
        $params = [
            'operacion' => 'ACEPTAR',
            'login'     => $this->username,
            'password'  => $this->password
        ];
        $result = $this->sendRequest('authentication', $params, 'POST');

        return strpos($result, 'Virtual') !== false;
    }

    protected function getSecurityToken()
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

    protected function getPortalEndpoint()
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

    protected function getNewDteToken()
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

    protected function getVoidDteToken()
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

    protected function getVerifyDteToken()
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

    protected function getTaxpayerSettings()
    {
        // Fetch the DTE token
        $token = $this->getNewDteToken();

        return json_decode($this->sendRequest(
            'fel-rest',
            [],
            'GET',
            $this->endpoint['portal'],
            '/publico/configuracion/' . $token['Nit'] . '/' . $token['Clave']
        ));
    }
}
