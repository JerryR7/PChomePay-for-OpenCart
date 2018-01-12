<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form_pchomepay" data-toggle="tooltip" title="<?php echo $button_save; ?>"
                        class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>"
                   class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <?php if (isset($error['error_warning'])) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error['error_warning']; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form_pchomepay"
                      class="form-horizontal">
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab-general">
                            <div class="form-group">
                                <label class="col-sm-2 control-label"
                                       for="input-status"><?php echo $entry_status; ?></label>
                                <div class="col-sm-10">
                                    <select name="pchomepay_status" id="input-status" class="form-control">
                                        <?php if ($pchomepay_status) { ?>
                                        <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                        <option value="0"><?php echo $text_disabled; ?></option>
                                        <?php } else { ?>
                                        <option value="1"><?php echo $text_enabled; ?></option>
                                        <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group required">
                                <label class="col-sm-2 control-label"
                                       for="entry-appid"><?php echo $entry_appid; ?></label>
                                <div class="col-sm-10">
                                    <input type="text" name="pchomepay_appid"
                                           value="<?php echo $pchomepay_appid; ?>"
                                           placeholder="<?php echo $entry_appid; ?>" id="entry-appid"
                                           class="form-control"/>
                                    <?php if ($error_appid) { ?>
                                    <div class="text-danger"><?php echo $error_appid; ?></div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="form-group required">
                                <label class="col-sm-2 control-label"
                                       for="entry-secret"><?php echo $entry_secret; ?></label>
                                <div class="col-sm-10">
                                    <input type="text" name="pchomepay_secret"
                                           value="<?php echo $pchomepay_secret; ?>"
                                           placeholder="<?php echo $entry_secret; ?>" id="entry-secret"
                                           class="form-control"/>
                                    <?php if ($error_secret) { ?>
                                    <div class="text-danger"><?php echo $error_secret; ?></div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label"
                                       for="entry-sandbox-secret"><?php echo $entry_sandbox_secret; ?></label>
                                <div class="col-sm-10">
                                    <input type="text" name="pchomepay_sandbox_secret"
                                           value="<?php echo $pchomepay_sandbox_secret; ?>"
                                           placeholder="<?php echo $entry_sandbox_secret; ?>" id="entry-sandbox-secret"
                                           class="form-control"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="input-live-demo">
                                    <span data-toggle="tooltip" title="<?php echo $help_test; ?>">
                                        <?php echo $entry_test; ?>
                                    </span>
                                </label>
                                <div class="col-sm-10">
                                    <select name="pchomepay_test" id="input-live-demo" class="form-control">
                                        <?php if ($pchomepay_test) { ?>
                                        <option value="1" selected="selected"><?php echo $text_yes; ?></option>
                                        <option value="0"><?php echo $text_no; ?></option>
                                        <?php } else { ?>
                                        <option value="1"><?php echo $text_yes; ?></option>
                                        <option value="0" selected="selected"><?php echo $text_no; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="input-debug">
                                    <span data-toggle="tooltip" title="<?php echo $help_debug; ?>">
                                        <?php echo $entry_debug; ?>
                                    </span>
                                </label>
                                <div class="col-sm-10">
                                    <select name="pchomepay_debug" id="input-debug" class="form-control">
                                        <?php if ($pchomepay_debug) { ?>
                                        <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                        <option value="0"><?php echo $text_disabled; ?></option>
                                        <?php } else { ?>
                                        <option value="1"><?php echo $text_enabled; ?></option>
                                        <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="required">
                                    <label class="col-sm-2 control-label">
                                        <?php echo $entry_payment_methods; ?>
                                    </label>
                                </div>
                                <div class="col-sm-2">
                                    <?php $checked = ' checked="checked"'; ?>
                                    <input type="checkbox" name="pchomepay_payment_methods[CARD]" value="CARD"<?php if (isset($pchomepay_payment_methods['CARD'])) { echo $checked; }?>/>
                                    <label class="control-label">
                                        &nbsp;<?php echo $text_card; ?>
                                    </label>
                                    <br/>
                                    <input type="checkbox" name="pchomepay_payment_methods[ATM]" value="ATM"<?php if (isset($pchomepay_payment_methods['ATM'])) { echo $checked; }?>/>
                                    <label class="control-label">
                                        &nbsp;<?php echo $text_atm; ?>
                                    </label>
                                    <br/>
                                    <input type="checkbox" name="pchomepay_payment_methods[EACH]" value="EACH"<?php if (isset($pchomepay_payment_methods['EACH'])) { echo $checked; }?>/>
                                    <label class="control-label">
                                        &nbsp;<?php echo $text_each; ?>
                                    </label>
                                    <br/>
                                    <input type="checkbox" name="pchomepay_payment_methods[ACCT]" value="ACCT"<?php if (isset($pchomepay_payment_methods['ACCT'])) { echo $checked; }?>/>
                                    <label class="control-label">
                                        &nbsp;<?php echo $text_acct; ?>
                                    </label>
                                    <?php if ($error_payment_methods) { ?>
                                    <div class="text-danger"><?php echo $error_payment_methods; ?></div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">
                                    <?php echo $entry_card_installment; ?>
                                </label>
                                <div class="col-sm-2">
                                    <?php $checked = ' checked="checked"'; ?>
                                    <input type="checkbox" name="pchomepay_card_installment[card_0]" value="CARD_0"<?php if (isset($pchomepay_card_installment['card_0'])) { echo $checked; }?>/>
                                    <label class="control-label">
                                        &nbsp;<?php echo $text_card_0; ?>
                                    </label>
                                    <br/>
                                    <input type="checkbox" name="pchomepay_card_installment[card_3]" value="CARD_3"<?php if (isset($pchomepay_card_installment['card_3'])) { echo $checked; }?>/>
                                    <label class="control-label">
                                        &nbsp;<?php echo $text_card_3; ?>
                                    </label>
                                    <br/>
                                    <input type="checkbox" name="pchomepay_card_installment[card_6]" value="CARD_6"<?php if (isset($pchomepay_card_installment['card_6'])) { echo $checked; }?>/>
                                    <label class="control-label">
                                        &nbsp;<?php echo $text_card_6; ?>
                                    </label>
                                    <br/>
                                    <input type="checkbox" name="pchomepay_card_installment[card_12]" value="CARD_12"<?php if (isset($pchomepay_card_installment['card_12'])) { echo $checked; }?>/>
                                    <label class="control-label">
                                        &nbsp;<?php echo $text_card_12; ?>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group required">
                                <label class="col-sm-2 control-label"
                                       for="entry-atm-expiredate"><?php echo $entry_atm_expiredate; ?></label>
                                <div class="col-sm-10">
                                    <input type="text" name="pchomepay_atm_expiredate"
                                           value="<?php echo $pchomepay_atm_expiredate; ?>"
                                           placeholder="<?php echo $entry_atm_expiredate; ?>" id="entry-atm-expiredate"
                                           class="form-control"/>
                                    <?php if ($error_atm_expiredate) { ?>
                                    <div class="text-danger"><?php echo $error_atm_expiredate; ?></div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label"
                                       for="input-geo-zone"><?php echo $entry_geo_zone; ?></label>
                                <div class="col-sm-10">
                                    <select name="pchomepay_geo_zone_id" id="input-geo-zone" class="form-control">
                                        <option value="0"><?php echo $text_all_zones; ?></option>
                                        <?php foreach ($geo_zones as $geo_zone) { ?>
                                        <?php if ($geo_zone['geo_zone_id'] == $pchomepay_geo_zone_id) { ?>
                                        <option value="<?php echo $geo_zone['geo_zone_id']; ?>"
                                                selected="selected"><?php echo $geo_zone['name']; ?></option>
                                        <?php } else { ?>
                                        <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                                        <?php } ?>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label"
                                       for="input-sort-order"><?php echo $entry_sort_order; ?></label>
                                <div class="col-sm-10">
                                    <input type="text" name="pchomepay_sort_order"
                                           value="<?php echo $pchomepay_sort_order; ?>"
                                           placeholder="<?php echo $entry_sort_order; ?>" id="input-sort-order"
                                           class="form-control"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php echo $footer; ?>