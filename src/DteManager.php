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

class DteManager extends VirtualAgency
{
    private $settings;

    public function __construct($username, $password, $fel_password, $session_dir = null)
    {
        parent::__construct($username, $password, $session_dir);

        // Set the Guatemalan time zone
        date_default_timezone_set('America/Guatemala');

        // Set Spanish as the localized language
        setlocale(LC_TIME, 'es_ES');

        // Fetch taxpayer settings
        $this->fel_password = $fel_password;
        $this->settings     = $this->getTaxpayerSettings();
    }

    public function createNewDte(array $invoice = [])
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
                'Content-Type: application/json;charset=UTF-8'
            ];
            $process = json_decode($this->sendRequest(
                'fel-rest',
                $request,
                'POST',
                $this->endpoint['new-dte'] . '?' . http_build_query($token),
                '/publico/procesarDocumento', $headers
            ));

            // Sign request
            if ($process->estadoHttp == 200) {
                $request['frasePaso'] = $this->fel_password;

                $signing = json_decode($this->sendRequest(
                    'fel-rest',
                    $request,
                    'POST',
                    $this->endpoint['new-dte'] . '?' . http_build_query($token),
                    '/publico/firmarDocumento',
                    $headers
                ));

                // Issue document certificate
                if ($signing->estadoHttp == 200) {
                    $xml = isset($signing->detalle[0]->mensaje) ? $signing->detalle[0]->mensaje : '';

                    $certification = json_decode($this->sendRequest(
                        'fel-rest',
                        $xml,
                        'POST',
                        $this->endpoint['new-dte'] . '?' . http_build_query($token),
                        '/publico/certificarDocumento',
                        $headers
                    ));

                    // Build response
                    $response = [
                        'xml'           => $xml,
                        'signing'       => $signing,
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

    public function getTaxpayer()
    {
        return $this->settings;
    }

    public function getDteTypes()
    {
        return $this->settings->respuesta->restriccionDocumento;
    }

    public function getBranches()
    {
        return $this->settings->respuesta->informacionEmisor->establecimientos;
    }

    public function getPhrases()
    {
        return $this->settings->respuesta->informacionEmisor->frases;
    }

    public function getErrorsCode()
    {
        $errors   = [];
        $messages = $this->settings->respuesta->contenidoMensajes->Contenido;

        if (!empty($messages) && is_array($messages)) {
            foreach ($messages as $message) {
                $errors[$message->codigo] = $message->mensaje;
            }
        }

        return $errors;
    }

    public function getAvailablePhrases()
    {
        $available_phrases = $this->settings->respuesta->frasesGenerales;
        $phrases           = [];

        if (!empty($available_phrases)) {
            foreach ($available_phrases as $phrases_group) {
                foreach ($phrases_group->frases as $phrase) {
                    $phrases[$phrase->codigoEscenario] = [
                        'tipo'          => $phrases_group->codigoTipoFrase,
                        'escenario'     => $phrase->codigoEscenario,
                        'textoAColocar' => $phrase->textoAColocar
                    ];
                }
            }
        }

        return $phrases;
    }

    public function getInternationalCommercialTerms()
    {
        return $this->settings->respuesta->catalogosGenerales->incoterms;
    }

    private function getDteRequest(array $params)
    {
        // Get DTE types
        $types = $this->getDteTypes();

        foreach ($types as $id => $dte) {
            $types[$id] = $dte->dte;
        }

        // Build items array
        $items       = [];
        $item_id     = 1;
        $grand_total = 0;

        $default_item = [
            'NumeroLinea'    => 1,
            'BienOServicio'  => 'B',
            'Cantidad'       => 1,
            'Descripcion'    => '',
            'PrecioUnitario' => 0,
            'Precio'         => 0,
            'Descuento'      => 0,
            'Total'          => 0,
            'MontoGrabable'  => 0
        ];

        if (isset($params['DatosEmision']['Items']['Item'])) {
            foreach ($params['DatosEmision']['Items']['Item'] as $item) {
                $item = array_merge($default_item, $item);

                $item['NumeroLinea'] = $item_id;

                // Format unitary price
                if ($item['PrecioUnitario'] > 0) {
                    $item['PrecioUnitario'] = number_format($item['PrecioUnitario'], 6);
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
                    $item['Total'] = number_format((($item['PrecioUnitario'] * $item['Cantidad']) - $item['Descuento']), 6);
                } else {
                    $item['Total'] = 0;
                }

                $items[]     = $item;
                $grand_total = $grand_total + $item['Total'];

                $item_id++;
            }
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

        if ($params['DatosEmision']['DatosGenerales']['Exp'] == 'SI') {
            $complements[] = [
                'IDComplemento'     => 'EXP',
                'NombreComplemento' => 'Exportacion',
                'URIComplemento'    => 'text',
                'Exportacion'       => [
                    'DireccionConsignatarioODestinatario' => isset($params['DatosEmision']['Receptor']['DireccionReceptor']) ? $params['DatosEmision']['Receptor']['DireccionReceptor'] : '',
                    'incoterm'                            => 'ZZZ',
                    'NombreConsignatarioODestinatario'    => isset($params['DatosEmision']['Receptor']['NombreReceptor']) ? $params['DatosEmision']['Receptor']['NombreReceptor'] : '',
                    'version'                             => '1'
                ]
            ];
        }

        // Build phrases array
        $phrases = $this->getPhrases();

        if ($params['DatosEmision']['DatosGenerales']['Exp'] == 'SI') {
            $available_phrases = $this->getAvailablePhrases();
            $phrases[]         = $available_phrases[1];
        }

        // Build DTE request
        $dte_request = [
            'SAT'       => [
                'DTE' => [
                    'DatosEmision'  => [
                        'DatosGenerales' => [
                            'Tipo'                 => isset($params['DatosEmision']['DatosGenerales']['Tipo']) ? $params['DatosEmision']['DatosGenerales']['Tipo'] : 'FPEQ',
                            'Exp'                  => isset($params['DatosEmision']['DatosGenerales']['Exp']) ? $params['DatosEmision']['DatosGenerales']['Exp'] : 'NO',
                            'FechaHoraEmisionForm' => date('Y-m-d\TH:i:s.v\Z'),
                            'CodigoMoneda'         => isset($params['DatosEmision']['DatosGenerales']['CodigoMoneda']) ? $params['DatosEmision']['DatosGenerales']['CodigoMoneda'] : 'GTQ',
                        ],
                        'Emisor'         => [
                            'DireccionEmisor'       => [
                                'Direccion'    => $current_branch->calleAvenida . '  ' . $current_branch->numeroCasa . ' ' . $current_branch->colonia . ', zona ' . $current_branch->zona . ', ' . $current_branch->municipio . ', ' . $current_branch->departamento,
                                'CodigoPostal' => 1,
                                'municipio'    => $current_branch->municipio,
                                'departamento' => $current_branch->departamento,
                                'pais'         => 'GT'
                            ],
                            'NITEmisor'             => $this->settings->respuesta->nitEmisor,
                            'NombreEmisor'          => $this->settings->respuesta->informacionEmisor->nombre,
                            'CodigoEstablecimiento' => 1,
                            'NombreComercial'       => $current_branch->nombre,
                            'CorreoEmisor'          => '',
                            'AfiliacionIVA'         => $this->settings->respuesta->informacionEmisor->afiliacionIVA
                        ],
                        'Receptor'       => [
                            'IDReceptor'     => isset($params['DatosEmision']['Receptor']['IDReceptor']) ? $params['DatosEmision']['Receptor']['IDReceptor'] : 'CF',
                            'TipoEspecial'   => isset($params['DatosEmision']['Receptor']['TipoEspecial']) ? $params['DatosEmision']['Receptor']['TipoEspecial'] : null,
                            'NombreReceptor' => isset($params['DatosEmision']['Receptor']['NombreReceptor']) ? $params['DatosEmision']['Receptor']['NombreReceptor'] : 'CONSUMIDOR FINAL',
                            'CorreoReceptor' => ''
                        ],
                        'Items'          => [
                            'Item' => $items
                        ],
                        'Totales'        => [
                            'GranTotal' => $grand_total
                        ],
                        'Frases'         => [
                            'Frase' => $phrases
                        ],
                        'Complementos'   => [
                            'Complemento' => $complements
                        ]
                    ],
                    'Certificacion' => [
                        'NITCertificador'        => '16693949',
                        'NombreCertificador'     => 'SuperIntendencia de Administracion Tributaria',
                        'NumeroAutorizacion'     => [
                            'Serie'  => 'F72BA9CD',
                            'Numero' => '226052895',
                            'text'   => 'F72BA9CD-0D79-4B1F-9453-0273B7D2EA88'
                        ],
                        'FechaHoraCertificacion' => '2019-02-11T00:00:00-06:00'
                    ]
                ]
            ],
            'Signature' => [
                'SignedInfo'     => [
                    'CanonicalizationMethod' => (object) [],
                    'SignatureMethod'        => (object) [],
                    'Reference'              => [
                        'DigestMethod' => (object) [],
                        'DigestValue'  => (object) []
                    ]
                ],
                'SignatureValue' => ''
            ]
        ];

        return $dte_request;
    }
}
