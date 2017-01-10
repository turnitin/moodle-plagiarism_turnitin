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
$string['pluginname'] = 'المكون الإضافي الخاص بسرقة محتوى Turnitin';
$string['turnitintooltwo'] = 'أداة Turnitin';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'وظيفة المكون الإضافي الخاص بسرقة محتوى Turnitin';
$string['connecttesterror'] = 'حدث خطأ أثناء الاتصال بـ Turnitin وهذه رسالة الخطأ:<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'تمكين Turnitin';
$string['excludebiblio'] = 'استثناء المراجع';
$string['excludequoted'] = 'استثناء نصوص الاقتباسات';
$string['excludevalue'] = 'استبعاد التطابقات الصغيرة';
$string['excludewords'] = 'كلمات';
$string['excludepercent'] = 'النسبة المئوية';
$string['norubric'] = 'لا توجد معايير قياسية';
$string['otherrubric'] = 'إستخدام المعيار القياسي العائد لمدربين أخرين';
$string['attachrubric'] = 'إلحاق باب أجوبة قياسية لهذه المهمة';
$string['launchrubricmanager'] = 'أطلق معالج الأجوبة القياسية';
$string['attachrubricnote'] = 'ملاحظة: سيكون بمقدور الطلاب مشاهدة المعايير القياسية الملحقة و محتوياتها قبل القيام بالتسليم.';
$string['erater_handbook_advanced'] = 'متقدم';
$string['erater_handbook_highschool'] = 'المدرسة الاعداية';
$string['erater_handbook_middleschool'] = 'المدرسة المتوسطة';
$string['erater_handbook_elementary'] = 'المدرسة الابتدائية';
$string['erater_handbook_learners'] = 'المتعلمين الانكليز';
$string['erater_dictionary_enus'] = 'القواميس الانكليزية الامريكية';
$string['erater_dictionary_engb'] = 'القواميس الانكليزية البريطانية';
$string['erater_dictionary_en'] = 'كلا من القواميس الانكليزية الامريكية والبريطانية';
$string['erater'] = 'تفعيل الفحص النحوي e-rater';
$string['erater_handbook'] = 'ETS&copy; Handbook';
$string['erater_dictionary'] = 'قاموس e-rater';
$string['erater_categories'] = 'فئات e-rater';
$string['erater_spelling'] = 'الاملاء';
$string['erater_grammar'] = 'القواعد';
$string['erater_usage'] = 'الاستخدام';
$string['erater_mechanics'] = 'الميكانيك';
$string['erater_style'] = 'الأسلوب';
$string['anonblindmarkingnote'] = 'ملحوظة: تم إزالة الإعداد المنفصل لوضع العلامات دون إظهار الأسماء بـ Turnitin. ستستخدم Turnitin إعداد وضع العلامات دون معرفة أسماء الطلاب التابع لـ Moodle لتحديد إعداد وضع العلامات دون إظهار الأسماء.';
$string['transmatch'] = 'تطابق الترجمة';
$string['genduedate'] = 'قم بإنشاء التقارير في موعد الاستحقاق (يسمح بإعادة الإرسال حتى موعد الإستحقاق)';
$string['genimmediately1'] = 'قم بتوليد التقارير فورا ( لا يسمح بإعادة التقديم)';
$string['genimmediately2'] = 'قم بتوليد التقارير فورا ( يسمح بإعادة التقديم حتى تاريخ الاستحقاق)';
$string['launchquickmarkmanager'] = 'إطلاق معالج Quickmark';
$string['launchpeermarkmanager'] = 'أطلق معالج Peermark';
$string['studentreports'] = 'عرض تقارير الاصالة للطلبة';
$string['studentreports_help'] = 'يسمح لك بعرض تقارير الاصالة للطلبة المستخدين. اذا ما تم تحديد نعم سيكون تقرير الاصالة متوفر للطلبة للعرض';
$string['submitondraft'] = 'أرسل الملف عند تحميل أول ملف.';
$string['submitonfinal'] = 'ارسل الملف عندما يقوم الطالب للتقييم';
$string['draftsubmit'] = 'متى يجب إرسال الملف إلى Turnitin؟';
$string['allownonor'] = 'السماح إرسال أي نوع من الملفات؟';
$string['allownonor_help'] = 'هذا الوضع يسمح بإرسال أي نوع من الملفات. مع تعيين هذا الخيار إلى &#34;نعم&#34;، سيتم فحص أصالة الإرسالات حيثما كان ذلك ممكنًا، وسوف تكون متاحة للتنزيل وستتوفر أدوات GradeMark للملاحظات حيثما كان ذلك ممكنًا.';
$string['norepository'] = 'لا توجد مستودعات';
$string['standardrepository'] = 'المستودع القياسي';
$string['submitpapersto'] = 'حفظ مستندات الطلبة';
$string['institutionalrepository'] = 'المستودعات المؤسسية (عند الاقتضاء)';
$string['submitpapersto_help'] = 'سيوفر هذا الإعداد للمدرسين القدرة على اختيار ما اذا كانت المستندات ستحفظ في مستودع مستندات الطلبة. تكمن الفائدة من ارسال المستندات إلى مستودع مستندات الطلبة في التحقق من مستندات الطلاب التي تم إرسالها مقابل إرسالات &#39;الطلاب الآخرين في صفوفك الدراسية الحالية أو السابقة. إذا اخترت &#34;لا مستودعات&#34; فلن يتم تخزين المستندات في مخزن مستندات الطلاب.&#39;';
$string['checkagainstnote'] = 'ملحوظة: إذا لم تحدد "نعم" لخيار "التحقق مقابل..." واحد على الأقل الموجود أدناه، فلن يتم إنشاء تقرير الأصالة.';
$string['spapercheck'] = 'قحص مقابل مستندات الطلبة المخزونة';
$string['internetcheck'] = 'فحص مقابل الإنترنت';
$string['journalcheck'] = 'فحص مقابل المجلات <br />والدوريات والمنشورات';
$string['compareinstitution'] = 'مقارنة الملفات المسلمة مع المستندات المسلمة داخل هذه المؤسسة';
$string['reportgenspeed'] = 'تقرير سرعة التكوين';
$string['genspeednote'] = 'ملحوظة: يستغرق إنشاء تقرير الأصالة لعمليات إعادة إرسال أربعة وعشرين ساعة.';
$string['locked_message'] = 'رسالة الإعدادات المغلقة';
$string['locked_message_help'] = 'إذا كانت أي من الإعدادات مغلقة، تظهر هذه الرسالة للتعريف بالسبب.';
$string['locked_message_default'] = 'تم إغلاق هذا الإعداد على مستوى الموقع';
$string['sharedrubric'] = 'معايير قياسية مشتركة';
$string['turnitinrefreshsubmissions'] = 'تحديث الإرسالات';
$string['turnitinrefreshingsubmissions'] = 'جاري تحديث الإرسالات';
$string['turnitinppulapre'] = 'لإرسال ملف إلى Turnitin، عليك أولاً القبول باتفاقية ترخيص المستخدم النهائي، وباختيارك عدم القبول باتفاقية ترخيص المستخدم النهائي الخاصة بنا سنقوم بإرسال ملفك إلى Moodle فقط. انقر هنا للقبول.';
$string['noscriptula'] = '"(لأن Javascript غير مفعل لديك سيتوجب عليك تحديث هذه الصفحة يدويًا قبل أن تتمكن من الإرسال و بعد الموافقة على اتفاقية المستخدم لـ Turnitin)"';
$string['filedoesnotexist'] = 'تم حذف الملف';

// Plugin settings.
$string['config'] = 'التكوين';
$string['defaults'] = 'الإعدادات الافتراضية';
$string['showusage'] = 'إظهار النسخة الاحتياطية للبيانات';
$string['saveusage'] = 'حفظ النسخة الاحتياطية للبيانات';
$string['errors'] = 'أخطاء';
$string['turnitinconfig'] = 'تكوين المكون الإضافي الخاص بسرقة محتوى Turnitin';
$string['tiiexplain'] = 'Turnitin هو منتج تجاري، ويجب أن يكون لديك اشتراك مدفوع لاستخدام هذه الخدمة، لمزيد من المعلومات راجع <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>';
$string['useturnitin'] = 'تمكين Turnitin';
$string['useturnitin_mod'] = 'تمكين Turnitin لـ {$a}';
$string['pp_configuredesc'] = 'يجب عليك تكوين هذه الوحدة داخل وحدة turnitintooltwo . يرجى النقر <a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>هنا </a>لتكوين المكون الإضافي هذا';
$string['turnitindefaults'] = 'الاعدادات الافتراضية لمكون الانتحال في Turnitin';
$string['defaultsdesc'] = 'الاعدادات التالية هي الاعدادات الإفتراضية التي تم تحديدها حين تم تفعيل Turnitinضمن نموذج فعالية';
$string['turnitinpluginsettings'] = 'الاعدادات لمكون الانتحال في Turnitin';
$string['pperrorsdesc'] = 'ظهرت مشكلة ما أثناء المحاولة رفع الملفات أدناه إلى Turnitin. لإعادة الإرسال، قم بتحديد الملفات التي ترغب في إعادة إرسالها واضغط على زر إعادة إرسال. سيتم معالجة هذه الملفات عند تشغيل كرون (cron) المرة القادمة.';
$string['pperrorssuccess'] = 'تم إعادة إرسال الملفات التي قمت بتحديدها وسيتم معالجتها من خلال كرون (cron).';
$string['pperrorsfail'] = 'ظهرت مشكلة ببعض الملفات التي قمت بتحديدها، ولم يمكن إنشاء حدث cron جديد.';
$string['resubmitselected'] = 'إعادة إرسال ملفات محددة';
$string['deleteconfirm'] = 'هل أنت متأكد من حذف هذا الإرسال؟\n\nلا يمكن الرجوع عن هذا الإجراء.';
$string['deletesubmission'] = 'حذف الإرسال';
$string['semptytable'] = 'لا توجد نتائج.';
$string['configupdated'] = 'تم تحديث التكوين';
$string['defaultupdated'] = 'تم تحديث الخيارات الافتراضية لـ Turnitin';
$string['notavailableyet'] = 'غير متوفر';
$string['resubmittoturnitin'] = 'إعادة الإرسال إلى Turnitin ';
$string['resubmitting'] = 'جاري إعادة الإرسال';
$string['id'] = 'معرف';
$string['student'] = 'طالب';
$string['course'] = 'الدرس';
$string['module'] = 'نموذج';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'عرض تقرير الاصالة';
$string['launchrubricview'] = 'مشاهدة المعيار القياسي المستخدم لتحديد العلامات';
$string['turnitinppulapost'] = 'لم يتم إرسال ملفك إلى Turnitin. الرجاء النقر هنا لقبول اتفاقية ترخيص المستخدم النهائي.';
$string['ppsubmissionerrorseelogs'] = 'لم يتم إرسال هذا الملف إلى Turnitin، يُرجى استشارة مسؤول المدرسة لديك.';
$string['ppsubmissionerrorstudent'] = 'لم يُرسل هذا الملف إلى Turnitin، يُرجى استشارة المدرس للحصول على مزيد من التفاصيل.';

// Receipts.
$string['messageprovider:submission'] = 'إشعارات الإيصال الرقمي للمكون الإضافي الخاص بسرقة محتوى Turnitin';
$string['digitalreceipt'] = 'إيصال رقمي';
$string['digital_receipt_subject'] = 'هذا هو الإيصال الرقمي الخاص بك';
$string['pp_digital_receipt_message'] = 'عزيزي {$a->firstname} {$a->lastname}،<br /><br />لقد أرسلت بنجاح ملف <strong>{$a->submission_title}</strong> إلى مهمة <strong>{$a->assignment_name}{$a->assignment_part}</strong> في الفصل الدراسي <strong>{$a->course_fullname}</strong> في <strong>{$a->submission_date}</strong>. معرف الإرسال هو <strong>{$a->submission_id}</strong>. يمكن رؤية الإيصال الرقمي الكامل الخاص بك وطباعته من زر الطباعة/التنزيل في عارض الوثائق.<br /><br />شكرًا لاستخدامك Turnitin،<br /><br />فريق Turnitin.';

// Paper statuses.
$string['turnitinid'] = 'معرف Turnitin ';
$string['turnitinstatus'] = 'حالة Turnitin ';
$string['pending'] = 'قيد الانتظار';
$string['similarity'] = 'التشابه';
$string['notorcapable'] = 'لا يمكن إنشاء تقرير أصالة لهذا الملف.';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'قام الطالب بعرض هذا المستند في:';
$string['student_notread'] = 'لم يقم الطالب بعرض هذا المستند.';
$string['launchpeermarkreviews'] = 'ابدأ تشغيل مراجعات Peermark';

// Cron.
$string['ppqueuesize'] = 'عدد الأحداث في قائمة أحداث المكون الإضافي الخاص بسرقة المحتوى';
$string['ppcronsubmissionlimitreached'] = 'لن يتم إرسال إرسالات إضافية إلى Turnitin من خلال تنفيذ هذا الكرون، حيث يتم معالجة {$a} فقط لكل تشغيل.';
$string['cronsubmittedsuccessfully'] = 'إرسال: تم إرسال{$a->title} (معرف TII: {$a->submissionid}) للمهمة {$a->assignmentname} على الدرس {$a->coursename} بنجاح إلى Turnitin.';
$string['pp_submission_error'] = 'أعادت Turnitin خطأ يتعلق بإرسالك:';
$string['turnitindeletionerror'] = 'فشل في حذف ارسال Turnitin. تك حذف نسخة Moodle المحلية لكن الإرسال إلى Turnitin لم يمكن حذفه.';
$string['ppeventsfailedconnection'] = 'لن يتم معالجة أي أحداث بواسطة المكون الإضافي الخاص بسرقة محتويات Turnitin من خلال تنفيذ هذا الكرون، حيث يتعذر إنشاء اتصال بـ Turnitin.';

// Error codes.
$string['tii_submission_failure'] = 'يرجى التشاور مع معلمك أو مسؤول النظام للحصول على مزيد من التفاصيل.';
$string['faultcode'] = 'رمز الخطأ';
$string['line'] = 'خط';
$string['message'] = 'رسالة';
$string['code'] = 'رمز';
$string['tiisubmissionsgeterror'] = 'حدث خطأ عند محاولة الحصول على الإرسالات لهذه المهمة من Turnitin';
$string['errorcode0'] = 'لم يتم إرسال هذا الملف إلى Turnitin، يُرجى استشارة مسؤول المدرسة لديك.';
$string['errorcode1'] = 'لم يتم إرسال هذا الملف إلى Turnitin، حيث أنه لا يوجد به محتوى كاف لإنتاج تقرير الأصالة.';
$string['errorcode2'] = 'لن يتم إرسال هذا الملف إلى Turnitin، حيث أنه يتجاوز الحد الأقصى لحجم {$a} المسموح به.';
$string['errorcode3'] = 'لم يُرسل هذا الملف إلى Turnitin لأن المستخدم لم يوافق على اتفاقية ترخيص المستخدم النهائي.';
$string['errorcode4'] = 'يجب عليك رفع ملفًا مدعومًا لهذه المهمة. أنواع الملفات المقبولة: doc وdocx وppt وpptx وpps وppsx وpdf وtxt وhtm وhtml وhwp وodt وwpd وps وrtf';
$string['errorcode5'] = 'لم يتم إرسال هذا الملف إلى Turnitin لوجود مشكلة في إنشاء الوحدة النمطية في Turnitin التي تمنع الإرسالات، الرجاء الرجوع إلى سجلات API للحصول على مزيد من المعلومات.';
$string['errorcode6'] = 'لم يتم إرسال هذا الملف إلى Turnitin لوجود مشكلة في تحرير إعدادات الوحدة النمطية في Turnitin التي تمنع الإرسالات، الرجاء الرجوع إلى سجلات API للحصول على مزيد من المعلومات.';
$string['errorcode7'] = 'لم يتم إرسال هذا الملف إلى Turnitin لوجود مشكلة في إنشاء المستخدم في Turnitin التي تمنع الإرسالات، الرجاء الرجوع إلى سجلات API للحصول على مزيد من المعلومات.';
$string['errorcode8'] = 'لم يتم إرسال هذا الملف إلى Turnitin لوجود مشكلة في إنشاء ملف temp. السبب الأكثر احتمالاً هو أن اسم الملف غير صالح. الرجاء إعادة تسمية الملف إعادة رفعه باستخدام تحرير الإرسال.';
$string['errorcode9'] = 'يتعذر إرسال الملف بسبب عدم وجود محتوى قابل للوصول في مجموعة الملفات ليتم إرسالها.';
$string['coursegeterror'] = 'لا يمكن الحصول على بيانات الدورة';
$string['configureerror'] = 'يجب تكوين هذه الوحدة بالكامل كمسؤول قبل استخدامها في الدورة، يرجى الاتصال بمسؤول Moodle.';
$string['turnitintoolofflineerror'] = 'نحن نواجه مشكلة في الوقت الحالي. يرجى المحاولة مجددًا بعد قليل.';
$string['defaultinserterror'] = 'حدث خطأ عند محاولة إدخال قيمة إعداد إفتراضي لقاعدة البيانات';
$string['defaultupdateerror'] = 'حدث خطأ عند محاولة تحديث قيمة إعداد إفتراضي في قاعدة البيانات';
$string['tiiassignmentgeterror'] = 'حدث خطأ عند محاولة الحصول على مهمة من Turnitin';
$string['assigngeterror'] = 'لا يمكن الحصول على بيانات أدوات turnitintooltwo';
$string['classupdateerror'] = 'لا يمكن تحديث بيانات درس Turnitin';
$string['pp_createsubmissionerror'] = 'حدث خطأ عند محاولة إنشاء التسليم في Turnitin';
$string['pp_updatesubmissionerror'] = 'حدث خطأ عند محاولة إعادة تسليم ما قمت بتسليمه الى Turnitin';
$string['tiisubmissiongeterror'] = 'حدث خطأ عند محاولة خلق المهمة في Turnitin';

// Javascript.
$string['closebutton'] = 'إغلاق';
$string['loadingdv'] = 'جاري تحميل عارض وثائق Turnitin ...';
$string['changerubricwarning'] = 'سيؤدي تغيير أو إزالة أي معيار قياسي إلى إزالة جميع الدرجات القياسية الحالية من المستندات في هذه المهمة، التي تشتمل على بطاقات الدرجات التي تم تعليمها مسبقًا. ستظل الدرجات الإجمالية للمستندات المعلمة مسبقًا كما هي.';
$string['messageprovider:submission'] = 'إشعارات الإيصال الرقمي للمكون الإضافي الخاص بسرقة محتوى Turnitin ';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'حالة Turnitin';
$string['deleted'] = 'تم حذف';
$string['pending'] = 'قيد الانتظار';
$string['because'] = 'هذا بسبب قيام أحد المسؤولين بحذف مهمة معلقة من قائمة المعالجة وإيقاف الإرسال إلى Turnitin. <br /><strong>ما زال الملف موجودًا في Moodle، الرجاء الاتصال بمعلمك.</strong><br />الرجاء الاطلاع أدناه على أي رموز خطأ:';
