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
$string['pluginname'] = 'Turnitin plagiatplugin';
$string['turnitintooltwo'] = 'Turnitin-verktyg';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Pluginuppgiften plagiat i Turnitin';
$string['connecttesterror'] = 'Det uppstod ett fel vid försök att ansluta till Turnitin. Inkommande felmeddelande visas nedan:<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'Aktivera Turnitin';
$string['excludebiblio'] = 'Exkludera källförteckning';
$string['excludequoted'] = 'Exkludera citerat material';
$string['excludevalue'] = 'Exkludera mindre matchningar';
$string['excludewords'] = 'Ord';
$string['excludepercent'] = 'Procent';
$string['norubric'] = 'Ingen bedömningsmatris';
$string['otherrubric'] = 'Använd en bedömnigsmatris som tillhör andra instruktörer';
$string['attachrubric'] = 'Bifoga en bedömningsmatris till denna uppgift';
$string['launchrubricmanager'] = 'Starta bedömningsmatrishanteraren';
$string['attachrubricnote'] = 'Observera: Studenterna kommer att kunna se bifogade bedömningsmatriser och deras innehåll innan de lämnar in.';
$string['erater_handbook_advanced'] = 'Avancerad';
$string['erater_handbook_highschool'] = 'Gymnasium';
$string['erater_handbook_middleschool'] = 'Grundskola (11-13 år)';
$string['erater_handbook_elementary'] = 'Grundskola (5-10 år)';
$string['erater_handbook_learners'] = 'Studenter som lär sig Engelska';
$string['erater_dictionary_enus'] = 'Lexikon för Amerikansk Engelska';
$string['erater_dictionary_engb'] = 'Lexikon för Brittisk Engelska';
$string['erater_dictionary_en'] = 'Lexikon för både amerikansk och brittisk engelska';
$string['erater'] = 'Aktivera e-rater grammatikkontroll';
$string['erater_handbook'] = 'ETS&copy; Handbok';
$string['erater_dictionary'] = 'e-rater Lexikon';
$string['erater_categories'] = 'e-rater Kategorier';
$string['erater_spelling'] = 'Stavning';
$string['erater_grammar'] = 'Grammatik';
$string['erater_usage'] = 'Användning';
$string['erater_mechanics'] = 'Teknik';
$string['erater_style'] = 'Stil';
$string['anonblindmarkingnote'] = 'Obs: Den separata Turnitin-funktionen anonyma kommentarer har tagits bort. Turnitin kommer att använda Moodles blindkommentarer för att avgöra om funktionen anonyma kommentarer ska användas.';
$string['transmatch'] = 'Matchande översättning';
$string['genduedate'] = 'Skapa rapporter på förfallodatum (återinlämningar tillåts fram till förfallodatum)';
$string['genimmediately1'] = 'Skapa rapporter omedelbart (återinlämningar tillåts inte)';
$string['genimmediately2'] = 'Skapa rapporter omedelbart (studenter kan återinlämna fram till förfallodatum) Efter {$a->num_resubmissions} återinlämningar skapas rapporter efter {$a->num_hours} timmar';
$string['launchquickmarkmanager'] = 'Starta Quickmark-hanteraren';
$string['launchpeermarkmanager'] = 'Starta Peermark Hanteraren';
$string['studentreports'] = 'Visa originalitetsrapporter för studenter';
$string['studentreports_help'] = 'Låter dig visa Turnitin originalitetsrapporter till studentanvändare. Om inställd på ja kommer originalitetsrapporten som genereras av Turnitin att bli tillgänglig för studenten att se.';
$string['submitondraft'] = 'Lämna in filen när den har laddats upp';
$string['submitonfinal'] = 'Lämna in filen när den har laddats upp';
$string['draftsubmit'] = 'När ska filen lämnas in till Turnitin?';
$string['allownonor'] = 'Tillåt inlämning av alla filtyper?';
$string['allownonor_help'] = 'Denna inställning gör så att alla filtyper kan lämnas in. Med det här alternativet inställt på &#34;Ja&#34; kommer inlämningar att kontrolleras för originalitet när så är möjligt, inlämningar kommer att finnas tillgängliga för nedladdning, och verktyg för GradeMark-respons kommer att finnas tillgängliga när detta är möjligt.';
$string['norepository'] = 'Inget arkiv';
$string['standardrepository'] = 'Standardarkiv';
$string['submitpapersto'] = 'Lagra studentuppsatser';
$string['institutionalrepository'] = 'Institutionellt arkiv (Om tillämpligt)';
$string['checkagainstnote'] = 'Obs. Om du inte väljer "Ja" för minst en av alternativen under "Kontrollera mot..." kommer en originalitetsrapport inte att skapas.';
$string['spapercheck'] = 'Kontrollera mot lagrade studentuppsatser';
$string['internetcheck'] = 'Kontrollera mot internet';
$string['journalcheck'] = 'Kontrollera mot facktidskrifter,<br />tidningar och publikationer';
$string['compareinstitution'] = 'Jämför inlämnade filer med uppsatser som lämnats in inom denna institution';
$string['reportgenspeed'] = 'Rapportera generationshastighet';
$string['locked_message'] = 'Låst meddelande';
$string['locked_message_help'] = 'Om några inställningar låses kommer detta meddelande att visa varför.';
$string['locked_message_default'] = 'Den här inställningen låses på anläggningsnivå';
$string['sharedrubric'] = 'Delad rubrik';
$string['turnitinrefreshsubmissions'] = 'Uppdatera inlämningar';
$string['turnitinrefreshingsubmissions'] = 'Uppdaterar inlämningar';
$string['turnitinppulapre'] = 'För att lämna in en fil till Turnitin måste du först godkänna våra användaravtal. Om du inte godkänner våra användaravtal kommer din fil endast att lämnas in till Moodle. Klicka här för att godkänna.';
$string['noscriptula'] = 'Eftersom du inte har JavaScript aktiverat måste du manuellt uppdatera den här sidan innan du kan göra en inlämning (efter att du accepterat Turnitins Användaravtal)';
$string['filedoesnotexist'] = 'Filen har raderats';
$string['reportgenspeed_resubmission'] = 'Du har redan lämnat in en uppsats i den här uppgiften och en Likhetsrapport skapades för din inlämning. Om du väljer att lämna in din uppsats igen kommer din tidigare inlämning att ersättas och en ny rapport kommer att skapas. Efter {$a->num_resubmissions} återinlämningar behöver du vänta {$a->num_hours} timmar efter en återinlämning för att kunna se en ny Likhetsrapport.';

// Plugin settings.
$string['config'] = 'Konfigurering';
$string['defaults'] = 'Standardinställningar';
$string['showusage'] = 'Visa datadump';
$string['saveusage'] = 'Spara datadump';
$string['errors'] = 'Fel';
$string['turnitinconfig'] = 'Konfiguration av plagiatplugin i Turnitin';
$string['tiiexplain'] = 'Turnitin är en kommersiell produkt och du måste ha ett betalt abonnemang för att använda den här tjänsten. För mer information se <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>';
$string['useturnitin'] = 'Aktivera Turnitin';
$string['useturnitin_mod'] = 'Aktivera Turnitin för {$a}';
$string['pp_configuredesc'] = 'Du måste konfigurera denna modul inom turnitintooltwo modulen. Var god klicka <a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>här</a> för att konfigurera denna plugin';
$string['turnitindefaults'] = 'Turnitin plagiatplugin, standardinställningar';
$string['defaultsdesc'] = 'Följande inställningar är standardvärden som fastställs vid aktivering av Turnitin inom en Aktivitetsmodul';
$string['turnitinpluginsettings'] = 'Turnitin plagiatplugin, inställningar';
$string['pperrorsdesc'] = 'Det finns ett problem med att ladda upp nedanstående filer till Turnitin. För att lämna in igen markerar du filerna du vill lämna in och trycker på inlämningsknappen. De kommer sedan att bearbetas nästa gång Cron körs.';
$string['pperrorssuccess'] = 'De valda filerna har lämnats in och kommer att bearbetas av Cron.';
$string['pperrorsfail'] = 'Ett problem uppståd med några av de valda filerna. En ny Cron-händelse kunde inte skapas för dem.';
$string['resubmitselected'] = 'Lämna in valda filer på nytt';
$string['deleteconfirm'] = 'Är du säker på att du vill radera denna inlämning?\n\nDenna åtgärd kan inte ångras.';
$string['deletesubmission'] = 'Radera inlämning';
$string['semptytable'] = 'Inga sökresultat hittades.';
$string['configupdated'] = 'Konfiguration uppdaterad';
$string['defaultupdated'] = 'Turnitin standardinställningar uppdaterade';
$string['notavailableyet'] = 'Ej tillgänglig';
$string['resubmittoturnitin'] = 'Lämna in igen till Turnitin';
$string['resubmitting'] = 'Ominlämning';
$string['id'] = 'ID';
$string['student'] = 'Student';
$string['course'] = 'Kurs';
$string['module'] = 'Modul';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'Visa Originalitetsrapport';
$string['launchrubricview'] = 'Visa bedömningsmatrisen som används för kommentering';
$string['turnitinppulapost'] = 'Din fil har inte lämnats in till Turnitin. Klicka här för att godkänna våra användaravtal.';
$string['ppsubmissionerrorseelogs'] = 'Den här filen har inte skickas till Turnitin. Be din systemadministratör om hjälp.';
$string['ppsubmissionerrorstudent'] = 'Den här filen har inte skickas till Turnitin. Be din lärare om hjälp.';

// Receipts.
$string['messageprovider:submission'] = 'Meddelanden för digitala kvitton för plagiatplugin från Turnitin';
$string['digitalreceipt'] = 'Digitalt kvitto';
$string['digital_receipt_subject'] = 'Det här är ditt digitala kvitto från Turnitin';
$string['pp_digital_receipt_message'] = 'Hej {$a->firstname} {$a->lastname}!<br /><br />Du har lämnat in filen <strong>{$a->submission_title}</strong> för uppgift <strong>{$a->assignment_name}{$a->assignment_part}</strong> för kursen <strong>{$a->course_fullname}</strong> den <strong>{$a->submission_date}</strong>. Ditt inlämnings-ID är <strong>{$a->submission_id}</strong>. Ditt fullständiga digitala kvitto kan visas och skrivas ut med knappen skriv ut/ladda ner i dokumentvisaren.<br /><br />Tack för att du använder Turnitin,<br /><br />Turnitin-teamet';

// Paper statuses.
$string['turnitinid'] = 'Turnitin-ID';
$string['turnitinstatus'] = 'Turnitin-status';
$string['pending'] = 'Väntar på bekräftelse';
$string['similarity'] = 'Likhetsindex';
$string['notorcapable'] = 'Det går inte att skapa en originalitetsrapport för den här filen.';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'Studenten visade uppsatsen den:';
$string['student_notread'] = 'Studenten har inte visat denna uppsats.';
$string['launchpeermarkreviews'] = 'Starta Peermark-recensioner';

// Cron.
$string['ppqueuesize'] = 'Antal händelser i händelsekön för plagiatplugin';
$string['ppcronsubmissionlimitreached'] = 'Inga fler inlämningar kommer att skickas till Turnitin av denna Cron-aktivitet, eftersom endast {$a} bearbetas per körning';
$string['cronsubmittedsuccessfully'] = 'Inlämning: {$a->title} (TII ID: {$a->submissionid}) för uppgift {$a->assignmentname} på kursen {$a->coursename} har lämnats in till Turnitin.';
$string['pp_submission_error'] = 'Ett fel har uppstått hos Turnitin i samband med inlämningen:';
$string['turnitindeletionerror'] = 'Radering av inlämning för Turnitin misslyckades. Den lokala Moodle-kopian har tagits bort men inlämningen i Turnitin kunde inte raderas.';
$string['ppeventsfailedconnection'] = 'Inga händelser kommer att bearbetas av plagiatplugin i Turnitin för den här Cron-aktiviteten eftersom det inte gick att ansluta till Turnitin.';

// Error codes.
$string['tii_submission_failure'] = 'Vänd dig till din handledare eller administratör för mer information.';
$string['faultcode'] = 'Felkod';
$string['line'] = 'Rad';
$string['message'] = 'Meddelande';
$string['code'] = 'Kod';
$string['tiisubmissionsgeterror'] = 'Det uppstod ett fel vid försök att hämta inlämningar för denna Turnitin uppgift';
$string['errorcode0'] = 'Den här filen har inte skickas till Turnitin. Be din systemadministratör om hjälp.';
$string['errorcode1'] = 'Filen har inte skickats till Turnitin eftersom den inte har tillräckligt mycket innehåll för att skapa en originalitetsrapport.';
$string['errorcode2'] = 'Den här filen kommer inte att skickas till Turnitin eftersom den överstiger högsta tillåtna storlek på {$a->maxfilesize}.';
$string['errorcode3'] = 'Den här filen kunde inte skickas till Turnitin eftersom användaren inte har godkänt Turnitins licensavtal för slutanvändare.';
$string['errorcode4'] = 'Du måste ladda upp en filtyp som stöds för den här uppgiften. Godkända filtyper är: .doc, .docx, .ppt, .pptx, .pps, .ppsx, .pdf, .txt, .htm, .html, .hwp, .odt, .wpd, .ps och .rtf';
$string['errorcode5'] = 'Filen har inte lämnats in till Turnitin eftersom ett fel uppstod när modulen skapades i Turnitin, vilket förhindrar inlämningar. Mer information finns i dina API-loggar';
$string['errorcode6'] = 'Filen har inte lämnats in till Turnitin eftersom ett fel uppstod när modulinställningarna redigerades i Turnitin, vilket förhindrar inlämningar. Mer information finns i dina API-loggar.';
$string['errorcode7'] = 'Filen har inte lämnats in till Turnitin eftersom ett fel uppstod när användaren skapades i Turnitin, vilket förhindrar inlämningar. Mer information finns i dina API-loggar.';
$string['errorcode8'] = 'Filen har inte lämnats in till Turnitin eftersom ett fel uppstod när den tillfälliga filen skulle skapas. Detta beror sannolikt på ett felaktigt filnamn. Ge filen ett nytt namn och ladda upp den igen med Redigera inlämning.';
$string['errorcode9'] = 'Filen kan inte lämnas in eftersom det inte finns något tillgängligt innehåll i filpoolen att lämna in.';
$string['coursegeterror'] = 'Kunde inte hämta kursinformation';
$string['configureerror'] = 'Du måste konfigurera denna modul fullt ut som Administratör innan du använder den i en kurs. Var god kontakta din Moodle-administratör.';
$string['turnitintoolofflineerror'] = 'Vi har ett tillfälligt problem. Försök igen om en liten stund.';
$string['defaultinserterror'] = 'Det uppstod ett fel vid försök att infoga en standardvärdeinställning i databasen';
$string['defaultupdateerror'] = 'Det uppstod ett fel vid försök att uppdatera en standardvärdeinställning i databasen';
$string['tiiassignmentgeterror'] = 'Det uppstod ett fel vid försök att hämta en uppgift från Turnitin';
$string['assigngeterror'] = 'Gick inte att hämta turnitintooltwo data';
$string['classupdateerror'] = 'Kunde inte uppdatera Turnitin-klassinformation';
$string['pp_createsubmissionerror'] = 'Det uppstod ett fel vid försök att skapa en inlämning i Turnitin';
$string['pp_updatesubmissionerror'] = 'Det uppstod ett fel vid försök att återinlämna din uppgift till Turnitin';
$string['tiisubmissiongeterror'] = 'Det uppstod ett fel vid försök att hämta ett inlämnande från Turnitin';

// Javascript.
$string['closebutton'] = 'Stäng';
$string['loadingdv'] = 'Laddar dokumentvisaren i Turnitin...';
$string['changerubricwarning'] = 'Om du ändrar eller tar bort en bedömningsmatris kommer detta att avlägsna all existerande bedömningsmatris-poäng från samtliga uppsatser i denna uppgift, inklusive poängkort som har markerats tidigare. Sammanlagda betyg för tidigare betygsatta uppsatser kommer att finnas kvar.';
$string['messageprovider:submission'] = 'Meddelanden för digitala kvitton för plagiatplugin från Turnitin';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Turnitin-status';
$string['deleted'] = 'Raderad';
$string['pending'] = 'Väntar på bekräftelse';
$string['because'] = 'Detta beror på att administratören raderade uppgiften som väntade på bekräftelse från uppgiftskön och avbröt inlämningen till Turnitin.<br /><strong>Filen finns fortfarande i Moodle. Kontakta din lärare.</strong><br />Se nedanstående felkoder:';
$string['submitpapersto_help'] = '<strong>Inget arkiv: </strong><br />Enligt anvisning sparar Turnitin inte dokument i något arkiv. Uppsatser behandlas endast för den första likhetsgranskningen.<br /><br /><strong>Standardarkiv: </strong><br />Turnitin sparar en kopia av det inlämnade dokumentet endast i standardarkivet. Om alternativet väljs, använder Turnitin endast sparade dokument i framtida likhetsgranskningar av andra dokument.<br /><br /><strong>Institutionellt arkiv (Om tillämpligt): </strong><br />Om alternativet väljs, sparar Turnitin inlämnade dokument endast i institutionens privata arkiv. Likhetsgranskningar av inlämnade dokument görs endast av andra instruktörer vid institutionen.';
$string['errorcode12'] = 'Den här filen har inte skickats till Turnitin eftersom den tillhör en uppgift där kursen har tagits bort. Rad-ID: ({$a->id}) | Kursmodul-ID: ({$a->cm}) | Användar-ID: ({$a->userid})';
