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
$string['pluginname'] = 'Plugin de plagiat Turnitin';
$string['turnitintooltwo'] = 'Outil Turnitin';
$string['turnitin'] = 'Turnitin';
$string['task_name'] = 'Tâche du plugin de plagiat Turnitin';
$string['connecttesterror'] = 'Une erreur est survenue lors de la connexion à Turnitin. Vous trouverez le message d’erreur ci-dessous :<br />';

// Assignment Settings.
$string['turnitin:enable'] = 'Activer Turnitin';
$string['excludebiblio'] = 'Exclure la bibliographie';
$string['excludequoted'] = 'Exclure les citations';
$string['excludevalue'] = 'Exclure les faibles correspondances';
$string['excludewords'] = 'Mots';
$string['excludepercent'] = 'Pourcentage';
$string['norubric'] = 'Aucune rubrique';
$string['otherrubric'] = 'Utiliser la grille d&#39;évaluation appartenant à un autre enseignant';
$string['attachrubric'] = 'Joindre une rubrique à cet exercice';
$string['launchrubricmanager'] = 'Démarrer le Gestionnaire de rubrique';
$string['attachrubricnote'] = 'Remarque : les étudiants pourront voir les rubriques jointes et leurs contenus avant de soumettre leur devoir.';
$string['erater_handbook_advanced'] = 'Avancé';
$string['erater_handbook_highschool'] = 'Lycée';
$string['erater_handbook_middleschool'] = 'Collège';
$string['erater_handbook_elementary'] = 'École Primaire';
$string['erater_handbook_learners'] = 'Étudiants en anglais';
$string['erater_dictionary_enus'] = 'Dictionnaire anglais (États-Unis)';
$string['erater_dictionary_engb'] = 'Dictionnaire anglais (Royaume-Uni)';
$string['erater_dictionary_en'] = 'Dictionnaires anglais (États-Unis et Royaume-Uni)';
$string['erater'] = 'Activer le correcteur de grammaire e-rater';
$string['erater_handbook'] = 'Manuel ETS&copy;';
$string['erater_dictionary'] = 'Dictionnaire e-rater';
$string['erater_categories'] = 'Catégories e-rater';
$string['erater_spelling'] = 'Orthographe';
$string['erater_grammar'] = 'Grammaire';
$string['erater_usage'] = 'Usage';
$string['erater_mechanics'] = 'Syntaxe';
$string['erater_style'] = 'Style';
$string['anonblindmarkingnote'] = 'Remarque : le paramètre d’annotations anonymes à part de Turnitin a été supprimé. Turnitin utilisera le paramètre d’anonymat des copies de Moodle pour déterminer le paramètre d’annotations anonymes.';
$string['transmatch'] = 'Traducteur de Similitude';
$string['genduedate'] = 'Générer des rapports à la date limite (les étudiants peuvent renvoyer jusqu’à la date limite)';
$string['genimmediately1'] = 'Générer des rapports immédiatement (les étudiants ne peuvent pas renvoyer)';
$string['genimmediately2'] = 'Générer des rapports immédiatement (les étudiants peuvent renvoyer jusqu’à la date limite) : Après {$a->num_resubmissions} renvois, les rapports se génèrent après {$a->num_hours} heures';
$string['launchquickmarkmanager'] = 'Démarrer le Gestionnaire Quickmark';
$string['launchpeermarkmanager'] = 'Démarrer le Gestionnaire Peermark';
$string['studentreports'] = 'Montrer les rapports d’originalité aux étudiants';
$string['studentreports_help'] = 'Cette option vous permet d’autoriser les étudiants à voir leur Rapport de Similitude. En sélectionnant Oui, le rapport d’analyse généré par Turnitin sera disponible pour les étudiants.';
$string['submitondraft'] = 'Soumettre le document une fois qu’il est chargé';
$string['submitonfinal'] = 'Soumettre le document lorsque l&#39;étudiant l&#39;envoie pour l&#39;évaluation';
$string['draftsubmit'] = 'Quand le document doit-il être envoyé à Turnitin ?';
$string['allownonor'] = 'Autoriser les envois de tous types de fichiers ?';
$string['allownonor_help'] = 'Ce paramètre permet l’envoi de tous types de fichiers. Si cette option est configurée sur &#34;Oui&#34;, le système vérifiera l’originalité des documents envoyés, si cela est possible ; les documents envoyés pourront être téléchargés et les outils de commentaires GradeMark seront aussi disponibles, si cela est possible.';
$string['norepository'] = 'Aucune base de données';
$string['standardrepository'] = 'Base de données standard';
$string['submitpapersto'] = 'Conserver les copies des étudiants';
$string['institutionalrepository'] = 'Base de données de l’établissement (le cas échéant)';
$string['checkagainstnote'] = 'Remarque : si vous ne sélectionnez pas « Oui » pour au moins une des options « Comparer avec... » ci-dessous, AUCUN rapport d’originalité ne sera généré.';
$string['spapercheck'] = 'Comparer avec la base de données des copies des étudiants';
$string['internetcheck'] = 'Comparer avec Internet';
$string['journalcheck'] = 'Comparer avec les journaux,<br />les revues et les publications';
$string['compareinstitution'] = 'Comparer les fichiers envoyés avec les copies soumises à l’établissement';
$string['reportgenspeed'] = 'Vitesse de traitement du rapport';
$string['locked_message'] = 'Message verrouillé';
$string['locked_message_help'] = 'Si un paramètre est verrouillé, ce message s’affiche pour expliquer pourquoi.';
$string['locked_message_default'] = 'Ce paramètre est verrouillé à l’échelle du site';
$string['sharedrubric'] = 'Rubrique partagée';
$string['turnitinrefreshsubmissions'] = 'Actualiser les envois';
$string['turnitinrefreshingsubmissions'] = 'Actualisation des envois';
$string['turnitinppulapre'] = 'Pour envoyer un fichier à Turnitin, vous devez d’abord accepter notre CLUF. Si vous ne l’acceptez pas, votre fichier ne sera envoyé qu’à Moodle. Cliquez ici pour accepter.';
$string['noscriptula'] = '(Comme vous n’avez pas JavaScript activé, vous devez manuellement actualiser cette page avant de pouvoir envoyer un document, et ce, après avoir accepté le contrat d’utilisateur de Turnitin)';
$string['filedoesnotexist'] = 'Le fichier a été supprimé';
$string['reportgenspeed_resubmission'] = 'Vous avez déjà soumis une copie pour cet exercice et un rapport de similarité a été généré pour votre copie envoyée. Si vous choisissez de renvoyer votre copie, votre précédente copie envoyée sera remplacée et un nouveau rapport sera généré. Après {$a->num_resubmissions} renvois, vous devrez attendre {$a->num_hours} heures après un renvoi pour voir un nouveau rapport de similarité.';

// Plugin settings.
$string['config'] = 'Configuration';
$string['defaults'] = 'Réglages par défaut';
$string['showusage'] = 'Affichez le contenu de la base de données';
$string['saveusage'] = 'Enregistrer le contenu de la base de données';
$string['errors'] = 'Erreurs';
$string['turnitinconfig'] = 'Configuration du plugin de plagiat Turnitin';
$string['tiiexplain'] = 'Turnitin est un produit commercial ; pour utiliser ce service, vous devez l’acheter. Pour plus d’informations, veuillez consulter la page <a href=http://docs.moodle.org/en/Turnitin_administration>http://docs.moodle.org/en/Turnitin_administration</a>';
$string['useturnitin'] = 'Activer Turnitin';
$string['useturnitin_mod'] = 'Activer Turnitin pour {$a}';
$string['pp_configuredesc'] = 'Vous devez configurer ce module depuis le module turnitintooltwo. Veuillez cliquer <a href={$a}/admin/settings.php?section=modsettingturnitintooltwo>ici</a> pour configurer ce plugin';
$string['turnitindefaults'] = 'Paramètres par défaut du plugin de plagiat Turnitin';
$string['defaultsdesc'] = 'Les réglages suivants sont configurés par défaut lorsque Turnitin est activé depuis un Module d’activité';
$string['turnitinpluginsettings'] = 'Paramètres du plugin de plagiat Turnitin';
$string['pperrorsdesc'] = 'Un problème est survenu durant le chargement des fichiers ci-dessous vers Turnitin. Pour procéder à un renvoi, sélectionnez les fichiers que vous souhaitez renvoyer et cliquez sur le bouton Renvoyer. Ils seront alors traités durant la prochaine exécution de cron.';
$string['pperrorssuccess'] = 'Les fichiers sélectionnés ont été renvoyés et seront traités par cron.';
$string['pperrorsfail'] = 'Un problème est survenu avec certains des fichiers sélectionnés, un nouvel événement cron n’a pas pu être créé pour eux.';
$string['resubmitselected'] = 'Renvoyer les fichiers sélectionnés';
$string['deleteconfirm'] = 'Voulez-vous vraiment supprimer cet envoi ?\n\nCette opération ne peut pas être annulée.';
$string['deletesubmission'] = 'Supprimer cet envoi';
$string['semptytable'] = 'Aucun résultat trouvé.';
$string['configupdated'] = 'Configuration mise à jour';
$string['defaultupdated'] = 'Mise à jour de Turnitin par défaut';
$string['notavailableyet'] = 'Non disponible';
$string['resubmittoturnitin'] = 'Renvoyer à Turnitin';
$string['resubmitting'] = 'Renvoi en cours';
$string['id'] = 'N°';
$string['student'] = 'Étudiant';
$string['course'] = 'Cours';
$string['module'] = 'Module';

// Grade book/View assignment page.
$string['turnitin:viewfullreport'] = 'Afficher le rapport d’originalité';
$string['launchrubricview'] = 'Voir la rubrique utilisée pour corriger';
$string['turnitinppulapost'] = 'Votre fichier n’a pas été renvoyé à Turnitin. Veuillez cliquer ici pour accepter notre CLUF.';
$string['ppsubmissionerrorseelogs'] = 'Le fichier n’a pas été envoyé à Turnitin, veuillez consulter votre administrateur système pour plus de renseignements';
$string['ppsubmissionerrorstudent'] = 'Le fichier n’a pas été envoyé à Turnitin, veuillez consulter votre tuteur pour plus de renseignements';

// Receipts.
$string['messageprovider:submission'] = 'Notifications d’accusé de réception électronique du plugin de plagiat Turnitin';
$string['digitalreceipt'] = 'Accusé de réception électronique';
$string['digital_receipt_subject'] = 'Voici votre accusé de réception électronique Turnitin';
$string['pp_digital_receipt_message'] = 'Cher(e) {$a->firstname} {$a->lastname},<br /><br />Vous avez bien envoyé le fichier <strong>{$a->submission_title}</strong> pour l’exercice <strong>{$a->assignment_name}{$a->assignment_part}</strong> du cours <strong>{$a->course_fullname}</strong> le <strong>{$a->submission_date}</strong>. Votre numéro d’envoi est le <strong>{$a->submission_id}</strong>. Votre accusé de réception électronique peut être consulté dans son intégralité et imprimé à l’aide du bouton Imprimer/Télécharger dans le Visualiseur de documents.<br /><br />Merci d’utiliser Turnitin,<br /><br />L’équipe Turnitin';

// Paper statuses.
$string['turnitinid'] = 'N° Turnitin';
$string['turnitinstatus'] = 'Statut de Turnitin';
$string['pending'] = 'En attente';
$string['similarity'] = 'Similarité';
$string['notorcapable'] = 'Impossible de produire un rapport d’originalité pour ce fichier.';
$string['grademark'] = 'GradeMark';
$string['student_read'] = 'L’étudiant a consulté la copie le :';
$string['student_notread'] = 'L&#39;étudiant n&#39;a pas consulté cette copie.';
$string['launchpeermarkreviews'] = 'Démarrer les évaluations Peermark';

// Cron.
$string['ppqueuesize'] = 'Nombre d’éléments dans la file d’événements du plugin de plagiat';
$string['ppcronsubmissionlimitreached'] = 'Aucun envoi additionnel ne sera adressé à Turnitin lors de cette exécution de cron, car seulement {$a} éléments sont traités par exécution';
$string['cronsubmittedsuccessfully'] = 'Envoi : {$a->title} (n° TII : {$a->submissionid}) pour l’exercice {$a->assignmentname} du cours {$a->coursename} a bien été envoyé à Turnitin.';
$string['pp_submission_error'] = 'Turnitin a renvoyé un message d’erreur pour votre envoi :';
$string['turnitindeletionerror'] = 'La suppression de la soumission a échoué. La copie locale de Moodle a été supprimée, mais pas l’envoi à Turnitin.';
$string['ppeventsfailedconnection'] = 'Aucun événement ne sera traité par le plugin de plagiat Turnitin pour cette exécution de cron, car aucune connexion à Turnitin ne peut être établie.';

// Error codes.
$string['tii_submission_failure'] = 'Veuillez consulter votre tuteur ou l’administrateur système pour plus de détails';
$string['faultcode'] = 'Code d’erreur';
$string['line'] = 'Ligne';
$string['message'] = 'Message';
$string['code'] = 'Code';
$string['tiisubmissionsgeterror'] = 'Une erreur est survenue en essayant d’obtenir les travaux envoyés pour cet exercice de Turnitin';
$string['errorcode0'] = 'Le fichier n’a pas été envoyé à Turnitin, veuillez consulter votre administrateur système pour plus de renseignements';
$string['errorcode1'] = 'Ce fichier n’a pas été envoyé à Turnitin, car il ne renferme pas suffisamment de contenu pour produire un rapport d’originalité.';
$string['errorcode2'] = 'Impossible de soumettre ce fichier à Turnitin, car sa taille excède le maximum autorisé de {$a}';
$string['errorcode3'] = 'Le fichier n’a pas été envoyé à Turnitin, car l’utilisateur n’a pas accepté les termes du contrat d’utilisateur de Turnitin.';
$string['errorcode4'] = 'Vous devez charger un type de fichier pris en charge pour cet exercice. Les types de fichiers acceptés sont les suivants : .doc, .docx, .ppt, .pptx, .pps, .ppsx, .pdf, .txt, .htm, .html, .hwp, .odt, .wpd, .ps et .rtf';
$string['errorcode5'] = 'Ce fichier n’a pas été envoyé à Turnitin, car un problème survenu lors de la création du module dans Turnitin empêche les envois. Veuillez consulter le journal de votre API pour plus d’informations';
$string['errorcode6'] = 'Ce fichier n’a pas été envoyé à Turnitin, car un problème survenu lors de la modification des paramètres du module dans Turnitin empêche les envois. Veuillez consulter le journal de votre API pour plus d’informations';
$string['errorcode7'] = 'Ce fichier n’a pas été envoyé à Turnitin, car un problème survenu lors de la création de l’utilisateur dans Turnitin empêche les envois. Veuillez consulter le journal de votre API pour plus d’informations';
$string['errorcode8'] = 'Ce fichier n’a pas été envoyé à Turnitin, car un problème est survenu lors de la création du fichier temporaire. La cause la plus probable est un nom de fichier incorrect. Veuillez renommer et renvoyer le fichier avec l’option de modification de l’envoi.';
$string['errorcode9'] = 'Impossible d’envoyer le fichier, car le pool de fichier ne renferme pas de contenu accessible.';
$string['coursegeterror'] = 'Impossible d’obtenir les données du cours';
$string['configureerror'] = 'Vous devez configurer entièrement ce module comme administrateur avant de pouvoir l’utiliser dans un cours. Veuillez contacter votre administrateur de Moodle.';
$string['turnitintoolofflineerror'] = 'Un problème temporaire est survenu. Merci de réessayer.';
$string['defaultinserterror'] = 'Une erreur est survenue en ajoutant une valeur par défaut dans la base de données';
$string['defaultupdateerror'] = 'Une erreur est survenue en tentant d&#39;actualiser une valeur par défaut de la base de données.';
$string['tiiassignmentgeterror'] = 'Une erreur est survenue en essayant d’obtenir un exercice Turnitin';
$string['assigngeterror'] = 'Impossible d’obtenir les données de Turnitintooltwo';
$string['classupdateerror'] = 'Impossible de mettre à jour les données des cours de Turnitin';
$string['pp_createsubmissionerror'] = 'Une erreur est survenue en essayant d’envoyer un document à Turnitin';
$string['pp_updatesubmissionerror'] = 'Une erreur est survenue en essayant de renvoyer votre document à Turnitin';
$string['tiisubmissiongeterror'] = 'Une erreur est survenue en tentant d&#39;obtenir une transmission de Turnitin';

// Javascript.
$string['closebutton'] = 'Fermer';
$string['loadingdv'] = 'Chargement du Visualiseur de Document Turnitin...';
$string['changerubricwarning'] = 'Si vous modifiez ou supprimez une rubrique, l’ensemble des notes de rubrique des copies de cet exercice (dont les scores des fiches d’évaluation) sera effacé. Les notes globales des copies déjà évaluées resteront, quant à elles, inchangées.';
$string['messageprovider:submission'] = 'Notifications d’accusé de réception électronique du plugin de plagiat Turnitin';

// Turnitin Submission Status.
$string['turnitinstatus'] = 'Statut de Turnitin';
$string['deleted'] = 'Supprimé';
$string['pending'] = 'En attente';
$string['because'] = 'Cela s’est produit car un administrateur a supprimé l’exercice en attente de la file d’attente de traitement et a annulé l’envoi à Turnitin.<br /><strong>Le fichier existe toujours dans Moodle, veuillez contacter votre enseignant.</strong><br />Les codes d’erreur sont indiqués ci-dessous :';
$string['submitpapersto_help'] = '<strong>Aucune base de données: </strong><br />Turnitin est chargé de ne pas conserver des documents envoyés à des bases de données. Nous traiterons la copie uniquement pour réaliser la comparaison initiale.<br /><br /><strong>Base de données standard: </strong><br />Turnitin conservera une copie du document envoyé uniquement dans la Base de données standard. En choisissant cette option, Turnitin est chargé d\'utiliser uniquement les documents conservés pour effectuer des comparaisons avec les documents qui seront envoyés à l\'avenir.<br /><br /><strong>Base de données de l’établissement (le cas échéant): </strong><br />Choisir cette option indique à Turnitin d\'ajouter uniquement les documents envoyés à une base de données privée de votre établissement. Des comparaisons des documents envoyés seront effectuées par d\'autres enseignants au sein de votre établissement.';
$string['errorcode12'] = 'Ce fichier n\'a pas été envoyé à Turnitin car il est lié à un exercice dans lequel le cours a été supprimé. N° de ligne : ({$a->id}) | N° de module de cours : ({$a->cm}) | N° d\'utilisateur : ({$a->userid})';
$string['tiiaccountconfig'] = 'Configuration de compte Turnitin';
$string['turnitinaccountid'] = 'Numéro de compte Turnitin';
$string['turnitinsecretkey'] = 'Clé Partagée Turnitin';
$string['turnitinapiurl'] = 'L’URL de L’API Turnitin';
$string['tiidebugginglogs'] = 'Dégogage et journalisation';
$string['turnitindiagnostic'] = 'Activer le mode diagnostique';
$string['enableperformancelogs'] = 'Activer l´enregistrement des performances du réseau';
$string['enableperformancelogs_desc'] = 'Une fois activé, toutes les demandes vers le serveur Turnitin seront enregistrées dans {tempdir}/turnitintooltwo/logs';
$string['turnitindiagnostic_desc'] = '<b>[Attention]</b><br />Le mode Diagnostic ne doit être activé que pour détecter des problèmes liés à l’API de Turnitin.';
$string['tiiaccountsettings_desc'] = 'Veuillez vous assurer que ces paramètres correspondent à ceux configurés dans votre compte Turnitin. Sinon, vous pourriez rencontrer des difficultés lors de la création d’exercices et/ou pour les copies envoyées par les étudiants.';
$string['tiiaccountsettings'] = 'Paramètres de compte Turnitin';
$string['turnitinusegrademark'] = 'Utiliser GradeMark';
$string['turnitinusegrademark_desc'] = 'Choisissez d’utiliser ou non GradeMark pour l’évaluation des envois.<br /><i>(Disponible uniquement pour ceux ayant configuré GradeMark dans leur compte)</i>';
$string['turnitinenablepeermark'] = 'Activer les exercice Peermark';
$string['turnitinenablepeermark_desc'] = 'Choisissez d’autoriser ou non la création d’exercices Peermark.<br/><i>(Disponible uniquement pour ceux ayant Peermark configuré pour leur compte)</i>';
$string['turnitinuseerater'] = 'Activer ETS&copy;';
$string['turnitinuseerater_desc'] = 'Choisissez d’activer ou non le correcteur de grammaire ETS&copy;.<br /><i>(Cette option ne doit être activée que si ETS&copy; e-rater est activé dans votre compte Turnitin)</i>';
$string['transmatch_desc'] = 'Détermine si le Traducteur de Similitude peut être activé sur l’écran de configuration de l’exercice.<br /><i>(Utilisez cette option uniquement si le Traducteur de Similitude est activé dans votre compte Turnitin)</i>';
$string['repositoryoptions_0'] = 'Activer les options de base de données standard de l’enseignant';
$string['repositoryoptions_1'] = 'Activer les options de stockage élargies de l´enseignant';
$string['repositoryoptions_2'] = 'Soumettre tous les documents vers la base de données standard';
$string['repositoryoptions_3'] = 'Ne pas soumettre les documents dans une base de données';
$string['turnitinrepositoryoptions'] = 'Base de Données Documentaire';
$string['turnitinrepositoryoptions_desc'] = 'Choisissez l’option de stockage des exercices Turnitin.<br /><i>(Vous ne pouvez choisir la base de données de l’établissement que si celle-ci est disponible pour le compte)</i>';
$string['tiimiscsettings'] = 'Paramètres de pluging divers';
$string['turnitintooltwoagreement_default'] = 'En cochant cette case je confirme que la copie que je transmets est la mienne et j’accepte toutes responsabilités concernant la violation des droits d’auteurs qui pourrait se produire suite à l’envoi de ce document.';
$string['turnitintooltwoagreement_desc'] = '<b>[Facultatif]</b><br />Merci d’introduire l’accord de confirmation des envois.<br />(<b>Remarque :</b> si le champ de l’accord reste vierge, aucune confirmation ne sera demandée aux étudiants lors de leur envoi)';
$string['turnitintooltwoagreement'] = 'Déclaration légale/ Accord';
$string['studentdataprivacy'] = 'Configuration de la confidentialité des données des étudiants';
$string['studentdataprivacy_desc'] = 'Les paramètres suivants peuvent être configurés pour s&#39;assurer que les données personnelles de l’étudiant ne sont pas transmises à Turnitin via l’API.';
$string['enablepseudo'] = 'Activer les paramètres de confidentialité de l’étudiant';
$string['enablepseudo_desc'] = 'Si cette option est sélectionnée, les adresses e-mails des étudiants seront transformées en pseudo équivalent aux appels d’API de Turnitin.<br /><i>(<b>Remarque :</b> cette option n’est plus modifiable si les données d’un utilisateur Moodle ont déjà été synchronisées avec Turnitin)</i>';
$string['pseudofirstname'] = 'Pseudo prénom de l’étudiant';
$string['pseudofirstname_desc'] = '<b>[Facultatif]</b><br />Prénom de l’étudiant qui s’affiche dans le Visualiseur de documents Turnitin';
$string['pseudolastname'] = 'Pseudo nom de famille de l’étudiant';
$string['pseudolastname_desc'] = 'Le nom de l´étudiant qui s´affiche dans le visualiseur de document Turnitin';
$string['pseudolastnamegen'] = 'Générer automatiquement le nom de famille';
$string['pseudolastnamegen_desc'] = 'Si ce paramètre est activé que le pseudo nom de famille est défini sur un champ de profil utilisateur, ce champ sera automatiquement rempli avec un identifiant unique.';
$string['pseudoemailsalt'] = 'Pseudo salage de chiffrement';
$string['pseudoemailsalt_desc'] = '<b>[Facultatif]</b><br />Salage optionnel pour augmenter la complexité de la pseudo adresse e-mail de l’étudiant.<br />(<b>Remarque :</b> le salage doit rester inchangé pour garder une certaine cohérence au niveau des pseudo adresses e-mails)';
$string['pseudoemaildomain'] = 'Pseudo nom de domaine';
$string['pseudoemaildomain_desc'] = '<b>[Facultatif]</b><br />Un nom de domaine optionnel pour les pseudo adresses e-mail. (Si ce champ est vierge, le nom de domaine @tiimoodle.com est utilisé par défaut)';
$string['pseudoemailaddress'] = 'Pseudo adresse e-mail';
$string['connecttest'] = 'Essayer de vous connecter à Turnitin';
$string['connecttestsuccess'] = 'La connexion de Moodle à Turnitin est réussie.';
