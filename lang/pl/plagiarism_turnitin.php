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
$string['pluginname'] = 'Wtyczka plagiatu Turnitin';
$string['turnitintooltwo'] = 'Narzędzie Turnitin';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Zadanie wtyczki plagiatu Turnitin';
$string['connecttesterror'] = 'Wystąpił błąd podczas łączenia z Turnitin. Poniżej znajduje się informacja o błędzie:<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'Włącz Turnitin';
$string['excludebiblio'] = 'Pomiń bibliografię';
$string['excludequoted'] = 'Wyklucz cytaty';
$string['excludevalue'] = 'Wyklucz małe dopasowania';
$string['excludewords'] = 'Słowa';
$string['excludepercent'] = 'Procent';
$string['norubric'] = 'Brak arkusza';
$string['otherrubric'] = 'Użyj arkusza należącego do innego instruktora';
$string['attachrubric'] = 'Dołącz arkusz do tego zadania';
$string['launchrubricmanager'] = 'Uruchom Menedżera Arkuszy';
$string['attachrubricnote'] = 'Uwaga: Przed dokonaniem wysyłki studenci będą mogli zobaczyć dołączone arkusze wraz z ich zawartością.';
$string['erater_handbook_advanced'] = 'Zaawansowany';
$string['erater_handbook_highschool'] = 'Szkoła średnia';
$string['erater_handbook_middleschool'] = 'Gimnazjum';
$string['erater_handbook_elementary'] = 'Szkoła podstawowa';
$string['erater_handbook_learners'] = 'Uczący się angielskiego';
$string['erater_dictionary_enus'] = 'Słownik języka angielskiego (odmiana amerykańska)';
$string['erater_dictionary_engb'] = 'Słownik języka angielskiego (odmiana brytyjska)';
$string['erater_dictionary_en'] = 'Słowniki języka angielskigo: odmiany amerykańska i brytyjska';
$string['erater'] = 'Włącz sprawdzanie gramatyki e-rater';
$string['erater_handbook'] = 'Podręcznik ETS&copy;';
$string['erater_dictionary'] = 'Słownik e-rater';
$string['erater_categories'] = 'Kategorie e-rater';
$string['erater_spelling'] = 'Ortografia';
$string['erater_grammar'] = 'Gramatyka';
$string['erater_usage'] = 'Norma językowa';
$string['erater_mechanics'] = 'Pisownia i interpunkcja';
$string['erater_style'] = 'Styl';
$string['anonblindmarkingnote'] = 'Uwaga: Oddzielne ustawienie anonimowych poprawek Turnitin zostało usunięte. Do ustalenia statusu ustawienia anonimowych poprawek zostanie użyte ustawienie ślepych poprawek usługi Moodle.';
$string['transmatch'] = 'Przetłumaczone zbieżności';
$string['genduedate'] = 'Sporządzaj raporty w terminie oddania zadania (ponowne wysyłki dozwolone do terminu oddania zadania)';
$string['genimmediately1'] = 'Sporządzaj raporty natychmiastowo (ponowne wysyłki niedozwolone)';
$string['genimmediately2'] = 'Sporządzaj raporty natychmiastowo (ponowne wysyłki dozwolone do terminu oddania)';
$string['launchquickmarkmanager'] = 'Uruchom Menedżera Quickmark';
$string['launchpeermarkmanager'] = 'Uruchom Menedżera Peermark';
$string['studentreports'] = 'Wyświetl raporty oryginalności studentom';
$string['studentreports_help'] = 'Umożliwia wyświetlenie raportów oryginalności Turnitin studentom-użytkownikom. Wybór „tak” udostępnia raport oryginalności wygenerowany przez Turnitin do wglądu studenta.';
$string['submitondraft'] = 'Przekaż plik w momencie wysłania';
$string['submitonfinal'] = 'Przekaż plik kiedy student wyśle do oceny';
$string['draftsubmit'] = 'Kiedy plik powinien być przekazany do Turnitin?';
$string['allownonor'] = 'Zezwolić na wysyłkę plików dowolnego typu?';
$string['allownonor_help'] = 'To ustawienie umożliwi wysłanie każdego rodzaju pliku. Wybór &#34;Tak&#34; spowoduje, że wysyłki zostaną sprawdzone pod względem oryginalności tam, gdzie to możliwe, wysyłki będą dostępne do pobrania, a narzędzia do wydawania opinii GradeMark będą dostępne w miarę możliwości.';
$string['norepository'] = 'Brak magazynu';
$string['standardrepository'] = 'Magazyn standardowy';
$string['submitpapersto'] = 'Przechowuj prace studentów';
$string['institutionalrepository'] = 'Magazyn instytucji (jeśli dotyczy)';
$string['submitpapersto_help'] = 'Dzięki temu ustawieniu instruktorzy mają wybór, czy przechowywać prace w magazynie prac studenckich Turnitin. Przechowywanie prac w magazynie pozwala na porównanie\&#39; prac wysłanych do zadania z pracami z Twoich obecnych i dawnych klas. Jeśli wybierzesz &#34;nie do magazynu&#34;, prace Twoich studentów\&#39; nie będą przechowywane w magazynie prac studenckich Turnitin.';
$string['checkagainstnote'] = 'Uwaga: W przypadku niewybrania ustawienia „Tak” w co najmniej jednej opcji „Sprawdzić w...” widocznej poniżej raport oryginalności NIE zostanie utworzony.';
$string['spapercheck'] = 'Porównaj z przechowywanymi pracami studentów';
$string['internetcheck'] = 'Sprawdź w Internecie';
$string['journalcheck'] = 'Sprawdź w czasopismach,<br />periodykach i publikacjach';
$string['compareinstitution'] = 'Porównaj wysłane pliki z pracami wysłanymi wewnątrz tej instytucji';
$string['reportgenspeed'] = 'Szybkość generowania raportu';
$string['genspeednote'] = 'Uwaga: W przypadku ponownych wysyłek raport oryginalności może zostać wygenerowany z 24-godzinnym opóźnieniem.';
$string['locked_message'] = 'Komunikat o blokadzie';
$string['locked_message_help'] = 'Jeżeli jakiekolwiek ustawienia są zablokowane, ten wyświetlany komunikat będzie zawierał wyjaśnienie powodu blokady.';
$string['locked_message_default'] = 'To ustawienie jest zablokowane na poziomie strony internetowej';
$string['sharedrubric'] = 'Udostępniany arkusz';
$string['turnitinrefreshsubmissions'] = 'Odśwież wysyłki';
$string['turnitinrefreshingsubmissions'] = 'Odświeżanie wysyłek';
$string['turnitinppulapre'] = 'Aby przesłać plik do Turnitin, należy najpierw zaakceptować naszą umowę licencyjną użytkownika końcowego (EULA). Brak akceptacji umowy EULA spowoduje przesłanie pliku wyłącznie do usługi Moodle. Kliknij tutaj, aby zaakceptować.';
$string['noscriptula'] = '(Ponieważ javascript nie jest włączony, strona wymaga manualnego odświeżenia przed dokonaniem wysyłki i po zaakceptowaniu umowy użytkownika Turnitin)';
$string['filedoesnotexist'] = 'Plik został usunięty';

// Plugin settings.
$string['config'] = 'Konfiguracja';
$string['defaults'] = 'Ustawienia domyślne';
$string['showusage'] = 'Pokaż zrzut danych';
$string['saveusage'] = 'Zachowaj zrzut danych';
$string['errors'] = 'Błędy';
$string['turnitinconfig'] = 'Konfiguracja wtyczki plagiatu Turnitin';
$string['tiiexplain'] = 'Turnitin jest produktem komercyjnym — do korzystania z niego wymagana jest płatna subskrypcja. Więcej informacji znajduje się w <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>';
$string['useturnitin'] = 'Włącz Turnitin';
$string['useturnitin_mod'] = 'Włącz Turnitin dla {$a}';
$string['pp_configuredesc'] = 'Musisz skonfigurować ten moduł wewnątrz modułu turnitintooltwo. Kliknij <a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>tutaj</a>, aby skonfigurować tę wtyczkę';
$string['turnitindefaults'] = 'Ustawienia domyślne wtyczki plagiatu Turnitin';
$string['defaultsdesc'] = 'Następujące ustawienia są ustawieniami domyślnymi gdy Turnitin jest włączony wewnątrz modułu aktywności';
$string['turnitinpluginsettings'] = 'Ustawienia wtyczki plagiatu Turnitin';
$string['pperrorsdesc'] = 'Podczas próby wysłania poniższych plików do Turnitin wystąpił problem. Aby wysłać je ponownie, wybierz pliki, które mają zostać ponownie wysłane, i naciśnij przycisk Wyślij ponownie. Pliki zostaną przetworzone przy następnym uruchomieniu Cron.';
$string['pperrorssuccess'] = 'Wybrane pliki zostały wysłane ponownie i zostaną przetworzone przez Cron.';
$string['pperrorsfail'] = 'W przypadku niektórych wybranych plików wystąpił problem. Utworzenie dla nich nowego zdarzenia Cron nie powiodło się.';
$string['resubmitselected'] = 'Wyślij wybrane pliki ponownie';
$string['deleteconfirm'] = 'Czy na pewno chcesz usunąć tę wysyłkę?\n\nTej operacji nie można cofnąć.';
$string['deletesubmission'] = 'Usunąć wysyłkę';
$string['semptytable'] = 'Nie znaleziono rezultatów.';
$string['configupdated'] = 'Konfiguracja zaktualizowana';
$string['defaultupdated'] = 'Ustawienia domyślne Turnitin zaktualizowane';
$string['notavailableyet'] = 'Niedostępny';
$string['resubmittoturnitin'] = 'Wyślij ponownie do Turnitin';
$string['resubmitting'] = 'Ponowne wysyłanie';
$string['id'] = 'Identyfikator';
$string['student'] = 'Student';
$string['course'] = 'Kurs';
$string['module'] = 'Moduł';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'Zobacz raport oryginalności';
$string['launchrubricview'] = 'Zobacz arkusz użyty do poprawek';
$string['turnitinppulapost'] = 'Plik nie został przesłany do Turnitin. Kliknij tutaj, aby zaakceptować naszą umowę EULA.';
$string['ppsubmissionerrorseelogs'] = 'Plik nie został przesłany do Turnitin. Skontaktuj się z administratorem systemu.';
$string['ppsubmissionerrorstudent'] = 'Plik nie został przesłany do Turnitin. Skontaktuj się z tutorem, aby uzyskać więcej informacji.';

// Receipts.
$string['messageprovider:submission'] = 'Powiadomienia o potwierdzeniu elektronicznym wtyczki plagiatu Turnitin';
$string['digitalreceipt'] = 'Potwierdzenie elektroniczne';
$string['digital_receipt_subject'] = 'To jest Twoje potwierdzenie elektroniczne Turnitin';
$string['pp_digital_receipt_message'] = 'Witaj {$a->firstname} {$a->lastname},<br /><br />Udało Ci się pomyślnie przesłać plik <strong>{$a->submission_title}</strong> do zadania <strong>{$a->assignment_name}{$a->assignment_part}</strong> w ramach klasy <strong>{$a->course_fullname}</strong> dnia <strong>{$a->submission_date}</strong>. Twój identyfikator wysyłki to <strong>{$a->submission_id}</strong>. Pełne potwierdzenie elektroniczne można wyświetlić i wydrukować, używając przycisku drukowania/pobierania w przeglądarce dokumentów.<br /><br />Dziękujemy za korzystanie z systemu Turnitin.<br /><br />Zespół Turnitin';

// Paper statuses.
$string['turnitinid'] = 'Identyfikator Turnitin';
$string['turnitinstatus'] = 'Status systemu Turnitin';
$string['pending'] = 'Oczekujące';
$string['similarity'] = 'Podobieństwo';
$string['notorcapable'] = 'Wygenerowanie raportu oryginalności dla tego pliku jest niemożliwe.';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'Student zobaczył pracę';
$string['student_notread'] = 'Student nie zobaczył tej pracy.';
$string['launchpeermarkreviews'] = 'Uruchom recenzje Peermark';

// Cron.
$string['ppqueuesize'] = 'Liczba zdarzeń w kolejce zdarzeń wtyczki plagiatu';
$string['ppcronsubmissionlimitreached'] = 'Uruchomiona instancja Cron umożliwia przetworzenie jednorazowo {$a} wysyłek, dlatego kolejne wysyłki nie będą przez nią przesyłane do Turnitin';
$string['cronsubmittedsuccessfully'] = 'Wysyłka: {$a->title} (Identyfikator Turnitin: {$a->submissionid}) do zadania {$a->assignmentname} w ramach kursu {$a->coursename} została pomyślnie przesłana do Turnitin.';
$string['pp_submission_error'] = 'System Turnitin zwrócił błąd dotyczący Twojej wysyłki:';
$string['turnitindeletionerror'] = 'Usunięcie wysyłki do Turnitin nie powiodło się. Lokalna kopia Moodle została usunięta, ale wysyłka do Turnitin nie może być usunięta.';
$string['ppeventsfailedconnection'] = 'Wtyczka plagiatu Turnitin uruchomiona w ramach tej instancji Cron nie będzie przetwarzać żadnych zdarzeń, ponieważ ustanowienie połączenia z Turnitin nie jest możliwe.';

// Error codes.
$string['tii_submission_failure'] = 'Aby uzyskać więcej informacji, skonsultuj się ze swoim tutorem lub administratorem systemu';
$string['faultcode'] = 'Kod błędu';
$string['line'] = 'Wiersz';
$string['message'] = 'Wiadomość';
$string['code'] = 'Kod';
$string['tiisubmissionsgeterror'] = 'Wystąpił błąd podczas próby uzyskania przesyłek do tego zadania z Turnitin';
$string['errorcode0'] = 'Plik nie został przesłany do Turnitin. Skontaktuj się z administratorem systemu.';
$string['errorcode1'] = 'Ten plik nie został przesłany do Turnitin, ponieważ nie zawiera treści w ilości wystarczającej do wygenerowania raportu oryginalności.';
$string['errorcode2'] = 'Ten plik nie zostanie przesłany do Turnitin, ponieważ jego wielkość przekracza dozwolony rozmiar {$a}';
$string['errorcode3'] = 'Plik nie został przesłany do Turnitin, ponieważ użytkownik nie zaakceptował umowy licencyjnej użytkownika końcowego Turnitin.';
$string['errorcode4'] = 'Musisz wysłać typ pliku obsługiwany przez to zadanie. Akceptowane typy pliku to: .doc, .docx, .ppt, .pptx, .pps, .ppsx, .pdf, .txt, .htm, .html, .hwp, .odt, .wpd, .ps oraz .rtf';
$string['errorcode5'] = 'Ten plik nie został wysłany do Turnitin, ponieważ wystąpił problem z utworzeniem modułu w Turnitin, co uniemożliwia dokonywanie przesyłek. Szczegółowe informacje znajdują się w dziennikach API';
$string['errorcode6'] = 'Ten plik nie został wysłany do Turnitin, ponieważ wystąpił problem z edycją ustawień modułu w Turnitin, co uniemożliwia dokonywanie przesyłek. Szczegółowe informacje znajdują się w dziennikach API';
$string['errorcode7'] = 'Ten plik nie został wysłany do Turnitin, ponieważ wystąpił problem z utworzeniem użytkownika w Turnitin, co uniemożliwia dokonywanie przesyłek. Szczegółowe informacje znajdują się w dziennikach API';
$string['errorcode8'] = 'Ten plik nie został wysłany do Turnitin, ponieważ wystąpił problem z utworzeniem pliku tymczasowego. Najbardziej prawdopodobną przyczyną jest nieprawidłowa nazwa pliku. Zmień nazwę pliku i wyślij go ponownie za pomocą opcji Edytuj wysyłkę.';
$string['errorcode9'] = 'Plik nie może zostać wysłany, ponieważ w puli pliku nie znajduje się żadna dostępna treść, którą można wysłać.';
$string['coursegeterror'] = 'Nie udało się pobrać danych kursu';
$string['configureerror'] = 'Musisz w pełni skonfigurować ten moduł jako administrator, aby użyć go w kursie. Skontaktuj się z administratorem Moodle.';
$string['turnitintoolofflineerror'] = 'Wystąpiły tymczasowe trudności. Spróbuj ponownie później.';
$string['defaultinserterror'] = 'Wystąpił błąd podczas próby wprowadzenia ustawienia domyślnego do bazy danych';
$string['defaultupdateerror'] = 'Wystąpił błąd podczas próby aktualizacji ustawienia domyślnego w bazie danych';
$string['tiiassignmentgeterror'] = 'Wystąpił błąd podczas próby uzyskania zadania z Turnitin';
$string['assigngeterror'] = 'Nie udało się uzyskać danych narzędzia Turnitin';
$string['classupdateerror'] = 'Aktualizacja danych klasy Turnitin nie powiodła się';
$string['pp_createsubmissionerror'] = 'Wystąpił błąd podczas próby dokonania wysyłki w Turnitin';
$string['pp_updatesubmissionerror'] = 'Wystąpił błąd podczas próby dokonania ponownej wysyłki do Turnitin';
$string['tiisubmissiongeterror'] = 'Wystąpił błąd podczas próby uzyskania przesyłki z Turnitin';

// Javascript.
$string['closebutton'] = 'Zamknij';
$string['loadingdv'] = 'Wczytywanie przeglądarki dokumentów Turnitin...';
$string['changerubricwarning'] = 'Zmiana lub odłączenie arkusza spowoduje usunięcie wszystkich wyników arkusza za prace z tego zadania, łącznie z naliczonymi wcześniej kartami wyników. Ogólne oceny za wcześniej ocenione prace pozostaną bez zmian.';
$string['messageprovider:submission'] = 'Powiadomienia o potwierdzeniu elektronicznym wtyczki plagiatu Turnitin';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Status systemu Turnitin';
$string['deleted'] = 'Usunięto';
$string['pending'] = 'Oczekujące';
$string['because'] = 'Powodem tego jest usunięcie przez administratora oczekującego zadania z kolejki przetwarzania i przerwanie wysyłki do Turnitin.<br /><strong>Plik w dalszym ciągu znajduje się w systemie Moodle. Skontaktuj się z instruktorem.</strong><br />Poniżej znajdują się kody błędu (jeżeli są dostępne):';
