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
$string['pluginname'] = 'Plugin de plágio Turnitin';
$string['turnitintooltwo'] = 'Ferramenta Turnitin';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Tarefa do Plugin de Plágio Turnitin';
$string['connecttesterror'] = 'Ocorreu um erro ao se conectar no Turnitin, a mensagem de erro de retorno está abaixo:<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'Habilitar o Turnitin';
$string['excludebiblio'] = 'Excluir Bibliografia';
$string['excludequoted'] = 'Excluir Material Citado';
$string['excludevalue'] = 'Excluir Correspondências Menores';
$string['excludewords'] = 'Palavras';
$string['excludepercent'] = 'Porcentagem';
$string['norubric'] = 'Nenhuma rubrica';
$string['otherrubric'] = 'Usar rubrica que pertence a outro professor';
$string['attachrubric'] = 'Anexar uma rubrica a esse trabalho';
$string['launchrubricmanager'] = 'Iniciar Gerenciador de Rubricas';
$string['attachrubricnote'] = 'Observação: os alunos poderão visualizar as rubricas anexadas e seus conteúdos antes do envio.';
$string['erater_handbook_advanced'] = 'Avançado';
$string['erater_handbook_highschool'] = 'Ensino Médio';
$string['erater_handbook_middleschool'] = 'Ensino Fundamental';
$string['erater_handbook_elementary'] = 'Ensino Fundamental';
$string['erater_handbook_learners'] = 'Estudantes de Inglês';
$string['erater_dictionary_enus'] = 'Dicionário de Inglês Americano';
$string['erater_dictionary_engb'] = 'Dicionário de Inglês Britânico';
$string['erater_dictionary_en'] = 'Dicionários de Inglês Americano e Britânico';
$string['erater'] = 'Habilitar a verificação gramatical e-rater';
$string['erater_handbook'] = 'Manual ETS&copy;';
$string['erater_dictionary'] = 'Dicionário e-rater';
$string['erater_categories'] = 'Catrgorias e-rater';
$string['erater_spelling'] = 'Ortografia';
$string['erater_grammar'] = 'Gramática';
$string['erater_usage'] = 'Uso';
$string['erater_mechanics'] = 'Mecânica';
$string['erater_style'] = 'Estilo';
$string['anonblindmarkingnote'] = 'Observação: a configuração de correção anônima Turnitin separada foi removida. O Turnitin usará a configuração de correção cega do Moodle\ para determinar a configuração de correção anônima.';
$string['transmatch'] = 'Correspondência Traduzida';
$string['genduedate'] = 'Gerar relatórios na data de entrega (reenvios são permitidos até a data de entrega)';
$string['genimmediately1'] = 'Gerar relatórios imediatamente (alunos não podem reenviar)';
$string['genimmediately2'] = 'Gerar relatórios imediatamente (alunos podem reenviar até a data de entrega): Após {$a->num_resubmissions} reenvios, gerar relatórios depois de {$a->num_hours} horas';
$string['launchquickmarkmanager'] = 'Iniciar o Gerenciador QuickMark';
$string['launchpeermarkmanager'] = 'Iniciar o Gerenciador PeerMark';
$string['studentreports'] = 'Exibir os Relatórios de Originalidade aos alunos';
$string['studentreports_help'] = 'Permite-o a exibir os relatórios de originalidade Turnitin. Se configurado para sim, o relatório de originalidade gerado pelo Turnitin estará disponível para o aluno visualizá-lo.';
$string['submitondraft'] = 'Enviar arquivo quando for feito o primeiro upload';
$string['submitonfinal'] = 'Enviar o arquivo quando o aluno enviá-lo para avaliação';
$string['draftsubmit'] = 'Quando é que o arquivo deve ser enviado ao Turnitin?';
$string['allownonor'] = 'Permitir envios de qualquer tipo de arquivo?';
$string['allownonor_help'] = 'Essa configuração permitirá o envio de qualquer tipo de arquivo. Com essa opção definida como &#34;Sim&#34;, a originalidade dos documentos será verificada sempre que possível, os documentos estarão disponíveis para download e as ferramentas de comentários GradeMark estarão disponíveis sempre que possível.';
$string['norepository'] = 'Nenhum Depósito';
$string['standardrepository'] = 'Depósito Padrão';
$string['submitpapersto'] = 'Armazenar Documentos dos Alunos';
$string['institutionalrepository'] = 'Depósito Institucional (Onde Aplicável)';
$string['checkagainstnote'] = 'Observação: se você não selecionar "Sim" para pelo menos uma das opções "Comparar com..." abaixo, um relatório de Originalidade NÃO será gerado.';
$string['spapercheck'] = 'Comparar com os documentos armazenados dos alunos';
$string['internetcheck'] = 'Comparar com a internet';
$string['journalcheck'] = 'Comparar com periódicos acadêmicos,<br />jornais e publicações';
$string['compareinstitution'] = 'Comparar os arquivos enviados com os documentos enviados por essa instituição';
$string['reportgenspeed'] = 'Velocidade de Geração do Relatório';
$string['locked_message'] = 'Mensagem trancada';
$string['locked_message_help'] = 'Se alguma configuração estiver trancada, essa mensagem é mostrada para dizer o porquê.';
$string['locked_message_default'] = 'Essa configuração está trancada a nível local';
$string['sharedrubric'] = 'Rubrica Compartilhada';
$string['turnitinrefreshsubmissions'] = 'Atualizar Envios';
$string['turnitinrefreshingsubmissions'] = 'Atualizando os envios';
$string['turnitinppulapre'] = 'Para enviar um arquivo ao Turnitin, primeiro você precisa aceitar nosso Contrato de Licença do Usuário Final. Se optar por não aceitar nosso Contrato de Licença do Usuário Final, seu arquivo será enviado apenas para o Moodle. Clique aqui para aceitar.';
$string['noscriptula'] = '(Como você não tem o JavaScript ativado, você terá que atualizar manualmente esta página antes de poder fazer um envio após aceitar o Acordo de Usuário Turnitin)';
$string['filedoesnotexist'] = 'O arquivo foi excluído';
$string['reportgenspeed_resubmission'] = 'Você já enviou um documento para esse trabalho e um Relatório de similaridades foi gerado para o seu envio. Se você optar por reenviar o seu documento, o seu envio anterior será substituído e um novo relatório será gerado. Após {$a->num_resubmissions} reenvios, você precisará esperar {$a->num_hours} horas após um reenvio para ver um novo relatório.';

// Plugin settings.
$string['config'] = 'Configurações';
$string['defaults'] = 'Configurações Padrão';
$string['showusage'] = 'Mostrar Dados do Despejo';
$string['saveusage'] = 'Salvar Dados do Despejo';
$string['errors'] = 'Erros';
$string['turnitinconfig'] = 'Configurações do Plugin de Plágio Turnitin';
$string['tiiexplain'] = 'O Turnitin é um produto comercial e você deve pagar uma assinatura para usar esse serviço. Para obter mais informações, consulte <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>';
$string['useturnitin'] = 'Habilitar o Turnitin';
$string['useturnitin_mod'] = 'Habilitar Turnitin para {$a}';
$string['pp_configuredesc'] = 'Você deve configurar esse módulo dentro do módulo turnitintooltwo. Clique <a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>aqui</a> para configurar esse plugin';
$string['turnitindefaults'] = 'Configurações padrão do plugin de plágio Turnitin';
$string['defaultsdesc'] = 'As seguintes configurações são as padrões definidas ao ativar o Turnitin em um módulo de atividades';
$string['turnitinpluginsettings'] = 'Configurações do plugin de plágio Turnitin';
$string['pperrorsdesc'] = 'Ocorreu um problema ao tentar fazer o upload dos arquivos abaixo para o Turnitin. Para reenviar, selecione os arquivos que você deseja reenviar e pressione o botão reenviar. Eles serão processados da próxima vez em que o cron for executado.';
$string['pperrorssuccess'] = 'Os arquivos que você selecionou foram reenviados e serão processados pelo cron.';
$string['pperrorsfail'] = 'Ocorreu um problema com alguns arquivos que você selecionou, um novo evento cron não pôde ser criado para eles.';
$string['resubmitselected'] = 'Reenviar Arquivos Selecionados';
$string['deleteconfirm'] = 'Tem certeza que você quer apagar esse envio?\n\nIsso não pode ser desfeito.';
$string['deletesubmission'] = 'Apagar Envio';
$string['semptytable'] = 'Nenhum resultado encontrado.';
$string['configupdated'] = 'Configurações atualizadas';
$string['defaultupdated'] = 'Configurações padrão Turnitin atualizadas';
$string['notavailableyet'] = 'Não disponível';
$string['resubmittoturnitin'] = 'Reenviar para Turnitin';
$string['resubmitting'] = 'Reenviado(s)';
$string['id'] = 'Identificação';
$string['student'] = 'Aluno';
$string['course'] = 'Curso';
$string['module'] = 'Módulo';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'Visualizar o Relatório de Originalidade';
$string['launchrubricview'] = 'Visualizar a rubrica usada para avaliação';
$string['turnitinppulapost'] = 'Seu arquivo não foi enviado ao Turnitin. Clique aqui para aceitar nosso Contrato de Licença do Usuário Final.';
$string['ppsubmissionerrorseelogs'] = 'Esse arquivo não foi enviado ao Turnitin. Consulte o administrador do sistema';
$string['ppsubmissionerrorstudent'] = 'Esse arquivo não foi enviado ao Turnitin. Consulte seu tutor para obter mais detalhes.';

// Receipts.
$string['messageprovider:submission'] = 'Notificações de Recebimento Digital do Plugin de Plágio Turnitin';
$string['digitalreceipt'] = 'Recibo Digital';
$string['digital_receipt_subject'] = 'Esse é o seu Recibo Digital Turnitin';
$string['pp_digital_receipt_message'] = 'Prezado(a) {$a->firstname} {$a->lastname},<br /><br />Você enviou com sucesso o arquivo <strong>{$a->submission_title}</strong> para o trabalho <strong>{$a->assignment_name}{$a->assignment_part}</strong> na aula <strong>{$a->course_fullname}</strong> sobre <strong>{$a->submission_date}</strong>. A identificação do seu envio é <strong>{$a->submission_id}</strong>. Seu recibo digital completo pode ser visualizado e impresso a partir do botão imprimir/baixar no Visualizador de Documentos.<br /><br />Obrigado por usar o Turnitin,<br /><br />A Equipe Turnitin';

// Paper statuses.
$string['turnitinid'] = 'Identificação Turnitin';
$string['turnitinstatus'] = 'Status Turnitin';
$string['pending'] = 'Pendente';
$string['similarity'] = 'Semelhança';
$string['notorcapable'] = 'Não é possível gerar um Relatório de Originalidade para esse arquivo.';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'O aluno visualizou o documento em:';
$string['student_notread'] = 'O aluno não visualizou esse documento.';
$string['launchpeermarkreviews'] = 'Iniciar as Revisões PeerMark';

// Cron.
$string['ppqueuesize'] = 'Número de eventos na fila de eventos do Plugin de Plágio';
$string['ppcronsubmissionlimitreached'] = 'Nenhum outro envio será feito para o Turnitin por esta execução de cron, uma vez que são processados apenas {$a} por operação.';
$string['cronsubmittedsuccessfully'] = 'Envio: {$a->title} (TII ID: {$a->submissionid}) para o trabalho {$a->assignmentname} no curso {$a->coursename} foi enviado com sucesso ao Turnitin.';
$string['pp_submission_error'] = 'O Turnitin retornou um erro com o seu envio:';
$string['turnitindeletionerror'] = 'Falha ao excluir o envio Turnitin. A cópia local do Moodle foi removida, mas o envio no Turnitin não pôde ser excluído.';
$string['ppeventsfailedconnection'] = 'Nenhum evento será processado pelo plugin de plágio Turnitin por essa execução do cron, já que a conexão ao Turnitin não pode ser estabelecida.';

// Error codes.
$string['tii_submission_failure'] = 'Consulte seu tutor ou o administrador do sistema para obter mais detalhes';
$string['faultcode'] = 'Código de falha';
$string['line'] = 'Linha';
$string['message'] = 'Mensagem';
$string['code'] = 'Código';
$string['tiisubmissionsgeterror'] = 'Ocorreu um erro ao tentar obter os envios para esse trabalho a partir do Turnitin';
$string['errorcode0'] = 'Esse arquivo não foi enviado ao Turnitin. Consulte o administrador do sistema';
$string['errorcode1'] = 'Esse arquivo não foi enviado ao Turnitin por não ter conteúdo suficiente para produzir um Relatório de Originalidade.';
$string['errorcode2'] = 'Esse arquivo não será enviado ao Turnitin pois excede o tamanho máximo de {$a} permitido.';
$string['errorcode3'] = 'Esse arquivo não foi enviado ao Turnitin porque o usuário não aceitou o Contrato de Licença do Usuário Final Turnitin';
$string['errorcode4'] = 'Você deve fazer o upload de um tipo de arquivo suportado para esse trabalho. Os tipos de arquivo aceitos são: .doc, .docx, .ppt, .pptx, .pps, .ppsx, .pdf, .txt, .htm, .html, .hwp, .odt, .wpd, .ps e .rtf';
$string['errorcode5'] = 'Esse arquivo não foi enviado ao Turnitin porque houve um problema na criação do módulo no Turnitin que está impedindo os envios, consulte seus registros de API para mais informações';
$string['errorcode6'] = 'Esse arquivo não foi enviado ao Turnitin porque houve um problema na edição das configurações do módulo no Turnitin que está impedindo os envios, consulte seus registros de API para mais informações';
$string['errorcode7'] = 'Esse arquivo não foi enviado ao Turnitin porque houve um problema na criação do usuário no Turnitin que está impedindo os envios, consulte seus registros de API para mais informações';
$string['errorcode8'] = 'Esse arquivo não foi enviado ao Turnitin porque houve um problema na criação do arquivo temporário. A causa mais provável é um nome de arquivo inválido. Renomeie o arquivo e refaça o upload usando Editar Envio.';
$string['errorcode9'] = 'O arquivo não pôde ser enviado por não ter conteúdo acessível no pool de arquivos para enviar.';
$string['coursegeterror'] = 'Não foi possível obter dados do curso';
$string['configureerror'] = 'Você deve configurar este módulo totalmente como Administrador antes de usá-lo dentro de um curso. Entre em contato com o administrador do Moodle.';
$string['turnitintoolofflineerror'] = 'Estamos passando por um problema temporário. Tente novamente em breve.';
$string['defaultinserterror'] = 'Ocorreu um erro ao tentar inserir um valor de configuração padrão no banco de dados';
$string['defaultupdateerror'] = 'Ocorreu um erro ao tentar um valor de configuração padrão no banco de dados';
$string['tiiassignmentgeterror'] = 'Ocorreu um erro ao tentar obter um trabalho a partir do Turnitin';
$string['assigngeterror'] = 'Não foi possível obter os dados do turnitintooltwo';
$string['classupdateerror'] = 'Não foi possível atualizar os dados da Aula Turnitin';
$string['pp_createsubmissionerror'] = 'Ocorreu um erro ao tentar criar o envio no Turnitin';
$string['pp_updatesubmissionerror'] = 'Ocorreu um erro ao tentar reenviar seus envios ao Turnitin';
$string['tiisubmissiongeterror'] = 'Ocorreu um erro ao tentar obter um envio a partir do Turnitin';

// Javascript.
$string['closebutton'] = 'Fechar';
$string['loadingdv'] = 'Carregando Visualizador de Documentos Turnitin...';
$string['changerubricwarning'] = 'Ao alterar ou separar uma rubrica irá remover todas as pontuações de rubricas existentes dos documentos nesse trabalho, incluindo os cartões de pontuação que foram pontuados anteriormente . As notas gerais para trabalhos avaliados anteriormente serão mantidas.';
$string['messageprovider:submission'] = 'Notificações de Recebimento Digital do Plugin de Plágio Turnitin';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Status Turnitin';
$string['deleted'] = 'Excluído';
$string['pending'] = 'Pendente';
$string['because'] = 'Isso ocorreu porque um administrador excluiu o trabalho pendente da fila de processamento e abortou o envio para o Turnitin.<br /><strong>O arquivo ainda existe no Moodle, entre em contato com seu instrutor.</strong><br />Veja abaixo para mais códigos de erro:';
$string['submitpapersto_help'] = '<strong>Nenhum Depósito: </strong><br />Instrui-se à Turnitin não armazenar documentos enviados em nenhum repositório. Nós só processaremos o documento para realizar a verificação de similaridade inicial.<br /><br /><strong>Depósito Padrão: </strong><br />A Turnitin armazenará uma cópia do documento enviado somente no Repositório Padrão. Ao escolher essa opção, a Turnitin é instruída a usar somente os documentos armazenados para realizar verificações de similaridade contra qualquer documento enviado no futuro.<br /><br /><strong>Depósito Institucional (Onde Aplicável): </strong><br />Escolher essa opção instrui a Turnitin a somente adicionar documentos enviados para um repositório privado na sua instituição. As verificações de similaridade para os documentos enviados só serão realizadas por outros professores da sua instituição.';
$string['errorcode12'] = 'Este arquivo não foi enviado à Turnitin porque pertence a um trabalho cujo o curso foi excluído. ID da linha: ({$a->id}) | ID do módulo do curso: ({$a->cm}) | ID do usuário: ({$a->userid})';
$string['tiiaccountconfig'] = 'Configurações da Conta Turnitin';
$string['turnitinaccountid'] = 'Identificação da Conta Turnitin';
$string['turnitinsecretkey'] = 'Chave Compartilhada Turnitin';
$string['turnitinapiurl'] = 'URL da API Turnitin';
$string['tiidebugginglogs'] = 'Depuração e Registro';
$string['turnitindiagnostic'] = 'Habilitar Modo Diagnóstico';
$string['enableperformancelogs'] = 'Habilitar registro de desempenho de rede';
$string['enableperformancelogs_desc'] = 'Se habilitado, cada solicitação ao servidor do Turnitin será registrada em {tempdir}/turnitintooltwo/logs';
$string['turnitindiagnostic_desc'] = '<b>[Cuidado]</b><br />Habilitar modo de Diagnóstico somente para rastrear os problemas com o API do Turnitin.';
$string['tiiaccountsettings_desc'] = 'Certifique-se que essas configurações correspondem àquelas configuradas em sua conta Turnitin, senão você pode ter problemas com a criação de trabalhos e/ou envios dos alunos.';
$string['tiiaccountsettings'] = 'Configurações da Conta Turnitin';
$string['turnitinusegrademark'] = 'Utilizar o GradeMark';
$string['turnitinusegrademark_desc'] = 'Escolher se prefere utilizar o GradeMark para avaliar os envios.<br /><i>(Isso só está disponível para aqueles que têm o GradeMark configurado em suas contas)</i>';
$string['turnitinenablepeermark'] = 'Habilitar os trabalhos PeerMark';
$string['turnitinenablepeermark_desc'] = 'Selecione se permite ou não a criação dos Trabalhos PeerMark<br/><i>(Essa opção só está disponível para aqueles que têm o PeerMark configurado em suas contas)</i>';
$string['turnitinuseerater'] = 'Habilitar ETS&copy;';
$string['turnitinuseerater_desc'] = 'Escolha entre habilitar verificação de gramática ETS&copy;.<br /><i>(Habilite essa opção apenas se o e-rater ETS&copy; estiver habilitado em sua conta Turnitin)</i>';
$string['transmatch_desc'] = 'Determina se a Correspondência Traduzida estará disponível como uma configuração na tela de configuração do trabalho.<br /><i>(Habilite essa opção somente se a Correspondência Traduzida estiver habilitada em sua conta Turnitin)</i>';
$string['repositoryoptions_0'] = 'Habilitar opções de depósito padrão do professor';
$string['repositoryoptions_1'] = 'Ativar as opções de depósito expandido do professor';
$string['repositoryoptions_2'] = 'Enviar todos documento para o depósito padrão';
$string['repositoryoptions_3'] = 'Não enviar documentos para um depósito';
$string['turnitinrepositoryoptions'] = 'Trabalhos do depósito de documentos';
$string['turnitinrepositoryoptions_desc'] = 'Escolher as opções de depósitos para Trabalhos Turnitin.<br /><i>(Um Depósito Institucional só está disponível para aqueles que habilitaram essa opção na conta)</i>';
$string['tiimiscsettings'] = 'Configurações Variadas de Plugins';
$string['pp_agreement_default'] = 'Ao selecionar essa caixa, eu confirmo que este envio é o meu próprio trabalho e eu aceito toda a responsabilidade por qualquer infração que possam ocorrer como resultado deste envio.';
$string['pp_agreement_desc'] = '<b>[Opcional]</b><br />Digite uma declaração de confirmação de acordo para os envios.<br />(<b>Observação:</b> Se o acordo for deixado completamente em branco, nenhuma confirmação de acordo será exigida dos alunos durante o envio)';
$string['pp_agreement'] = 'Aviso Legal / Acordo';
$string['studentdataprivacy'] = 'Configurações de privacidade de dados dos alunos';
$string['studentdataprivacy_desc'] = 'As seguintes configurações podem ser configuradas para assegurar que os dados pessoais do aluno&#39; não sejam transmitidos para o Turnitin através do API.';
$string['enablepseudo'] = 'Habilitar a Privacidade do Aluno';
$string['enablepseudo_desc'] = 'Se essa opção estiver selecionada, os endereços de email dos alunos serão transformados em um pseudo equivalente às chamadas Turnitin API.<br /><i>(<b>Observação:</b> essa opção não pode ser alterada se os dados do usuário Moodle já tiverem sido sincronizados com o Turnitin)</i>';
$string['pseudofirstname'] = 'Pseudo Nome do Aluno';
$string['pseudofirstname_desc'] = '<b>[Opcional]</b><br />O nome do aluno a ser exibido no visualizador de documentos Turnitin';
$string['pseudolastname'] = 'Pseudo Sobrenome do Aluno';
$string['pseudolastname_desc'] = 'O sobrenome do aluno a ser exibido no visualizador de documentos Turnitin';
$string['pseudolastnamegen'] = 'Auto Gerar Sobrenome';
$string['pseudolastnamegen_desc'] = 'Se definido para sim e o pseudo sobrenome for definido para um campo de perfil de usuário, o campo será automaticamente preenchido com um identificador exclusivo.';
$string['pseudoemailsalt'] = 'Sal da Pseudocriptografia';
$string['pseudoemailsalt_desc'] = '<b>[Opcional]</b><br />Um sal opcional para aumentar a complexidade do endereço de Pseudoemail do Aluno gerado.<br />(<b>Observação:</b> o sal deve permanecer inalterado a fim de manter consistentes os endereços de pseudoemail)';
$string['pseudoemaildomain'] = 'Domínio do Pseudoemail';
$string['pseudoemaildomain_desc'] = '<b>[Opcional]</b><br />Um domínio opcional para os endereços de pseudoemail. (Padrões para @tiimoodle.com, se deixado em branco)';
$string['pseudoemailaddress'] = 'Endereço Pseudoemail';
$string['connecttest'] = 'Testar Conexão do Turnitin';
$string['connecttestsuccess'] = 'O Moodle se conectou ao Turnitin com sucesso.';
$string['diagnosticoptions_0'] = 'Desligado';
$string['diagnosticoptions_1'] = 'Padrão';
$string['diagnosticoptions_2'] = 'Depuração';
