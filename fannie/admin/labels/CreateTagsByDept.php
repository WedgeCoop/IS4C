<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI.php')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CreateTagsByDept extends FanniePage {

    protected $title = "Fannie : Department Shelf Tags";
    protected $header = "Department Shelf Tags";

    public $description = '[Department Shelf Tags] generates a set of shelf tags for given POS
    department(s).';
    public $themed = true;

    private $msgs = '';

    function preprocess(){
        global $FANNIE_OP_DB;
        if (FormLib::get_form_value('deptStart',False) !== False){
            $start = FormLib::get_form_value('deptStart');
            $end = FormLib::get_form_value('deptEnd');
            $pageID = FormLib::get_form_value('sID',0);
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $q = $dbc->prepare_statement("
                SELECT p.upc,
                    p.description,
                    p.normal_price,
                    x.manufacturer,
                    x.distributor,
                    v.sku,
                    CASE WHEN v.size IS NOT NULL THEN v.size ELSE "
                        . $dbc->concat(
                            "p.size",
                            "' '",
                            "p.unitofmeasure",
                            ''
                        ) ." END AS pack_size_and_units,
                    CASE WHEN v.units IS NOT NULL THEN v.units ELSE 1 END AS units_per_case
                FROM products AS p
                    LEFT JOIN prodExtra AS x ON p.upc=x.upc
                    LEFT JOIN vendorItems AS v ON p.upc=v.upc
                    LEFT JOIN vendors AS n ON v.vendorID=n.vendorID
                WHERE p.department BETWEEN ? AND ?
                ORDER BY p.upc,
                    CASE WHEN p.default_vendor_id=v.vendorID THEN 0 ELSE 1 END,
                    CASE WHEN x.distributor=n.vendorName THEN 0 ELSE 1 END,
                    v.vendorID"
            );
            $r = $dbc->exec_statement($q,array($start,$end));
            $tag = new ShelftagsModel($dbc);
            $prevUPC = 'invalidUPC';
            while ($w = $dbc->fetch_row($r)) {
                if ($prevUPC == $w['upc']) {
                    // multiple vendor matches for this item
                    // already created a tag for it w/ first
                    // priority vendor
                    continue;
                }
                $tag->id($pageID);
                $tag->upc($w['upc']);
                $tag->description($w['description']);
                $tag->normal_price($w['normal_price']);
                $tag->brand($w['manufacturer']);
                $tag->sku($w['sku']);
                $tag->size($w['pack_size_and_units']);
                $tag->units($w['units_per_case']);
                $tag->vendor($w['distributor']);
                $tag->pricePerUnit(\COREPOS\Fannie\API\lib\PriceLib::pricePerUnit($w['normal_price'], $w['size']));
                $tag->save();
                $prevUPC = $w['upc'];
            }
            $this->msgs = sprintf('<em>Created tags for departments #%d through #%d</em>
                    <br /><a href="ShelfTagIndex.php">Home</a>',
                $start, $end);
        }
        return True;
    }

    function body_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $deptsQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
        $deptsR = $dbc->exec_statement($deptsQ);
        $deptsList = "";

        $deptSubQ = $dbc->prepare_statement("SELECT superID,super_name FROM MasterSuperDepts
                GROUP BY superID,super_name
                ORDER BY superID");
        $deptSubR = $dbc->exec_statement($deptSubQ);

        $deptSubList = "";
        while($deptSubW = $dbc->fetch_array($deptSubR)){
          $deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
        }
        while ($deptsW = $dbc->fetch_array($deptsR))
          $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";

        $ret = '';
        if (!empty($this->msgs)){
            $ret .= '<div class="alert alert-success">';
            $ret .= $this->msgs;
            $ret .= '</div>';
        }

        ob_start();
        ?>
        <form action="CreateTagsByDept.php" method="get">
        <div class="row form-group form-horizontal"> 
            <label class="col-sm-2">Department Start</label>
            <div class="col-sm-4">
                <select onchange="$('#deptStart').val($(this).val());"
                    class="form-control">
                    <?php echo "$deptsList\n" ?>
                </select>
            </div>
            <div class="col-sm-2">
                <input type=text name=deptStart id=deptStart class="form-control" value=1 />
            </div>
        </div>
        <div class="row form-group form-horizontal"> 
            <label class="col-sm-2">Department End</label>
            <div class="col-sm-4">
                <select onchange="$('#deptEnd').val($(this).val());"
                    class="form-control">
                    <?php echo "$deptsList\n" ?>
                </select>
            </div>
            <div class="col-sm-2">
                <input type=text name=deptEnd id=deptEnd class="form-control" value=1 />
            </div>
        </div>
        <div class="row form-group form-horizontal">
            <label class="col-sm-2">Page</label>
            <div class="col-sm-4">
                <select name="sID" class="form-control">
                    <?php echo $deptSubList; ?></select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-default">Create Shelftags</button>
            </div>
        </div>
        </form>
        <?php
        return $ret.ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Create shelf tags for all items in a 
            POS department range. Tags will be queued for
            printing under the selected super department.</p>';
    }
}

FannieDispatch::conditionalExec();

?>
