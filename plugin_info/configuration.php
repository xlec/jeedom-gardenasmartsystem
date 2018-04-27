<?php

require_once dirname(__FILE__).'/../../../core/php/core.inc.php';

include_file('core', 'authentification', 'php');

if (!isConnect('admin')) {
  throw new Exception('{{401 - Refused access}}');
}
?>
<form class="form-horizontal">
  <fieldset>
           <div class="form-group">
              <label class="col-lg-4 control-label">{{Username : }}</label>
              <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="username" style="margin-top:5px" placeholder="Username"/>
              </div>
            </div>

            <div class="form-group">
              <label class="col-lg-4 control-label">{{Password : }}</label>
              <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="password" style="margin-top:5px" placeholder="Password" type="password"/>
              </div>
            </div>
  </fieldset>
</form>

<script>
    function gardenasmartsystem_postSaveConfiguration(){
      $.ajax({// fonction permettant de faire de l'ajax
      type: "POST", // methode de transmission des données au fichier php
      url: "plugins/gardenasmartsystem/core/ajax/gardenasmartsystem.ajax.php", // url du fichier php
      data: {
        action: "postSave",
      },
      dataType: 'json',
      error: function (request, status, error) {
        handleAjaxError(request, status, error);
      },
      success: function (data) { // si l'appel a bien fonctionné
      if (data.state != 'ok') {
        $('#div_alert').showAlert({message: data.result, level: 'danger'});
        return;
      }
    }
  });
}

</script>
