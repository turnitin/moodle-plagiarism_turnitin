Moodle Direct V2 Troubleshooting
--------------------------------

1) You may need to ensure that within your designated moodledata directory; the turnitintooltwo subdirectory and the subsequent logs subdirectory have the correct permissions to be able to create directories and files.

2) You may need to ensure that the turnitintooltwo directory within your designated data directory and it's logs subdirectory have the correct permissions to be able to create directories and files.

3) Pop-ups will need to be enabled on the browser being used if access to the Turnitin Document Viewer is required.

4) There have been very isolated reports of the settings not showing for the Plagiarism plugin despite it being enabled, this is due to it not showing in Moodle cache. The solution is for an administrator to purge all caches and it should then appear.


cURL
----

1) In order for the module to work correctly you must enable support for cURL in your php.ini file. To do this locate the following line in your php.ini file:

	;extension=php_curl.dll
	OR
	;extension=php_curl.so

	Remove the semi-colon at the start of the line to activate the php cURL extension. Once you have done this you will need to restart your web server service.

	More information on cURL and more detailed instructions for installing it can be found here: http://uk3.php.net/curl

2) If you encounter connectivity issues (error: Turnitin API Base URL incorrect or unavailable) this could be related to a CA certificate being unavailable to cURL.

If cURL has an out of date (or no) CA certificates, the interaction with Turnitin will fail due to cURL performing peer SSL certificate verification and not being able to verify the Turnitin SSL certificate.
Until cURL 7.18.0 some CA certificates were provided, but after 7.18.0 no cs certificates have been provided at all. Because of this, the Moodle server administrator would need to ensure that an up to date CA certificate bundle is used. To be clear, Moodle doesn't need an SSL certificate, however, it needs to have the certificate bundle in place so cURL can recognize the SSL certificates of Turnitin.

Information on how to install a certificate bundle is available via the URLs below. Note that the URLs are third party sites and not affiliated with Turnitin or iParadigms in any way:

Information for Linux environments: http://docs.moodle.org/26/en/SSL_certificate_for_moodle.org

Information for Windows environments: http://curl.haxx.se/docs/sslcerts.html

WSDL
----

We have had reported issues with users not being able to parse the WSDL files that the API requires. The relevant error message starts “PHP Fatal error:  SOAP-ERROR: Parsing WSDL: ….”

From version 2014012405 onwards we have bundled the WSDL files with the plugin download, however the issue does still occur for some users. This is due to a PHP bug with libxml_disable_entity_loader() being set to true and preventing external entities from being loaded. If this is set by a PHP script then PHP uses this value for all processes on the server. For further information see: https://bugs.php.net/bug.php?id=64938.

To fix this, you need to add the following line to your to your moodle config.php:

libxml_disable_entity_loader(false);

Thanks to Dan Marsden for the information and solution.