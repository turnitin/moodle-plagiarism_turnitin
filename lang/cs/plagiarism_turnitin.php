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
 * @package   plagiarism_turnitin
 * @copyright 2012 iParadigms LLC
 */

/*
 * To change this template, choose Tools | Templates.
 * and open the template in the editor.
 */

// General.
$string['pluginname'] = 'Plugin plagiátorství Turnitin';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Úloha pluginu plagiátorství Turnitin';
$string['connecttesterror'] = 'Došlo k chybě během připojení k Turnitin. Následuje chybové hlášení níže:<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'Povolit Turnitin';
$string['excludebiblio'] = 'Nezahrnout bibliografii';
$string['excludequoted'] = 'Nezahrnout citovaný materiál';
$string['excludevalue'] = 'Nezahrnout malé shody';
$string['excludewords'] = 'Slova';
$string['excludepercent'] = 'Procento';
$string['norubric'] = 'Žádná rubrika';
$string['otherrubric'] = 'Použít rubriku patřící jinému instruktorovi';
$string['attachrubric'] = 'Přiložit rubriku k zadání';
$string['launchrubricmanager'] = 'Spustit Správce rubrik';
$string['attachrubricnote'] = 'Poznámka: studenti si budou moci před odevzdáním prohlížet přiložené rubriky a jejich obsah.';
$string['erater_handbook_advanced'] = 'Pokročilý';
$string['erater_handbook_highschool'] = 'Střední škola';
$string['erater_handbook_middleschool'] = '2. stupeň';
$string['erater_handbook_elementary'] = 'Základní škola';
$string['erater_handbook_learners'] = 'Studenti angličtiny';
$string['erater_dictionary_enus'] = 'Slovník americké angličtiny';
$string['erater_dictionary_engb'] = 'Slovník britské angličtiny';
$string['erater_dictionary_en'] = 'Slovníky americké i britské angličtiny';
$string['erater'] = 'Povolit kontrolu gramatiky e-rater';
$string['erater_handbook'] = 'ETS&copy; příručka';
$string['erater_dictionary'] = 'Slovník e-rater';
$string['erater_categories'] = 'Kategorie e-rater';
$string['erater_spelling'] = 'Pravopis';
$string['erater_grammar'] = 'Gramatika';
$string['erater_usage'] = 'Použití';
$string['erater_mechanics'] = 'Mechanika';
$string['erater_style'] = 'Styl';
$string['anonblindmarkingnote'] = 'Poznámka: Samostatné nastavení anonymního označení Turnitin bylo odstraněno. Turnitin bude používat nastavení zaslepeného označení Moodle k určení anonymního nastavení označení.';
$string['transmatch'] = 'Přeložená shoda';
$string['genduedate'] = 'Generovat zprávy k termínu dokončení (studenti mohou práci znovu odevzdat až do termínu dokončení)';
$string['genimmediately1'] = 'Generovat zprávy bezprostředně (studenti nemohou práci znovu odevzdat)';
$string['genimmediately2'] = 'Generovat zprávy bezprostředně (studenti mohou opakovaně odevzdávat až do termínu dokončení): Po {$a->num_resubmissions} opakovaných odevzdáních se zprávy generují po {$a->num_hours} hodinách';
$string['launchquickmarkmanager'] = 'Spustit Správce Quickmark';
$string['launchpeermarkmanager'] = 'Spustit Správce Peermark';
$string['studentreports'] = 'Zobrazit studentům zprávy o původnosti';
$string['studentreports_help'] = 'Umožní vám zobrazit zprávy o původnosti studentským uživatelům. Pokud je volba nastavena na hodnotu Ano, budou si studenti moci zobrazit zprávu o původnosti vytvořenou systémem Turnitin.';
$string['submitondraft'] = 'Odevzdat soubor při prvním nahrání';
$string['submitonfinal'] = 'Odevzdat soubor, když jej student odešle ke známkování';
$string['draftsubmit'] = 'Kdy má být soubor odevzdán do systému Turnitin?';
$string['allownonor'] = 'Povolit odevzdání jakéhokoli typu souboru?';
$string['allownonor_help'] = 'Toto nastavení umožní nahrání jakéhokoli druhu souboru. Pokud je tato možnost nastavena jako &#34;Ano&#34;, bude původnost zkontrolována u všech odevzdaných prací, kde je to možné. Odevzdané práce budou dostupné ke stáhnutí, budou dostupné i nástroje zpětné vazby GradeMark, kde to bude možné.';
$string['norepository'] = 'Žádný archiv';
$string['standardrepository'] = 'Standardní archiv';
$string['submitpapersto'] = 'Uložit studentské práce';
$string['institutionalrepository'] = 'Archiv instituce (v případě potřeby)';
$string['checkagainstnote'] = 'Poznámka: Pokud nezvolíte „Ano“ pro alespoň jednu z možností „Zkontrolovat proti...“ níže, pak nebude vygenerována zpráva originality.';
$string['spapercheck'] = 'Porovnejte s uloženými studentskými pracemi';
$string['internetcheck'] = 'Porovnat s internetem';
$string['journalcheck'] = 'Porovnat s odbornými časopisy, <br />periodiky a publikacemi.';
$string['compareinstitution'] = 'Porovnat odevzdané soubory s odevzdanými pracemi v rámci této instituce';
$string['reportgenspeed'] = 'Rychlost generování zpráv';
$string['locked_message'] = 'Uzamčená zpráva';
$string['locked_message_help'] = 'V případě uzamčení nastavení tato zpráva uvádí důvod.';
$string['locked_message_default'] = 'Toto nastavení je uzamčeno na úrovni stránky';
$string['sharedrubric'] = 'Sdílená rubrika';
$string['turnitinrefreshsubmissions'] = 'Obnovit odevzdané práce';
$string['turnitinrefreshingsubmissions'] = 'Aktualizace odevzdaných prací';
$string['turnitinppulapre'] = 'Chcete-li odevzdat soubor do služby Turnitin, musíte nejprve přijmout naši smlouvu EULA. Pokud se rozhodnete nepřijmout naši smlouvu EULA, bude váš soubor odevzdán pouze do Moodle. Kliknutím zde potvrďte.';
$string['noscriptula'] = '(Jelikož nemáte zapnutý jazyk Javascript, budete muset tuto stránku ručně obnovit předtím, než budete moci provést odevzdání své práce, a to až po přijetí Podmínek pro uživatele Turnitin)';
$string['filedoesnotexist'] = 'Soubor byl smazán.';
$string['reportgenspeed_resubmission'] = 'Do tohoto úkolu jste již odevzdali práci a Zpráva o podobnosti pro ni byla vytvořena. Pokud se rozhodnete práci znovu odevzdat, dřívější odevzdání bude nahrazeno a bude vytvořena nová zpráva. Poté, co práci {$a->num_resubmissions}x opakovaně odevzdáte, budete muset na zobrazení nové Zprávy o podobnosti počkat {$a->num_hours} hodin.';

// Plugin settings.
$string['config'] = 'Konfigurace';
$string['defaults'] = 'Výchozí nastavení';
$string['showusage'] = 'Zobrazit výpis dat';
$string['saveusage'] = 'Uložit výpis dat';
$string['errors'] = 'Chyby';
$string['turnitinconfig'] = 'Konfigurace pluginu plagiátorství Turnitin';
$string['tiiexplain'] = 'Turnitin je komerční produkt, tudíž musíte mít pro využívání této služby předplatné. Pro více informací se obraťte na <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>';
$string['useturnitin'] = 'Povolit Turnitin';
$string['useturnitin_mod'] = 'Aktivovat Turnitin pro {$a}';
$string['turnitindefaults'] = 'Výchozí nastavení pro plugin plagiátorství Turnitin';
$string['defaultsdesc'] = 'Následující nastavení jsou výchozí při povolení systému Turnitin v rámci modulu aktivity';
$string['turnitinpluginsettings'] = 'Nastavení pro plugin plagiátorství Turnitin';
$string['pperrorsdesc'] = 'Došlo k problému při pokusu o nahrávání souborů níže do služby Turnitin. Chcete-li je znovu odevzdat, vyberte požadované soubory a stiskněte tlačítko Znovu odevzdat. Soubory budou zpracovány při dalším spuštění cronu.';
$string['pperrorssuccess'] = 'Zvolené soubory byly znovu odevzdány a budou zpracovány cronem.';
$string['pperrorsfail'] = 'Došlo k problému s některými ze zvolených souborů. Nebylo možné pro ně vytvořit novou událost cron.';
$string['resubmitselected'] = 'Znovu odevzdat vybrané soubory';
$string['deleteconfirm'] = 'Určitě chcete smazat toto odevzdání?\n\nTento krok nelze vrátit zpět.';
$string['deletesubmission'] = 'Smazat odevzdání';
$string['semptytable'] = 'Nebyly nalezeny žádné výsledky.';
$string['configupdated'] = 'Konfigurace aktualizována';
$string['defaultupdated'] = 'Výchozí hodnoty Turnitin byly aktualizovány';
$string['notavailableyet'] = 'Není k dispozici';
$string['resubmittoturnitin'] = 'Znovu odevzdat do Turnitin';
$string['resubmitting'] = 'Opětovné odevzdání';
$string['id'] = 'Id';
$string['student'] = 'Student';
$string['course'] = 'Kurz';
$string['module'] = 'Modul';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'Zobrazit zprávu o původnosti';
$string['launchrubricview'] = 'Zobrazit rubriku použitou ke známkování';
$string['turnitinppulapost'] = 'Váš soubory nebyl odevzdán do systému Turnitin. Kliknutím zde přijmete naši smlouvu EULA.';
$string['ppsubmissionerrorseelogs'] = 'Soubor nebyl odevzdán do systému Turnitin. Poraďte se se správcem systému.';
$string['ppsubmissionerrorstudent'] = 'Soubor nebyl předán do systému Turnitin. Další informace vám podá váš vyučující.';

// Receipts.
$string['messageprovider:submission'] = 'Oznámení digitálního příjmu pluginu plagiátorství Turnitin';
$string['digitalreceipt'] = 'Digitální doklad';
$string['digital_receipt_subject'] = 'Toto je váš digitální doklad Turnitin';
$string['pp_digital_receipt_message'] = 'Vážený {$a->firstname} {$a->lastname},<br /><br />úspěšně jste odevzdal/a soubor <strong>{$a->submission_title}</strong> do přiřazení <strong>{$a->assignment_name}{$a->assignment_part}</strong> v kurzu <strong>{$a->course_fullname}</strong> na <strong>{$a->submission_date}</strong>. Vaše ID odevzdání je <strong>{$a->submission_id}</strong>. Váš digitální doklad lze zobrazit a vytisknout pomocí tlačítka tisk/stáhnout v Prohlížeči dokumentů.<br /><br />Děkujeme, že používáte systém Turnitin,<br /><br />Tým Turnitin';

// Paper statuses.
$string['turnitinid'] = 'ID Turnitin';
$string['turnitinstatus'] = 'Stav systému Turnitin';
$string['pending'] = 'Probíhá';
$string['similarity'] = 'Podobnost';
$string['notorcapable'] = 'Pro tento soubor nelze vytvořit zprávu o původnosti.';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'Student si práci prohlédl dne:';
$string['student_notread'] = 'Student si tuto práci neprohlédl.';
$string['launchpeermarkreviews'] = 'Spustit Posudky Peermark';

// Cron.
$string['ppqueuesize'] = 'Počet událostí ve frontě pluginu plagiátorství';
$string['ppcronsubmissionlimitreached'] = 'Do systému Turnitin nebyly odeslány žádné další odevzdání tímto spuštěním cron, jelikož na spuštění bylo zpracováno pouze {$a}';
$string['cronsubmittedsuccessfully'] = 'Odevzdání: {$a->title} (TII ID: {$a->submissionid}) pro přiřazení {$a->assignmentname} v kurzu {$a->coursename} bylo úspěšně odevzdáno do systému Turnitin.';
$string['pp_submission_error'] = 'Systém Turnitin vrátil s odevzdáním chybu:';
$string['turnitindeletionerror'] = 'Smazání odevzdaných prací Turnitin se nezdařilo. Místní kopie Moodle byla odstraněna, ale odevzdané práce v systému Turnitin nebylo možné smazat.';
$string['ppeventsfailedconnection'] = 'V rámci tohoto spuštění cron nebudou pluginem plagiátorství Turnitin zpracovány žádné události, jelikož připojení k systému Turnitin nelze navázat.';

// Error codes.
$string['tii_submission_failure'] = 'Další informace vám podá váš vyučující nebo správce systému.';
$string['faultcode'] = 'Chybný kód';
$string['line'] = 'Řádek';
$string['message'] = 'Zpráva';
$string['code'] = 'Kód';
$string['tiisubmissionsgeterror'] = 'Došlo k chybě při získávání odevzdané práce pro tento úkol ze systému Turnitin';
$string['errorcode0'] = 'Soubor nebyl odevzdán do systému Turnitin. Poraďte se se správcem systému.';
$string['errorcode1'] = 'Tento soubor nebyl odeslán do systému Turnitin, jelikož neobsahuje dostatek obsahu k vytvoření zprávy o původnosti.';
$string['errorcode2'] = 'Soubor nebude předán do systému Turnitin, protože přesahuje maximální povolenou velikost {$a}.';
$string['errorcode3'] = 'Soubor nebyl předán do systému Turnitin, protože uživatel nepřijal licenční ujednání koncového uživatele systému Turnitin.';
$string['errorcode4'] = 'Pro tento úkol musíte nahrát podporovaný typ souboru. Přijímané typy souborů jsou; .doc, .docx, .ppt, .pptx, .pps, .ppsx, .pdf, .txt, .htm, .html, .hwp, .odt, .wpd, .ps a .rtf';
$string['errorcode5'] = 'Tento soubor nebyl odevzdán do systému Turnitin, protože došlo k problému při vytváření modulu v systému Turnitin, který brání odevzdání. Další informace naleznete v protokolech API.';
$string['errorcode6'] = 'Tento soubor nebyl odevzdán do systému Turnitin, protože došlo k problému při úpravách nastavení modulu v systému Turnitin, který brání odevzdání. Další informace naleznete v protokolech API.';
$string['errorcode7'] = 'Tento soubor nebyl odevzdán do systému Turnitin, protože došlo k problému při vytváření uživatele v systému Turnitin, který brání odevzdání. Další informace naleznete v protokolech API.';
$string['errorcode8'] = 'Tento soubor nebyl odevzdán do systému Turnitin, protože došlo k problému při vytváření dočasného souboru. Nejpravděpodobnější příčinou je neplatný název souboru. Přejmenujte soubor a znovu ho nahrajte pomocí položky Upravit odevzdání.';
$string['errorcode9'] = 'Soubor nelze odevzdat, jelikož ve fondu souborů není žádný dostupný obsah k odevzdání.';
$string['coursegeterror'] = 'Nebylo možné získat údaje o kurzu';
$string['configureerror'] = 'Tento modul před jeho použitím v kurzu musíte zcela nastavit v roli Administrátora. Obraťte se prosím na svého Moodle administrátora.';
$string['turnitintoolofflineerror'] = 'Vyskytl se dočasný problém. Zkuste prosím za chvíli znovu.';
$string['defaultinserterror'] = 'Došlo k chybě při vkládání hodnoty výchozího nastavení do databáze';
$string['defaultupdateerror'] = 'Došlo k chybě při aktualizaci hodnoty výchozího nastavení v databázi';
$string['tiiassignmentgeterror'] = 'Došlo k chybě při získávání úkolu ze systému Turnitin';
$string['assigngeterror'] = 'Nelze získat Turnitin údaje';
$string['classupdateerror'] = 'Nebylo možné aktualizovat údaje kurzu Turnitin';
$string['pp_createsubmissionerror'] = 'Došlo k chybě při tvoření odevzdání v systému Turnitin';
$string['pp_updatesubmissionerror'] = 'Došlo k chybě při opětovném odeslání vaší odevzdané práce do systému Turnitin';
$string['tiisubmissiongeterror'] = 'Došlo k chybě při získávání odevzdané práce ze systému Turnitin';

// Javascript.
$string['closebutton'] = 'Zavřít';
$string['loadingdv'] = 'Načítá se Prohlížeč dokumentů Turnitin...';
$string['changerubricwarning'] = 'Úprava nebo odpojení rubriky odstraní všechna existující bodování rubrik u prací v tomto úkolu včetně bodovacích karet, které byly předtím oznámkované. U dříve ohodnocených prací zůstane celkové hodnocení zachováno.';
$string['messageprovider:submission'] = 'Oznámení digitálního příjmu pluginu plagiátorství Turnitin';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Stav systému Turnitin';
$string['deleted'] = 'Smazáno';
$string['pending'] = 'Probíhá';
$string['because'] = 'Je to způsobeno smazáním probíhajícího úkolu z fronty zpracování správcem a zrušeným odevzdáním do systému Turnitin.<br /><strong>Soubor se stále nachází v systému Moodle, obraťte se na svého instruktora.</strong><br />Níže naleznete všechny chybové kódy:';
$string['submitpapersto_help'] = '<strong>Žádný archiv: </strong><br />Turnitin nebude odevzdané práce ukládat do žádného úložiště. Práci zpracujeme pouze pro účely kontroly podobnosti.<br /><br /><strong>Standardní archiv: </strong><br />Turnitin uloží kopii odevzdaného dokumentu pouze do standardního úložiště. Pokud je vybrána tato možnost, bude Turnitin používat uložené dokumenty pouze ke kontrole podobnosti s dokumenty, které budou odevzdány v budoucnu.<br /><br /><strong>Archiv instituce (v případě potřeby): </strong><br />Tato možnost znamená, že bude Turnitin odevzdané dokumenty přidávat pouze do soukromého úložiště vaší instituce. Kontroly podobnosti odevzdaných dokumentů budou provádět pouze jiní instruktoři ve vaší instituci.';
$string['errorcode12'] = 'Tento soubor nebyl odevzdán do systému Turnitin, protože patří k úkolu v kursu, který byl  odstraněn. ID řádku: ({$a->id}) | ID modulu kurzu: ({$a->cm}) | ID uživatele: ({$a->userid})';
$string['tiiaccountconfig'] = 'Konfigurace účtu Turnitin';
$string['turnitinaccountid'] = 'ID účtu Turnitin';
$string['turnitinsecretkey'] = 'Sdílený klíč Turnitin';
$string['turnitinapiurl'] = 'Turnitin API URL';
$string['tiidebugginglogs'] = 'Ladění a protokolování';
$string['turnitindiagnostic'] = 'Povolit diagnostický režim';
$string['enableperformancelogs'] = 'Povolte protokolování výkonu sítě';
$string['enableperformancelogs_desc'] = 'V případě povolení bude každá žádost na serveru Turnitin zaprotokolována v protokolech {tempdir}/plagiarism_turnitin/logs';
$string['turnitindiagnostic_desc'] = '<b>[Caution]</b><br />Povolit diagnostický režim pouze za účelem vystopování problémů s Turnitin API.';
$string['tiiaccountsettings_desc'] = 'Ověřte, zda se tato nastavení shodují s těmi nakonfigurovanými v účtu Turnitin, v opačném případě se můžete setkat s potížemi při vytváření úkolů a/nebo odevzdávání prací studenty.';
$string['tiiaccountsettings'] = 'Nastavení účtu Turnitin';
$string['turnitinusegrademark'] = 'Používání systému GradeMark';
$string['turnitinusegrademark_desc'] = 'Zvolte, zda má být při hodnocení odevzdaných prací použit systém GradeMark.<br /><i>(Tato možnost je dostupná, pouze pokud máte na svém účtu nakonfigurován systém GradeMark)</i>';
$string['turnitinenablepeermark'] = 'Povolit úkoly Peermark';
$string['turnitinenablepeermark_desc'] = 'Zvolte, zda má být povoleno vytvoření úkolů Peermark<br/><i>(Tato možnost je dostupná pouze pro účty, které mají nakonfigurovánu funkci Peermark)</i>';
$string['turnitinuseerater'] = 'Povolit ETS&copy;';
$string['turnitinuseerater_desc'] = 'Zvolte, zda má být povolena kontrola pravopisu ETS&copy;.<br /><i>(Aktivujte tuto možnost, pouze pokud je ETS&copy; e-rater povolen u vašeho účtu Turnitin)</i>';
$string['transmatch_desc'] = 'Stanoví, zda bude Přeložená shoda dostupná jako možnost nastavení na obrazovce nastavení úkolu.<br /><i>(Povolte tuto možnost pouze tehdy, pokud je Přeložená shoda povolena na vašem Turnitin účtu)</i>';
$string['repositoryoptions_0'] = 'Dát instruktorovi standardní volby archivu';
$string['repositoryoptions_1'] = 'Aktivovat instruktorské rozšířené možnosti archivu';
$string['repositoryoptions_2'] = 'Odevzdat všechny práce do standardního archivu';
$string['repositoryoptions_3'] = 'Neodesílejte žádné práce do archivu';
$string['turnitinrepositoryoptions'] = 'Úkoly archivu prací';
$string['turnitinrepositoryoptions_desc'] = 'Zvolte možnosti archivu pro úkoly Turnitin. <br /><i>(Archiv instituce mají k dispozici pouze ti, kdo si ho aktivovali u svého účtu)</i>';
$string['tiimiscsettings'] = 'Různé nastavení pluginu';
$string['pp_agreement_default'] = 'Potvrzuji, že tato odevzdaná práce je moje vlastní práce, a přijímám veškerou zodpovědnost za případná porušení autorských práv, která mohou nastat v důsledku tohoto odevzdání.';
$string['pp_agreement_desc'] = '<b>[Optional]</b><br />Zadejte prohlášení o potvrzení dohody pro odevzdání prací.<br />(<b>Poznámka:</b> Pokud je dohoda ponechána zcela prázdná, nebude od studentů během odevzdání požadováno její potvrzení)';
$string['pp_agreement'] = 'Prohlášení / Dohoda';
$string['studentdataprivacy'] = 'Nastavení ochrany osobních údajů studenta';
$string['studentdataprivacy_desc'] = 'Následující nastavení lze nakonfigurovat tak, aby bylo zajištěno, že osobní údaje studentů nebudou předány do systému Turnitin prostřednictvím API.';
$string['enablepseudo'] = 'Povolit soukromí studenta';
$string['enablepseudo_desc'] = 'Je-li tato možnost vybrána, e-mailové adresy studentů budou přeměněny na zástupný ekvivalent pro hovory Turnitin API.<br /><i>(<b>Poznámka:</b> Tuto možnost nelze změnit, pokud již byly jakékoli údaje Moodle uživatele synchronizovány se systémem Turnitin)</i>';
$string['pseudofirstname'] = 'Zástupné křestní jméno studenta';
$string['pseudofirstname_desc'] = '<b>[Optional]</b><br />Křestní jméno studenta pro zobrazení v prohlížeči dokumentů Turnitin';
$string['pseudolastname'] = 'Zástupné příjmení studenta';
$string['pseudolastname_desc'] = 'Příjmení studenta pro zobrazení v prohlížeči dokumentů Turnitin';
$string['pseudolastnamegen'] = 'Automatické generování příjmení';
$string['pseudolastnamegen_desc'] = 'V případě nastavení na ano a pseudo příjmení je nastaveno na pole uživatelského profilu, pak bude pole automaticky vyplněno pomocí jedinečného identifikátoru.';
$string['pseudoemailsalt'] = 'Náhodný řetězec zástupného šifrování';
$string['pseudoemailsalt_desc'] = '<b>[Optional]</b><br />Volitelný řetězec pro zvýšení složitosti generovaných Pseudo studentských e-mailových adres.<br />(<b>Poznámka:</b> Řetězec nesmí být změněn, aby se zachovala konzistence pseudo e-mailových adres)';
$string['pseudoemaildomain'] = 'E-mailová pseudo-doména';
$string['pseudoemaildomain_desc'] = '<b>[Optional]</b><br />Volitelná doména pro zástupné e-mailové adresy. (Je-li pole prázdné, výchozí hodnota je @tiimoodle.com)';
$string['pseudoemailaddress'] = 'Zástupná e-mailová adresa';
$string['connecttest'] = 'Prověřit připojení Turnitin';
$string['connecttestsuccess'] = 'Moodle byl úspěšně připojen k Turnitin.';
$string['diagnosticoptions_0'] = 'Vypnuto';
$string['diagnosticoptions_1'] = 'Standardní';
$string['diagnosticoptions_2'] = 'Ladění';
$string['repositoryoptions_4'] = 'Odeslat všechy práce do archivu instituce';
$string['turnitinrepositoryoptions_help'] = '<strong>Dát instruktorovi standardní volby archivu: </strong><br />Instruktoři mohou Turnitin nastavit tak, aby dokumenty přidával buď do standardního úložiště, do soukromého úložiště vaší instituce nebo do žádného úložiště.<br /><br /><strong>Aktivovat instruktorské rozšířené možnosti archivu: </strong><br />Tato možnost instruktorům umožňuje zobrazit nastavení úkolu, aby mohli dát studentům instrukce, kde jejich dokumenty budou uloženy. Studenti mohou své dokumenty přidat do standardního úložiště pro studenty nebo do soukromého úložiště vaší instituce.<br /><br /><strong>Odevzdat všechny práce do standardního archivu: </strong><br />Ve výchozím nastavení se všechny dokumenty přidávají do stadnardního úložiště pro studenty.<br /><br /><strong>Neodesílejte žádné práce do archivu: </strong><br />Dokumenty se budou používat pouze k úvodní kontrole v systému Turnitin a zobrazí se pouze instruktorovi pro účely známkování.<br /><br /><strong>Odeslat všechy práce do archivu instituce: </strong><br />Turnitim bude všechny práce ukládat do archivu prací instituce. Kontroly podobnosti odevzdaných prací budou provádět pouze jiní instruktoři ve vaší instituci.';
$string['turnitinuseanon'] = 'Použijte anonymní známkování';
$string['turnitinuseanon_desc'] = 'Zvolte, zda má být anonymní známkování povoleno při hodnocení odevzdaných prací.<br /><i>(Tato možnost je dostupná, pouze pokud máte na svém účtu anonymní známkování nakonfigurováno)</i>';
$string['createassignmenterror'] = 'Došlo k chybě při tvoření úkolu v systému Turnitin';
$string['editassignmenterror'] = 'Došlo k chybě při úpravách úkolu v systému Turnitin';
$string['ppassignmentediterror'] = 'Modul: {$a->title} (TII ID: {$a->assignmentid}) nelze v systému Turnitin upravit. Další informace najdete v API protokolu.';
$string['pp_classcreationerror'] = 'Tuto třídu nelze v systému Turnitin vytvořit. Další informace najdete v API protokolu.';
$string['unlinkusers'] = 'Odpojit studenty';
$string['relinkusers'] = 'Opětovně propojit uživatele';
$string['unlinkrelinkusers'] = 'Odpojit / Opětovně propojit uživatele Turnitin';
$string['nointegration'] = 'Žádná integrace';
$string['sprevious'] = 'Předchozí';
$string['snext'] = 'Další';
$string['slengthmenu'] = 'Show _MENU_ Entries';
$string['ssearch'] = 'Vyhledat:';
$string['sprocessing'] = 'Načítání dat ze systému Turnitin...';
$string['szerorecords'] = 'Žádné záznamy k zobrazení.';
$string['sinfo'] = 'Showing _START_ to _END_ of _TOTAL_ entries.';
$string['userupdateerror'] = 'Nelze aktualizovat uživatelské údaje';
$string['connecttestcommerror'] = 'Nebylo možné se k Turnitin připojit. Zkontrolujte své API URL nastavení.';
