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
$string['pluginname'] = 'Plugin del plagio di Turnitin';
$string['turnitintooltwo'] = 'Strumento Turnitin';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Attività del plugin del plagio di Turnitin';
$string['connecttesterror'] = 'Si è verificato un errore di connessione a Turnitin. Il messaggio di errore di ritorno è il seguente:<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'Abilita Turnitin';
$string['excludebiblio'] = 'Escludi bibliografia';
$string['excludequoted'] = 'Escludi materiale citato';
$string['excludevalue'] = 'Escludi corrispondenze brevi';
$string['excludewords'] = 'Parole';
$string['excludepercent'] = 'Percentuale';
$string['norubric'] = 'Nessuna pagella';
$string['otherrubric'] = 'Usa la pagella di un altro docente';
$string['attachrubric'] = 'Allega una pagella a questo compito';
$string['launchrubricmanager'] = 'Avvia lo strumento di gestione pagelle';
$string['attachrubricnote'] = 'Nota: gli studenti saranno in grado di visualizzare le pagelle allegate e il loro contenuto prima di consegnare.';
$string['erater_handbook_advanced'] = 'Avanzato';
$string['erater_handbook_highschool'] = 'Scuola superiore';
$string['erater_handbook_middleschool'] = 'Scuola media';
$string['erater_handbook_elementary'] = 'Scuola primaria';
$string['erater_handbook_learners'] = 'Studenti di inglese';
$string['erater_dictionary_enus'] = 'Dizionario inglese (Stati Uniti)';
$string['erater_dictionary_engb'] = 'Dizionario inglese (Regno Unito)';
$string['erater_dictionary_en'] = 'Dizionario inglese (Stati Uniti e Regno Unito)';
$string['erater'] = 'Abilita il controllo grammaticale e-rater';
$string['erater_handbook'] = 'Manuale ETS&copy;';
$string['erater_dictionary'] = 'Dizionario e-rater';
$string['erater_categories'] = 'Categorie e-rater';
$string['erater_spelling'] = 'Ortografia';
$string['erater_grammar'] = 'Grammatica';
$string['erater_usage'] = 'Uso';
$string['erater_mechanics'] = 'Sintassi';
$string['erater_style'] = 'Stile';
$string['anonblindmarkingnote'] = 'Nota: l&#39;impostazione di valutazione anonima separata di Turnitin è stata rimossa. Turnitin utilizzerà l&#39;impostazione di valutazione anonima di Moodle per determinare l&#39;impostazione di valutazione anonima.';
$string['transmatch'] = 'Corrispondenza di testo tradotto';
$string['genduedate'] = 'Genera report alla data di scadenza (gli studenti possono riconsegnare fino alla data di scadenza)';
$string['genimmediately1'] = 'Genera subito report (gli studenti non possono riconsegnare)';
$string['genimmediately2'] = 'Genera subito report (gli studenti possono riconsegnare fino alla data di scadenza): Dopo {$a->num_resubmissions} riconsegne, genera report dopo {$a->num_hours} ore';
$string['launchquickmarkmanager'] = 'Avvia QuickMark Manager';
$string['launchpeermarkmanager'] = 'Avvia PeerMark Manager';
$string['studentreports'] = 'Mostra gli Originality Report agli studenti';
$string['studentreports_help'] = 'Consente di mostrare agli utenti studenti gli Originality Report di Turnitin. Se impostato su sì gli Originality Report generati da Turnitin possono essere visionati dallo studente.';
$string['submitondraft'] = 'Consegna il file appena viene caricato';
$string['submitonfinal'] = 'Consegna il file quando lo studente lo invia per la valutazione';
$string['draftsubmit'] = 'Quando deve essere consegnato il file a Turnitin?';
$string['allownonor'] = 'Consentire la consegna di qualsiasi tipo di file?';
$string['allownonor_help'] = 'Questa impostazione permetterà la consegna di qualsiasi tipo di file. Con questa opzione impostata su &#34;Sì&#34;, ove possibile verrà eseguita la verifica dell&#39;originalità delle consegne, queste ultime saranno disponibili per il download e, ove possibile, saranno disponibili anche gli strumenti di feedback GradeMark.';
$string['norepository'] = 'Nessun archivio';
$string['standardrepository'] = 'Archivio standard';
$string['submitpapersto'] = 'Archivia gli elaborati degli studenti';
$string['institutionalrepository'] = 'Archivio dell&#39;istituto (ove applicabile)';
$string['checkagainstnote'] = 'Nota: se non selezioni "Sì" per almeno una delle opzioni "Confronta con..." di seguito, NON verrà generato alcun Originality Report.';
$string['spapercheck'] = 'Confronta con gli elaborati degli studenti memorizzati';
$string['internetcheck'] = 'Confronta con Internet';
$string['journalcheck'] = 'Confronta con journal,<br />periodici e pubblicazioni';
$string['compareinstitution'] = 'Confronta i file consegnati con gli elaborati consegnati in questo istituto';
$string['reportgenspeed'] = 'Velocità di generazione dei report';
$string['locked_message'] = 'Messaggio bloccato';
$string['locked_message_help'] = 'Se eventuali impostazioni sono bloccate, viene mostrato questo messaggio per spiegarne il motivo.';
$string['locked_message_default'] = 'Questa impostazione è bloccata a livello di sito';
$string['sharedrubric'] = 'Pagella condivisa';
$string['turnitinrefreshsubmissions'] = 'Aggiorna consegne';
$string['turnitinrefreshingsubmissions'] = 'Aggiornamento consegne';
$string['turnitinppulapre'] = 'Per consegnare un file a Turnitin è necessario accettare innanzitutto il Contratto di licenza con l&#39;utente finale. Scegliendo di non accettare il Contratto di licenza con l&#39;utente finale, il file verrà consegnato solo a Moodle. Fai clic qui per accettare.';
$string['noscriptula'] = '(Dal momento che javascript non è abilitato, dovrai aggiornare manualmente questa pagina per poter consegnare un elaborato dopo aver accettato il Contratto con l&#39;utente di Turnitin)';
$string['filedoesnotexist'] = 'Il file è stato cancellato';
$string['reportgenspeed_resubmission'] = 'Hai già presentato un elaborato per questo compito ed è stato generato un report Somiglianza per la riconsegna. Se scegli di riconsegnare il tuo elaborato, l\'elaborato precedente verrà sostituito e verrà generato un nuovo report. Dopo {$a->num_resubmissions} riconsegne, si dovrà attendere {$a->num_hours} ore dopo la riconsegna per visualizzare un nuovo report.';

// Plugin settings.
$string['config'] = 'Configurazione';
$string['defaults'] = 'Impostazioni predefinite';
$string['showusage'] = 'Mostra data dump';
$string['saveusage'] = 'Salva data dump';
$string['errors'] = 'Errori';
$string['turnitinconfig'] = 'Configurazione del plugin del plagio di Turnitin';
$string['tiiexplain'] = 'Turnitin è un prodotto commerciale ed è necessario avere una sottoscrizione a pagamento per utilizzare questo servizio. Per ulteriori informazioni, vai a <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>';
$string['useturnitin'] = 'Abilita Turnitin';
$string['useturnitin_mod'] = 'Abilita Turnitin per {$a}';
$string['pp_configuredesc'] = 'Devi configurare il modulo all&#39;interno del modulo turnitintooltwo. Fai clic <a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>qui</a> per configurare questo plugin';
$string['turnitindefaults'] = 'Impostazioni predefinite del plugin del plagio di Turnitin';
$string['defaultsdesc'] = 'Le seguenti impostazioni sono i valori predefiniti impostati durante l&#39;abilitazione di Turnitin all&#39;interno di un modulo di attività';
$string['turnitinpluginsettings'] = 'Impostazioni del plugin del plagio di Turnitin';
$string['pperrorsdesc'] = 'Si è verificato un problema durante il tentativo di caricamento dei file seguenti in Turnitin. Per riconsegnare, selezionare i file che desideri riconsegnare e premi il pulsante Riconsegna. Verranno elaborati la volta successiva che verrà eseguito Cron.';
$string['pperrorssuccess'] = 'I file selezionati sono stati riconsegnati e verranno elaborati da Cron.';
$string['pperrorsfail'] = 'Si è verificato un problema con alcuni dei file selezionati. Non è stato possibile creare un nuovo evento Cron per tali file.';
$string['resubmitselected'] = 'Riconsegna i file selezionati';
$string['deleteconfirm'] = 'Eliminare questa consegna?\n\nL&#39;azione non potrà essere annullata.';
$string['deletesubmission'] = 'Elimina consegna';
$string['semptytable'] = 'Nessun risultato trovato.';
$string['configupdated'] = 'Configurazione aggiornata';
$string['defaultupdated'] = 'Impostazioni predefinite Turnitin aggiornate';
$string['notavailableyet'] = 'Non disponibile';
$string['resubmittoturnitin'] = 'Riconsegna a Turnitin';
$string['resubmitting'] = 'Riconsegna in corso';
$string['id'] = 'ID';
$string['student'] = 'Studente';
$string['course'] = 'Corso';
$string['module'] = 'Modulo';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'Visualizza Originality Report';
$string['launchrubricview'] = 'Visualizza la pagella utilizzata per la valutazione';
$string['turnitinppulapost'] = 'Il file non è stato consegnato a Turnitin. Fai clic qui per accettare il Contratto di licenza con l&#39;utente finale.';
$string['ppsubmissionerrorseelogs'] = 'Questo file non è stato consegnato a Turnitin. Consulta il tuo amministratore di sistema';
$string['ppsubmissionerrorstudent'] = 'Questo file non è stato consegnato a Turnitin, consulta il tuo tutor per ulteriori dettagli';

// Receipts.
$string['messageprovider:submission'] = 'Notifiche della ricevuta digitale del plugin del plagio di Turnitin';
$string['digitalreceipt'] = 'Ricevuta digitale';
$string['digital_receipt_subject'] = 'Questa è la tua ricevuta digitale Turnitin';
$string['pp_digital_receipt_message'] = 'Gentile {$a->firstname} {$a->lastname},<br /><br />hai consegnato il file <strong>{$a->submission_title}</strong> per il compito <strong>{$a->assignment_name}{$a->assignment_part}</strong> nella classe <strong>{$a->course_fullname}</strong> in <strong>{$a->submission_date}</strong>. L&#39;ID consegna è <strong>{$a->submission_id}</strong>. La ricevuta digitale completa può essere visualizzata e stampata dal pulsante di stampa/download nel visualizzatore documenti.<br /><br />Grazie per aver utilizzato Turnitin,<br /><br />Il team Turnitin';

// Paper statuses.
$string['turnitinid'] = 'ID Turnitin';
$string['turnitinstatus'] = 'Stato Turnitin';
$string['pending'] = 'In sospeso';
$string['similarity'] = 'Somiglianza';
$string['notorcapable'] = 'Non è possibile produrre un Originality Report per questo file.';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'Lo studente ha visualizzato questo elaborato il:';
$string['student_notread'] = 'Lo studente non ha visualizzato questo elaborato.';
$string['launchpeermarkreviews'] = 'Avvia revisioni PeerMark';

// Cron.
$string['ppqueuesize'] = 'Numero di eventi nella coda di eventi del plugin di plagio';
$string['ppcronsubmissionlimitreached'] = 'Non verranno inviate ulteriori consegne a Turnitin da questa esecuzione Cron poiché solo {$a} vengono elaborate per ogni esecuzione';
$string['cronsubmittedsuccessfully'] = 'Consegna: {$a->title} (ID TII: {$a->submissionid}) per il compito {$a->assignmentname} nel corso {$a->coursename} consegnata a Turnitin.';
$string['pp_submission_error'] = 'Turnitin ha restituito un errore contestualmente alla consegna:';
$string['turnitindeletionerror'] = 'L&#39;eliminazione delle consegne non ha avuto esito positivo. La copia locale Moodle è stata rimossa, ma non è stato possibile eliminare la consegna in Turnitin.';
$string['ppeventsfailedconnection'] = 'Non verrà elaborato alcun evento dal plugin del plagio di Turnitin da questa esecuzione Cron poiché non è stato possibile stabilire una connessione a Turnitin.';

// Error codes.
$string['tii_submission_failure'] = 'Consulta il tutor o l&#39;amministratore di sistema per ulteriori dettagli';
$string['faultcode'] = 'Codice guasto';
$string['line'] = 'Linea';
$string['message'] = 'Messaggio';
$string['code'] = 'Codice';
$string['tiisubmissionsgeterror'] = 'Si è verificato un errore durante il tentativo di ottenere da Turnitin le consegne per questo compito';
$string['errorcode0'] = 'Questo file non è stato consegnato a Turnitin. Consulta il tuo amministratore di sistema';
$string['errorcode1'] = 'Questo file non è stato inviato a Turnitin poiché non dispone di abbastanza contenuto per produrre un Originality Report.';
$string['errorcode2'] = 'Questo file non verrà consegnato a Turnitin perché supera la dimensione massima di {$a} consentita';
$string['errorcode3'] = 'Questo file non è stato consegnato a Turnitin perché l&#39;utente non ha accettato il contratto di licenza con l&#39;utente finale Turnitin.';
$string['errorcode4'] = 'Devi caricare un tipo di file supportato per questo compito. I tipi di file accettati sono: .doc, .docx, .ppt, .pptx, .pps, .ppsx, .pdf, .txt, .htm, .html, .hwp, .odt, .wpd, .ps e .rtf';
$string['errorcode5'] = 'Questo file non è stato consegnato a Turnitin perché si è verificato un problema con la creazione del modulo in Turnitin che blocca le consegne. Consulta i log delle API per ulteriori informazioni';
$string['errorcode6'] = 'Questo file non è stato consegnato a Turnitin perché si è verificato un problema con la modifica delle impostazioni del modulo in Turnitin che blocca le consegne. Consulta i log delle API per ulteriori informazioni';
$string['errorcode7'] = 'Questo file non è stato consegnato a Turnitin perché si è verificato un problema con la creazione dell&#39;utente in Turnitin che blocca le consegne. Consulta i log delle API per ulteriori informazioni';
$string['errorcode8'] = 'Questo file non è stato consegnato a Turnitin perché si è verificato un problema con la creazione del file temp. La causa più probabile è un nome file non valido. Rinomina il file e caricalo di nuovo utilizzando l&#39;opzione Modifica consegna.';
$string['errorcode9'] = 'Impossibile consegnare il file poiché non sono presenti contenuti accessibili nel pool di file da consegnare.';
$string['coursegeterror'] = 'Impossibile ottenere dati del corso';
$string['configureerror'] = 'Devi configurare il modulo completamente come amministratore prima di utilizzarlo all&#39;interno di un corso. Contatta l&#39;amministratore di Moodle.';
$string['turnitintoolofflineerror'] = 'Si è verificato un problema momentaneo. Riprova più tardi.';
$string['defaultinserterror'] = 'Si è verificato un errore durante il tentativo di inserire un valore predefinito nel database';
$string['defaultupdateerror'] = 'Si è verificato un errore durante il tentativo di aggiornare un valore predefinito nel database';
$string['tiiassignmentgeterror'] = 'Si è verificato un errore durante il tentativo di ottenere un compito da Turnitin';
$string['assigngeterror'] = 'Impossibile ottenere i dati da turnitintooltwo';
$string['classupdateerror'] = 'Impossibile aggiornare i dati della classe Turnitin';
$string['pp_createsubmissionerror'] = 'Si è verificato un errore durante il tentativo di creare la consegna in Turnitin';
$string['pp_updatesubmissionerror'] = 'Si è verificato un errore durante il tentativo di riconsegnare il compito in Turnitin';
$string['tiisubmissiongeterror'] = 'Si è verificato un errore durante il tentativo di ottenere una consegna da Turnitin';

// Javascript.
$string['closebutton'] = 'Chiudi';
$string['loadingdv'] = 'Caricamento del visualizzatore documenti Turnitin in corso...';
$string['changerubricwarning'] = 'La modifica o rimozione di una pagella rimuoverà tutto il punteggio esistente della pagella dagli elaborati di questo compito, comprese le schede di valutazione compilate precedentemente. Rimarranno i voti complessivi degli elaborati valutati in precedenza.';
$string['messageprovider:submission'] = 'Notifiche della ricevuta digitale del plugin del plagio di Turnitin';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Stato Turnitin';
$string['deleted'] = 'Eliminato';
$string['pending'] = 'In sospeso';
$string['because'] = 'Un amministratore ha eliminato il compito in sospeso dalla coda di elaborazione e ha interrotto la consegna a Turnitin.<br /><strong>Il file esiste ancora in Moodle, contatta il docente.</strong><br />Cerca di seguito eventuali codici di errore:';
$string['submitpapersto_help'] = '<strong>Nessun archivio: </strong><br />Turnitin è istruito di non archiviare in alcun archivio i documenti inviati. Elaboreremo gli elaborati solo per eseguire il controllo di somiglianza iniziale.<br /><br /><strong>Archivio standard: </strong><br />Turnitin archivierà una copia del documento inviato solo nell\'archivio standard. Scegliendo questa opzione, Turnitin è istruito di utilizzare solo i documenti archiviati per eseguire controlli di somiglianza rispetto ai documenti inviati in futuro.<br /><br /><strong>Archivio dell&#39;istituto (ove applicabile): </strong><br />La scelta di questa opzione istruisce Turnitin di aggiungere solo i documenti inviati a un archivio privato del proprio istituto. I controlli di somiglianza con documenti inviati saranno eseguiti solo da altri docenti all\'interno del vostro istituto.';
$string['errorcode12'] = 'Questo file non è stato inviato a Turnitin perché appartiene a un compito il cui corso è stato eliminato. ID riga: ({$a-> id}) | ID modulo del corso: ({$a-> cm}) | ID utente: ({$a->userid})';
$string['tiiaccountconfig'] = 'Configurazione account Turnitin';
$string['turnitinaccountid'] = 'ID account Turnitin';
$string['turnitinsecretkey'] = 'Chiave condivisa Turnitin';
$string['turnitinapiurl'] = 'URL dell\&#39; API Turnitin';
$string['tiidebugginglogs'] = 'Debug e log';
$string['turnitindiagnostic'] = 'Abilita modalità diagnostica';
$string['enableperformancelogs'] = 'Abilita la registrazione di Network Performance';
$string['enableperformancelogs_desc'] = 'Se abilitata, ciascuna richiesta al server Turnitin verrà registrata in {tempdir}/turnitintooltwo/logs';
$string['turnitindiagnostic_desc'] = '<b>[Attenzione]</b><br />Abilita modalità diagnostica solo per rilevare i problemi relativi all&#39;API Turnitin.';
$string['tiiaccountsettings_desc'] = 'Verifica che queste impostazioni corrispondano a quelle configurate nell&#39;account Turnitin, altrimenti potrebbero verificarsi problemi con la creazione del compito e/o con le consegne degli studenti.';
$string['tiiaccountsettings'] = 'Impostazioni account Turnitin';
$string['turnitinusegrademark'] = 'Usa GradeMark';
$string['turnitinusegrademark_desc'] = 'Scegli se utilizzare GradeMark per valutare le consegne.<br /><i>(Disponibile solo per chi ha configurato GradeMark per il proprio account)</i>';
$string['turnitinenablepeermark'] = 'Abilita compiti PeerMark';
$string['turnitinenablepeermark_desc'] = 'Scegli se consentire o meno la creazione di compiti PeerMark<br/><i>(questa opzione è disponibile solo per chi ha configurato PeerMark nel proprio account)</i>';
$string['turnitinuseerater'] = 'Abilita ETS&copy;';
$string['turnitinuseerater_desc'] = 'Scegli se abilitare il controllo grammaticale ETS&copy; .<br /><i>(Attiva questa opzione solo se ETS&copy; e-rater è abilitato nel tuo account Turnitin)</i>';
$string['transmatch_desc'] = 'Determina se la Corrispondenza di testo tradotto sarà disponibile come impostazione nella schermata di impostazione del compito.<br /><i>(Abilita questa opzione solo se Corrispondenza di testo tradotto è abilitata nell&#39;account Turnitin)</i>';
$string['repositoryoptions_0'] = 'Abilita le opzioni archivio standard del docente';
$string['repositoryoptions_1'] = 'Abilita le opzioni archivio estese per il docente';
$string['repositoryoptions_2'] = 'Consegna tutti gli elaborati nell&#39;archivio standard';
$string['repositoryoptions_3'] = 'Non consegnare elaborati in un archivio';
$string['turnitinrepositoryoptions'] = 'Compiti dell&#39;archivio elaborati';
$string['turnitinrepositoryoptions_desc'] = 'Scegli le opzioni archivio per i compiti Turnitin.<br /><i>(Un archivio dell&#39;istituto è disponibile solo per chi ha abilitato questa opzione nel proprio account)</i>';
$string['tiimiscsettings'] = 'Impostazioni plugin varie';
$string['turnitintooltwoagreement_default'] = 'Selezionando questa casella, confermo che questa consegna è frutto del mio lavoro e accetto ogni responsabilità per qualsiasi violazione del copyright che si può verificare in seguito a questa consegna.';
$string['turnitintooltwoagreement_desc'] = '<b>[Facoltativo]</b><br />Inserisci una dichiarazione di conferma del contratto per le consegne.<br />(<b>Nota:</b> se il contratto viene lasciato completamente vuoto non sarà richiesta alcuna conferma da parte degli studenti durante la consegna)';
$string['turnitintooltwoagreement'] = 'Esclusione di responsabilità / Contratto';
$string['studentdataprivacy'] = 'Impostazioni sulla privacy dei dati dello studente';
$string['studentdataprivacy_desc'] = 'Le seguenti impostazioni possono essere configurate per garantire che i dati personali dello studente non vengano trasmessi a Turnitin tramite l&#39;API.';
$string['enablepseudo'] = 'Abilita Privacy studente';
$string['enablepseudo_desc'] = 'Se questa opzione è selezionata, gli indirizzi e-mail degli studenti saranno trasformati in pseudo equivalenti per le chiamate API Turnitin.<br /><i>(<b>Nota:</b> Questa opzione non può essere modificata se i dati utente Moodle sono già stati sincronizzati con Turnitin)</i>';
$string['pseudofirstname'] = 'Pseudo nome dello studente';
$string['pseudofirstname_desc'] = '<b>[Facoltativo]</b><br />Il nome dello studente da visualizzare nel visualizzatore documenti di Turnitin';
$string['pseudolastname'] = 'Pseudo cognome dello studente';
$string['pseudolastname_desc'] = 'Il cognome dello studente da visualizzare nel visualizzatore documenti Turnitin';
$string['pseudolastnamegen'] = 'Genera automaticamente il cognome';
$string['pseudolastnamegen_desc'] = 'Se impostato su "Sì" e lo pseudo cognome è impostato su un campo del profilo utente, il campo sarà automaticamente compilato con un identificatore univoco.';
$string['pseudoemailsalt'] = 'Pseudo crittografia Salt';
$string['pseudoemailsalt_desc'] = '<b>[Facoltativo]</b><br />Un salt facoltativo per aumentare la complessità dello pseudo indirizzo e-mail dello studente generato.<br />(<b>Nota:</b> Il salt deve restare invariato per mantenere la coerenza tra gli pseudo indirizzi e-mail)';
$string['pseudoemaildomain'] = 'Pseudo dominio e-mail';
$string['pseudoemaildomain_desc'] = '<b>[Facoltativo]</b><br />Un dominio facoltativo per gli pseudo indirizzi e-mail. (Per impostazione predefinita a @tiimoodle.com se vuoto)';
$string['pseudoemailaddress'] = 'Pseudo indirizzo e-mail';
$string['connecttest'] = 'Test di connessione di Turnitin';
$string['connecttestsuccess'] = 'Moodle è correttamente connesso a Turnitin.';
