<?php
$action = 'modifier';

require_once dirname(__FILE__).'/../../../sources/Afup/AFUP_Assemblee_Generale.php';
require_once dirname(__FILE__).'/../../../sources/Afup/AFUP_Personnes_Physiques.php';
require_once dirname(__FILE__).'/../../../sources/Afup/AFUP_Cotisations.php';

$assemblee_generale = new AFUP_Assemblee_Generale($bdd);
$cotisations = new AFUP_Cotisations($bdd);
$personnes_physiques = new AFUP_Personnes_Physiques($bdd);

$timestamp = $assemblee_generale->obternirDerniereDate();

$identifiant = $droits->obtenirIdentifiant();
$cotisation = $personnes_physiques->obtenirDerniereCotisation($identifiant);

if ($timestamp > strtotime("-1 day", time())) {
    $date_assemblee_generale = convertirTimestampEnDate($timestamp);
    $smarty->assign('date_assemblee_generale', $date_assemblee_generale);
    if ($timestamp > strtotime("+14 day", $cotisation['date_fin'])) {
        $smarty->assign('erreur', 'La date d\'échéance de votre dernière cotisation précède la date de la prochaine assemblée générale.<br/><br/>Vous ne pourrez donc pas voter lors de cette assemblée générale.<br/><br/>Vous pouvez dès à présent régler votre cotisation via <a href="/pages/administration/index.php?page=membre_cotisation">"Ma cotisation"</a>');
    } else {
        list($presence, $id_personne_avec_pouvoir) = $assemblee_generale->obtenirInfos($_SESSION['afup_login'], $timestamp);
        $assemblee_generale->marquerConsultation($_SESSION['afup_login'], $timestamp);


        $formulaire = &instancierFormulaire('index.php?page=membre_assemblee_generale');
        $formulaire->setDefaults(array('date' => date("d/m/Y", time()),
                                       'presence' => $presence,
                                       'id_personne_avec_pouvoir' => $id_personne_avec_pouvoir));

        $formulaire->addElement('header'  , ''         , 'Je serais présent(e)');
        $formulaire->addElement('radio'   , 'presence' , 'Oui'                   , '', AFUP_ASSEMBLEE_GENERALE_PRESENCE_OUI);
        $formulaire->addElement('radio'   , 'presence' , 'Non'                   , '', AFUP_ASSEMBLEE_GENERALE_PRESENCE_NON);
        $formulaire->addElement('radio'   , 'presence' , 'Je ne sais pas encore' , '', AFUP_ASSEMBLEE_GENERALE_PRESENCE_INDETERMINE);

        $formulaire->addElement('header'  , ''                         , 'Je donne mon pouvoir à');
        $formulaire->addElement('select'  , 'id_personne_avec_pouvoir' , 'Nom' , array(null => '' ) + $assemblee_generale->obtenirPresents($timestamp));

        $formulaire->addElement('header'  , 'boutons'   , '');
        $formulaire->addElement('hidden'  , 'date'      , $timestamp);
        $formulaire->addElement('submit'  , 'soumettre' , 'confirmer');

        if ($formulaire->validate()) {
            if ($action == 'modifier') {
                $ok = $assemblee_generale->modifier($_SESSION['afup_login'],
                                                    $timestamp,
                                                    $formulaire->exportValue('presence'),
                                                    $formulaire->exportValue('id_personne_avec_pouvoir'));
            }

            if ($ok) {
                if ($action == 'modifier') {
                    AFUP_Logs::log('Modification de la présence et du pouvoir de la personne physique');
                }
                afficherMessage('La présence et le pouvoir ont été modifiés', 'index.php?page=membre_assemblee_generale');
            } else {
                $smarty->assign('erreur', 'Une erreur est survenue lors de la modification de la présence et du pouvoir');
            }
        }

        $smarty->assign('formulaire', genererFormulaire($formulaire));
    }
} else {
		unset($assemblee_generale);
}
?>