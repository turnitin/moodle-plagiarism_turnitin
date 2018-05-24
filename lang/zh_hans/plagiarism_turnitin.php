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
$string['pluginname'] = 'Turnitin 剽窃 Plugin';
$string['turnitintooltwo'] = 'Turnitin 工具';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Turnitin 剽窃 Plugin 任务';
$string['connecttesterror'] = '连接至 Turnitin 时出错。返回的错误消息如下：<br />';

// Assignment Settings.
$string['turnitin:enable'] = '启用 Turnitin';
$string['excludebiblio'] = '不含参考书目';
$string['excludequoted'] = '排除引用资料';
$string['excludevalue'] = '排除小型匹配结果';
$string['excludewords'] = '字';
$string['excludepercent'] = '百分比';
$string['norubric'] = '无评分表';
$string['otherrubric'] = '使用属于其他导师的评分表';
$string['attachrubric'] = '将评分表附加至此作业';
$string['launchrubricmanager'] = '启动评分表管理工具';
$string['attachrubricnote'] = '注意：学生将可以在提交前查看附加的评分表及其内容。';
$string['erater_handbook_advanced'] = '进阶';
$string['erater_handbook_highschool'] = '高中';
$string['erater_handbook_middleschool'] = '中学';
$string['erater_handbook_elementary'] = '小学';
$string['erater_handbook_learners'] = '英文学习者';
$string['erater_dictionary_enus'] = '美式英文字典';
$string['erater_dictionary_engb'] = '英式英文字典';
$string['erater_dictionary_en'] = '美式和英式英语字典';
$string['erater'] = '启用 e-rater 文法检查';
$string['erater_handbook'] = 'ETS&copy; 手册';
$string['erater_dictionary'] = 'e-rater 字典';
$string['erater_categories'] = 'e-rater 类型';
$string['erater_spelling'] = '拼字';
$string['erater_grammar'] = '文法';
$string['erater_usage'] = '用法';
$string['erater_mechanics'] = '技巧';
$string['erater_style'] = '风格';
$string['anonblindmarkingnote'] = '注意：已删除单独的 Turnitin 匿名标记设置。Turnitin 将使用 Moodle 隐蔽标记设置确定匿名标记设置。';
$string['transmatch'] = '已翻译的相符功能';
$string['genduedate'] = '在截止日期当天生成报告（学生可在截止日期前重新提交）';
$string['genimmediately1'] = '立即生成报告（学生不能重新提交）';
$string['genimmediately2'] = '立即生成报告（允许学生在截止日期前重新提交）{$a->num_resubmissions}重新提交 {$a->num_hours} 小时之后，可以产生报告。';
$string['launchquickmarkmanager'] = '启动 Quickmark 管理工具';
$string['launchpeermarkmanager'] = '启动 Peermark 管理工具';
$string['studentreports'] = '显示原创性报告给学生';
$string['studentreports_help'] = '允许您向学生用户显示 Turnitin 原创性报告。如果设置为“确定”，则 Turnitin 生成的原创性报告将可供学生查看。';
$string['submitondraft'] = '在首次上传时提交文件';
$string['submitonfinal'] = '当学生发送以供标记时提交文件';
$string['draftsubmit'] = '文件应在何时提交至 Turnitin？';
$string['allownonor'] = '允许提交任何文件类型吗？';
$string['allownonor_help'] = '此设置将允许提交任何文件类型。如果此选项设为“是”，则在可行的前提下，系统会检查提交内容的原创性，提交内容将可供下载并且 GradeMark 反馈工具将可供使用。';
$string['norepository'] = '无存储库';
$string['standardrepository'] = '标准存储库';
$string['submitpapersto'] = '存储学生论文';
$string['institutionalrepository'] = '机构存储库（适用时）';
$string['checkagainstnote'] = '注意：如果您没有为下面至少一个“做比较...”选项选择“是”，则不会生成“原创性”报告。';
$string['spapercheck'] = '与已存储的学生论文做比较';
$string['internetcheck'] = '与网络比较';
$string['journalcheck'] = '与杂志、<br />期刊与刊物比较';
$string['compareinstitution'] = '将已提交的文件与在此机构内提交的论文进行比较';
$string['reportgenspeed'] = '报告生成速度';
$string['locked_message'] = '锁定的消息';
$string['locked_message_help'] = '如果锁定了任何设置，将显示此消息说明原因。';
$string['locked_message_default'] = '此设置已锁定在站点级别';
$string['sharedrubric'] = '已共享评分表';
$string['turnitinrefreshsubmissions'] = '更新提交内容';
$string['turnitinrefreshingsubmissions'] = '更新提交内容';
$string['turnitinppulapre'] = '要向 Turnitin 提交文件，您必须首先接受我们的 EULA。选择不接受我们的 EULA 只会将您的文件提交到 Moodle。单击此处以接受。';
$string['noscriptula'] = '（由于您没有启用 javascript，因此在接受 Turnitin 用户协议后，您必须手动更新此页面才能提交）';
$string['filedoesnotexist'] = '文件已被删除';
$string['reportgenspeed_resubmission'] = '您已针对该作业提交论文，所提交论文的相似度报告已生成。如果您选择重新提交论文，那么之前提交的内容将被替换，并将生成新的报告。在 {$a->num_resubmissions} 次重新提交后，您需要在提交后等待 {$a->num_hours} 小时才能查看新报告。';

// Plugin settings.
$string['config'] = '配置';
$string['defaults'] = '默认设置';
$string['showusage'] = '显示数据转储';
$string['saveusage'] = '保存数据转储';
$string['errors'] = '错误';
$string['turnitinconfig'] = 'Turnitin 剽窃 Plugin 配置';
$string['tiiexplain'] = 'Turnitin 为商务产品。您必须付订购费才能使用此服务。有关更多信息，请访问 <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>';
$string['useturnitin'] = '启用 Turnitin';
$string['useturnitin_mod'] = '启用 Turnitin {$a}';
$string['pp_configuredesc'] = '您必须在 turnitintooltwo 单元内配置此单元。请单击<a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>此处</a>安装此 plugin';
$string['turnitindefaults'] = 'Turnitin 剽窃 Plugin 默认设置';
$string['defaultsdesc'] = '以下设置为在活动单元内启用 Turnitin 时设置的默认值';
$string['turnitinpluginsettings'] = 'Turnitin 剽窃 Plugin 设置';
$string['pperrorsdesc'] = '尝试将以下文件上传至 Turnitin 时出现问题。要重新提交，请选择您要重新提交的文件并按“重新提交”按钮。随后在下次运行 cron 时将处理这些文件。';
$string['pperrorssuccess'] = '您选择的文件已重新提交并将由 cron 进行处理。';
$string['pperrorsfail'] = '您选择的一些文件有误，无法为其创建新的 cron 事件。';
$string['resubmitselected'] = '重新提交所选文件';
$string['deleteconfirm'] = '是否确定要删除此提交内容？\n\n此操作无法撤消。';
$string['deletesubmission'] = '删除提交内容';
$string['semptytable'] = '未找到任何结果。';
$string['configupdated'] = '配置已更新';
$string['defaultupdated'] = 'Turnitin 默认值已更新';
$string['notavailableyet'] = '不可用';
$string['resubmittoturnitin'] = '重新提交至 Turnitin';
$string['resubmitting'] = '重新提交';
$string['id'] = '代码';
$string['student'] = '学生';
$string['course'] = '课程';
$string['module'] = '单元';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = '查看原创性报告';
$string['launchrubricview'] = '查看用于标记的评分表';
$string['turnitinppulapost'] = '您的文件尚未提交至 Turnitin。请单击此处接受我们的 EULA。';
$string['ppsubmissionerrorseelogs'] = '此文件尚未提交至 Turnitin，请咨询您的系统管理员';
$string['ppsubmissionerrorstudent'] = '此文件尚未提交至 Turnitin，请问您的导师登记来查询更多详情';

// Receipts.
$string['messageprovider:submission'] = 'Turnitin 剽窃 Plugin 数字回执通知';
$string['digitalreceipt'] = '数字回执';
$string['digital_receipt_subject'] = '这是您的 Turnitin 数字回执';
$string['pp_digital_receipt_message'] = '尊敬的 {$a->firstname} {$a->lastname}，<br /><br />您已于 <strong>{$a->submission_date}</strong>将文件 <strong>{$a->submission_title}</strong> 成功提交至 <strong>{$a->course_fullname}</strong> 课堂的分配 <strong>{$a->assignment_name}{$a->assignment_part}</strong>。您的提交 ID 为 <strong>{$a->submission_id}</strong>。可以通过文档查看器中的“打印/下载”按钮查看并打印您的完整数字回执。<br /><br />感谢您使用 Turnitin，<br /><br />Turnitin 团队敬上';

// Paper statuses.
$string['turnitinid'] = 'Turnitin 代码';
$string['turnitinstatus'] = 'Turnitin 状态';
$string['pending'] = '未决';
$string['similarity'] = '相似度';
$string['notorcapable'] = '无法为此文件生成原创性报告。';
$string['grademark'] = 'GradeMark';
$string['student_read'] = '学生查看论文的时间：';
$string['student_notread'] = '学生尚未查看此论文。';
$string['launchpeermarkreviews'] = '启动 Peermark 评价';

// Cron.
$string['ppqueuesize'] = '剽窃 Plugin 事件队列中的事件数';
$string['ppcronsubmissionlimitreached'] = '此 cron 执行不会向 Turnitin 发送其他任何提交，因为每次运行只会处理 {$a}';
$string['cronsubmittedsuccessfully'] = '提交：课程 {$a->coursename} 中分配 {$a->assignmentname} 的 {$a->title}（TII ID：{$a->submissionid}）已成功提交至 Turnitin。';
$string['pp_submission_error'] = 'Turnitin 为您的提交返回了一个错误：';
$string['turnitindeletionerror'] = 'Turnitin 提交内容刪除失败。计算机上的 Moodle 副本已移除，但 Turnitin 內的提交内容无法刪除。';
$string['ppeventsfailedconnection'] = 'Turnitin 剽窃 Plugin 的此 cron 执行不会处理任何事件，因为无法建立到 Turnitin 的连接。';

// Error codes.
$string['tii_submission_failure'] = '请咨询您的辅导或系统管理员以获得更多资讯';
$string['faultcode'] = '错误代号';
$string['line'] = '列';
$string['message'] = '信息';
$string['code'] = '代号';
$string['tiisubmissionsgeterror'] = '尝试从 Turnitin 中获取此作业的提交内容时出错';
$string['errorcode0'] = '此文件尚未提交至 Turnitin，请咨询您的系统管理员';
$string['errorcode1'] = '这个文件尚未发送至 Turnitin，因为它没有足够内容生成原创性报告。';
$string['errorcode2'] = '这个文件将不会被提交至 Turnitin，因为它超过允许的文件大小上限 {$a}';
$string['errorcode3'] = '这个文件尚未被提交至 Turnitin，因为用户尚未接受 Turnitin 终端用户许可证协议。';
$string['errorcode4'] = '您必须为此分配上传受支持的文件类型。接受的文件类型包括：.doc、.docx、.ppt、.pptx、.pps、.ppsx、.pdf、.txt、.htm、.html、.hwp、.odt、.wpd、.ps 和 .rtf';
$string['errorcode5'] = '这个文件尚未被提交至 Turnitin，因为在 Turnitin 中创建单元时出现问题，这将阻止提交，请查看您的 API 日志了解更多信息';
$string['errorcode6'] = '这个文件尚未被提交至 Turnitin，因为在 Turnitin 中编辑单元设置时出现问题，这将阻止提交，请查看您的 API 日志了解更多信息';
$string['errorcode7'] = '这个文件尚未被提交至 Turnitin，因为在 Turnitin 中创建用户时出现问题，这将阻止提交，请查看您的 API 日志了解更多信息';
$string['errorcode8'] = '这个文件尚未被提交至 Turnitin，因为创建临时文件时出现问题。最可能的原因是文件名无效。请重命名文件并使用“编辑提交”重新上传。';
$string['errorcode9'] = '无法提交文件，因为文件池中没有可访问的内容供提交。';
$string['coursegeterror'] = '无法获得课程数据';
$string['configureerror'] = '您必须完全以管理员身份配置此单元才能在课程内使用它。请联系您的 Moodle 管理员。';
$string['turnitintoolofflineerror'] = '我们遇到临时问题。请稍后再试。';
$string['defaultinserterror'] = '尝试将默认设置值插入数据库时出错';
$string['defaultupdateerror'] = '尝试更新数据库中的默认设置值时出错';
$string['tiiassignmentgeterror'] = '尝试从 Turnitin 中获取作业时出错';
$string['assigngeterror'] = '无法获得 turnitintooltwo 数据';
$string['classupdateerror'] = '无法更新 Turnitin 课程数据';
$string['pp_createsubmissionerror'] = '尝试在 Turnitin 中创建提交内容时出错';
$string['pp_updatesubmissionerror'] = '尝试将提交内容重新提交至 Turnitin 时出错';
$string['tiisubmissiongeterror'] = '尝试从 Turnitin 中获取提交内容时出错';

// Javascript.
$string['closebutton'] = '关闭';
$string['loadingdv'] = '正在加载 Turnitin 文档查看器...';
$string['changerubricwarning'] = '更改或分离评分表将从此作业的论文中移除所有现有的评分表分数，包括之前已标记的评分卡。之前已评分的论文的总成绩将会被保留。';
$string['messageprovider:submission'] = 'Turnitin 剽窃 Plugin 数字回执通知';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Turnitin 状态';
$string['deleted'] = '已删除';
$string['pending'] = '未决';
$string['because'] = '这是因为，管理员从处理队列中删除了待处理的作业并中止向 Turnitin 提交内容。<br /><strong>相应文件仍存在于 Moodle 中，请联系您的导师。</strong><br />请看下面的错误代码：';
$string['submitpapersto_help'] = '<strong>无存储库: </strong><br />Turnitin 被设定为不将上传文件储存至任何知识库。文件仅用于初始查重。<br /><br /><strong>标准存储库: </strong><br />Turnitin 将只在标准知识库中储存上传文件的副本。选择此选项，Turnitin 对日后上传文件的查重工作将只使用已储存文件。<br /><br /><strong>机构存储库（适用时）: </strong><br />选择此选项，将 Turnitin 设定为只添加文件至您机构的私有知识库。上传文件的查重工作将只由您机构的其他教员完成。';
$string['errorcode12'] = '该文件未能上传至 Turnitin，因其所在任务课程已删除。行 ID: ({$a->id}) | 课程模块 ID: ({$a->cm}) | 用户 ID: ({$a->userid})';
