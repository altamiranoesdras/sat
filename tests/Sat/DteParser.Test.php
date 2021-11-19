<?php
declare(strict_types = 1);

use Tester\Assert;
use \Sat\DteParser;

require __DIR__ . '/../bootstrap.php';

/**
 * Set DTE file
 */
$dte = '<?xml version="1.0" encoding="UTF-8" standalone="no"?><dte:GTDocumento xmlns:dte="http://www.sat.gob.gt/dte/fel/0.2.0" xmlns:cca="http://www.sat.gob.gt/face2/CobroXCuentaAjena/0.1.0" xmlns:cex="http://www.sat.gob.gt/face2/ComplementoExportaciones/0.1.0" xmlns:cfc="http://www.sat.gob.gt/dte/fel/CompCambiaria/0.1.0" xmlns:cfe="http://www.sat.gob.gt/face2/ComplementoFacturaEspecial/0.1.0" xmlns:cno="http://www.sat.gob.gt/face2/ComplementoReferenciaNota/0.1.0" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:n1="http://www.altova.com/samplexml/other-namespace" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" Version="0.1" xsi:schemaLocation="http://www.sat.gob.gt/dte/fel/0.2.0 GT_Documento-0.2.0.xsd"><dte:SAT ClaseDocumento="dte"><dte:DTE ID="DatosCertificados"><dte:DatosEmision ID="DatosEmision"><dte:DatosGenerales CodigoMoneda="GTQ" FechaHoraEmision="2021-11-18T02:42:15-06:00" Tipo="FACT"/><dte:Emisor AfiliacionIVA="GEN" CodigoEstablecimiento="1" CorreoEmisor="" NITEmisor="28733657" NombreComercial="Awesome, Inc." NombreEmisor="Awesome Holdings Corporation"><dte:DireccionEmisor><dte:Direccion>31 AVENIDA  14-08 CIUDAD DE PLATA II, zona 7, Guatemala, GUATEMALA</dte:Direccion><dte:CodigoPostal>1</dte:CodigoPostal><dte:Municipio>Guatemala</dte:Municipio><dte:Departamento>GUATEMALA</dte:Departamento><dte:Pais>GT</dte:Pais></dte:DireccionEmisor></dte:Emisor><dte:Receptor CorreoReceptor="" IDReceptor="CF" NombreReceptor="CONSUMIDOR FINAL"/><dte:Frases><dte:Frase CodigoEscenario="2" TipoFrase="1"/></dte:Frases><dte:Items><dte:Item BienOServicio="B" NumeroLinea="1"><dte:Cantidad>1</dte:Cantidad><dte:Descripcion>Test</dte:Descripcion><dte:PrecioUnitario>100</dte:PrecioUnitario><dte:Precio>100.000000</dte:Precio><dte:Descuento>0</dte:Descuento><dte:Impuestos><dte:Impuesto><dte:NombreCorto>IVA</dte:NombreCorto><dte:CodigoUnidadGravable>1</dte:CodigoUnidadGravable><dte:MontoGravable>89.285714</dte:MontoGravable><dte:MontoImpuesto>10.714286</dte:MontoImpuesto></dte:Impuesto></dte:Impuestos><dte:Total>100.000000</dte:Total></dte:Item></dte:Items><dte:Totales><dte:TotalImpuestos><dte:TotalImpuesto NombreCorto="IVA" TotalMontoImpuesto="10.714286"/></dte:TotalImpuestos><dte:GranTotal>100.000000</dte:GranTotal></dte:Totales></dte:DatosEmision><dte:Certificacion><dte:NITCertificador>16693949</dte:NITCertificador><dte:NombreCertificador>Superintendencia de Administracion Tributaria</dte:NombreCertificador><dte:NumeroAutorizacion Numero="2535474368" Serie="D412A347">D412A347-9720-44C0-A9CC-FDE068F0A2E7</dte:NumeroAutorizacion><dte:FechaHoraCertificacion>2021-11-18T02:42:15-06:00</dte:FechaHoraCertificacion></dte:Certificacion></dte:DTE></dte:SAT><ds:Signature Id="xmldsig-0f9247a2-1dd4-4936-8594-b4ed4c5e47b3">
<ds:SignedInfo>
<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
<ds:Reference Id="xmldsig-0f9247a2-1dd4-4936-8594-b4ed4c5e47b3-ref0" URI="#DatosEmision">
<ds:Transforms>
<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
</ds:Transforms>
<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
<ds:DigestValue>echB6iiy9mLcRku/38m+NQYBkdughplOI0r2z03YrCo=</ds:DigestValue>
</ds:Reference>
<ds:Reference Type="http://uri.etsi.org/01903#SignedProperties" URI="#xmldsig-0f9247a2-1dd4-4936-8594-b4ed4c5e47b3-signedprops">
<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
<ds:DigestValue>Ea5EyHIgeohVPzds6ynBGsnnfXGrIkAW1qpolINf1Yo=</ds:DigestValue>
</ds:Reference>
</ds:SignedInfo>
<ds:SignatureValue Id="xmldsig-0f9247a2-1dd4-4936-8594-b4ed4c5e47b3-sigvalue">
5Gbckd6Gk34gVuA8gY1ev15fSP3O4RcedQhJh9uLyyz6Ft7JdUjmD9TNo3WdcAnxmcRABOWZJWTn
t4X18grtWVfGm6Bs8iT1bpWNdPtZi3Ds7e20To/b/kRJY4IZtsUECug34/0c7HUIYB/fw7UqvITp
aYQJS/+RW8jPoX8ChrCVBaAr8INO4mJlsBWdMomLFPhtpWOQUK3jP60YWKyQlbWJPgbFhSk7g1ur
qkwSsZYG3pZLj+6myriMR37Nda5n8oRk2htJnlIIVH6bLTu5bLeepeB8uYk8iLKQQEFTx3ojdEK3
XHmzujX0IZBEeXIX4Inp8Q+189j9s2HtfDjS6w==
</ds:SignatureValue>
<ds:KeyInfo>
<ds:X509Data>
<ds:X509Certificate>
MIIDYTCCAkmgAwIBAgIIYslpuahc8tYwDQYJKoZIhvcNAQELBQAwODE2MDQGA1UEAwwtU3VwZXJp
bnRlbmRlbmNpYSBkZSBBZG1pbmlzdHJhY2lvbiBUcmlidXRhcmlhMB4XDTIxMDEyNTEyNDczMloX
DTIzMDEyNTEyNDczMlowKDERMA8GA1UEAwwIMjg3MzM2NTcxEzARBgNVBAoMCnNhdC5nb2IuZ3Qw
ggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDtpR1BxAWadRRDN6KJ4DhCjx/cmV4n/zaI
lnqhjSWLDDuUwVOgBTOYZrf72UpE8qPleGuzP5/Pn2rAsuWbq9p0rh3VOJA5HG4l2m2FyKEZZmVL
5nqSMPWhsr9w8dCKSCDcH+5C8IrOYmQ7fhBXjojTukqNli2R1labdZhD8vvJSS5B1SC04jZIzfvc
7iSQq9l6zvWVoJXUAaDubLWLqlSuVrDfeI3di1AMMUYiXJv1TdDii6mmqqcKu1pP6D6AsboZuJTf
w/BBsZWfgsag11Skm8cLVPUwt82iFdxye6iLZcTtRrS5L45I7hkPtNTjbgRqhaMOWniBsF5enkSH
l4XrAgMBAAGjfzB9MAwGA1UdEwEB/wQCMAAwHwYDVR0jBBgwFoAUWIJ2jVgosclDBAK01cheai//
+EowHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUFBwMEMB0GA1UdDgQWBBS1Mp5rYhw1zPOuE6H8
3z8ecGkwRzAOBgNVHQ8BAf8EBAMCBeAwDQYJKoZIhvcNAQELBQADggEBAH4DZUE45qC6rh/x6Y9a
TZhvT1ChKGI9inrrEvykvVJLpv8UDNcncNWyBQr1YnfsXBMZgKbRhNWY2DERWBebS9M4wjf8I7oY
AUCjodYY3vXk3nTq0JPoROie/PgToNL17wIw8Jn3oFuc1LvQVC8Qq5xGyozxdTbQWzimyWPjfzzX
NZvfmz6nqEreehwBU0TteiJWbMi1jMr/dzYHa3JrtDjQrI2TXVJJIRqILRZ7yuzWgf0ABf1PQKT5
m4rx4Dx4U4W24vHtzt2gyZWIyRC2O+5Z0/S42VPH74PgeTNzC+NvUwRA+HQSktoiyqmIy4XjlZxp
9l/mEl50V27rrlgMzss=
</ds:X509Certificate>
</ds:X509Data>
</ds:KeyInfo>
<ds:Object><xades:QualifyingProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" xmlns:xades141="http://uri.etsi.org/01903/v1.4.1#" Target="#xmldsig-0f9247a2-1dd4-4936-8594-b4ed4c5e47b3"><xades:SignedProperties Id="xmldsig-0f9247a2-1dd4-4936-8594-b4ed4c5e47b3-signedprops"><xades:SignedSignatureProperties><xades:SigningTime>2021-11-18T08:42:15.585Z</xades:SigningTime><xades:SigningCertificate><xades:Cert><xades:CertDigest><ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/><ds:DigestValue>Oho62r3M4ezOKv2cDo2rJoeQXPYRUOILVWs4NEFACQY=</ds:DigestValue></xades:CertDigest><xades:IssuerSerial><ds:X509IssuerName>CN=Superintendencia de Administracion Tributaria</ds:X509IssuerName><ds:X509SerialNumber>7118336932150309590</ds:X509SerialNumber></xades:IssuerSerial></xades:Cert><xades:Cert><xades:CertDigest><ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/><ds:DigestValue>dMKFT5GpRMlOOkvm+Eejattj/UGN45s1Ujg/fal4b6w=</ds:DigestValue></xades:CertDigest><xades:IssuerSerial><ds:X509IssuerName>CN=Superintendencia de Administracion Tributaria</ds:X509IssuerName><ds:X509SerialNumber>49188108610261972699090769446035082589</ds:X509SerialNumber></xades:IssuerSerial></xades:Cert></xades:SigningCertificate></xades:SignedSignatureProperties></xades:SignedProperties></xades:QualifyingProperties></ds:Object>
</ds:Signature><ds:Signature Id="xmldsig-eda4f599-f6d8-4754-8578-2930813f62fd">
<ds:SignedInfo>
<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
<ds:Reference Id="xmldsig-eda4f599-f6d8-4754-8578-2930813f62fd-ref0" URI="#DatosCertificados">
<ds:Transforms>
<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
</ds:Transforms>
<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
<ds:DigestValue>KmSm5WTI6HhPMmfkuPT2FbFlLeREeDo4SLOrI2pZjl0=</ds:DigestValue>
</ds:Reference>
<ds:Reference Type="http://uri.etsi.org/01903#SignedProperties" URI="#xmldsig-eda4f599-f6d8-4754-8578-2930813f62fd-signedprops">
<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
<ds:DigestValue>HxKH2m8fEP0ucsnIHNyZXOswLdzQH7h/rUXMHxbWeW8=</ds:DigestValue>
</ds:Reference>
</ds:SignedInfo>
<ds:SignatureValue Id="xmldsig-eda4f599-f6d8-4754-8578-2930813f62fd-sigvalue">
MCDpMLsPiZ2RceNc11vAtqZfjth1CXuQIreu2HX/0weVMRrKQFCY8sNP1/p/grqJs6eeCIjzqz/P
PILKUAieorO6TULf8TwjxBD311eRuOluc4uRmoSP4Z/eN1BWaD2TGyEGdeyhRyAT+MWybD1qdPcf
04JYcYFfB5Aa7yDnVupti0hbMWzqgxMhc3M6+jc9ZS2DsupwNe2vYMZzgtfcAMgcs3Ga/Hfu5ckr
WqdlUlUSK/NWDIavX5dser/haAJCKahyz6AYzL6eqA+LdwIKrt62MbjKn596rEOurGopLktOAA+E
I2DMSRU0bDXOuP9Y+KDVjucW5qcGTM3jK2HQsA==
</ds:SignatureValue>
<ds:KeyInfo>
<ds:X509Data>
<ds:X509Certificate>
MIIIjDCCB3SgAwIBAgIQNwPVyprfuehhO4/z/w73lDANBgkqhkiG9w0BAQsFADCBqDEcMBoGA1UE
CQwTd3d3LmNlcnRpY2FtYXJhLmNvbTEPMA0GA1UEBwwGQk9HT1RBMRkwFwYDVQQIDBBESVNUUklU
TyBDQVBJVEFMMQswCQYDVQQGEwJDTzEYMBYGA1UECwwPTklUIDgzMDA4NDQzMy03MRgwFgYDVQQK
DA9DRVJUSUNBTUFSQSBTLkExGzAZBgNVBAMMEkFDIFNVQiBDRVJUSUNBTUFSQTAgFw0yMTA5MTAx
NzAzNDdaGA8yMDIyMDkxMDE3MDM0N1owggEsMR4wHAYDVQQJDBU3IEF2ZW5pZGEgMy03MyB6b25h
IDkxEjAQBgNVBAgMCUdVQVRFTUFMQTEMMAoGA1UECwwDU0FUMQ4wDAYDVQQFEwU3MTg5NjEhMB8G
CisGAQQBgbVjAgMTEU5JVCBFTlQgIDE2NjkzOTQ5MTcwNQYDVQQKDC5TVVBFUklOVEVOREVOQ0lB
IERFIEFETUlOSVNUUkFDScOTTiBUUklCVVRBUklBMRIwEAYDVQQHDAlHVUFURU1BTEExIjAgBgkq
hkiG9w0BCQEWE0pNSVJJQVNHQFNBVC5HT0IuR1QxCzAJBgNVBAYTAkdUMTcwNQYDVQQDDC5TVVBF
UklOVEVOREVOQ0lBIERFIEFETUlOSVNUUkFDScOTTiBUUklCVVRBUklBMIIBIDANBgkqhkiG9w0B
AQEFAAOCAQ0AMIIBCAKCAQEAido081fJrmomp+dcNAv9+7Ed/os1c2MQDkWvbbXTeKkKd2YnI5yq
Zh668WhQscbKepalu22QH7STySsxLLxal/c7LqtuQaE28AkBm9ekljb2qWM3dW9319L4VsaJjRVd
5ShEGXq8eds/1dq8ZT8BzpYC7D9sZZYbmRkkutE3/rKi9TA2wx2KnsbqoahfnXDEOJcQ9k0YmBKg
9Kor89xMaxg3/87E8ae3e8vj7kNTDbMLe4AoqmKyku2xyYAPw1CPRxBvVA6wia50TGOqJuiAinye
XzYdZwaeDHBy0ByilvHallOt+temjyO09FIYEscF6HS31R3Le2sXY1Ht3nJToQIBA6OCBCkwggQl
MDYGCCsGAQUFBwEBBCowKDAmBggrBgEFBQcwAYYaaHR0cDovL29jc3AuY2VydGljYW1hcmEuY28w
HgYDVR0RBBcwFYETSk1JUklBU0dAU0FULkdPQi5HVDCCAmgGA1UdIASCAl8wggJbMIHpBgsrBgEE
AYG1YzIBCDCB2TB7BggrBgEFBQcCARZvaHR0cDovL3d3dy5maXJtYS1lLmNvbS5ndC9kb2NzL0VU
LUZFLTAxX1YzX0VTUEVDSUZJQ0FDSU9OX1RFQ05JQ0FfREVDTEFSQUNJT05fREVfUFJBQ1RJQ0FT
X0RFX0NFUlRJRklDQUNJT04ucGRmMFoGCCsGAQUFBwICME4aTExpbWl0YWNpb25lcyBkZSBnYXJh
bnTtYXMgZGUgZXN0ZSBjZXJ0aWZpY2FkbyBzZSBwdWVkZW4gZW5jb250cmFyIGVuIGxhIERQQy4w
PgYLKwYBBAGBtWMKCgEwLzAtBggrBgEFBQcCAjAhGh9EaXNwb3NpdGl2byBkZSBoYXJkd2FyZSAo
VG9rZW4pMIIBKwYLKwYBBAGBtWMyZXgwggEaMIIBFgYIKwYBBQUHAgIwggEIGoIBBENBTUFSQSBE
RSBDT01FUkNJTyBERSBHVUFURU1BTEEgTklUIDM1MTU5LTgsIGluZm9AZmlybWEtZS5jb20uZ3Qs
IEF1dG9yaXphZG8gc2Vn+m4gcmVzb2x1Y2nzbiBOby4gUFNDLTAxLTIwMTIgZGVsIFJlZ2lzdHJv
IGRlIFByZXN0YWRvcmVzIGRlIFNlcnZpY2lvcyBkZSBDZXJ0aWZpY2FjafNuIGRlbCBNaW5pc3Rl
cmlvIGRlIEVjb25vbe1hIGRlIGxhIFJlcPpibGljYSBkZSBHdWF0ZW1hbGEgZW1pdGlkYSBlbCAy
MyBkZSBlbmVybyBkZWwgYfFvIDIwMTIuMAwGA1UdEwEB/wQCMAAwDgYDVR0PAQH/BAQDAgP4MCcG
A1UdJQQgMB4GCCsGAQUFBwMBBggrBgEFBQcDAgYIKwYBBQUHAwQwHQYDVR0OBBYEFMU/Zo4RIkQr
+oro+lCVY6lykJdYMB8GA1UdIwQYMBaAFIBxzDKSWHX0AyE6q74c04/yIBXtMIHXBgNVHR8Egc8w
gcwwgcmggcaggcOGXmh0dHA6Ly93d3cuY2VydGljYW1hcmEuY29tL3JlcG9zaXRvcmlvcmV2b2Nh
Y2lvbmVzL2FjX3N1Ym9yZGluYWRhX2NlcnRpY2FtYXJhXzIwMTQuY3JsP2NybD1jcmyGYWh0dHA6
Ly9taXJyb3IuY2VydGljYW1hcmEuY29tL3JlcG9zaXRvcmlvcmV2b2NhY2lvbmVzL2FjX3N1Ym9y
ZGluYWRhX2NlcnRpY2FtYXJhXzIwMTQuY3JsP2NybD1jcmwwDQYJKoZIhvcNAQELBQADggEBAD4s
z6yCZWWnII1GPOo6OGbkln5ouKKrRXg6aTehDX5R1EcJdqxQIbCaaUnxZvzPIPmtn3Q5/Zj6kJGP
9n/m6raXALWIxpbzdYF499kgDvI18cjaNVhSc9R5hPdcpM5Jf5mtRPcjVHJEEKDTPCCVC/c0SlCA
+h+0BoVgYDGwxPGuQ4SO6nKbKdMpWS4+QUXpLK0vfQdY1wpzDz2PNamPWhQ0NsKiBmrjizYG+WR1
mGJCZsurQN5PkN/2/CQ3p9pTRtc6WIBJe9vyzfkY8GZNSm7IPoScDnhr6k39IB5r4WcoQ48o0At/
GupwV51jgCJGbkZNRKfcY1k6lPVRRBQvTRw=
</ds:X509Certificate>
</ds:X509Data>
</ds:KeyInfo>
<ds:Object><xades:QualifyingProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" xmlns:xades141="http://uri.etsi.org/01903/v1.4.1#" Target="#xmldsig-eda4f599-f6d8-4754-8578-2930813f62fd"><xades:SignedProperties Id="xmldsig-eda4f599-f6d8-4754-8578-2930813f62fd-signedprops"><xades:SignedSignatureProperties><xades:SigningTime>2021-11-18T08:42:15.698Z</xades:SigningTime><xades:SigningCertificate><xades:Cert><xades:CertDigest><ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/><ds:DigestValue>+iS6ptVUrBwCLWuKa/4oKGiovPYc8h+gH8xISvASklo=</ds:DigestValue></xades:CertDigest><xades:IssuerSerial><ds:X509IssuerName>CN=AC SUB CERTICAMARA,O=CERTICAMARA S.A,OU=NIT 830084433-7,C=CO,ST=DISTRITO CAPITAL,L=BOGOTA,STREET=www.certicamara.com</ds:X509IssuerName><ds:X509SerialNumber>73127452864011543074502985262788704148</ds:X509SerialNumber></xades:IssuerSerial></xades:Cert></xades:SigningCertificate></xades:SignedSignatureProperties></xades:SignedProperties></xades:QualifyingProperties></ds:Object>
</ds:Signature></dte:GTDocumento>';

/**
 * Mock the original class.
 */
$DteParser = new DteParser($dte);

/**
 * DteParser::getUser() Test
 *
 * @assert type Check if the returned response it's an array.
 * @assert contains Check if the returned array has the expected properties.
 */
Assert::type('object', $DteParser->getParsedDocument());
Assert::type('string', $DteParser->getParsedDocument(true));
Assert::contains(
    'Guatemala',
    $DteParser->getParsedDocument()->SAT->DTE->DatosEmision->Emisor->DireccionEmisor->Municipio
);

/**
 * Clear all temporary files
 */
@rmdir(__DIR__ . '/output');
@rmdir(TMP_DIR);
