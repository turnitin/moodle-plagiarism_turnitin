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
$string['pluginname'] = 'Turnitin-Plugin zur Plagiarismuserkennung';
$string['turnitintooltwo'] = 'Turnitin-Tool';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Turnitin-Plugin zur Plagiarismuserkennung – Aufgabe';
$string['connecttesterror'] = 'Bei der Verbindung mit Turnitin ist ein Fehler aufgetreten, siehe Fehlermeldung:<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'Turnitin aktivieren';
$string['excludebiblio'] = 'Bibliografie ausschließen';
$string['excludequoted'] = 'Zitiertes Material ausschließen';
$string['excludevalue'] = 'Geringfügige Übereinstimmungen ausschließen';
$string['excludewords'] = 'Wörter';
$string['excludepercent'] = 'Prozent';
$string['norubric'] = 'Keine Rubrik';
$string['otherrubric'] = 'Rubrik einer anderen Lehrkraft verwenden';
$string['attachrubric'] = 'Dieser Aufgabe eine Rubrik anhängen';
$string['launchrubricmanager'] = 'Rubrikmanager starten';
$string['attachrubricnote'] = 'Hinweis: Studenten können angehängte Rubriken und deren Inhalt vor dem Übermitteln aufrufen.';
$string['erater_handbook_advanced'] = 'Erweitert';
$string['erater_handbook_highschool'] = 'Oberstufe';
$string['erater_handbook_middleschool'] = 'Mittelstufe';
$string['erater_handbook_elementary'] = 'Grundschule';
$string['erater_handbook_learners'] = 'Englisch Lernende';
$string['erater_dictionary_enus'] = 'US-amerikanisches Englisch-Lexikon';
$string['erater_dictionary_engb'] = 'Britisches Englisch-Lexikon';
$string['erater_dictionary_en'] = 'Britisches und US-amerikanisches Englisch-Lexikon';
$string['erater'] = 'e-rater-Grammatikprüfung aktivieren';
$string['erater_handbook'] = 'ETS&copy;-Handbuch';
$string['erater_dictionary'] = 'e-rater-Wörterbuch';
$string['erater_categories'] = 'e-rater-Kategorien';
$string['erater_spelling'] = 'Rechtschreibung';
$string['erater_grammar'] = 'Grammatik';
$string['erater_usage'] = 'Gebrauch';
$string['erater_mechanics'] = 'Funktionsweise';
$string['erater_style'] = 'Stil';
$string['anonblindmarkingnote'] = 'Hinweis: Die separate Turnitin-Einstellung für anonyme Benotung wurde entfernt. Turnitin legt die Einstellung für anonymes Benoten anhand der Moodle-Einstellung für Blindbewertung fest.';
$string['transmatch'] = 'Übersetzte Übereinstimmung';
$string['genduedate'] = 'Berichte am Fälligkeitsdatum erstellen (erneute Übermittlungen sind bis zum Fälligkeitsdatum zulässig)';
$string['genimmediately1'] = 'Berichte sofort erstellen (erneute Übermittlungen sind nicht zulässig)';
$string['genimmediately2'] = 'Berichte sofort erstellen (erneute Übermittlungen sind bis zum Fälligkeitsdatum zulässig)';
$string['launchquickmarkmanager'] = 'QuickMark-Manager starten';
$string['launchpeermarkmanager'] = 'PeerMark-Manager starten';
$string['studentreports'] = 'Studenten den Echtheitsbericht anzeigen';
$string['studentreports_help'] = 'Ermöglicht Studenten das Anzeigen von Turnitin-Echtheitsberichten. Wenn Sie die Option "Ja" wählen, können Studenten den von Turnitin erstellten Echtheitsbericht ansehen.';
$string['submitondraft'] = 'Datei beim ersten Hochladen übermitteln';
$string['submitonfinal'] = 'Datei übermitteln, wenn der Student sie zum Markieren sendet.';
$string['draftsubmit'] = 'Wann muss die Datei an Turnitin übermittelt werden?';
$string['allownonor'] = 'Jeden Dateityp zur Übermittlung zulassen?';
$string['allownonor_help'] = 'Mit dieser Einstellung können alle Dateitypen übermitteln werden. Ist diese Option auf &#34;Ja&#34; gesetzt, werden Übermittlungen ggf. auf ihre Echtheit überprüft und zum Download bereitgestellt. Außerdem stehen wenn möglich GradeMark-Feedbacktools zur Verfügung.';
$string['norepository'] = 'Kein Repository';
$string['standardrepository'] = 'Standard-Repository ';
$string['submitpapersto'] = 'Studentenarbeiten ablegen';
$string['institutionalrepository'] = 'Institutions-Repository (wenn vorhanden)';
$string['submitpapersto_help'] = 'Mit dieser Einstellung können Lehrkräfte festlegen, ob Arbeiten in einem Turnitin-Repository für Studentenarbeiten gespeichert werden. Der Vorteil einer Übermittlung von Arbeiten das Studentenarbeits-Repository besteht darin, dass die zu einer Aufgabe übermittelten Studentenarbeiten mit den Arbeiten von Studenten&#39; Ihrer aktuellen sowie früheren Kurse abgeglichen werden. Wenn Sie die Option &#34;Kein Repository&#34; wählen, werden die Arbeiten Ihrer Studenten&#39; nicht im Studentenarbeits-Repository von Turnitin gespeichert.';
$string['checkagainstnote'] = 'Hinweis: Wenn Sie nicht für mindestens eine der folgenden Abgleichoptionen „Ja“ auswählen, wird KEIN Echtheitsbericht generiert.';
$string['spapercheck'] = 'Abgleich mit vorhandenen Studentenarbeiten';
$string['internetcheck'] = 'Abgleich mit dem Internet';
$string['journalcheck'] = 'Abgleich mit Zeitungen,<br />Periodika und anderen Publikationen';
$string['compareinstitution'] = 'Eingereichte Dateien mit den an dieser Institution übermittelten Arbeiten vergleichen';
$string['reportgenspeed'] = 'Geschwindigkeit beim Erstellen des Berichts';
$string['genspeednote'] = 'Hinweis: Bei der Generierung eines Echtheitsberichts für erneute Übermittlungen kann eine Verzögerung von 24 Stunden auftreten.';
$string['locked_message'] = 'Gesperrte Nachricht';
$string['locked_message_help'] = 'Wenn Einstellungen gesperrt sind, wird in dieser Nachricht der Grund dafür angegeben.';
$string['locked_message_default'] = 'Diese Einstellung ist auf Websiteebene gesperrt.';
$string['sharedrubric'] = 'Freigegebene Rubrik';
$string['turnitinrefreshsubmissions'] = 'Übermittlungen aktualisieren';
$string['turnitinrefreshingsubmissions'] = 'Übermittlungen werden aktualisiert...';
$string['turnitinppulapre'] = 'Vor der Übermittung einer Datei an Turnitin müssen Sie unsere EULA (Endbenutzer-Lizenzvereinbarung) akzeptieren. Wenn Sie die EULA nicht akzeptieren, wird Ihre Datei nur an Moodle übermittelt. Klicken Sie zum Akzeptieren hier.';
$string['noscriptula'] = '(Da Sie Javascript nicht aktiviert haben, müssen Sie diese Seite manuell aktualisieren, ehe Sie nach dem Akzeptieren der Nutzungsbedingungen von Turnitin eine Übermittlung vornehmen können)';
$string['filedoesnotexist'] = 'Datei wurde gelöscht';

// Plugin settings.
$string['config'] = 'Konfiguration';
$string['defaults'] = 'Standardeinstellungen';
$string['showusage'] = 'Datenspeicher anzeigen';
$string['saveusage'] = 'Datenanzeige sichern';
$string['errors'] = 'Fehler';
$string['turnitinconfig'] = 'Konfiguration für das Turnitin-Plug-in gegen Plagiarismus';
$string['tiiexplain'] = 'Turnitin ist ein kommerzielles Produkt, und Sie benötigen ein zahlungspflichtiges Abonnement, um diesen Dienst nutzen zu können. Weitere Informationen finden Sie unter <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>.';
$string['useturnitin'] = 'Turnitin aktivieren';
$string['useturnitin_mod'] = 'Turnitin aktivieren für {$a}';
$string['pp_configuredesc'] = 'Sie müssen dieses Modul innerhalb des turnitintooltwo-Moduls konfigurieren. Klicken Sie <a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>hier</a>, um dieses Plug-in zu konfigurieren.';
$string['turnitindefaults'] = 'Standardeinstellungen für das Turnitin-Plugin zur Plagiarismuserkennung';
$string['defaultsdesc'] = 'Die folgenden Einstellungen bilden den Standard, wenn Turnitin mit einem Aktivitätsmodul aktiviert ist.';
$string['turnitinpluginsettings'] = 'Einstellungen für das Turnitin-Plugin zur Plagiarismuserkennung';
$string['pperrorsdesc'] = 'Beim Versuch, die folgenden Dateien bei Turnitin hochzuladen, ist ein Problem aufgetreten. Wählen Sie für eine erneute Übermittlung die gewünschten Dateien aus, und klicken Sie auf die Schaltfläche „Erneut übermitteln“. Diese Dateien werden dann bei der nächsten Cron-Ausführung verarbeitet.';
$string['pperrorssuccess'] = 'Die ausgewählten Dateien wurden erneut übermittelt und werden von Cron verarbeitet.';
$string['pperrorsfail'] = 'Bei einigen der ausgewählten Dateien ist ein Problem aufgetreten. Für diese Dateien konnte kein neues Cron-Ereignis erstellt werden.';
$string['resubmitselected'] = 'Ausgewählte Dateien erneut übermitteln';
$string['deleteconfirm'] = 'Möchten Sie diese Übermittlung wirklich löschen? \n\nDieser Vorgang kann nicht rückgängig gemacht werden.';
$string['deletesubmission'] = 'Übermittlung löschen';
$string['semptytable'] = 'Keine Ergebnisse vorhanden.';
$string['configupdated'] = 'Konfiguration aktualisiert';
$string['defaultupdated'] = 'Turnitin-Standards aktualisiert';
$string['notavailableyet'] = 'Nicht verfügbar';
$string['resubmittoturnitin'] = 'Erneut an Turnitin übermitteln';
$string['resubmitting'] = 'Wird erneut übermittelt...';
$string['id'] = 'ID';
$string['student'] = 'Student';
$string['course'] = 'Kurs';
$string['module'] = 'Module';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'Echtheitsbericht anzeigen';
$string['launchrubricview'] = 'Die für das Markieren verwendete Rubik anzeigen';
$string['turnitinppulapost'] = 'Ihre Datei wurde nicht an Turnitin übermittelt. Klicken Sie hier, um unsere EULA zu akzeptieren.';
$string['ppsubmissionerrorseelogs'] = 'Diese Datei wurde nicht an Turnitin übermittelt; wenden Sie sich an Ihren Systemadministrator.';
$string['ppsubmissionerrorstudent'] = 'Diese Datei wurde nicht an Turnitin übermittelt, für zusätzliche Details kontaktieren Sie bitte Ihren Tutor';

// Receipts.
$string['messageprovider:submission'] = 'Turnitin-Plugin zur Plagiarismuserkennung – Benachrichtigungen zum digitalen Beleg';
$string['digitalreceipt'] = 'Digitaler Beleg';
$string['digital_receipt_subject'] = 'Dies ist Ihr digitaler Beleg von Turnitin.';
$string['pp_digital_receipt_message'] = 'Sehr geehrte/r {$a->firstname} {$a->lastname},<br /><br />Sie haben die Datei <strong>{$a->submission_title}</strong> für die Aufgabe <strong>{$a->assignment_name}{$a->assignment_part}</strong> in Kurs <strong>{$a->course_fullname}</strong> am <strong>{$a->submission_date}</strong> erfolgreich hochgeladen. Ihre Übermittlungs-ID lautet <strong>{$a->submission_id}</strong>. Ihren vollständigen digitalen Beleg können Sie über die Schaltfläche „Drucken/Download“ in der Dokumentenansicht anzeigen und drucken.<br /><br />Vielen Dank, dass Sie Turnitin verwenden,<br /><br />das Turnitin-Team';

// Paper statuses.
$string['turnitinid'] = 'Turnitin-ID';
$string['turnitinstatus'] = 'Turnitin-Status';
$string['pending'] = 'Ausstehend';
$string['similarity'] = 'Ähnlichkeit';
$string['notorcapable'] = 'Für diese Datei lässt sich kein Echtheitsbericht erstellen.';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'Der Student hat die Arbeit aufgerufen über:';
$string['student_notread'] = 'Der Student hat die Arbeit nicht aufgerufen.';
$string['launchpeermarkreviews'] = 'PeerMark-Reviews starten';

// Cron.
$string['ppqueuesize'] = 'Anzahl der Ereignisse in der Ereigniswarteschlange des Plugin zur Plagiarismuserkennung';
$string['ppcronsubmissionlimitreached'] = 'Von diesem Cron-Ausdruck werden keine weiteren Übermittlungen mehr an Turnitin gesendet, da nur {$a} pro Ausführung verarbeitet werden.';
$string['cronsubmittedsuccessfully'] = 'Übermittlung: {$a->title} (TII-ID: {$a->submissionid}) für die Aufgabe {$a->assignmentname} in Kurs {$a->coursename} wurde erfolgreich an Turnitin übermittelt.';
$string['pp_submission_error'] = 'Turnitin hat einen Fehler für Ihre Übermittlung zurückgegeben:';
$string['turnitindeletionerror'] = 'Die Löschung der Turnitin-Übermittlung ist fehlgeschlagen. Die lokale Moodle-Kopie wurde entfernt, die Übermittlung bei Turnitin konnte jedoch nicht gelöscht werden.';
$string['ppeventsfailedconnection'] = 'In dieser Cron-Ausführung werden vom Turnitin-Plugin zur Plagiarismuserkennung keine Ereignisse verarbeitet, da keine Verbindung mit Turnitin hergestellt werden kann.';

// Error codes.
$string['tii_submission_failure'] = 'Weitere Informationen erhalten Sie von Ihrem Tutor oder dem Systemadministrator.';
$string['faultcode'] = 'Fehlercode';
$string['line'] = 'Linie';
$string['message'] = 'Nachricht';
$string['code'] = 'Code';
$string['tiisubmissionsgeterror'] = 'Beim Versuch, von Turnitin Übermittlungen zu dieser Aufgabe abzurufen, ist ein Fehler aufgetreten.';
$string['errorcode0'] = 'Diese Datei wurde nicht an Turnitin übermittelt; wenden Sie sich an Ihren Systemadministrator.';
$string['errorcode1'] = 'Diese Datei wurde nicht an Turnitin gesendet, da sie nicht genügend Inhalt zum Erstellen eines Echtheitsberichts enthält.';
$string['errorcode2'] = 'Diese Datei wird nicht an Turnitin übermittelt, da sie die maximal zulässige Größe von {$a} überschreitet.';
$string['errorcode3'] = 'Diese Datei wurde nicht an Turnitin übermittelt, da der Benutzer die Endbenutzer-Lizenzvereinbarung nicht akzeptiert hat.';
$string['errorcode4'] = 'Sie müssen einen unterstützten Dateityp für diese Aufgabe hochladen. Folgende Dateitypen werden akzeptiert: DOC, DOCX, PPT, PPTX, PPS, PPSX, PDF, TXT, HTM, HTML, HWP, ODT, WPD, PS und RTF.';
$string['errorcode5'] = 'Diese Datei wurde nicht an Turnitin übermittelt, da beim Erstellen des Moduls in Turnitin ein Problem aufgetreten ist, das Übermittlungen verhindert. Weitere Informationen finden Sie in Ihren API-Protokollen.';
$string['errorcode6'] = 'Diese Datei wurde nicht an Turnitin übermittelt, da beim Bearbeiten der Moduleinstellungen in Turnitin ein Problem aufgetreten ist, das Übermittlungen verhindert. Weitere Informationen finden Sie in Ihren API-Protokollen.';
$string['errorcode7'] = 'Diese Datei wurde nicht an Turnitin übermittelt, da beim Erstellen des Benutzers in Turnitin ein Problem aufgetreten ist, das Übermittlungen verhindert. Weitere Informationen finden Sie in Ihren API-Protokollen.';
$string['errorcode8'] = 'Diese Datei wurde nicht an Turnitin übermittelt, da beim Erstellen der temporären Datei ein Problem aufgetreten ist. Die wahrscheinlichste Ursache ist ein ungültiger Dateiname. Benennen Sie die Datei um, und laden Sie sie mit der Option zum Bearbeiten von Übermittlungen erneut hoch.';
$string['errorcode9'] = 'Die Datei kann nicht übermittelt werden, da im Dateipool kein zugänglicher Inhalt für eine Übermittlung vorhanden ist.';
$string['coursegeterror'] = 'Kursdaten konnten nicht abgerufen werden.';
$string['configureerror'] = 'Sie müssen dieses Modul vollständig als Administrator konfigurieren, um es in einem Kurs benutzen zu können. Wenden Sie sich an Ihren Moodle-Administrator.';
$string['turnitintoolofflineerror'] = 'Es ist ein vorübergehendes Problem aufgetreten. Bitte versuchen Sie es später erneut.';
$string['defaultinserterror'] = 'Beim Einfügen einer Standardwerteinstellung in die Datenbank ist ein Fehler eingetreten.';
$string['defaultupdateerror'] = 'Beim Aktualisieren einer Standardwerteinstellung in der Datenbank ist ein Fehler eingetreten.';
$string['tiiassignmentgeterror'] = 'Beim Versuch, eine Aufgabe von Turnitin abzurufen, ist ein Fehler aufgetreten.';
$string['assigngeterror'] = 'Daten für turnitintooltwo konnten nicht aufgerufen werden.';
$string['classupdateerror'] = 'Daten des Turnitin-Kurses konnten nicht aktualisiert werden.';
$string['pp_createsubmissionerror'] = 'Beim Versuch, eine Übermittlung zu Turnitin einzurichten, ist ein Fehler aufgetreten.';
$string['pp_updatesubmissionerror'] = 'Beim Versuch, Ihre Übermittlung zu Turnitin erneut vorzunehmen, ist ein Fehler aufgetreten.';
$string['tiisubmissiongeterror'] = 'Beim Versuch, eine Übermittlung vom Turnitin zu erhalten, ist ein Fehler aufgetreten.';

// Javascript.
$string['closebutton'] = 'Schließen';
$string['loadingdv'] = 'Turnitin-Dokumentenansicht wird geladen...';
$string['changerubricwarning'] = 'Durch das Ändern oder Entfernen einer Rubrik werden alle vorhandenen Rubrikbewertungen der Arbeiten zu dieser Aufgabe entfernt, einschließlich ausgefüllter Bewertungskarten. Gesamtnoten für zuvor bewertete Arbeiten bleiben erhalten.';
$string['messageprovider:submission'] = 'Turnitin-Plugin zur Plagiarismuserkennung – Benachrichtigungen zum digitalen Beleg';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Turnitin-Status';
$string['deleted'] = 'Gelöscht';
$string['pending'] = 'Ausstehend';
$string['because'] = 'Ursache: Ein Administrator hat die ausstehende Aufgabe aus der Verarbeitungswarteschlange gelöscht und die Übermittlung an Turnitin abgebrochen.<br /><strong>Die Datei ist weiterhin in Moodle vorhanden; wenden Sie sich an die zuständige Lehrkraft.</strong><br />Fehlercodes siehe unten:';
