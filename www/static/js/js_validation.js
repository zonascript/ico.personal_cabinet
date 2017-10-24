function validator_validate_form(formname, errors) {
    validator_clean_errors(formname);
    var errorsList = validator_collect_errors(formname, errors);
    for (var name in errorsList) {
        var errorsName = name.replace(/\[/g, "_").replace(/\]/g, "");
        var $errors = $('#' + errorsName + '_errors');
        var errorsItems = errorsList[name];
        errorsItems.forEach(function(error){
            $errors.css({'display': ''});
            $errors.append($('<li/>').text(error));
        });
    }
}

function validator_collect_errors(namespace, errors)
{
    var result = {};
    for (var name in errors) {
        var joined = namespace + "[" + name + "]";
        if (errors[name] instanceof Array) {
            result[joined] = errors[name];
        } else {
            var innerResult = validator_collect_errors(joined, errors[name]);
            for (var innerName in innerResult) {
                var innerErrors = innerResult[innerName];
                result[innerName] = innerErrors;
            }
        }
    }
    return result;
}

function validator_clean_errors(formname) {
    $( ".error[id^='" + formname + "']" ).html("").css({'display': 'none'});
}

/**
 *  Можно указать селектор к которому применится класс 'success' после успешной обработки формы
 *  По-умолчанию класс 'success' добавится непосредственно форме
 *
 *  data-success-selector=".success-holder"
 *
 *  <form action="{% url 'namespace:route' %}" method="post" data-ajax-form="ContactForm">
 *      ...
 *  </form>
 */
$(function(){
    $(document).on('submit', '[data-ajax-form]', function(e){
        e.preventDefault();
        var $form = $(this);
        var classes = $form.data('ajax-form').split(',');
        var successSelector = $form.data('success-selector');
        var $success = successSelector ? $(successSelector) : $form;

        $.ajax({
            url: $form.attr('action'),
            data: $form.serialize(),
            type: $form.attr('method'),
            dataType: 'json',
            success: function(data) {
                var errors = {};
                if (data.errors) {
                    errors = data.errors;
                }
                for(var i in classes) {
                    var cls = classes[i];
                    if (errors[cls]) {
                        validator_validate_form(cls, data.errors[cls]);
                    } else {
                        validator_clean_errors(cls);
                    }
                }
                if (data.state == 'success') {
                    $success.addClass('success');
                }
            }
        });

        return false;
    });
});

/**
 Пример action:

 public function actionContact()
 {
     if (!$this->request->getIsAjax() || !$this->request->getIsPost()) {
         $this->error(404);
     }
     $form = new ContactForm();
     $data = [
         'state' => 'success'
     ];
     if (!($form->populate($_POST)->isValid() && $form->save())) {
         $data = [
             'state' => 'error',
             'errors' => [
                 $form->classNameShort() => $form->getErrors()
             ]
         ];
     }
     echo $this->json($data);
 }

 */