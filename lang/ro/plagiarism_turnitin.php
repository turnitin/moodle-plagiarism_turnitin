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
$string['pluginname'] = 'Plugin de detectare a plagiatului Turnitin';
$string['turnitintooltwo'] = 'Instrument Turnitin';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Procesul plugin de detectare a plagiatului Turnitin';
$string['connecttesterror'] = 'Eroare la conectarea la Turnitin. S-a returnat mesajul de eroare de mai jos:<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'Activare Turnitin';
$string['excludebiblio'] = 'Excluderea bibliografiei';
$string['excludequoted'] = 'Excluderea citatelor';
$string['excludevalue'] = 'Excludere similitudini mici';
$string['excludewords'] = 'Cuvinte';
$string['excludepercent'] = 'Procent';
$string['norubric'] = 'Niciun barem';
$string['otherrubric'] = 'Utilizarea baremului unui alt profesor';
$string['attachrubric'] = 'Atașarea unui barem la această temă';
$string['launchrubricmanager'] = 'Lansarea Managerului de bareme';
$string['attachrubricnote'] = 'Observație: studenții vor putea să vadă baremele atașate și conținutul lor înainte de a-și depune lucrările.';
$string['erater_handbook_advanced'] = 'Avansat';
$string['erater_handbook_highschool'] = 'Liceu';
$string['erater_handbook_middleschool'] = 'Gimnaziu';
$string['erater_handbook_elementary'] = 'Școală primară';
$string['erater_handbook_learners'] = 'Studenți ai limbii engleze';
$string['erater_dictionary_enus'] = 'Dicționar de engleză americană';
$string['erater_dictionary_engb'] = 'Dicționar de engleză britanică';
$string['erater_dictionary_en'] = 'Ambele dicționare: de engleză americană și britanică';
$string['erater'] = 'Activarea verificării gramaticale cu e-rater';
$string['erater_handbook'] = 'Manual ETS&copy;';
$string['erater_dictionary'] = 'Dicționar e-rater';
$string['erater_categories'] = 'Categorii e-rater';
$string['erater_spelling'] = 'Ortografie';
$string['erater_grammar'] = 'Gramatică';
$string['erater_usage'] = 'Utilizare';
$string['erater_mechanics'] = 'Ortografie și punctuație';
$string['erater_style'] = 'Stil';
$string['anonblindmarkingnote'] = 'Observație: setarea separată de însemnări anonime în Turnitin a fost eliminată. Turnitin va utiliza setarea de însemnări anonime din Moodle pentru a determina ce setare trebuie să folosească.';
$string['transmatch'] = 'Similitudini cu traduceri';
$string['genduedate'] = 'Generare a rapoartelor la data limită de depunere (studenții pot redepune până la data limită de depunere)';
$string['genimmediately1'] = 'Generare imediată a rapoartelor (studenții nu pot redepune)';
$string['genimmediately2'] = 'Generare imediată a rapoartelor (studenții pot redepune până la data limită de depunere): După {$a->num_resubmissions} redepuneri, rapoartele se generează după {$a->num_hours} ore';
$string['launchquickmarkmanager'] = 'Lansare Manager Quickmark';
$string['launchpeermarkmanager'] = 'Lansare Manager Peermark';
$string['studentreports'] = 'Afișarea Rapoartelor privind originalitatea pentru studenți';
$string['studentreports_help'] = 'Vă permite să afișați utilizatorilor studenți rapoartele Turnitin privind originalitatea. Dacă este setat la da, rapoartele privind originalitatea generate de Turnitin pot fi văzute de către studenți.';
$string['submitondraft'] = 'Depunere fișier la prima încărcare';
$string['submitonfinal'] = 'Depunerea fișierului atunci când studentul trimite lucrarea pentru însemnări';
$string['draftsubmit'] = 'Când se depune fișierul în Turnitin?';
$string['allownonor'] = 'Se permite depunerea oricărui tip de fișier?';
$string['allownonor_help'] = 'Această setare va permite depunerea oricărui tip de fișier. Dacă opțiunea este setată la „Da”, originalitatea depunerilor va fi verificată în limita posibilităților, depunerile vor fi disponibile pentru descărcare și instrumentele de feedback GradeMark vor fi disponibile în limita posibilităților.';
$string['norepository'] = 'Niciun depozit';
$string['standardrepository'] = 'Depozit standard';
$string['submitpapersto'] = 'Stocarea lucrărilor studenților';
$string['institutionalrepository'] = 'Depozitul instituțional (dacă este cazul)';
$string['checkagainstnote'] = 'Observație: dacă nu selectați „Da” la cel puțin una dintre opțiunile „Verificare prin comparație cu...” de mai jos, Raportul privind originalitatea NU se va genera.';
$string['spapercheck'] = 'Verificare prin comparație cu lucrările depozitate ale studenților';
$string['internetcheck'] = 'Verificare prin comparație cu materialele de pe internet';
$string['journalcheck'] = 'Verificare prin comparație cu jurnale,<br />periodice și publicații';
$string['compareinstitution'] = 'Fișierele depuse se compară cu lucrările depuse în această instituție';
$string['reportgenspeed'] = 'Viteza generării raportului';
$string['locked_message'] = 'Mesaj blocat';
$string['locked_message_help'] = 'Dacă se blochează oricare dintre setări, se afișează acest mesaj pentru a explica motivul.';
$string['locked_message_default'] = 'Această setare este blocată la nivel de site';
$string['sharedrubric'] = 'Barem partajat';
$string['turnitinrefreshsubmissions'] = 'Reîncărcarea depunerilor';
$string['turnitinrefreshingsubmissions'] = 'Se reîmprospătează depunerile';
$string['turnitinppulapre'] = 'Pentru a putea depune un fișier în Turnitin, trebuie să acceptați în prealabil EULA (Acordul de licență cu utilizatorul final). Dacă decideți să nu acceptați EULA, fișierul se va depune doar în Moodle. Faceți clic aici pentru a accepta.';
$string['noscriptula'] = '(Întrucât nu aveți Javascript activat, va trebui să reîncărcați manual această pagină pentru a putea depune o lucrare după acceptarea Acordului de utilizator Turnitin)';
$string['filedoesnotexist'] = 'Fișierul a fost șters';
$string['reportgenspeed_resubmission'] = 'Ați depus deja o lucrare la această temă și un Raport de similitudine a fost generat pentru depunere. Dacă decideți să vă redepuneți lucrarea, depunerea anterioară va fi înlocuită și va fi generat un raport nou. Pentru a vizualiza un nou Raport de similitudine, după {$a->num_resubmissions} redepuneri, va trebui să așteptați {$a->num_hours} ore după o redepunere.';

// Plugin settings.
$string['config'] = 'Configurație';
$string['defaults'] = 'Setări implicite';
$string['showusage'] = 'Afișarea conținutului bazei de date';
$string['saveusage'] = 'Salvarea conținutului bazei de date';
$string['errors'] = 'Erori';
$string['turnitinconfig'] = 'Configurarea pluginului de detectare a plagiatului Turnitin';
$string['tiiexplain'] = 'Turnitin este un produs comercial și trebuie să aveți un abonament plătit pentru a folosi acest serviciu. Pentru mai multe informații citiți <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>';
$string['useturnitin'] = 'Activare Turnitin';
$string['useturnitin_mod'] = 'Activare Turnitin pentru {$a}';
$string['pp_configuredesc'] = 'Trebuie să configurați acest modul în modulul turnitintooltwo. Faceți clic <a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>aici</a> pentru a configura acest plugin';
$string['turnitindefaults'] = 'Setările implicite ale pluginului de detectare a plagiatului Turnitin';
$string['defaultsdesc'] = 'Următoarele setări sunt valorile implicite setate la activarea Turnitin într-un modul de activitate';
$string['turnitinpluginsettings'] = 'Setări plugin de detectare a plagiatului Turnitin';
$string['pperrorsdesc'] = 'Problemă la încercarea de a încărca fișierele de mai jos în Turnitin. Pentru a redepune materialul, selectați fișierele pe care doriți să le redepuneți, apoi faceți clic pe butonul de redepunere. Acestea se vor procesa la următoarea executare a lucrării cron.';
$string['pperrorssuccess'] = 'Fișierele pe care le-ați selectat au fost redepuse și vor fi procesate de lucrarea cron.';
$string['pperrorsfail'] = 'Problemă cu unele dintre fișierele pe care le-ați selectat, nu s-a putut crea un nou eveniment cron pentru acestea.';
$string['resubmitselected'] = 'Redepunerea fișierelor selectate';
$string['deleteconfirm'] = 'Sigur doriți să ștergeți această depunere?\n\nAcțiunea nu poate fi anulată.';
$string['deletesubmission'] = 'Ștergere depunere';
$string['semptytable'] = 'Nu s-a găsit niciun rezultat.';
$string['configupdated'] = 'Configurație actualizată';
$string['defaultupdated'] = 'Setările implicite Turnitin au fost actualizate';
$string['notavailableyet'] = 'Indisponibil';
$string['resubmittoturnitin'] = 'Redepunere în Turnitin';
$string['resubmitting'] = 'Redepunere în curs';
$string['id'] = 'ID';
$string['student'] = 'Student';
$string['course'] = 'Curs';
$string['module'] = 'Modul';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'Vizualizarea Raportului privind originalitatea';
$string['launchrubricview'] = 'Vizualizarea baremului folosit pentru însemnări';
$string['turnitinppulapost'] = 'Fișierul nu a fost depus în Turnitin. Faceți clic aici pentru a accepta EULA.';
$string['ppsubmissionerrorseelogs'] = 'Acest fișier nu a fost depus în Turnitin, consultați administratorul de sistem';
$string['ppsubmissionerrorstudent'] = 'Acest fișier nu a fost depus în Turnitin, consultați-vă îndrumătorul pentru mai multe detalii';

// Receipts.
$string['messageprovider:submission'] = 'Notificări privind confirmările digitale din pluginul de detectare a plagiatului Turnitin';
$string['digitalreceipt'] = 'Confirmare digitală';
$string['digital_receipt_subject'] = 'Aceasta este Confirmarea digitală Turnitin';
$string['pp_digital_receipt_message'] = 'Stimate/Stimată {$a->firstname} {$a->lastname},<br /><br />Ați depus cu succes fișierul <strong>{$a->submission_title}</strong> cu tema <strong>{$a->assignment_name}{$a->assignment_part}</strong> din cursul <strong>{$a->course_fullname}</strong>, pe data de <strong>{$a->submission_date}</strong>. ID-ul depunerii este <strong>{$a->submission_id}</strong>. Confirmarea digitală completă se poate vizualiza și imprima prin intermediul butonului de imprimare/descărcare din vizualizatorul de documente.<br /><br />Vă mulțumim pentru utilizarea Turnitin,<br /><br />Echipa Turnitin';

// Paper statuses.
$string['turnitinid'] = 'ID Turnitin';
$string['turnitinstatus'] = 'Stare Turnitin';
$string['pending'] = 'În așteptare';
$string['similarity'] = 'Similitudine';
$string['notorcapable'] = 'Pentru acest fișier nu se poate genera un Raport privind originalitatea.';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'Studentul a văzut lucrarea la:';
$string['student_notread'] = 'Studentul nu a văzut această lucrare.';
$string['launchpeermarkreviews'] = 'Lansare evaluări Peermark';

// Cron.
$string['ppqueuesize'] = 'Numărul evenimentelor din coada de evenimente a pluginului de detectare a plagiatului';
$string['ppcronsubmissionlimitreached'] = 'La această executare a lucrării cron nu se mai trimit în Turnitin alte depuneri, deoarece la o executare se procesează cel mult {$a}';
$string['cronsubmittedsuccessfully'] = 'Lucrarea: {$a->title} (ID TII: {$a->submissionid}) pentru tema {$a->assignmentname} din cursul {$a->coursename} a fost depusă cu succes în Turnitin.';
$string['pp_submission_error'] = 'Turnitin a returnat o eroare legată de depunere:';
$string['turnitindeletionerror'] = 'Ștergerea depunerii Turnitin nu a reușit. Copia locală Moodle a fost înlăturată, dar depunerea din Turnitin nu a putut fi ștearsă.';
$string['ppeventsfailedconnection'] = 'La această executare a lucrării cron nu se vor procesa alte evenimente cu pluginul de detectare a plagiatului Turnitin, deoarece nu se poate stabili conexiunea cu Turnitin.';

// Error codes.
$string['tii_submission_failure'] = 'Consultați-vă îndrumătorul sau administratorul de sistem pentru mai multe detalii';
$string['faultcode'] = 'Cod de eroare';
$string['line'] = 'Linie';
$string['message'] = 'Mesaj';
$string['code'] = 'Cod';
$string['tiisubmissionsgeterror'] = 'Eroare la încercarea de a obține depunerile pentru această temă de la Turnitin';
$string['errorcode0'] = 'Acest fișier nu a fost depus în Turnitin, consultați administratorul de sistem';
$string['errorcode1'] = 'Acest fișier nu a fost trimis în Turnitin, deoarece conținutul este insuficient pentru generarea unui Raport privind originalitatea.';
$string['errorcode2'] = 'Acest fișier nu se va depune în Turnitin pentru că depășește dimensiunea maximă admisă de {$a}';
$string['errorcode3'] = 'Acest fișier nu a fost depus în Turnitin, deoarece utilizatorul nu a acceptat Acordul de licență cu utilizatorul final al Turnitin.';
$string['errorcode4'] = 'Pentru această temă trebuie să încărcați un tip de fișier acceptat. Tipurile acceptate sunt: .doc, .docx, .ppt, .pptx, .pps, .ppsx, .pdf, .txt, .htm, .html, .hwp, .odt, .wpd, .ps și .rtf';
$string['errorcode5'] = 'Acest fișier nu a fost depus în Turnitin din cauza unei probleme la crearea modulului în Turnitin, care împiedică depunerile, pentru informații suplimentare, consultați jurnalele API';
$string['errorcode6'] = 'Acest fișier nu a fost depus în Turnitin din cauza unei probleme la editarea modulului în Turnitin, care împiedică depunerile, pentru informații suplimentare, consultați jurnalele API';
$string['errorcode7'] = 'Acest fișier nu a fost depus în Turnitin din cauza unei probleme la crearea utilizatorului în Turnitin, care împiedică depunerile, pentru informații suplimentare, consultați jurnalele API';
$string['errorcode8'] = 'Acest fișier nu a fost depus în Turnitin din cauza unei probleme la crearea fișierului temporar. Cauza probabilă este un nume de fișier incorect. Redenumiți fișierul și reîncărcați-l prin Editare depunere.';
$string['errorcode9'] = 'Imposibil de depus fișierul: în lista de fișiere nu există conținut accesibil care să poată fi depus.';
$string['coursegeterror'] = 'Nu au putut fi obținute datele cursului';
$string['configureerror'] = 'Pentru a putea utiliza modul într-un curs, trebuie să-l configurați complet ca Administrator. Contactați administratorul Moodle.';
$string['turnitintoolofflineerror'] = 'Avem o problemă temporară. Încercați din nou în scurt timp.';
$string['defaultinserterror'] = 'Eroare la încercarea de a introduce o valoare de setare implicită în baza de date';
$string['defaultupdateerror'] = 'Eroare la încercarea de a actualiza o valoare de setare implicită în baza de date';
$string['tiiassignmentgeterror'] = 'Eroare la încercarea de a obține o temă de la Turnitin';
$string['assigngeterror'] = 'Imposibil de obținut datele turnitintooltwo';
$string['classupdateerror'] = 'Nu au putut fi actualizate datele cursului Turnitin';
$string['pp_createsubmissionerror'] = 'Eroare la încercarea de a crea depunerea în Turnitin';
$string['pp_updatesubmissionerror'] = 'Eroare la încercarea de a redepune lucrarea în Turnitin';
$string['tiisubmissiongeterror'] = 'Eroare la încercarea de a obține o depunere de la Turnitin';

// Javascript.
$string['closebutton'] = 'Închidere';
$string['loadingdv'] = 'Se încarcă vizualizatorul de documente Turnitin...';
$string['changerubricwarning'] = 'Modificarea sau detașarea unui barem va înlătura din lucrările cu această temă toate punctajele acordate pe baza baremului, inclusiv grilele de notare punctate anterior. Notele generale ale lucrărilor notate anterior se vor păstra.';
$string['messageprovider:submission'] = 'Notificări privind confirmările digitale din pluginul de detectare a plagiatului Turnitin';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Stare Turnitin';
$string['deleted'] = 'Șters';
$string['pending'] = 'În așteptare';
$string['because'] = 'Cauza este faptul că un administrator a șters o temă în așteptare din coada de procesare și a întrerupt depunerea în Turnitin.<br /><strong>Fișierul există în continuare în Moodle, contactați profesorul.</strong><br />Căutați mai jos codul de eroare:';
$string['submitpapersto_help'] = '<strong>Niciun depozit: </strong><br />Turnitin este configurat să nu stocheze documentele depuse în niciun depozit. Vom procesa lucrarea numai pentru o verificare inițială de similaritate.<br /><br /><strong>Depozit standard: </strong><br />Turnitin va trimite un exemplar al documentului depus numai în Depozitul standard. Alegând această opțiune, Turnitin va fi configurat să folosească numai documentele depuse pentru a efectua verificări de similaritate pentru documente depuse în viitor.<br /><br /><strong>Depozitul instituțional (dacă este cazul): </strong><br />Prin alegerea acestei opțiuni, Turnitin este configurat să adauge documentele depuse într-un depozit privat al instituției. Verificările de similaritate pentru documentele depuse se vor efectua de către alți profesori din instituția dvs.';
$string['errorcode12'] = 'Fișierul nu a fost depus la Turnitin deoarece aparține unei teme la care cursul a fost șters. ID rând: ({$a->id}) | ID modul de curs: ({$a->cm}) |  ID de utilizator: ({$a->userid})';
$string['tiiaccountconfig'] = 'Configurarea contului Turnitin';
$string['turnitinaccountid'] = 'ID de cont Turnitin';
$string['turnitinsecretkey'] = 'Cheia partajată Turnitin';
$string['turnitinapiurl'] = 'URL-ul API Turnitin';
$string['tiidebugginglogs'] = 'Depanare și înregistrare în jurnal';
$string['turnitindiagnostic'] = 'Activați Modul diagnostic';
$string['enableperformancelogs'] = 'Activarea înregistrării în jurnal a performanțelor rețelei';
$string['enableperformancelogs_desc'] = 'Dacă activați opțiunea, fiecare solicitare trimisă serverului Turnitin va fi înregistrată în jurnalele din {tempdir}/turnitintooltwo/logs';
$string['turnitindiagnostic_desc'] = '<b>[Atenție]</b><br />Activați Modul diagnostic numai pentru a identifica problemele cu API Turnitin.';
$string['tiiaccountsettings_desc'] = 'Asigurați-vă că aceste setări sunt identice cu cele pe care le-ați configurat în contul Turnitin, în caz contrar puteți întâmpina probleme în crearea temelor și/dau în lucrul cu materialele depuse de studenți.';
$string['tiiaccountsettings'] = 'Setările contului Turnitin';
$string['turnitinusegrademark'] = 'Utilizare GradeMark';
$string['turnitinusegrademark_desc'] = 'Stabiliți dacă depunerile se vor nota prin GradeMark.<br /><i>(Opțiunea este disponibilă doar utilizatorilor pentru conturile cărora s-a configurat GradeMark)</i>';
$string['turnitinenablepeermark'] = 'Activarea temelor Peermark';
$string['turnitinenablepeermark_desc'] = 'Stabiliți dacă se vor putea crea teme Peermark.<br/><i>(Opțiunea este disponibilă doar utilizatorilor pentru conturile cărora s-a configurat Peermark)</i>';
$string['turnitinuseerater'] = 'Activare ETS&copy;';
$string['turnitinuseerater_desc'] = 'Alegeți dacă activați verificarea gramaticii cu ETS&copy;.<br /><i>(Activați această opțiune doar dacă ETS&copy; e-rater este activat în contul Turnitin)</i>';
$string['transmatch_desc'] = 'Determină dacă setarea Similitudini cu traduceri va fi disponibilă în ecranul de configurare a temei.<br /><i>(Activați această opțiune doar dacă Similitudini cu traduceri este activată la contul Turnitin)</i>';
$string['repositoryoptions_0'] = 'Activarea opțiunilor de depozit standard pentru profesor';
$string['repositoryoptions_1'] = 'Activarea opțiunilor de depozit extinse pentru profesor';
$string['repositoryoptions_2'] = 'Toate lucrările se trimit în depozitul standard';
$string['repositoryoptions_3'] = 'Nu se trimite nicio lucrare în niciun depozit';
$string['turnitinrepositoryoptions'] = 'Teme – depozit de lucrări';
$string['turnitinrepositoryoptions_desc'] = 'Alegeți opțiunile de depozit pentru temele Turnitin.<br /><i>(Depozitul instituțional este disponibil doar utilizatorilor pentru ale căror conturi s-a activat acest lucru)</i>';
$string['tiimiscsettings'] = 'Diverse setări de plugin';
$string['turnitintooltwoagreement_default'] = 'Confirm că această depunere este munca mea și îmi asum toată răspunderea pentru orice violare a drepturilor de autor care se poate acea loc ca rezultat al acestei depuneri.';
$string['turnitintooltwoagreement_desc'] = '<b>[Opțional]</b><br />Introduceți o declarație de confirmare a acordului pentru depuneri.<br />(<b>Observație:</b> Dacă acordul este lăsat complet gol, studenților nu li se va cere nicio confirmare a acordului în momentul depunerii)';
$string['turnitintooltwoagreement'] = 'Exonerare de răspundere/Acord';
$string['studentdataprivacy'] = 'Setările de confidențialitate a datelor studenților';
$string['studentdataprivacy_desc'] = 'Următoarele setări pot fi configurate pentru a împiedica transmiterea datelor personale ale studentului către Turnitin, prin API.';
$string['enablepseudo'] = 'Activarea confidențialității studentului';
$string['enablepseudo_desc'] = 'Dacă această opțiune este selectată, adresele de e-mail ale studenților vor fi transformate în pseudoadrese echivalente în apelurile API Turnitin.<br /><i>(<b>Observație:</b> Această opțiune nu se poate modifica dacă s-au sincronizat deja date ale utilizatorului din Moodle în Turnitin)</i>';
$string['pseudofirstname'] = 'Pseudoprenumele studentului';
$string['pseudofirstname_desc'] = '<b>[Opțional]</b><br />Prenumele studentului care se va afișa în vizualizatorul de documente Turnitin';
$string['pseudolastname'] = 'Pseudonumele de familie al studentului';
$string['pseudolastname_desc'] = 'Numele de familie al studentului care se va afișa în vizualizatorul de documente Turnitin';
$string['pseudolastnamegen'] = 'Generarea automată a numelui de familie';
$string['pseudolastnamegen_desc'] = 'Dacă opțiunea este setată la valoarea da și presudonumele de familie este setat la un câmp din profilul de utilizator, câmpul se va completa automat cu un identificator unic.';
$string['pseudoemailsalt'] = 'Completare cu numere pseudoaleatoare la criptare';
$string['pseudoemailsalt_desc'] = '<b>[Opțional]</b><br />O completare opțională pentru a mări complexitatea pseudoadresei de e-mail generate a studentului.<br />(<b>Observație:</b> Completarea trebuie să rămână neschimbată pentru a avea pseudoadrese de e-mail uniforme)';
$string['pseudoemaildomain'] = 'Pseudodomeniu de e-mail';
$string['pseudoemaildomain_desc'] = '<b>[Opțional]</b><br />Un domeniu opțional pentru pseudoadresele de e-mail. (Dacă rămâne necompletat, primește valoarea implicită @tiimoodle.com)';
$string['pseudoemailaddress'] = 'Pseudoadresă de e-mail';
$string['connecttest'] = 'Testarea conexiunii Turnitin';
$string['connecttestsuccess'] = 'Moodle s-a conectat cu succes la Turnitin.';
