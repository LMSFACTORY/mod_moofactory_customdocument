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
 * Language strings for the customdocument module
 *
 * @package     mod_customdocument
 * @copyright   2024 Patrick ROCHET <patrick.r@lmsfactory.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$string['modulename'] = 'Document personnalisé';
$string['modulenameplural'] = 'Documents personnalisés';
$string['pluginname'] = 'Document personnalisé';
$string['viewcertificateviews'] = 'Voir les {$a} documents délivrés';
$string['summaryofattempts'] = 'Résumé des documents reçus précédemment';
$string['issued'] = 'Délivré';
$string['coursegrade'] = 'Note de la formation';
$string['awardedto'] = 'Décerné à';
$string['receiveddate'] = 'Date de réception';
$string['issueddate'] = 'Date de déliverance';
$string['grade'] = 'Note';
$string['code'] = 'Code';
$string['report'] = 'Rapport';
$string['hours'] = 'heures';
$string['keywords'] = 'document, certificate, course, pdf, moodle';
$string['pluginadministration'] = 'Administration du document';
$string['deletissuedcertificates'] = 'Supprimer les documents délivrés';
$string['nocertificatesissued'] = 'Il n\'y a aucun document délivré.';
// $string['awardedsubject'] = 'Notification de document émis : {$a->document} délivré à {$a->student}';
$string['awardedsubject'] = '{$a->student} a obtenu un document sur {$a->sitefullname}';
$string['modulename_help'] = 'Document personnalisé permet aux enseignants/formateurs de créer un document personnalisé (attestation...) pouvant être délivré aux participants ayant levé les restrictions d\'accès de l\'activité.';
$string['certificatecopy'] = 'COPIER';

// Généraux
$string['certificatename'] = 'Nom du document';
$string['certificatename_help'] = 'Nom du document, notamment affiché sur la page de cours';
$string['intro'] = 'Description';

//Conception de la première page
$string['designoptions'] = 'Conception de la première page';
$string['certificateimage'] = 'Image de fond du document';
$string['certificateimage_help'] = 'Image qui sera utilisée en arrière plan de la première page du document.';
$string['certificatetext'] = 'Contenu de la première page';
$string['certificatetext_help'] = 'Ajoutez le contenu de la première page du document : textes, tableaux, champs de fusion (champs qui seront remplacés par les données de l\'apprenant), images (logo, signature)... et mettez-les en forme.';
$string['infochamps'] = '<a href="{$a}" target="popup" >Cliquez ici pour afficher tous les champs de fusions</a>';

$string['height'] = 'Hauteur du document';
$string['width'] = 'Largeur du document';
$string['size'] = 'Taille du document';
$string['size_help'] = 'Largeur et hauteur (en millimètres) du document (toutes les pages). La taille par défaut est : Portrait A4';
$string['textposition'] = 'Position du contenu de la première page';
$string['textposition_help'] = 'Coordonnées XY (en millimètres) du contenu de la première page du document';
$string['certificatetextx'] = 'Marge de gauche du contenu de la première page';
$string['certificatetexty'] = 'Marge du haut du contenu de la première page';

// Conception de la seconde page
$string['secondpageoptions'] = 'Conception de la seconde page';
$string['enablesecondpage'] = 'Activer la seconde page du document';
$string['enablesecondpage_help'] = 'Si vous souhaitez ajouter une seconde page au document, activez cette option.';
$string['secondimage'] = 'Image de fond de la seconde page du document';
$string['secondimage_help'] = 'Image qui sera utilisée en arrière plan de la seconde page du document.';
$string['secondpagetext'] = 'Contenu de la seconde page';

$string['secondpagex'] = 'Marge de gauche du contenu de la seconde page';
$string['secondpagey'] = 'Marge du haut du contenu de la seconde page';
$string['secondtextposition'] = 'Position du contenu de la seconde page';
$string['secondtextposition_help'] = 'Coordonnées XY (en millimètres) du contenu de la seconde page du document';

// Conception de la troisième page
$string['thirdpageoptions'] = 'Conception de la troisième page';
$string['enablethirdpage'] = 'Activer la troisième page du document';
$string['enablethirdpage_help'] = 'Si vous souhaitez ajouter une troisième page au document, activez cette option.';
$string['thirdimage'] = 'Image de fond de la troisième page du document';
$string['thirdimage_help'] = 'Image qui sera utilisée en arrière plan de la troisième page du document.';
$string['thirdpagetext'] = 'Contenu de la troisième page';

$string['thirdpagex'] = 'Marge de gauche du contenu de la troisième page';
$string['thirdpagey'] = 'Marge du haut du contenu de la troisième page';
$string['thirdtextposition'] = 'Position du contenu de la troisième page';
$string['thirdtextposition_help'] = 'Coordonnées XY (en millimètres) du contenu de la troisième page du document';

// Conception de la quatrième page
$string['fourthpageoptions'] = 'Conception de la quatrième page';
$string['enablefourthpage'] = 'Activer la quatrième page du document';
$string['enablefourthpage_help'] = 'Si vous souhaitez ajouter une quatrième page au document, activez cette option.';
$string['fourthimage'] = 'Image de fond de la quatrième page du document';
$string['fourthimage_help'] = 'Image qui sera utilisée en arrière plan de la quatrième page du document.';
$string['fourthpagetext'] = 'Contenu de la quatrième page';

$string['fourthpagex'] = 'Marge de gauche du contenu de la quatrième page';
$string['fourthpagey'] = 'Marge du haut du contenu de la quatrième page';
$string['fourthtextposition'] = 'Position du contenu de la quatrième page';
$string['fourthtextposition_help'] = 'Coordonnées XY (en millimètres) du contenu de la quatrième page du document';

//Références et formats des champs de fusion
$string['variablesoptions'] = 'Références et format des champs de fusion';
$string['printdate'] = 'Date d\'achèvement de l\'activité sélectionnée pour le champ de fusion {{ACTIVITYCOMPLETIONDATE}}';
$string['printdate_help'] = 'Sélectionnez l\'activité dont la date d\'achèvement par l s\'affichera pour le champ de fusion {{ACTIVITYCOMPLETIONDATE}}';

$string['printgrade'] = 'Note de référence pour les tableaux et le champ de fusion {{GRADE}}';
$string['printgrade_help'] = 'Sélectionnez la note du cours ou celle d\'une activité présente dans le carnet de notes. Cet élément sera la référence pour afficher la note de l\'étudiant à la place du champ de fusion {{GRADE}} mais aussi celui qui s\'affichera dans les listes "Récapitulatif des documents délivrés" et "Action en lot".';
$string['gradefmt'] = 'Format de la note de l\'apprenant';
$string['gradefmt_help'] = 'Il existe trois formats de notes disponibles : en pourcentage, en point (dépend du paramétrage des différentes activités) ou en lettre (dépend du paramétrage du carnet de note).';
$string['gradeletter'] = 'Note par lettres';
$string['gradepercent'] = 'Note par pourcentage';
$string['gradepoints'] = 'Note par points';

$string['datefmt'] = 'Format de date';
$string['datefmt_help'] = 'Entrez un format de date PHP valide (<a href="http://www.php.net/manual/en/function.strftime.php"> Date Formats</a>). Ou, laissez le champ vide pour garder le format par défaut.';
$string['printoutcome'] = 'Imprimer le résultat';
$string['printoutcome_help'] = 'Choisissez n\'importe quel élément du cours dont vous voulez imprimer le résultat obtenu par l\'utilisateur sur le document  (<a href="https://docs.moodle.org/3x/fr/Objectifs">Objectifs</a>). Un exemple pourrait être "Résultat obtenu : Maîtrise."';

// Options du QR Code
$string['qrcodeoptions'] = 'Options du QR code';
$string['printqrcode'] = 'Imprimer le QR code du document';
$string['printqrcode_help'] = 'Sélectionnez "Oui" pour imprimer un QR code unique pour chaque document généré.';
$string['qrcodefirstpage'] = 'Imprimer le QR code sur la première page';
$string['qrcodefirstpage_help'] = 'Sélectionnez "oui" pour imprimer le QR code sur la première page et "non" pour l\'imprimer sur la dernière page.';
$string['codex'] = 'Position initiale horizontale du QR code';
$string['codey'] = 'Position initiale verticale du QR code';
$string['qrcodeposition'] = 'Position du QR code';
$string['qrcodeposition_help'] = 'Coordonnées XY (en millimètres) du QR Code sur la page sélectionnée du document';

//Options de délivrance
$string['issueoptions'] = 'Options de délivrance';
$string['emailteachers'] = 'Notifier et envoyer une copie par courriel aux enseignants et superviseurs de l\'apprenant';
$string['emailteachers_help'] = 'Si "Oui", chaque fois qu\'un document sera généré, les rôles ayant la capacité "mod/customdocument:canreceivenotifications" recevront un courriel avec le document en pièce jointe.';
$string['emailothers'] = 'Autres adresses courriels à notifier';
$string['emailothers_help'] = 'Entrez ici, séparées par une virgule, les adresses courriel qui doivent être alertées chaque fois qu\'un document sera généré.';

$string['delivery'] = 'Comportement lors de l\'émission des documents par l\'étudiant';
$string['delivery_help'] = 'Choisissez ici la façon dont vous souhaitez que vos étudiants obtiennent leur document lorsqu\'ils cliquent sur le bouton "Obtenir mon document".
<br>En retournant sur l\'activité, l\'apprenant verra la date à laquelle son document a été émis.';
$string['openbrowser'] = 'Ouvrir dans une nouvelle fenêtre';
$string['download'] = 'Forcer le téléchargement';
$string['emailcertificate'] = 'Envoi par courriel';
$string['nodelivering'] = 'Pas d\'émission du document, l\'utilisateur le recevra autrement';
$string['emailoncompletion'] = 'Envoi automatique par courriel après achevement du cours';
$string['emailonrestriction'] = 'Envoi automatique par courriel après levée de la restriction d\'accès';

// Générer un document ou un document de test
$string['standardview'] = 'Générer un document de test';
$string['getcertificate'] = 'Obtenir votre document';
$string['openwindow'] = 'Cliquez sur ce bouton pour ouvrir votre document dans une nouvelle fenêtre de votre navigateur.';
$string['opendownload'] = 'Cliquez sur ce bouton pour télécharger votre document.';
$string['issueddownload'] = 'Le document généré [id: {$a}] a été téléchargé';
$string['openemail'] = 'Cliquez sur ce bouton pour recevoir votre document par courriel.';
$string['emailsent'] = 'L\'email a été envoyé';

//Récapitulatif des documents délivrés
$string['issuedview'] = 'Récapitulatif des documents délivrés';
$string['firstname'] = "Prénom";
$string['lastname'] = "Nom";
$string['fullname'] = "Nom complet";
$string['receptiondate'] = "Date de réception";
$string['deleteall'] = "Tout supprimer";
$string['deleteselected'] = "Supprimer la sélection";

// Action en lot
$string['bulkview'] = 'Action en lot';
$string['showusers'] = 'Afficher';
$string['completedusers'] = 'Utilisateurs qui remplissent les conditions (restriction d\'accès)';
$string['allusers'] = 'Tous les utilisateurs inscrits';
$string['bulkaction'] = 'Choisir une action en lot : ';
$string['onepdf'] = 'Télécharger les documents dans un fichier pdf (1 page par document)';
$string['multipdf'] = 'Télécharger les documents dans un fichier zip (1 fichier pdf par document)';
$string['sendtoemail'] = 'Envoyer à l\'adresse courriel de l\'utilisateur';
$string['bulkbuttonlabel'] = 'Télécharger ou envoyer les documents';
$string['bulkwarning'] = "Si aucune sélection, tous les apprenants de la liste seront pris en compte";

// Contenu des emails
$string['emailstudentsubject'] = 'Votre document est disponible sur {$a->sitefullname}';
$string['emailstudenttext'] = '
Bonjour {$a->username},

Ci-joint vous trouverez votre document "{$a->certificate}" concernant la formation "{$a->course}".
 
CECI EST UN MESSAGE AUTOMATIQUE - MERCI DE NE PAS REPONDRE';

$string['emailteachermail'] = '
Bonjour{$a->username},

{$a->student} a obtenu le document "{$a->document}" pour la formation "{$a->course}".

Ci-joint vous trouverez le document.
Vous pouvez également le consulter à l\'adresse : {$a->url}.

CECI EST UN MESSAGE AUTOMATIQUE - MERCI DE NE PAS REPONDRE';

$string['emailteachermailhtml'] = '
Bonjour{$a->username},

{$a->student} a obtenu le document "<i>{$a->document}</i>" pour la formation "{$a->course}".

Ci-joint vous trouverez le document.
Vous pouvez également le consulter à l\'adresse : <a href="{$a->url}">Récapitulatif des documents délivrés</a>.

CECI EST UN MESSAGE AUTOMATIQUE - MERCI DE NE PAS REPONDRE';

// Admin settings page
$string['defaultwidth'] = 'Largeur par défaut';
$string['defaultheight'] = 'Hauteur par défaut';
$string['defaultcertificatetextx'] = 'Marge de gauche par défaut du contenu du document';
$string['defaultcertificatetexty'] = 'Marge du haut par défaut du contenu du document';
$string['defaultcodex'] = 'Position initiale horizontale du QR code par défaut';
$string['defaultcodey'] = 'Position initiale verticale du QR code par défaut';
$string['certlifetime'] = 'Documents récupérables pendant (en mois) :';
$string['certlifetime_help'] = 'Durée en mois pendant laquelle vous souhaitez conserver les documents délivrés. Les documents délivrés qui sont plus vieux que cet âge sont automatiquement supprimés.';
$string['defaultperpage'] = 'Nombre d\'éléments par page';
$string['defaultperpage_help'] = 'Nombre d\'éléments (apprenant ou document) à afficher par page (max: 200) pour les "Récaptulatif des documents délivrés" et "Action en lot".';

// Verify document page
$string['certificateverification'] = 'Vérification du document';
$string['neverdeleteoption'] = 'Ne jamais supprimer';
$string['verifycertificate'] = 'Verifier le document';
$string['eventcertificate_verified'] = 'Document vérifié';
$string['eventcertificate_verified_description'] = 'The user with id {$a->userid} verified the document with id {$a->certificateid}, issued to user with id {$a->document_userid}.';

// For Capabilities
$string['customdocument:addinstance'] = "Ajouter une activité Document personnalisé";
$string['customdocument:manage'] = "Gérer l'activité Document personnalisé";
$string['customdocument:view'] = "Voir l'activité Document personnalisé";
$string['customdocument:canreceivenotifications'] = "Recevoir les notifications destinées aux enseignants";

// Erreurs
$string['filenotfound'] = 'Fichier non trouvé : {$a}';
$string['invalidcode'] = 'Code du document invalide';
$string['cantdeleteissue'] = 'Erreur dans la suppression des documents délivrés';
$string['cantissue'] = 'Ce document ne peut être obtenu car l\'utilisateur ne remplit pas les conditions d\'activité';

$string['usercontextnotfound'] = 'Le contexte de l\'utilisateur non trouvé';
$string['usernotfound'] = 'Utilisateur non trouvé';
$string['coursenotfound'] = 'Cours non trouvé';
$string['issuedcertificatenotfound'] = 'Document délivré non trouvé';
$string['certificatenot'] = 'Instance de Document personnalisé non trouvé';

$string['upgradeerror'] = 'Erreur lors de la mise à jour $a';
$string['notreceived'] = 'Aucun document délivré';

$string['customhelp_help'] = "Contactez le formateur en cas de besoin";

// Tableau des champs de fusion
$string['moduledoctitle'] = "Champs de fusion de Document personalisé";
$string['thnom'] = "Nom";
$string['thdescription'] = "Description";
$string['colon'] = " : ";

//Paramètres du cours
$string['modulecoursetitle'] = "Paramètres du cours";
$string['coursenamedesc'] = "Nom complet du cours";
$string['coursestartdatedesc'] = "Date du début de cours (paramètres du cours)";
$string['courseenddatedesc'] = "Date du fin de cours (paramètres du cours)";
$string['courseversiondesc'] = "Version courante du cours";
$string['coursecustomfielddesc'] = "Champs personnalisés de cours (définis par l'administrateur du site), le XXX correspond au nom abrégé du champ et doit être mis en majuscule.<br>Exemple : COURSECUSTOMFIELD_COURSEVERSION pour le champ 'Version'";

//Champs de profil de l'utilisateur
$string['userprofilefield'] = "Champs de profil de l'utilisateur";
$string['usernamedesc'] = "Nom complet de l'utilisateur (nom + prénom)";
$string['firstnamedesc'] = "Prénom de l'utilisateur";
$string['lastnamedesc'] = "Nom de l'utilisateur";
$string['emaildesc'] = "Adresse email de l'utilisateur";
$string['citydesc'] = "Ville de l'utilisateur";
$string['countrydesc'] = "Pays de l'utilisateur";
$string['userimage'] = "Avatar de l'utilisateur";
$string['idnumberdesc'] = " Numéro d'identification défini dans le profil de l'utilisateur au niveau des champs 'Facultatif'";
$string['institutiondesc'] = "Institution de l'utilisateur";
$string['departmentdesc'] = "Département de l'utilisateur";
$string['phone1desc'] = "Téléphone de l'utilisateur";
$string['phone2desc'] = "Téléphone mobile de l'utilisateur";
$string['addressdesc'] = "Adresse de l'utilisateur";
$string['profile_xxxx_desc'] = "Champs personnalisés de profil de l'utilisateur (définis par l'administrateur du site), le XXX correspond au nom abrégé du champ et doit être mis en majuscule.<br>Exemple : PROFILE_ORGA pour le champ 'Mon organisation'";
// strings notebook and result
$string['resulttitle'] = "Notes et resultats";
$string['gradesdesc'] = "Note sélectionnée dans les 'Références et formats des champs de fusion' (note du cours, note d'une activité…)";
$string['activitygradesdesc'] = "Liste de toutes les notes de l'utilisateur sur toutes les activités du cours.";
$string['outcomedesc'] = "Affiche le niveau de compétence de l'apprenant basé sur le paramétrage des objectifs dans le cours : <a href='https://docs.moodle.org/3x/fr/Objectifs'>Objectifs</a>";

// Strings Dates
$string['activitycompletiondesc'] = "Date d'achèvement de l'activité sélectionnée dans les options 'Références et formats des champs de fusion' du plugin";
$string['deliverancedatedesc'] = "Date de délivrance du certificat";
$string['coursecompletiondesc'] = "Date d'achèvement de cours";
$string['coursefirstaccessdesc'] = "Date de premier accès au cours";
$string['courselastaccessdesc'] = "Date de dernier accès au cours";
$string['enrolmentenddesc'] = "Date de fin de l'inscription de l'apprenant";
$string['enrolmentstartdesc'] = "Date de début de l'inscription de l'apprenant";

// Strings Time
$string['moduletimetitle'] = "Temps";
$string['usermoofactorytimedesc'] = "Temps passé en heures et en minutes sur le cours comme défini dans le format de cours moofactory (logs, Intelliboard ou estimé). Compatible uniquement avec le format de cours moofactory.";

// Strigns Course attribution
$string['moduleattributiontitle'] = "Attribution dans le cours";
$string['teachersdesc'] = "Liste des contacts du cours, comme défini au niveau de l'administration du site sur la page /admin/settings.php?section=coursecontact";
$string['userroledesc'] = "Rôles de l'utilisateur dans le cours";
$string['groupnamedesc'] = "Liste des groupes auxquels est inscrit l'apprenant (en ligne, séparés par une virgule)";

// String Certificate/Document Info
$string['moduleinfotitle'] = "Informations du Document";
$string['certcodedesc'] = "Code unique du document";

$string['formatnotfound'] = "Avertissement: Le plugin 'Format de cours moofactory' est nécessaire";
$string['formatfound'] = "Le plugin 'Format de cours moofactory' est bien installé. Vous pouvez utiliser pleinement le document personnalisé moofactory. Plus d'information sur la suite <a href='https://moofactory.fr/' target='_blank'>moofactory.fr</a> ";
$string['formatwarning'] = "Avertissement : pour utiliser le champs de fusion {USERMOOFACTORYTIME} vous devez installer le plugin 'Format de cours moofactory'. Plus d'information sur ce plugin : <a href='https://moofactory.fr/' target='_blank'>moofactory.fr</a>";

// Validity.
$string['validity'] = 'Durée de validité du document, en mois.';
$string['validity_help'] = 'Durée pendant laquelle le document est valide.';
$string['defaultvalidity'] = 'Durée de validité par défaut du document, en mois.';
$string['renewalperiod'] = 'Délai de renouvellement du document, en semaines.';
$string['renewalperiod_help'] = 'Délai pendant lequel l\'utilisateur à la possibilité de renouveler son document. La date de début est calulée en fontion de la durée de validité. À partir de cette date, l\'utilisateur devra à nouveau lever les éventuelles restrictions pour l\'obtention d\'un nouveau document.';
$string['defaultrenewalperiod'] = 'Délai de renouvellement par défaut du document, en semaines.';
$string['resetall'] = 'Réinitialiser toutes les activités du cours.';
$string['resetall_help'] = 'Si cette case est cochée, toutes les activités du cours seront réinitialisées à la date de début de la période de renouvellement (achèvement et note). Dans le cas contraire, seules les activités faisant l\'objet de restrictions pour l\'obtention d\'un nouveau document le seront.';
$string['expired'] = ' <span class="expired">(périmé)</span>';
$string['expiredtxt'] = ' (périmé)';

$string['version_category'] = 'Version';
$string['courseversion'] = 'Version courante du cours';

// cron task
$string['sendmailrestriction'] = 'Envoi de couriels après levée des restrictions';
