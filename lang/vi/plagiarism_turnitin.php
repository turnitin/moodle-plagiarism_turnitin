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
$string['pluginname'] = 'Phần bổ trợ chống đạo văn của Turnitin';
$string['turnitintooltwo'] = 'Công cụ Turnitin';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Tác vụ của Phần bổ trợ Chống đạo văn của Turnitin';
$string['connecttesterror'] = 'Xảy ra lỗi khi kết nối với Turnitin, thông báo lỗi như sau:<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'Cho phép Turnitin';
$string['excludebiblio'] = 'Loại trừ Mục lục tham khảo';
$string['excludequoted'] = 'Loại trừ Tài liệu Trích dẫn';
$string['excludevalue'] = 'Loại trừ Trùng khớp Nhỏ';
$string['excludewords'] = 'Từ';
$string['excludepercent'] = 'Phần trăm';
$string['norubric'] = 'Không có thang đánh giá';
$string['otherrubric'] = 'Dùng thang đánh giá thuộc về một người hướng dẫn khác';
$string['attachrubric'] = 'Đính kèm một thang đánh giá vào bài tập này';
$string['launchrubricmanager'] = 'Mở Trình quản lý Thang đánh giá';
$string['attachrubricnote'] = 'Lưu ý: Học sinh sẽ có thể xem các thang đánh giá đính kèm và nội dung của mình trước khi nộp bài.';
$string['erater_handbook_advanced'] = 'Nâng cao';
$string['erater_handbook_highschool'] = 'Trung học Phổ thông';
$string['erater_handbook_middleschool'] = 'Trung học Cơ sở';
$string['erater_handbook_elementary'] = 'Tiểu học';
$string['erater_handbook_learners'] = 'Người học Tiếng Anh';
$string['erater_dictionary_enus'] = 'Từ điển Tiếng Anh Hoa Kỳ';
$string['erater_dictionary_engb'] = 'Từ điển Tiếng Anh Anh Quốc';
$string['erater_dictionary_en'] = 'Cả Từ điển Tiếng Anh Hoa Kỳ và Anh Quốc';
$string['erater'] = 'Cho phép kiểm tra ngữ pháp e-rater';
$string['erater_handbook'] = 'Sổ tay ETS&copy;';
$string['erater_dictionary'] = 'Từ điển e-rater';
$string['erater_categories'] = 'Các danh mục e-rater';
$string['erater_spelling'] = 'Đánh vần';
$string['erater_grammar'] = 'Ngữ pháp';
$string['erater_usage'] = 'Cách dùng từ';
$string['erater_mechanics'] = 'Kỹ năng viết';
$string['erater_style'] = 'Văn phong';
$string['anonblindmarkingnote'] = 'Lưu ý: Cài đặt nhận xét ẩn danh riêng của Turnitin đã được gỡ bỏ. Turnitin sẽ sử dụng cài đặt nhận xét ẩn của Moodle để xác định cài đặt nhận xét ẩn danh.';
$string['transmatch'] = 'Đối chiếu Bản dịch';
$string['genduedate'] = 'Tạo báo cáo vào ngày đến hạn (học viên có thể gửi lại cho đến ngày đến hạn)';
$string['genimmediately1'] = 'Tạo báo cáo ngay (học viên không thể gửi lại)';
$string['genimmediately2'] = 'Tổng hợp báo cáo ngay lập tức (học sinh có thể nộp bài lại cho đến ngày hết hạn nộp): Sau {$a->num_resubmissions} bài nộp lại, báo cáo sẽ được tổng hợp sau {$a->num_hours} giờ';
$string['launchquickmarkmanager'] = 'Mở Trình Quản lý Quickmark';
$string['launchpeermarkmanager'] = 'Mở Trình Quản lý Peermark';
$string['studentreports'] = 'Hiển thị Báo cáo Độc sáng cho Học sinh';
$string['studentreports_help'] = 'Cho phép bạn hiển thị các báo cáo độc sáng của Turnitin cho những người dùng là học sinh. Nếu bạn đặt là chấp thuận, báo cáo độc sáng do Turnitin tổng hợp sẽ được hiển thị cho học sinh xem.';
$string['submitondraft'] = 'Nộp tập tin khi vừa tải lên';
$string['submitonfinal'] = 'Nộp tập tin khi học sinh gửi để chấm điểm';
$string['draftsubmit'] = 'Khi nào thì tập tin sẽ được nộp cho Turnitin?';
$string['allownonor'] = 'Cho phép nộp tập tin có định dạng bất kỳ?';
$string['allownonor_help'] = 'Cài đặt này cho phép nộp tập tin có định dạng bất kỳ. Nếu tùy chọn được chọn là &#34;Đồng ý&#34;, các bài nộp sẽ được kiểm tra về tính độc sáng ở những chỗ có thể, bài nộp sẽ khả dụng để tải về và các công cụ phản hồi GradeMark sẽ khả dụng ở những phần có thể.';
$string['norepository'] = 'Không có Kho dữ liệu';
$string['standardrepository'] = 'Kho dữ liệu Chuẩn';
$string['submitpapersto'] = 'Lưu trữ Bài của Học sinh';
$string['institutionalrepository'] = 'Kho dữ liệu của Tổ chức (Nếu có)';
$string['checkagainstnote'] = 'Lưu ý: Nếu bạn không chọn "Có" cho ít nhất một trong các tùy chọn "Đối chiếu với..." bên dưới thì Báo cáo độc sáng sẽ KHÔNG được tổng hợp.';
$string['spapercheck'] = 'Đối chiếu với những bài đã lưu của học sinh';
$string['internetcheck'] = 'Đối chiếu với Internet';
$string['journalcheck'] = 'Đối chiếu với các tạp chí chuyên ngành, <br />tạp chí định kỳ và các ấn phẩm xuất bản';
$string['compareinstitution'] = 'Đối chiếu các tập tin đã nộp với các bài đã nộp vào bên trong tổ chức này';
$string['reportgenspeed'] = 'Tốc độ Tổng hợp Báo cáo';
$string['locked_message'] = 'Thông báo đã khóa';
$string['locked_message_help'] = 'Nếu có bất kỳ cài đặt nào bị khóa, thông báo này sẽ hiển thị để cho biết lý do.';
$string['locked_message_default'] = 'Cài đặt này bị khóa ở cấp độ trang mạng';
$string['sharedrubric'] = 'Thang đánh giá chung';
$string['turnitinrefreshsubmissions'] = 'Làm mới các Bài nộp';
$string['turnitinrefreshingsubmissions'] = 'Làm mới các Bài nộp';
$string['turnitinppulapre'] = 'Để nộp tập tin cho Turnitin, trước tiên bạn phải chấp thuận EULA của chúng tôi. Nếu bạn chọn không chấp thuận EULA của chúng tôi thì tập tin của bạn sẽ chỉ được nộp cho Moodle. Nhấp vào đây để chấp thuận.';
$string['noscriptula'] = '(Do bạn không cho phép Javascript, bạn sẽ phải làm mới một cách thủ công trang này để có thể thực hiện nộp bài sau khi đã chấp nhận Thỏa thuận Người Dùng Turnitin)';
$string['filedoesnotexist'] = 'Tập tin đã được xóa';
$string['reportgenspeed_resubmission'] = 'Bạn đã nộp bài tập này và một Báo cáo Tính Tương đồng đã được tạo cho bài bạn nộp. Nếu bạn chọn nộp lại bài, bài tập bạn đã nộp trước đây sẽ được thay thế và một báo cáo mới sẽ được tạo. Sau {$a->num_resubmissions} lần nộp lại bài tập, bạn sẽ cần phải đợi {$a->num_hours} giờ sau mỗi lần nộp lại bài để xem Báo cáo Tính Tương đồng mới.';

// Plugin settings.
$string['config'] = 'Cấu hình';
$string['defaults'] = 'Cài đặt Mặc định';
$string['showusage'] = 'Hiển thị Kết xuất Dữ liệu';
$string['saveusage'] = 'Lưu Kết xuất Dữ liệu';
$string['errors'] = 'Lỗi';
$string['turnitinconfig'] = 'Cấu hình phần Bổ trợ về Đạo văn của Turnitin';
$string['tiiexplain'] = 'Turnitin là một sản phẩm thương mại và bạn phải trả phí thuê bao đăng ký để sử dụng dịch vụ này. Để biết thêm thông tin, vui lòng xem <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>';
$string['useturnitin'] = 'Cho phép Turnitin';
$string['useturnitin_mod'] = 'Cho phép Turnitin cho {$a}';
$string['pp_configuredesc'] = 'Bạn phải định cấu hình mô-đun này bên trong mô-đun turnitintooltwo. Vui lòng nhấp vào <a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>đây</a> để định cấu hình phần bổ trợ này';
$string['turnitindefaults'] = 'Cài đặt mặc định phần bổ trợ chống đạo văn của Turnitin';
$string['defaultsdesc'] = 'Các cài đặt sau đây là mặc định khi cho phép Turnitin bên trong một Mô-đun Hoạt động';
$string['turnitinpluginsettings'] = 'Cài đặt phần bổ trợ chống đạo văn của Turnitin';
$string['pperrorsdesc'] = 'Xảy ra sự cố khi cố gắng tải các tập tin dưới đây lên Turnitin. Để nộp lại, hãy chọn tập tin bạn muốn nộp lại rồi nhấn nút nộp lại. Sau đó, những tập tin này sẽ được xử lý vào lần chạy cron tiếp theo.';
$string['pperrorssuccess'] = 'Các tập tin bạn chọn đã được nộp lại và sẽ được cron xử lý.';
$string['pperrorsfail'] = 'Xảy ra sự cố với một số tập tin bạn chọn, không thể tạo một sự kiện cron mới cho chúng.';
$string['resubmitselected'] = 'Nộp lại Tập tin Đã chọn';
$string['deleteconfirm'] = 'Bạn có chắc muốn xóa bài nộp này không?\n\nThao tác này sẽ không thể hoàn tác.';
$string['deletesubmission'] = 'Xóa Bài nộp';
$string['semptytable'] = 'Không tìm thấy kết quả nào.';
$string['configupdated'] = 'Đã cập nhật cấu hình';
$string['defaultupdated'] = 'Đã cập nhật các cài đặt mặc định Turnitin';
$string['notavailableyet'] = 'Không khả dụng';
$string['resubmittoturnitin'] = 'Nộp lại cho Turnitin';
$string['resubmitting'] = 'Đang nộp lại';
$string['id'] = 'ID';
$string['student'] = 'Học sinh';
$string['course'] = 'Khóa học';
$string['module'] = 'Mô-đun';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'Xem Báo cáo Độc Sáng';
$string['launchrubricview'] = 'Xem Thang đánh giá dùng để chấm điểm';
$string['turnitinppulapost'] = 'Tập tin của bạn chưa được nộp vào Turnitin. Vui lòng nhấp vào đây để chấp thuận EULA của chúng tôi.';
$string['ppsubmissionerrorseelogs'] = 'Tập tin này chưa được nộp cho Turnitin, vui lòng tư vấn quản trị viên hệ thống của bạn';
$string['ppsubmissionerrorstudent'] = 'Tập tin này chưa được nộp vào Turnitin, vui lòng tư vấn trợ giảng của bạn để biết thêm chi tiết';

// Receipts.
$string['messageprovider:submission'] = 'Thông báo về Biên lai Điện tử trong Phần bổ trợ Chống đạo văn của Turnitin';
$string['digitalreceipt'] = 'Biên lai Điện tử';
$string['digital_receipt_subject'] = 'Đây là Biên lai Điện tử Turnitin của bạn';
$string['pp_digital_receipt_message'] = '{$a->firstname} {$a->lastname} thân mến!<br /><br />Bạn đã nộp thành công tập tin <strong>{$a->submission_title}</strong> cho bài tập <strong>{$a->assignment_name}{$a->assignment_part}</strong> trong lớp <strong>{$a->course_fullname}</strong> trên <strong>{$a->submission_date}</strong>. Id bài nộp của bạn là <strong>{$a->submission_id}</strong>. Bạn có thể xem và in biên lai điện tử của mình từ nút in/tải về trong Trình xem Tài liệu.<br /><br />Cảm ơn bạn đã sử dụng Turnitin!<br /><br />Nhóm Turnitin';

// Paper statuses.
$string['turnitinid'] = 'ID Turnitin';
$string['turnitinstatus'] = 'Trạng thái Turnitin';
$string['pending'] = 'Đang chờ';
$string['similarity'] = 'Tương đồng';
$string['notorcapable'] = 'Không thể tổng hợp một Báo cáo Độc sáng cho tập tin này.';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'Học sinh đã xem bài vào:';
$string['student_notread'] = 'Học sinh vẫn chưa xem bài này.';
$string['launchpeermarkreviews'] = 'Mở Bình duyệt Peermark';

// Cron.
$string['ppqueuesize'] = 'Số lượng sự kiện trong hàng đợi sự kiện của Phần bổ trợ Chống đạo văn';
$string['ppcronsubmissionlimitreached'] = 'Sẽ không có thêm bài nộp nào được gửi tới Turnitin bằng lệnh thực thi cron này vì chỉ có {$a} được xử lý trên mỗi lần chạy';
$string['cronsubmittedsuccessfully'] = 'Bài nộp: {$a->title} (ID TII: {$a->submissionid}) cho bài tập {$a->assignmentname} trên khóa học {$a->coursename} đã được nộp thành công cho Turnitin.';
$string['pp_submission_error'] = 'Turnitin đã trả về một lỗi với bài nộp của bạn:';
$string['turnitindeletionerror'] = 'Xóa bài nộp Turnitin không thành công. Bản lưu cục bộ trong Moodle đã được gỡ bỏ nhưng không thể xóa bài nộp trong Turnitin.';
$string['ppeventsfailedconnection'] = 'Sẽ không có sự kiện nào được phần bổ trợ chống đạo văn của Turnitin xử lý bằng lệnh thực thi cron này vì không thể thiết lập kết nối tới Turnitin.';

// Error codes.
$string['tii_submission_failure'] = 'Vui lòng tư vấn trợ giảng hoặc quản trị viên hệ thống của bạn để biết thêm chi tiết';
$string['faultcode'] = 'Mã Lỗi';
$string['line'] = 'Dòng';
$string['message'] = 'Thông báo';
$string['code'] = 'Mã';
$string['tiisubmissionsgeterror'] = 'Xảy ra lỗi khi đang cố gắng lấy bài nộp cho bài tập này từ Turnitin';
$string['errorcode0'] = 'Tập tin này chưa được nộp cho Turnitin, vui lòng tư vấn quản trị viên hệ thống của bạn';
$string['errorcode1'] = 'Tập tin này chưa được gửi tới Turnitin vì tập tin không có đủ nội dung để tổng hợp Báo cáo Độc sáng.';
$string['errorcode2'] = 'Tập tin này sẽ không được nộp cho Turnitin vì vượt quá kích cỡ tối đa cho phép là {$a->maxfilesize}';
$string['errorcode3'] = 'Tập tin này chưa được nộp cho Turnitin vì người dùng chưa chấp thuận Thỏa thuận Giấy phép Người dùng Cuối của Turnitin.';
$string['errorcode4'] = 'Bạn phải tải lên một tập tin ở định dạng được hỗ trợ cho bài tập này. Định dạng tập tin được chấp nhận gồm: .doc, .docx, .ppt, .pptx, .pps, .ppsx, .pdf, .txt, .htm, .html, .hwp, .odt, .wpd, .ps và .rtf';
$string['errorcode5'] = 'Tập tin này chưa được nộp cho Turnitin vì có sự cố khi tạo mô-đun trong Turnitin khiến các bài nộp bị chặn, vui lòng xem các bản ghi API của bạn để biết thêm thông tin';
$string['errorcode6'] = 'Tập tin này chưa được nộp cho Turnitin vì có sự cố khi chỉnh sửa cài đặt mô-đun trong Turnitin khiến không nộp được, vui lòng xem các bản ghi API của bạn để biết thêm thông tin';
$string['errorcode7'] = 'Tập tin này chưa được nộp cho Turnitin vì có sự cố khi tạo người dùng trong Turnitin khiến không nộp được, vui lòng xem các bản ghi API của bạn để biết thêm thông tin';
$string['errorcode8'] = 'Tập tin này chưa được nộp cho Turnitin vì có sự cố khi tạo tập tin tạm thời. Rất có thể nguyên nhân là do tên tập tin không hợp lệ. Vui lòng đổi tên tập tin và tải lên lại bằng chức năng Hiệu chỉnh Bài nộp.';
$string['errorcode9'] = 'Không thể nộp tập tin vì không có nội dung truy cập được trong vùng lưu trữ tập tin để nộp.';
$string['coursegeterror'] = 'Không thể lấy dữ liệu khóa học';
$string['configureerror'] = 'Bạn phải định cấu hình toàn bộ cho mô-đun này trong vai trò Quản trị viên trước khi sử dụng mô-đun trong một khóa học. Vui lòng liên lạc quản trị viên Moodle của bạn.';
$string['turnitintoolofflineerror'] = 'Chúng tôi hiện đang gặp một sự cố tạm thời. Vui lòng thử lại trong giây lát.';
$string['defaultinserterror'] = 'Xảy ra lỗi khi cố gắng chèn một giá trị cài đặt mặc định vào cơ sở dữ liệu';
$string['defaultupdateerror'] = 'Xảy ra lỗi khi cố gắng cập nhật một giá trị cài đặt mặc định vào cơ sở dữ liệu';
$string['tiiassignmentgeterror'] = 'Xảy ra lỗi khi cố gắng lấy một bài tập từ Turnitin';
$string['assigngeterror'] = 'Không thể lấy dữ liệu turnitintooltwo';
$string['classupdateerror'] = 'Không thể cập nhật dữ liệu Lớp Turnitin';
$string['pp_createsubmissionerror'] = 'Xảy ra lỗi khi đang cố gắng tạo bài nộp trên Turnitin';
$string['pp_updatesubmissionerror'] = 'Xảy ra lỗi khi đang cố gắng nộp lại bài nộp trên Turnitin';
$string['tiisubmissiongeterror'] = 'Xảy ra lỗi khi đang cố gắng lấy bài nộp từ Turnitin';

// Javascript.
$string['closebutton'] = 'Đóng';
$string['loadingdv'] = 'Đang tải Trình xem Tài liệu Turnitin...';
$string['changerubricwarning'] = 'Thay đổi hoặc hủy đính kèm một thang đánh giá sẽ gỡ bỏ tất cả điểm đánh giá hiện có theo thang đánh giá đó khỏi các bài nộp trong bài tập này, kể cả các thẻ điểm đã được chấm trước đây. Điểm tổng quát cho những bài đã chấm trước đây sẽ được duy trì.';
$string['messageprovider:submission'] = 'Thông báo về Biên lai Điện tử trong Phần bổ trợ Chống đạo văn của Turnitin';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Trạng thái Turnitin';
$string['deleted'] = 'Đã xóa';
$string['pending'] = 'Đang chờ';
$string['because'] = 'Điều này là do quản trị viên đã xóa bài tập đang chờ khỏi hàng đợi xử lý và hủy bỏ bài nộp cho Turnitin.<br /><strong>Tập tin vẫn tồn tại trong Moodle, vui lòng liên lạc người hướng dẫn của bạn.</strong><br />Vui lòng xem thông tin dưới đây để biết mọi mã lỗi:';
$string['submitpapersto_help'] = '<strong>Không có Kho dữ liệu: </strong><br />Turnitin được hướng dẫn không lưu trữ các tài liệu đã nộp vào bất kỳ kho dữ liệu nào. Chúng tôi sẽ chỉ xử lý bài nộp giấy để thực hiện hoạt động kiểm tra tính tương đồng ban đầu.<br /><br /><strong>Kho dữ liệu Chuẩn: </strong><br />Turnitin sẽ chỉ lưu trữ một bản sao của tài liệu đã nộp trong Kho dữ liệu tiêu chuẩn. Bằng việc chọn tùy chọn này, Turnitin được hướng dẫn chỉ sử dụng tài liệu đã lưu trữ để thực hiện các hoạt động kiểm tra tính tương đồng đối với mọi tài liệu được nộp trong tương lai.<br /><br /><strong>Kho dữ liệu của Tổ chức (Nếu có): </strong><br />Việc chọn tùy chọn này sẽ hướng dẫn Turnitin chỉ thêm tài liệu đã nộp vào kho dữ liệu riêng của trường bạn. Các hoạt động kiểm tra tính tương đồng với tài liệu đã nộp sẽ do những người hướng dẫn khác trong trường của bạn thực hiện.';
$string['errorcode12'] = 'Tệp này chưa được gửi đến Turnitin vì tệp thuộc một bài tập trong khóa học đã bị xóa. ID hàng: ({$a->id}) | ID mô-đun khóa học: ({$a->cm}) | ID người dùng: ({$a->userid})';
