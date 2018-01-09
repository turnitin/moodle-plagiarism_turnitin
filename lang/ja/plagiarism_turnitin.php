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
$string['pluginname'] = 'Turnitin Plagiarismプラグイン';
$string['turnitintooltwo'] = 'Turnitinツール';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Turnitin Plagiarismプラグインのタスク';
$string['connecttesterror'] = 'Turnitinへの接続中にエラーが発生しました。エラーメッセージは以下の通りです。<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'Turnitinを有効にする';
$string['excludebiblio'] = '参考文献の除外';
$string['excludequoted'] = '引用文献を除外';
$string['excludevalue'] = '小さな一致を除外する';
$string['excludewords'] = '語';
$string['excludepercent'] = 'パーセント';
$string['norubric'] = '採点なし';
$string['otherrubric'] = '他の講師に属するルーブリックを使用する';
$string['attachrubric'] = '採点をこの課題に添付する';
$string['launchrubricmanager'] = '採点マネジャーを起動する';
$string['attachrubricnote'] = '注意：学生は提出する前に、添付された採点およびその内容を閲覧できます。';
$string['erater_handbook_advanced'] = '上級';
$string['erater_handbook_highschool'] = '高校';
$string['erater_handbook_middleschool'] = '中学校';
$string['erater_handbook_elementary'] = '小学校';
$string['erater_handbook_learners'] = '英語学習者';
$string['erater_dictionary_enus'] = 'アメリカ英語辞書';
$string['erater_dictionary_engb'] = 'イギリス英語辞書';
$string['erater_dictionary_en'] = 'アメリカ／イギリス英語辞書';
$string['erater'] = 'e-rater文法チェックを有効にする';
$string['erater_handbook'] = 'ETS&copy; ハンドブック';
$string['erater_dictionary'] = 'e-rater 辞書';
$string['erater_categories'] = 'e-rater カテゴリー';
$string['erater_spelling'] = 'スペリング';
$string['erater_grammar'] = '文法';
$string['erater_usage'] = '使用法';
$string['erater_mechanics'] = 'メカニズム';
$string['erater_style'] = 'スタイル';
$string['anonblindmarkingnote'] = '注意：Turnitinの個別の匿名マーキングの設定が削除されました。Turnitinでは、Moodleのブラインドマーキングの設定を使って、匿名マーキングの設定を決定します。';
$string['transmatch'] = '翻訳一致機能';
$string['genduedate'] = '提出期限日にレポートを作成する（提出期限日までの再提出可）';
$string['genimmediately1'] = '即座にレポートを作成する（再提出不可）';
$string['genimmediately2'] = '即座にレポートを作成する（再提出は提出期限日まで可能）{$a->num_resubmissions} つの再提出後、レポートは {$a->num_hours} 時間後に作成されます';
$string['launchquickmarkmanager'] = 'Quickmarkマネジャーを起動する';
$string['launchpeermarkmanager'] = 'Peermarkマネジャーを起動する';
$string['studentreports'] = 'オリジナリティ レポートを学生に表示する';
$string['studentreports_help'] = 'Turnitinオリジナリティーレポートを受講生に表示することを許可する。はい、に設定すると、Turnitinにより作成されたオリジナリティーレポートは受講生により閲覧することができます。';
$string['submitondraft'] = '最初にアップロードされた際にファイルを提出する';
$string['submitonfinal'] = '受講生がマーキングに送信した際にファイルを提出する';
$string['draftsubmit'] = 'いつTurnitinにファイルを提出しますか？';
$string['allownonor'] = 'すべてのファイルタイプの提出物の提出を許可しますか？';
$string['allownonor_help'] = 'この設定では、すべてのファイルタイプで提出が可能になります。この設定を［はい］に設定すると、提出物のオリジナリティがチェックされ、ダウンロードが可能になり、また、GradeMarkフィードバックツールも利用できるようになります。';
$string['norepository'] = 'リポジトリなし';
$string['standardrepository'] = '標準リポジトリ';
$string['submitpapersto'] = '学生レポートを保存';
$string['institutionalrepository'] = '所属機関リポジトリ（適用する場合）';
$string['submitpapersto_help'] = 'インストラクタはこの設定を使って、Turnitinの学生のレポートリポジトリに提出されたレポートを保存するかどうかを決めます。レポートをリポジトリに保存すると、課題に提出されたレポートを現在のクラス、または過去に提出された提出物に照らし合わせてチェックできるメリットがあります。［リポジトリなし］を設定すると、Turnitinの学生のレポートリポジトリにレポートは保存されません。';
$string['checkagainstnote'] = '注意：［～に対してチェックする］オプションのいずれにも［はい］を選択しなかった場合は、オリジナリティ レポートが生成されません。';
$string['spapercheck'] = '保存されている学生のレポートと比較する';
$string['internetcheck'] = 'インターネットでチェックする';
$string['journalcheck'] = 'ジャーナル、定期刊行物、<br />出版物をチェックする';
$string['compareinstitution'] = '提出されたファイルを教育機関内のレポートと比較する';
$string['reportgenspeed'] = '作成速度を報告する';
$string['locked_message'] = 'ロックのメッセージ';
$string['locked_message_help'] = 'ロックされている設定がある場合は、このメッセージでその理由を説明します。';
$string['locked_message_default'] = 'この設定はサイトレベルでロックされています';
$string['sharedrubric'] = '共有された採点';
$string['turnitinrefreshsubmissions'] = '提出物を更新';
$string['turnitinrefreshingsubmissions'] = '提出物を更新中';
$string['turnitinppulapre'] = 'Turnitinにファイルを提出するには、EULAへの同意が必要です。EULAに同意しないことを選択すると、ファイルはMoodleだけに提出されます。同意するには、こちらをクリックしてください。';
$string['noscriptula'] = '（ジャバスクリプトが作動されていないため、Turnitinユーザー使用規約に承諾した後、提出する前にこのページをマニュアル操作で更新する必要があります。）';
$string['filedoesnotexist'] = 'ファイルは削除されました';
$string['reportgenspeed_resubmission'] = 'この課題に対するレポートはすでに提出されており、その提出への類似性レポートが作成されました。レポートを再提出すると選択した場合、最初の提出と置き換えられ、新たなレポートが作成されます。{$a->num_resubmissions}再提出後は、新しい類似性レポートを見るのに再提出から{$a->num_hours}時間待つ必要があります。';

// Plugin settings.
$string['config'] = '設定';
$string['defaults'] = 'デフォルト設定';
$string['showusage'] = 'ダンプされたデータを表示';
$string['saveusage'] = 'ダンプされたデータを保存';
$string['errors'] = 'エラー';
$string['turnitinconfig'] = 'Turnitin Plagiarismプラグインの構成';
$string['tiiexplain'] = 'Turnitinは商用製品であり、このサービスを利用するにはサービス料のお支払いが必要です。詳しくは、<a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>をご覧ください。';
$string['useturnitin'] = 'Turnitinを有効にする';
$string['useturnitin_mod'] = 'Turnitinを有効にする： {$a}';
$string['pp_configuredesc'] = 'このモジュールはturnitintooltwoモジュール内で設定する必要があります。このプラグインを設定するには<a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>こちら</a>をクリックしてください。';
$string['turnitindefaults'] = 'Turnitin Plagiarismプラグインデフォルト設定';
$string['defaultsdesc'] = '次の設定はアクティビティーモジュール内のTurnitinを有効にする際のデフォルト設定です';
$string['turnitinpluginsettings'] = 'Turnitin Plagiarismプラグイン設定';
$string['pperrorsdesc'] = '以下のファイルをTurnitinにアップロード中に問題が発生しました。ファイルを再提出するには、再提出するファイルを選択し、［再提出］ボタンをクリックしてください。これらは、次回Cronを実行したときに処理されます。';
$string['pperrorssuccess'] = '選択したファイルが再提出され、Cronで処理されます。';
$string['pperrorsfail'] = '選択したファイルのいくつかで問題が発生しました。これらには新しいCronイベントを生成できませんでした。';
$string['resubmitselected'] = '選択したファイルを再提出する';
$string['deleteconfirm'] = 'この提出物を削除しますか？\n\n　いったん削除すると、元に戻すことはできません。';
$string['deletesubmission'] = '提出物を削除';
$string['semptytable'] = '検索結果がありません。';
$string['configupdated'] = '設定が更新されました';
$string['defaultupdated'] = 'Turnitin デフォルトが更新されました';
$string['notavailableyet'] = '利用できません';
$string['resubmittoturnitin'] = 'Turnitinに再提出する';
$string['resubmitting'] = '再提出中';
$string['id'] = 'ID';
$string['student'] = '学生';
$string['course'] = 'コース';
$string['module'] = 'モジュール';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'オリジナリティ レポートを閲覧';
$string['launchrubricview'] = 'マーキングに使用された採点を閲覧する';
$string['turnitinppulapost'] = 'あなたのファイルはTurnitinに提出されませんでした。こちらをクリックして、EULAに同意してください。';
$string['ppsubmissionerrorseelogs'] = 'このファイルはTurnitinに提出されていません。詳しくは、システム管理者にお問い合わせください。';
$string['ppsubmissionerrorstudent'] = 'このファイルはTurnitinに提出されていません。更なる詳細に関しては、チューターまでご相談ください。';

// Receipts.
$string['messageprovider:submission'] = 'Turnitin Plagiarismプラグインのデジタル受領書に関する通知';
$string['digitalreceipt'] = 'デジタル受領書';
$string['digital_receipt_subject'] = 'これはあなたのTurnitinのデジタル受領書です';
$string['pp_digital_receipt_message'] = '{$a->lastname} {$a->firstname}様、<br /><br />あなたは<strong>{$a->submission_date}</strong>に、<strong>{$a->course_fullname}</strong>クラスの課題<strong>{$a->assignment_name}{$a->assignment_part}</strong>にファイル<strong>{$a->submission_title}</strong>を提出しました。提出IDは<strong>{$a->submission_id}</strong>です。デジタル受領書はすべて、文書閲覧内にある印刷やダウンロードボタンを使って閲覧および印刷することができます。<br /><br />Turnitinをご利用いただき、ありがとうございます。<br /><br />Turnitinチーム一同';

// Paper statuses.
$string['turnitinid'] = 'Turnitin ID';
$string['turnitinstatus'] = 'Turnitinのステータス';
$string['pending'] = '保留中';
$string['similarity'] = '類似性';
$string['notorcapable'] = 'このファイルに対してオリジナリティ レポートを作成することができません。';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'レポートの閲覧日：';
$string['student_notread'] = '受講生はこのレポートをまだ閲覧していません。';
$string['launchpeermarkreviews'] = 'Peermarkレビューを起動する';

// Cron.
$string['ppqueuesize'] = 'Plagiarismプラグインのイベントキューにあるイベント数';
$string['ppcronsubmissionlimitreached'] = 'Cronは一度に{$a}件までの提出物しか処理しないので、これ以上Turnitinに提出物を送れません。';
$string['cronsubmittedsuccessfully'] = '{$a->coursename}コースの課題{$a->assignmentname}に対して、提出物：{$a->title}（TII ID：{$a->submissionid}）が正しくTurnitinに送信されました。';
$string['pp_submission_error'] = 'Turnitinから次の提出物についてエラーが返されました。';
$string['turnitindeletionerror'] = 'Turnitinの提出物削除に失敗しました。ローカル Moodle コピーは削除されましたが、Turnitin内の提出物を削除することはできませんでした。';
$string['ppeventsfailedconnection'] = 'Turnitinに接続していないので、Turnitin PlagiarismプラグインはCronでイベントを処理できません。';

// Error codes.
$string['tii_submission_failure'] = '詳しくは、チューターかシステム管理者にお問い合わせください。';
$string['faultcode'] = 'フォルトコード';
$string['line'] = 'ライン';
$string['message'] = 'メッセージ';
$string['code'] = 'コード';
$string['tiisubmissionsgeterror'] = '提出物をTurnitinからこの課題へ入手する際にエラーが発生しました';
$string['errorcode0'] = 'このファイルはTurnitinに提出されていません。詳しくは、システム管理者にお問い合わせください。';
$string['errorcode1'] = 'このファイルはオリジナリティ レポートを作成するコンテンツが不足しているので、Turnitinに送信されていません。';
$string['errorcode2'] = 'このファイルは許容されるサイズの上限{$a}を超えているため、Turnitinに提出できません。';
$string['errorcode3'] = 'このファイルはユーザーが、Turnitinのユーザーライセンス契約に同意していないため、Turnitinへ提出することができません';
$string['errorcode4'] = 'この課題に対応しているファイルの種類でアップロードする必要があります。アップロード可能なファイルは、.doc、.docx、.ppt、.pptx、.pps、.ppsx、.pdf、.txt、.htm、.html、.hwp、.odt、.wpd、.ps、.rtfです。';
$string['errorcode5'] = 'Turnitin内でのモジュールの作成に問題があるため、このファイルはTurnitinに提出されていません。詳しくは、APIログを参照してください。';
$string['errorcode6'] = 'Turnitin内でのモジュール設定の編集に問題があるため、このファイルはTurnitinに提出されていません。詳しくは、APIログを参照してください。';
$string['errorcode7'] = 'Turnitin内でのユーザーの作成に問題があるため、このファイルはTurnitinに提出されていません。詳しくは、APIログを参照してください。';
$string['errorcode8'] = '一時ファイルの作成に問題があるため、このファイルはTurnitinに提出されていません。ファイル名が無効である可能性があります。［提出物の編集］を使ってファイルの名前を変更してからもう一度アップロードしてください。';
$string['errorcode9'] = 'このファイルは、ファイルプールにアクセス可能なコンテンツがないため、送信できません。';
$string['coursegeterror'] = 'コースデータを取得できませんでした';
$string['configureerror'] = 'このコースの使用を開始する前に、管理者がこのモジュールを設定する必要があります。Moodle管理者までお問い合わせください。';
$string['turnitintoolofflineerror'] = '現在一時的な問題が発生しています。後ほど再度試みてください。';
$string['defaultinserterror'] = 'データベースのデフォルト設定値を挿入中にエラーが発生しました';
$string['defaultupdateerror'] = 'データベースのデフォルト設定値を更新中にエラーが発生しました';
$string['tiiassignmentgeterror'] = 'Turnitinから課題を入手する際にエラーが発生しました';
$string['assigngeterror'] = 'turnitintooltwoデータを取得できませんでした';
$string['classupdateerror'] = 'Turnitinクラスのデータを更新できませんでした';
$string['pp_createsubmissionerror'] = 'Turnitinで提出物を作成する際にエラーが発生しました';
$string['pp_updatesubmissionerror'] = '提出物をTurnitinへ再提出する際にエラーが発生しました';
$string['tiisubmissiongeterror'] = '提出物をTurnitinから入手する際にエラーが発生しました';

// Javascript.
$string['closebutton'] = '閉じる';
$string['loadingdv'] = 'Turnitin文書閲覧を読み込み中...';
$string['changerubricwarning'] = '採点を変更したり解除したりすると、スコアカードを含めてこの課題のレポートに既に存在する採点がすべて削除されます。以前に採点されたレポートの全体評価は残ります。';
$string['messageprovider:submission'] = 'Turnitin Plagiarismプラグインのデジタル受領書に関する通知';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Turnitinのステータス';
$string['deleted'] = '削除されました';
$string['pending'] = '保留中';
$string['because'] = 'これは、管理者が保留中の課題をプロセスキューから削除し、Turnitinへの提出を中止したためです。<br /><strong>ファイルはMoodleに残ります。インストラクタにお問い合わせください。</strong><br />エラーコードは次の通りです。';
