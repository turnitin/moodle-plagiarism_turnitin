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
$string['pluginname'] = 'Plugin plagiátorství Turnitin';
$string['turnitintooltwo'] = 'Nástroj Turnitin';
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
$string['submitpapersto_help'] = 'Nastavení poskytuje instruktorům možnost zvolit, zda práce budou uloženy v archivu prací Turnitin. Výhodou odevzdání prací do tohoto archivu je, že práce odevzdané v rámci tohoto úkolu jsou porovnány s dalšími odevzdanými pracemi v rámci vašich současných a předchozích kurzů. Jestliže zvolíte &#34;bez archivu&#34;, odevzdané práce nebudou uloženy v archivu prací Turnitin.';
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
$string['pp_configuredesc'] = 'Tento modul musíte nakonfigurovat v rámci modulu turnitintooltwo. Kliknutím <a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>zde</a> nakonfigurujete tento modul plugin';
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
$string['assigngeterror'] = 'Nelze získat turnitintooltwo údaje';
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
