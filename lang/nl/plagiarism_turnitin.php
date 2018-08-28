<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   turnitintooltwo
 * @copyright 2012 iParadigms LLC
 */

/*
 * To change this template, choose Tools | Templates.
 * and open the template in the editor.
 */

// General.
$string['pluginname'] = 'Turnitin-plagiaat-plugin';
$string['turnitintooltwo'] = 'Turnitin Tool';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Turnitin-plagiaat-plugin-taak';
$string['connecttesterror'] = 'Er is een fout opgetreden bij het maken van verbinding met Turnitin. De foutmelding staat hieronder:<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'Turnitin inschakelen';
$string['excludebiblio'] = 'Exclusief bibliografie';
$string['excludequoted'] = 'Exclusief citaten';
$string['excludevalue'] = 'Kleine overeenkomsten uitsluiten';
$string['excludewords'] = 'Woorden';
$string['excludepercent'] = 'Procent';
$string['norubric'] = 'Geen beoordelingsschema';
$string['otherrubric'] = 'Beoordelingsschema van andere docent gebruiken';
$string['attachrubric'] = 'Een beoordelingsschema toevoegen aan deze opdracht';
$string['launchrubricmanager'] = 'Rubric Manager starten';
$string['attachrubricnote'] = 'Let op: studenten kunnen bijgevoegde beoordelingsschema&#39;s en de inhoud ervan bekijken alvorens in te dienen.';
$string['erater_handbook_advanced'] = 'Gevorderd';
$string['erater_handbook_highschool'] = 'Bovenbouw van middelbare school';
$string['erater_handbook_middleschool'] = 'Onderbouw van middelbare school';
$string['erater_handbook_elementary'] = 'Basisschool';
$string['erater_handbook_learners'] = 'Leerlingen Engels';
$string['erater_dictionary_enus'] = 'Amerikaans-Engels woordenboek';
$string['erater_dictionary_engb'] = 'Brits-Engels woordenboek';
$string['erater_dictionary_en'] = 'Zowel Amerikaans- als Brits-Engels woordenboek';
$string['erater'] = 'E-rater grammaticacontrole inschakelen';
$string['erater_handbook'] = 'ETS&copy; Handboek';
$string['erater_dictionary'] = 'e-rater Woordenboek';
$string['erater_categories'] = 'e-rater Categorieën';
$string['erater_spelling'] = 'Spelling';
$string['erater_grammar'] = 'Grammatica';
$string['erater_usage'] = 'Gebruik';
$string['erater_mechanics'] = 'Techniek';
$string['erater_style'] = 'Stijl';
$string['anonblindmarkingnote'] = 'Opmerking: de afzonderlijke Turnitin-instelling voor anonieme beoordeling is verwijderd. Turnitin gebruikt de Moodle-instelling voor blinde beoordeling om de instelling voor anonieme beoordeling te bepalen.';
$string['transmatch'] = 'Vertaald matchen';
$string['genduedate'] = 'Genereer rapporten op de opleverdatum (studenten mogen opnieuw indienen tot de opleverdatum)';
$string['genimmediately1'] = 'Genereer rapporten onmiddellijk (studenten mogen niet opnieuw indienen)';
$string['genimmediately2'] = 'Genereer onmiddellijk rapporten (opnieuw indienen is toegestaan voor studenten tot de opleverdatum): Na {$a->num_resubmissions} keer opnieuw indienen, worden na {$a->num_hours} uur rapporten gegenereerd';
$string['launchquickmarkmanager'] = 'Quickmark Manager starten';
$string['launchpeermarkmanager'] = 'Peermark Manager starten';
$string['studentreports'] = 'Originaliteitsrapporten weergeven voor studenten';
$string['studentreports_help'] = 'Hiermee kunt u originaliteitsrapporten van Turnitin tonen aan studenten. Indien &#39;Ja&#39; is geselecteerd, kunnen studenten originaliteitsrapporten bekijken die door Turnitin zijn gegenereerd.';
$string['submitondraft'] = 'Bestand verzenden wanneer het voor het eerst wordt geüpload';
$string['submitonfinal'] = 'Bestand verzenden wanneer student het ter beoordeling verzendt';
$string['draftsubmit'] = 'Wanneer moet het bestand worden ingediend bij Turnitin?';
$string['allownonor'] = 'Inzendingen van elk bestandstype toestaan?';
$string['allownonor_help'] = 'Met deze instelling kunnen alle bestandstypen worden ingediend. Als deze optie is ingesteld op &#34;Ja&#34;, worden inzendingen waar mogelijk gecontroleerd op originaliteit, zijn inzendingen beschikbaar om te worden gedownload zijn waar mogelijk GradeMark feedback-tools beschikbaar.';
$string['norepository'] = 'Geen online opslag';
$string['standardrepository'] = 'Standaard online opslag';
$string['submitpapersto'] = 'Papers van studenten opslaan';
$string['institutionalrepository'] = 'Online opslag van instelling (indien van toepassing)';
$string['checkagainstnote'] = 'Opmerking: als u &#39;Ja’ niet voor minimaal één &#39;Vergelijken met...&#39;-optie hieronder selecteert, wordt er GEEN Originaliteitsrapport gegenereerd.';
$string['spapercheck'] = 'Vergelijken met opgeslagen papers van studenten';
$string['internetcheck'] = 'Vergelijken met internet';
$string['journalcheck'] = 'Vergelijken met tijdschriften,<br />periodieken en publicaties';
$string['compareinstitution'] = 'Ingediende bestanden vergelijken met papers die zijn ingediend binnen deze onderwijsinstelling';
$string['reportgenspeed'] = 'Generatiesnelheid van rapport';
$string['locked_message'] = 'Vergrendeld bericht';
$string['locked_message_help'] = 'Als er instellingen zijn die zijn vergrendeld, wordt dit bericht weergegeven om uit te leggen waarom.';
$string['locked_message_default'] = 'Deze instelling is vergrendeld op siteniveau';
$string['sharedrubric'] = 'Gedeeld beoordelingsschema';
$string['turnitinrefreshsubmissions'] = 'Inzendingen vernieuwen';
$string['turnitinrefreshingsubmissions'] = 'Inzendingen vernieuwen';
$string['turnitinppulapre'] = 'Als u een bestand wilt indienen bij Turnitin, moet u eerst onze EULA accepteren. Als u ervoor kiest onze EULA niet te accepteren, wordt uw bestand alleen bij Moodle ingediend. Klik hier om de EULA te accepteren.';
$string['noscriptula'] = '(Aangezien u JavaScript niet hebt ingeschakeld, moet u deze pagina handmatig vernieuwen voordat u uw inzending opnieuw kunt indienen nadat u de Turnitin-gebruikersovereenkomst hebt geaccepteerd)';
$string['filedoesnotexist'] = 'Bestand is verwijderd';
$string['reportgenspeed_resubmission'] = 'U hebt al een paper voor deze opdracht ingezonden en er is een Similariteitsrapport gegenereerd voor deze inzending. Als u ervoor kiest om uw paper opnieuw te in te dienen, wordt uw eerdere inzending vervangen en wordt er een nieuw rapport gegenereerd. Als u iets {$a->num_resubmissions} opnieuw indient, moet u {$a->num_hours} uur wachten om een nieuw Similariteitsrapport te kunnen bekijken.';

// Plugin settings.
$string['config'] = 'Configuratie';
$string['defaults'] = 'Standaardinstellingen';
$string['showusage'] = 'Gegevensdump weergeven';
$string['saveusage'] = 'Gegevensdump opslaan';
$string['errors'] = 'Fouten';
$string['turnitinconfig'] = 'Configuratie van Turnitin-plagiaat-plugin';
$string['tiiexplain'] = 'Turnitin is een commercieel product en u moet betalen voor een abonnement om gebruik te maken van deze dienst. Zie <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a> voor meer informatie.';
$string['useturnitin'] = 'Turnitin inschakelen';
$string['useturnitin_mod'] = 'Turnitin inschakelen voor {$a}';
$string['pp_configuredesc'] = 'U moet deze module binnen de turnitintooltwo-module configureren. Klik <a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>hier</a> om deze plugin te configureren.';
$string['turnitindefaults'] = 'Standaardinstellingen voor plagiaat-plugin van Turnitin';
$string['defaultsdesc'] = 'De volgende instellingen worden standaard ingesteld wanneer Turnitin wordt ingeschakeld binnen een Activiteitenmodule';
$string['turnitinpluginsettings'] = 'Instellingen voor plagiaat-plugin van Turnitin';
$string['pperrorsdesc'] = 'Er is een probleem opgetreden bij het uploaden van de onderstaande bestanden naar Turnitin. Als u opnieuw wilt proberen deze in te dienen, dient u de bestanden te selecteren die u wilt indienen en op de knop Opnieuw indienen te klikken. De bestanden worden vervolgens verwerkt wanneer de cron de volgende keer wordt uitgevoerd.';
$string['pperrorssuccess'] = 'De bestanden die u hebt geselecteerd, zijn opnieuw ingediend en worden door de cron verwerkt.';
$string['pperrorsfail'] = 'Er is een probleem met enkele van de bestanden die u hebt geselecteerd. Er kan geen nieuwe cron-gebeurtenis worden gemaakt voor de bestanden.';
$string['resubmitselected'] = 'Geselecteerde bestanden opnieuw indienen';
$string['deleteconfirm'] = 'Weet u zeker dat u deze inzending wilt verwijderen?\n\nDeze actie kan niet ongedaan worden gemaakt.';
$string['deletesubmission'] = 'Inzending verwijderen';
$string['semptytable'] = 'Geen resultaten gevonden.';
$string['configupdated'] = 'Configuratie bijgewerkt';
$string['defaultupdated'] = 'Turnitin-standaardwaarden bijgewerkt';
$string['notavailableyet'] = 'Niet beschikbaar';
$string['resubmittoturnitin'] = 'Opnieuw indienen bij Turnitin';
$string['resubmitting'] = 'Opnieuw indienen';
$string['id'] = 'Id';
$string['student'] = 'Student';
$string['course'] = 'Cursus';
$string['module'] = 'Module';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'Originaliteitsrapport bekijken';
$string['launchrubricview'] = 'Bekijk het beoordelingsschema (rubric) dat is gebruikt voor het beoordelen';
$string['turnitinppulapost'] = 'Uw bestand is niet ingediend bij Turnitin. Klik hier om onze EULA te accepteren.';
$string['ppsubmissionerrorseelogs'] = 'Dit bestand is niet ingediend bij Turnitin. Raadplaag uw systeembeheerder voor meer informatie.';
$string['ppsubmissionerrorstudent'] = 'Dit bestand is niet ingediend bij Turnitin. Raadplaag uw privédocent voor meer informatie.';

// Receipts.
$string['messageprovider:submission'] = 'Meldingen over digitaal ontvangstbewijs voor Turnitin-plagiaat-plugin';
$string['digitalreceipt'] = 'Digitaal ontvangstbewijs';
$string['digital_receipt_subject'] = 'Dit is uw digitaal ontvangstbewijs van Turnitin';
$string['pp_digital_receipt_message'] = 'Beste {$a->firstname} {$a->lastname},<br /><br />U hebt het bestand <strong>{$a->submission_title}</strong> ingediend bij de opdracht <strong>{$a->assignment_name}{$a->assignment_part}</strong> in de cursus <strong>{$a->course_fullname}</strong> op <strong>{$a->submission_date}</strong>. Uw inzendings-id is <strong>{$a->submission_id}</strong>. Uw volledige digitaal ontvangstbewijs kunt u zien en afdrukken door in Document Viewer te drukken op de knop voor afdrukken/downloaden.<br /><br />Bedankt dat u Turnitin gebruikt,<br /><br />Het Turnitin-team';

// Paper statuses.
$string['turnitinid'] = 'Turnitin-id';
$string['turnitinstatus'] = 'Turnitin-status';
$string['pending'] = 'In afwachting van behandeling';
$string['similarity'] = 'Vergelijkbaarheid';
$string['notorcapable'] = 'Het is niet mogelijk om een originaliteitsrapport te genereren voor dit bestand.';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'De student heeft de paper bekeken op:';
$string['student_notread'] = 'De student heeft de paper niet bekeken.';
$string['launchpeermarkreviews'] = 'Peermark-evaluaties starten';

// Cron.
$string['ppqueuesize'] = 'Aantal gebeurtenissen in de wachtrij met Plagiaat-Plugin-gebeurtenissen';
$string['ppcronsubmissionlimitreached'] = 'Er worden verder geen inzendingen meer verzonden naar Turnitin door deze cron-uitvoering omdat er slechts {$a} per keer worden verwerkt';
$string['cronsubmittedsuccessfully'] = 'Inzending: {$a->title} (TII-id: {$a->submissionid}) voor de opdracht {$a->assignmentname} op de cursus {$a->coursename} is ingediend bij Turnitin.';
$string['pp_submission_error'] = 'Turnitin heeft een fout geretourneerd voor uw inzending:';
$string['turnitindeletionerror'] = 'Verwijderen van Turnitin-inzending mislukt. De lokale Moodle-kopie is verwijderd, maar de inzending in Turnitin kan niet worden verwijderd.';
$string['ppeventsfailedconnection'] = 'Er worden geen gebeurtenissen verwerkt door de Turnitin-plagiaat-plugin als gevolg van het uitvoeren van deze cron, aangezien er geen verbinding met Turnitin tot stand kan worden gebracht.';

// Error codes.
$string['tii_submission_failure'] = 'Neem contact op met uw privédocent of systeembeheerder voor meer informatie';
$string['faultcode'] = 'Foutcode';
$string['line'] = 'Lijn';
$string['message'] = 'Bericht';
$string['code'] = 'Code';
$string['tiisubmissionsgeterror'] = 'Er is een fout opgetreden tijdens het bij Turnitin ophalen van inzendingen voor deze opdracht';
$string['errorcode0'] = 'Dit bestand is niet ingediend bij Turnitin. Raadplaag uw systeembeheerder voor meer informatie.';
$string['errorcode1'] = 'Dit bestand is niet naar Turnitin verzonden omdat het bestand onvoldoende inhoud heeft om een originaliteitsrapport te produceren.';
$string['errorcode2'] = 'Dit bestand kan niet worden ingediend bij Turnitin omdat het bestand de maximaal toegestane grootte van {$a->maxfilesize} overschrijdt';
$string['errorcode3'] = 'Dit bestand is niet ingediend bij Turnitin omdat de gebruiker de Eindgebruikersovereenkomst van Turnitin niet heeft geaccepteerd.';
$string['errorcode4'] = 'U dient een bestand van een ondersteund type te uploaden voor deze opdracht. Geaccepteerde bestandstypen zijn; .doc, .docx, .ppt, .pptx, .pps, .ppsx, .pdf, .txt, .htm, .html, .hwp, .odt, .wpd, .ps en .rtf';
$string['errorcode5'] = 'Dit bestand is niet naar Turnitin verzonden omdat er een probleem is met het maken van de module in Turnitin die inzendingen verhindert. Raadpleeg uw API-logbestanden voor meer informatie.';
$string['errorcode6'] = 'Dit bestand is niet naar Turnitin verzonden omdat er een probleem is met het bewerken van de module in Turnitin, waardoor inzenden niet mogelijk is. Raadpleeg uw API-logbestanden voor meer informatie.';
$string['errorcode7'] = 'Dit bestand is niet naar Turnitin verzonden omdat er een probleem is met het maken van de gebruiker in Turnitin, waardoor inzenden niet mogelijk is. Raadpleeg uw API-logbestanden voor meer informatie.';
$string['errorcode8'] = 'Dit bestand is niet naar Turnitin verzonden omdat er een probleem is met het maken van het tijdelijke bestand. De meest waarschijnlijke oorzaak is een ongeldige bestandsnaam. Wijzig de naam van het bestand en upload opnieuw via Inzending bewerken.';
$string['errorcode9'] = 'Dit bestand kan niet worden verzonden omdat er geen toegankelijke inhoud is in de bestandspool om te verzenden.';
$string['coursegeterror'] = 'Kan cursusgegevens niet ophalen';
$string['configureerror'] = 'U moet deze module volledig configureren als beheerder voordat u deze kunt gebruiken in een cursus. Neem contact op met uw Moodle-beheerder.';
$string['turnitintoolofflineerror'] = 'Er is een tijdelijke fout opgetreden. Probeer het later opnieuw.';
$string['defaultinserterror'] = 'Er is een fout opgetreden bij een poging een standaardinstellingswaarde in te voegen in de database';
$string['defaultupdateerror'] = 'Er is een fout opgetreden bij een poging een standaardinstellingswaarde bij te werken in de database';
$string['tiiassignmentgeterror'] = 'Er is een fout opgetreden bij het ophalen van een opdracht van Turnitin';
$string['assigngeterror'] = 'Kan turnitintooltwo-gegevens niet ophalen';
$string['classupdateerror'] = 'Kan Turnitin-cursusgegevens niet bijwerken';
$string['pp_createsubmissionerror'] = 'Er is een fout opgetreden bij een poging de inzending in Turnitin te maken';
$string['pp_updatesubmissionerror'] = 'Er is een fout opgetreden bij een poging uw inzending opnieuw in te dienen bij Turnitin';
$string['tiisubmissiongeterror'] = 'Er is een fout opgetreden bij het ophalen van een inzending uit Turnitin';

// Javascript.
$string['closebutton'] = 'Sluiten';
$string['loadingdv'] = 'Turnitin Document Viewer laden...';
$string['changerubricwarning'] = 'Bij het wijzigen of losmaken van een rubric, of beoordelingsschema, worden alle bestaande rubric-scores van papers binnen deze opdracht verwijderd, met inbegrip van scorekaarten die eerder van opmerkingen zijn voorzien. De totale cijfers voor eerder gecorrigeerde papers blijven bewaard.';
$string['messageprovider:submission'] = 'Meldingen over digitaal ontvangstbewijs voor Turnitin-plagiaat-plugin';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Turnitin-status';
$string['deleted'] = 'Verwijderd';
$string['pending'] = 'In afwachting van behandeling';
$string['because'] = 'Dit komt doordat een beheerder de opdracht, die in afwachting van behandeling was, uit de verwerkingswachtrij heeft verwijderd en de inzending naar Turnitin heeft afgebroken.<br /><strong>Het bestand bestaat nog in Moodle. Neem contact op met uw docent.</strong><br />Foutcodes staan hieronder:';
$string['submitpapersto_help'] = '<strong>Geen online opslag: </strong><br />Turnitin heeft de opdracht ontvangen om ingediende documenten in geen enkele bibliotheek op te slaan. We verwerken de paper alleen om de eerste controle op overeenkomsten uit te kunnen voeren.<br /><br /><strong>Standaard online opslag: </strong><br />Turnitin slaat alleen in de standaardbibliotheek een kopie op van het ingediende document. Door deze optie te kiezen, krijgt Turnitin de opdracht om alleen opgeslagen documenten te gebruiken om te controleren op overeenkomsten tussen alle documenten die in de toekomst worden ingediend. <br /><br /><strong>Online opslag van instelling (indien van toepassing): </strong><br />Als er voor deze optie wordt gekozen, krijgt Turnitin de opdracht om alleen ingediende documenten toe te voegen aan een privébibliotheek van uw instelling. Controles op overeenkomsten tussen de ingediende documenten worden alleen uitgevoerd door andere instructeurs binnen uw instelling.';
$string['errorcode12'] = 'Dit bestand is niet verzonden naar Turnitin omdat het bij een opdracht hoort waarvan de cursus is verwijderd. Serie-ID: ({$a->id}) | Cursusmodule-ID: ({$a->cm}) | Gebruikers-ID: ({$a->userid})';
