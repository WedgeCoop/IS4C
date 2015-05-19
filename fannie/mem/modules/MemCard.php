<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class MemCard extends \COREPOS\Fannie\API\member\MemberModule {

    // Return a form segment for display or edit the Member Card#
    function showEditForm($memNum, $country="US"){
        global $FANNIE_URL;
        global $FANNIE_MEMBER_UPC_PREFIX;

        $dbc = $this->db();

        $prefix = isset($FANNIE_MEMBER_UPC_PREFIX) ? $FANNIE_MEMBER_UPC_PREFIX : "";
        $plen = strlen($prefix);

        $infoQ = $dbc->prepare_statement("SELECT upc
                FROM memberCards
                WHERE card_no=?");
        $infoR = $dbc->exec_statement($infoQ,array($memNum));
        if ( $infoR === false ) {
            return "Error: problem checking for Member Card<br />";
        }

        $ret = "<div class=\"panel panel-default\">
            <div class=\"panel-heading\">Membership Card</div>
            <div class=\"panel-body\">";
        if ( $dbc->num_rows($infoR) > 0 ) {
            $infoW = $dbc->fetch_row($infoR);
            $upc = $infoW['upc'];
            if ( $prefix && strpos("$upc", "$prefix") === 0 ) {
                $upc = substr($upc,$plen);
                $upc = ltrim($upc,"0");
            }
        } else {
            $upc = "";
        }

        $ret .= '<div class="form-group form-inline">
            <span class="label primaryBackground">Card#</span>
            <input type="text" name="memberCard" class="form-control"
                value="' . $upc . '" />
            </div>';

        $ret .= "</div>";
        $ret .= "</div>";

        return $ret;

    // showEditForm
    }

    // Update, insert or delete the Member Card#.
    // Return "" on success or an error message.
    function saveFormData($memNum){

        global $FANNIE_MEMBER_UPC_PREFIX, $FANNIE_ROOT;
        $dbc = $this->db();
        if (!class_exists("MemberCardsModel"))
            include($FANNIE_ROOT.'classlib2.0/data/models/MemberCardsModel.php');

        $prefix = isset($FANNIE_MEMBER_UPC_PREFIX) ? $FANNIE_MEMBER_UPC_PREFIX : "";
        $plen = strlen($prefix);

        $form_upc = FormLib::get_form_value('memberCard','');
        // Restore prefix and leading 0's to upc.
        if ( $form_upc && strlen($form_upc) < 13 ) {
            $clen = (13 - $plen);
            $form_upc = sprintf("{$prefix}%0{$clen}d", $form_upc);
        }

        $model = new MemberCardsModel($dbc);
        $model->card_no($memNum);
        $model->upc($form_upc);
        $saved = $model->save();
        $model->pushToLanes();

        if (!$saved) {
            return 'Error: problem saving Member Card<br />';
        } else {
            return '';
        }

    // saveFormData
    }

// MemCard
}

?>
