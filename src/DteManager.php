<?php
/**
 * Provides a wrapper class for issuance, verification and voiding of DTE documents
 * issued by the Superintendencia de Administración Tributaria of Guatemala.
 *
 * @package    Sat
 * @subpackage Sat.DteManager
 * @copyright  Copyright (c) 2018-2019 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Sat;

use stdClass;

class DteManager extends VirtualAgency
{
    /**
     * @var \stdClass The taxpayer settings.
     */
    private $settings;

    /**
     * DteManager constructor.
     *
     * @param string $username The SAT virtual agency username.
     * @param string $password The SAT virtual agency password.
     * @param string $fel_password The FEL certifier password.
     * @param string|null $session_dir The session directory.
     *
     * @throws \Sat\Error\Authentication
     * @throws \Sat\Error\InvalidEndpoint
     * @throws \Sat\Error\UnknownError
     */
    public function __construct(string $username, string $password, string $fel_password, string $session_dir = null)
    {
        parent::__construct($username, $password, $session_dir);

        // Set the Guatemalan time zone
        date_default_timezone_set('America/Guatemala');

        // Set Spanish as the localized language
        setlocale(LC_TIME, 'es_ES');

        // Fetch taxpayer settings
        $this->fel_password = $fel_password;
        $this->settings = $this->getTaxpayerSettings();
    }

    /**
     * Create a new DTE.
     *
     * @param array $invoice An array containing all the invoice parameters.
     *
     * @return \stdClass
     * @throws \Sat\Error\Authentication
     * @throws \Sat\Error\DteError
     * @throws \Sat\Error\InvalidEndpoint
     * @throws \Sat\Error\UnknownError
     */
    public function createNewDte(array $invoice = []) : stdClass
    {
        // Fetch the DTE token
        $token = $this->getNewDteToken();

        // Initialize the "New DTE" application
        $app = $this->sendRequest('new-dte', $token, 'GET', $this->endpoint['portal']);

        // Validate DTE request
        if (strpos($app, 'nitCertificador') !== false) {
            // Get DTE authorization number
            $request = $this->getDteRequest($invoice);

            // Process request
            $headers = [
                'Accept: application/json;charset=utf-8',
                'Content-Type: application/json;charset=UTF-8',
                'Authorization: ' . ($this->settings->respuesta->tokenJWT ?? '')
            ];
            $process = json_decode(
                $this->sendRequest(
                    'fel-rest',
                    $request,
                    'POST',
                    $this->endpoint['new-dte'] . '?' . http_build_query($token),
                    '/publico/procesarDocumento',
                    $headers
                )
            );

            // Sign request
            if ($process->estadoHttp == 200) {
                $request['frasePaso'] = $this->fel_password;

                $signing = json_decode(
                    $this->sendRequest(
                        'fel-rest',
                        $request,
                        'POST',
                        $this->endpoint['new-dte'] . '?' . http_build_query($token),
                        '/publico/firmarDocumento',
                        $headers
                    )
                );

                // Issue document certificate
                if ($signing->estadoHttp == 200) {
                    $xml = isset($signing->detalle[0]->mensaje) ? $signing->detalle[0]->mensaje : '';

                    $certification = json_decode(
                        $this->sendRequest(
                            'fel-rest',
                            $xml,
                            'POST',
                            $this->endpoint['new-dte'] . '?' . http_build_query($token),
                            '/publico/certificarDocumento',
                            $headers
                        )
                    );

                    // Build response
                    $response = [
                        'xml' => $xml,
                        'signing' => $signing,
                        'certification' => $certification
                    ];
                    $response = (object) array_merge($response, (array) $certification);

                    return $response;
                } else {
                    throw new Error\DteError($signing->mensaje);
                }
            } else {
                throw new Error\DteError($process->mensaje);
            }
        } else {
            throw new Error\UnknownError('An error occurred trying to initialize the application');
        }
    }

    /**
     * Get the taxpayer settings.
     *
     * @return \stdClass An object with all the taxpayer settings.
     */
    public function getTaxpayer() : stdClass
    {
        return (object) $this->settings;
    }

    /**
     * Get all the DTE types.
     *
     * @return array An array containing all the DTE types.
     */
    public function getDteTypes() : array
    {
        return (array) $this->settings->respuesta->restriccionDocumento;
    }

    /**
     * Get all branches.
     *
     * @return array An array containing all the registered branches.
     */
    public function getBranches() : array
    {
        return (array) $this->settings->respuesta->informacionEmisor->establecimientos;
    }

    /**
     * Get phrases.
     *
     * @return array An array containing the required phrases.
     */
    public function getPhrases() : array
    {
        return (array) $this->settings->respuesta->informacionEmisor->frases;
    }

    /**
     * Get the error codes.
     *
     * @return array An array containing all the error codes of the API.
     */
    public function getErrorsCode() : array
    {
        $errors = [];
        $messages = $this->settings->respuesta->contenidoMensajes->Contenido;

        if (!empty($messages) && is_array($messages)) {
            foreach ($messages as $message) {
                $errors[$message->codigo] = $message->mensaje;
            }
        }

        return $errors;
    }

    /**
     * Get all the available phrases.
     *
     * @return array An array containing all the available phrases.
     */
    public function getAvailablePhrases() : array
    {
        $available_phrases = $this->settings->respuesta->frasesGenerales;
        $phrases = [];

        if (!empty($available_phrases)) {
            foreach ($available_phrases as $phrases_group) {
                foreach ($phrases_group->frases as $phrase) {
                    $phrases[$phrase->codigoEscenario] = [
                        'tipo' => $phrases_group->codigoTipoFrase,
                        'escenario' => $phrase->codigoEscenario,
                        'textoAColocar' => $phrase->textoAColocar
                    ];
                }
            }
        }

        return $phrases;
    }

    /**
     * Get the international commercial terms.
     *
     * @return array An array containing all the international commercial terms.
     */
    public function getInternationalCommercialTerms() : array
    {
        return (array) $this->settings->respuesta->catalogosGenerales->incoterms;
    }

    /**
     * Get a DTE issuing request.
     *
     * @param array $params An array containing all the parameters required by the DTE document.
     *
     * @return array An array contaning the API response and the DTE document.
     */
    private function getDteRequest(array $params) : array
    {
        // Get DTE types
        $types = $this->getDteTypes();

        foreach ($types as $id => $dte) {
            $types[$id] = $dte->dte;
        }

        // Build items array
        $items = [];
        $item_id = 1;
        $grand_total = 0;
        $taxes = 0;

        $default_item = [
            'NumeroLinea' => 1,
            'BienOServicio' => 'B',
            'Cantidad' => 1,
            'Descripcion' => '',
            'PrecioUnitario' => 0,
            'Precio' => 0,
            'Descuento' => 0,
            'Impuestos' => [
                [
                    'CodigoUnidadGravable' => 1,
                    'MontoGravable' => 0,
                    'MontoImpuesto' => 0,
                    'NombreCorto' => 'IVA'
                ]
            ],
            'Total' => 0,
            'MontoGrabable' => 0
        ];

        if (isset($params['DatosEmision']['Items']['Item'])) {
            foreach ($params['DatosEmision']['Items']['Item'] as $item) {
                $item = array_merge($default_item, $item);

                $item['NumeroLinea'] = $item_id;

                // Format quantity
                if ($item['Cantidad'] > 0) {
                    $item['Cantidad'] = $item['Cantidad'] + 0;
                } else {
                    $item['Cantidad'] = 0;
                }

                // Format unitary price
                if ($item['PrecioUnitario'] > 0) {
                    $item['PrecioUnitario'] = $item['PrecioUnitario'] + 0;
                } else {
                    $item['PrecioUnitario'] = 0;
                }

                // Format discount
                if ($item['Descuento'] > 0) {
                    $item['Descuento'] = number_format($item['Descuento'], 6);
                } else {
                    $item['Descuento'] = 0;
                }

                // Format total price without discount
                if (($item['PrecioUnitario'] * $item['Cantidad']) > 0) {
                    $item['Precio'] = number_format(($item['PrecioUnitario'] * $item['Cantidad']), 6);
                } else {
                    $item['Precio'] = 0;
                }

                // Format total price with discount
                if ((($item['PrecioUnitario'] * $item['Cantidad']) - $item['Descuento']) > 0) {
                    $item['Total'] = number_format(
                        (($item['PrecioUnitario'] * $item['Cantidad']) - $item['Descuento']),
                        6
                    );
                } else {
                    $item['Total'] = 0;
                }

                // Format taxable amount
                if (($item['Total']) > 0) {
                    $item['Impuestos'][0]['MontoGravable'] = number_format(($item['Total'] / 1.12), 6);
                    $item['MontoGrabable'] = number_format(($item['Total'] / 1.12), 6);
                } else {
                    $item['Impuestos'][0]['MontoGravable'] = 0;
                    $item['MontoGrabable'] = 0;
                }

                // Format taxes amount
                if (($item['Total']) > 0) {
                    $item['Impuestos'][0]['MontoImpuesto'] = number_format(
                        ($item['Total'] - $item['Impuestos'][$item_id - 1]['MontoGravable']),
                        6
                    );
                } else {
                    $item['Impuestos'][0]['MontoImpuesto'] = 0;
                }

                $items[] = $item;
                $grand_total = $grand_total + $item['Total'];

                $item_id++;
            }
        }

        // Calculate total taxes
        foreach ($items as $item) {
            $taxes = $taxes + $item['Impuestos'][0]['MontoImpuesto'];
        }

        // Format grand total
        $grand_total = number_format($grand_total, 6);

        // Get branch information
        $current_branch = [];

        foreach ($this->settings->respuesta->informacionEmisor->establecimientos as $branch) {
            $branch_id = 1;

            if (isset($params['DatosEmision']['Emisor']['CodigoEstablecimiento'])) {
                $branch_id = $params['DatosEmision']['Emisor']['CodigoEstablecimiento'];
            }

            if ($branch->numero == $branch_id) {
                $current_branch = $branch;

                break;
            }
        }

        // Build complements array
        $complements = [];

        if (($params['DatosEmision']['DatosGenerales']['Exp'] ?? 'NO') == 'SI') {
            $complements[] = [
                'IDComplemento' => 'EXP',
                'NombreComplemento' => 'Exportacion',
                'URIComplemento' => 'text',
                'Exportacion' => [
                    'DireccionConsignatarioODestinatario' => $params['DatosEmision']['Receptor']['DireccionReceptor'] ?? '',
                    'incoterm' => 'ZZZ',
                    'NombreConsignatarioODestinatario' => $params['DatosEmision']['Receptor']['NombreReceptor'] ?? '',
                    'version' => '1'
                ]
            ];
        }

        // Build phrases array
        $phrases = $this->getPhrases();

        if (($params['DatosEmision']['DatosGenerales']['Exp'] ?? 'NO') == 'SI') {
            $available_phrases = $this->getAvailablePhrases();
            $phrases[] = $available_phrases[1];
        }

        // Build DTE request
        $dte_request = [
            'SAT' => [
                'DTE' => [
                    'DatosEmision' => [
                        'DatosGenerales' => [
                            'Tipo' => $params['DatosEmision']['DatosGenerales']['Tipo'] ?? 'FPEQ',
                            'Exp' => $params['DatosEmision']['DatosGenerales']['Exp'] ?? 'NO',
                            'FechaHoraEmisionForm' => date('Y-m-d\TH:i:s.v\Z'),
                            'CodigoMoneda' => $params['DatosEmision']['DatosGenerales']['CodigoMoneda'] ?? 'GTQ',
                        ],
                        'Emisor' => [
                            'DireccionEmisor' => [
                                'Direccion' => $current_branch->calleAvenida . '  ' . $current_branch->numeroCasa .
                                    ' ' . $current_branch->colonia . ', zona ' . $current_branch->zona . ', ' .
                                    $current_branch->municipio . ', ' . $current_branch->departamento,
                                'CodigoPostal' => 1,
                                'municipio' => $current_branch->municipio,
                                'departamento' => $current_branch->departamento,
                                'pais' => 'GT'
                            ],
                            'NITEmisor' => $this->settings->respuesta->nitEmisor,
                            'NombreEmisor' => $this->settings->respuesta->informacionEmisor->nombre,
                            'CodigoEstablecimiento' => $current_branch->numero,
                            'NombreComercial' => $current_branch->nombre,
                            'CorreoEmisor' => '',
                            'AfiliacionIVA' => $this->settings->respuesta->informacionEmisor->afiliacionIVA
                        ],
                        'Receptor' => [
                            'IDReceptor' => $params['DatosEmision']['Receptor']['IDReceptor'] ?? 'CF',
                            'TipoEspecial' => $params['DatosEmision']['Receptor']['TipoEspecial'] ?? null,
                            'NombreReceptor' => $params['DatosEmision']['Receptor']['NombreReceptor'] ??
                                'CONSUMIDOR FINAL',
                            'CorreoReceptor' => ''
                        ],
                        'Items' => [
                            'Item' => $items
                        ],
                        'Totales' => [
                            'GranTotal' => $grand_total,
                            'TotalImpuestos' => [
                                'TotalImpuesto' => [
                                    [
                                        'NombreCorto' => 'IVA',
                                        'TotalMontoImpuesto' => (string) $taxes,
                                    ]
                                ]
                            ]
                        ],
                        'Frases' => [
                            'Frase' => $phrases
                        ],
                        'Complementos' => [
                            'Complemento' => $complements
                        ]
                    ],
                    'Certificacion' => [
                        'NITCertificador' => '16693949',
                        'NombreCertificador' => 'Superintendencia de Administracion Tributaria',
                        'NumeroAutorizacion' => [
                            'Serie' => 'F72BA9CD',
                            'Numero' => '226052895',
                            'text' => 'F72BA9CD-0D79-4B1F-9453-0273B7D2EA88'
                        ],
                        'FechaHoraCertificacion' => '2019-02-11T00:00:00-06:00'
                    ]
                ]
            ],
            'Signature' => [
                'SignedInfo' => [
                    'CanonicalizationMethod' => (object) [],
                    'SignatureMethod' => (object) [],
                    'Reference' => [
                        'DigestMethod' => (object) [],
                        'DigestValue' => (object) []
                    ]
                ],
                'SignatureValue' => ''
            ],
            'nombreNavegador' => 'Safari 14'
        ];

        if (($params['DatosEmision']['DatosGenerales']['Exp'] ?? 'NO') == 'NO') {
            unset($dte_request['SAT']['DTE']['DatosEmision']['DatosGenerales']['Exp']);
        }

        if (empty($complements)) {
            unset($dte_request['SAT']['DTE']['DatosEmision']['Complementos']);
        }

        return $dte_request;
    }
}
