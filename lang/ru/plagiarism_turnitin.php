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
$string['pluginname'] = 'Модуль Turnitin для проверки на плагиат';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Задача модуля Turnitin для проверки на плагиат';
$string['connecttesterror'] = 'Произошла ошибка при подключении к Turnitin. Сообщение об ошибке:<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'Включить Turnitin';
$string['excludebiblio'] = 'Исключить библиографию';
$string['excludequoted'] = 'Исключить цитаты';
$string['excludevalue'] = 'Исключить незначительные совпадения';
$string['excludewords'] = 'Слова';
$string['excludepercent'] = 'Процент';
$string['norubric'] = 'Без рубрики';
$string['otherrubric'] = 'Использовать рубрику, принадлежащую другому преподавателю';
$string['attachrubric'] = 'Добавить рубрику к этому заданию';
$string['launchrubricmanager'] = 'Запустить диспетчер рубрик';
$string['attachrubricnote'] = 'Примечание: студенты смогут просматривать добавленные рубрики и их содержание до сдачи работы.';
$string['erater_handbook_advanced'] = 'Продвинутый';
$string['erater_handbook_highschool'] = 'Старшие классы';
$string['erater_handbook_middleschool'] = 'Средняя школа';
$string['erater_handbook_elementary'] = 'Начальная школа';
$string['erater_handbook_learners'] = 'Студенты английского языка';
$string['erater_dictionary_enus'] = 'Словарь американского английского языка';
$string['erater_dictionary_engb'] = 'Словарь британского английского языка';
$string['erater_dictionary_en'] = 'Словари американского и британского английского языка';
$string['erater'] = 'Включить проверку грамматики с помощью e-rater';
$string['erater_handbook'] = 'Руководство ETS&copy;';
$string['erater_dictionary'] = 'Словарь e-rater';
$string['erater_categories'] = 'Категории e-rater';
$string['erater_spelling'] = 'Орфография';
$string['erater_grammar'] = 'Грамматика';
$string['erater_usage'] = 'Применение';
$string['erater_mechanics'] = 'Синтаксис';
$string['erater_style'] = 'Стиль';
$string['anonblindmarkingnote'] = 'Примечание: в системе Turnitin больше нет отдельной настройки для обеспечения анонимности работ. Turnitin будет использовать настройку анонимности Moodle.';
$string['transmatch'] = 'Переведенные совпадения';
$string['genduedate'] = 'Генерировать в дату выполнения (повторная отправка работы разрешена до даты выполнения)';
$string['genimmediately1'] = 'Генерировать отчеты немедленно (повторная отправка работы запрещена)';
$string['genimmediately2'] = 'Генерировать отчеты немедленно (повторная отправка работы разрешена до установленного срока) После {$a->num_resubmissions} повторной отправки работы отчеты генерируются через {$a->num_hours} час (-а/-ов)';
$string['launchquickmarkmanager'] = 'Запустить диспетчер Quickmark';
$string['launchpeermarkmanager'] = 'Запустить диспетчер Рееrmark';
$string['studentreports'] = 'Отобразить свидетельства оригинальности для студентов';
$string['studentreports_help'] = 'Позволяет показывать свидетельства оригинальности Turnitin студентам-пользователям. Если установлено значение «да», то свидетельства оригинальности, генерируемые Turnitin, доступны для просмотра студентами.';
$string['submitondraft'] = 'Отправить файл при первой загрузке';
$string['submitonfinal'] = 'Отправить файл, когда студент посылает файл для оценки';
$string['draftsubmit'] = 'Когда файл должен быть представлен в Turnitin?';
$string['allownonor'] = 'Разрешить сдачу работ в файлах любого формата?';
$string['allownonor_help'] = 'Этот параметр позволяет студентам сдавать работы в файлах любого формата. Если выбрать значение &#34;Да&#34;, отправленные работы будут по мере возможности проверяться на оригинальность содержания, работы можно будет загружать, и по мере возможности будет предоставлен доступ к инструментам комментирования GradeMark.';
$string['norepository'] = 'Не сохранять';
$string['standardrepository'] = 'Стандартное хранилище';
$string['submitpapersto'] = 'Сохранять работы студентов';
$string['institutionalrepository'] = 'Хранилище учебного заведения (если применимо)';
$string['checkagainstnote'] = 'Примечание: если выбрать вариант «Да» хотя бы для одной из перечисленных ниже проверок, то отчет об оригинальности формироваться НЕ будет.';
$string['spapercheck'] = 'Проверить по сохраненным работам студентов';
$string['internetcheck'] = 'Проверить в Интернете';
$string['journalcheck'] = 'Проверить по журналам,<br />периодическим изданиям и публикациям';
$string['compareinstitution'] = 'Сравнить полученные файлы с работами, отправленными в этом учебном заведении';
$string['reportgenspeed'] = 'Скорость формирования отчета';
$string['locked_message'] = 'Сообщение о блокировке';
$string['locked_message_help'] = 'Если какие-либо из настроек заблокированы, отображается сообщение с указанием причины.';
$string['locked_message_default'] = 'Данная настройка заблокирована для всего сайта';
$string['sharedrubric'] = 'Общая рубрика';
$string['turnitinrefreshsubmissions'] = 'Обновить список отправленных работ';
$string['turnitinrefreshingsubmissions'] = 'Обновление списка отправленных работ';
$string['turnitinppulapre'] = 'Прежде чем отправить файл в систему Turnitin, необходимо принять условия соглашения с конечным пользователем. В противном случае вы сможете отправить файл только в систему Moodle. Нажмите здесь, чтобы принять условия.';
$string['noscriptula'] = '(Поскольку в вашем браузере не включен Javascript, прежде чем отправлять работу, вам необходимо будет вручную обновить эту страницу после того, как вы примете условия пользовательского соглашения.)';
$string['filedoesnotexist'] = 'Файл удален';
$string['reportgenspeed_resubmission'] = 'Вы уже отправили работу по этому заданию, и Отчет о подобии для отправленной работы уже создан. При выборе повторной отправки работы отправленная ранее работа будет заменена на новую и будет создан новый Отчет о подобии. После {$a->num_resubmissions} повторной отправки работы им необходимо подождать {$a->num_hours} час(-а/-ов), прежде чем можно будет просмотреть новый Отчет о подобии.';

// Plugin settings.
$string['config'] = 'Конфигурация';
$string['defaults'] = 'Настройки по умолчанию';
$string['showusage'] = 'Показать запись данных';
$string['saveusage'] = 'Сохранить запись данных';
$string['errors'] = 'Ошибки';
$string['turnitinconfig'] = 'Конфигурация модуля Turnitin для проверки на плагиат';
$string['tiiexplain'] = 'Turnitin является коммерческим продуктом, и право пользования этим сервисом предоставляется на основе платной подписки. Дополнительные сведения см. на странице <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>';
$string['useturnitin'] = 'Включить Turnitin';
$string['useturnitin_mod'] = 'Включить Turnitin для {$a}';
$string['turnitindefaults'] = 'Настройки модуля Turnitin для проверки на плагиат по умолчанию';
$string['defaultsdesc'] = 'Следующие настройки устанавливаются по умолчанию, когда Turnitin включается внутри модуля активности';
$string['turnitinpluginsettings'] = 'Настройки модуля Turnitin для проверки на плагиат';
$string['pperrorsdesc'] = 'При попытке загрузить эти файлы в систему Turnitin произошла ошибка. Чтобы отправить работу повторно, выберите файлы, которые вы хотите отправить, и нажмите кнопку повторной отправки. Обработка будет произведена во время следующего сеанса работы планировщика.';
$string['pperrorssuccess'] = 'Выбранные файлы отправлены повторно и ожидают обработки планировщиком.';
$string['pperrorsfail'] = 'Во время обработки некоторых выбранных файлов произошла ошибка. Не удалось создать новое событие планировщика.';
$string['resubmitselected'] = 'Отправить выбранные файлы повторно';
$string['deleteconfirm'] = 'Вы уверены, что хотите удалить эту работу? Это действие будет невозможно отменить.';
$string['deletesubmission'] = 'Удалить работу';
$string['semptytable'] = 'Результаты не найдены';
$string['configupdated'] = 'Конфигурация обновлена';
$string['defaultupdated'] = 'Значения по умолчанию Turnitin обновлены';
$string['notavailableyet'] = 'Недоступно';
$string['resubmittoturnitin'] = 'Отправить в Turnitin повторно';
$string['resubmitting'] = 'Повторная отправка';
$string['id'] = 'Идентификатор';
$string['student'] = 'Студент';
$string['course'] = 'Курс';
$string['module'] = 'Модуль';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'Просмотреть отчет об оригинальности';
$string['launchrubricview'] = 'Просмотреть рубрику, использованную для маркировки';
$string['turnitinppulapost'] = 'Не удалось отправить файл в Turnitin. Нажмите здесь, чтобы принять условия лицензионного соглашения с конечным пользователем.';
$string['ppsubmissionerrorseelogs'] = 'Не удалось отправить файл в систему Turnitin. Обратитесь за дополнительной информацией к системному администратору';
$string['ppsubmissionerrorstudent'] = 'Не удалось отправить файл в систему Turnitin. Обратитесь за дополнительной информацией к своему преподавателю';

// Receipts.
$string['messageprovider:submission'] = 'Уведомления о цифровой квитанции модуля Turnitin для проверки на плагиат';
$string['digitalreceipt'] = 'Цифровая квитанция';
$string['digital_receipt_subject'] = 'Это цифровая квитанция Turnitin';
$string['pp_digital_receipt_message'] = 'Уважаемый(-ая) {$a->firstname} {$a->lastname},<br /><br />Вы успешно отправили файл <strong>{$a->submission_title}</strong> с выполненным заданием <strong>{$a->assignment_name}{$a->assignment_part}</strong> по классу <strong>{$a->course_fullname}</strong> <strong>{$a->submission_date}</strong>. Идентификатор вашей работы — <strong>{$a->submission_id}</strong>. Цифровую квитанцию можно полностью просмотреть и напечатать, нажав на кнопку печати и загрузки в окне просмотра документов.<br /><br />Спасибо, что пользуетесь Turnitin.<br /><br />Команда Turnitin';

// Paper statuses.
$string['turnitinid'] = 'Идентификатор Turnitin';
$string['turnitinstatus'] = 'Статус Turnitin';
$string['pending'] = 'На рассмотрении';
$string['similarity'] = 'Совпадение';
$string['notorcapable'] = 'Для этого файла невозможно создать отчет об оригинальности.';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'Дата последнего просмотра студентом:';
$string['student_notread'] = 'Студент не просмотрел эту работу.';
$string['launchpeermarkreviews'] = 'Запустить обзоры Рееrmark';

// Cron.
$string['ppqueuesize'] = 'Количество событий в очереди событий модуля для проверки на плагиат';
$string['ppcronsubmissionlimitreached'] = 'Новые работы будут отправлены после окончания этого сеанса планировщика: во время одного сеанса можно обработать только {$a} работ';
$string['cronsubmittedsuccessfully'] = 'Отправка работы: {$a->title} (идентификатор TII: {$a->submissionid}) файл с выполненным заданием {$a->assignmentname} по курсу {$a->coursename} успешно отправлен в Turnitin.';
$string['pp_submission_error'] = 'Система Turnitin сообщает об ошибке при обработке работы.';
$string['turnitindeletionerror'] = 'При попытке удалить работу, отправленную в Turnitin, произошла ошибка. Локальная копия Moodle удалена, но не удалось удалить работу из системы Turnitin.';
$string['ppeventsfailedconnection'] = 'Не удается установить соединение с Turnitin. Модуль Turnitin для проверки на плагиат не будет обрабатывать события до окончания этого сеанса планировщика.';

// Error codes.
$string['tii_submission_failure'] = 'Обратитесь за дополнительной информацией к своему преподавателю или системному администратору';
$string['faultcode'] = 'Код ошибки';
$string['line'] = 'Строка';
$string['message'] = 'Сообщение';
$string['code'] = 'Код ошибки';
$string['tiisubmissionsgeterror'] = 'При попытке получить файлы с выполненным заданием из Turnitin произошла ошибка';
$string['errorcode0'] = 'Не удалось отправить файл в систему Turnitin. Обратитесь за дополнительной информацией к системному администратору';
$string['errorcode1'] = 'Не удалось отправить файл в систему Turnitin. Содержания недостаточно для формирования отчета об оригинальности.';
$string['errorcode2'] = 'Невозможно отправить файл в систему Turnitin. Максимально допустимый размер файла составляет {$a}.';
$string['errorcode3'] = 'Не удалось отправить файл в систему Turnitin, поскольку пользователь не принял условия лицензионного соглашения Turnitin с конечным пользователем.';
$string['errorcode4'] = 'Необходимо загрузить выполненное задание в файле поддерживаемого формата. Допустимы следующие форматы: .doc, .docx, .ppt, .pptx, .pps, .ppsx, .pdf, .txt, .htm, .html, .hwp, .odt, .wpd, .ps и .rtf.';
$string['errorcode5'] = 'Не удалось отправить файл в систему Turnitin. При создании модуля произошла ошибка, из-за которой невозможна отправка работ. Обратитесь за дополнительной информацией к журналам API';
$string['errorcode6'] = 'Не удалось отправить файл в систему Turnitin. При изменении настроек модуля произошла ошибка, из-за которой невозможна отправка работ. Обратитесь за дополнительной информацией к журналам API';
$string['errorcode7'] = 'Не удалось отправить файл в систему Turnitin. При создании пользователя Turnitin произошла ошибка, из-за которой невозможна отправка работ. Обратитесь за дополнительной информацией к журналам API';
$string['errorcode8'] = 'Не удалось отправить файл в систему Turnitin. При создании временного файла произошла ошибка. Вероятная причина — недопустимое имя файла. Переименуйте файл и загрузите его заново с помощью функции «Редактировать отправленную работу».';
$string['errorcode9'] = 'Невозможно отправить файл. Нет доступного для отправки содержимого в пуле файлов.';
$string['coursegeterror'] = 'Не удалось получить данные курса';
$string['configureerror'] = 'Прежде чем использовать этот модуль в рамках курса, необходимо произвести его полную настройку от имени администратора. Свяжитесь с администратором Moodle.';
$string['turnitintoolofflineerror'] = 'Возникла временная техническая проблема. Повторите попытку позже.';
$string['defaultinserterror'] = 'При попытке внести значение настройки по умолчанию в базу данных произошла ошибка';
$string['defaultupdateerror'] = 'Произошла ошибка при попытке обновить значение установки по умолчанию в базе данных';
$string['tiiassignmentgeterror'] = 'При попытке получить задание из Turnitin произошла ошибка';
$string['assigngeterror'] = 'Не удалось получить данные Turnitin';
$string['classupdateerror'] = 'Не удалось обновить данные класса Turnitin';
$string['pp_createsubmissionerror'] = 'При попытке отправить работу в систему Turnitin произошла ошибка';
$string['pp_updatesubmissionerror'] = 'Возникла ошибка при попытке вновь сделать представление в Turnitin';
$string['tiisubmissiongeterror'] = 'Возникла ошибка при попытке получить представление из Тurnitin';

// Javascript.
$string['closebutton'] = 'Закрыть';
$string['loadingdv'] = 'Средство просмотра документов загружается...';
$string['changerubricwarning'] = 'При изменении или отмене назначения рубрики будут удалены все оценки работ по этому заданию, принадлежащие этой рубрике, включая уже подписанные оценочные листы. Общие оценки проверенных работ останутся без изменений.';
$string['messageprovider:submission'] = 'Уведомления о цифровой квитанции модуля Turnitin для проверки на плагиат';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Статус Turnitin';
$string['deleted'] = 'Удалено';
$string['pending'] = 'На рассмотрении';
$string['because'] = 'Администратор удалил задание, находящееся в очереди на рассмотрение, и прервал процесс отправки в систему Turnitin.<br /><strong>Файл сохранен в Moodle. Свяжитесь с вашим преподавателем.</strong><br />Коды ошибок указаны ниже:';
$string['submitpapersto_help'] = '<strong>Не сохранять: </strong><br />Turnitin получил инструкции не хранить представленные документы ни в каких репозиториях. Мы будем выполнять только первоначальную проверку на подобие.<br /><br /><strong>Стандартное хранилище: </strong><br />Turnitin будет хранить копию представленного документа только в типовом репозитории. При выборе этого параметра Turnitin получит указания об использовании сохраненных документов для выполнения проверки их подобия с любыми документами, которые будут представлены в будущем.<br /><br /><strong>Хранилище учебного заведения (если применимо): </strong><br />При выборе этого параметра Turnitin получит инструкцию добавлять представленные документы только в закрытый репозиторий вашего учебного заведения. Проверка представленных документов на подобие будет выполняться другими преподавателями вашего учебного заведения.';
$string['errorcode12'] = 'Этот файл не был отправлен в Turnitin, поскольку он относится к заданию, в котором был удален курс. Идентификатор строки: ({$a->id}) | Идентификатор модуля курса: ({$a->cm}) | Идентификатор пользователя: ({$a->userid})';
$string['tiiaccountconfig'] = 'Настройка аккаунта Turnitin';
$string['turnitinaccountid'] = 'Идентификатор аккаунта Turnitin';
$string['turnitinsecretkey'] = 'Общий ключ Turnitin';
$string['turnitinapiurl'] = 'Turnitin API URL';
$string['tiidebugginglogs'] = 'Отладка и запись';
$string['turnitindiagnostic'] = 'Включить режим диагностики';
$string['enableperformancelogs'] = 'Разрешить протоколирование сведений о производительности сети';
$string['enableperformancelogs_desc'] = 'При включении этого параметра каждый запрос, отправленный на сервер Turnitin, будет внесен в журнал {tempdir}/plagiarism_turnitin/logs';
$string['turnitindiagnostic_desc'] = '<b>[Внимание]</b><br />Режим диагностики следует включать только при необходимости отслеживания ошибок API Turnitin.';
$string['tiiaccountsettings_desc'] = 'Убедитесь, что эти настройки совпадают с настройками вашего аккаунта Turnitin. В противном случае могут возникнуть проблемы с созданием заданий и/или отправкой работ студентами.';
$string['tiiaccountsettings'] = 'Параметры аккаунта Turnitin';
$string['turnitinusegrademark'] = 'Использовать GradeMark';
$string['turnitinusegrademark_desc'] = 'Этот параметр определяет возможность использования GradeMark для оценки отправленных заданий.<br /><i>(Этот параметр доступен только в том случае, если в аккаунте пользователя настроена функция GradeMark.)</i>';
$string['turnitinenablepeermark'] = 'Включить задания Рееrmark';
$string['turnitinenablepeermark_desc'] = 'С помощью этого параметра можно настроить возможность создавать задания Peermark<br/><i>(Эта возможность доступна только при условии, если для вашего аккаунта настроен инструмент Peermark)</i>';
$string['turnitinuseerater'] = 'Включить ETS&copy;';
$string['turnitinuseerater_desc'] = 'С помощью этого параметра можно включить инструмент проверки грамматики ETS&copy;.<br /><i>(Этот режим доступен, только если в вашем аккаунте Turnitin включен инструмент ETS&copy; e-rater.)</i>';
$string['transmatch_desc'] = 'Этот параметр определяет возможность выбора функции «Переведенные совпадения» при настройке задания.<br /><i>(Этот режим можно включить только в том случае, если в вашем аккаунте Turnitin включена функция «Переведенные совпадения».)</i>';
$string['repositoryoptions_0'] = 'Включить стандартные варианты хранилища для преподавателя';
$string['repositoryoptions_1'] = 'Включить расширенные варианты хранилища для преподавателя';
$string['repositoryoptions_2'] = 'Отправить все работы в стандартное хранилище';
$string['repositoryoptions_3'] = 'Не отправлять работы в хранилище';
$string['turnitinrepositoryoptions'] = 'Задания в архиве работ';
$string['turnitinrepositoryoptions_desc'] = 'Этот параметр позволяет выбрать хранилище для заданий Turnitin.<br /><i>(Хранилище учебного заведения доступно только для пользователей, включивших эту возможность в своем аккаунте.)</i>';
$string['tiimiscsettings'] = 'Различные настройки модуля';
$string['pp_agreement_default'] = 'Отмечая это окно, я подтверждаю, что это представление является моей собственной работой и я принимаю на себя всю тветственность за любое нарушение авторских прав, которое может появиться в результате этого представления.';
$string['pp_agreement_desc'] = '<b>[Необязательно]</b><br />Введите заявление о согласии для отправки работ.<br />(<b>Примечание:</b> если оставить поле для заявления пустым, при отправке работ студентам не потребуется подтверждать свое согласие)';
$string['pp_agreement'] = 'Заявление об авторских правах/соглашение';
$string['studentdataprivacy'] = 'Установки конфиденциальности данных студента';
$string['studentdataprivacy_desc'] = 'Следующие настройки позволяют запретить передачу персональных данных студента в систему Turnitin через данный API.';
$string['enablepseudo'] = 'Включить установку защиты персональных данных студента';
$string['enablepseudo_desc'] = 'При выборе этого параметра в вызовах Turnitin API адреса электронной почты студентов будут скрыты и заменены эквивалентными фиктивными адресами.<br /><i>(<b>Примечание:</b> если данные пользователя Moodle уже синхронизированы с Turnitin, значение этого параметра будет невозможно изменить.)</i>';
$string['pseudofirstname'] = 'Псевдоимя студента';
$string['pseudofirstname_desc'] = '<b>[Необязательно]</b><br />Имя студента, которое будет отображаться в средстве просмотра документов Turnitin';
$string['pseudolastname'] = 'Псевдофамилия студента';
$string['pseudolastname_desc'] = 'Фамилия студента для отображения в средстве просмотра документов Turnitin';
$string['pseudolastnamegen'] = 'Автоматически генерировать фамилию';
$string['pseudolastnamegen_desc'] = 'Если для этого параметра задано значение «Да» и для соответствующего поля профиля задан режим псевдофамилии, это поле будет автоматически заполнено уникальным идентификатором.';
$string['pseudoemailsalt'] = 'Псевдошифрование с помощью модификатора';
$string['pseudoemailsalt_desc'] = '<b>[Необязательно]</b><br />Дополнительное использование модификатора для усложнения сформированного псевдоадреса электронной почты студента.<br />(<b>Примечание:</b> чтобы не нарушить согласованность псевдоадресов, не следует менять данный модификатор.)';
$string['pseudoemaildomain'] = 'Псевдодомен электронной почты';
$string['pseudoemaildomain_desc'] = '<b>[Необязательно]</b><br />Возможность выбора домена для фиктивных адресов электронной почты. (Если оставить это поле пустым, по умолчанию будет использоваться домен @tiimoodle.com.)';
$string['pseudoemailaddress'] = 'Псевдоадрес электронной почты';
$string['connecttest'] = 'Проверить подключение к Turnitin';
$string['connecttestsuccess'] = 'Moodle успешно подключен к Turnitin.';
$string['diagnosticoptions_0'] = 'Выкл.';
$string['diagnosticoptions_1'] = 'Стандартн.';
$string['diagnosticoptions_2'] = 'Отладка';
$string['repositoryoptions_4'] = 'Отправить все работы в репозиторий учебного заведения';
$string['turnitinrepositoryoptions_help'] = '<strong>Включить стандартные варианты хранилища для преподавателя: </strong><br />Преподаватели могут дать Turnitin указание добавлять документ либо в типовой репозиторий, либо в репозиторий учебного заведения, либо не добавлять его ни в один из репозиториев.<br /><br /><strong>Включить расширенные варианты хранилища для преподавателя: </strong><br />Этот параметр позволит преподавателям просматривать настройки задания, чтобы позволить студентам указывать Turnitin, где сохраняются их документы. Студенты могут выбирать, сохранять ли свои документы в типовом студенческом репозитории или в закрытом репозитории.<br /><br /><strong>Отправить все работы в стандартное хранилище: </strong><br />Все документы по умолчанию будут добавлены в типовой студенческий репозиторий.<br /><br /><strong>Не отправлять работы в хранилище: </strong><br />Документы будут использованы только для выполнения первоначальной проверки и отображены, чтобы преподаватель мог поставить оценку.<br /><br /><strong>Отправить все работы в репозиторий учебного заведения: </strong><br />Turnitin получил инструкции хранить все документы в репозитории документов учебного заведения. Проверки представленных документов на подобие будут выполняться только преподавателями вашего учебного заведения.';
$string['turnitinuseanon'] = 'Использовать анонимную маркировку';
$string['turnitinuseanon_desc'] = 'Этот параметр определяет возможность анонимной проверки отправленных работ.<br /><i>(Этот параметр доступен только в том случае, если в аккаунте пользователя настроена функция анонимности.)</i>';
$string['createassignmenterror'] = 'Возникла ошибка при попытке создать задание вTurnitin';
$string['editassignmenterror'] = 'Возникла ошибка при попытке отредактировать задание вTurnitin';
$string['ppassignmentediterror'] = 'Не удалось отредактировать модуль {$a->title} (идентификатор TII: {$a->assignmentid}) в Turnitin. Дополнительную информацию см. в записях журнала API.';
$string['pp_classcreationerror'] = 'Не удалось создать класс в Turnitin. Дополнительную информацию см. в записях журнала API.';
$string['unlinkusers'] = 'Отключить пользователей';
$string['relinkusers'] = 'Вторичное подключение пользователей';
$string['unlinkrelinkusers'] = 'Отключение/Вторичное подключение пользователей';
$string['nointegration'] = 'Интеграция отсутствует';
$string['sprevious'] = 'Предыдущий';
$string['snext'] = 'Следующий';
$string['slengthmenu'] = 'Показать пункты _МЕНЮ_';
$string['ssearch'] = 'Поиск:';
$string['sprocessing'] = 'Загрузка данных из Turnitin...';
$string['szerorecords'] = 'Нет записей для отображения';
$string['sinfo'] = 'Отображены элементы с _ПЕРВОГО_ до _ПОСЛЕДНЕГО_, _ВСЕГО_ элементов';
$string['userupdateerror'] = 'Не удалось обновить данные пользователя';
$string['connecttestcommerror'] = 'Не удалось подключиться к Turnitin. Проверьте настройки URL-адреса API.';
